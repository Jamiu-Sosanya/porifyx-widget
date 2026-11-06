<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Voice fallback for browsers without built-in speech recognition (Firefox, Brave):
 * the widget records a short clip and we transcribe it with Whisper (Groq first, then OpenAI).
 */
class TranscribeController extends Controller
{
    public static function provider(): ?array
    {
        if (filled(config('ai.groq.key'))) return ['https://api.groq.com/openai/v1/audio/transcriptions', config('ai.groq.key'), 'whisper-large-v3-turbo'];
        if (filled(config('ai.openai.key'))) return ['https://api.openai.com/v1/audio/transcriptions', config('ai.openai.key'), 'whisper-1'];

        return null;
    }

    public function __invoke(Request $request, string $widgetUuid): JsonResponse
    {
        $widget = Widget::forPublicApi($widgetUuid);
        abort_unless($widget->voice_input_enabled && $widget->user->canUse('messages'), 403);
        $p = self::provider();
        abort_unless($p, 503, 'Voice transcription is not configured.');
        $request->validate(['audio' => 'required|file|max:8192', 'lang' => 'nullable|string|max:10']);

        $file = $request->file('audio');
        $bytes = file_get_contents($file->getRealPath());
        if ($bytes === '' || $bytes === false) return response()->json(['error' => 'The recording was empty. Please try again.'], 422);
        try {
            $res = Http::timeout(30)->withToken($p[1])
                ->attach('file', $bytes, 'speech.' . ($file->getClientOriginalExtension() ?: 'webm'))
                ->post($p[0], array_filter(['model' => $p[2], 'language' => substr((string) $request->input('lang'), 0, 2) ?: null, 'response_format' => 'json']));
            if (! $res->successful()) {
                Log::warning('Transcription failed', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 300)]);
                return response()->json(['error' => 'Could not understand the recording.'], 422);
            }

            return response()->json(['text' => trim((string) $res->json('text'))]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Could not understand the recording.'], 422);
        }
    }
}
