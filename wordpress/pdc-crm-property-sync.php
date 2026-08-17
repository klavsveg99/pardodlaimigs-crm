<?php

/**
 * Plugin Name: Pārdod Laimīgs CRM Property Sync
 * Description: Pulls property data from CRM and overwrites WordPress property posts. CRM is the single source of truth.
 * Version: 1.0.0
 * Author: Pārdod Laimīgs
 *
 * INSTALLATION:
 * 1. Drop this file into wp-content/mu-plugins/ (or wp-content/plugins/ and activate)
 * 2. Configure the constants below
 * 3. The sync runs via WP-Cron every 5 minutes, or trigger manually from WP Admin → Tools
 */
if (! defined('ABSPATH')) {
    exit;
}

// ─── Configuration ───────────────────────────────────────────────
define('PDC_CRM_API_URL', 'https://crm.pardodlaimigs.lv/api/crm/properties');
define('PDC_CRM_API_KEY', 'YOUR_CRM_API_KEY_HERE'); // Match WP_CRM_API_KEY from CRM private/config.php
define('PDC_SYNC_INTERVAL', 5 * MINUTE_IN_SECONDS); // Every 5 minutes

// ─── Status mapping: CRM status → WordPress post_status ──────────
function pdc_map_status(string $crm_status): string
{
    return match ($crm_status) {
        'published' => 'publish',
        'draft' => 'draft',
        'expired' => 'expired',     // Requires Essential Real Estate support
        'hidden' => 'private',
        'sold' => 'publish',     // Keep published, mark as sold via meta
        default => 'draft',
    };
}

// ─── Category mapping: CRM category → WordPress term ─────────────
function pdc_ensure_category(string $category_name): int
{
    $term = term_exists($category_name, 'property-status');
    if (! $term) {
        $term = wp_insert_term($category_name, 'property-status');
    }

    return is_wp_error($term) ? 0 : (int) $term['term_id'];
}

