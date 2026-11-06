<?php
/**
 * Plugin Name:       PoriFyx Widget
 * Description:       Adds your PoriFyx AI chat widget to every page and keeps its knowledge in sync with your posts, pages and products.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            PoriFyx
 * License:           GPL-2.0-or-later
 * Text Domain:       porifyx-widget
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PFXW_OPT', 'pfxw_settings');

function pfxw_settings() {
    return wp_parse_args(get_option(PFXW_OPT, array()), array(
        'platform'   => '',
        'widget_id'  => '',
        'token'      => '',
        'show'       => '1',
        'auto_sync'  => '1',
        'post_types' => array('post', 'page'),
    ));
}

/* ── Widget on the front end ─────────────────────────────────────────── */
add_action('wp_footer', function () {
    $s = pfxw_settings();
    if ($s['show'] !== '1' || ! $s['widget_id'] || ! $s['platform'] || is_admin()) {
        return;
    }
    printf(
        '<script src="%s" data-widget-id="%s" async></script>' . "\n",
        esc_url(untrailingslashit($s['platform']) . '/widget.js'),
        esc_attr($s['widget_id'])
    );
});

/* ── Content sync ────────────────────────────────────────────────────── */
function pfxw_request($method, $path, $body = null) {
    $s = pfxw_settings();
    $args = array(
        'method'  => $method,
        'timeout' => 20,
        'headers' => array('Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-Widget-Token' => $s['token']),
    );
    if ($body !== null) {
        $args['body'] = wp_json_encode($body);
    }
    $res = wp_remote_request(untrailingslashit($s['platform']) . '/api/sync/' . rawurlencode($s['widget_id']) . $path, $args);
    if (is_wp_error($res)) {
        return $res;
    }
    $code = wp_remote_retrieve_response_code($res);
    if ($code >= 300) {
        return new WP_Error('pfxw', sprintf('PoriFyx returned HTTP %d: %s', $code, wp_strip_all_tags(wp_remote_retrieve_body($res))));
    }
    return json_decode(wp_remote_retrieve_body($res), true);
}

function pfxw_sync_post($post) {
    $post = get_post($post);
    $s = pfxw_settings();
    if (! $post || ! in_array($post->post_type, (array) $s['post_types'], true) || ! $s['token']) {
        return null;
    }
    $url = get_permalink($post);
    if ($post->post_status !== 'publish' || post_password_required($post)) {
        return pfxw_request('DELETE', '/pages', array('url' => $url));
    }
    $html = apply_filters('the_content', $post->post_content); // renders blocks and page builders
    if ($post->post_type === 'product' && function_exists('wc_get_product') && ($p = wc_get_product($post->ID))) {
        $html .= "\nPrice: " . wp_strip_all_tags(wc_price($p->get_price())) . "\n" . ($p->is_in_stock() ? 'In stock' : 'Out of stock') . "\n" . $p->get_short_description();
    }
    $text = trim(preg_replace('/\n{3,}/', "\n\n", wp_strip_all_tags(html_entity_decode(strip_shortcodes($html)))));
    if ($text === '') {
        return null;
    }
    return pfxw_request('POST', '/pages', array('url' => $url, 'title' => get_the_title($post), 'content' => $post->post_title . "\n\n" . $text));
}

add_action('save_post', function ($post_id, $post) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || pfxw_settings()['auto_sync'] !== '1') {
        return;
    }
    pfxw_sync_post($post);
}, 20, 2);

add_action('before_delete_post', function ($post_id) {
    $s = pfxw_settings();
    if ($s['token'] && in_array(get_post_type($post_id), (array) $s['post_types'], true)) {
        pfxw_request('DELETE', '/pages', array('url' => get_permalink($post_id)));
    }
});

/* Batch sync (25 posts per click/AJAX call so shared hosting never times out). */
add_action('wp_ajax_pfxw_sync_batch', function () {
    check_ajax_referer('pfxw_sync');
    if (! current_user_can('manage_options')) {
        wp_send_json_error('Not allowed', 403);
    }
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $q = new WP_Query(array('post_type' => (array) pfxw_settings()['post_types'], 'post_status' => 'publish', 'posts_per_page' => 25, 'paged' => $page, 'fields' => 'ids', 'no_found_rows' => false));
    $errors = array();
    foreach ($q->posts as $id) {
        $r = pfxw_sync_post($id);
        if (is_wp_error($r)) {
            $errors[] = get_the_title($id) . ': ' . $r->get_error_message();
        }
    }
    wp_send_json_success(array('page' => $page, 'pages' => (int) $q->max_num_pages, 'done' => count($q->posts), 'total' => (int) $q->found_posts, 'errors' => $errors));
});

