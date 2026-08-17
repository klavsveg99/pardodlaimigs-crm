<style>
:root {
    --pdc-primary: #285854;
    --pdc-primary-darker: #1e4843;
}

/* Brand/sidebar treatment. Filament owns the page, form, table and modal colors. */
.fi-sidebar {
    background: linear-gradient(180deg, #1e4843 0%, #285854 100%) !important;
}

.fi-sidebar-nav-groups {
    background: transparent !important;
}

.fi-sidebar-item.fi-active .fi-sidebar-item-btn {
    background: rgba(255, 255, 255, 0.14) !important;
}

.fi-sidebar-item.fi-active .fi-sidebar-item-label,
.fi-sidebar-item.fi-active .fi-sidebar-item-icon,
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-btn,
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label,
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-icon {
    color: #ffffff !important;
}

.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-btn:hover {
    background: rgba(255, 255, 255, 0.08) !important;
}

.fi-sidebar-group-label,
.fi-sidebar-group-collapse-btn {
    color: rgba(255, 255, 255, 0.85) !important;
}

.fi-section-header-heading,
h1.fi-header-heading {
    color: var(--primary-600) !important;
}

.dark .fi-section-header-heading,
.dark h1.fi-header-heading {
    color: var(--primary-400) !important;
}

/* Keep the dark shell intentionally monochrome. */
.dark .fi-sidebar,
.dark .fi-topbar,
.dark .fi-header,
.dark .fi-header-ctn,
.dark .fi-main-ctn {
    background: #0b0f14 !important;
    border-color: #27303a !important;
}

.dark .fi-topbar *,
.dark .fi-header *,
.dark .fi-header-ctn * {
    color: #f9fafb !important;
}

.dark .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
    background: #ffffff !important;
    color: #0b0f14 !important;
}

.dark .fi-sidebar-item.fi-active .fi-sidebar-item-label,
.dark .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
    color: #0b0f14 !important;
}

/* Floating menus and select popovers must clear tables, buttons and cards. */
[x-float],
.fi-dropdown-panel,
.fi-select-options,
.fi-fo-select-options {
    z-index: 9999 !important;
}

.fi-modal,
.fi-modal-window {
    z-index: 10000 !important;
}

/* Colored buttons have solid backgrounds, so their labels stay white. */
.fi-btn.fi-color-primary,
.fi-btn.fi-color-primary *,
.fi-btn.fi-color-success,
.fi-btn.fi-color-success *,
.fi-btn.fi-color-warning,
.fi-btn.fi-color-warning *,
.fi-btn.fi-color-danger,
.fi-btn.fi-color-danger *,
.fi-btn.fi-color-info,
.fi-btn.fi-color-info * {
    color: #ffffff !important;
}

.fi-btn.fi-color-primary {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}

.fi-btn.fi-color-primary:hover {
    background-color: var(--pdc-primary-darker) !important;
    border-color: var(--pdc-primary-darker) !important;
}

.fi-link {
    color: var(--primary-600) !important;
}

.dark .fi-link {
    color: var(--primary-400) !important;
}

.dark .pdc-map-search {
    background: #111827 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .pdc-map-search::placeholder {
    color: #9ca3af !important;
}

.dark .pdc-map-help {
    color: #9ca3af !important;
}

.fi-sidebar-item-badge-ctn .fi-badge,
.fi-sidebar-item-badge-ctn .fi-badge-label,
.fi-sidebar-item-badge-ctn .fi-badge-label-ctn {
    color: #ffffff !important;
}

/* Keep data cells readable without wrapping column headings or clipping cards. */
.fi-ta-ctn,
.fi-ta-content-ctn,
.fi-ta-content {
    max-width: 100% !important;
    min-width: 0 !important;
}

.fi-ta-content-ctn {
    overflow-x: hidden !important;
}

.fi-ta-table {
    table-layout: auto !important;
    box-sizing: border-box !important;
    width: calc(100% - 2px) !important;
    max-width: calc(100% - 2px) !important;
    min-width: 0 !important;
    margin-inline: 1px !important;
}

.fi-ta-table td {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    min-width: 0 !important;
}

.fi-ta-table td.pdc-nowrap,
.fi-ta-table td.pdc-nowrap .fi-ta-text {
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
}

.fi-ta-table th {
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
}

.fi-ta-table .fi-ta-text {
    overflow: visible !important;
    text-overflow: clip !important;
}

.fi-ta-actions {
    max-width: 100% !important;
}

@media (min-width: 640px) {
    .fi-ta-table td:has(> .fi-ta-actions) {
        width: 1% !important;
        min-width: 9rem !important;
        padding-inline: 1rem !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
    }

    .fi-ta-table td > .fi-ta-actions {
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 1rem !important;
        white-space: nowrap !important;
    }

    .fi-ta-table td > .fi-ta-actions > * {
        flex: 0 0 auto !important;
        margin: 0 !important;
    }
}
</style>
