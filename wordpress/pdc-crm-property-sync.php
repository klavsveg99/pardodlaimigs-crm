<?php

/**
 * Plugin Name: Pārdod Laimīgs CRM Property Sync
 * Description: Pulls property data from CRM and overwrites WordPress property posts. CRM is the single source of truth.
 * Version: 2.3.7
 * Author: Pārdod Laimīgs
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PDC_CRM_API_URL', 'https://crm.pardodlaimigs.lv/api/crm/properties');
define('PDC_CRM_API_KEY', 'WP_PDC_CRM_API_KEY_PROVISIONED_ON_SERVER');
define('PDC_CRM_AGENTS_URL', 'https://crm.pardodlaimigs.lv/api/crm/agents');
define('PDC_SYNC_INTERVAL', 5 * MINUTE_IN_SECONDS);

function pdc_log($msg) {
    error_log('[PDC CRM] ' . $msg);
}

/**
 * Purge the public page for a property post.
 *
 * Gallery and featured-image changes are meta-only updates: they never fire
 * save_post, so the LiteSpeed page cache would keep serving stale HTML with
 * the old gallery (the "text updated but images didn't" report). Purge the
 * post explicitly whenever the gallery or thumbnail actually changes.
 */
function pdc_purge_post_cache($post_id) {
    clean_post_cache($post_id);
    do_action('litespeed_purge_post', $post_id);
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
        return substr($path, strlen('wp-content/uploads/'));
    }
    if (strpos($path, 'storage/attachments/') === 0) {
        // Use a deterministic, time-independent storage path so re-syncs match existing
        // attachments instead of orphaning them when the calendar month rolls over.
        // First sync: store under pdc-crm/attachments/<basename> — WP can serve it from there.
        return 'pdc-crm/attachments/' . substr($path, strlen('storage/attachments/'));
    }
    if (strpos($path, 'storage/avatars/') === 0) {
        return 'pdc-crm/avatars/' . substr($path, strlen('storage/avatars/'));
    }
    if (strpos($path, 'storage/') === 0) {
        return 'pdc-crm/' . substr($path, strlen('storage/'));
    }
    return $path;
}

function pdc_proxy_url($path) {
    return 'https://crm.pardodlaimigs.lv/api/crm/attachment-proxy?path=' . rawurlencode($path) . '&_k=' . substr(hash_hmac('sha256', $path, PDC_CRM_API_KEY), 0, 16);
}

/**
 * Build the download URL for a CRM attachment. CRM-hosted files go through the
 * HMAC-signed proxy so a signed URL stays valid regardless of route names.
 */
function pdc_attachment_download_url($attachment) {
    $url = isset($attachment['url']) ? esc_url_raw($attachment['url']) : '';
    if ($url === '' || strpos($url, 'https://crm.pardodlaimigs.lv') !== 0) {
        return $url;
    }
    $path = isset($attachment['raw_path']) && $attachment['raw_path'] !== ''
        ? $attachment['raw_path']
        : (string) parse_url($url, PHP_URL_PATH);
    $path = ltrim($path, '/');
    if ($path === '') {
        return '';
    }
    $proxy = pdc_proxy_url($path);
    // Version the URL with the CRM file size so proxies/caches can never serve
    // a stale copy after the CRM re-optimises an image in place.
    $size = isset($attachment['size']) ? (int) $attachment['size'] : 0;
    return $size > 0 ? $proxy . '&v=' . $size : $proxy;
}

/**
 * Decide whether an existing WP attachment must be re-downloaded because the
 * CRM copy changed (e.g. after the CRM re-optimised its images). When the CRM
 * reports the file size this short-circuits without a network call; otherwise
 * it falls back to a HEAD request rate-limited to once per hour.
 */
