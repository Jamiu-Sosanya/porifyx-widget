# PoriFyx Widget

Proprietary software of PoriFyx. All rights reserved.

PoriFyx Widget is an AI chat platform. Businesses train an assistant on their own content, embed it on their website with one script tag, and use it to answer questions, qualify leads and hand conversations to human agents.

Stack: Laravel 12, Tailwind CSS 4, Alpine.js, Vite. Runs on standard PHP hosting.

## Features

- **Embeddable AI widget**: streaming replies, voice input, file upload, emoji, light and dark themes, proactive messages, offline form, CSAT survey.
- **Five AI providers**: OpenAI, Anthropic Claude, Google Gemini, Groq and OpenRouter. Each widget picks its own provider and model.
- **Automatic failover**: if a widget's provider returns an error, the request is retried once on the backup provider (`AI_FALLBACK_PROVIDER`).
- **Knowledge base**: train from URLs, PDFs, plain text and Q&A pairs, with version history and chunked retrieval.
- **Lead capture and scoring**: cold, warm and hot leads, notes, segmentation, export to Excel and PDF.
- **Live inbox**: agent takeover, canned responses, AI summaries, sentiment analysis and suggested replies.
- **Channels**: WhatsApp, Telegram, Facebook, Instagram, X, Slack, Discord, SMS (Twilio) and email.
- **Automations**: trigger actions on lead score, keywords or conversation events.
- **A/B testing** of widget variants.
- **Teams** with role-based access.
- **Billing**: Stripe subscriptions, plan limits and invoices.
- **Signed webhooks**: the `PFX-Signature` header carries an HMAC-SHA256 signature. Delivery logs are kept for each widget.
- **Security**: TOTP two-factor login, admin IP allowlist, per-widget rate limits and domain restriction.
- **App features**: installable PWA, web push (VAPID), demo mode, web installer and a full admin panel.

## Requirements

- PHP 8.2+ with the `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd` and `zip` extensions
- MySQL 8 or MariaDB 10.6+ (SQLite works for local development)
- Composer 2, Node.js 18+
- An API key for at least one AI provider

## Installation

### Web installer

1. Upload the files and point the web root at `public/`.
2. Open `https://yourdomain.com/install/` and complete the wizard. It writes `.env`, runs the migrations and creates the admin account.

### Manual

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci && npm run build
```

Then set `INSTALLATION_COMPLETE=true` in `.env`.

## Key configuration

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`, `GROQ_API_KEY`, `OPENROUTER_API_KEY` | AI provider keys. Admins can also set them in **Admin → Settings → AI**. |
| `AI_FALLBACK_PROVIDER` | Backup provider for failover. Leave blank to turn failover off. |
| `BRAND_*` | Name, tagline, logo, colour and social links |
| `VAPID_*` | Web push keys (`php artisan webpush:vapid`) |
| `DEMO_MODE` | Blocks all write operations |
| `ADMIN_IP_WHITELIST` | Comma-separated IP addresses that may open `/admin` |

## Background jobs

Training, webhooks and push notifications run on the queue. Run a worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

On shared hosting, a cron job every minute is enough:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
* * * * * php /path/to/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

## Embedding a widget

Copy the snippet from **Widgets → Embed**:

```html
<script src="https://yourdomain.com/widget.js" data-widget="WIDGET-UUID" async></script>
```

## Tests

```bash
php artisan test
```

## Documentation

The HTML guides in `Docs/` cover installation, users, admins, developers, the PWA and troubleshooting.

## Support

Email info@porifyx.com.
