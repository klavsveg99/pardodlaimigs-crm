<?php

/**
 * Plugin Name: Pārdod Laimīgs CRM Property Sync
 * Description: Pulls property data from CRM and overwrites WordPress property posts. CRM is the single source of truth.
 * Version: 2.0.0
 * Author: Pārdod Laimīgs
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PDC_CRM_API_URL', 'https://crm.pardodlaimigs.lv/api/crm/properties');
define('PDC_CRM_API_KEY', 'g124gqAEeDe3125v523bVScac4v');
define('PDC_CRM_AGENTS_URL', 'https://crm.pardodlaimigs.lv/api/crm/agents');
define('PDC_SYNC_INTERVAL', 5 * MINUTE_IN_SECONDS);

function pdc_log($msg) {
    error_log('[PDC CRM] ' . $msg);
}

function pdc_map_status($crm_status) {
    switch ($crm_status) {
        case 'published': return 'publish';
        case 'draft':     return 'draft';
        case 'expired':   return 'expired';
        case 'hidden':    return 'private';
        case 'sold':      return 'publish';
        default:          return 'draft';
    }
}

function pdc_ensure_category($category_name) {
    if (empty($category_name)) { return 0; }
    $term = term_exists($category_name, 'property-status');
    if (! $term) {
        $term = wp_insert_term($category_name, 'property-status');
    }
    return is_wp_error($term) ? 0 : (int) $term['term_id'];
}

function pdc_find_existing_media($filename) {
    global $wpdb;

    $like = '%' . $wpdb->esc_like($filename);
    $meta_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
            $like
        )
    );
    if ($meta_id && (int) $meta_id > 0) {
        return (int) $meta_id;
    }

    $guid_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE %s LIMIT 1",
            $like
        )
    );
    if ($guid_id && (int) $guid_id > 0) {
        return (int) $guid_id;
    }

    return 0;
}

/**
 * Match an existing attachment by its exact uploads-relative path (e.g. "2025/09/foo.jpg").
 * This avoids the cross-property contamination caused by matching on basename alone,
 * since different properties legitimately share generic filenames like "1.jpg".
 */
function pdc_relative_upload_path($url) {
    $url = esc_url_raw($url);
    if ($url === '') { return ''; }
    $path = parse_url($url, PHP_URL_PATH);
    if ($path === null) { return ''; }
    $path = ltrim($path, '/');
    if (strpos($path, 'wp-content/uploads/') === 0) {
        $path = substr($path, strlen('wp-content/uploads/'));
    }
    return $path;
}

function pdc_find_existing_media_by_path($path) {
    global $wpdb;
    if ($path === '') { return 0; }
    $meta_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            $path
        )
    );
    return $meta_id && (int) $meta_id > 0 ? (int) $meta_id : 0;
}

