<div class="pdc-ai-panel" x-data="{
        open: false,
        result: { title: '', description: '', description_ss: '', facebook: '', instagram: '' },
        inserted: { title: false, description: false },
        init() {
            this.$wire.on('ai-results-updated', () => {
                const r = this.$wire.aiResult ?? {};
                this.result = {
                    title: r.title ?? '',
                    description: r.description ?? '',
                    description_ss: r.description_ss ?? '',
                    facebook: r.facebook ?? '',
                    instagram: r.instagram ?? '',
                };
                this.inserted = { title: false, description: false };
                this.open = true;
            });
        },
        regenerate(mode) {
            this.$wire.generateAi(mode, this.result.description);
        },
        insert(field) {
            if (field === 'description') {
                this.$wire.set('data.description', this.result.description);
            } else if (field === 'title') {
                this.$wire.set('data.title', this.result.title);
            }
            this.inserted[field] = true;
        },
        copy(text) {
            navigator.clipboard?.writeText(text ?? '');
        },
    }" x-cloak>
    <style>
        .pdc-ai-panel [x-cloak] { display: none !important; }
        .pdc-ai-panel .pdc-ai-modal {
            position: fixed; inset: 0; z-index: 100;
            display: flex; align-items: flex-start; justify-content: center;
            padding: 1.5rem 1rem; overflow-y: auto;
            background: rgb(0 0 0 / 0.45);
        }
        .pdc-ai-panel .pdc-ai-card {
            width: 100%; max-width: 720px; margin-top: 2rem;
            background: #fff; border-radius: 0.75rem;
            box-shadow: 0 20px 40px rgb(0 0 0 / 0.25);
        }
        .dark .pdc-ai-panel .pdc-ai-card { background: #111827; color: #f9fafb; }
        .pdc-ai-panel .pdc-ai-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.875rem 1.25rem;
            border-bottom: 1px solid rgb(148 163 184 / 0.35);
        }
        .pdc-ai-panel .pdc-ai-body { padding: 1rem 1.25rem 1.25rem; max-height: 70vh; overflow-y: auto; }
        .pdc-ai-panel .pdc-ai-sec { margin-bottom: 1.1rem; }
        .pdc-ai-panel .pdc-ai-sec-label {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            font-size: .8rem; font-weight: 600; margin-bottom: .35rem;
        }
        .dark .pdc-ai-panel .pdc-ai-sec-label { color: #e5e7eb; }
        .pdc-ai-panel .pdc-ai-sec textarea {
            width: 100%; border: 1px solid rgb(148 163 184 / 0.6); border-radius: .5rem;
            padding: .55rem .7rem; font-size: .85rem; line-height: 1.45; min-height: 92px;
            background: #fff; color: #111827; resize: vertical;
        }
        .dark .pdc-ai-panel .pdc-ai-sec textarea { background: #0b0f14; color: #f3f4f6; border-color: #374151; }
        .pdc-ai-panel .pdc-ai-btn {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .3rem .6rem; border-radius: .45rem; font-size: .75rem; font-weight: 600;
            border: 1px solid rgb(148 163 184 / 0.6); background: #fff; color: #374151; cursor: pointer;
        }
        .pdc-ai-panel .pdc-ai-btn:hover:not(:disabled) { background: #f1f5f9; }
        .pdc-ai-panel .pdc-ai-btn:disabled { opacity: .55; cursor: not-allowed; }
        .dark .pdc-ai-panel .pdc-ai-btn { background: #1f2937; color: #e5e7eb; border-color: #374151; }
        .pdc-ai-panel .pdc-ai-btn-primary {
            background: var(--primary, #14532d); color: #fff; border-color: var(--primary, #14532d);
        }
        .pdc-ai-panel .pdc-ai-btn-primary:hover:not(:disabled) { filter: brightness(1.1); background: var(--primary, #14532d); }
        .pdc-ai-panel .pdc-ai-btn-inserted { background: #dcfce7 !important; color: #166534 !important; border-color: #86efac !important; }
        .pdc-ai-panel .pdc-ai-foot {
            display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: flex-end;
            padding: .75rem 1.25rem; border-top: 1px solid rgb(148 163 184 / 0.35);
        }
        .pdc-ai-panel .pdc-ai-gen-btn {
            display: inline-flex; align-items: center; gap: .45rem;
            padding: .5rem .9rem; border-radius: .5rem; font-size: .85rem; font-weight: 600;
            border: 1px solid rgb(148 163 184 / 0.6); background: #fff; color: #374151; cursor: pointer;
        }
        .dark .pdc-ai-panel .pdc-ai-gen-btn { background: #1f2937; color: #e5e7eb; border-color: #374151; }
        .pdc-ai-panel .pdc-ai-gen-btn:disabled { opacity: .6; cursor: wait; }
        .pdc-ai-panel .pdc-ai-spin {
            width: .9rem; height: .9rem; border-radius: 9999px;
            border: 2px solid rgb(148 163 184 / .5); border-top-color: currentColor;
            animation: pdc-spin 1s linear infinite; display: inline-block;
        }
        @keyframes pdc-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="flex items-center gap-3 flex-wrap">
        <button type="button" class="pdc-ai-gen-btn" @click="$wire.generateAi('full')" :disabled="$wire.aiGenerating">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L13.663 8.5a2 2 0 0 0 1.437 1.437l6.135 1.581a.5.5 0 0 1 0 .964L15.5 13.663a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/></svg>
            <span wire:loading.remove wire:target="generateAi">Ģenerēt tekstus ar AI</span>
            <span wire:loading wire:target="generateAi"><span class="pdc-ai-spin"></span> Ģenerē…</span>
        </button>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Ģenerē: pilno aprakstu (LV+EN), ss.lv tekstu, virsrakstu, Facebook un Instagram
            @if ($providerLabel !== '—') · {{ $providerLabel }} @endif
        </span>
    </div>

    <template x-if="open">
        <div class="pdc-ai-modal" @click.self="open = false" @keydown.escape.window="open = false">
            <div class="pdc-ai-card" role="dialog" aria-modal="true">
                <div class="pdc-ai-head">
                    <strong class="text-sm">AI ģenerētie teksti</strong>
                    <button type="button" class="pdc-ai-btn" @click="open = false">Aizvērt</button>
                </div>
                <div class="pdc-ai-body">
                    <div class="pdc-ai-sec">
                        <div class="pdc-ai-sec-label">
                            <span>Virsraksta variants</span>
                            <span class="flex gap-2">
                                <button type="button" class="pdc-ai-btn" @click="copy(result.title)">Kopēt</button>
                                <button type="button" class="pdc-ai-btn" :class="inserted.title ? 'pdc-ai-btn-inserted' : ''"
                                        :disabled="inserted.title || !result.title"
                                        @click="insert('title')">
                                    <span x-show="!inserted.title">Ievietot nosaukumā</span>
                                    <span x-show="inserted.title">✓ Ievietots</span>
                                </button>
                            </span>
                        </div>
                        <textarea x-model="result.title" x-on:input="inserted.title = false"
                                  placeholder="Ģenerētais virsraksts"></textarea>
                    </div>

                    <div class="pdc-ai-sec">
                        <div class="pdc-ai-sec-label">
                            <span>Pilnais apraksts (mājaslapa / portāli, LV+EN)</span>
                            <span class="flex gap-2">
                                <button type="button" class="pdc-ai-btn" @click="copy(result.description)">Kopēt</button>
                                <button type="button" class="pdc-ai-btn" :class="inserted.description ? 'pdc-ai-btn-inserted' : ''"
                                        :disabled="inserted.description || !result.description"
                                        @click="insert('description')">
                                    <span x-show="!inserted.description">Ievietot aprakstā</span>
                                    <span x-show="inserted.description">✓ Ievietots</span>
                                </button>
                            </span>
                        </div>
                        <textarea x-model="result.description" x-on:input="inserted.description = false"
                                  style="min-height: 170px"
                                  placeholder="Ģenerētais apraksts"></textarea>
                    </div>

                    <div class="pdc-ai-sec">
                        <div class="pdc-ai-sec-label">
                            <span>ss.lv teksts</span>
                            <button type="button" class="pdc-ai-btn" @click="copy(result.description_ss)">Kopēt</button>
                        </div>
                        <textarea x-model="result.description_ss" placeholder="ss.lv stila teksts"></textarea>
                    </div>

                    <div class="pdc-ai-sec">
                        <div class="pdc-ai-sec-label">
                            <span>Facebook ieraksts</span>
                            <button type="button" class="pdc-ai-btn" @click="copy(result.facebook)">Kopēt</button>
                        </div>
                        <textarea x-model="result.facebook" placeholder="Facebook teksts"></textarea>
                    </div>

                    <div class="pdc-ai-sec">
                        <div class="pdc-ai-sec-label">
                            <span>Instagram / Reels</span>
                            <button type="button" class="pdc-ai-btn" @click="copy(result.instagram)">Kopēt</button>
                        </div>
                        <textarea x-model="result.instagram" placeholder="Instagram teksts"></textarea>
                    </div>
                </div>
                <div class="pdc-ai-foot">
                    <button type="button" class="pdc-ai-btn" :disabled="$wire.aiGenerating" @click="regenerate('full')">Ģenerēt vēlreiz</button>
                    <button type="button" class="pdc-ai-btn" :disabled="$wire.aiGenerating" @click="regenerate('shorten')">Saīsināt</button>
                    <button type="button" class="pdc-ai-btn" :disabled="$wire.aiGenerating" @click="regenerate('persuasive')">Padarīt pārliecinošāku</button>
                </div>
            </div>
        </div>
    </template>
</div>
