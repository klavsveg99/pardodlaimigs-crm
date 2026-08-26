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

/* ── Standardized icon colors ─────────────────────────────────── */
/* Light mode: icons are var(--pdc-primary) on light backgrounds. */
.fi-icon,
.fi-btn .fi-icon,
.fi-section .fi-icon,
.fi-card .fi-icon,
.fi-wi-stats-overview-stat .fi-icon,
.fi-badge .fi-icon,
.fi-alert .fi-icon,
.fi-ta-table .fi-icon {
    color: var(--pdc-primary) !important;
}

/* Colored button icons stay white (solid bg). */
.fi-btn.fi-color-primary .fi-icon,
.fi-btn.fi-color-success .fi-icon,
.fi-btn.fi-color-warning .fi-icon,
.fi-btn.fi-color-danger .fi-icon,
.fi-btn.fi-color-info .fi-icon {
    color: #ffffff !important;
}

/* Dark mode: icons are white on dark backgrounds. */
.dark .fi-icon,
.dark .fi-btn .fi-icon,
.dark .fi-section .fi-icon,
.dark .fi-card .fi-icon,
.dark .fi-wi-stats-overview-stat .fi-icon,
.dark .fi-badge .fi-icon,
.dark .fi-alert .fi-icon,
.dark .fi-ta-table .fi-icon,
.dark .fi-modal .fi-icon,
.dark .fi-dropdown-panel .fi-icon,
.dark .fi-select-options .fi-icon,
.dark .fi-fo-select-options .fi-icon {
    color: #ffffff !important;
}

