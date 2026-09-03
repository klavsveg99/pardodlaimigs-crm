<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<style>
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
        --fc-today-bg-color: rgba(40, 88, 84, 0.08);
        --fc-today-border-color: rgba(40, 88, 84, 0.25);
        --fc-event-bg-color: var(--pdc-primary);
        --fc-event-border-color: var(--pdc-primary-darker);
        --fc-event-text-color: #fff;
        --fc-more-link-text-color: var(--pdc-primary);
        --fc-more-link-bg-color: rgba(40, 88, 84, 0.1);
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
        background-color: var(--pdc-primary) !important;
        border-color: var(--pdc-primary) !important;
        color: #fff !important;
    }

    .fc .fc-button:hover:not(:disabled) {
        background-color: color-mix(in srgb, var(--pdc-primary) 80%, white) !important;
        border-color: color-mix(in srgb, var(--pdc-primary) 80%, white) !important;
    }

    .fc .fc-button:not(:disabled):active,
    .fc .fc-button:not(:disabled).fc-button-active {
        background-color: var(--pdc-primary-darker) !important;
        border-color: var(--pdc-primary-darker) !important;
    }

    .fc .fc-button:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--pdc-primary) 80%, white);
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
    /* Calendar page "Aģents" filter select — explicit styling for light + dark */
    .pdc-agent-filter,
    #agent-filter {
        display: inline-block;
        width: 12rem;
        max-width: 100%;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        background-repeat: no-repeat;
        background-position: right 0.6rem center;
        background-size: 1.1rem;
        background-color: #ffffff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 2.25rem 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #111827;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
    }
    .pdc-agent-filter:focus,
    #agent-filter:focus {
        outline: none;
        border-color: var(--pdc-primary);
        box-shadow: 0 0 0 2px rgba(40, 88, 84, 0.2);
    }
    .pdc-agent-filter > option,
    #agent-filter > option {
        background-color: #ffffff;
        color: #111827;
    }
    .dark .pdc-agent-filter,
    .dark #agent-filter {
        background-color: #18181b;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20' stroke='%23a1a1aa'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
        border-color: #3f3f46;
        color: #e5e7eb;
    }
    .dark .pdc-agent-filter:hover,
    .dark #agent-filter:hover {
        border-color: #6b7280;
    }
    .dark .pdc-agent-filter:focus,
    .dark #agent-filter:focus {
        border-color: var(--pdc-primary);
        box-shadow: 0 0 0 2px rgba(40, 88, 84, 0.35);
    }
    .dark .pdc-agent-filter > option,
    .dark #agent-filter > option {
        background-color: #18181b;
        color: #e5e7eb;
    }

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
        background-color: rgba(40, 88, 84, 0.08) !important;
    }

    .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: var(--pdc-primary-darker) !important;
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
        color: var(--pdc-primary) !important;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    /* Make the calendar height fluid so it never overflows the viewport */
    .fc-calendar-wrapper {
        height: auto !important;
        min-height: 480px;
    }

    .fc-calendar-wrapper .fc {
        min-height: 440px;
    }

    @media (max-width: 768px) {
        .fc-calendar-wrapper {
            padding: 0.4rem;
            min-height: 0;
        }

        /* Remove horizontal padding from the section that wraps the calendar on mobile */
        .fi-section-content,
        .fi-wi-calendar-viewings .fi-section > .fi-section-content {
            padding: 0 !important;
        }

        .fc-calendar-wrapper .fc {
            min-height: 0;
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
            align-items: stretch !important;
            gap: 0.4rem;
        }

        .fc .fc-toolbar-chunk {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.3rem;
            max-width: 100%;
        }

        /* Center the title on its own row */
        .fc .fc-toolbar .fc-toolbar-chunk:nth-child(2) {
            justify-content: center;
            order: -1;
            width: 100%;
        }

        .fc .fc-toolbar-title {
            font-size: 1rem !important;
        }

        /* Buttons stretch and become easier to tap on touch screens */
        .fc .fc-button {
            flex: 1 1 auto;
            padding: 0.55rem 0.4rem !important;
            font-size: 0.75rem !important;
            min-height: 2.4rem;
            touch-action: manipulation;
        }

        /* Increase day-cell tap targets */
        .fc .fc-daygrid-day {
            min-height: 3.4rem;
        }

        .fc .fc-daygrid-day-number {
            padding: 0.4rem !important;
            font-size: 0.875rem !important;
        }

        .fc .fc-event {
            padding: 0.2rem 0.3rem !important;
            font-size: 0.6875rem !important;
        }

        /* List view: roomier rows on small screens */
        .fc .fc-list-event {
            padding: 0.6rem 0.5rem !important;
        }

        .fc .fc-list-event-title {
            line-height: 1.5 !important;
        }

        .fc .fc-timegrid-slot {
            height: 2.6rem !important;
        }
    }

    /* ── Dark mode — match the gray tones of the rest of the panel ── */
    .dark .fc {
        --fc-border-color: #3f3f46;
        --fc-page-bg-color: #18181b;
        --fc-neutral-bg-color: #18181b;
        --fc-today-bg-color: #27272a;
        --fc-today-border-color: #3f3f46;
        --fc-event-text-color: #fff;
        --fc-more-link-text-color: #a1a1aa;
        --fc-more-link-bg-color: #27272a;
    }

    .dark .fc-calendar-wrapper {
        border-color: #3f3f46;
        background: #18181b;
    }

    .dark .fc .fc-toolbar {
        border-bottom-color: #3f3f46;
    }

    .dark .fc .fc-toolbar-title {
        color: #f4f4f5 !important;
    }

    .dark .fc .fc-button {
        background-color: #3f3f46 !important;
        border-color: #3f3f46 !important;
        color: #f4f4f5 !important;
    }

    .dark .fc .fc-button:hover:not(:disabled) {
        background-color: #52525b !important;
        border-color: #52525b !important;
    }

    .dark .fc .fc-button:not(:disabled):active,
    .dark .fc .fc-button:not(:disabled).fc-button-active {
        background-color: #71717a !important;
        border-color: #71717a !important;
    }

    .dark .fc .fc-today-button {
        background-color: #3f3f46 !important;
        border-color: #3f3f46 !important;
        color: #f4f4f5 !important;
    }

    .dark .fc .fc-scrollgrid {
        border-color: #3f3f46 !important;
    }

    .dark .fc .fc-scrollgrid td,
    .dark .fc .fc-scrollgrid th {
        border-color: #3f3f46 !important;
    }

    .dark .fc .fc-col-header-cell {
        background: #18181b !important;
        color: #a1a1aa !important;
        border-color: #3f3f46 !important;
    }

    .dark .fc .fc-daygrid-day-number {
        color: #d4d4d8 !important;
    }

    .dark .fc .fc-daygrid-day.fc-day-today {
        background-color: #27272a !important;
    }

    .dark .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: #e4e4e7 !important;
        font-weight: 700;
    }

    .dark .fc .fc-daygrid-day:hover {
        background: #27272a !important;
    }

    .dark .fc .fc-more-link {
        color: #a1a1aa !important;
    }

    .dark .fc .fc-list-event:hover td {
        background-color: #1f1f23 !important;
    }

    .dark .fc .fc-list-day-cushion {
        background: #18181b !important;
    }

    .dark .fi-wi-calendar-viewings .fi-section-header-heading,
    .dark .fi-wi-calendar-viewings .fi-section .fi-section-heading {
        color: #f4f4f5 !important;
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
                        // On mobile show only 2-letter day names in both month and week/timegrid headers.
                        // (v6 passes a DateEnv marker object here — not a real Date — so build the date from its fields.)
                        dayHeaderFormat: (info) => {
                            const d = info.date;
                            const dt = new Date(Date.UTC(d.year, d.month, d.day));
                            const name = dt.toLocaleDateString('lv', { weekday: 'short' });
                            return window.innerWidth < 640 ? name.slice(0, 2) : name;
                        },
                        // Never let a single month day overflow vertically; collapse extra events into "+n more"
                        dayMaxEvents: window.innerWidth < 640 ? 2 : 3,
                        nowIndicator: true,
                        events: this.toFcEvents(),
                        eventClick: (info) => {
                            if (info.event.url) {
                                window.location.href = info.event.url;
                            }
                        },
                        height: 'auto',
                        expandRows: false,
                    });
                    this.calendar.render();
                },
            }));
        });
    })();
</script>
