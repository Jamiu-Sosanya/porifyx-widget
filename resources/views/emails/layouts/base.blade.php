<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<title>@yield('subject', config('branding.name'))</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
/* Reset */
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #0d0f14; }
table { border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; }
img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
a { color: inherit; }

/* Base layout */
.email-body    { background-color: #0d0f14; padding: 40px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
.email-wrapper { max-width: 600px; margin: 0 auto; }
.email-header  { text-align: center; padding-bottom: 24px; }
.brand-name    { font-size: 22px; font-weight: 800; color: #ffffff; text-decoration: none; letter-spacing: -0.5px; }
.brand-dot     { color: #2f5ee8; }

/* Card */
.card          { background-color: #161920; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden; }
.card-hero     { background: linear-gradient(135deg, #1e1b4b 0%, #161920 65%); padding: 36px 40px 28px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.card-body     { padding: 32px 40px; }
.card-footer   { padding: 20px 40px; border-top: 1px solid rgba(255,255,255,0.06); }

/* Typography */
h1  { font-size: 24px; font-weight: 700; color: #ffffff; line-height: 1.3; margin: 0 0 8px; letter-spacing: -0.3px; }
h2  { font-size: 18px; font-weight: 600; color: #ffffff; margin: 0 0 12px; }
p   { font-size: 15px; line-height: 1.7; color: #9ca3af; margin: 0 0 16px; }
p.lead { font-size: 16px; color: #d1d5db; }
p:last-child { margin-bottom: 0; }
strong { color: #e5e7eb; font-weight: 600; }
.text-muted   { color: #6b7280; font-size: 13px; }
.text-small   { font-size: 13px; }
.text-white   { color: #ffffff !important; }
.text-center  { text-align: center; }

/* Links */
a.link { color: #6b8ff2; text-decoration: none; }
a.link:hover { text-decoration: underline; }

/* Buttons */
.btn-wrap { text-align: center; margin: 28px 0; }
.btn {
    display: inline-block;
    padding: 14px 36px;
    background: linear-gradient(135deg, #2f5ee8, #0891b2);
    color: #ffffff !important;
    font-size: 15px;
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
    letter-spacing: -0.1px;
    border: none;
}
.btn-success  { background: linear-gradient(135deg, #059669, #047857); }
.btn-danger   { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.btn-warning  { background: linear-gradient(135deg, #d97706, #b45309); }
.btn-outline  { background: transparent; color: #6b8ff2 !important; border: 1px solid rgba(47, 94, 232,0.4); }

/* Divider */
hr { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }

/* Data rows (invoice-style) */
.data-table   { width: 100%; margin: 16px 0; }
.data-row     { border-bottom: 1px solid rgba(255,255,255,0.04); }
.data-row td  { padding: 10px 0; font-size: 13px; vertical-align: top; }
.data-label   { color: #6b7280; }
.data-value   { color: #e5e7eb; font-weight: 500; text-align: right; }
.data-row:last-child td { border-bottom: none; }

/* Badges */
.badge         { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; }
.badge-hot     { background: rgba(239,68,68,0.15);  color: #f87171; }
.badge-warm    { background: rgba(245,158,11,0.15); color: #fbbf24; }
.badge-cold    { background: rgba(14,165,233,0.15); color: #38bdf8; }
.badge-success { background: rgba(16,185,129,0.15); color: #34d399; }
.badge-danger  { background: rgba(239,68,68,0.15);  color: #f87171; }

/* Alert / info boxes */
.alert         { padding: 14px 16px; border-radius: 10px; margin: 20px 0; font-size: 13px; line-height: 1.6; }
.alert-info    { background: rgba(47, 94, 232,0.1);  border-left: 3px solid #2f5ee8; color: #a5b4fc; }
.alert-warning { background: rgba(245,158,11,0.1); border-left: 3px solid #f59e0b; color: #fcd34d; }
.alert-success { background: rgba(16,185,129,0.1); border-left: 3px solid #10b981; color: #6ee7b7; }
.alert-danger  { background: rgba(239,68,68,0.1);  border-left: 3px solid #ef4444; color: #fca5a5; }

/* Icon rows */
.feature-item  { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.feature-icon  { width: 36px; height: 36px; border-radius: 9px; background: rgba(47, 94, 232,0.15); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.feature-text  { font-size: 14px; color: #9ca3af; padding-top: 6px; line-height: 1.5; }
.feature-text strong { color: #e5e7eb; }

/* Stat boxes */
.stat-grid    { display: flex; gap: 12px; margin: 20px 0; }
.stat-box     { flex: 1; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 14px 16px; text-align: center; }
.stat-value   { font-size: 22px; font-weight: 700; color: #ffffff; display: block; }
.stat-label   { font-size: 11px; color: #6b7280; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }

/* Footer */
.email-footer-text { font-size: 12px; color: #4b5563; line-height: 1.7; text-align: center; }
.footer-links a    { color: #6b7280; font-size: 12px; margin: 0 8px; text-decoration: none; }
.footer-links a:hover { color: #9ca3af; }
.footer-copyright  { font-size: 11px; color: #374151; margin-top: 16px; text-align: center; }

/* Responsive */
@media screen and (max-width: 600px) {
    .card-hero, .card-body, .card-footer { padding: 24px 20px !important; }
    h1 { font-size: 21px !important; }
    .stat-grid { flex-wrap: wrap; }
    .stat-box  { min-width: calc(50% - 6px); }
    .btn       { padding: 13px 24px !important; font-size: 14px !important; }
}
</style>
</head>
<body>
<div class="email-body">
    <div class="email-wrapper">

        {{-- Brand header --}}
        <div class="email-header">
            @if(config('branding.logo_url'))
            <a href="{{ config('app.url') }}"><img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.name') }}" height="36" style="height:36px;"></a>
            @else
            <a href="{{ config('app.url') }}" class="brand-name" style="text-decoration:none;">
                {{ config('branding.name') }}<span class="brand-dot">.</span>
            </a>
            @endif
        </div>

        {{-- Email card --}}
        <div class="card">
            @yield('content')

            {{-- Standard footer --}}
            <div class="card-footer">
                <p class="email-footer-text">
                    You're receiving this email because you have an account at
                    <a href="{{ config('app.url') }}" style="color:#6b7280;">{{ config('branding.name') }}</a>.
                </p>
                <p class="footer-links" style="text-align:center; margin-top:10px;">
                    <a href="{{ config('app.url') }}/settings/notifications">Email Settings</a>
                    <a href="{{ config('branding.privacy_url') ?: config('app.url').'/privacy' }}">Privacy</a>
                    <a href="{{ config('branding.terms_url') ?: config('app.url').'/terms' }}">Terms</a>
                    @if(config('branding.support_url'))
                    <a href="{{ config('branding.support_url') }}">Support</a>
                    @endif
                </p>
            </div>
        </div>

        {{-- Copyright --}}
        <p class="footer-copyright">
            {{ config('branding.copyright') ?: '© ' . date('Y') . ' ' . config('branding.name') . '. All rights reserved.' }}
            @if(config('mail.from.address'))
             · {{ config('mail.from.address') }}
            @endif
        </p>
    </div>
</div>
</body>
</html>