function pdc_attachment_needs_refresh($media_id, $url, $declared_size) {
    $local = get_attached_file($media_id);
    if ($url === '' || $local === false || $local === '') {
        return false;
    }
    if (! is_file($local)) {
        return true;
    }

    $stored = (int) get_post_meta($media_id, '_pdc_crm_attachment_size', true);
    $local_size = (int) @filesize($local);
    if ($declared_size > 0 && $stored > 0 && $stored === $declared_size && $local_size > 0 && $local_size === $stored) {
        return false;
    }

    $checked = (int) get_post_meta($media_id, '_pdc_crm_attachment_checked_at', true);
    if ($checked > 0 && (time() - $checked) < HOUR_IN_SECONDS) {
        return false;
    }

    $response = wp_remote_head($url, ['timeout' => 15, 'redirection' => 5]);
    $remote_size = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_header($response, 'content-length');

    update_post_meta($media_id, '_pdc_crm_attachment_checked_at', time());

    if ($remote_size <= 0) {
        return false;
    }

    if ($stored > 0 && $stored === $remote_size) {
        return false;
    }

    if ($local_size > 0 && $local_size === $remote_size) {
        update_post_meta($media_id, '_pdc_crm_attachment_size', $remote_size);
        return false;
    }

    return true;
}

/**
 * Re-download the file behind an existing attachment, overwrite it in place and
 * regenerate its metadata + thumbnail sizes. Keeps the same media ID and URL so
 * galleries and references stay intact.
 */
function pdc_refresh_attachment_file($media_id, $url, $name) {
    $local = get_attached_file($media_id);
    if ($url === '' || $local === false || $local === '' || ! is_dir(dirname($local))) {
        return false;
    }

    $tmp = @download_url($url . '&_t=' . time(), 30);
    if (is_wp_error($tmp)) {
        pdc_log('refresh download failed: ' . $name . ': ' . $tmp->get_error_message());
        return false;
    }

    $ok = @rename($tmp, $local);
    if (! $ok) {
        $ok = @copy($tmp, $local);
        @unlink($tmp);
    }
    if (! $ok) {
        @unlink($tmp);
        return false;
    }

    $size = (int) @filesize($local);
    update_post_meta($media_id, '_pdc_crm_attachment_size', $size);
    update_post_meta($media_id, '_pdc_crm_attachment_checked_at', time());

    $meta = wp_generate_attachment_metadata($media_id, $local);
    if ($meta && ! is_wp_error($meta)) {
        wp_update_attachment_metadata($media_id, $meta);
    }

    pdc_log('Refreshed attachment #' . $media_id . ' (' . $name . ')');
    return true;
}

function pdc_upload_to_subdir($subdir) {
    add_filter('upload_dir', function ($upload) use ($subdir) {
        $upload['subdir'] = $subdir;
        $upload['path'] = rtrim($upload['basedir'], '/') . '/' . trim($subdir, '/');
        $upload['url']  = rtrim($upload['baseurl'], '/') . '/' . trim($subdir, '/');
        return $upload;
    });
}

function pdc_clear_upload_dir_filter() {
    remove_all_filters('upload_dir');
}

function pdc_create_attachment_from_sideload($tmp_file, $name, $post_id, $subdir) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    pdc_upload_to_subdir($subdir);
    try {
        $result = media_handle_sideload(
            ['name' => $name, 'tmp_name' => $tmp_file],
            $post_id,
            $name
        );
    } finally {
        pdc_clear_upload_dir_filter();
    }
    return $result;
}

