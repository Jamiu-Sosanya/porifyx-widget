/**
 * PoriFyx Widget v1.0 — Embeddable Widget Script
 * Features: Multi-theme, Markdown, Smart Suggestions, Proactive Messaging,
 *           Sound Notifications, Offline Form, Emoji Reactions, Timestamps,
 *           Unread Badge, Auto-language, Conversation Starters
 *
 * Usage: <script src="/widget.js" data-widget-id="uuid" defer></script>
 */
(function () {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────────
    let apiBase         = '';
    let config          = null;
    let conversationUuid = null;
    let fingerprint     = null;
    let isOpen          = false;
    let isLoading       = false;
    let handoffDone     = false;
    let aiTurnCount     = 0;
    let unreadCount     = 0;
    let proactiveShown  = false;
    let pageCount       = parseInt(sessionStorage.getItem('pfx_pages') || '0') + 1;
    let pageStartTime   = Date.now();
    let proactiveTimer  = null;
    let currentTheme    = 'dark';
    let pollTimer       = null;
    let lastMessageUuid = null;

    sessionStorage.setItem('pfx_pages', pageCount);

    // ── Audio (notification beep) ─────────────────────────────────────────────
    let audioCtx = null;
    function playNotificationSound() {
        if (!config || !config.sound_enabled) return;
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc    = audioCtx.createOscillator();
            const gain   = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type      = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
            osc.start(audioCtx.currentTime);
            osc.stop(audioCtx.currentTime + 0.3);
        } catch (e) { /* silent fail */ }
    }

    // ── Themes ────────────────────────────────────────────────────────────────
    const THEMES = {
        dark: {
            bg: '#0f0f13', surface: 'rgba(255,255,255,0.06)', border: 'rgba(255,255,255,0.08)',
            text: 'rgba(255,255,255,0.85)', textDim: 'rgba(255,255,255,0.45)',
            inputBg: 'rgba(255,255,255,0.04)', inputBorder: 'rgba(255,255,255,0.1)',
            scrollbar: 'rgba(255,255,255,0.1)', visitorBubble: null, aiBubble: 'rgba(255,255,255,0.06)',
            aiBubbleBorder: 'rgba(255,255,255,0.06)',
        },
        light: {
            bg: '#ffffff', surface: '#f3f4f6', border: '#e5e7eb',
            text: '#111827', textDim: '#6b7280',
            inputBg: '#f9fafb', inputBorder: '#d1d5db',
            scrollbar: '#d1d5db', visitorBubble: null, aiBubble: '#f3f4f6',
            aiBubbleBorder: '#e5e7eb',
        },
        minimal: {
            bg: '#fafafa', surface: '#ffffff', border: '#efefef',
            text: '#222222', textDim: '#888888',
            inputBg: '#ffffff', inputBorder: '#e0e0e0',
            scrollbar: '#e0e0e0', visitorBubble: null, aiBubble: '#ffffff',
            aiBubbleBorder: '#efefef',
        },
        bold: {
            bg: '#1a0533', surface: 'rgba(255,255,255,0.08)', border: 'rgba(255,255,255,0.1)',
            text: 'rgba(255,255,255,0.9)', textDim: 'rgba(255,255,255,0.5)',
            inputBg: 'rgba(255,255,255,0.05)', inputBorder: 'rgba(255,255,255,0.12)',
            scrollbar: 'rgba(255,255,255,0.12)', visitorBubble: null, aiBubble: 'rgba(255,255,255,0.07)',
            aiBubbleBorder: 'rgba(255,255,255,0.1)',
        },
        ocean: {
            bg: '#0c1e35', surface: 'rgba(56,189,248,0.08)', border: 'rgba(56,189,248,0.15)',
            text: 'rgba(255,255,255,0.9)', textDim: 'rgba(148,218,240,0.6)',
            inputBg: 'rgba(56,189,248,0.05)', inputBorder: 'rgba(56,189,248,0.2)',
            scrollbar: 'rgba(56,189,248,0.15)', visitorBubble: null, aiBubble: 'rgba(56,189,248,0.08)',
            aiBubbleBorder: 'rgba(56,189,248,0.15)',
        },
        nature: {
            bg: '#0f1f0f', surface: 'rgba(134,239,172,0.08)', border: 'rgba(134,239,172,0.12)',
            text: 'rgba(255,255,255,0.88)', textDim: 'rgba(134,239,172,0.6)',
            inputBg: 'rgba(134,239,172,0.04)', inputBorder: 'rgba(134,239,172,0.15)',
            scrollbar: 'rgba(134,239,172,0.12)', visitorBubble: null, aiBubble: 'rgba(134,239,172,0.07)',
            aiBubbleBorder: 'rgba(134,239,172,0.12)',
        },
    };

    function getTheme() {
        const t = (config && config.theme) ? config.theme : 'dark';
        return THEMES[t] || THEMES.dark;
    }

    // ── Utilities ─────────────────────────────────────────────────────────────
    function normalizeApiBase(url) {
        return String(url || '').replace(/\/+$/, '');
    }

    function generateFingerprint() {
        const nav = navigator;
        const raw = [nav.userAgent, nav.language, screen.width, screen.height, new Date().getTimezoneOffset()].join('|');
        let hash = 0;
        for (let i = 0; i < raw.length; i++) {
            hash = ((hash << 5) - hash) + raw.charCodeAt(i);
            hash |= 0;
        }
        return 'fp_' + Math.abs(hash).toString(36) + '_' + Date.now().toString(36);
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /**
     * Simple markdown renderer: bold, italic, code, links, lists, line breaks.
     */
    function renderMarkdown(text) {
        // The assistant ends a reply with [[BOOKING]] to offer the booking button.
        if (/\[\[BOOKING\]\]/.test(text)) { text = text.replace(/\s*\[\[BOOKING\]\]\s*/g, ''); addBookingChip(); }
        if (/\[\[MANAGE_BOOKING\]\]/.test(text)) { text = text.replace(/\s*\[\[MANAGE_BOOKING\]\]\s*/g, ''); addBookingChip('manage'); }
        text = text.replace(/\s*\[\[[A-Z]*\]?$/, ''); // hide a half-streamed marker
        let html = escapeHtml(text);
        // Code blocks
        html = html.replace(/```([\s\S]*?)```/g, '<pre class="pfx-pre"><code>$1</code></pre>');
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code class="pfx-code">$1</code>');
        // Bold
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Links
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="pfx-link">$1</a>');
        // Unordered list items
        html = html.replace(/^[-*] (.+)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*<\/li>\n?)+/g, function(m) { return '<ul class="pfx-ul">' + m + '</ul>'; });
        // Numbered list
        html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
        // Line breaks
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function timeAgo(dateStr) {
        const now  = Date.now();
        const then = new Date(dateStr).getTime();
        const diff = Math.floor((now - then) / 1000);
        if (diff < 60)   return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400)return Math.floor(diff / 3600) + 'h ago';
        return new Date(dateStr).toLocaleDateString();
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    async function api(method, path, body) {
        const opts = { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(apiBase + path, opts);
        if (!res.ok) throw new Error('API error: ' + res.status);
        return res.json();
    }

    // ── Public API ─────────────────────────────────────────────────────────────
    // Capture queue BEFORE overwriting window.pfx — otherwise .q is lost
    const _queue = (window.pfx && window.pfx.q) ? window.pfx.q.slice() : [];

    window.pfx = function (action, options) {
        if (action === 'init') init(options);
        if (action === 'open' && config) { if (!isOpen) toggleChat(); }
        if (action === 'close' && config) { if (isOpen) toggleChat(); }
        if (action === 'book' && config) openBooking();
    };

    // Replay any calls that were queued before the script loaded
    _queue.forEach(function (args) { window.pfx.apply(null, args); });

    // Script-tag install: <script src=".../widget.js" data-widget-id="UUID" async></script>
    const _cs = document.currentScript;
    if (_cs && _cs.getAttribute("data-widget-id") && !_queue.some(function (a) { return a[0] === "init"; })) {
        init({ widgetId: _cs.getAttribute("data-widget-id"), apiBase: _cs.getAttribute("data-api-base") || new URL(_cs.src).origin + "/api/widget" });
    }

    // ── Initialization ────────────────────────────────────────────────────────
    async function init(options) {
        options = options || {};
        const widgetId = options.widgetId || (document.currentScript && document.currentScript.getAttribute('data-widget-id'));
        if (!widgetId) return console.error('PFX: widgetId required');

        apiBase = normalizeApiBase(options.apiBase || '');
        if (!apiBase) {
            const sc = document.currentScript;
            if (sc && sc.getAttribute('data-api-base')) {
                apiBase = normalizeApiBase(sc.getAttribute('data-api-base'));
            }
        }
        if (!apiBase) apiBase = normalizeApiBase(window.location.origin + '/api/widget');

        fingerprint = localStorage.getItem('pfx_fp') || generateFingerprint();
        localStorage.setItem('pfx_fp', fingerprint);

        try {
            const data = await api('GET', '/' + widgetId + '/config?page_url=' + encodeURIComponent(window.location.href) + '&fingerprint=' + encodeURIComponent(fingerprint));
            config = data.widget;

            // Apply A/B test variant if active
            if (config.ab_test) {
                const variantConfig = config.ab_test.config;
                config = { ...config, ...variantConfig };
            }

            currentTheme = config.theme || 'dark';
            handoffDone = false;
            aiTurnCount = 0;
            render();
        } catch (err) {
            console.error('PFX: Failed to load widget config', err);
        }
    }

    // ── Rendering ─────────────────────────────────────────────────────────────
    function render() {
        injectStyles();

        const wrap = document.createElement('div');
        wrap.id = 'pfx-widget';
        wrap.innerHTML = buildLauncherHTML() + buildChatHTML();
        document.body.appendChild(wrap);

        bindEvents();

        if (config.auto_open) {
            setTimeout(function () { if (!isOpen) toggleChat(); }, (config.auto_open_delay || 3) * 1000);
        }

        setupProactiveMessaging();
    }

    // Launcher icon: preset SVG from the server, or the business's uploaded image.
    function launcherIconHTML() {
        const ic = config.launcher_icon || {};
        if (ic.type === 'image' && ic.url) {
            return '<img src="' + escapeAttr(ic.url) + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">';
        }
        return '<span style="display:flex;color:' + escapeAttr(config.primary_color || '#2f5ee8') + ';">' + (ic.svg || '') + '</span>';
    }

    // Phone layout: full screen (recommended) or a custom share of the screen; launcher size on phones.
    function mobileChatCSS() {
        const L = config.launcher_style || {};
        const ms = +L.m_size || 56;
        // The text label is hidden on phones, so the launcher is a plain icon button there.
        const launcher = '#pfx-launcher{width:' + ms + 'px !important;height:' + ms + 'px !important;padding:0 !important;gap:0 !important;}';
        if (L.m_mode === 'custom') {
            const w = Math.min(100, Math.max(70, +L.m_w || 94)), h = Math.min(100, Math.max(50, +L.m_h || 85));
            return launcher + '#pfx-chat{width:' + w + 'vw !important;height:' + h + 'svh !important;left:' + ((100 - w) / 2) + 'vw !important;right:auto !important;bottom:' + (h >= 100 ? 0 : 8) + 'px !important;top:auto !important;border-radius:' + (w >= 100 ? 0 : 16) + 'px !important;}';
        }
        return launcher + '#pfx-chat{width:100vw !important;height:100svh !important;left:0 !important;right:0 !important;top:0 !important;bottom:0 !important;border-radius:0 !important;}';
    }

    function buildLauncherHTML() {
        const L      = config.launcher_style || {};
        const pos    = config.position || 'bottom-right';
        const size   = +L.size || 60;
        const ox     = (L.offset_x != null ? +L.offset_x : 20) + 'px';
        const oy     = (L.offset_y != null ? +L.offset_y : 20) + 'px';
        const hPos   = pos.includes('left') ? 'left:' + ox + ';' : 'right:' + ox + ';';
        const vPos   = pos.includes('top') ? 'top:' + oy + ';' : 'bottom:' + oy + ';';
        const label  = (L.label || '').trim();
        const shape  = config.launcher_shape;
        const radius = shape === 'square' ? Math.round(size * 0.18) : shape === 'rounded' ? Math.round(size * 0.3)
                     : shape === 'custom' ? (+L.radius || 0) : 999;
        const bg     = L.bg_type === 'gradient'
            ? 'linear-gradient(' + (+L.angle || 135) + 'deg,' + (L.bg || config.primary_color) + ',' + (L.bg2 || config.secondary_color || config.primary_color) + ')'
            : (L.bg || config.primary_color);
        const shadow = { none: 'none', soft: '0 6px 18px -6px rgba(0,0,0,.35)', medium: '0 10px 28px -6px rgba(0,0,0,.45)', strong: '0 18px 44px -8px rgba(0,0,0,.6)' }[L.shadow || 'medium'];
        const border = +L.border_width ? (+L.border_width + 'px solid ' + (L.border_color || '#ffffff')) : 'none';
        const anim   = L.animation && L.animation !== 'none' ? 'pfx-anim-' + L.animation : '';
        const mHide  = config.show_on_mobile === false ? 'pfx-hide-mobile' : '';
        const dims   = label ? 'height:' + size + 'px;padding:0 ' + Math.round(size * 0.32) + 'px 0 ' + Math.round(size * 0.22) + 'px;gap:10px;'
                             : 'width:' + size + 'px;height:' + size + 'px;';

        return '<style>@keyframes pfxPulse{0%{box-shadow:0 0 0 0 var(--pfx-ring)}70%{box-shadow:0 0 0 16px transparent}100%{box-shadow:0 0 0 0 transparent}}@keyframes pfxPing{0%{box-shadow:0 0 0 0 var(--pfx-ring)}30%{box-shadow:0 0 0 14px transparent}100%{box-shadow:0 0 0 0 transparent}}@keyframes pfxGlow{from{box-shadow:0 0 6px 0 var(--pfx-ring)}to{box-shadow:0 0 26px 6px var(--pfx-ring)}}@keyframes pfxBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}@keyframes pfxFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}@keyframes pfxWiggle{0%,30%,100%{transform:rotate(0)}6%,18%{transform:rotate(-10deg)}12%,24%{transform:rotate(10deg)}}@keyframes pfxShake{0%,25%,100%{transform:translateX(0)}5%,15%{transform:translateX(-4px)}10%,20%{transform:translateX(4px)}}@keyframes pfxHeart{0%,40%,100%{transform:scale(1)}10%,30%{transform:scale(1.12)}20%{transform:scale(.97)}}.pfx-anim-pulse{animation:pfxPulse 2.2s infinite}.pfx-anim-ping{animation:pfxPing 3s infinite}.pfx-anim-glow{animation:pfxGlow 1.6s ease-in-out infinite alternate}.pfx-anim-bounce{animation:pfxBounce 2s ease-in-out infinite}.pfx-anim-float{animation:pfxFloat 3s ease-in-out infinite}.pfx-anim-wiggle{animation:pfxWiggle 2.6s ease-in-out infinite}.pfx-anim-shake{animation:pfxShake 3s ease-in-out infinite}.pfx-anim-heartbeat{animation:pfxHeart 1.8s ease-in-out infinite}@media(prefers-reduced-motion:reduce){[class*=pfx-anim-]{animation:none!important}}' +
            '#pfx-launcher .pfx-l-icon{width:' + Math.round(size * 0.5) + 'px;height:' + Math.round(size * 0.5) + 'px;display:flex;align-items:center;justify-content:center;flex-shrink:0}#pfx-launcher .pfx-l-icon svg{width:100%;height:100%}' +
            '@media(max-width:640px){#pfx-launcher .pfx-l-text{display:none}}</style>' +
            '<div id="pfx-launcher" role="button" tabindex="0" aria-label="' + escapeAttr(label || 'Open chat') + '" class="' + mHide + ' ' + anim + '" style="position:fixed;' + hPos + vPos + dims +
            '--pfx-ring:' + (L.bg || config.primary_color) + '99;border-radius:' + radius + 'px;background:' + bg + ';border:' + border + ';box-shadow:' + shadow + ';display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:99998;transition:transform .25s;box-sizing:border-box;" ' +
            'onmouseover="this.style.transform=\'scale(1.06)\'" onmouseout="this.style.transform=\'\'">' +
            '<span class="pfx-l-icon"' + (config.launcher_icon && config.launcher_icon.type === 'image' ? ' style="width:100%;height:100%;border-radius:inherit;overflow:hidden"' : '') + '>' + launcherIconHTML() + '</span>' +
            (label ? '<span class="pfx-l-text" style="color:' + escapeAttr(L.icon_color || '#ffffff') + ';font:600 14px/1 ' + (config.font_family || 'system-ui,sans-serif') + ';white-space:nowrap;">' + escapeHtml(label) + '</span>' : '') +
            '<div id="pfx-badge" style="position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;border-radius:10px;background:#ef4444;color:white;font-size:11px;font-weight:700;align-items:center;justify-content:center;padding:0 4px;box-shadow:0 2px 8px rgba(239,68,68,0.5);">0</div>' +
            '</div>';
    }

    function buildChatHTML() {
        const t     = getTheme();
        const pos   = config.position || 'bottom-right';
        const hPos  = pos.includes('left') ? 'left:20px;' : 'right:20px;';
        const vPos  = pos.includes('top')  ? 'top:20px;'  : 'bottom:20px;';
        const mHide = config.show_on_mobile === false ? 'pfx-hide-mobile' : '';
        const showHO = config.show_handoff_button !== false;
        const hoLabel = escapeHtml(config.handoff_button_label || 'Talk to a human');

        return '<div id="pfx-chat" class="' + mHide + '" style="position:fixed;' + hPos + vPos + 'width:min(' + (+(config.launcher_style || {}).chat_w || 380) + 'px,calc(100vw - 32px));height:min(' + (+(config.launcher_style || {}).chat_h || 600) + 'px,calc(100vh - 110px));display:none;flex-direction:column;border-radius:16px;overflow:hidden;box-shadow:0 30px 80px -20px rgba(11,19,36,0.55);z-index:99999;font-family:' + (config.font_family || '\'Plus Jakarta Sans\',system-ui,-apple-system,sans-serif') + ';background:' + t.bg + ';border:1px solid ' + t.border + ';opacity:0;transform:translateY(24px) scale(0.97);transition:opacity 0.35s cubic-bezier(0.4,0,0.2,1),transform 0.35s cubic-bezier(0.4,0,0.2,1);">' +

            // ── Header ──
            '<div id="pfx-header" style="padding:16px 16px 14px;background:linear-gradient(135deg,' + config.primary_color + ' 0%,' + (config.secondary_color || config.primary_color) + ' 100%);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0;">' +
            '<div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">' +
            (config.ai_avatar
                ? '<img src="' + escapeAttr(config.ai_avatar) + '" alt="" style="width:38px;height:38px;border-radius:12px;object-fit:cover;flex-shrink:0;border:2px solid rgba(255,255,255,0.3);">'
                : '<div style="width:38px;height:38px;border-radius:50%;background:white;color:' + config.primary_color + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;font-weight:800;">' + escapeHtml((config.ai_name || 'A').trim().charAt(0).toUpperCase()) + '</div>') +
            '<div style="min-width:0;">' +
            '<div style="font-weight:700;color:white;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(config.ai_name || 'AI Assistant') + '</div>' +
            '<div style="font-size:11px;color:rgba(255,255,255,0.75);display:flex;align-items:center;gap:4px;"><span style="width:7px;height:7px;border-radius:50%;background:#4ade80;display:inline-block;"></span>Online</div>' +
            '</div></div>' +
            '<div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">' +
            (showHO ? '<button id="pfx-handoff" type="button" style="background:rgba(255,255,255,0.18);border:none;border-radius:8px;padding:6px 10px;font-size:11px;font-weight:600;color:white;cursor:pointer;white-space:nowrap;transition:background 0.2s;">' + hoLabel + '</button>' : '') +
            (config.booking ? '<button id="pfx-book" type="button" title="' + escapeAttr(config.booking.label) + '" aria-label="' + escapeAttr(config.booking.label) + '" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:14px;transition:background 0.2s;">📅</button>' : '') +
            '<button id="pfx-theme-toggle" type="button" title="Toggle theme" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:14px;transition:background 0.2s;">🌙</button>' +
            '<button id="pfx-close" type="button" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:16px;line-height:1;transition:background 0.2s;">✕</button>' +
            '</div></div>' +

            // ── Booking panel ──
            '<div id="pfx-booking" class="pfx-bk" style="display:none;"></div>' +

            // ── Messages ──
            '<div id="pfx-messages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;"></div>' +

            // ── Conversation starters (shown only before first message) ──
            '<div id="pfx-starters" style="display:none;padding:0 14px 10px;"></div>' +

            // ── Quick replies ──
            '<div id="pfx-quick-replies" class="pfx-chips-wrap"></div>' +

            // ── CSAT ──
            '<div id="pfx-csat" style="display:none;border-top:1px solid ' + t.border + ';padding:12px 16px;"></div>' +

            // ── Offline form ──
            '<div id="pfx-offline" style="display:none;border-top:1px solid ' + t.border + ';padding:14px 16px;"></div>' +

            // ── Input area ──
            '<div style="padding:10px 14px 12px;border-top:1px solid ' + t.border + ';flex-shrink:0;background:' + t.bg + ';">' +
            '<div id="pfx-collect-form" style="display:none;margin-bottom:8px;"></div>' +
            '<div style="display:flex;gap:8px;align-items:flex-end;">' +
            '<textarea id="pfx-input" rows="1" placeholder="' + escapeAttr(config.placeholder_text || 'Type a message...') + '" style="flex:1;resize:none;border:1px solid ' + t.inputBorder + ';border-radius:12px;padding:10px 14px;font-size:13px;background:' + t.inputBg + ';color:' + t.text + ';outline:none;font-family:inherit;transition:border-color 0.2s,max-height 0.2s;max-height:120px;line-height:1.5;overflow-y:auto;"></textarea>' +
            '<button id="pfx-send" type="button" style="width:42px;height:42px;border-radius:12px;border:none;background:' + config.primary_color + ';color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity 0.2s,transform 0.15s;box-shadow:0 4px 12px rgba(0,0,0,0.2);">' +
            '<svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>' +
            '</div></div>' +

            (config.show_branding !== false ? '<div style="text-align:center;padding:4px;font-size:10px;color:' + t.textDim + ';background:' + t.bg + ';">Powered by <a href="https://porifyx.com" target="_blank" rel="noopener" style="color:inherit"><strong>PoriFyx</strong></a></div>' : '') +
            '</div>';
    }

    function bindEvents() {
        document.getElementById('pfx-launcher').addEventListener('click', toggleChat);
        document.getElementById('pfx-close').addEventListener('click', toggleChat);
        document.getElementById('pfx-send').addEventListener('click', sendMessage);

        const input = document.getElementById('pfx-input');
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        input.addEventListener('focus', function () {
            const t = getTheme();
            this.style.borderColor = config.primary_color;
        });
        input.addEventListener('blur', function () {
            const t = getTheme();
            this.style.borderColor = t.inputBorder;
        });

        const ho = document.getElementById('pfx-handoff');
        if (ho) ho.addEventListener('click', requestHandoff);

        const bookBtn = document.getElementById('pfx-book');
        if (bookBtn) bookBtn.addEventListener('click', function () { bk.fromAi = false; openBooking(); });

        const themeBtn = document.getElementById('pfx-theme-toggle');
        if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

        mountQuickReplies();
    }

    // ── Theme toggle ──────────────────────────────────────────────────────────
    function toggleTheme() {
        const themes = Object.keys(THEMES);
        const idx    = themes.indexOf(currentTheme);
        currentTheme = themes[(idx + 1) % themes.length];
        config.theme = currentTheme;
        applyTheme();
        const btn = document.getElementById('pfx-theme-toggle');
        if (btn) btn.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
    }

    function applyTheme() {
        const t     = getTheme();
        const chat  = document.getElementById('pfx-chat');
        if (!chat) return;

        chat.style.background = t.bg;
        chat.style.borderColor = t.border;

        // Re-inject styles
        const oldStyle = document.getElementById('pfx-styles');
        if (oldStyle) oldStyle.remove();
        injectStyles();

        // Update input
        const input = document.getElementById('pfx-input');
        if (input) {
            input.style.background   = t.inputBg;
            input.style.borderColor  = t.inputBorder;
            input.style.color        = t.text;
        }

        // Update offline/csat borders
        ['pfx-csat', 'pfx-offline'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.style.borderTopColor = t.border;
        });
    }

    // ── Styles injection ──────────────────────────────────────────────────────
    function injectStyles() {
        const t    = getTheme();
        const pri  = config.primary_color || '#2f5ee8';

        const css = `
#pfx-chat.pfx-open { opacity:1 !important; transform:translateY(0) scale(1) !important; }
#pfx-badge { display:none; }
#pfx-badge.visible { display:flex !important; }

.pfx-msg { display:flex; margin-bottom:2px; }
.pfx-msg-visitor { justify-content:flex-end; }
.pfx-msg-ai,.pfx-msg-agent,.pfx-msg-system { justify-content:flex-start; }

.pfx-bubble-wrap { display:flex; flex-direction:column; max-width:82%; }
.pfx-msg-visitor .pfx-bubble-wrap { align-items:flex-end; }
.pfx-msg-ai .pfx-bubble-wrap { align-items:flex-start; }

.pfx-bubble { padding:10px 14px; border-radius:18px; font-size:13px; line-height:1.55; word-wrap:break-word; }
.pfx-bubble-visitor { background:${pri}; color:white; border-bottom-right-radius:4px; }
.pfx-bubble-ai,.pfx-bubble-agent { background:${t.aiBubble}; color:${t.text}; border:1px solid ${t.aiBubbleBorder}; border-bottom-left-radius:4px; }
.pfx-bubble-system { background:transparent; color:${t.textDim}; font-size:11px; text-align:center; border:none; padding:4px 8px; }

.pfx-ts { font-size:10px; color:${t.textDim}; margin-top:3px; padding:0 4px; }

.pfx-reactions { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
.pfx-reaction-btn { font-size:14px; background:${t.surface}; border:1px solid ${t.border}; border-radius:8px; padding:2px 6px; cursor:pointer; transition:background 0.15s; }
.pfx-reaction-btn:hover { background:${t.aiBubble}; }
.pfx-add-reaction { font-size:12px; opacity:0.4; background:none; border:1px dashed ${t.border}; border-radius:8px; padding:2px 6px; cursor:pointer; }
.pfx-add-reaction:hover { opacity:0.8; }

.pfx-chips-wrap { display:flex; flex-wrap:wrap; gap:6px; padding:0 14px 8px; }
.pfx-chip { font-size:12px; padding:6px 12px; border-radius:999px; border:1px solid ${t.border}; background:${t.surface}; color:${t.text}; cursor:pointer; transition:background 0.2s,border-color 0.2s; white-space:nowrap; }
.pfx-chip:hover { background:${pri}; border-color:${pri}; color:white; }
.pfx-chip-starter { font-size:12px; padding:8px 14px; border-radius:12px; border:1px solid ${t.border}; background:${t.surface}; color:${t.text}; cursor:pointer; transition:all 0.2s; text-align:left; width:100%; margin-bottom:4px; }
.pfx-chip-starter:hover { background:${pri}; border-color:${pri}; color:white; }

.pfx-typing span { display:inline-block; width:7px; height:7px; border-radius:50%; background:${t.textDim}; margin:0 2px; animation:pfx-bounce 1.4s infinite ease-in-out; }
.pfx-typing span:nth-child(1) { animation-delay:0s; }
.pfx-typing span:nth-child(2) { animation-delay:0.2s; }
.pfx-typing span:nth-child(3) { animation-delay:0.4s; }
@keyframes pfx-bounce { 0%,80%,100%{transform:scale(0)} 40%{transform:scale(1)} }

.pfx-pre { background:rgba(0,0,0,0.2); border-radius:8px; padding:10px 12px; overflow-x:auto; font-size:12px; margin:6px 0; }
.pfx-code { background:rgba(0,0,0,0.15); border-radius:4px; padding:1px 5px; font-size:12px; font-family:monospace; }
.pfx-link { color:${pri}; text-decoration:underline; }
.pfx-ul { margin:6px 0 6px 16px; padding:0; }
.pfx-ul li { margin:2px 0; }

.pfx-offline-input { width:100%; padding:8px 12px; border-radius:10px; border:1px solid ${t.inputBorder}; background:${t.inputBg}; color:${t.text}; font-size:13px; outline:none; box-sizing:border-box; margin-bottom:8px; font-family:inherit; }
.pfx-offline-input:focus { border-color:${pri}; }
.pfx-btn-primary { background:${pri}; color:white; border:none; border-radius:10px; padding:10px 16px; font-size:13px; font-weight:600; cursor:pointer; width:100%; transition:opacity 0.2s; }
.pfx-btn-primary:hover { opacity:0.85; }
.pfx-bk { position:absolute; left:0; right:0; bottom:0; top:70px; z-index:5; display:flex; flex-direction:column; background:${t.bg}; color:${t.text}; }
.pfx-bk-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:12px 14px; border-bottom:1px solid ${t.border}; }
.pfx-bk-title { font-size:14px; font-weight:700; text-align:center; flex:1; }
.pfx-bk-icon { width:30px; height:30px; border-radius:8px; border:none; background:${t.surface}; color:${t.text}; cursor:pointer; font-size:16px; }
.pfx-bk-body { flex:1; overflow-y:auto; padding:14px; }
.pfx-bk-muted { font-size:12px; color:${t.textDim}; }
.pfx-bk-label { font-size:12px; font-weight:700; margin:4px 0 8px; }
.pfx-bk-card { display:block; width:100%; text-align:left; padding:12px 14px; margin-bottom:8px; border-radius:12px; border:1px solid ${t.border}; background:${t.surface}; color:${t.text}; cursor:pointer; font-family:inherit; font-size:13px; }
.pfx-bk-card:hover { border-color:${pri}; }
.pfx-bk-card-top { display:flex; justify-content:space-between; gap:8px; margin-bottom:3px; }
.pfx-bk-dates { display:flex; gap:6px; overflow-x:auto; padding-bottom:6px; margin-bottom:10px; }
.pfx-bk-date { flex:0 0 auto; width:54px; padding:7px 0; border-radius:12px; border:1px solid ${t.border}; background:${t.surface}; color:${t.text}; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:1px; font-size:10px; font-family:inherit; }
.pfx-bk-date strong { font-size:16px; }
.pfx-bk-date.on { background:${pri}; border-color:${pri}; color:white; }
.pfx-bk-slots { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
.pfx-bk-slots .pfx-bk-muted { grid-column:1/-1; }
.pfx-bk-slot { padding:9px 0; border-radius:10px; border:1px solid ${t.border}; background:${t.surface}; color:${t.text}; cursor:pointer; font-size:12px; font-weight:600; font-family:inherit; }
.pfx-bk-slot:hover { background:${pri}; border-color:${pri}; color:white; }
.pfx-bk-summary { display:flex; flex-direction:column; gap:2px; padding:10px 12px; margin-bottom:10px; border-radius:12px; background:${t.surface}; font-size:12px; }
.pfx-bk-error { font-size:12px; color:#ef4444; margin-bottom:8px; }
.pfx-bk-done { display:flex; flex-direction:column; align-items:center; text-align:center; gap:4px; padding:24px 8px; font-size:13px; }
.pfx-bk-tick { width:52px; height:52px; border-radius:50%; background:${pri}; color:white; display:flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:8px; }
.pfx-bk-link { margin-top:10px; font-size:12px; font-weight:600; color:${pri}; }

.pfx-csat-stars button { font-size:22px; background:none; border:none; cursor:pointer; padding:2px 4px; transition:transform 0.15s; }
.pfx-csat-stars button:hover { transform:scale(1.25); }

.pfx-proactive { position:fixed; z-index:99997; max-width:280px; background:${t.bg === '#ffffff' || t.bg === '#fafafa' ? '#1a1a2e' : t.bg}; color:white; border-radius:16px; padding:16px; box-shadow:0 16px 50px rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); animation:pfx-slide-in 0.4s cubic-bezier(0.4,0,0.2,1); }
@keyframes pfx-slide-in { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

#pfx-messages::-webkit-scrollbar { width:4px; }
#pfx-messages::-webkit-scrollbar-thumb { background:${t.scrollbar}; border-radius:4px; }
#pfx-messages::-webkit-scrollbar-track { background:transparent; }

@media(max-width:480px) {
  .pfx-hide-mobile { display:none !important; }
  ${mobileChatCSS()}
}
        `;

        const style = document.createElement('style');
        style.id    = 'pfx-styles';
        style.textContent = css;
        document.head.appendChild(style);
    }

    // ── Conversation starters ─────────────────────────────────────────────────
    function mountStarters() {
        const el = document.getElementById('pfx-starters');
        if (!el) return;
        const starters = config.conversation_starters || [];
        if (!starters.length) { el.style.display = 'none'; return; }

        el.style.display = 'block';
        el.innerHTML = starters.map(function (text) {
            return '<button type="button" class="pfx-chip-starter" data-text="' + escapeAttr(text) + '">' + escapeHtml(text) + '</button>';
        }).join('');

        el.querySelectorAll('.pfx-chip-starter').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById('pfx-input');
                input.value = btn.getAttribute('data-text') || '';
                el.style.display = 'none';
                sendMessage();
            });
        });
    }

    function mountQuickReplies(replies) {
        const el = document.getElementById('pfx-quick-replies');
        if (!el) return;
        const list = replies || config.suggested_replies || [];
        if (!list.length) { el.innerHTML = ''; return; }

        el.innerHTML = list.map(function (text) {
            return '<button type="button" class="pfx-chip" data-text="' + escapeAttr(text) + '">' + escapeHtml(text) + '</button>';
        }).join('');

        el.querySelectorAll('.pfx-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById('pfx-input');
                input.value = btn.getAttribute('data-text') || '';
                el.innerHTML = '';
                sendMessage();
            });
        });
    }

    // ── Chat open/close ───────────────────────────────────────────────────────
    async function toggleChat() {
        isOpen = !isOpen;
        const chat     = document.getElementById('pfx-chat');
        const launcher = document.getElementById('pfx-launcher');
        const badge    = document.getElementById('pfx-badge');

        if (isOpen) {
            chat.style.display = 'flex';
            launcher.style.display = 'none';
            setTimeout(function () { chat.classList.add('pfx-open'); }, 10);

            // Clear unread badge
            unreadCount = 0;
            if (badge) { badge.textContent = '0'; badge.classList.remove('visible'); }

            if (!conversationUuid) await startConversation();
            startPolling();
        } else {
            stopPolling();
            chat.classList.remove('pfx-open');
            setTimeout(function () {
                chat.style.display = 'none';
                launcher.style.display = 'flex';
            }, 350);
        }
    }

    // ── Start conversation ────────────────────────────────────────────────────
    async function startConversation() {
        try {
            const data = await api('POST', '/' + config.uuid + '/conversations', {
                fingerprint:  fingerprint,
                page_url:     window.location.href,
                referrer:     document.referrer || undefined,
                timezone:     Intl.DateTimeFormat().resolvedOptions().timeZone,
                device_type:  /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
            });

            conversationUuid = data.conversation_uuid;
            aiTurnCount      = 0;

            const msgContainer = document.getElementById('pfx-messages');
            msgContainer.innerHTML = '';

            data.messages.forEach(function (msg) {
                appendMessage(msg.role, msg.content, null, msg.created_at, msg.smart_suggestions || []);
            });

            // Track last seen message so polling only fetches new ones
            if (data.messages.length > 0) {
                lastMessageUuid = data.messages[data.messages.length - 1].uuid;
            }

            // Show starters if no messages beyond welcome
            if (data.messages.length <= 1) mountStarters();
            else mountQuickReplies();

            resetCsatBar();
        } catch (err) {
            appendMessage('ai', 'Sorry, I\'m having trouble connecting. Please try again later.');
        }
    }

    // ── Message rendering ─────────────────────────────────────────────────────
    function appendMessage(role, content, elId, timestamp, suggestions) {
        const c      = document.getElementById('pfx-messages');
        const outer  = document.createElement('div');
        outer.className = 'pfx-msg pfx-msg-' + role;
        if (elId) outer.id = elId;

        const t    = getTheme();
        const time = timestamp ? formatTime(timestamp) : formatTime(new Date().toISOString());

        if (role === 'system') {
            outer.innerHTML = '<div class="pfx-bubble-wrap" style="width:100%;"><div class="pfx-bubble pfx-bubble-system">' + escapeHtml(content) + '</div></div>';
        } else {
            const rendered = role === 'ai' || role === 'agent' ? renderMarkdown(content) : escapeHtml(content).replace(/\n/g, '<br>');
            const tsHtml   = '<div class="pfx-ts">' + time + '</div>';
            const sugHtml  = (suggestions && suggestions.length && role === 'ai')
                ? '<div class="pfx-chips-wrap" style="padding:4px 0 0;">' +
                    suggestions.map(function (s) {
                        return '<button type="button" class="pfx-chip pfx-smart-chip" data-text="' + escapeAttr(s) + '">' + escapeHtml(s) + '</button>';
                    }).join('') +
                  '</div>'
                : '';
            const reactHtml = role === 'ai' ? '<div class="pfx-reactions" data-id=""></div>' : '';

            outer.innerHTML =
                '<div class="pfx-bubble-wrap">' +
                '<div class="pfx-bubble pfx-bubble-' + role + '">' + rendered + '</div>' +
                sugHtml +
                (role === 'ai' ? reactHtml : '') +
                tsHtml +
                '</div>';
        }

        c.appendChild(outer);
        c.scrollTop = c.scrollHeight;

        // Bind smart suggestion chips
        outer.querySelectorAll('.pfx-smart-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById('pfx-input');
                input.value = btn.getAttribute('data-text') || '';
                // Remove all suggestion rows
                document.querySelectorAll('.pfx-chips-wrap').forEach(function (el) { el.innerHTML = ''; });
                sendMessage();
            });
        });
    }

    function showTyping() {
        const c   = document.getElementById('pfx-messages');
        const div = document.createElement('div');
        div.id    = 'pfx-typing';
        div.className = 'pfx-msg pfx-msg-ai';
        div.innerHTML = '<div class="pfx-bubble-wrap"><div class="pfx-bubble pfx-bubble-ai pfx-typing"><span></span><span></span><span></span></div></div>';
        c.appendChild(div);
        c.scrollTop = c.scrollHeight;
    }

    function hideTyping() {
        const el = document.getElementById('pfx-typing');
        if (el) el.remove();
    }

    // ── Sending messages ──────────────────────────────────────────────────────
    async function sendMessage() {
        const input   = document.getElementById('pfx-input');
        const content = input.value.trim();
        if (!content || isLoading) return;

        // Hide starters after first message
        const starters = document.getElementById('pfx-starters');
        if (starters) starters.style.display = 'none';

        input.value = '';
        input.style.height = 'auto';

        appendMessage('visitor', content, null, new Date().toISOString());
        maybeOfferBooking(content);
        showTyping();
        isLoading = true;

        try {
            if (config.message_streaming) {
                await sendMessageStream(content);
            } else {
                const data = await api('POST', '/' + config.uuid + '/conversations/' + conversationUuid + '/messages', { content });
                hideTyping();
                if (data.ai_response) {
                    appendMessage('ai', data.ai_response.content, null, data.ai_response.created_at, data.ai_response.smart_suggestions || []);
                    if (data.ai_response.uuid) lastMessageUuid = data.ai_response.uuid;
                    registerAiReply();
                    playNotificationSound();
                }
            }
        } catch (err) {
            hideTyping();
            if (err.message && err.message.includes('429')) {
                appendMessage('ai', 'You\'re sending messages too quickly. Please wait a moment.');
            } else {
                appendMessage('ai', 'Sorry, something went wrong. Please try again.');
            }
        }

        isLoading = false;
    }

    async function sendMessageStream(content) {
        const url = apiBase + '/' + config.uuid + '/conversations/' + conversationUuid + '/stream';
        const res = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'text/event-stream' },
            body:    JSON.stringify({ content }),
        });

        if (res.status === 429) throw new Error('429');
        if (!res.ok)           throw new Error('stream error');

        const reader  = res.body.getReader();
        const decoder = new TextDecoder();
        let buf    = '';
        let full   = '';
        const bubId = 'pfx-stream-' + Date.now();
        const t    = getTheme();

        // Create streaming bubble
        const c    = document.getElementById('pfx-messages');
        const div  = document.createElement('div');
        div.id      = bubId;
        div.className = 'pfx-msg pfx-msg-ai';
        div.innerHTML = '<div class="pfx-bubble-wrap"><div class="pfx-bubble pfx-bubble-ai"></div></div>';
        hideTyping();
        c.appendChild(div);

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buf += decoder.decode(value, { stream: true });
            const parts = buf.split('\n\n');
            buf = parts.pop() || '';

            for (let i = 0; i < parts.length; i++) {
                const line = parts[i].trim();
                if (!line.startsWith('data: ')) continue;
                let payload;
                try { payload = JSON.parse(line.slice(6)); } catch (e) { continue; }

                if (payload.chunk) {
                    full += payload.chunk;
                    const bub = div.querySelector('.pfx-bubble');
                    if (bub) bub.innerHTML = renderMarkdown(full);
                    c.scrollTop = c.scrollHeight;
                }

                if (payload.done) {
                    // Append smart suggestions inside the bubble wrapper
                    const suggestions = payload.smart_suggestions || [];
                    if (suggestions.length) {
                        const wrap = div.querySelector('.pfx-bubble-wrap');
                        if (wrap) {
                            const sugDiv = document.createElement('div');
                            sugDiv.className = 'pfx-chips-wrap';
                            sugDiv.style.padding = '4px 0 0';
                            sugDiv.innerHTML = suggestions.map(function (s) {
                                return '<button type="button" class="pfx-chip pfx-smart-chip" data-text="' + escapeAttr(s) + '">' + escapeHtml(s) + '</button>';
                            }).join('');
                            wrap.appendChild(sugDiv);

                            sugDiv.querySelectorAll('.pfx-smart-chip').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    const input = document.getElementById('pfx-input');
                                    input.value = btn.getAttribute('data-text') || '';
                                    document.querySelectorAll('.pfx-smart-chip').forEach(function (b) { b.parentElement && b.parentElement.remove(); });
                                    sendMessage();
                                });
                            });
                        }
                    }

                    // Append timestamp
                    const wrap2 = div.querySelector('.pfx-bubble-wrap');
                    if (wrap2) {
                        const tsDiv = document.createElement('div');
                        tsDiv.className = 'pfx-ts';
                        tsDiv.textContent = formatTime(new Date().toISOString());
                        wrap2.appendChild(tsDiv);
                    }

                    div.removeAttribute('id');
                    if (payload.message_uuid) lastMessageUuid = payload.message_uuid;
                    registerAiReply();
                    playNotificationSound();
                    break;
                }
            }
        }
    }

    // ── Agent message polling ─────────────────────────────────────────────────
    function startPolling() {
        stopPolling();
        pollTimer = setInterval(doPoll, 5000);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    async function doPoll() {
        if (!conversationUuid || !config) return;
        try {
            const qs = lastMessageUuid ? '?after=' + encodeURIComponent(lastMessageUuid) : '';
            const data = await api('GET', '/' + config.uuid + '/conversations/' + conversationUuid + '/poll' + qs);
            if (data.messages && data.messages.length) {
                data.messages.forEach(function (msg) {
                    appendMessage(msg.role, msg.content, null, msg.created_at, []);
                    lastMessageUuid = msg.uuid;
                    if (!isOpen) {
                        unreadCount++;
                        const badge = document.getElementById('pfx-badge');
                        if (badge) { badge.textContent = unreadCount; badge.classList.add('visible'); }
                        playNotificationSound();
                    }
                });
            }
        } catch (e) { /* silent — polling is best-effort */ }
    }

    // ── Handoff ───────────────────────────────────────────────────────────────
    async function requestHandoff() {
        if (handoffDone || isLoading || !conversationUuid) return;
        isLoading = true;
        try {
            const data = await api('POST', '/' + config.uuid + '/conversations/' + conversationUuid + '/handoff', {});
            handoffDone = true;
            if (data.message) appendMessage('ai', data.message.content, null, data.message.created_at);
            const ho = document.getElementById('pfx-handoff');
            if (ho) ho.style.display = 'none';
        } catch (err) {
            appendMessage('ai', 'Could not reach the team inbox. Please try again or leave a message below.');
            showOfflineForm();
        }
        isLoading = false;
    }

    // ── CSAT ──────────────────────────────────────────────────────────────────
    function registerAiReply() {
        aiTurnCount += 1;
        tryShowCsat();

        // Increment unread if chat is closed
        if (!isOpen) {
            unreadCount++;
            const badge = document.getElementById('pfx-badge');
            if (badge) {
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                badge.classList.add('visible');
            }
        }
    }

    function resetCsatBar() {
        const el = document.getElementById('pfx-csat');
        if (el) { el.style.display = 'none'; el.innerHTML = ''; el.removeAttribute('data-done'); }
    }

    function tryShowCsat() {
        if (!config.csat_prompt_enabled || !conversationUuid) return;
        const k = 'pfx_csat_' + conversationUuid;
        if (sessionStorage.getItem(k)) return;
        if (aiTurnCount < 2) return;
        const el = document.getElementById('pfx-csat');
        if (!el || el.getAttribute('data-done') === '1') return;

        const t = getTheme();
        el.style.display = 'block';
        el.innerHTML =
            '<div style="font-size:12px;color:' + t.textDim + ';margin-bottom:8px;font-weight:500;">How helpful was this chat?</div>' +
            '<div class="pfx-csat-stars" style="display:flex;gap:2px;margin-bottom:8px;">' +
            [1,2,3,4,5].map(function (n) {
                return '<button type="button" data-rating="' + n + '" aria-label="' + n + ' stars" style="font-size:24px;color:#fbbf24;opacity:0.25;padding:2px;transition:opacity 0.15s,transform 0.15s;">★</button>';
            }).join('') +
            '</div>' +
            '<button type="button" class="pfx-csat-skip" style="font-size:11px;color:' + t.textDim + ';background:none;border:none;cursor:pointer;padding:0;opacity:0.6;">Dismiss</button>';

        const stars = el.querySelectorAll('.pfx-csat-stars button');
        stars.forEach(function (btn) {
            btn.addEventListener('mouseover', function () {
                const r = parseInt(btn.getAttribute('data-rating'));
                stars.forEach(function (b, i) { b.style.opacity = i < r ? '1' : '0.25'; });
            });
            btn.addEventListener('mouseout', function () {
                stars.forEach(function (b) { b.style.opacity = '0.25'; });
            });
            btn.addEventListener('click', function () {
                submitCsat(parseInt(btn.getAttribute('data-rating')));
            });
        });

        el.querySelector('.pfx-csat-skip').addEventListener('click', function () {
            sessionStorage.setItem(k, 'skip');
            el.style.display = 'none';
        });
    }

    async function submitCsat(rating) {
        const k = 'pfx_csat_' + conversationUuid;
        try {
            await api('POST', '/' + config.uuid + '/conversations/' + conversationUuid + '/feedback', { rating, comment: '' });
            sessionStorage.setItem(k, '1');
            const el = document.getElementById('pfx-csat');
            if (el) {
                el.setAttribute('data-done', '1');
                const t = getTheme();
                el.innerHTML = '<div style="font-size:12px;color:' + t.textDim + ';text-align:center;padding:4px 0;">Thank you for your feedback! ★'.repeat(rating) + '</div>';
                setTimeout(function () { el.style.display = 'none'; }, 3000);
            }
        } catch (e) { console.warn('PFX: CSAT submit failed', e); }
    }

    // ── Offline form ──────────────────────────────────────────────────────────
    function showOfflineForm() {
        if (!config.offline_form_enabled) return;
        const el = document.getElementById('pfx-offline');
        if (!el || el.getAttribute('data-shown') === '1') return;
        el.setAttribute('data-shown', '1');

        const t = getTheme();
        el.style.display = 'block';
        el.innerHTML =
            '<div style="font-size:12px;color:' + t.textDim + ';margin-bottom:10px;">' + escapeHtml(config.offline_form_message || 'Leave us a message and we\'ll get back to you.') + '</div>' +
            '<input type="text"  id="pfx-offline-name"    class="pfx-offline-input" placeholder="Your name (optional)">' +
            '<input type="email" id="pfx-offline-email"   class="pfx-offline-input" placeholder="Your email address *">' +
            '<textarea           id="pfx-offline-message" class="pfx-offline-input" rows="3" style="resize:none;" placeholder="Your message *"></textarea>' +
            '<button type="button" id="pfx-offline-send" class="pfx-btn-primary">Send Message</button>';

        document.getElementById('pfx-offline-send').addEventListener('click', submitOfflineMessage);
    }

    async function submitOfflineMessage() {
        const name    = (document.getElementById('pfx-offline-name')    || {}).value || '';
        const email   = (document.getElementById('pfx-offline-email')   || {}).value || '';
        const message = (document.getElementById('pfx-offline-message') || {}).value || '';

        if (!email || !message) {
            alert('Please fill in your email and message.');
            return;
        }

        try {
            const btn = document.getElementById('pfx-offline-send');
            if (btn) btn.disabled = true;

            await api('POST', '/' + config.uuid + '/offline', {
                name,
                email,
                message,
                page_url: window.location.href,
                conversation_uuid: conversationUuid || undefined,
            });

            const el = document.getElementById('pfx-offline');
            const t  = getTheme();
            if (el) {
                el.innerHTML = '<div style="text-align:center;padding:8px;font-size:13px;color:' + t.text + ';">✅ Message sent! We\'ll get back to you soon.</div>';
                setTimeout(function () { el.style.display = 'none'; }, 4000);
            }
        } catch (e) {
            console.warn('PFX: Offline form failed', e);
        }
    }

    // ── Bookings ──────────────────────────────────────────────────────────────
    const bk = { info: null, service: null, date: null, slot: null, slots: [], form: null };

    async function bkApi(method, path, body) {
        const opts = { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(apiBase + '/' + config.uuid + '/booking' + path, opts);
        let data = {};
        try { data = await res.json(); } catch (e) {}
        return { ok: res.ok, status: res.status, data };
    }

    function bkPanel(title, body, back) {
        const el = document.getElementById('pfx-booking');
        el.innerHTML =
            '<div class="pfx-bk-head">' +
            (back ? '<button type="button" class="pfx-bk-icon" data-bk="back" aria-label="Back">‹</button>' : '<span style="width:30px"></span>') +
            '<div class="pfx-bk-title">' + escapeHtml(title) + '</div>' +
            '<button type="button" class="pfx-bk-icon" data-bk="close" aria-label="Close">✕</button></div>' +
            '<div class="pfx-bk-body">' + body + '</div>';
        el.style.display = 'flex';
        el.querySelector('[data-bk=close]').onclick = closeBooking;
        if (back) el.querySelector('[data-bk=back]').onclick = back;
        return el;
    }

    const BK_MANAGE_LINK = '<button type="button" class="pfx-bk-link pfx-bk-manage" style="display:block;margin:14px auto 0;background:none;border:none;cursor:pointer;font-family:inherit;">Already booked? Change or cancel it</button>';

    function bindManageLink(el) {
        const b = el.querySelector('.pfx-bk-manage');
        if (b) b.onclick = function () { openManage(); };
    }

    function closeBooking() {
        const el = document.getElementById('pfx-booking');
        if (el) el.style.display = 'none';
    }

    async function openBooking() {
        if (!config || !config.booking) return;
        bk.moving = null;
        if (!isOpen) await toggleChat();
        bkPanel(config.booking.label, '<div class="pfx-bk-muted">Loading…</div>');
        const r = await bkApi('GET', '');
        if (!r.ok) { bkPanel(config.booking.label, '<div class="pfx-bk-muted">Booking is not available right now.</div>'); return; }
        bk.info = r.data;
        // Always start on the service list: it also holds the "Already booked?" link.
        bkServices();
    }

    function bkStaff() {
        bk.staff = null;
        const team = bk.service.staff || [];
        if (team.length < 2) { bk.staff = team[0] ? team[0].id : null; return bkDates(); }
        const back = bkServices;
        const el = bkPanel('Who would you like?',
            '<button type="button" class="pfx-bk-card" data-s=""><strong>Anyone available</strong><br><span class="pfx-bk-muted">Shows the most times</span></button>' +
            team.map(function (m) { return '<button type="button" class="pfx-bk-card" data-s="' + escapeAttr(m.id) + '"><strong>' + escapeHtml(m.name) + '</strong></button>'; }).join(''), back);
        el.querySelectorAll('.pfx-bk-card').forEach(function (b) {
            b.onclick = function () { bk.staff = b.getAttribute('data-s') || null; bk.date = null; bkDates(); };
        });
    }

    function bkServices() {
        const el = bkPanel('Choose a service', bk.info.services.map(function (s, i) {
            return '<button type="button" class="pfx-bk-card" data-i="' + i + '">' +
                '<span class="pfx-bk-card-top"><strong>' + escapeHtml(s.name) + '</strong><span>' + escapeHtml(s.price || '') + '</span></span>' +
                '<span class="pfx-bk-muted">' + s.duration + ' min' + (s.description ? ' · ' + escapeHtml(s.description) : '') + '</span></button>';
        }).join('') + BK_MANAGE_LINK);
        bindManageLink(el);
        el.querySelectorAll('.pfx-bk-card').forEach(function (b) {
            b.onclick = function () { bk.service = bk.info.services[+b.getAttribute('data-i')]; bk.date = null; bkStaff(); };
        });
    }

    function bkDates() {
        const back = bk.moving ? function () { bkManageView(bk.moving); } : ((bk.service.staff || []).length > 1 ? bkStaff : bkServices);
        if (!bk.info.dates.length) { bkPanel(bk.service.name, '<div class="pfx-bk-muted">No dates are open for booking at the moment.</div>', back); return; }
        const chips = bk.info.dates.map(function (d) {
            const dt = new Date(d + 'T12:00:00');
            return '<button type="button" class="pfx-bk-date" data-d="' + d + '">' +
                '<span>' + dt.toLocaleDateString(undefined, { weekday: 'short' }) + '</span><strong>' + dt.getDate() + '</strong><span>' + dt.toLocaleDateString(undefined, { month: 'short' }) + '</span></button>';
        }).join('');
        const el = bkPanel(bk.moving ? 'Pick a new time' : bk.service.name,
            '<div class="pfx-bk-label">Pick a day</div><div class="pfx-bk-dates">' + chips + '</div>' +
            '<div class="pfx-bk-label">Pick a time <span class="pfx-bk-muted">(' + escapeHtml(bk.info.timezone) + ')</span></div><div id="pfx-bk-slots" class="pfx-bk-slots"></div>', back);
        const pick = function (d, auto) {
            el.querySelectorAll('.pfx-bk-date').forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-d') === d); });
            bkSlots(d).then(function (count) {
                // On first open, skip ahead to the first day that still has free times.
                const next = bk.info.dates[bk.info.dates.indexOf(d) + 1];
                if (auto && count === 0 && next && auto < 14) pick(next, auto + 1);
                const on = el.querySelector('.pfx-bk-date.on');
                if (on) on.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
        };
        el.querySelectorAll('.pfx-bk-date').forEach(function (b) { b.onclick = function () { pick(b.getAttribute('data-d')); }; });
        if (bk.date && bk.info.dates.indexOf(bk.date) > -1) pick(bk.date); else pick(bk.info.dates[0], 1);
    }

    async function bkSlots(date) {
        bk.date = date;
        const box = document.getElementById('pfx-bk-slots');
        box.innerHTML = '<div class="pfx-bk-muted">Loading times…</div>';
        const r = await bkApi('GET', '/slots?service=' + encodeURIComponent(bk.service.id) + '&date=' + date + (bk.staff ? '&staff=' + encodeURIComponent(bk.staff) : '') + (bk.moving ? '&ignore=' + encodeURIComponent(bk.moving.token) : ''));
        if (bk.date !== date) return -1;
        bk.slots = (r.ok && r.data.slots) || [];
        box.innerHTML = bk.slots.length
            ? bk.slots.map(function (s, i) { return '<button type="button" class="pfx-bk-slot" data-i="' + i + '">' + escapeHtml(s.label) + '</button>'; }).join('')
            : '<div class="pfx-bk-muted">No free times on this day. Try another day.</div>';
        box.querySelectorAll('.pfx-bk-slot').forEach(function (b) {
            b.onclick = function () { bk.slot = bk.slots[+b.getAttribute('data-i')]; if (bk.moving) bkConfirmMove(); else bkDetails(); };
        });
        return bk.slots.length;
    }

    function bkDetails(error) {
        const when = new Date(bk.date + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }) + ', ' + bk.slot.label;
        const v = bk.form || {};
        const el = bkPanel('Your details',
            '<div class="pfx-bk-summary"><strong>' + escapeHtml(bk.service.name) + '</strong><span>' + escapeHtml(when) + ' (' + escapeHtml(bk.info.timezone) + ')</span></div>' +
            (error ? '<div class="pfx-bk-error">' + escapeHtml(error) + '</div>' : '') +
            '<input id="pfx-bk-name" class="pfx-offline-input" autocomplete="name" placeholder="Your name *" value="' + escapeAttr(v.name || '') + '">' +
            '<input id="pfx-bk-email" type="email" class="pfx-offline-input" autocomplete="email" placeholder="Email address *" value="' + escapeAttr(v.email || '') + '">' +
            '<input id="pfx-bk-phone" type="tel" class="pfx-offline-input" autocomplete="tel" placeholder="Phone number' + (bk.info.require_phone ? ' *' : ' (optional)') + '" value="' + escapeAttr(v.phone || '') + '">' +
            (bk.info.questions || []).map(function (q) {
                const val = (v.answers || {})[q.id];
                const ph = escapeAttr(q.label + (q.required ? ' *' : ''));
                if (q.type === 'checkbox') return '<label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;margin-bottom:8px;"><input type="checkbox" data-q="' + escapeAttr(q.id) + '"' + (val ? ' checked' : '') + '> ' + escapeHtml(q.label) + (q.required ? ' *' : '') + '</label>';
                if (q.type === 'select') return '<select data-q="' + escapeAttr(q.id) + '" class="pfx-offline-input"><option value="">' + ph + '</option>' + q.options.map(function (o) { return '<option' + (o === val ? ' selected' : '') + '>' + escapeHtml(o) + '</option>'; }).join('') + '</select>';
                if (q.type === 'textarea') return '<textarea data-q="' + escapeAttr(q.id) + '" class="pfx-offline-input" rows="2" style="resize:none" placeholder="' + ph + '">' + escapeHtml(val || '') + '</textarea>';
                return '<input data-q="' + escapeAttr(q.id) + '" class="pfx-offline-input" placeholder="' + ph + '" value="' + escapeAttr(val || '') + '">';
            }).join('') +
            '<textarea id="pfx-bk-notes" class="pfx-offline-input" rows="2" style="resize:none" placeholder="Anything we should know? (optional)">' + escapeHtml(v.notes || '') + '</textarea>' +
            '<button type="button" id="pfx-bk-go" class="pfx-btn-primary">' + (bk.info.auto_confirm ? 'Confirm booking' : 'Request booking') + '</button>', bkDates);
        el.querySelector('#pfx-bk-go').onclick = bkSubmit;
    }

    async function bkSubmit() {
        const val = function (id) { return ((document.getElementById(id) || {}).value || '').trim(); };
        const answers = {};
        document.querySelectorAll('#pfx-booking [data-q]').forEach(function (el) { answers[el.getAttribute('data-q')] = el.type === 'checkbox' ? el.checked : el.value.trim(); });
        bk.form = { name: val('pfx-bk-name'), email: val('pfx-bk-email'), phone: val('pfx-bk-phone'), notes: val('pfx-bk-notes'), answers: answers };
        const missing = (bk.info.questions || []).some(function (q) { return q.required && !answers[q.id]; });
        if (missing || !bk.form.name || !/^\S+@\S+\.\S+$/.test(bk.form.email) || (bk.info.require_phone && !bk.form.phone)) {
            return bkDetails('Please fill in the required fields.');
        }
        const btn = document.getElementById('pfx-bk-go');
        btn.disabled = true; btn.textContent = 'Booking…';
        const r = await bkApi('POST', '', Object.assign({}, bk.form, {
            service_id: bk.service.id, staff_id: bk.staff || undefined, start: bk.slot.start, source: bk.fromAi ? 'ai' : 'widget',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || undefined,
            conversation_uuid: conversationUuid || undefined,
        }));
        if (!r.ok) {
            const errors = (r.data && r.data.errors) || {};
            if (errors.start) { bk.slot = null; bkDates(); return; }
            const first = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : null;
            return bkDetails(first || (r.status === 429 ? 'Too many attempts. Please wait a minute.' : 'Something went wrong. Please try again.'));
        }
        const d = r.data;
        bkPanel(d.status === 'pending' ? 'Request sent' : 'You are booked in',
            '<div class="pfx-bk-done"><div class="pfx-bk-tick">✓</div>' +
            '<strong>' + escapeHtml(d.service) + (d.staff ? ' with ' + escapeHtml(d.staff) : '') + '</strong><span>' + escapeHtml(d.when) + ' (' + escapeHtml(d.timezone) + ')</span>' +
            bkRefBox(d.reference) +
            '<p class="pfx-bk-muted">' + (d.status === 'pending' ? 'We will confirm by email shortly.' : 'A confirmation and calendar invite are on their way to ' + escapeHtml(bk.form.email) + '.') + ' Keep your booking number to change or cancel here in the chat.</p>' +
            '<a href="' + escapeAttr(d.manage_url) + '" target="_blank" rel="noopener" class="pfx-bk-link">Manage or cancel</a></div>');
        bk.form = null;
    }

    function bkRefBox(ref) {
        if (!ref) return '';
        return '<div style="margin:10px 0 6px;padding:10px 14px;border-radius:12px;border:1px dashed ' + (config.primary_color || '#2f5ee8') + ';">' +
            '<div class="pfx-bk-muted" style="text-transform:uppercase;letter-spacing:0.12em;font-size:10px;">Booking number</div>' +
            '<div style="font-size:20px;font-weight:800;letter-spacing:0.08em;font-family:ui-monospace,Consolas,monospace;">' + escapeHtml(ref) + '</div></div>';
    }

    async function openManage(prefill) {
        if (!config || !config.booking) return;
        if (!isOpen) await toggleChat();
        bk.moving = null;
        const v = bk.lookup || {};
        const el = bkPanel('Manage my booking',
            '<p class="pfx-bk-muted" style="margin:0 0 12px;">Enter the booking number from your confirmation email (it starts with BK-) and the email you booked with.</p>' +
            '<div id="pfx-bk-lerr" class="pfx-bk-error" style="display:none"></div>' +
            '<input id="pfx-bk-ref" class="pfx-offline-input" placeholder="Booking number, e.g. BK-7F3K9Q" autocomplete="off" style="text-transform:uppercase;letter-spacing:0.06em;" value="' + escapeAttr(prefill || v.reference || '') + '">' +
            '<input id="pfx-bk-lemail" type="email" class="pfx-offline-input" placeholder="Email address" autocomplete="email" value="' + escapeAttr(v.email || '') + '">' +
            '<button type="button" id="pfx-bk-find" class="pfx-btn-primary">Find my booking</button>' +
            '<button type="button" class="pfx-bk-link pfx-bk-new" style="display:block;margin:14px auto 0;background:none;border:none;cursor:pointer;font-family:inherit;">Make a new booking instead</button>');
        el.querySelector('.pfx-bk-new').onclick = openBooking;
        el.querySelector('#pfx-bk-find').onclick = async function () {
            const ref = el.querySelector('#pfx-bk-ref').value.trim(), email = el.querySelector('#pfx-bk-lemail').value.trim();
            const err = el.querySelector('#pfx-bk-lerr');
            if (!ref || !/^\S+@\S+\.\S+$/.test(email)) { err.textContent = 'Please enter your booking number and email.'; err.style.display = 'block'; return; }
            this.disabled = true; this.textContent = 'Looking…';
            const r = await bkApi('POST', '/lookup', { reference: ref, email: email });
            bk.lookup = { reference: ref, email: email };
            if (!r.ok) {
                this.disabled = false; this.textContent = 'Find my booking';
                err.textContent = r.status === 429 ? 'Too many attempts. Please wait a minute and try again.' : ((r.data && r.data.message) || 'Something went wrong. Please try again.');
                err.style.display = 'block';
                return;
            }
            bkManageView(r.data);
        };
    }

    function bkManageView(d, notice) {
        const labels = { pending: 'Waiting for confirmation', confirmed: 'Confirmed', completed: 'Completed', cancelled: 'Cancelled', no_show: 'Missed' };
        const el = bkPanel('Your booking',
            (notice ? '<div class="pfx-bk-summary" style="text-align:center;">' + escapeHtml(notice) + '</div>' : '') +
            bkRefBox(d.reference) +
            '<div class="pfx-bk-summary"><strong>' + escapeHtml(d.service) + (d.staff ? ' with ' + escapeHtml(d.staff) : '') + '</strong>' +
            '<span>' + escapeHtml(d.when) + ' (' + escapeHtml(d.timezone) + ')</span><span class="pfx-bk-muted">' + escapeHtml(labels[d.status] || d.status) + '</span></div>' +
            (d.can_move ? '<button type="button" id="pfx-bk-move" class="pfx-btn-primary" style="margin-bottom:8px;">Change time</button>' : '') +
            (d.can_cancel ? '<button type="button" id="pfx-bk-cancel" class="pfx-bk-card" style="text-align:center;color:#ef4444;font-weight:700;">Cancel booking</button>' : '') +
            (!d.can_move && !d.can_cancel && (d.status === 'pending' || d.status === 'confirmed') ? '<p class="pfx-bk-muted">This booking can no longer be changed online. Please contact us directly.</p>' : '') +
            '<a href="' + escapeAttr(d.manage_url) + '" target="_blank" rel="noopener" class="pfx-bk-link" style="display:block;text-align:center;">Open booking page</a>',
            function () { openManage(d.reference); });
        const mv = el.querySelector('#pfx-bk-move');
        if (mv) mv.onclick = async function () {
            if (!bk.info) { const r = await bkApi('GET', ''); if (!r.ok) return; bk.info = r.data; }
            bk.service = bk.info.services.find(function (x) { return x.id === d.service_id; }) || { id: d.service_id, name: d.service, staff: [] };
            bk.staff = d.staff_id || null;
            bk.moving = d; bk.date = null;
            bkDates();
        };
        const cn = el.querySelector('#pfx-bk-cancel');
        if (cn) cn.onclick = function () {
            const c = bkPanel('Cancel booking?',
                '<p style="font-size:13px;margin:0 0 14px;">Cancel ' + escapeHtml(d.service) + ' on ' + escapeHtml(d.when) + '?' + '</p>' +
                '<button type="button" id="pfx-bk-yes" class="pfx-btn-primary" style="background:#ef4444;margin-bottom:8px;">Yes, cancel it</button>' +
                '<button type="button" id="pfx-bk-no" class="pfx-bk-card" style="text-align:center;">Keep my booking</button>',
                function () { bkManageView(d); });
            c.querySelector('#pfx-bk-no').onclick = function () { bkManageView(d); };
            c.querySelector('#pfx-bk-yes').onclick = async function () {
                this.disabled = true; this.textContent = 'Cancelling…';
                const r = await bkApi('POST', '/manage/' + encodeURIComponent(d.token) + '/cancel');
                if (!r.ok) return bkManageView(d, (r.data && r.data.message) || 'We could not cancel it. Please try again.');
                bkManageView(r.data, 'Your booking is cancelled. We have emailed you a confirmation.');
                if (conversationUuid) appendMessage('ai', '❌ Booking ' + d.reference + ' cancelled.', null, new Date().toISOString());
            };
        };
    }

    function bkConfirmMove() {
        const d = bk.moving;
        const when = new Date(bk.date + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }) + ', ' + bk.slot.label;
        const el = bkPanel('Confirm new time',
            '<div class="pfx-bk-summary"><span class="pfx-bk-muted">From</span><span>' + escapeHtml(d.when) + '</span><span class="pfx-bk-muted" style="margin-top:6px;">To</span><strong>' + escapeHtml(when) + '</strong></div>' +
            '<button type="button" id="pfx-bk-domove" class="pfx-btn-primary">Move my booking</button>', bkDates);
        el.querySelector('#pfx-bk-domove').onclick = async function () {
            this.disabled = true; this.textContent = 'Moving…';
            const r = await bkApi('POST', '/manage/' + encodeURIComponent(d.token) + '/reschedule', { start: bk.slot.start });
            if (!r.ok) {
                if (r.data && r.data.errors && r.data.errors.start) { bk.slot = null; return bkDates(); }
                return bkManageView(d, (r.data && r.data.message) || 'We could not move it. Please try again.');
            }
            bk.moving = null;
            bkManageView(r.data, 'Done. Your booking has moved and we have emailed you the new time.');
            if (conversationUuid) appendMessage('ai', '📅 Booking ' + d.reference + ' moved to ' + r.data.when + '.', null, new Date().toISOString());
        };
    }

    function addBookingChip(fromAi) {
        if (!config || !config.booking) return;
        const key = fromAi === 'manage' ? 'chipTimerM' : 'chipTimer';
        if (bk[key]) return;
        bk[key] = setTimeout(function () {
            bk[key] = null;
            const wrap = document.getElementById('pfx-messages');
            const manage = fromAi === 'manage';
            if (!wrap || (wrap.lastElementChild && wrap.lastElementChild.classList.contains(manage ? 'pfx-bk-chip-m' : 'pfx-bk-chip'))) return;
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pfx-chip ' + (manage ? 'pfx-bk-chip-m' : 'pfx-bk-chip');
            b.style.alignSelf = 'flex-start';
            b.textContent = manage ? '🔎 Manage my booking' : '📅 ' + config.booking.label;
            const ref = bk.refHint;
            b.onclick = manage ? function () { openManage(ref); } : function () { bk.fromAi = true; openBooking(); };
            wrap.appendChild(b);
            wrap.scrollTop = wrap.scrollHeight;
        }, 1200);
    }

    function maybeOfferBooking(text) {
        if (!config.booking) return;
        const ref = text.match(/\bBK[-\s]?([A-Z0-9]{6})\b/i);
        if (ref) bk.refHint = 'BK-' + ref[1].toUpperCase();
        if (ref || /\b(cancel|reschedul\w*|change|move|amend|my)\b[\s\S]*\b(booking|appointment)\b|\bbooking (number|ref\w*)\b/i.test(text)) {
            addBookingChip('manage');
        } else if (/\b(book|booking|appointments?|schedule|reserve|reservation|slots?|availability)\b/i.test(text)) {
            addBookingChip();
        }
    }

    // ── Proactive messaging ───────────────────────────────────────────────────
    function setupProactiveMessaging() {
        if (!config.proactive || !config.proactive.enabled) return;
        if (isOpen) return;
        if (proactiveShown) return;

        const cfg = config.proactive;

        // Time-on-page trigger
        if (cfg.trigger_seconds > 0) {
            proactiveTimer = setTimeout(function () {
                if (!isOpen && !proactiveShown) triggerProactiveMessage(cfg);
            }, cfg.trigger_seconds * 1000);
        }

        // Page count trigger
        if (cfg.trigger_pages > 0 && pageCount >= cfg.trigger_pages) {
            if (!isOpen && !proactiveShown) {
                setTimeout(function () { triggerProactiveMessage(cfg); }, 1500);
            }
        }

        // Advanced messages from DB
        if (cfg.messages && cfg.messages.length) {
            cfg.messages.forEach(function (pm) {
                if (pm.trigger_type === 'exit_intent') {
                    document.addEventListener('mouseleave', function handler(e) {
                        if (e.clientY < 10 && !isOpen && !proactiveShown) {
                            document.removeEventListener('mouseleave', handler);
                            showProactiveBubble(pm.message, pm.id, pm.quick_replies || []);
                        }
                    });
                } else if (pm.trigger_type === 'scroll_depth') {
                    window.addEventListener('scroll', function handler() {
                        const scrolled = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
                        if (scrolled >= pm.trigger_value && !isOpen && !proactiveShown) {
                            window.removeEventListener('scroll', handler);
                            showProactiveBubble(pm.message, pm.id, pm.quick_replies || []);
                        }
                    });
                }
            });
        }
    }

    function triggerProactiveMessage(cfg) {
        const message = cfg.message;
        if (!message) return;
        showProactiveBubble(message, null, []);
    }

    function showProactiveBubble(message, pmId, quickReplies) {
        if (proactiveShown || isOpen) return;
        proactiveShown = true;

        const pos    = config.position || 'bottom-right';
        const hPos   = pos.includes('left') ? 'left:90px;' : 'right:90px;';
        const vPos   = pos.includes('top')  ? 'top:20px;' : 'bottom:20px;';

        const bubble = document.createElement('div');
        bubble.className = 'pfx-proactive';
        bubble.style.cssText = 'position:fixed;' + hPos + vPos + 'z-index:99997;max-width:280px;';

        const qrHtml = quickReplies.length
            ? '<div class="pfx-chips-wrap" style="margin-top:10px;padding:0;">' +
                quickReplies.map(function (r) {
                    return '<button type="button" class="pfx-chip" data-text="' + escapeAttr(r) + '" style="font-size:11px;">' + escapeHtml(r) + '</button>';
                }).join('') +
              '</div>'
            : '';

        bubble.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">' +
            '<div style="font-size:13px;line-height:1.5;">' + escapeHtml(message) + '</div>' +
            '<button type="button" id="pfx-proactive-close" style="background:rgba(255,255,255,0.1);border:none;border-radius:6px;width:24px;height:24px;cursor:pointer;color:white;flex-shrink:0;font-size:12px;">✕</button>' +
            '</div>' + qrHtml;

        document.body.appendChild(bubble);

        if (pmId) {
            api('POST', '/' + config.uuid + '/proactive/track', { proactive_id: pmId, event: 'shown' }).catch(function () {});
        }

        bubble.querySelector('#pfx-proactive-close').addEventListener('click', function () {
            bubble.remove();
        });

        // Clicking bubble opens chat
        bubble.addEventListener('click', function (e) {
            if (e.target.closest('#pfx-proactive-close')) return;
            const text = e.target.closest('[data-text]');
            if (text) {
                const input = document.getElementById('pfx-input');
                bubble.remove();
                if (!isOpen) toggleChat().then(function () {
                    if (input) { input.value = text.getAttribute('data-text') || ''; sendMessage(); }
                });
                if (pmId) api('POST', '/' + config.uuid + '/proactive/track', { proactive_id: pmId, event: 'clicked' }).catch(function () {});
                return;
            }
            bubble.remove();
            if (!isOpen) toggleChat();
            if (pmId) api('POST', '/' + config.uuid + '/proactive/track', { proactive_id: pmId, event: 'clicked' }).catch(function () {});
        });

        // Auto-dismiss after 12 seconds
        setTimeout(function () {
            if (bubble.parentNode) {
                bubble.style.opacity = '0';
                bubble.style.transition = 'opacity 0.5s';
                setTimeout(function () { bubble.remove(); }, 500);
            }
        }, 12000);
    }
})();