function pdc_sync_attachments($post_id, $attachments) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    if (empty($attachments)) {
        update_post_meta($post_id, 'real_estate_property_images', '');
        delete_post_thumbnail($post_id);
        return '';
    }

    $seen = [];
    $unique = [];
    foreach ($attachments as $attachment) {
        $url = isset($attachment['url']) ? esc_url_raw($attachment['url']) : '';
        if ($url === '') { continue; }
        $path = pdc_relative_upload_path($url);
        if ($path === '') { continue; }
        if (isset($seen[$path])) { continue; }
        $seen[$path] = true;
        $attachment['url'] = $url;
        $attachment['path'] = $path;
        $attachment['name'] = basename($url);
        $unique[] = $attachment;
    }

    $image_ids = [];
    $need_download = [];

    foreach ($unique as $attachment) {
        $url  = $attachment['url'];
        $path = $attachment['path'];
        $name = $attachment['name'];
        $mime = isset($attachment['mime_type']) ? $attachment['mime_type'] : '';

        $is_image = (strpos($mime, 'image/') === 0 || preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $name));

        $media_id = pdc_find_existing_media_by_path($path);

        if ($media_id > 0) {
            update_post_meta($media_id, '_pdc_crm_attachment_url', $url);
        } else {
            $need_download[] = $attachment;
        }

        if ($media_id > 0 && $is_image) {
            $image_ids[] = $media_id;
        }
    }

    foreach ($need_download as $attachment) {
        $url  = $attachment['url'];
        $name = $attachment['name'];
        $mime = isset($attachment['mime_type']) ? $attachment['mime_type'] : '';

        if (strpos($url, 'crm.pardodlaimigs.lv') !== false) {
            pdc_log('Skip CRM URL (not accessible): ' . $name);
            continue;
        }

        $tmp = @download_url($url, 15);
        if (is_wp_error($tmp)) {
            pdc_log('download failed: ' . $name . ': ' . $tmp->get_error_message());
            continue;
        }

        $file_array = ['name' => $name, 'tmp_name' => $tmp];
        $media_id = (int) media_handle_sideload($file_array, $post_id, $name);
        if (is_wp_error($media_id)) {
            @unlink($tmp);
            pdc_log('sideload failed: ' . $name . ': ' . $media_id->get_error_message());
            continue;
        }

        update_post_meta($media_id, '_pdc_crm_attachment_url', $url);

        $is_image = (strpos($mime, 'image/') === 0 || preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $name));
        if ($is_image) {
            $image_ids[] = $media_id;
        }
    }

    $gallery = implode('|', $image_ids);
    update_post_meta($post_id, 'real_estate_property_images', $gallery);

    if ($image_ids) {
        set_post_thumbnail($post_id, $image_ids[0]);
    } else {
        delete_post_thumbnail($post_id);
    }

    pdc_log('Post #' . $post_id . ': ' . count($image_ids) . ' images (' . count($need_download) . ' downloaded)');
    return $gallery;
}