function pdc_find_existing_media_by_path($path, $url = '') {
    global $wpdb;
    if ($path === '' && $url === '') {
        return 0;
    }

    // 0. Match on the CRM source URL first — unambiguous across syncs even when
    //    WP had to rename the stored file (e.g. "<name>-10.jpg" collision). Only
    //    existing attachments qualify (orphaned postmeta of deleted posts is ignored)
    //    and the newest match wins.
    //    VERIFY the match: the pre-2.3 basename matcher stamped CRM URLs onto
    //    other properties' files ("1.jpg", "5.jpg", ...). Such a stale stamp
    //    points at a file in a different directory — ignore it so the sync
    //    re-resolves to the correct file (or downloads it fresh) instead of
    //    re-attaching the wrong property's photo as featured image.
    if ($url !== '') {
        $by_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT pm.post_id
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_pdc_crm_attachment_url'
                   AND pm.meta_value = %s
                   AND p.post_type = 'attachment'
                 ORDER BY pm.post_id DESC
                 LIMIT 1",
                $url
            )
        );
        if ($by_url && (int) $by_url > 0) {
            $stored_file = get_post_meta((int) $by_url, '_wp_attached_file', true);
            if (is_string($stored_file) && $stored_file !== ''
                && pdc_same_upload_dir($stored_file, $path)
                && pdc_same_upload_basename($stored_file, $path)) {
                return (int) $by_url;
            }
            // Stale/contaminated stamp — ignore this match and fall through.
            pdc_log('Ignoring stale URL stamp on #' . (int) $by_url . ' (' . $stored_file . ') for ' . $path);
        }
    }

    // 1. Exact deterministic path match (new pdc-crm/ storage layout)
    $meta_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            $path
        )
    );
    if ($meta_id && (int) $meta_id > 0) {
        return (int) $meta_id;
    }

    // 2. Legacy path: previous version stored CRM attachments under the current
    //    YYYY/MM/<basename>. Try every recent YYYY/MM with the same basename so
    //    a re-sync doesn't generate orphan attachments when the calendar month rolls.
    $basename = basename($path);
    if ($basename === '' || $basename === '.' || $basename === '/') {
        return 0;
    }

    // 2. Legacy path: previous version stored CRM attachments under the current
    //    YYYY/MM/<basename>. Probe the current and previous month only — this is
    //    enough to cover the rollover case without ballooning query count.
    $current_year  = (int) gmdate('Y');
    $current_month = (int) gmdate('m');
    $month_probes = [$current_month];
    if ($current_month > 1)  { $month_probes[] = $current_month - 1; }
    if ($current_month === 1) { $month_probes[] = 12; $year_candidates = [$current_year, $current_year - 1]; }
    else                       { $year_candidates = [$current_year]; }
    foreach ($year_candidates as $year) {
        foreach ($month_probes as $month) {
            $legacy = sprintf('%04d/%02d/%s', $year, $month, $basename);
            $legacy_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
                    $legacy
                )
            );
            if ($legacy_id && (int) $legacy_id > 0) {
                update_post_meta((int) $legacy_id, '_wp_attached_file', $path);
                return (int) $legacy_id;
            }
        }
    }

    // 3. Last-resort basename fallback — SAME DIRECTORY ONLY.
    //    Matching "anywhere in the uploads tree" is what attached other
    //    properties' "1.jpg"/"5.jpg"/"6.jpg" as featured images. A fallback hit
    //    must live in the expected directory; otherwise download fresh.
    $base = preg_replace('/-\d+$/', '', preg_replace('/\.[^.]+$/', '', $basename));
    $ext  = pathinfo($basename, PATHINFO_EXTENSION);
    if ($base === '' || $ext === '') {
        return 0;
    }

    $dir = trim(dirname($path), '/');
    if ($dir === '' || $dir === '.') {
        return 0;
    }

    $like = $wpdb->esc_like($dir . '/' . $base . '.') . '%';
    $fallback = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attached_file'
               AND meta_value LIKE %s
             ORDER BY post_id DESC
             LIMIT 1",
            $like
        )
    );

    return $fallback && (int) $fallback > 0 ? (int) $fallback : 0;
}

/**
 * Same uploads-relative directory? (e.g. "2025/10" vs "2025/09" → false)
 */
function pdc_same_upload_dir($a, $b) {
    return trim(dirname((string) $a), '/') === trim(dirname((string) $b), '/');
}

/**
 * Same basename, tolerating WP collision renames ("5.jpg" vs "5-2.jpg") and
 * WP's sanitize_file_name() stripping leading underscores/dashes
 * ("_MG_8004-HDR.JPG" → "MG_8004-HDR-6.jpg"). Without the leading-char
 * tolerance those files miss the URL-stamp match and get re-downloaded on
 * every sync, churning duplicate attachments and slowing syncs to minutes.
 */
function pdc_same_upload_basename($a, $b) {
    $strip = function ($f) {
        $name = basename((string) $f);
        $name = preg_replace('/\.[^.]+$/', '', $name);
        $name = preg_replace('/-\d+$/', '', (string) $name);
        return preg_replace('/^[_-]+/', '', (string) $name);
    };
    $ea = pathinfo((string) $a, PATHINFO_EXTENSION);
    $eb = pathinfo((string) $b, PATHINFO_EXTENSION);
    return $strip($a) !== '' && strcasecmp($strip($a), $strip($b)) === 0
        && strcasecmp((string) $ea, (string) $eb) === 0;
}

