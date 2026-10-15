<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Pārdod Laimīgs' }}</title>
    <meta name="description" content="{{ $pageDescription ?? $copy['heroBody'] ?? '' }}">
    <link rel="canonical" href="{{ $canonical }}">
    @foreach ($languageUrls as $code => $url)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $languageUrls['lv'] ?? $canonical }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('site-assets/images/brand/favicon.svg') }}">
    <meta name="theme-color" content="#285854">
    {{-- Font Awesome, for the quick-contact icon (same version and hash as the
         static preview build). --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
          integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('site-assets/site.css') }}?v=11">
    <script defer src="{{ asset('site-assets/site.js') }}?v=6"></script>
</head>
<body class="site">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ \App\Support\SiteUrls::path($locale) }}" aria-label="Pārdod Laimīgs">
            <img src="{{ asset('site-assets/images/brand/logo.svg') }}?v=2" alt="Pārdod Laimīgs">
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="{{ $copy['menuOpen'] ?? 'Atvērt izvēlni' }}"><span></span><span></span><span></span></button>
        <nav class="main-navigation" aria-label="{{ $copy['mainMenu'] ?? 'Galvenā izvēlne' }}">
            <a href="{{ \App\Support\SiteUrls::path($locale) }}#ipasumi">{{ $copy['navProperties'] ?? '' }}</a>
            <a href="{{ \App\Support\SiteUrls::path($locale, '/pardot/') }}">{{ $copy['navSell'] ?? '' }}</a>
            <a href="{{ \App\Support\SiteUrls::path($locale) }}#komanda">{{ $copy['navAbout'] ?? '' }}</a>
            <a href="{{ \App\Support\SiteUrls::path($locale).'#blog' }}">{{ $copy['navBlog'] ?? '' }}</a>
            <a href="#kontakti">{{ $copy['navContact'] ?? '' }}</a>
            <a href="#buj">{{ $copy['navFaq'] ?? '' }}</a>
        </nav>
        <div class="header-actions">
            <div class="language-switcher">
                <button class="language-picker" type="button" id="language-toggle" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ $copy['languageLabel'] ?? 'Valoda' }}">
                    <img class="language-flag" src="{{ asset('site-assets/images/flags/'.$flags[$locale]) }}" alt="" width="18" height="12">
                    <span class="language-name">{{ $languages[$locale] }}</span>
                    <span class="language-caret" aria-hidden="true"></span>
                </button>
                <ul class="language-menu" id="language-menu" role="listbox" aria-label="{{ $copy['languageLabel'] ?? 'Valoda' }}" hidden>
                    @foreach ($languages as $code => $name)
                        <li class="language-option{{ $code === $locale ? ' is-active' : '' }}" role="option" data-language="{{ $code }}" aria-selected="{{ $code === $locale ? 'true' : 'false' }}" tabindex="{{ $code === $locale ? '0' : '-1' }}">
                            <a href="{{ $languageUrls[$code] ?? '#' }}">
                                <img class="language-flag" src="{{ asset('site-assets/images/flags/'.$flags[$code]) }}" alt="" width="18" height="12">
                                <span>{{ $name }}</span>
                                <span class="language-check" aria-hidden="true">✓</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a class="header-phone" href="tel:+37124922942">+371 2492 2942</a>
        </div>
    </div>
</header>