// ─── Fetch all properties from CRM ───────────────────────────────
function pdc_fetch_properties(): array
{
    $response = wp_remote_get(PDC_CRM_API_URL, [
        'headers' => [
            'X-CRM-API-Key' => PDC_CRM_API_KEY,
            'Accept' => 'application/json',
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('[PDC CRM] Fetch failed: '.$response->get_error_message());

        return [];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        error_log('[PDC CRM] HTTP '.$code);

        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return $body['properties'] ?? [];
}

// ─── Upsert a single property: overwrite ALL fields ──────────────
function pdc_upsert_property(array $data): int
{
    $crm_id = $data['crm_id'] ?? 0;
    $title = $data['title'] ?? '';
    $slug = $data['slug'] ?? sanitize_title($title);
    $content = $data['description'] ?? '';
    $price = $data['price'] ?? 0;
    $currency = $data['currency'] ?? 'EUR';
    $category = $data['category'] ?? '';
    $status = $data['status'] ?? 'draft';
    $beds = $data['beds'] ?? null;
    $baths = $data['baths'] ?? null;
    $size_m2 = $data['size_m2'] ?? null;
    $land_m2 = $data['land_m2'] ?? null;
    $kadastra = $data['kadastra_nr'] ?? '';
    $city = $data['city'] ?? '';
    $address = $data['address'] ?? '';
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;

    // Check if property already exists (by CRM ID stored in meta)
    $existing = get_posts([
        'post_type' => 'property',
        'meta_key' => '_pdc_crm_id',
        'meta_value' => $crm_id,
        'numberposts' => 1,
        'post_status' => 'any',
    ]);

    $post_id = $existing ? $existing[0]->ID : 0;

    // Map CRM status to WordPress status
    $wp_status = pdc_map_status($status);

    // If sold, force status to publish (sold is shown via meta)
    $is_sold = ($status === 'sold');

    $post_data = [
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
        'post_status' => $wp_status,
        'post_type' => 'property',
        'menu_order' => 0,
    ];

    if ($post_id) {
        wp_update_post($post_data + ['ID' => $post_id]);
    } else {
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            error_log('[PDC CRM] Insert failed for '.$title.': '.$post_id->get_error_message());

            return 0;
        }
    }

    // ── Overwrite ALL meta fields (CRM is source of truth) ───────
    // Essential Real Estate meta keys (ere_property_*)
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

    // Category (taxonomy)
    if ($category) {
        $cat_id = pdc_ensure_category($category);
        if ($cat_id) {
            wp_set_object_terms($post_id, $cat_id, 'property-status');
        }
    }

    return (int) $post_id;
}

// ─── Full sync: fetch all CRM properties, overwrite WordPress ────
function pdc_full_sync(): int
{
    $properties = pdc_fetch_properties();
    $synced = 0;

    foreach ($properties as $prop) {
        $result = pdc_upsert_property($prop);
        if ($result > 0) {
            $synced++;
        }
    }

    // Hide WordPress properties that no longer exist in CRM
    $crm_ids = array_column($properties, 'crm_id');
    $orphan_args = [
        'post_type' => 'property',
        'meta_key' => '_pdc_crm_id',
        'numberposts' => -1,
        'post_status' => 'any',
    ];
    $orphan_posts = get_posts($orphan_args);
    foreach ($orphan_posts as $orphan) {
        $orphan_crm_id = (int) get_post_meta($orphan->ID, '_pdc_crm_id', true);
        if (! in_array($orphan_crm_id, $crm_ids, true)) {
            wp_update_post([
                'ID' => $orphan->ID,
                'post_status' => 'private',
            ]);
        }
    }

    update_option('pdc_last_sync', current_time('mysql'));
    update_option('pdc_synced_count', $synced);

    return $synced;
}

// ─── WP-Cron scheduling ──────────────────────────────────────────
register_activation_hook(__FILE__, function () {
    if (! wp_next_scheduled('pdc_crm_sync_hook')) {
        wp_schedule_event(time(), 'pdc_five_minute', 'pdc_crm_sync_hook');
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('pdc_crm_sync_hook');
});

add_filter('cron_schedules', function ($schedules) {
    $schedules['pdc_five_minute'] = [
        'interval' => PDC_SYNC_INTERVAL,
        'display' => __('Every 5 Minutes (PDC CRM)'),
    ];

    return $schedules;
});

add_action('pdc_crm_sync_hook', 'pdc_full_sync');

// ─── WP-CLI manual sync ──────────────────────────────────────────
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('pdc sync', function () {
        WP_CLI::log('Starting CRM property sync...');
        $count = pdc_full_sync();
        WP_CLI::success("Synced {$count} properties from CRM.");
    });
}

// ─── Admin page under Tools for manual trigger ───────────────────
add_action('admin_menu', function () {
    add_management_page(
        'CRM Property Sync',
        'CRM Property Sync',
        'manage_options',
        'pdc-crm-sync',
        'pdc_sync_admin_page'
    );
});

function pdc_sync_admin_page()
{
    if (isset($_POST['pdc_sync_now']) && check_admin_referer('pdc_sync')) {
        $count = pdc_full_sync();
        echo '<div class="notice notice-success"><p>Synced '.esc_html($count).' properties from CRM.</p></div>';
    }

    $last = get_option('pdc_last_sync', 'Never');
    $count = get_option('pdc_synced_count', 0);

    echo '<div class="wrap">';
    echo '<h1>CRM Property Sync</h1>';
    echo '<p>Last sync: <strong>'.esc_html($last).'</strong> · Properties synced: <strong>'.esc_html($count).'</strong></p>';
    echo '<form method="post">';
    wp_nonce_field('pdc_sync');
    echo '<button type="submit" name="pdc_sync_now" class="button button-primary">Sync Now</button>';
    echo '</form>';
    echo '<p>Automatic sync runs every 5 minutes via WP-Cron. Ensure your cron is active.</p>';
    echo '</div>';
}
