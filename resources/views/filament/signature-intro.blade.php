@auth
    @php
        $signatureUser = auth()->user();

        $showSignatureIntro = $signatureUser !== null
            && $signatureUser->signature_intro_seen_at === null;

        $signatureUrl = null;

        if ($showSignatureIntro) {
            if ($signatureUser->isAdmin()) {
                $signatureUrl = \App\Filament\Admin\Resources\UserResource::getUrl('edit', ['record' => $signatureUser]);
            } elseif (! $signatureUser->isPhoto() && ! $signatureUser->isStudent()) {
                $signatureUrl = \App\Filament\Admin\Pages\MyProfile::getUrl();
            }
        }
    @endphp

    @if ($showSignatureIntro)
        <style>
            .pdc-sig-intro {
                position: fixed;
                inset: 0;
                z-index: 56;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .pdc-sig-intro-overlay {
                position: fixed;
                inset: 0;
                z-index: 40;
                background: rgb(0 0 0 / 0.6);
            }

            .pdc-sig-intro-window {
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

            .pdc-sig-intro-head {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .pdc-sig-intro-kicker {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--pdc-primary);
            }

            .pdc-sig-intro-title {
                margin: 0.15rem 0 0;
                font-size: 1.25rem;
                font-weight: 700;
                line-height: 1.3;
            }

            .pdc-sig-intro-close {
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

            .pdc-sig-intro-close svg { width: 0.875rem; height: 0.875rem; }

            .pdc-sig-intro-body {
                margin-top: 0.75rem;
                font-size: 0.875rem;
                line-height: 1.55;
                color: #374151;
            }

            .pdc-sig-intro-list {
                display: grid;
                gap: 0.35rem;
                margin-top: 0.7rem;
                padding-left: 1.1rem;
                list-style: disc;
            }

            .pdc-sig-intro-foot {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                margin-top: 1.1rem;
            }

            body.pdc-sig-intro-lock { overflow: hidden !important; }

            .dark .pdc-sig-intro-window { border-color: #374151; background: #111827; color: #f3f4f6; }
            .dark .pdc-sig-intro-kicker { color: #8fc4be; }
            .dark .pdc-sig-intro-close { border-color: #374151; background: #1f2937; color: #d1d5db; }
            .dark .pdc-sig-intro-body { color: #d1d5db; }
        </style>

        <div
            class="pdc-sig-intro"
            x-data="{
                open: true,
                markSeen() {
                    if (window.pdcSignatureIntroSeenSent) return;
                    window.pdcSignatureIntroSeenSent = true;

                    fetch(@json(route('signature.intro-seen')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        keepalive: true,
                    }).catch(() => {});
                },
                close() {
                    this.markSeen();
                    this.open = false;
                    document.body.classList.remove('pdc-sig-intro-lock');
                },
            }"
            x-init="document.body.classList.add('pdc-sig-intro-lock')"
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="close()"
        >
            <div class="pdc-sig-intro-overlay" x-on:click="close()"></div>

            <div
                class="pdc-sig-intro-window"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pdc-sig-intro-title"
                x-trap.noreturn="open"
                x-init="$nextTick(() => $el.querySelector('.pdc-sig-intro-close')?.focus())"
            >
                <div class="pdc-sig-intro-head">
                    <div>
                        <div class="pdc-sig-intro-kicker">Kas jauns</div>
                        <h2 class="pdc-sig-intro-title" id="pdc-sig-intro-title">Jauns e-pasta paraksts</h2>
                    </div>

                    <button type="button" class="pdc-sig-intro-close" aria-label="Aizvērt" title="Aizvērt" x-on:click="close()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="pdc-sig-intro-body">
                    <p>Visiem lietotājiem ir izveidots vienots e-pasta paraksts.</p>
                    <ul class="pdc-sig-intro-list">
                        <li>Tas automātiski parādās katra no CRM nosūtītā e-pasta beigās, zem ziņas un virs CRM kājenes.</li>
                        <li>Parakstā ir jūsu foto, vārds, amats, e-pasts un tālrunis, ja tas norādīts.</li>
                    </ul>
                    @if ($signatureUrl)
                        <p style="margin-top: 0.7rem;">Ja nepieciešams, parakstu vari mainīt savā profilā.</p>
                    @endif
                </div>

                <div class="pdc-sig-intro-foot">
                    @if ($signatureUrl)
                        <x-filament::button tag="a" :href="$signatureUrl" x-on:click="markSeen()">
                            Rediģēt parakstu
                        </x-filament::button>
                    @endif

                    <x-filament::button color="gray" x-on:click="close()">
                        Aizvērt
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
@endauth
