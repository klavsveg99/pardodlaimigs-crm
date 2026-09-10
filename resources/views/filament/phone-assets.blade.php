<link href="https://cdn.jsdelivr.net/npm/intl-tel-input@29.2.3/dist/css/intlTelInput.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@29.2.3/dist/js/intlTelInputWithUtils.min.js" defer></script>

<style>
    [x-cloak] { display: none !important; }

    .pdc-phone-field,
    .pdc-phone-field .iti {
        width: 100%;
    }

    .pdc-phone-field .iti__selected-flag {
        background-color: #fff;
        border-radius: 0.5rem 0 0 0.5rem;
    }

    .pdc-phone-field .iti__country-container .iti__arrow {
        position: static;
        margin-left: 5px;
        margin-top: 0;
        border-top-color: #6b7280;
    }

    .pdc-phone-field input.pdc-phone-invalid {
        border-color: #cf2e2e !important;
    }

    .pdc-phone-field .iti__country-container .iti__dial-code {
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

    .dark .pdc-phone-field .iti__country-container .iti__arrow {
        border-top-color: #a1a1aa;
    }

    .dark .pdc-phone-field .iti__country-container .iti__dial-code {
        color: #d4d4d8;
    }

    .dark .pdc-phone-field .iti__country-list {
        background-color: #18181b;
        border-color: #3f3f46;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    .dark .pdc-phone-field .iti__country {
        background-color: #18181b;
        color: #e5e7eb;
    }

    .dark .pdc-phone-field .iti__country:hover {
        background-color: #27272a;
    }

    .dark .pdc-phone-field .iti__country .iti__dial-code {
        color: #a1a1aa;
    }

    .dark .pdc-phone-field .iti__search-input {
        background-color: #27272a;
        color: #e5e7eb;
        border-color: #3f3f46;
    }

    .dark .pdc-phone-field .iti__search-input::placeholder {
        color: #a1a1aa;
    }

    .dark .pdc-phone-field .iti__search-clear,
    .dark .pdc-phone-field .iti__search-icon {
        color: #a1a1aa;
    }

    .dark .pdc-phone-field .iti__noresults {
        background-color: #18181b;
        color: #a1a1aa;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pdcPhone', (statePath) => {
            // Keep the Iti instance outside Alpine's reactive proxy — it is
            // a private-class object and breaks behind a Proxy.
            let iti = null;
            let lastSent = null;

            return {
                error: false,
                syncing: false,
                init() {
                    if (typeof window.intlTelInput !== 'function') {
                        this.$nextTick(() => this.init());
                        return;
                    }

                    const input = this.$refs.tel;

                    iti = window.intlTelInput(input, {
                        initialCountry: 'lv',
                        separateDialCode: true,
                        strictMode: true,
                        countryNameLocale: 'lv',
                        hiddenInputs: null,
                        uiTranslations: {
                            noCountrySelected: 'Izvēlieties valsti tālruņa numuram',
                            countryListAriaLabel: 'Valstu saraksts',
                            searchPlaceholder: 'Meklēt',
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
            };
        });
    });
</script>
