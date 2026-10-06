<?php

/**
 * Plugin Name: Pārdod Laimīgs Google atsauksmes
 * Description: Google atsauksmju saites: apaļa "G" ikonas poga virs sīkdatņu ikonas un "G" ikona galvenajā ēdienā blakus Facebook.
 * Version: 1.3.0
 * Author: Pārdod Laimīgs
 */
if (! defined('ABSPATH')) {
    exit;
}

define('PDC_GOOGLE_REVIEW_URL', 'https://g.page/r/CRZd6XZu2hhmEAE/review');

/**
 * Google atsauksmju ikona galvenajā ēdienā (hederī) blakus Facebook ikonai.
 * Sociālās ikonas ir parasta izvēlne ar <i class="fa-brands ..."> elementiem,
 * tāpēc pievienojam jaunu <li> tieši aiz Facebook <li> (filtrs nostrādā gan
 * galvenajā hederī, gan mobilajā izvēlnē).
 */
add_filter('wp_nav_menu_items', function ($items, $args) {
    if (is_admin() || stripos($items, 'fa-facebook') === false || strpos($items, 'pdc-google-menu-item') !== false) {
        return $items;
    }

    $item = '<li class="social-menu menu-item pdc-google-menu-item">'
        .'<a class="menu-link" target="_blank" rel="noopener" href="'.esc_url(PDC_GOOGLE_REVIEW_URL).'" aria-label="Google atsauksmes">'
        .'<i class="fa-brands fa-google" aria-hidden="true"></i>'
        .'</a></li>';

    $facebook = stripos($items, 'fa-facebook');
    $end = strpos($items, '</li>', $facebook);

    if ($end === false) {
        return $items.$item;
    }

    return substr($items, 0, $end + 5).$item.substr($items, $end + 5);
}, 10, 2);

/**
 * Peldoša Google atsauksmju poga frontendā. Izvade ir tikai CSS + JS
 * (poga tiek izveidota ar JavaScript), tāpēc tas nav saistīts ar tēmu
 * vai lapas saturu. Poga ir 45x45 apaļa, 15px no kreisās malas un 15px
 * virs sīkdatņu (cky) ikonas, kas arī ir 45x45 ar 15px atkāpēm, tāpēc
 * abas ikonas stāv vienā vertikālā rindā gan datorā, gan mobilajā.
 */
add_action('wp_footer', function (): void {
    if (is_admin() || is_feed()) {
        return;
    }
    ?>
    <style>
        .pdc-greview {
            position: fixed;
            left: 15px;
            /* 15px (apakša) + 45px (sīkdatņu ikona) + 15px (atstarpe) */
            bottom: 75px;
            z-index: 9998;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 50%;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.16);
            text-decoration: none !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .pdc-greview:hover,
        .pdc-greview:focus,
        .pdc-greview:focus-visible {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.22);
            text-decoration: none !important;
        }

        .pdc-greview,
        .pdc-greview:hover,
        .pdc-greview:focus,
        .pdc-greview:focus-visible,
        .pdc-greview * {
            text-decoration: none !important;
        }

        .pdc-greview__logo {
            width: 24px;
            height: 24px;
            display: block;
        }

        /* Buttonizer kontaktpoga (apakšā labajā stūrī): tāds pats attālums no
           apakšas un malas (15px) kā Google atsauksmju pogai un sīkdatņu pogai.
           Buttonizer pats liek 50px (20px mobilajā). */
        .buttonizer-group {
            bottom: 15px !important;
            right: 15px !important;
        }
    </style>
    <script>
        (function () {
            var reviewUrl = <?php echo wp_json_encode(PDC_GOOGLE_REVIEW_URL); ?>;

            function build() {
                if (document.querySelector('.pdc-greview')) {
                    return;
                }

                var link = document.createElement('a');
                link.className = 'pdc-greview';
                link.href = reviewUrl;
                link.target = '_blank';
                link.rel = 'noopener';
                link.setAttribute('aria-label', 'Atstāt atsauksmi Google');
                link.innerHTML =
                    '<svg class="pdc-greview__logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
                    + '<path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>'
                    + '<path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>'
                    + '<path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"/>'
                    + '<path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>'
                    + '</svg>';

                if (document.body) {
                    document.body.appendChild(link);
                } else {
                    document.addEventListener('DOMContentLoaded', function () {
                        document.body.appendChild(link);
                    });
                }
            }

            build();
        })();
    </script>
    <?php
});
