<?php

/**
 * Plugin Name: Pārdod Laimīgs Google atsauksmes
 * Description: Peldošs "Google atsauksmes" widgets vietnes kreisajā malā ar saiti uz Google atsauksmju lapu.
 * Version: 1.0.0
 * Author: Pārdod Laimīgs
 */
if (! defined('ABSPATH')) {
    exit;
}

define('PDC_GOOGLE_REVIEW_URL', 'https://g.page/r/CRZd6XZu2hhmEAE/review');

/**
 * Peldošs Google atsauksmju widgets frontendā. Izvade ir tikai CSS + JS
 * (widgets tiek izveidots ar JavaScript), tāpēc tas nav saistīts ar tēmu
 * vai lapas saturu un parādās visā vietnē.
 */
add_action('wp_footer', function (): void {
    if (is_admin() || is_feed()) {
        return;
    }
    ?>
    <style>
        .pdc-greview {
            position: fixed;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            z-index: 9998;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.85rem 0.6rem 0.65rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 0;
            border-radius: 0 0.65rem 0.65rem 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14);
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.1;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .pdc-greview:hover,
        .pdc-greview:focus-visible {
            transform: translateY(-50%) translateX(2px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.2);
        }

        .pdc-greview__logo {
            width: 24px;
            height: 24px;
            flex: none;
        }

        .pdc-greview__text {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .pdc-greview__title {
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
        }

        .pdc-greview__sub {
            font-size: 11px;
            color: #6b7280;
        }

        @media (max-width: 600px) {
            .pdc-greview {
                padding: 0.5rem;
            }

            .pdc-greview__text {
                display: none;
            }
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
                    + '</svg>'
                    + '<span class="pdc-greview__text">'
                    + '<span class="pdc-greview__title">Google atsauksmes</span>'
                    + '<span class="pdc-greview__sub">Atstāt atsauksmi</span>'
                    + '</span>';

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
