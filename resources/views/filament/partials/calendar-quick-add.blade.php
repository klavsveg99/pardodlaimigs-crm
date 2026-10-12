{{--
    Kalendāra ātrā pievienošana. Atverama ar pārlūka notikumu
    `pdc-calendar-quick-add` (no FullCalendar dateClick ar detail {date,
    allDay}). Ļauj uzreiz izveidot uzdevumu vai apskati, datumu/laiku
    aizpildot automātiski; pēc saglabāšanas notikums parādās kalendārā bez
    lapas pārlādes (`pdc-calendar-event-created`).
--}}
@if (! auth()->user()?->isPhoto())
    <div
        data-pdc-calendar-quick-add
        data-options-url="{{ route('calendar.quick-add.options') }}"
        data-store-url="{{ route('calendar.quick-add.store') }}"
        x-data="calendarQuickAdd"
        x-on:pdc-calendar-quick-add.window="openWith($event.detail)"
        x-cloak
    >
        <template x-if="open">
            <div class="pdc-qa-overlay fi-fixed-positioning-context" x-on:click.self="close()">
                <div class="pdc-qa-modal" x-on:keydown.escape.window="close()">
                    <div class="pdc-qa-head">
                        <span x-text="type === 'task' ? 'Jauns uzdevums' : 'Jauna apskate'"></span>
                        <button type="button" class="pdc-qa-close" x-on:click="close()" title="Aizvērt">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="pdc-qa-type">
                        <button type="button" :class="{ 'is-active': type === 'task' }" x-on:click="type = 'task'">Uzdevums</button>
                        <button type="button" :class="{ 'is-active': type === 'viewing' }" x-on:click="type = 'viewing'">Apskate</button>
                    </div>

                    <div class="pdc-qa-body">
                        <template x-if="! optionsLoaded">
                            <div class="pdc-qa-loading">Ielādē...</div>
                        </template>

                        <template x-if="optionsLoaded && type === 'task'">
                            <div class="pdc-qa-fields">
                                <label class="pdc-qa-field">
                                    <span>Nosaukums</span>
                                    <input type="text" x-model="form.title" />
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Līdz</span>
                                    <input type="datetime-local" x-model="form.due_at" />
                                </label>
                                <label class="pdc-qa-field" x-show="canPickAgent">
                                    <span>Aģents</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'assigned_user_id', 'placeholder' => 'Izvēlies'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Izpildītājs</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'izpilditajs_id', 'placeholder' => 'Nav'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Klients</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'client_id', 'placeholder' => 'Nav'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Īpašums</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'property_id', 'placeholder' => 'Nav'])
                                </label>
                                <label class="pdc-qa-field pdc-qa-field-full">
                                    <span>Apraksts</span>
                                    <textarea rows="3" x-model="form.body"></textarea>
                                </label>
                            </div>
                        </template>

                        <template x-if="optionsLoaded && type === 'viewing'">
                            <div class="pdc-qa-fields">
                                <label class="pdc-qa-field">
                                    <span>Īpašums</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'property_id', 'placeholder' => 'Izvēlies'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Klients</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'client_id', 'placeholder' => 'Izvēlies'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Kad</span>
                                    <input type="datetime-local" x-model="form.scheduled_at" />
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Ilgums (min)</span>
                                    <input type="number" min="1" max="600" step="5" x-model="form.duration_min" />
                                </label>
                                <label class="pdc-qa-field" x-show="canPickAgent">
                                    <span>Aģents</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'agent_user_id', 'placeholder' => 'Izvēlies'])
                                </label>
                                <label class="pdc-qa-field">
                                    <span>Statuss</span>
                                    @include('filament.partials.quick-add-select', ['field' => 'status', 'placeholder' => 'Statuss', 'state' => 'form.status', 'searchable' => false])
                                </label>
                                <label class="pdc-qa-field pdc-qa-field-full">
                                    <span>Piezīmes</span>
                                    <textarea rows="3" x-model="form.notes_md"></textarea>
                                </label>
                            </div>
                        </template>

                        <div class="pdc-qa-error" x-show="error" x-text="error"></div>
                    </div>

                    <div class="pdc-qa-foot">
                        <button type="button" class="pdc-qa-cancel" x-on:click="close()">Atcelt</button>
                        <button type="button" class="pdc-qa-save" x-on:click="submit()" :disabled="saving">
                            <span x-text="saving ? 'Saglabā...' : 'Saglabāt'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        .pdc-qa-overlay {
            position: fixed;
            inset: 0;
            z-index: 2147483647;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6);
            padding: 1rem;
        }

        .pdc-qa-modal {
            width: 100%;
            max-width: 560px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        }

        .pdc-qa-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 0.95rem;
            color: #111827;
        }

        .pdc-qa-close {
            height: 2rem;
            width: 2rem;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: transparent;
            border: 1px solid #e5e7eb;
            color: #374151;
            cursor: pointer;
            padding: 0;
        }

        .pdc-qa-close svg {
            width: 1rem;
            height: 1rem;
        }

        .pdc-qa-type {
            display: flex;
            gap: 0.4rem;
            padding: 0.75rem 1.1rem 0;
        }

        .pdc-qa-type button {
            flex: 1 1 0;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            font-size: 0.83rem;
            cursor: pointer;
        }

        .pdc-qa-type button.is-active {
            background: var(--pdc-primary, #285854);
            border-color: var(--pdc-primary, #285854);
            color: #ffffff;
        }

        .pdc-qa-body {
            padding: 1rem 1.1rem;
            overflow-y: auto;
        }

        .pdc-qa-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .pdc-qa-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .pdc-qa-field-full {
            grid-column: 1 / -1;
        }

        .pdc-qa-field > span {
            font-size: 0.78rem;
            font-weight: 600;
            color: #374151;
        }

        .pdc-qa-field > input,
        .pdc-qa-field > select,
        .pdc-qa-field > textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.45rem 0.6rem;
            font-size: 0.875rem;
            background: #ffffff;
            color: #111827;
            font-family: inherit;
        }

        .pdc-qa-field .fi-input-wrp {
            width: 100%;
        }

        .pdc-qa-loading {
            padding: 1.5rem 0;
            text-align: center;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .pdc-qa-field textarea {
            resize: vertical;
        }

        .pdc-qa-error {
            margin-top: 0.75rem;
            padding: 0.5rem 0.7rem;
            border-radius: 0.45rem;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            font-size: 0.8rem;
        }

        .pdc-qa-foot {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 0.9rem 1.1rem;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .pdc-qa-cancel {
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            background: transparent;
            color: #374151;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .pdc-qa-save {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
            padding: 0.5rem 0.95rem;
            border-radius: 0.5rem;
            background: var(--pdc-primary, #285854);
            color: #ffffff;
            border: 1px solid var(--pdc-primary-darker, #285854);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.84rem;
        }

        .pdc-qa-save:disabled {
            opacity: 0.7;
            cursor: default;
        }

        .dark .pdc-qa-modal {
            background: #111827;
        }

        .dark .pdc-qa-head {
            color: #f3f4f6;
            border-bottom-color: #27303a;
        }

        .dark .pdc-qa-close {
            border-color: #27303a;
            color: #d1d5db;
        }

        .dark .pdc-qa-type button {
            background: #1f2937;
            border-color: #27303a;
            color: #d1d5db;
        }

        .dark .pdc-qa-type button.is-active {
            background: var(--pdc-primary, #285854);
            border-color: var(--pdc-primary, #285854);
            color: #ffffff;
        }

        .dark .pdc-qa-field > span {
            color: #d1d5db;
        }

        .dark .pdc-qa-field > input,
        .dark .pdc-qa-field > select,
        .dark .pdc-qa-field > textarea {
            background: #0b0f14;
            border-color: #27303a;
            color: #f3f4f6;
        }

        .dark .pdc-qa-loading {
            color: #9ca3af;
        }

        .dark .pdc-qa-error {
            background: #3b1d1d;
            border-color: #7f1d1d;
            color: #fca5a5;
        }

        .dark .pdc-qa-foot {
            background: #111827;
            border-top-color: #27303a;
        }

        .dark .pdc-qa-cancel {
            color: #d1d5db;
            border-color: #27303a;
        }

        @media (max-width: 560px) {
            .pdc-qa-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('calendarQuickAdd', () => ({
                open: false,
                type: 'task',
                saving: false,
                error: '',
                optionsUrl: '',
                storeUrl: '',
                optionsLoaded: false,
                canPickAgent: false,
                optionLists: { agents: [], izpilditajs: [], clients: [], properties: [] },
                form: {},
                init() {
                    this.optionsUrl = this.$el.dataset.optionsUrl;
                    this.storeUrl = this.$el.dataset.storeUrl;
                    this.form = this.emptyForm('');
                },
                emptyForm(dateTime) {
                    return {
                        title: '',
                        due_at: dateTime,
                        assigned_user_id: '',
                        izpilditajs_id: '',
                        client_id: '',
                        property_id: '',
                        body: '',
                        scheduled_at: dateTime,
                        duration_min: 30,
                        agent_user_id: '',
                        status: 'scheduled',
                        notes_md: '',
                    };
                },
                toLocal(dateStr, allDay) {
                    if (! dateStr) return '';
                    if (allDay || dateStr.length <= 10) return dateStr.slice(0, 10) + 'T00:00';
                    return dateStr.slice(0, 16);
                },
                openWith(detail) {
                    if (! detail) return;
                    this.type = 'task';
                    this.error = '';
                    this.form = this.emptyForm(this.toLocal(detail.date, detail.allDay));
                    this.open = true;
                    this.loadOptions();
                },
                async loadOptions() {
                    if (this.optionsLoaded) return;
                    try {
                        const resp = await fetch(this.optionsUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (! resp.ok) return;
                        const data = await resp.json();
                        this.optionLists = {
                            agents: data.agents || [],
                            izpilditajs: data.izpilditajs || [],
                            clients: data.clients || [],
                            properties: data.properties || [],
                        };
                        this.canPickAgent = !! data.canPickAgent;
                        this.optionsLoaded = true;
                    } catch (e) {
                        // Sarakstus neizdevās ielādēt - lauki paliek tukši.
                    }
                },
                // Opcijas Filament select komponentei (static, bez Livewire).
                optionsFor(field) {
                    if (field === 'status') {
                        return [
                            { label: 'Ieplānota', value: 'scheduled', isDisabled: false },
                            { label: 'Notikusi', value: 'done', isDisabled: false },
                            { label: 'Atcelta', value: 'cancelled', isDisabled: false },
                            { label: 'Neatnāca', value: 'no_show', isDisabled: false },
                        ];
                    }
                    const lists = {
                        assigned_user_id: this.optionLists.agents,
                        agent_user_id: this.optionLists.agents,
                        izpilditajs_id: this.optionLists.izpilditajs,
                        client_id: this.optionLists.clients,
                        property_id: this.optionLists.properties,
                    };
                    return (lists[field] || []).map((o) => ({ label: o.name, value: String(o.id), isDisabled: false }));
                },
                // Nolasa Filament select komponentu atlasītās vērtības.
                readSelects() {
                    const out = {};
                    this.$el.querySelectorAll('[data-pdc-select]').forEach((el) => {
                        const field = el.getAttribute('data-pdc-select');
                        const inner = el.querySelector('[x-data]');
                        const data = inner && window.Alpine ? window.Alpine.$data(inner) : null;
                        out[field] = data && data.state != null ? String(data.state) : '';
                    });
                    return out;
                },
                close() {
                    if (this.saving) return;
                    this.open = false;
                },
                async submit() {
                    if (this.saving) return;
                    this.error = '';

                    Object.assign(this.form, this.readSelects());

                    if (this.type === 'task' && ! this.form.title.trim()) {
                        this.error = 'Nosaukums ir obligāts.';
                        return;
                    }
                    if (this.type === 'viewing') {
                        if (! this.form.property_id) { this.error = 'Īpašums ir obligāts.'; return; }
                        if (! this.form.client_id) { this.error = 'Klients ir obligāts.'; return; }
                        if (! this.form.scheduled_at) { this.error = 'Norādi datumu un laiku.'; return; }
                    }

                    this.saving = true;
                    try {
                        const token = document.querySelector('meta[name=csrf-token]')?.content;
                        const resp = await fetch(this.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ type: this.type, ...this.form }),
                        });
                        const data = await resp.json().catch(() => ({}));
                        if (! resp.ok || ! data.ok) {
                            this.error = this.firstError(data);
                            return;
                        }
                        this.open = false;
                        window.dispatchEvent(new CustomEvent('pdc-calendar-event-created', { detail: { event: data.event } }));
                        this.toast(this.type === 'task' ? 'Uzdevums pievienots kalendāram' : 'Apskate pievienota kalendāram');
                    } catch (e) {
                        this.error = 'Kļūda: ' + (e.message || 'nezināma');
                    } finally {
                        this.saving = false;
                    }
                },
                firstError(data) {
                    if (data && data.errors) {
                        const first = Object.values(data.errors)[0];
                        if (Array.isArray(first) && first.length) return first[0];
                    }
                    return (data && data.message) || 'Saglabāšana neizdevās.';
                },
                toast(message) {
                    const note = document.createElement('div');
                    note.textContent = message;
                    note.setAttribute('style', 'position:fixed;top:1rem;right:1rem;z-index:2147483647;background:var(--pdc-primary, #285854);color:#fff;padding:0.6rem 1rem;border-radius:0.5rem;font-weight:600;font-size:0.85rem;box-shadow:0 8px 24px rgba(0,0,0,0.25);');
                    document.body.appendChild(note);
                    setTimeout(() => note.remove(), 3500);
                },
            }));
        });
    </script>
@endif
