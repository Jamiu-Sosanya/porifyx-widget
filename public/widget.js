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

    function buildLauncherHTML() {
        const t       = getTheme();
        const pos     = config.position || 'bottom-right';
        const hPos    = pos.includes('left') ? 'left:20px;' : 'right:20px;';
        const vPos    = pos.includes('top')  ? 'top:20px;'  : 'bottom:20px;';
        const shape   = config.launcher_shape === 'rounded' ? '16px' : (config.launcher_shape === 'square' ? '10px' : '22px');
        const mHide   = config.show_on_mobile === false ? 'pfx-hide-mobile' : '';

        return '<div id="pfx-launcher" class="' + mHide + '" style="position:fixed;' + hPos + vPos + 'width:60px;height:60px;border-radius:' + shape + ';background:' + config.primary_color + ';display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 10px 28px -6px ' + config.primary_color + 'aa,0 2px 6px rgba(0,0,0,0.2);z-index:99998;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform=\'scale(1.1)\';this.style.boxShadow=\'0 12px 40px rgba(0,0,0,0.45)\'" onmouseout="this.style.transform=\'scale(1)\';this.style.boxShadow=\'0 8px 30px rgba(0,0,0,0.35)\'">' +
            '<svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-8l-5 4v-4H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" fill="white" fill-opacity=".95"/><path d="M12 6.5l1.2 2.8 2.8 1.2-2.8 1.2L12 14.5l-1.2-2.8L8 10.5l2.8-1.2z" fill="' + config.primary_color + '"/></svg>' +
            '<div id="pfx-badge" style="display:none;position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;border-radius:10px;background:#ef4444;color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;box-shadow:0 2px 8px rgba(239,68,68,0.5);">0</div>' +
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

        return '<div id="pfx-chat" class="' + mHide + '" style="position:fixed;' + hPos + vPos + 'width:390px;height:500px;display:none;flex-direction:column;border-radius:16px;overflow:hidden;box-shadow:0 30px 80px -20px rgba(11,19,36,0.55);z-index:99999;font-family:' + (config.font_family || '\'Plus Jakarta Sans\',system-ui,-apple-system,sans-serif') + ';background:' + t.bg + ';border:1px solid ' + t.border + ';opacity:0;transform:translateY(24px) scale(0.97);transition:opacity 0.35s cubic-bezier(0.4,0,0.2,1),transform 0.35s cubic-bezier(0.4,0,0.2,1);">' +

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
            '<button id="pfx-theme-toggle" type="button" title="Toggle theme" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:14px;transition:background 0.2s;">🌙</button>' +
            '<button id="pfx-close" type="button" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:16px;line-height:1;transition:background 0.2s;">✕</button>' +
            '</div></div>' +

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

.pfx-csat-stars button { font-size:22px; background:none; border:none; cursor:pointer; padding:2px 4px; transition:transform 0.15s; }
.pfx-csat-stars button:hover { transform:scale(1.25); }

.pfx-proactive { position:fixed; z-index:99997; max-width:280px; background:${t.bg === '#ffffff' || t.bg === '#fafafa' ? '#1a1a2e' : t.bg}; color:white; border-radius:16px; padding:16px; box-shadow:0 16px 50px rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); animation:pfx-slide-in 0.4s cubic-bezier(0.4,0,0.2,1); }
@keyframes pfx-slide-in { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

#pfx-messages::-webkit-scrollbar { width:4px; }
#pfx-messages::-webkit-scrollbar-thumb { background:${t.scrollbar}; border-radius:4px; }
#pfx-messages::-webkit-scrollbar-track { background:transparent; }

@media(max-width:480px) {
  .pfx-hide-mobile { display:none !important; }
  #pfx-chat { width:calc(100vw - 16px) !important; height:calc(100svh - 16px) !important; left:8px !important; right:8px !important; bottom:8px !important; border-radius:16px !important; }
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
