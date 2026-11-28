@auth
    @php
        $featureAnnouncement = app(\App\Services\Announcements\FeatureAnnouncements::class)
            ->nextFor(auth()->user());
    @endphp

    @if ($featureAnnouncement)
        <style>
            [x-cloak] { display: none !important; }

            .pdc-feature-popup {
                position: fixed;
                inset: 0;
                z-index: 56;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .pdc-feature-popup-overlay {
                position: fixed;
                inset: 0;
                z-index: 40;
                background: rgb(0 0 0 / 0.6);
            }

            .pdc-feature-popup-window {
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

            .pdc-feature-popup-head {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .pdc-feature-popup-kicker {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--pdc-primary);
            }

            .pdc-feature-popup-title {
                margin: 0.15rem 0 0;
                font-size: 1.25rem;
                font-weight: 700;
                line-height: 1.3;
            }

            .pdc-feature-popup-close {
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

            .pdc-feature-popup-close svg { width: 0.875rem; height: 0.875rem; }

            .pdc-feature-popup-body {
                margin-top: 0.75rem;
                font-size: 0.875rem;
                line-height: 1.55;
                color: #374151;
            }

            .pdc-feature-popup-list {
                display: grid;
                gap: 0.35rem;
                margin-top: 0.7rem;
                padding-left: 1.1rem;
                list-style: disc;
            }

            .pdc-feature-popup-foot {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: 0.5rem;
                margin-top: 1.1rem;
            }

            body.pdc-feature-popup-lock { overflow: hidden !important; }

            .dark .pdc-feature-popup-window { border-color: #374151; background: #111827; color: #f3f4f6; }
            .dark .pdc-feature-popup-kicker { color: #8fc4be; }
            .dark .pdc-feature-popup-close { border-color: #374151; background: #1f2937; color: #d1d5db; }
            .dark .pdc-feature-popup-body { color: #d1d5db; }
        </style>

        <div
            class="pdc-feature-popup"
            data-seen-url="{{ route('feature-announcements.seen', ['feature' => $featureAnnouncement['key']]) }}"
            x-data="{
                open: true,
                markSeen() {
                    window.pdcFeatureAnnouncementSeen && window.pdcFeatureAnnouncementSeen();
                },
                close() {
                    this.markSeen();
                    this.open = false;
                    document.body.classList.remove('pdc-feature-popup-lock');
                },
            }"
            x-init="document.body.classList.add('pdc-feature-popup-lock')"
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="close()"
        >
            <div class="pdc-feature-popup-overlay" x-on:click="close()"></div>

            <div
                class="pdc-feature-popup-window"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pdc-feature-popup-title"
                x-trap.noreturn="open"
                x-init="$nextTick(() => $el.querySelector('.pdc-feature-popup-close')?.focus())"
            >
                <div class="pdc-feature-popup-head">
                    <div>
                        <div class="pdc-feature-popup-kicker">{{ $featureAnnouncement['kicker'] }}</div>
                        <h2 class="pdc-feature-popup-title" id="pdc-feature-popup-title">{{ $featureAnnouncement['title'] }}</h2>
                    </div>

                    <button
                        type="button"
                        class="pdc-feature-popup-close"
                        aria-label="Aizvērt"
                        title="Aizvērt"
                        x-on:click="close()"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="pdc-feature-popup-body">
                    @if (filled($featureAnnouncement['body_html'] ?? null))
                        <p>{!! $featureAnnouncement['body_html'] !!}</p>
                    @else
                        <p>{{ $featureAnnouncement['body'] }}</p>
                    @endif

                    @if ($featureAnnouncement['bullets'] !== [])
                        <ul class="pdc-feature-popup-list">
                            @foreach ($featureAnnouncement['bullets'] as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="pdc-feature-popup-foot">
                    @if ($featureAnnouncement['link_url'])
                        <x-filament::button tag="a" :href="$featureAnnouncement['link_url']" x-on:click="markSeen()">
                            {{ $featureAnnouncement['link_label'] }}
                        </x-filament::button>
                    @endif

                    <x-filament::button color="gray" x-on:click="close()">
                        Aizvērt
                    </x-filament::button>
                </div>
            </div>
        </div>

        <script>
            window.pdcFeatureAnnouncementSeen = function () {
                if (window.pdcFeatureAnnouncementSeenSent) {
                    return;
                }

                window.pdcFeatureAnnouncementSeenSent = true;

                const url = document.querySelector('.pdc-feature-popup')?.dataset.seenUrl;

                if (!url) {
                    return;
                }

                fetch(url, {
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
