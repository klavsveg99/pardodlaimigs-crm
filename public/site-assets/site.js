/**
 * Public site behaviour (pardodlaimigs.lv).
 *
 * The pages are rendered on the server; this file only adds the interactive
 * parts: header language dropdown, mobile menu, hero carousel, listing filters,
 * the property dialog, the review slider, the cookie choices and the quick
 * contact panel. Each block runs only when its markup is on the page.
 */
(function () {
    'use strict';

    const $ = (selector, scope) => (scope || document).querySelector(selector);
    const $$ = (selector, scope) => Array.from((scope || document).querySelectorAll(selector));

    /* ── Header language dropdown ───────────────────────────────────────── */
    function setupLanguageSwitcher() {
        const toggle = $('#language-toggle');
        const menu = $('#language-menu');
        if (!toggle || !menu) return;

        const options = $$('[data-language]', menu);
        const active = options.find((option) => option.classList.contains('is-active')) || options[0];
        const isOpen = () => !menu.hidden;

        const close = (restoreFocus) => {
            if (!isOpen()) return;
            const inside = menu.contains(document.activeElement);
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus !== false && inside) toggle.focus();
        };

        const open = (focusFirst) => {
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            (focusFirst ? options[0] : active)?.focus();
        };

        const focusOption = (index) => {
            const bounded = (index + options.length) % options.length;
            options.forEach((option, position) => option.setAttribute('tabindex', position === bounded ? '0' : '-1'));
            options[bounded].focus();
        };

        toggle.addEventListener('click', () => (isOpen() ? close() : open()));
        toggle.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                open(true);
            }
        });

        options.forEach((option, index) => {
            option.addEventListener('mouseenter', () => {
                options.forEach((other, position) => other.setAttribute('tabindex', position === index ? '0' : '-1'));
            });
            option.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') { event.preventDefault(); focusOption(index + 1); }
                else if (event.key === 'ArrowUp') { event.preventDefault(); focusOption(index - 1); }
                else if (event.key === 'Home') { event.preventDefault(); focusOption(0); }
                else if (event.key === 'End') { event.preventDefault(); focusOption(options.length - 1); }
                else if (event.key === 'Escape') { event.preventDefault(); close(); }
            });
        });

        document.addEventListener('click', (event) => {
            if (isOpen() && !event.target.closest('.language-switcher')) close(false);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close(false);
        });
    }

    /* ── Mobile menu ────────────────────────────────────────────────────── */
    function setupMobileMenu() {
        const menu = $('#mobile-menu');
        const toggle = $('.menu-toggle');
        if (!menu || !toggle) return;

        const open = () => {
            menu.classList.add('is-open');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            menu.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-locked');
            $('.mobile-menu-close', menu)?.focus();
        };

        const close = (restore) => {
            if (!menu.classList.contains('is-open')) return;
            menu.classList.remove('is-open');
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-locked');
            if (restore !== false) toggle.focus();
        };

        toggle.addEventListener('click', () => (menu.classList.contains('is-open') ? close() : open()));
        $$('[data-menu-close]', menu).forEach((element) => element.addEventListener('click', () => close()));
        $$('.mobile-menu-nav a', menu).forEach((link) => link.addEventListener('click', () => close(false)));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(false); });

        window.matchMedia('(max-width: 680px)').addEventListener('change', (event) => {
            if (!event.matches) close(false);
        });
    }

    /* ── Hero carousel ──────────────────────────────────────────────────── */
    function setupHero() {
        const visual = $('#hero-carousel');
        if (!visual) return;

        const slides = $$('.hero-slide', visual);
        const positions = $$('[data-hero-slide]', visual).map((node) => JSON.parse(node.textContent));
        const title = $('#hero-caption-title');
        const location = $('#hero-caption-location');
        const position = $('#hero-position');
        const total = $('#hero-total');
        const link = $('[data-hero-link]', visual);
        const progress = $('#hero-progress');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const delay = 6000;

        if (!slides.length) return;
        let index = 0;
        let timer = null;

        const show = (next) => {
            index = (next + slides.length) % slides.length;
            slides.forEach((slide, position_) => slide.classList.toggle('is-active', position_ === index));
            const data = positions[index] || {};
            if (title) title.textContent = data.title || '';
            if (location) location.textContent = [data.city, data.price].filter(Boolean).join(' · ');
            if (position) position.textContent = String(index + 1).padStart(2, '0');
            if (link) {
                link.dataset.heroSlug = data.slug || '';
                link.setAttribute('aria-label', data.label || '');
            }
            if (progress) {
                progress.classList.remove('is-running');
                void progress.offsetWidth;
                if (!reduced) progress.classList.add('is-running');
            }
        };

        const start = () => {
            clearInterval(timer);
            if (reduced || slides.length < 2) return;
            timer = setInterval(() => show(index + 1), delay);
            if (progress) {
                progress.classList.remove('is-running');
                void progress.offsetWidth;
                progress.classList.add('is-running');
            }
        };

        const pause = () => {
            clearInterval(timer);
            progress?.classList.remove('is-running');
        };

        if (total) total.textContent = String(slides.length).padStart(2, '0');
        show(0);

        $('[data-hero-prev]', visual)?.addEventListener('click', () => { show(index - 1); start(); });
        $('[data-hero-next]', visual)?.addEventListener('click', () => { show(index + 1); start(); });
        link?.addEventListener('click', () => openProperty(link.dataset.heroSlug));
        visual.addEventListener('mouseenter', pause);
        visual.addEventListener('mouseleave', start);
        visual.addEventListener('focusin', pause);
        visual.addEventListener('focusout', start);
        document.addEventListener('visibilitychange', () => (document.hidden ? pause() : start()));
        start();
    }

    /* ── Listing filters ────────────────────────────────────────────────── */
    function setupFilters() {
        const grid = $('#property-grid');
        if (!grid) return;

        const category = $('#search-category');
        const price = $('#search-price');
        const query = $('#search-query');
        const count = $('#property-count');
        const summary = $('#filter-summary');
        const viewAll = $('#view-all');
        const cards = $$('.property-card', grid);
        const limit = Number(grid.dataset.limit || 6);

        const priceOf = (card) => Number(card.dataset.price || 0) || 0;

        const apply = () => {
            const wantedCategory = category?.value || '';
            const max = Number(price?.value || 0);
            const needle = (query?.value || '').trim().toLowerCase();
            let shown = 0;
            let expanded = grid.dataset.expanded === '1';

            cards.forEach((card) => {
                const matchesCategory = !wantedCategory || card.dataset.category === wantedCategory;
                const value = priceOf(card);
                const matchesPrice = !max || (value > 0 && value <= max);
                const haystack = (card.dataset.search || '').toLowerCase();
                const matchesQuery = !needle || haystack.includes(needle);
                const matches = matchesCategory && matchesPrice && matchesQuery;

                if (matches) {
                    shown += 1;
                    card.hidden = !expanded && shown > limit;
                } else {
                    card.hidden = true;
                }
            });

            const visible = cards.filter((card) => !card.hidden).length;
            if (count) count.textContent = String(shown) + ' ' + (count.dataset.suffix || '');
            if (summary) summary.textContent = shown === 0 ? (summary.dataset.empty || '') : '';
            if (viewAll) viewAll.hidden = expanded || shown <= visible;
        };

        const search = $('#property-search');
        search?.addEventListener('submit', (event) => {
            event.preventDefault();
            grid.dataset.expanded = '1';
            apply();
            $('#ipasumi')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        [category, price].forEach((field) => field?.addEventListener('change', () => {
            grid.dataset.expanded = '1';
            apply();
        }));
        query?.addEventListener('input', () => {
            grid.dataset.expanded = '1';
            apply();
        });

        viewAll?.addEventListener('click', () => {
            grid.dataset.expanded = '1';
            apply();
        });

        apply();
    }

    /* ── Property dialog ────────────────────────────────────────────────── */
    function properties() {
        const payload = $('#site-properties');
        if (!payload) return [];
        try {
            return JSON.parse(payload.textContent) || [];
        } catch (error) {
            return [];
        }
    }

    function openProperty(slug) {
        const dialog = $('#property-dialog');
        if (!dialog || !slug) return;

        const property = properties().find((item) => item.slug === slug);
        if (!property) return;

        const images = (property.gallery && property.gallery.length ? property.gallery : [property.image]).filter(Boolean);
        const hasLocation = Number.isFinite(property.lat) && Number.isFinite(property.lng);
        const facts = (property.facts || []).map((fact) => `<li><strong>${fact.value}</strong><span>${fact.label}</span></li>`).join('');

        $('#property-dialog-content').innerHTML = `
            <div class="property-detail">
              <div class="property-detail-media">
                <img id="property-detail-image" src="${images[0]}" alt="${property.title}">
                ${images.length > 1 ? `
                  <button class="property-nav property-nav-prev" type="button" data-gallery-step="-1" aria-label="Previous"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                  <button class="property-nav property-nav-next" type="button" data-gallery-step="1" aria-label="Next"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                  <span class="property-gallery-position" id="property-gallery-position">1 / ${images.length}</span>
                  <div class="property-detail-thumbs" role="group">
                    ${images.map((image, index) => `<button type="button" class="property-thumb${index === 0 ? ' is-active' : ''}" data-gallery-index="${index}"><img src="${image}" alt="" loading="lazy"></button>`).join('')}
                  </div>` : ''}
              </div>
              <div class="property-detail-copy">
                <div class="property-type"><span class="property-type-blob">${property.category_label || ''}</span>${images.length > 1 ? `<span class="property-photo-count">${images.length} ${property.photos_label || ''}</span>` : ''}</div>
                <h2 id="property-dialog-title">${property.title}</h2>
                <div class="property-detail-place">
                  <p class="property-detail-address">${property.address || ''}</p>
                  ${hasLocation ? `<button class="property-map-toggle" type="button" id="property-map-toggle" aria-expanded="false"><span>${property.map_label || ''}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>` : ''}
                </div>
                ${hasLocation ? '<div class="property-map" id="property-map" hidden></div>' : ''}
                <p class="property-detail-price">${property.price || ''}</p>
                ${facts ? `<ul class="property-detail-facts">${facts}</ul>` : ''}
                <p class="property-detail-text">${property.summary || ''}</p>
                <div class="property-detail-actions">
                  <a class="button button-dark" href="${property.url}">${property.details_label || ''}</a>
                </div>
              </div>
            </div>`;

        const main = $('#property-detail-image');
        const counter = $('#property-gallery-position');
        const thumbs = $$('[data-gallery-index]', dialog);
        let index = 0;

        const show = (next) => {
            index = ((next % images.length) + images.length) % images.length;
            main.src = images[index];
            if (counter) counter.textContent = `${index + 1} / ${images.length}`;
            thumbs.forEach((thumb, position) => thumb.classList.toggle('is-active', position === index));
        };

        thumbs.forEach((thumb) => thumb.addEventListener('click', () => show(Number(thumb.dataset.galleryIndex))));
        $$('[data-gallery-step]', dialog).forEach((button) => button.addEventListener('click', () => show(index + Number(button.dataset.galleryStep))));

        const mapToggle = $('#property-map-toggle', dialog);
        mapToggle?.addEventListener('click', () => {
            const map = $('#property-map', dialog);
            const expanded = mapToggle.getAttribute('aria-expanded') !== 'true';
            mapToggle.setAttribute('aria-expanded', String(expanded));
            map.hidden = !expanded;
            if (expanded && !map.querySelector('iframe')) {
                map.innerHTML = `<iframe title="${property.map_label || ''}" src="https://www.google.com/maps?q=${property.lat},${property.lng}&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                  <a class="property-map-link" href="https://www.google.com/maps?q=${property.lat},${property.lng}" target="_blank" rel="noreferrer">${property.maps_label || 'Google Maps'}</a>`;
            }
        });

        dialog.showModal();
    }

    function setupPropertyDialog() {
        const dialog = $('#property-dialog');
        if (!dialog) return;

        $$('[data-property]').forEach((element) => element.addEventListener('click', () => openProperty(element.dataset.property)));
        dialog.addEventListener('keydown', (event) => {
            const thumbs = $$('[data-gallery-index]', dialog);
            if (thumbs.length < 2) return;
            const current = thumbs.findIndex((thumb) => thumb.classList.contains('is-active'));
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                event.preventDefault();
                const next = (current + (event.key === 'ArrowRight' ? 1 : -1) + thumbs.length) % thumbs.length;
                thumbs[next].click();
            }
        });
    }

    /* ── Review slider ──────────────────────────────────────────────────── */
    function setupReviews() {
        const card = $('#review-quote');
        if (!card) return;

        const reviews = $$('[data-review]').map((node) => JSON.parse(node.textContent));
        if (!reviews.length) return;

        const name = $('#review-name');
        const position = $('#review-position');
        let index = 0;

        const render = () => {
            const review = reviews[index];
            card.textContent = review.quote;
            if (name) name.textContent = review.name;
            if (position) position.textContent = `${String(index + 1).padStart(2, '0')} / ${String(reviews.length).padStart(2, '0')}`;
        };

        $('#review-prev')?.addEventListener('click', () => {
            index = (index + reviews.length - 1) % reviews.length;
            render();
        });
        $('#review-next')?.addEventListener('click', () => {
            index = (index + 1) % reviews.length;
            render();
        });
        render();
    }

    /* ── Cookie choices ─────────────────────────────────────────────────── */
    function setupCookies() {
        const banner = $('#cookie-banner');
        const dialog = $('#cookie-dialog');
        if (!banner && !dialog) return;

        const analytics = $('#analytics-cookies');
        const stored = () => window.localStorage.getItem('cookie-consent');

        const save = (all) => {
            window.localStorage.setItem('cookie-consent', all ? 'all' : 'essential');
            if (banner) banner.hidden = true;
            dialog?.close();
        };

        if (banner) banner.hidden = stored() !== null;
        if (analytics) analytics.checked = stored() === 'all';

        $('#accept-cookies')?.addEventListener('click', () => save(true));
        $('#reject-cookies')?.addEventListener('click', () => save(false));
        $('#open-cookie-settings')?.addEventListener('click', () => {
            if (analytics) analytics.checked = stored() === 'all';
            dialog?.showModal();
        });
        $('#manage-cookies')?.addEventListener('click', () => {
            if (analytics) analytics.checked = stored() === 'all';
            dialog?.showModal();
        });
        $('#save-cookie-settings')?.addEventListener('click', () => save(!!analytics?.checked));
    }

    /* ── Quick contact ──────────────────────────────────────────────────── */
    function setupQuickContact() {
        const toggle = $('#quick-contact-toggle');
        const panel = $('#quick-contact-panel');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            panel.hidden = !open;
        });
    }

    /* ── Property page gallery and map ──────────────────────────────────── */
    function setupPropertyPage() {
        const main = $('#property-detail-image');
        const payload = $('#site-property-gallery');
        if (!main || !payload) return;

        let images = [];
        try {
            images = JSON.parse(payload.textContent) || [];
        } catch (error) {
            images = [];
        }
        if (images.length < 2) return;

        const counter = $('#property-gallery-position');
        const thumbs = $$('[data-gallery-index]');
        let index = 0;

        const show = (next) => {
            index = ((next % images.length) + images.length) % images.length;
            main.src = images[index];
            if (counter) counter.textContent = `${index + 1} / ${images.length}`;
            thumbs.forEach((thumb, position) => thumb.classList.toggle('is-active', position === index));
        };

        thumbs.forEach((thumb) => thumb.addEventListener('click', () => show(Number(thumb.dataset.galleryIndex))));
        $$('[data-gallery-step]').forEach((button) => button.addEventListener('click', () => show(index + Number(button.dataset.galleryStep))));

        document.addEventListener('keydown', (event) => {
            // Never move the gallery while the visitor is typing in the form.
            if (event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable]')) return;
            if (event.key === 'ArrowRight') show(index + 1);
            else if (event.key === 'ArrowLeft') show(index - 1);
        });

        // Swipe on touch devices.
        let touchStart = null;
        main.addEventListener('touchstart', (event) => { touchStart = event.changedTouches[0].clientX; }, { passive: true });
        main.addEventListener('touchend', (event) => {
            if (touchStart === null) return;
            const delta = event.changedTouches[0].clientX - touchStart;
            touchStart = null;
            if (Math.abs(delta) > 40) show(index + (delta < 0 ? 1 : -1));
        });
    }

    function setupPropertyPageMap() {
        const toggle = $('#property-map-toggle');
        const map = $('#property-map');
        if (!toggle || !map) return;

        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(expanded));
            map.hidden = !expanded;
            if (expanded && !map.querySelector('iframe')) {
                const query = toggle.dataset.lat + ',' + toggle.dataset.lng;
                map.innerHTML = `<iframe title="Google Maps" src="https://www.google.com/maps?q=${query}&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                  <a class="property-map-link" href="https://www.google.com/maps?q=${query}" target="_blank" rel="noreferrer">Google Maps</a>`;
            }
        });
    }

    /* ── Sticky header ──────────────────────────────────────────────────── */
    function setupStickyHeader() {
        const header = $('.site-header');
        if (!header) return;

        let last = window.scrollY;
        let ticking = false;

        // The header is sticky: it slides away going down and returns on the
        // way up, or as soon as the top of the page is in reach.
        const update = () => {
            ticking = false;
            const y = Math.max(0, window.scrollY);
            if (y <= 120 || y < last - 4) header.classList.remove('is-hidden');
            else if (y > last + 4) header.classList.add('is-hidden');
            last = y;
        };

        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(update);
        }, { passive: true });

        // Show it again when something inside it takes focus, otherwise the
        // browser scrolls the page back to the top to reveal it.
        document.addEventListener('focusin', (event) => {
            if (event.target.closest('.site-header')) header.classList.remove('is-hidden');
        });
    }

    /* ── Valuation form: validation and reCAPTCHA v3 ───────────────────── */
    const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;

    function phonePrefixes() {
        const payload = $('#phone-prefixes');
        if (!payload) return {};
        try {
            return JSON.parse(payload.textContent) || {};
        } catch (error) {
            return {};
        }
    }

    function setFieldError(field, message) {
        const error = $(`#${field.name}-error`);
        field.setAttribute('aria-invalid', 'true');
        if (error) {
            error.textContent = message;
            error.hidden = false;
        }
    }

    function clearFieldError(field) {
        const error = $(`#${field.name}-error`);
        field.removeAttribute('aria-invalid');
        if (error) error.hidden = true;
    }

    /** Email shape plus the national number length of the chosen country. */
    function validateContactForm(form) {
        let valid = true;

        clearFieldError(form.email);
        if (!EMAIL_PATTERN.test(form.email.value.trim())) {
            setFieldError(form.email, form.dataset.emailInvalid || 'Invalid email address.');
            valid = false;
        }

        clearFieldError(form.phone);
        const rule = phonePrefixes()[form.phone_prefix.value];
        const digits = form.phone.value.replace(/\D/g, '');
        if (rule && (digits.length < rule.min || digits.length > rule.max)) {
            setFieldError(form.phone, form.dataset.phoneInvalid || 'Invalid phone number.');
            valid = false;
        }

        return valid;
    }

    function loadRecaptcha(siteKey) {
        if (!siteKey || $('script[data-recaptcha]')) return;
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.async = true;
        script.dataset.recaptcha = '1';
        document.head.appendChild(script);
    }

    /** The token goes into the hidden field; the form is then submitted for real. */
    function requestRecaptchaToken(form, submit, attempt = 0) {
        const siteKey = form.dataset.siteKey;

        // Without a key configured the server skips the check as well.
        if (!siteKey) {
            submit('');
            return;
        }

        // api.js loads asynchronously: give it a moment instead of posting an
        // unverified request the server will only throw away.
        if (typeof grecaptcha === 'undefined') {
            if (attempt < 40) {
                setTimeout(() => requestRecaptchaToken(form, submit, attempt + 1), 150);
                return;
            }
            showCaptchaFailure(form);
            return;
        }

        grecaptcha.ready(() => {
            grecaptcha.execute(siteKey, { action: 'valuation' })
                .then((token) => (token ? submit(token) : showCaptchaFailure(form)))
                .catch(() => showCaptchaFailure(form));
        });
    }

    function showCaptchaFailure(form) {
        const feedback = $('#form-feedback');
        if (feedback) {
            feedback.textContent = form.dataset.captchaFailed || '';
            feedback.hidden = false;
        }
    }

    function setupContactForm() {
        const form = $('.contact-form');
        if (!form) return;

        loadRecaptcha(form.dataset.siteKey);

        form.addEventListener('submit', (event) => {
            if (!validateContactForm(form)) {
                event.preventDefault();
                form.querySelector('[aria-invalid="true"]')?.focus();
                return;
            }

            if (form.dataset.recaptchaReady === '1') return;

            // Hold the native submit until the token is in hand.
            event.preventDefault();
            requestRecaptchaToken(form, (token) => {
                form.recaptcha_token.value = token;
                form.dataset.recaptchaReady = '1';
                form.submit();
            });
        });

        form.querySelectorAll('input[name="email"], input[name="phone"]').forEach((field) => {
            field.addEventListener('input', () => clearFieldError(field));
        });
        form.phone_prefix?.addEventListener('change', () => clearFieldError(form.phone));
    }

    /* ── Intro video dialog ─────────────────────────────────────────────── */
    function setupVideoDialog() {
        const dialog = $('#video-dialog');
        const video = $('#intro-video');
        if (!dialog || !video) return;

        // The file is 24 MB: it only downloads once opened (preload="none") and
        // stops again when the dialog closes.
        dialog.addEventListener('close', () => {
            video.pause();
            video.currentTime = 0;
        });

        $('#open-video')?.addEventListener('click', () => dialog.showModal());
    }

    /* ── Team: the agent's short description ───────────────────────────── */
    /** A few of the agent's listings; the button opens their page for the rest. */
    function renderAgentListings(agent) {
        const block = $('#agent-dialog-listings');
        const list = $('#agent-dialog-properties');
        const more = $('#agent-dialog-more');
        if (!block || !list || !more) return;

        const properties = agent.properties || [];
        block.hidden = properties.length === 0;
        if (properties.length === 0) return;

        list.innerHTML = properties.map((property) => `
            <a class="agent-property" href="${property.url}">
              <img src="${property.image}" alt="" loading="lazy">
              <span class="agent-property-copy"><strong>${property.title}</strong><span>${property.price}</span></span>
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>`).join('');

        const count = $('#agent-dialog-count');
        if (count) count.textContent = `${agent.propertyCount} ${block.dataset.countSuffix || ''}`.trim();

        more.href = agent.url;
        more.hidden = !(agent.propertyCount > properties.length);
    }

    function setupAgentDialog() {
        const dialog = $('#agent-dialog');
        if (!dialog) return;

        const data = $$('[data-agent-data]').map((node) => JSON.parse(node.textContent));


        const open = (slug) => {
            const agent = data.find((item) => item.slug === slug);
            if (!agent) return;

            $('#agent-dialog-photo').src = agent.photo;
            $('#agent-dialog-photo').alt = agent.name;
            dialog.classList.toggle('is-portrait', Boolean(agent.portrait));
            $('#agent-dialog-name').textContent = agent.name;
            $('#agent-dialog-bio').textContent = agent.description;

            const phone = $('#agent-dialog-phone');
            phone.href = `tel:${agent.phone.replace(/\s/g, '')}`;
            phone.querySelector('span').textContent = agent.phone;
            phone.hidden = !agent.phone;

            renderAgentListings(agent);

            dialog.showModal();
        };

        $$('[data-agent]').forEach((element) => element.addEventListener('click', () => open(element.dataset.agent)));
    }

    /* ── Blog: load more ────────────────────────────────────────────────── */
    function setupBlogMore() {
        const button = $('#load-more-posts');
        const grid = $('#article-grid');
        if (!button || !grid) return;

        const error = $('#load-more-error');

        button.addEventListener('click', async () => {
            if (button.getAttribute('aria-busy') === 'true') return;
            const page = Number(button.dataset.page || 2);
            button.setAttribute('aria-busy', 'true');
            if (error) error.hidden = true;

            try {
                const response = await fetch(`${button.dataset.endpoint}?page=${page}&locale=${button.dataset.locale}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                grid.insertAdjacentHTML('beforeend', data.html || '');
                button.dataset.page = String(page + 1);
                button.hidden = !data.hasMore;
            } catch (failure) {
                // Keep the button so the visitor can try again.
                if (error) error.hidden = false;
            } finally {
                button.setAttribute('aria-busy', 'false');
            }
        });
    }

    /* ── "Katram īpašumam - sava pieeja" preselects the type ────────────── */
    function setupPreselectLinks() {
        $$('[data-preselect]').forEach((link) => {
            link.addEventListener('click', () => {
                const select = $('.contact-form select[name="property_type"]');
                if (select) select.value = link.dataset.preselect;
            });
        });
    }

    function setupDialogs() {
        $$('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
        $$('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        }));
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupLanguageSwitcher();
        setupMobileMenu();
        setupStickyHeader();
        setupHero();
        setupFilters();
        setupPropertyDialog();
        setupPropertyPage();
        setupPropertyPageMap();
        setupReviews();
        setupCookies();
        setupQuickContact();
        setupContactForm();
        setupVideoDialog();
        setupAgentDialog();
        setupBlogMore();
        setupPreselectLinks();
        setupDialogs();
    });
})();
