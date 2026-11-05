@extends('emails.layouts.base')
@section('subject', 'Invoice #' . ($invoice->invoice_number ?? 'INV-0001') . ' — ' . config('branding.name'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🧾</p>
    <h1>Payment receipt</h1>
    <p class="lead">Invoice #{{ $invoice->invoice_number ?? 'INV-0001' }} · {{ $invoice->created_at?->format('M d, Y') ?? now()->format('M d, Y') }}</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Thank you for your payment. Here is your receipt for the billing period.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Invoice #</td>
            <td class="data-value">{{ $invoice->invoice_number ?? 'INV-0001' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Date</td>
            <td class="data-value">{{ $invoice->created_at?->format('M d, Y') ?? now()->format('M d, Y') }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Billing period</td>
            <td class="data-value">
                {{ $invoice->period_start?->format('M d, Y') ?? '—' }} – {{ $invoice->period_end?->format('M d, Y') ?? '—' }}
            </td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Plan</td>
            <td class="data-value">{{ $plan->name ?? '—' }} ({{ ucfirst($subscription->billing_cycle ?? 'monthly') }})</td>
        </tr>
    </table>

    <hr>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label" style="font-size:14px;">Subtotal</td>
            <td class="data-value">${{ number_format($invoice->subtotal ?? $invoice->amount ?? 0, 2) }}</td>
        </tr>
        @if(isset($invoice->tax) && $invoice->tax > 0)
        <tr class="data-row">
            <td class="data-label" style="font-size:14px;">Tax ({{ $invoice->tax_rate ?? '' }}%)</td>
            <td class="data-value">${{ number_format($invoice->tax, 2) }}</td>
        </tr>
        @endif
        <tr class="data-row">
            <td class="data-label" style="font-size:16px;font-weight:700;color:#ffffff;">Total</td>
            <td class="data-value" style="font-size:16px;font-weight:700;color:#ffffff;">${{ number_format($invoice->amount ?? 0, 2) }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Status</td>
            <td class="data-value"><span class="badge badge-success">Paid ✓</span></td>
        </tr>
    </table>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/settings/billing" class="btn btn-outline">View Invoice History →</a>
    </div>

    <p class="text-muted">If you have any questions about this invoice, contact us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>
    <p class="text-muted">For VAT/tax invoices, visit your <a href="{{ config('app.url') }}/settings/billing" class="link">billing page</a> to add your tax details.</p>
</div>
@endsection
