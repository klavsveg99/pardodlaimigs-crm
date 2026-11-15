@auth
    @php
        $introUser = auth()->user();

        $showMekletajiIntro = $introUser !== null
            && $introUser->mekletaji_intro_seen_at === null
            && ! $introUser->isPhoto()
            && ! $introUser->isStudent();

        $mekletajiIntroUrl = \App\Filament\Admin\Resources\PropertySearchResource::getUrl('index');
    @endphp

    @if ($showMekletajiIntro)
        <style>
            [x-cloak] { display: none !important; }

            .pdc-intro {
                position: fixed;
                inset: 0;
                z-index: 55;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .pdc-intro-overlay {
                position: fixed;
                inset: 0;
                z-index: 40;
                background: rgb(0 0 0 / 0.6);
            }

            .pdc-intro-window {
                position: relative;
                z-index: 50;
                width: min(30rem, 100%);
                max-height: calc(100dvh - 2rem);
                overflow-y: auto;
                padding: 1.25rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background: #ffffff;
                color: #111827;
                box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.35);
            }

            .pdc-intro-head { display: flex; align-items: flex-start; gap: 0.75rem; }

            .pdc-intro-kicker {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--pdc-primary);
            }

            .pdc-intro-title {
                margin: 0.15rem 0 0;
                font-size: 1.25rem;
                font-weight: 700;
                line-height: 1.3;
            }

            .pdc-intro-close {
                display: inline-flex;
                flex: none;
                align-items: center;
                justify-content: center;
                width: 1.75rem;
                height: 1.75rem;
                margin-left: auto;
                border: 1px solid #e5e7eb;
                border-radius: 9999px;
                background: #f3f4f6;
                color: #4b5563;
                cursor: pointer;
            }

            .pdc-intro-close svg { width: 0.875rem; height: 0.875rem; }

            .pdc-intro-body {
                margin-top: 0.75rem;
                font-size: 0.875rem;
                line-height: 1.55;
                color: #374151;
            }

            .pdc-intro-list {
                display: grid;
                gap: 0.3rem;
                margin-top: 0.65rem;
                padding-left: 1.1rem;
                list-style: disc;
            }

            .pdc-intro-foot {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                margin-top: 1.1rem;
            }

            body.pdc-intro-lock { overflow: hidden !important; }

            .dark .pdc-intro-window { border-color: #374151; background: #111827; color: #f3f4f6; }
            .dark .pdc-intro-kicker { color: #8fc4be; }
            .dark .pdc-intro-close { border-color: #374151; background: #1f2937; color: #d1d5db; }
            .dark .pdc-intro-body { color: #d1d5db; }
        </style>

        <div
            class="pdc-intro"
            x-data="{
                open: true,
                close() {
                    pdcMekletajiIntroSeen();
                    this.open = false;
                    document.body.classList.remove('pdc-intro-lock');
                },
            }"
            x-init="document.body.classList.add('pdc-intro-lock')"
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="close()"
        >
            <div class="pdc-intro-overlay" x-on:click="close()"></div>

            <div
                class="pdc-intro-window"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pdc-intro-title"
                x-trap.noreturn="open"
                x-init="$nextTick(() => $el.querySelector('.pdc-intro-close')?.focus())"
            >
                <div class="pdc-intro-head">
                    <div>
                        <div class="pdc-intro-kicker">Kas jauns · CRM 2.0</div>
                        <h2 class="pdc-intro-title" id="pdc-intro-title">Meklētāji</h2>
                    </div>

                    <button
                        type="button"
                        class="pdc-intro-close"
                        aria-label="Aizvērt"
                        title="Aizvērt"
                        x-on:click="close()"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="pdc-intro-body">
                    <p>Jauna sadaļa, kas meklē ss.lv un city24.lv portālos un savāc atrastos sludinājumus vienā vietā.</p>
                    <ul class="pdc-intro-list">
                        <li>Regulāras pārbaudes: ik pēc 6 stundām, katru dienu vai reizi nedēļā.</li>
                        <li>Atrastos objektus vari atvērt sludinājumā, atzīmēt apskatītus vai atzīmēt vairākus uzreiz.</li>
                        <li>No atrastā objekta vari pievienot CRM īpašumu ar jau aizpildītu nosaukumu, cenu, platību un atrašanās vietu.</li>
                    </ul>
                </div>

                <div class="pdc-intro-foot">
                    <x-filament::button color="gray" x-on:click="close()">
                        Aizvērt
                    </x-filament::button>

                    <x-filament::button tag="a" :href="$mekletajiIntroUrl" x-on:click="pdcMekletajiIntroSeen()">
                        Apskatīt Meklētājus
                    </x-filament::button>
                </div>
            </div>
        </div>

        <script>
            window.pdcMekletajiIntroSeen = function () {
                if (window.pdcMekletajiIntroSeenSent) {
                    return;
                }

                window.pdcMekletajiIntroSeenSent = true;

                fetch(@json(route('mekletaji.intro-seen')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    keepalive: true,
                }).catch(() => {});
            };
        </script>
    @endif
@endauth
