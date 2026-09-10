<link href="https://cdn.jsdelivr.net/npm/intl-tel-input@29.2.3/dist/css/intlTelInput.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@29.2.3/dist/js/intlTelInputWithUtils.min.js" defer></script>

<style>
    [x-cloak] { display: none !important; }

    .pdc-phone-field,
    .pdc-phone-field .iti {
        width: 100%;
    }

    /* Let the country dropdown escape the input's overflow/max-height clipping */
    .pdc-phone-field .iti,
    .iti__country-container {
        overflow: visible !important;
    }

    /* Filament's fi-input-wrp has overflow:hidden — it would clip the dropdown */
    .fi-fo-text-input:has(> .pdc-phone-field) {
        overflow: visible !important;
    }

    .iti__country-selector {
        z-index: 90;
        max-height: 70vh;
        overflow-y: auto;
    }

    .pdc-phone-field .iti__selected-flag {
        background-color: #fff;
        border-radius: 0.5rem 0 0 0.5rem;
    }

    .iti__country-container .iti__arrow {
        position: static;
        margin-left: 5px;
        margin-top: 0;
        border-top-color: #6b7280;
    }

    .pdc-phone-field input.pdc-phone-invalid {
        border-color: #cf2e2e !important;
    }

    .iti__country-container .iti__dial-code {
        color: #374151;
        font-weight: 500;
    }

    .dark .pdc-phone-field .iti__selected-flag {
        background-color: #333338;
    }

    .dark .pdc-phone-field .iti__selected-flag:hover,
    .dark .pdc-phone-field .iti__selected-flag:focus {
        background-color: #3f3f46;
    }

    .dark .iti__country-container .iti__arrow {
        border-top-color: #a1a1aa;
    }

    .dark .iti__country-container .iti__dial-code {
        color: #d4d4d8;
    }

    .dark .iti__country-selector {
        background-color: #18181b;
        border-color: #3f3f46;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    .dark .iti__country {
        background-color: #18181b;
        color: #e5e7eb;
    }

    .dark .iti__country:hover {
        background-color: #27272a;
    }

    .dark .iti__country .iti__dial-code {
        color: #a1a1aa;
    }

    .dark .iti__search-input {
        background-color: #27272a;
        color: #e5e7eb;
        border-color: #3f3f46;
    }

    .dark .iti__search-input::placeholder {
        color: #a1a1aa;
    }

    .dark .iti__search-clear,
    .dark .iti__search-icon {
        color: #a1a1aa;
    }

    .dark .iti__noresults {
        background-color: #18181b;
        color: #a1a1aa;
    }

    /* View pages: plain static value, no prefix widget */
    .pdc-phone-field-static .fi-input {
        color: inherit;
        background: transparent;
        border: none;
        padding-left: 0.75rem;
    }
    .pdc-phone-field-static .iti__country-container {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pdcPhone', (statePath, isStatic = false) => {
            // Keep the Iti instance outside Alpine's reactive proxy — it is
            // a private-class object and breaks behind a Proxy.
            let iti = null;
            let lastSent = null;

            return {
                error: false,
                syncing: false,
                init() {
                    const input = this.$refs.tel;

                    if (isStatic) {
                        // View pages: show the full stored value with prefix,
                        // e.g. "+37129396969" → "+371 29396969".
                        if (typeof window.intlTelInput !== 'function') {
                            this.$nextTick(() => this.init());
                            return;
                        }

                        input.value = this.withDialSpace(
                            String(this.$wire.get(statePath) ?? '').trim(),
                        );
                        return;
                    }

                    if (typeof window.intlTelInput !== 'function') {
                        this.$nextTick(() => this.init());
                        return;
                    }

                    iti = window.intlTelInput(input, {
                        dropdownParent: document.body,
                        initialCountry: 'lv',
                        separateDialCode: true,
                        strictMode: true,
                        countryNameLocale: 'lv',
                        countrySearch: false,
                        hiddenInputs: null,
                        uiTranslations: {
                            noCountrySelected: 'Izvēlieties valsti tālruņa numuram',
                            countryListAriaLabel: 'Valstu saraksts',
                        },
                    });

                    const initial = this.$wire.get(statePath) ?? '';
                    if (String(initial).trim() !== '') {
                        try {
                            iti.setNumber(String(initial));
                        } catch (e) {
                            input.value = String(initial);
                        }

                        // Migrate legacy / formatted values to clean E.164
                        // only when the restored number is actually valid —
                        // broken legacy values stay as-is for the user to fix.
                        const normalized = iti.getNumber();
                        if (normalized && normalized !== String(initial) && iti.isValidNumber()) {
                            this.commit();
                        }
                    }
                    lastSent = iti.getNumber() || (this.$wire.get(statePath) ?? '');

                    input.addEventListener('input', () => this.commit());
                    input.addEventListener('countrychange', () => this.commit());
                    input.addEventListener('blur', () => this.validate());

                    // External state changes (form re-fills, resets) — re-sync display.
                    this.$wire.$watch(statePath, (value) => {
                        if (this.syncing) return;
                        const v = String(value ?? '');
                        const current = iti.getNumber();
                        if (v !== '' && v !== current) {
                            try {
                                iti.setNumber(v);
                            } catch (e) {
                                input.value = v;
                            }
                        } else if (v === '' && input.value.trim() !== '') {
                            input.value = '';
                            this.error = false;
                            input.classList.remove('pdc-phone-invalid');
                        }
                    });
                },
                commit() {
                    if (!iti) return;
                    const input = this.$refs.tel;
                    const number = iti.getNumber();
                    const value = number !== '' ? number : input.value.trim();
                    if (value === lastSent) return;
                    lastSent = value;
                    this.syncing = true;
                    this.$wire.set(statePath, value);
                    this.$nextTick(() => { this.syncing = false; });
                },
                validate() {
                    if (!iti) return;
                    const input = this.$refs.tel;
                    if (input.value.trim() === '') {
                        this.error = false;
                    } else {
                        this.error = iti.isValidNumber() === false;
                    }
                    input.classList.toggle('pdc-phone-invalid', this.error);
                },
                withDialSpace(value) {
                    if (value === '' || !value.startsWith('+')) {
                        return value;
                    }

                    let countries;
                    try {
                        countries = window.intlTelInput.getAllCountries();
                    } catch (e) {
                        return value;
                    }

                    // Longest matching dial code wins (e.g. +1 vs +1242).
                    const digitPart = value.slice(1);
                    let best = '';
                    for (const c of countries) {
                        const dial = String(c.dialCode);
                        if (
                            dial.length > best.length
                            && dial.length < digitPart.length
                            && digitPart.startsWith(dial)
                        ) {
                            best = dial;
                        }
                    }

                    return best === '' ? value : '+' + best + ' ' + digitPart.slice(best.length);
                },
            };
        });
    });
</script>
