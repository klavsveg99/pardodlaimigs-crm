<style>
:root {
    --pdc-primary: #285854;
    --pdc-primary-darker: #1e4843;
    --pdc-warning: #f97316;
    /* Kill lighter teal #008078 everywhere – remap Tailwind teal palette to primary */
    --teal-50: var(--pdc-primary) !important;
    --teal-100: var(--pdc-primary) !important;
    --teal-200: var(--pdc-primary) !important;
    --teal-300: var(--pdc-primary) !important;
    --teal-400: var(--pdc-primary) !important;
    --teal-500: var(--pdc-primary) !important;
    --teal-600: var(--pdc-primary) !important;
    --teal-700: var(--pdc-primary-darker) !important;
    --teal-800: var(--pdc-primary-darker) !important;
    --teal-900: var(--pdc-primary-darker) !important;
    --teal-950: var(--pdc-primary-darker) !important;
    /* Kill the light success teal (oklch 0.753529 0.150273 168.74) that shows on
       success-coloured buttons/actions – remap the success palette to the brand
       accent (var(--pdc-primary)) just like primary/teal/warning. */
    --success-50: var(--pdc-primary) !important;
    --success-100: var(--pdc-primary) !important;
    --success-200: var(--pdc-primary) !important;
    --success-300: var(--pdc-primary) !important;
    --success-400: var(--pdc-primary) !important;
    --success-500: var(--pdc-primary) !important;
    --success-600: var(--pdc-primary) !important;
    --success-700: var(--pdc-primary-darker) !important;
    --success-800: var(--pdc-primary-darker) !important;
    --success-900: var(--pdc-primary-darker) !important;
    --success-950: var(--pdc-primary-darker) !important;
}

/* Marketing consent checkbox – true bottom alignment (flex, not pt-6) */
.fi-sc-component:has(#form\.marketing_consent) {
    display: flex !important;
    align-items: end !important;
    align-self: end !important;
    height: 100% !important;
}
.fi-sc-component:has(#form\.marketing_consent) .fi-fo-field,
.fi-fo-field:has(#form\.marketing_consent) {
    display: flex !important;
    align-items: end !important;
    height: 100% !important;
    margin-top: 0 !important;
    padding-top: 0 !important;
}
.fi-fo-field:has(#form\.marketing_consent) .fi-fo-field-label-col,
.fi-fo-field:has(#form\.marketing_consent) .fi-fo-field-label-ctn {
    display: flex !important;
    align-items: end !important;
    height: 100% !important;
}
/* GDPR checkbox bg – force primary, kill teal #008078 even when disabled/checked */
#form\.marketing_consent:checked,
.fi-fo-field:has(#form\.marketing_consent) .fi-checkbox-input:checked,
.fi-fo-field:has(#form\.marketing_consent) input[type="checkbox"]:checked {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    accent-color: var(--pdc-primary) !important;
}
#form\.marketing_consent:checked:disabled,
.fi-fo-field:has(#form\.marketing_consent) .fi-checkbox-input:checked:disabled {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    opacity: 1 !important;
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

/* Chart widget (main dashboard "Komisijas tendence") heading must match the
   neutral style of the other dashboard table-widget section titles. */
.fi-wi-chart .fi-section-header-heading {
    color: var(--gray-950) !important;
}

.dark .fi-section-header-heading,
.dark h1.fi-header-heading {
    color: #f9fafb !important;
}

.dark .fi-wi-chart .fi-section-header-heading {
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
.fi-fo-select-options,
.fi-fo-field .fi-select-options,
.fi-dropdown-panel[x-float] {
    z-index: 50000 !important;
}

/* Reduce sidebar/header authority – modals/lightbox/editor must be on top */
.fi-sidebar,
.fi-topbar,
.fi-header,
.fi-main-ctn {
    z-index: 10 !important;
}

.fi-modal,
.fi-modal-window,
.fi-file-upload .fi-modal,
.pdc-editor-modal,
.pdc-editor-panel,
.cropper-container,
.cropper-modal,
.fi-file-upload-image-editor,
.fi-file-upload-image-editor .fi-modal {
    z-index: 100000 !important;
}

.fi-modal .fi-modal-overlay,
.fi-modal .fi-modal-window,
.pdc-editor-modal .fi-modal-overlay {
    z-index: 100000 !important;
}

.fi-file-upload .fi-btn.fi-color-primary,
.fi-file-upload-image-editor .fi-btn.fi-color-primary,
.pdc-editor-panel .fi-btn.fi-color-primary {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    color: #ffffff !important;
}

/* Colored buttons have solid backgrounds, so their labels stay white – only for solid (not outlined/ghost) */
.fi-btn.fi-color-primary:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost),
.fi-btn.fi-color-primary:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost) *,
.fi-btn.fi-color-success:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost),
.fi-btn.fi-color-success:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost) *,
.fi-btn.fi-color-warning:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost),
.fi-btn.fi-color-warning:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost) *,
.fi-btn.fi-color-danger:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost),
.fi-btn.fi-color-danger:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost) *,
.fi-btn.fi-color-info:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost),
.fi-btn.fi-color-info:not(.fi-btn-type-outlined):not(.fi-btn-type-ghost) * {
    color: #ffffff !important;
}