/* Sidebar icons stay as-is — excluded from global icon rules. */
.fi-sidebar .fi-icon {
    color: inherit !important;
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
.fi-modal-window,
.fi-file-upload .fi-modal,
.cropper-container,
.cropper-modal,
.fi-file-upload-image-editor {
    z-index: 99999 !important;
}

.fi-modal .fi-modal-overlay,
.fi-modal .fi-modal-window {
    z-index: 99999 !important;
}

.fi-file-upload .fi-btn.fi-color-primary,
.fi-file-upload-image-editor .fi-btn.fi-color-primary {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    color: #ffffff !important;
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
    color: #ffffff !important;
}

.dark .pdc-map-search {
    background: #0b0f14 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .pdc-map-container {
    border-color: #4b5563 !important;
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
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .fi-login .fi-input::placeholder,
.dark .fi-login-page .fi-input::placeholder {
    color: #9ca3af !important;
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
    background: #0b0f14 !important;
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
    background: #0b0f14 !important;
    border-color: #4b5563 !important;
}

.dark .fi-file-upload-dropzone:hover {
    border-color: var(--pdc-primary) !important;
}

.dark .fi-file-upload-item {
    background: #0b0f14 !important;
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
    background: #0b0f14 !important;
}

/* Table action dropdown/menu */
.dark .fi-ta-table [x-float],
.dark .fi-ta-table .fi-dropdown-panel,
.dark .fi-table .fi-dropdown-panel {
    background: #0b0f14 !important;
    border-color: #374151 !important;
}

.dark .fi-ta-table .fi-dropdown-panel .fi-dropdown-item,
.dark .fi-table .fi-dropdown-panel .fi-dropdown-item {
    color: #f3f4f6 !important;
}

.dark .fi-ta-table .fi-dropdown-panel .fi-dropdown-item:hover,
.dark .fi-table .fi-dropdown-panel .fi-dropdown-item:hover {
    background: #374151 !important;
}

/* Section headers in dark mode */
.dark .fi-section-header-heading {
    color: #f9fafb !important;
}

/* Badges in dark mode */
.dark .fi-badge {
    background: #0b0f14 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}

/* Alerts in dark mode */
.dark .fi-alert {
    background: #0b0f14 !important;
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

/* Table search/filter inputs - comprehensive override */
.dark .fi-ta-search-input,
.dark .fi-ta-filter-form .fi-input,
.dark .fi-table-search-input,
.dark .fi-table-filter-form .fi-input,
.dark .fi-toolbar .fi-input,
.dark [data-filament-table-search] input,
.dark [data-filament-table-filter] input,
.dark .fi-table-filters-form .fi-input {
    background: #0b0f14 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

.dark .fi-ta-search-input::placeholder,
.dark .fi-table-search-input::placeholder,
.dark .fi-toolbar .fi-input::placeholder {
    color: #9ca3af !important;
}

.dark .fi-ta-search-input:focus,
.dark .fi-table-search-input:focus,
.dark .fi-toolbar .fi-input:focus {
    --tw-ring-color: var(--pdc-primary) !important;
    --tw-ring-offset-color: #0b0f14 !important;
    border-color: var(--pdc-primary) !important;
}

/* Table action buttons - force white icons on transparent in dark mode */
.dark .fi-ta-actions .fi-btn,
.dark .fi-ta-header-actions .fi-btn,
.dark .fi-page-header .fi-btn,
.dark .fi-ta-table .fi-btn {
    background: transparent !important;
    color: #ffffff !important;
    border-color: transparent !important;
}

.dark .fi-ta-actions .fi-btn:hover,
.dark .fi-ta-header-actions .fi-btn:hover,
.dark .fi-page-header .fi-btn:hover,
.dark .fi-ta-table .fi-btn:hover {
    background: rgba(255, 255, 255, 0.1) !important;
}

.dark .fi-ta-actions .fi-btn svg,
.dark .fi-ta-header-actions .fi-btn svg,
.dark .fi-page-header .fi-btn svg,
.dark .fi-ta-table .fi-btn svg {
    color: #ffffff !important;
}

/* Dropdown action buttons inside table */
.dark .fi-ta-table .fi-dropdown-panel .fi-btn,
.dark .fi-table .fi-dropdown-panel .fi-btn {
    background: transparent !important;
    color: #ffffff !important;
}

.dark .fi-ta-table .fi-dropdown-panel .fi-btn:hover,
.dark .fi-table .fi-dropdown-panel .fi-btn:hover {
    background: #374151 !important;
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

/* Standardized section spacing */
.fi-section {
    margin-bottom: 1.5rem !important;
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
}

.fi-section > .fi-section-content-ctn {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
}

.fi-section > .fi-section-content-ctn > .fi-section-content {
    flex: 1 !important;
}

/* Property form grid - stretch sections to fill row height */
.fi-fo-field-wrp-grid > .fi-fo-field-wrp > .fi-section {
    height: 100% !important;
}

.fi-section-header {
    padding: 1.25rem 1.5rem 0 !important;
}

.fi-section-header-heading {
    font-size: 1rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
}

/* Remove Filament's built-in header/content border on all sections */
.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
    border-top-style: none !important;
    border-top-width: 0 !important;
}

.fi-section-content {
    padding: 1rem 1.5rem 1.5rem !important;
}

/* Home stats 4-col box inner padding must be 0 (20px side / 16px top removed) */
.fi-wi-stats-overview .fi-section-content {
    padding: 0 !important;
}

/* Fix massive perceived bottom padding: remove extra dd margin on last card */
.fi-section-content .grid > div:last-child dd,
.fi-section-content .rounded-xl dd {
    margin-bottom: 0 !important;
}
.fi-section-content dd:last-child {
    margin-bottom: 0 !important;
}

/* Stats overview widget - remove outer padding */
.fi-wi-stats-overview {
    padding: 0 !important;
}

.fi-wi-stats-overview-stat {
    padding: 1rem !important;
}

/* Restore Filament form field container spacing */
.fi-section-content .fi-fo-field,
.fi-section-content .fi-form-field,
.fi-section-content > div[class*="fi-fo"],
.fi-section-content > div[class*="fi-form"] {
    margin-bottom: 1rem !important;
}

.fi-section-content .fi-fo-field:last-child,
.fi-section-content .fi-form-field:last-child {
    margin-bottom: 0 !important;
}

/* Grid field spacing in sections */
.fi-section-content > .grid,
.fi-section-content > div[class*="grid"] {
    gap: 1.25rem !important;
}

/* View page dt/dd spacing */
.fi-section-content dt {
    font-size: 0.75rem !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: #6b7280 !important;
    margin-bottom: 0.25rem !important;
}

.dark .fi-section-content dt {
    color: #9ca3af !important;
}

.fi-section-content dd {
    font-size: 0.875rem !important;
    font-weight: 400 !important;
    margin: 0 0 1rem 0 !important;
    line-height: 1.5 !important;
}

.dark .fi-section-content dd {
    color: #f3f4f6 !important;
}

/* Description/prose spacing */
.fi-section-content .prose {
    margin: 0 !important;
    padding-top: 0.5rem !important;
}

/* Attachment grid spacing */
.fi-section-content .grid[class*="grid-cols"] {
    gap: 0.75rem !important;
}

/* Related records list spacing */
.fi-section-content .space-y-2 > * + * {
    margin-top: 0.5rem !important;
}

.fi-section-content .space-y-3 > * + * {
    margin-top: 0.75rem !important;
}

/* Empty state spacing */
.fi-section-content > p.text-center {
    padding: 2rem 1rem !important;
    margin: 0 !important;
}

/* Badge spacing in sections */
.fi-section-content .fi-badge {
    font-size: 0.7rem !important;
    padding: 0.125rem 0.5rem !important;
}

/* Avatar + text spacing */
.fi-section-content .flex.items-center.gap-3 {
    gap: 0.75rem !important;
}

/* File upload editor control panel footer buttons */
.fi-fo-file-upload-editor-control-panel-footer {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.fi-fo-file-upload-editor-control-panel-footer .fi-btn {
    flex: 1;
    font-size: 0.875rem;
}

.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-primary,
.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-primary:hover {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    color: #ffffff !important;
}

.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-primary.fi-btn-type-outlined,
.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-primary.fi-btn-type-outlined:hover {
    background: transparent !important;
    border: 2px solid var(--pdc-primary) !important;
    color: var(--pdc-primary) !important;
}

/* Save button in editor should also use accent, not green */
.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-success,
.fi-fo-file-upload-editor-control-panel-footer .fi-btn.fi-color-success:hover {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    color: #ffffff !important;
}

/* Hide admin sidebar/topbar when photo editor modal is open - must appear behind overlay */
html:has(.fi-modal) .fi-sidebar {
    display: none !important;
}
html:has(.fi-modal) .fi-topbar,
html:has(.fi-modal) .fi-header,
html:has(.fi-modal) [class*="form-actions"] {
    z-index: 1 !important;
}
html:has(.cropper-container) .fi-sidebar {
    display: none !important;
}

/* View page cards fallback - Tailwind utilities not in compiled app.css */
.fi-section-content .rounded-xl { border-radius: 0.75rem !important; }
.fi-section-content .border { border-width: 1px !important; border-style: solid !important; }
.fi-section-content .border-gray-200 { border-color: #e5e7eb !important; }
.fi-section-content .bg-gray-50 { background-color: #f9fafb !important; }
.fi-section-content .bg-white { background-color: #fff !important; }
.fi-section-content .p-4 { padding: 1rem !important; }
.fi-section-content .p-8 { padding: 2rem !important; }
.fi-section-content .gap-3 { gap: 0.75rem !important; }
.fi-section-content .text-center { text-align: center !important; }
.fi-section-content .border-dashed { border-style: dashed !important; }
.fi-section-content .border-2 { border-width: 2px !important; }
.fi-section-content .block { display: block !important; }
.fi-section-content .overflow-hidden { overflow: hidden !important; }
.fi-section-content .relative { position: relative !important; }
.fi-section-content .absolute { position: absolute !important; }
.fi-section-content .inset-0 { inset: 0 !important; }
.fi-section-content .left-2 { left: 0.5rem !important; }
.fi-section-content .top-2 { top: 0.5rem !important; }
.fi-section-content .h-32 { height: 8rem !important; }
.fi-section-content .w-full { width: 100% !important; }
.fi-section-content .object-cover { object-fit: cover !important; }
@media (min-width: 768px) { .fi-section-content .md\:h-36 { height: 9rem !important; } }
</style>
