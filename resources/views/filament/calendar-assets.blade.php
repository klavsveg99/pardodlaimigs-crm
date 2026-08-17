<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<style>
    /* ── Filament teal palette ─────────────────────────────────── */
    :root {
        --crm-primary: #285854;
        --crm-primary-light: #3a7d78;
        --crm-primary-dark: #1e4340;
        --crm-primary-50: #eef6f5;
        --crm-primary-100: #d4ece9;
        --crm-primary-200: #a8d9d3;
    }

    /* ── Container ─────────────────────────────────────────────── */
    .fc-calendar-wrapper {
        border: 1px solid var(--fi-color-base-200, #e5e7eb);
        border-radius: var(--fi-radius-lg, 0.75rem);
        background: var(--fi-color-base-50, #fff);
        padding: 1rem;
        display: flex;
        flex-direction: column;
    }

    /* The calendar fills the wrapper completely */
    .fc-calendar-wrapper .fc {
        flex: 1 1 auto;
        min-height: 0;
    }

    /* ── Widget heading — match Filament table widget style ────── */
    .fi-wi-calendar-viewings .fi-section-header-heading,
    .fi-wi-calendar-viewings .fi-section .fi-section-heading {
        color: var(--fi-color-base-900, #0b0f14) !important;
        font-weight: 600 !important;
    }

    .fi-wi-calendar-viewings .fi-section-header-icon,
    .fi-wi-calendar-viewings .fi-section .fi-section-icon {
        color: var(--fi-color-base-500, #6b7280) !important;
    }

    /* ── FullCalendar CSS vars ─────────────────────────────────── */
    .fc {
        --fc-border-color: #e5e7eb;
        --fc-page-bg-color: #fff;
        --fc-neutral-bg-color: #f9fafb;
        --fc-today-bg-color: var(--crm-primary-50);
        --fc-today-border-color: var(--crm-primary-200);
        --fc-event-bg-color: var(--crm-primary);
        --fc-event-border-color: var(--crm-primary-dark);
        --fc-event-text-color: #fff;
        --fc-more-link-text-color: var(--crm-primary);
        --fc-more-link-bg-color: var(--crm-primary-100);
    }

    /* ── Toolbar ───────────────────────────────────────────────── */
    .fc .fc-toolbar {
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem !important;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .fc .fc-toolbar-title {
        font-size: 1.125rem !important;
        font-weight: 600 !important;
        color: #0b0f14 !important;
        text-transform: none !important;
        letter-spacing: normal !important;
    }

    /* ── Buttons ───────────────────────────────────────────────── */
    .fc .fc-button {
        font-weight: 500 !important;
        font-size: 0.8125rem !important;
        text-transform: none !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.375rem !important;
        box-shadow: none !important;
        transition: background-color 0.15s, border-color 0.15s !important;
        background-color: var(--crm-primary) !important;
        border-color: var(--crm-primary) !important;
        color: #fff !important;
    }

    .fc .fc-button:hover:not(:disabled) {
        background-color: var(--crm-primary-light) !important;
        border-color: var(--crm-primary-light) !important;
    }

    .fc .fc-button:not(:disabled):active,
    .fc .fc-button:not(:disabled).fc-button-active {
        background-color: var(--crm-primary-dark) !important;
        border-color: var(--crm-primary-dark) !important;
    }

    .fc .fc-button:focus-visible {
        outline: 2px solid var(--crm-primary-light);
        outline-offset: 2px;
    }

    .fc .fc-button:disabled {
        opacity: 0.5;
    }

    .fc .fc-today-button {
        background-color: #6b7280 !important;
        border-color: #6b7280 !important;
        color: #fff !important;
    }

    .fc .fc-today-button:hover:not(:disabled) {
        background-color: #4b5563 !important;
        border-color: #4b5563 !important;
    }

    /* View-switcher button group */
    .fc .fc-button-group > .fc-button {
        border-radius: 0 !important;
    }

    .fc .fc-button-group > .fc-button:first-child {
        border-top-left-radius: 0.375rem !important;
        border-bottom-left-radius: 0.375rem !important;
    }

    .fc .fc-button-group > .fc-button:last-child {
        border-top-right-radius: 0.375rem !important;
        border-bottom-right-radius: 0.375rem !important;
    }

    /* ── Day grid ──────────────────────────────────────────────── */
    .fc .fc-scrollgrid {
        border-color: #e5e7eb !important;
    }

    .fc .fc-scrollgrid td,
    .fc .fc-scrollgrid th {
        border-color: #e5e7eb !important;
    }

    .fc .fc-col-header-cell {
        background-color: #f9fafb !important;
        padding: 0.5rem 0 !important;
        font-size: 0.6875rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: #6b7280 !important;
        border-color: #e5e7eb !important;
    }

    .fc .fc-daygrid-day-number {
        padding: 0.375rem 0.5rem !important;
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        color: #374151 !important;
    }

    .fc .fc-daygrid-day.fc-day-today {
        background-color: var(--crm-primary-50) !important;
    }

    .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: var(--crm-primary-dark) !important;
        font-weight: 700 !important;
    }

    .fc .fc-daygrid-day:hover {
        background-color: #f9fafb !important;
    }

    /* ── Events ────────────────────────────────────────────────── */
    .fc .fc-event {
        border-radius: 0.25rem !important;
        padding: 0.125rem 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        border: none !important;
        cursor: pointer !important;
    }

    .fc .fc-daygrid-event {
        margin-bottom: 0.125rem !important;
    }

    /* ── "More" link ───────────────────────────────────────────── */
    .fc .fc-more-link {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        color: var(--crm-primary) !important;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .fc-calendar-wrapper {
            overflow: hidden;
            padding: 0.5rem;
        }

        .fc-calendar-wrapper .fc,
        .fc .fc-view-harness,
        .fc .fc-scroller {
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden !important;
        }

        .fc .fc-toolbar {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.35rem;
        }

        .fc .fc-toolbar-chunk {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            max-width: 100%;
        }

        .fc .fc-toolbar-title {
            font-size: 1rem !important;
        }

        .fc .fc-button {
            padding: 0.3rem 0.45rem !important;
            font-size: 0.7rem !important;
        }
    }

    /* ── Dark mode ─────────────────────────────────────────────── */
    .dark .fc {
        --fc-border-color: #374151;
        --fc-page-bg-color: #0b0f14;
        --fc-neutral-bg-color: #0b0f14;
        --fc-today-bg-color: #1a2e2d;
        --fc-today-border-color: #2d5a56;
        --fc-event-text-color: #fff;
        --fc-more-link-text-color: #5eead4;
        --fc-more-link-bg-color: #1a2e2d;
    }

    .dark .fc-calendar-wrapper {
        border-color: #374151;
        background: #0b0f14;
    }

    .dark .fc .fc-toolbar {
        border-bottom-color: #374151;
    }

    .dark .fc .fc-toolbar-title {
        color: #f3f4f6 !important;
    }

    .dark .fc .fc-button {
        background-color: #2d5a56 !important;
        border-color: #2d5a56 !important;
        color: #fff !important;
    }

    .dark .fc .fc-button:hover:not(:disabled) {
        background-color: #3a7d78 !important;
        border-color: #3a7d78 !important;
    }

    .dark .fc .fc-button:not(:disabled):active,
    .dark .fc .fc-button:not(:disabled).fc-button-active {
        background-color: #1e4843 !important;
        border-color: #1e4843 !important;
    }

    .dark .fc .fc-today-button {
        background-color: #4b5563 !important;
        border-color: #4b5563 !important;
    }

    .dark .fc .fc-scrollgrid {
        border-color: #374151 !important;
    }

    .dark .fc .fc-scrollgrid td,
    .dark .fc .fc-scrollgrid th {
        border-color: #374151 !important;
    }

    .dark .fc .fc-col-header-cell {
        background: #0b0f14 !important;
        color: #9ca3af !important;
        border-color: #374151 !important;
    }

    .dark .fc .fc-daygrid-day-number {
        color: #d1d5db !important;
    }

    .dark .fc .fc-daygrid-day.fc-day-today {
        background-color: #1a2e2d !important;
    }

    .dark .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: #5eead4 !important;
    }

    .dark .fc .fc-daygrid-day:hover {
        background: #0b0f14 !important;
    }

    .dark .fc .fc-more-link {
        color: #5eead4 !important;
    }

    .dark .fi-wi-calendar-viewings .fi-section-header-heading,
    .dark .fi-wi-calendar-viewings .fi-section .fi-section-heading {
        color: #f3f4f6 !important;
    }
</style>

<script>
    (function () {
        function loadCalendarLibrary() {
            if (window.FullCalendar || !document.querySelector('[data-calendar-viewings]')) {
                return;
            }
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js';
            document.head.appendChild(s);
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('calendarCombined', () => ({
                events: [],
                agentId: null,
                calendar: null,
                init() {
                    try {
                        this.events = JSON.parse(this.$el.dataset.events || '[]');
                    } catch (e) {
                        this.events = [];
                    }
                    loadCalendarLibrary();
                    this.$nextTick(() => this.renderCalendar());

                    window.addEventListener('calendar-agent-changed', (e) => {
                        this.agentId = e.detail?.agentId ? String(e.detail.agentId) : null;
                        this.refreshEvents();
                    });
                },
                filteredEvents() {
                    if (!this.agentId) return this.events;
                    return this.events.filter((e) => String(e.agentId) === this.agentId);
                },
                refreshEvents() {
                    if (!this.calendar) return;
                    this.calendar.removeAllEvents();
                    this.calendar.addEventSource(this.toFcEvents());
                },
                toFcEvents() {
                    return this.filteredEvents().map((e) => {
                        const prefix = e.type === 'task' ? '[Uzdevums] ' : '[Apskate] ';
                        return {
                            id: e.id,
                            title: prefix + e.title + ' – ' + e.client,
                            start: e.start,
                            end: e.end,
                            backgroundColor: e.color,
                            borderColor: e.color,
                            url: e.url,
                            extendedProps: {
                                agent: e.agent,
                                status: e.status,
                                type: e.type,
                            },
                        };
                    });
                },
                renderCalendar() {
                    if (typeof FullCalendar === 'undefined') {
                        setTimeout(() => this.renderCalendar(), 150);
                        return;
                    }
                    if (!this.$refs.calendar || this.calendar) {
                        return;
                    }
                    this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                        initialView: window.innerWidth < 640 ? 'listWeek' : 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: window.innerWidth < 640 ? 'listWeek,dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                        },
                        buttonText: {
                            today: 'Šodien',
                            month: 'Mēnesis',
                            week: 'Nedēļa',
                            day: 'Diena',
                            list: 'Saraksts',
                        },
                        locale: 'lv',
                        events: this.toFcEvents(),
                        eventClick: (info) => {
                            if (info.event.url) {
                                window.location.href = info.event.url;
                            }
                        },
                        height: parseInt(this.$el.dataset.height || '700', 10),
                    });
                    this.calendar.render();
                },
            }));
        });
    })();
</script>
