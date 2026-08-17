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
    color: var(--pdc-primary-darker) !important;
}

.dark .fi-section-header-heading,
.dark h1.fi-header-heading {
    color: #f9fafb !important;
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

.dark .fi-sidebar-item.fi-active .fi-sidebar-item-badge-ctn .fi-badge,
.dark .fi-sidebar-item.fi-active .fi-sidebar-item-badge-ctn .fi-badge-label,
.dark .fi-sidebar-item.fi-active .fi-sidebar-item-badge-ctn .fi-badge-label-ctn {
    background: #0b0f14 !important;
    color: #ffffff !important;
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
    color: var(--pdc-primary) !important;
}

.dark .fi-link {
    color: var(--pdc-primary) !important;
}

.dark .pdc-map-search {
    background: #0b0f14 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .pdc-map-search::placeholder {
    color: #9ca3af !important;
}

.dark .pdc-map-help {
    color: #9ca3af !important;
}

/* Keep data cells readable without wrapping column headings or clipping cards. */
.fi-ta-ctn,
.fi-ta-content-ctn,
.fi-ta-content {
    max-width: 100% !important;
    min-width: 0 !important;
}

/* Login page dark mode - match admin dark shell */
.dark .fi-login,
.dark .fi-login-page,
.dark body.fi-login {
    background: #0b0f14 !important;
}

.dark .fi-login .fi-card,
.dark .fi-login-page .fi-card {
    background: #0b0f14 !important;
    border-color: #27303a !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
}

.dark .fi-login .fi-card *,
.dark .fi-login-page .fi-card * {
    color: #f9fafb !important;
}

.dark .fi-login .fi-btn.fi-color-primary,
.dark .fi-login-page .fi-btn.fi-color-primary {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}

.dark .fi-login .fi-btn.fi-color-primary:hover,
.dark .fi-login-page .fi-btn.fi-color-primary:hover {
    background-color: var(--pdc-primary-darker) !important;
    border-color: var(--pdc-primary-darker) !important;
}

.dark .fi-login .fi-input,
.dark .fi-login-page .fi-input {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .fi-login .fi-input::placeholder,
.dark .fi-login-page .fi-input::placeholder {
    color: #9ca3af !important;
}

.dark .fi-login .fi-label,
.dark .fi-login-page .fi-label {
    color: #f9fafb !important;
}

/* Form inputs dark mode - avoid white-on-white */
.dark .fi-input,
.dark .fi-textarea,
.dark .fi-select,
.dark .fi-select select,
.dark .fi-multi-select,
.dark .fi-multi-select select,
.dark .fi-date-time-picker input,
.dark .fi-date-picker input,
.dark .fi-time-picker input,
.dark .fi-color-picker input,
.dark .fi-file-upload input,
.dark .fi-rich-editor .tiptap {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .fi-input::placeholder,
.dark .fi-textarea::placeholder {
    color: #9ca3af !important;
}

.dark .fi-label {
    color: #f9fafb !important;
}

.dark .fi-hint {
    color: #9ca3af !important;
}

/* Select options in dropdowns */
.dark .fi-select-options,
.dark .fi-fo-select-options {
    background: #1f2937 !important;
    border-color: #374151 !important;
}

.dark .fi-select-options .fi-select-option,
.dark .fi-fo-select-options .fi-select-option {
    color: #f3f4f6 !important;
}

.dark .fi-select-options .fi-select-option:hover,
.dark .fi-fo-select-options .fi-select-option:hover {
    background: #374151 !important;
}

.dark .fi-select-options .fi-select-option[aria-selected="true"],
.dark .fi-fo-select-options .fi-select-option[aria-selected="true"] {
    background: #2d5a56 !important;
    color: #fff !important;
}

/* File upload dropzone */
.dark .fi-file-upload-dropzone {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
}

.dark .fi-file-upload-dropzone:hover {
    border-color: var(--pdc-primary) !important;
}

.dark .fi-file-upload-item {
    background: #1f2937 !important;
    border-color: #374151 !important;
}

/* Checkbox/radio/toggle */
.dark .fi-checkbox input,
.dark .fi-radio input,
.dark .fi-toggle input {
    accent-color: var(--pdc-primary) !important;
}

.dark .fi-form-check-label {
    color: #f3f4f6 !important;
}

/* Tabs */
.dark .fi-tabs-nav {
    border-color: #374151 !important;
}

.dark .fi-tabs-tab {
    color: #9ca3af !important;
}

.dark .fi-tabs-tab.fi-active {
    color: var(--pdc-primary) !important;
}

/* Cards in dark mode */
.dark .fi-card {
    background: #0b0f14 !important;
    border-color: #27303a !important;
}

/* Tables in dark mode */
.dark .fi-ta-table {
    border-color: #374151 !important;
}

.dark .fi-ta-table th {
    background: #0b0f14 !important;
    color: #f9fafb !important;
    border-color: #374151 !important;
}

.dark .fi-ta-table td {
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}

.dark .fi-ta-table tr:hover td {
    background: #1f2937 !important;
}

/* Table action buttons in dark mode */
.dark .fi-ta-table .fi-btn {
    color: #f3f4f6 !important;
}

.dark .fi-ta-table .fi-btn.fi-color-primary,
.dark .fi-ta-table .fi-btn.fi-color-primary *,
.dark .fi-ta-table .fi-btn.fi-color-success,
.dark .fi-ta-table .fi-btn.fi-color-success *,
.dark .fi-ta-table .fi-btn.fi-color-warning,
.dark .fi-ta-table .fi-btn.fi-color-warning *,
.dark .fi-ta-table .fi-btn.fi-color-danger,
.dark .fi-ta-table .fi-btn.fi-color-danger *,
.dark .fi-ta-table .fi-btn.fi-color-info,
.dark .fi-ta-table .fi-btn.fi-color-info * {
    color: #ffffff !important;
}

.dark .fi-ta-table .fi-btn.fi-color-gray {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .fi-ta-table .fi-btn.fi-color-gray:hover {
    background: #4b5563 !important;
    border-color: #6b7280 !important;
}

/* Table action dropdown/menu */
.dark .fi-ta-table [x-float],
.dark .fi-ta-table .fi-dropdown-panel {
    background: #1f2937 !important;
    border-color: #374151 !important;
}

.dark .fi-ta-table .fi-dropdown-panel .fi-dropdown-item {
    color: #f3f4f6 !important;
}

.dark .fi-ta-table .fi-dropdown-panel .fi-dropdown-item:hover {
    background: #374151 !important;
}

/* Section headers in dark mode */
.dark .fi-section-header {
    border-color: #27303a !important;
}

.dark .fi-section-header-heading {
    color: #f9fafb !important;
}

/* Badges in dark mode */
.dark .fi-badge {
    background: #1f2937 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}

/* Alerts in dark mode */
.dark .fi-alert {
    background: #1f2937 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}

/* Modals in dark mode */
.dark .fi-modal-content {
    background: #0b0f14 !important;
    border-color: #27303a !important;
}

.dark .fi-modal-header {
    border-color: #27303a !important;
}

.dark .fi-modal-footer {
    border-color: #27303a !important;
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

/* Focus rings - override Filament default primary color */
.fi-input:focus,
.fi-textarea:focus,
.fi-select:focus,
.fi-multi-select:focus,
.fi-date-time-picker input:focus,
.fi-date-picker input:focus,
.fi-time-picker input:focus,
.fi-file-upload-dropzone:focus-within {
    --tw-ring-color: var(--pdc-primary) !important;
    --tw-ring-offset-color: #fff !important;
}

.dark .fi-input:focus,
.dark .fi-textarea:focus,
.dark .fi-select:focus,
.dark .fi-multi-select:focus,
.dark .fi-date-time-picker input:focus,
.dark .fi-date-picker input:focus,
.dark .fi-time-picker input:focus,
.dark .fi-file-upload-dropzone:focus-within {
    --tw-ring-color: var(--pdc-primary) !important;
    --tw-ring-offset-color: #0b0f14 !important;
}

/* Checkbox/radio focus */
.fi-checkbox input:focus,
.fi-radio input:focus,
.fi-toggle input:focus {
    --tw-ring-color: var(--pdc-primary) !important;
}

/* Button focus */
.fi-btn:focus {
    --tw-ring-color: var(--pdc-primary) !important;
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