<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <div class="mobile-menu-overlay" data-menu-close></div>
    <div class="mobile-menu-panel" role="dialog" aria-modal="true" aria-label="{{ $copy['mainMenu'] ?? 'Galvenā izvēlne' }}">
        <div class="mobile-menu-head">
            <img class="mobile-menu-logo" src="{{ asset('site-assets/images/brand/logo.svg') }}?v=2" alt="Pārdod Laimīgs">
            <button class="mobile-menu-close" type="button" data-menu-close aria-label="{{ $copy['close'] ?? 'Aizvērt' }}"><span aria-hidden="true"></span><span aria-hidden="true"></span></button>
        </div>
        <nav class="mobile-menu-nav" aria-label="{{ $copy['mainMenu'] ?? 'Galvenā izvēlne' }}">
            @foreach ([
                [\App\Support\SiteUrls::path($locale) . '#ipasumi', $copy['navProperties'] ?? ''],
                [\App\Support\SiteUrls::path($locale, '/pardot/'), $copy['navSell'] ?? ''],
                [\App\Support\SiteUrls::path($locale) . '#komanda', $copy['navAbout'] ?? ''],
                [\App\Support\SiteUrls::path($locale).'#blog', $copy['navBlog'] ?? ''],
                ['#kontakti', $copy['navContact'] ?? ''],
                ['#buj', $copy['navFaq'] ?? ''],
            ] as $index => [$href, $label])
                <a href="{{ $href }}"><span class="mobile-menu-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><span class="mobile-menu-label-text">{{ $label }}</span><span class="mobile-menu-arrow" aria-hidden="true">↗</span></a>
            @endforeach
        </nav>
        <div class="mobile-menu-block">
            <p class="mobile-menu-label">{{ $copy['quickContact'] ?? 'Ātrā saziņa' }}</p>
            <a class="mobile-menu-contact" href="https://wa.me/37124922942" target="_blank" rel="noreferrer"><span>WhatsApp</span><strong>+371 2492 2942</strong></a>
            <a class="mobile-menu-contact" href="tel:+37124922942"><span>{{ $copy['call'] ?? 'Zvanīt' }}</span><strong>+371 2492 2942</strong></a>
        </div>
        <div class="mobile-menu-foot">
            <div class="mobile-menu-social">
                <a href="https://www.youtube.com/@pardodlaimigs" target="_blank" rel="noreferrer">YouTube</a>
                <a href="https://www.facebook.com/PardodLaimigs/" target="_blank" rel="noreferrer">Facebook</a>
                <a href="https://instagram.com/pardodlaimigs" target="_blank" rel="noreferrer">Instagram</a>
                <a href="https://g.page/r/CRZd6XZu2hhmEAE/review" target="_blank" rel="noreferrer">Google</a>
            </div>
        </div>
        <div class="mobile-menu-block mobile-menu-language">
            <p class="mobile-menu-label">{{ $copy['languageLabel'] ?? 'Valoda' }}</p>
            <div class="mobile-menu-langs" role="group" aria-label="{{ $copy['languageLabel'] ?? 'Valoda' }}">
                @foreach ($languages as $code => $name)
                    <a class="mobile-menu-lang{{ $code === $locale ? ' is-active' : '' }}" href="{{ $languageUrls[$code] ?? '#' }}" aria-current="{{ $code === $locale ? 'true' : 'false' }}">
                        <img src="{{ asset('site-assets/images/flags/'.$flags[$code]) }}" alt="" width="18" height="12">{{ $name }}<span class="language-check" aria-hidden="true">✓</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<main id="top">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-main">
        <div class="footer-brand-block">
            <a class="brand footer-brand" href="{{ \App\Support\SiteUrls::path($locale) }}"><img src="{{ asset('site-assets/images/brand/logo.svg') }}?v=2" alt="Pārdod Laimīgs"></a>
            <p>{{ $copy['footerLine'] ?? '' }}</p>
        </div>
        <div class="footer-contact-block">
            <a class="footer-phone" href="tel:+37124922942">+371 2492 2942</a>
            <div class="footer-links">
                <a href="https://www.youtube.com/@pardodlaimigs" target="_blank" rel="noreferrer">YouTube</a>
                <a href="https://www.facebook.com/PardodLaimigs/" target="_blank" rel="noreferrer">Facebook</a>
                <a href="https://instagram.com/pardodlaimigs" target="_blank" rel="noreferrer">Instagram</a>
                <a href="https://g.page/r/CRZd6XZu2hhmEAE/review" target="_blank" rel="noreferrer">Google</a>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ date('Y') }} Pārdod Laimīgs</span>
        <div class="footer-legal">
            <button type="button" class="footer-cookie-link" id="manage-cookies">{{ $copy['cookieSettings'] ?? 'Sīkdatņu iestatījumi' }}</button>
            <a href="{{ \App\Support\SiteUrls::path($locale, '/privatuma-politika/') }}">{{ $copy['privacyLabel'] ?? 'Privātuma politika' }}</a>
        </div>
    </div>
