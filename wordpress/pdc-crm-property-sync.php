<?php

/**
 * Plugin Name: Pārdod Laimīgs CRM Property Sync
 * Description: Pulls property data from CRM and overwrites WordPress property posts. CRM is the single source of truth.
 * Version: 1.1.0
 * Author: Pārdod Laimīgs
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PDC_CRM_API_URL', 'https://crm.pardodlaimigs.lv/api/crm/properties');
define('PDC_CRM_API_KEY', 'g124gqAEeDe3125v523bVScac4v');
define('PDC_SYNC_INTERVAL', 5 * MINUTE_IN_SECONDS);

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
    $term = term_exists($category_name, 'property-status');
    if (! $term) {
        $term = wp_insert_term($category_name, 'property-status');
    }
    return is_wp_error($term) ? 0 : (int) $term['term_id'];
}

function pdc_sync_attachments($post_id, $attachments) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $image_ids = [];
    $documents = [];

    foreach ($attachments as $attachment) {
        $url = esc_url_raw($attachment['url'] ?? '');
        $name = sanitize_file_name($attachment['name'] ?? basename((string) $url));
        $mime = (string) ($attachment['mime_type'] ?? '');

        if ($url === '') { continue; }

        $existing = get_posts([
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_key'    => '_pdc_crm_attachment_url',
            'meta_value'  => $url,
            'numberposts' => 1,
        ]);

        $media_id = $existing ? (int) $existing[0]->ID : 0;
        if (! $media_id) {
            if (strpos($mime, 'image/') === 0) {
                $media_id = (int) media_sideload_image($url, $post_id, $name, 'id');
            } else {
                $tmp = download_url($url);
                if (! is_wp_error($tmp)) {
                    $media_id = (int) media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], $post_id, $name);
                    if (is_wp_error($media_id)) { @unlink($tmp); $media_id = 0; }
                }
            }
            if ($media_id > 0) {
                update_post_meta($media_id, '_pdc_crm_attachment_url', $url);
            }
        }

        if ($media_id <= 0) { continue; }

        if (strpos($mime, 'image/') === 0) {
            $image_ids[] = $media_id;
        } else {
            $documents[] = ['url' => $url, 'name' => $name, 'mime_type' => $mime];
        }
    }

    update_post_meta($post_id, 'ere_property_gallery', implode(',', $image_ids));
    update_post_meta($post_id, '_pdc_crm_documents', $documents);

    if ($image_ids) {
        set_post_thumbnail($post_id, $image_ids[0]);
    } else {
        delete_post_thumbnail($post_id);
    }
}

function pdc_fetch_properties() {
    $response = wp_remote_get(PDC_CRM_API_URL, [
        'headers' => [
            'X-CRM-API-Key' => PDC_CRM_API_KEY,
            'Accept'        => 'application/json',
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('[PDC CRM] Fetch failed: ' . $response->get_error_message());
        return [];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        error_log('[PDC CRM] HTTP ' . $code);
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return isset($body['properties']) ? $body['properties'] : [];
}

function pdc_upsert_property($data) {
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

    $existing = get_posts([
        'post_type'   => 'property',
        'meta_key'    => '_pdc_crm_id',
        'meta_value'  => $crm_id,
        'numberposts' => 1,
        'post_status' => 'any',
    ]);

    $post_id = $existing ? $existing[0]->ID : 0;
    $wp_status = pdc_map_status($status);

    $post_data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
        'post_status'  => $wp_status,
        'post_type'    => 'property',
        'menu_order'   => 0,
    ];

    if ($post_id) {
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
    } else {
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            error_log('[PDC CRM] Insert failed for ' . $title . ': ' . $post_id->get_error_message());
            return 0;
        }
    }

    update_post_meta($post_id, '_pdc_crm_id', $crm_id);
    update_post_meta($post_id, 'ere_property_price', $price);
    update_post_meta($post_id, 'ere_property_price_prefix', $currency);
    update_post_meta($post_id, 'ere_property_bedrooms', $beds);
    update_post_meta($post_id, 'ere_property_bathrooms', $baths);
    update_post_meta($post_id, 'ere_property_area', $size_m2);
    update_post_meta($post_id, 'ere_property_land_area', $land_m2);
    update_post_meta($post_id, 'ere_property_land_area_unit', 'm²');
    update_post_meta($post_id, 'ere_property_cadastral_number', $kadastra);
    update_post_meta($post_id, 'ere_property_address', $address);
    update_post_meta($post_id, 'ere_property_latitude', $lat);
    update_post_meta($post_id, 'ere_property_longitude', $lng);
    update_post_meta($post_id, '_pdc_last_sync', current_time('mysql'));
    pdc_sync_attachments($post_id, isset($data['attachments']) ? $data['attachments'] : []);

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
    $properties = pdc_fetch_properties();
    $synced = 0;

    foreach ($properties as $prop) {
        $result = pdc_upsert_property($prop);
        if ($result > 0) { $synced++; }
    }

    $crm_ids = [];
    foreach ($properties as $prop) {
        $crm_ids[] = isset($prop['crm_id']) ? $prop['crm_id'] : 0;
    }

    $orphan_posts = get_posts([
        'post_type'   => 'property',
        'meta_key'    => '_pdc_crm_id',
        'numberposts' => -1,
        'post_status' => 'any',
    ]);
    foreach ($orphan_posts as $orphan) {
        $orphan_crm_id = (int) get_post_meta($orphan->ID, '_pdc_crm_id', true);
        if (! in_array($orphan_crm_id, $crm_ids, true)) {
            wp_update_post(['ID' => $orphan->ID, 'post_status' => 'private']);
        }
    }

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