function pdc_fetch_json($url) {
    $response = wp_remote_get($url, [
        'headers' => [
            'X-CRM-API-Key' => PDC_CRM_API_KEY,
            'Accept'        => 'application/json',
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        pdc_log('Fetch failed for ' . $url . ': ' . $response->get_error_message());
        return null;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        pdc_log('HTTP ' . $code . ' for ' . $url);
        return null;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

function pdc_sync_agent_avatar($post_id, $avatar_url) {
    if (empty($avatar_url)) {
        delete_post_thumbnail($post_id);
        return;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $url = esc_url_raw($avatar_url);
    $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'avatar.jpg');
    if ($filename === '') { $filename = 'avatar.jpg'; }

    $existing = pdc_find_existing_media($filename);
    if ($existing > 0) {
        set_post_thumbnail($post_id, $existing);
        update_post_meta($existing, '_pdc_crm_agent_avatar_url', $url);
        return;
    }

    $tmp = @download_url($url, 15);
    if (is_wp_error($tmp)) {
        pdc_log('agent avatar download failed: ' . $filename . ': ' . $tmp->get_error_message() . ' url=' . $url);
        return;
    }
    $file_array = ['name' => $filename, 'tmp_name' => $tmp];
    $media_id = (int) media_handle_sideload($file_array, $post_id, $filename);
    if (is_wp_error($media_id)) {
        @unlink($tmp);
        pdc_log('agent avatar sideload failed: ' . $filename . ': ' . $media_id->get_error_message());
        return;
    }
    update_post_meta($media_id, '_pdc_crm_agent_avatar_url', $url);
    set_post_thumbnail($post_id, $media_id);
    pdc_log('Agent #' . $post_id . ' avatar set to ' . $media_id);
}

function pdc_sync_agents() {
    $data = pdc_fetch_json(PDC_CRM_AGENTS_URL);
    if (! $data || ! isset($data['agents'])) {
        pdc_log('No agents data from CRM');
        return [];
    }

    $agent_map = [];
    foreach ($data['agents'] as $agent) {
        $crm_id = isset($agent['id']) ? $agent['id'] : 0;
        $name = isset($agent['name']) ? $agent['name'] : '';
        $email = isset($agent['email']) ? $agent['email'] : '';
        $phone = isset($agent['phone']) ? $agent['phone'] : '';
        $position = isset($agent['position']) ? $agent['position'] : '';
        $description = isset($agent['description']) ? $agent['description'] : '';
        $avatar_url = isset($agent['avatar_url']) ? $agent['avatar_url'] : '';
        $facebook_url = isset($agent['facebook_url']) ? $agent['facebook_url'] : '';
        $instagram_url = isset($agent['instagram_url']) ? $agent['instagram_url'] : '';
        $linkedin_url = isset($agent['linkedin_url']) ? $agent['linkedin_url'] : '';
        $website_url = isset($agent['website_url']) ? $agent['website_url'] : '';
        if ($crm_id <= 0 || empty($name)) { continue; }

        $existing = get_posts([
            'post_type'   => 'agent',
            'meta_key'    => '_pdc_crm_agent_id',
            'meta_value'  => $crm_id,
            'numberposts' => 1,
            'post_status' => 'any',
        ]);

        $post_id = $existing ? $existing[0]->ID : 0;

        if ($post_id) {
            wp_update_post(['ID' => $post_id, 'post_title' => $name, 'post_content' => $description, 'post_status' => 'publish']);
        } else {
            $post_id = wp_insert_post([
                'post_title'  => $name,
                'post_content' => $description,
                'post_type'   => 'agent',
                'post_status' => 'publish',
            ], true);
            if (is_wp_error($post_id)) {
                pdc_log('Agent insert failed: ' . $name . ': ' . $post_id->get_error_message());
                continue;
            }
        }

        update_post_meta($post_id, '_pdc_crm_agent_id', $crm_id);
        update_post_meta($post_id, 'real_estate_agent_email', $email);
        update_post_meta($post_id, 'real_estate_agent_mobile_number', $phone);
        update_post_meta($post_id, 'real_estate_agent_position', $position);
        update_post_meta($post_id, 'real_estate_agent_description', $description);
        update_post_meta($post_id, 'real_estate_agent_facebook_url', $facebook_url);
        update_post_meta($post_id, 'real_estate_agent_instagram_url', $instagram_url);
        update_post_meta($post_id, 'real_estate_agent_linkedin_url', $linkedin_url);
        update_post_meta($post_id, 'real_estate_agent_website_url', $website_url);
        update_post_meta($post_id, 'real_estate_agent_display_option', 'agent_info');

        pdc_sync_agent_avatar($post_id, $avatar_url);

        $agent_map[$name] = $post_id;
    }

    pdc_log('Synced ' . count($agent_map) . ' agents');
    return $agent_map;
}

function pdc_upsert_property($data, $agent_map = []) {
    $crm_id   = isset($data['crm_id']) ? $data['crm_id'] : 0;
    $title    = isset($data['title']) ? $data['title'] : '';
    $slug     = isset($data['slug']) ? $data['slug'] : sanitize_title($title);
    $content  = isset($data['description']) ? $data['description'] : '';
    $price    = isset($data['price']) ? $data['price'] : 0;
    $currency = isset($data['currency']) ? $data['currency'] : 'EUR';
    $category = isset($data['category']) ? $data['category'] : '';
    $status   = isset($data['status']) ? $data['status'] : 'draft';
    $beds     = isset($data['beds']) ? $data['beds'] : null;
    $baths    = isset($data['baths']) ? $data['baths'] : null;
    $size_m2  = isset($data['size_m2']) ? $data['size_m2'] : null;
    $land_m2  = isset($data['land_m2']) ? $data['land_m2'] : null;
    $kadastra = isset($data['kadastra_nr']) ? $data['kadastra_nr'] : '';
    $city     = isset($data['city']) ? $data['city'] : '';
    $address  = isset($data['address']) ? $data['address'] : '';
    $lat      = isset($data['lat']) ? $data['lat'] : null;
    $lng      = isset($data['lng']) ? $data['lng'] : null;
    $sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
    $agent_name = isset($data['agent']['name']) ? $data['agent']['name'] : '';

    $existing = get_posts([
        'post_type'   => 'property',
        'meta_key'    => '_pdc_crm_id',
        'meta_value'  => $crm_id,
        'numberposts' => -1,
        'post_status' => 'any',
    ]);

    $post_id = 0;
    if (! empty($existing)) {
        $post_id = $existing[0]->ID;
        if (count($existing) > 1) {
            foreach (array_slice($existing, 1) as $dup) {
                wp_update_post(['ID' => $dup->ID, 'post_status' => 'private']);
            }
        }
    }

    $wp_status = pdc_map_status($status);

    $post_data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
        'post_status'  => $wp_status,
        'post_type'    => 'property',
        'menu_order'   => $sort_order,
    ];

    if ($post_id) {
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
    } else {
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            pdc_log('Insert failed for ' . $title . ': ' . $post_id->get_error_message());
            return 0;
        }
    }

    update_post_meta($post_id, '_pdc_crm_id', $crm_id);
    update_post_meta($post_id, '_pdc_last_sync', current_time('mysql'));

    update_post_meta($post_id, 'real_estate_property_price', $price);
    update_post_meta($post_id, 'real_estate_property_price_unit', '1');
    update_post_meta($post_id, 'real_estate_property_price_short', $price);
    update_post_meta($post_id, 'real_estate_property_price_on_call', 0);
    update_post_meta($post_id, 'real_estate_property_identity', $post_id);
    // Size may be NULL for land-only properties (e.g., Zeme) — store 0 so ERE size slider (0-3000) doesn't exclude them via meta_query
    update_post_meta($post_id, 'real_estate_property_size', $size_m2 !== null && $size_m2 !== '' ? $size_m2 : 0);
    update_post_meta($post_id, 'real_estate_property_land', $land_m2 ? round($land_m2 / 10000, 2) : '');
    update_post_meta($post_id, 'real_estate_property_bedrooms', $beds);
    update_post_meta($post_id, 'real_estate_property_bathrooms', $baths);
    update_post_meta($post_id, 'real_estate_property_address', $address);
    update_post_meta($post_id, 'real_estate_property_country', 'LV');
    // ERE expects property_location as array ['location' => 'lat,lng', 'address' => '...']
    // Must pass array directly — WP will serialize once. Passing serialize() causes double-serialization
    // (s:"a:2:{...}") which breaks ERE maps. Coords are critical for LV addresses that don't geocode reliably.
    update_post_meta($post_id, 'real_estate_property_location', [
        'location' => ($lat && $lng) ? $lat . ',' . $lng : '',
        'address'  => $address,
    ]);

    pdc_sync_attachments($post_id, isset($data['attachments']) ? $data['attachments'] : []);

    if (! empty($agent_name) && isset($agent_map[$agent_name])) {
        update_post_meta($post_id, 'real_estate_property_agent', $agent_map[$agent_name]);
        update_post_meta($post_id, 'real_estate_agent_display_option', 'agent_info');
    }

    if ($category) {
        $cat_id = pdc_ensure_category($category);
        if ($cat_id) {
            wp_set_object_terms($post_id, $cat_id, 'property-status');
        }
    }

    if ($city) {
        $city_term = term_exists($city, 'property-city');
        if (! $city_term) {
            $city_term = wp_insert_term($city, 'property-city');
        }
        if (! is_wp_error($city_term)) {
            wp_set_object_terms($post_id, (int) $city_term['term_id'], 'property-city');
        }
    }

    return (int) $post_id;
}

function pdc_full_sync() {
    ignore_user_abort(true);
    set_time_limit(0);

    $start = time();
    pdc_log('Sync started');

    $agent_map = pdc_sync_agents();

    $data = pdc_fetch_json(PDC_CRM_API_URL);
    if (! $data || ! isset($data['properties'])) {
        pdc_log('No properties data from CRM');
        return 0;
    }

    $properties = $data['properties'];
    $synced = 0;

    foreach ($properties as $prop) {
        $result = pdc_upsert_property($prop, $agent_map);
        if ($result > 0) { $synced++; }
    }

    $crm_ids = [];
    foreach ($properties as $prop) {
        $crm_ids[] = isset($prop['crm_id']) ? $prop['crm_id'] : 0;
    }

    $orphan_posts = get_posts([
        'post_type'   => 'property',
        'numberposts' => -1,
        'post_status' => 'any',
    ]);
    foreach ($orphan_posts as $orphan) {
        $orphan_crm_id = (int) get_post_meta($orphan->ID, '_pdc_crm_id', true);
        if (! in_array($orphan_crm_id, $crm_ids, true)) {
            wp_update_post(['ID' => $orphan->ID, 'post_status' => 'private']);
        }
    }

    $elapsed = time() - $start;
    pdc_log('Sync completed: ' . $synced . ' properties in ' . $elapsed . 's');

    update_option('pdc_last_sync', current_time('mysql'));
    update_option('pdc_synced_count', $synced);
    return $synced;
}

add_action('init', function () {
    if (! wp_next_scheduled('pdc_crm_sync_hook')) {
        wp_schedule_event(time(), 'pdc_five_minute', 'pdc_crm_sync_hook');
    }
});

add_filter('cron_schedules', function ($schedules) {
    $schedules['pdc_five_minute'] = [
        'interval' => PDC_SYNC_INTERVAL,
        'display'  => __('Every 5 Minutes (PDC CRM)'),
    ];
    return $schedules;
});

add_action('pdc_crm_sync_hook', 'pdc_full_sync');

add_action('admin_menu', function () {
    add_management_page(
        'CRM Property Sync',
        'CRM Property Sync',
        'manage_options',
        'pdc-crm-sync',
        function () {
            if (isset($_POST['pdc_sync_now']) && check_admin_referer('pdc_sync')) {
                $count = pdc_full_sync();
                echo '<div class="notice notice-success"><p>Synced ' . esc_html($count) . ' properties from CRM.</p></div>';
            }
            $last = get_option('pdc_last_sync', 'Never');
            $count = get_option('pdc_synced_count', 0);
            echo '<div class="wrap">';
            echo '<h1>CRM Property Sync</h1>';
            echo '<p>Last sync: <strong>' . esc_html($last) . '</strong> &middot; Properties synced: <strong>' . esc_html($count) . '</strong></p>';
            echo '<form method="post">';
            wp_nonce_field('pdc_sync');
            echo '<button type="submit" name="pdc_sync_now" class="button button-primary">Sync Now</button>';
            echo '</form>';
            echo '<p>Automatic sync runs every 5 minutes via WP-Cron.</p>';
            echo '</div>';
        }
    );
});

add_filter('cron_schedules', function ($schedules) {
    $schedules['pdc_five_minute'] = [
        'interval' => PDC_SYNC_INTERVAL,
        'display'  => __('Every 5 Minutes (PDC CRM)'),
    ];
    return $schedules;
});

add_action('pdc_crm_sync_hook', 'pdc_full_sync');

add_action('admin_menu', function () {
    add_management_page(
        'CRM Property Sync',
        'CRM Property Sync',
        'manage_options',
        'pdc-crm-sync',
        function () {
            if (isset($_POST['pdc_sync_now']) && check_admin_referer('pdc_sync')) {
                $count = pdc_full_sync();
                echo '<div class="notice notice-success"><p>Synced ' . esc_html($count) . ' properties from CRM.</p></div>';
            }
            $last = get_option('pdc_last_sync', 'Never');
            $count = get_option('pdc_synced_count', 0);
            echo '<div class="wrap">';
            echo '<h1>CRM Property Sync</h1>';
            echo '<p>Last sync: <strong>' . esc_html($last) . '</strong> &middot; Properties synced: <strong>' . esc_html($count) . '</strong></p>';
            echo '<form method="post">';
            wp_nonce_field('pdc_sync');
            echo '<button type="submit" name="pdc_sync_now" class="button button-primary">Sync Now</button>';
            echo '</form>';
            echo '<p>Automatic sync runs every 5 minutes via WP-Cron.</p>';
            echo '</div>';
        }
    );
});