/* Outlined danger (Dzēst atlasītos) must be red on transparent, not white-on-white */
.fi-btn.fi-color-danger.fi-btn-type-outlined {
    background-color: transparent !important;
    border-color: #cf2e2e !important;
    color: #cf2e2e !important;
}
.fi-btn.fi-color-danger.fi-btn-type-outlined * {
    color: #cf2e2e !important;
}
.fi-btn.fi-color-danger.fi-btn-type-outlined:hover {
    background-color: rgba(207,46,46,0.08) !important;
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

/* Let Filament handle field-label <-> input gap (fi-fo-field gap-y-2 / field.css).
   Only normalize checkbox/radio inline vertical alignment which was broken by the
   previous global block label override. No global margin-bottom override here. */
.fi-fo-field.fi-fo-field-has-inline-label .fi-fo-field-label {
    align-items: center !important;
}
.fi-fo-field.fi-fo-field-has-inline-label .fi-checkbox-input,
.fi-fo-field.fi-fo-field-has-inline-label .fi-radio-input {
    margin: 0 !important;
}
.opacity-60 { opacity: 0.6 !important; }
.opacity-0 { opacity: 0 !important; }
.group:hover .group-hover\:opacity-100 { opacity: 1 !important; }

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

/* All checked checkboxes use global accent via var(--pdc-primary) (#285854) – not hardcoded */
input[type="checkbox"]:checked,
.fi-checkbox-input:checked,
.fi-checkbox input:checked,
.fi-fo-field input[type="checkbox"]:checked,
.fi-checkbox-input[checked],
input[type="checkbox"][checked] {
    accent-color: var(--pdc-primary) !important;
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    --tw-ring-color: var(--pdc-primary) !important;
    --tw-ring-offset-color: var(--pdc-primary) !important;
}
.dark .fi-checkbox input:checked,
.dark .fi-checkbox-input:checked,
.dark input[type="checkbox"]:checked {
    accent-color: var(--pdc-primary) !important;
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}
/* Checkbox/radio/toggle (unchecked dark) */
.dark .fi-checkbox input,
.dark .fi-radio input,
.dark .fi-toggle input {
    accent-color: var(--pdc-primary) !important;
}

.dark .fi-form-check-label {
    color: #f3f4f6 !important;
}

/* Tabs – light + dark always use primary, kill teal #008078 */
.fi-tabs-nav {
    border-color: #e5e7eb !important;
}
.fi-tabs-tab {
    color: #6b7280 !important;
}
.fi-tabs-tab.fi-active,
.fi-tabs-tab[aria-selected="true"],
[role="tablist"] button[aria-selected="true"],
[role="tab"].fi-active {
    color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}
.fi-tabs-tab.fi-active::after,
.fi-tabs-tab[aria-selected="true"]::after {
    background-color: var(--pdc-primary) !important;
}
.dark .fi-tabs-nav {
    border-color: #374151 !important;
}

.dark .fi-tabs-tab {
    color: #9ca3af !important;
}

.dark .fi-tabs-tab.fi-active,
.dark .fi-tabs-tab[aria-selected="true"],
.dark [role="tablist"] button[aria-selected="true"] {
    color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}

/* Remap any teal usage to primary */
.fi-color-teal {
    --color-50: var(--pdc-primary) !important;
    --color-100: var(--pdc-primary) !important;
    --color-200: var(--pdc-primary) !important;
    --color-300: var(--pdc-primary) !important;
    --color-400: var(--pdc-primary) !important;
    --color-500: var(--pdc-primary) !important;
    --color-600: var(--pdc-primary-darker) !important;
    --color-700: var(--pdc-primary-darker) !important;
    --color-800: var(--pdc-primary-darker) !important;
    --color-900: var(--pdc-primary-darker) !important;
    --color-950: var(--pdc-primary-darker) !important;
}

/* Remap any success usage (buttons/actions) to primary – kills the light
   success teal oklch(0.753529 0.150273 168.74) that appeared on new buttons. */
.fi-color-success {
    --color-50: var(--pdc-primary) !important;
    --color-100: var(--pdc-primary) !important;
    --color-200: var(--pdc-primary) !important;
    --color-300: var(--pdc-primary) !important;
    --color-400: var(--pdc-primary) !important;
    --color-500: var(--pdc-primary) !important;
    --color-600: var(--pdc-primary-darker) !important;
    --color-700: var(--pdc-primary-darker) !important;
    --color-800: var(--pdc-primary-darker) !important;
    --color-900: var(--pdc-primary-darker) !important;
    --color-950: var(--pdc-primary-darker) !important;
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
    overflow-x: auto !important;
}

.fi-ta-table {
    table-layout: auto !important;
    box-sizing: border-box !important;
    width: 100% !important;
    min-width: 720px !important;
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

/* Standardized section spacing – keep Filament's grid for content containers;
   previous display:block override broke StatsOverview 4-col (@xl/fi-grid) */
.fi-section {
    margin-bottom: 1.5rem !important;
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

/* Form field & grid gaps are owned by Filament (fi-sc gap / fi-fo-field gap-y-2).
   Previous margin-bottom + gap overrides created double-spacing and inconsistency
   between resources. Remove them; keep Filament defaults uniform. */

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

/* Sidebar/header no longer hide behind modals – proper z-index above handles it */

/* View page cards fallback - Tailwind utilities not in compiled app.css */
.fi-section-content .rounded-xl { border-radius: 0.75rem !important; }
.fi-section-content .border { border-width: 1px !important; border-style: solid !important; }
.fi-section-content .border-gray-200 { border-color: #e5e7eb !important; }
.fi-section-content .bg-gray-50 { background-color: #f9fafb !important; }
.fi-section-content .bg-white { background-color: #fff !important; }
.fi-section-content .p-4 { padding: 1rem !important; }
.fi-section-content .grid { display: grid !important; }
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
.fi-section-content .mt-4 { margin-top: 1rem !important; }
.fi-section-content .mt-6 { margin-top: 1.5rem !important; }
.fi-section-content .shadow-sm { --tw-shadow: 0 1px 3px 0 var(--tw-shadow-color, rgb(0 0 0 / 0.1)), 0 1px 2px -1px var(--tw-shadow-color, rgb(0 0 0 / 0.1)); box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow); }
.fi-section-content .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
.fi-section-content .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
@media (min-width: 768px) { .fi-section-content .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } .fi-section-content .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } .fi-section-content .md\:h-36 { height: 9rem !important; } }
@media (min-width: 1024px) { .fi-section-content .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; } .fi-section-content .lg\:grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)) !important; } }
</style>