function pdc_sync_attachments($post_id, $attachments) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $previous_gallery = (string) get_post_meta($post_id, 'real_estate_property_images', true);
    $previous_thumb = (int) get_post_thumbnail_id($post_id);

    if (empty($attachments)) {
        if ($previous_gallery !== '') {
            update_post_meta($post_id, 'real_estate_property_images', '');
            pdc_purge_post_cache($post_id);
        }
        if ($previous_thumb !== 0) {
            delete_post_thumbnail($post_id);
            pdc_purge_post_cache($post_id);
        }
        return '';
    }

    // CRM order is canonical: sort by sort_order so index 0 is the CRM featured image.
    if (is_array($attachments)) {
        usort($attachments, function ($a, $b) {
            $sa = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
            $sb = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;
            return $sa <=> $sb;
        });
    }

    $seen = [];
    $unique = [];
    foreach ($attachments as $attachment) {
        $url = isset($attachment['url']) ? esc_url_raw($attachment['url']) : '';
        if ($url === '') { continue; }
        $raw_path = parse_url($url, PHP_URL_PATH);
        $raw_path = $raw_path ? ltrim($raw_path, '/') : '';
        if ($raw_path === '') { continue; }
        $raw_path = preg_replace('#^storage/#', '', $raw_path, 1);
        $wp_path = pdc_relative_upload_path($url);
        if ($wp_path === '') { continue; }
        if (isset($seen[$wp_path])) { continue; }
        $seen[$wp_path] = true;
        $attachment['url'] = $url;
        $attachment['path'] = $wp_path;
        $attachment['raw_path'] = $raw_path;
        if (empty($attachment['name']) || $attachment['name'] === basename($url)) {
            $provided = isset($attachment['name']) ? (string) $attachment['name'] : '';
            $attachment['name'] = $provided !== '' ? $provided : basename($url);
        }
        $unique[] = $attachment;
    }

    // Single pass in CRM order — existing attachments are reused, missing ones
    // are downloaded inline. The previous two-phase version (all existing first,
    // then all downloads) scrambled gallery order whenever the CRM featured
    // image had not been downloaded yet, so the WP featured image mismatched CRM.
    $image_ids = [];
    $downloaded = 0;

    foreach ($unique as $attachment) {
        $url  = $attachment['url'];
        $path = $attachment['path'];
        $name = $attachment['name'];
        $mime = isset($attachment['mime_type']) ? $attachment['mime_type'] : '';

        $is_image = (strpos($mime, 'image/') === 0 || preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $name));
        if (! $is_image) {
            continue;
        }

        $media_id = pdc_find_existing_media_by_path($path, $url);

        if ($media_id > 0) {
            update_post_meta($media_id, '_pdc_crm_attachment_url', $url);
            $download_url = pdc_attachment_download_url($attachment);
            $declared_size = isset($attachment['size']) ? (int) $attachment['size'] : 0;
            if (pdc_attachment_needs_refresh($media_id, $download_url, $declared_size)) {
                pdc_refresh_attachment_file($media_id, $download_url, $name);
            }
            $image_ids[] = $media_id;
            continue;
        }

        if (str_starts_with($url, 'https://crm.pardodlaimigs.lv')) {
            if ($path === '') {
                pdc_log('Skip CRM URL (no path): ' . $name);
                continue;
            }
            $proxy_path = $attachment['raw_path'] ?? $path;
            $download_url = pdc_proxy_url($proxy_path) . '&_t=' . time();
        } else {
            $download_url = $url;
        }

        $tmp = @download_url($download_url, 15);
        if (is_wp_error($tmp)) {
            pdc_log('download failed: ' . $name . ': ' . $tmp->get_error_message());
            continue;
        }

        // Compute the deterministic subdirectory from the path so re-syncs land in the
        // same place even when the calendar month rolls over.
        $subdir = trim(dirname($path), '/');
        $media_id = pdc_create_attachment_from_sideload($tmp, $name, $post_id, $subdir);
        @unlink($tmp);
        if (is_wp_error($media_id)) {
            @unlink($tmp);
            pdc_log('sideload failed: ' . $name . ': ' . $media_id->get_error_message());
            continue;
        }

        update_post_meta($media_id, '_pdc_crm_attachment_url', $url);

        $sideloaded_size = (int) @filesize(get_attached_file($media_id));
        if ($sideloaded_size > 0) {
            update_post_meta($media_id, '_pdc_crm_attachment_size', $sideloaded_size);
        }

        $image_ids[] = (int) $media_id;
        $downloaded++;
    }

    // CRM order is canonical: the gallery is the attachment IDs joined in CRM
    // sort_order, so a pure reorder (same images, different order) still yields
    // a different string → meta update + cache purge. Updates and purges only
    // happen when the value actually changed, so the 5-minute sync stays quiet.
    $gallery = implode('|', $image_ids);
    if ($gallery !== $previous_gallery) {
        update_post_meta($post_id, 'real_estate_property_images', $gallery);
        pdc_purge_post_cache($post_id);
    }

    // Featured image must always mirror CRM: first attachment in CRM sort order.
    if ($image_ids) {
        $featured = (int) $image_ids[0];
        if ($previous_thumb !== $featured) {
            set_post_thumbnail($post_id, $featured);
            pdc_purge_post_cache($post_id);
        }
    } elseif ($previous_thumb !== 0) {
        delete_post_thumbnail($post_id);
        pdc_purge_post_cache($post_id);
    }

    pdc_log('Post #' . $post_id . ': ' . count($image_ids) . ' images (' . $downloaded . ' downloaded)');
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

    $url = esc_url_raw($avatar_url);
    $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'avatar.jpg');
    if ($filename === '') { $filename = 'avatar.jpg'; }

    global $wpdb;
    $existing_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_pdc_crm_agent_avatar_url' AND meta_value = %s LIMIT 1",
            $url
        )
    );
    if ($existing_id > 0) {
        set_post_thumbnail($post_id, $existing_id);
        return;
    }

    $existing = pdc_find_existing_media($filename);
    if ($existing > 0) {
        update_post_meta($existing, '_pdc_crm_agent_avatar_url', $url);
        set_post_thumbnail($post_id, $existing);
        return;
    }

    $tmp = @download_url($url, 15);
    if (is_wp_error($tmp)) {
        pdc_log('agent avatar download failed: ' . $filename . ': ' . $tmp->get_error_message() . ' url=' . $url);
        return;
    }

    // Place avatars in a stable subdirectory so re-syncs don't churn them monthly.
    $subdir = trim(dirname(pdc_relative_upload_path($url)), '/');
    $media_id = pdc_create_attachment_from_sideload($tmp, $filename, $post_id, $subdir ?: 'pdc-crm/avatars');
    @unlink($tmp);
    if (is_wp_error($media_id)) {
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
        $agent_map['id:' . $crm_id] = $post_id;
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
    $agent_crm_id = isset($data['agent']['id']) ? (int) $data['agent']['id'] : 0;

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

    $assigned_agent_id = 0;
    if ($agent_crm_id > 0 && isset($agent_map['id:' . $agent_crm_id])) {
        $assigned_agent_id = (int) $agent_map['id:' . $agent_crm_id];
    } elseif (! empty($agent_name) && isset($agent_map[$agent_name])) {
        $assigned_agent_id = (int) $agent_map[$agent_name];
    }
    if ($assigned_agent_id > 0) {
        update_post_meta($post_id, 'real_estate_property_agent', $assigned_agent_id);
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

    // Guard against overlapping runs: WP-Cron fires every 5 min but a full
    // sync with downloads takes longer — concurrent runs race each other,
    // duplicate downloads and flip-flop galleries/thumbnails.
    if (get_transient('pdc_sync_running')) {
        pdc_log('Sync skipped: another sync is already running');
        return 0;
    }
    set_transient('pdc_sync_running', time(), 15 * MINUTE_IN_SECONDS);

    $start = time();
    pdc_log('Sync started');

    try {

    // No system cron on the CRM host: trigger the Laravel scheduler remotely
    // so SyncWpForms (contact form sync) and other scheduled jobs run.
    wp_remote_get('https://crm.pardodlaimigs.lv/cron-schedule', [
        'timeout' => 60,
        'blocking' => false,
        'headers' => ['X-CRM-API-Key' => PDC_CRM_API_KEY],
    ]);

    $agent_map = pdc_sync_agents();

    $data = pdc_fetch_json(PDC_CRM_API_URL);
    if (! $data || ! isset($data['properties'])) {
        pdc_log('No properties data from CRM');
        delete_transient('pdc_sync_running');
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
    delete_transient('pdc_sync_running');
    return $synced;
    } finally {
        // Always release the lock, even on fatal errors mid-sync.
        delete_transient('pdc_sync_running');
    }
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