</footer>

<button class="quick-contact-button" id="quick-contact-toggle" type="button" aria-label="{{ $copy['quickContact'] ?? '' }}" aria-expanded="false" aria-controls="quick-contact-panel">
    <i class="fa-solid fa-headset" aria-hidden="true"></i><span>{{ $copy['quickContact'] ?? '' }}</span>
</button>
<div class="quick-contact-panel" id="quick-contact-panel" hidden>
    <a class="quick-contact-single" href="https://wa.me/37124922942" target="_blank" rel="noreferrer"><span>WhatsApp</span></a>
    <a href="tel:+37124922942"><span>{{ $copy['call'] ?? 'Zvanīt' }}</span><strong>+371 2492 2942</strong></a>
</div>

@if (! empty($showCookieBanner))
<div class="cookie-banner" id="cookie-banner" hidden>
    <div class="cookie-copy"><strong>{{ $copy['cookiesTitle'] ?? '' }}</strong><p>{{ $copy['cookiesText'] ?? '' }}</p></div>
    <div class="cookie-actions">
        <button type="button" class="button button-dark" id="accept-cookies">{{ $copy['acceptCookies'] ?? '' }}</button>
        <button type="button" class="button button-quiet" id="reject-cookies">{{ $copy['rejectCookies'] ?? '' }}</button>
        <button type="button" class="cookie-settings" id="open-cookie-settings">{{ $copy['cookieSettings'] ?? '' }}</button>
    </div>
</div>
@endif

<dialog class="video-dialog" id="video-dialog" aria-label="{{ $copy['videoAction'] ?? '' }}">
    <button class="dialog-close" type="button" data-close-dialog aria-label="{{ $copy['close'] ?? 'Aizvērt' }}">×</button>
    <video id="intro-video" controls preload="none" playsinline width="1600" height="900" poster="{{ asset('site-media/pardod-laimigs-intro-poster.jpg') }}">
        <source src="{{ asset('site-media/pardod-laimigs-intro.mp4') }}" type="video/mp4">
    </video>
</dialog>

<dialog class="property-dialog" id="property-dialog" aria-labelledby="property-dialog-title">
    <button class="dialog-close" type="button" data-close-dialog aria-label="{{ $copy['close'] ?? 'Aizvērt' }}">×</button>
    <div id="property-dialog-content"></div>
</dialog>

@if (! empty($showCookieBanner))
<dialog class="cookie-dialog" id="cookie-dialog" aria-labelledby="cookie-dialog-title">
    <button class="dialog-close" type="button" data-close-dialog aria-label="{{ $copy['close'] ?? 'Aizvērt' }}">×</button>
    <h2 id="cookie-dialog-title">{{ $copy['cookiesTitle'] ?? '' }}</h2>
    <p>{{ $copy['cookiesText'] ?? '' }}</p>
    <label class="cookie-option"><span>{{ $copy['cookieNecessary'] ?? '' }}</span><input type="checkbox" checked disabled></label>
    <label class="cookie-option"><span>{{ $copy['cookieAnalytics'] ?? '' }}</span><input id="analytics-cookies" type="checkbox"></label>
    <button class="button button-dark" id="save-cookie-settings" type="button">{{ $copy['saveChoices'] ?? '' }}</button>
</dialog>
@endif

@stack('scripts')
</body>
</html>