/* ── Settings page ───────────────────────────────────────────────────── */
add_action('admin_menu', function () {
    add_options_page('PoriFyx Widget', 'PoriFyx Widget', 'manage_options', 'porifyx-widget', 'pfxw_page');
});

add_action('admin_init', function () {
    register_setting('pfxw', PFXW_OPT, array('sanitize_callback' => function ($in) {
        return array(
            'platform'   => esc_url_raw(trim($in['platform'] ?? '')),
            'widget_id'  => sanitize_text_field($in['widget_id'] ?? ''),
            'token'      => sanitize_text_field($in['token'] ?? ''),
            'show'       => empty($in['show']) ? '0' : '1',
            'auto_sync'  => empty($in['auto_sync']) ? '0' : '1',
            'post_types' => array_values(array_map('sanitize_key', (array) ($in['post_types'] ?? array()))),
        );
    }));
});

function pfxw_page() {
    $s = pfxw_settings();
    $types = get_post_types(array('public' => true), 'objects');
    $test = null;
    if ($s['token'] && $s['widget_id'] && $s['platform']) {
        $test = pfxw_request('GET', '/ping');
    }
    ?>
    <div class="wrap">
        <h1>PoriFyx Widget</h1>
        <?php if ($test !== null) : ?>
            <div class="notice <?php echo is_wp_error($test) ? 'notice-error' : 'notice-success'; ?>"><p>
                <?php echo is_wp_error($test) ? esc_html($test->get_error_message()) : esc_html(sprintf('Connected to "%s".', $test['widget'] ?? '')); ?>
            </p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields('pfxw'); ?>
            <table class="form-table" role="presentation">
                <tr><th><label for="pfxw_platform">Platform URL</label></th><td><input id="pfxw_platform" class="regular-text" name="<?php echo PFXW_OPT; ?>[platform]" value="<?php echo esc_attr($s['platform']); ?>" placeholder="https://app.porifyx.com"></td></tr>
                <tr><th><label for="pfxw_id">Widget ID</label></th><td><input id="pfxw_id" class="regular-text" name="<?php echo PFXW_OPT; ?>[widget_id]" value="<?php echo esc_attr($s['widget_id']); ?>"><p class="description">Widget → Embed code in your PoriFyx dashboard.</p></td></tr>
                <tr><th><label for="pfxw_token">Widget token</label></th><td><input id="pfxw_token" class="regular-text" type="password" name="<?php echo PFXW_OPT; ?>[token]" value="<?php echo esc_attr($s['token']); ?>"><p class="description">Widget → Security in your dashboard. Needed only for content sync.</p></td></tr>
                <tr><th>Show the chat widget</th><td><label><input type="checkbox" name="<?php echo PFXW_OPT; ?>[show]" value="1" <?php checked($s['show'], '1'); ?>> On every page</label></td></tr>
                <tr><th>Keep knowledge in sync</th><td><label><input type="checkbox" name="<?php echo PFXW_OPT; ?>[auto_sync]" value="1" <?php checked($s['auto_sync'], '1'); ?>> Send published content when it is saved</label>
                    <p><?php foreach ($types as $t) : if ($t->name === 'attachment') continue; ?>
                        <label style="margin-right:12px"><input type="checkbox" name="<?php echo PFXW_OPT; ?>[post_types][]" value="<?php echo esc_attr($t->name); ?>" <?php checked(in_array($t->name, (array) $s['post_types'], true)); ?>> <?php echo esc_html($t->labels->name); ?></label>
                    <?php endforeach; ?></p></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Sync existing content</h2>
        <p>Sends every published item of the selected types to your assistant's knowledge base.</p>
        <p><button type="button" class="button button-primary" id="pfxw-sync" <?php disabled(! $s['token']); ?>>Sync now</button> <span id="pfxw-status"></span></p>
        <script>
        document.getElementById('pfxw-sync').addEventListener('click', async function () {
            const btn = this, out = document.getElementById('pfxw-status');
            btn.disabled = true;
            let page = 1, pages = 1, sent = 0, errors = [];
            while (page <= pages) {
                const body = new URLSearchParams({ action: 'pfxw_sync_batch', page: page, _ajax_nonce: '<?php echo esc_js(wp_create_nonce('pfxw_sync')); ?>' });
                const r = await (await fetch(ajaxurl, { method: 'POST', body: body })).json();
                if (!r.success) { out.textContent = 'Failed: ' + r.data; break; }
                pages = r.data.pages; sent += r.data.done; errors = errors.concat(r.data.errors);
                out.textContent = 'Sent ' + sent + ' of ' + r.data.total + '…';
                page++;
            }
            out.textContent = 'Done: ' + sent + ' items sent.' + (errors.length ? ' ' + errors.length + ' failed: ' + errors.slice(0, 3).join('; ') : '');
            btn.disabled = false;
        });
        </script>
    </div>
    <?php
}
