<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemAlert;

class AlertingService
{
    /**
     * Check for conversation volume anomalies.
     */
    public function checkConversationAnomalies(): void
    {
        $widgets = Widget::all();

        foreach ($widgets as $widget) {
            $this->checkWidgetConversationAnomaly($widget);
        }
    }

    /**
     * Check for lead quality anomalies.
     */
    public function checkLeadQualityAnomalies(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->checkUserLeadQualityAnomaly($user);
        }
    }

    /**
     * Check for A/B test anomalies.
     */
    public function checkAbTestAnomalies(): void
    {
        $widgets = Widget::with('abTests')->get();

        foreach ($widgets as $widget) {
            foreach ($widget->abTests as $abTest) {
                $this->checkAbTestAnomaly($abTest);
            }
        }
    }

    /**
     * Check conversation volume for a specific widget.
     */
    protected function checkWidgetConversationAnomaly(Widget $widget): void
    {
        $cacheKey = "conversation_volume_{$widget->id}";

        // Get current 24h conversation count
        $currentCount = Conversation::where('widget_id', $widget->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Get historical average (last 7 days, excluding today)
        $historicalAverage = Conversation::where('widget_id', $widget->id)
            ->whereBetween('created_at', [now()->subDays(8), now()->subDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->avg('count') ?? 0;

        // Store current count for future comparisons
        $previousCounts = Cache::get($cacheKey, []);
        $previousCounts[] = $currentCount;
        $previousCounts = array_slice($previousCounts, -7); // Keep last 7 days
        Cache::put($cacheKey, $previousCounts, now()->addDays(1));

        // Check for significant drop (more than 50% below historical average)
        if ($historicalAverage > 10 && $currentCount < $historicalAverage * 0.5) {
            $this->sendAlert($widget->user, 'conversation_volume_drop', [
                'widget_name' => $widget->name,
                'current_count' => $currentCount,
                'historical_average' => round($historicalAverage, 1),
                'drop_percentage' => round((1 - $currentCount / $historicalAverage) * 100, 1),
            ]);
        }

        // Check for unusual spike (more than 3x historical average)
        if ($historicalAverage > 0 && $currentCount > $historicalAverage * 3) {
            $this->sendAlert($widget->user, 'conversation_volume_spike', [
                'widget_name' => $widget->name,
                'current_count' => $currentCount,
                'historical_average' => round($historicalAverage, 1),
                'spike_percentage' => round(($currentCount / $historicalAverage - 1) * 100, 1),
            ]);
        }
    }

    /**
     * Check lead quality for a specific user.
     */
    protected function checkUserLeadQualityAnomaly(User $user): void
    {
        $cacheKey = "lead_quality_{$user->id}";

        // Get current lead quality metrics
        $leads = Lead::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($leads->isEmpty()) {
            return;
        }

        $currentMetrics = [
            'average_score' => $leads->avg('score'),
            'hot_lead_percentage' => $leads->where('temperature', 'hot')->count() / $leads->count() * 100,
            'grade_a_percentage' => $leads->where('quality_grade', 'A')->count() / $leads->count() * 100,
        ];

        // Get historical metrics
        $historicalMetrics = Cache::get($cacheKey, $currentMetrics);

        // Update cache with current metrics
        Cache::put($cacheKey, $currentMetrics, now()->addDays(7));

        // Check for quality drop
        if ($historicalMetrics['average_score'] > 50 &&
            $currentMetrics['average_score'] < $historicalMetrics['average_score'] * 0.7) {
            $this->sendAlert($user, 'lead_quality_drop', [
                'current_average' => round($currentMetrics['average_score'], 1),
                'historical_average' => round($historicalMetrics['average_score'], 1),
                'drop_percentage' => round((1 - $currentMetrics['average_score'] / $historicalMetrics['average_score']) * 100, 1),
                'lead_count' => $leads->count(),
            ]);
        }

        // Check for hot lead percentage drop
        if ($historicalMetrics['hot_lead_percentage'] > 10 &&
            $currentMetrics['hot_lead_percentage'] < $historicalMetrics['hot_lead_percentage'] * 0.5) {
            $this->sendAlert($user, 'hot_lead_drop', [
                'current_percentage' => round($currentMetrics['hot_lead_percentage'], 1),
                'historical_percentage' => round($historicalMetrics['hot_lead_percentage'], 1),
                'lead_count' => $leads->count(),
            ]);
        }
    }

    /**
     * Check A/B test for anomalies.
     */
    protected function checkAbTestAnomaly($abTest): void
    {
        if (!$abTest->is_active) {
            return;
        }

        $totalImpressions = $abTest->impressions_a + $abTest->impressions_b;
        $totalConversions = $abTest->conversations_a + $abTest->conversations_b;

        // Only check if we have enough data
        if ($totalImpressions < 100 || $totalConversions < 10) {
            return;
        }

        $conversionRateA = $abTest->conversations_a / max($abTest->impressions_a, 1);
        $conversionRateB = $abTest->conversations_b / max($abTest->impressions_b, 1);

        // Check for significant difference (>50% difference in conversion rates)
        $rateDifference = abs($conversionRateA - $conversionRateB);
        $averageRate = ($conversionRateA + $conversionRateB) / 2;

        if ($averageRate > 0 && $rateDifference / $averageRate > 0.5) {
            $winner = $conversionRateA > $conversionRateB ? 'A' : 'B';
            $loser = $conversionRateA > $conversionRateB ? 'B' : 'A';

            $this->sendAlert($abTest->widget->user, 'ab_test_significant_difference', [
                'test_name' => $abTest->name,
                'variant_a_rate' => round($conversionRateA * 100, 2) . '%',
                'variant_b_rate' => round($conversionRateB * 100, 2) . '%',
                'difference' => round($rateDifference * 100, 2) . '%',
                'recommended_winner' => $winner,
                'total_impressions' => $totalImpressions,
            ]);
        }
    }

    /**
     * Send an alert notification.
     */
    protected function sendAlert(User $user, string $alertType, array $data): void
    {
        // Prevent duplicate alerts within 24 hours
        $alertKey = "alert_{$alertType}_{$user->id}_" . md5(serialize($data));
        if (Cache::has($alertKey)) {
            return;
        }
        Cache::put($alertKey, true, now()->addDay());

        // Log the alert
        Log::info("Alert triggered: {$alertType}", [
            'user_id' => $user->id,
            'data' => $data,
        ]);

        // Send notification
        try {
            Notification::send($user, new SystemAlert($alertType, $data));
        } catch (\Exception $e) {
            Log::error("Failed to send alert notification: {$e->getMessage()}", [
                'user_id' => $user->id,
                'alert_type' => $alertType,
            ]);
        }
    }

    /**
     * Run all anomaly checks.
     */
    public function runAllChecks(): void
    {
        $this->checkConversationAnomalies();
        $this->checkLeadQualityAnomalies();
        $this->checkAbTestAnomalies();
    }
}