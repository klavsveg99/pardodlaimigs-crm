<style>
:root {
    --pdc-primary: #285854;
    --pdc-primary-darker: #1e4843;
    --pdc-secondary: #414042;
}
:root, :root.dark {
    --primary-50: var(--pdc-primary) !important;
    --primary-100: var(--pdc-primary) !important;
    --primary-200: var(--pdc-primary) !important;
    --primary-300: var(--pdc-primary) !important;
    --primary-400: var(--pdc-primary) !important;
    --primary-500: var(--pdc-primary) !important;
    --primary-600: var(--pdc-primary-darker) !important;
    --primary-700: var(--pdc-primary-darker) !important;
    --primary-800: var(--pdc-primary-darker) !important;
    --primary-900: var(--pdc-primary-darker) !important;
    --primary-950: var(--pdc-primary-darker) !important;
}
.fi-color-primary {
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
.fi-sidebar {
    background: linear-gradient(180deg, #1e4843 0%, #285854 100%) !important;
}
.fi-sidebar-nav-groups {
    background: transparent !important;
}
.fi-sidebar-item.fi-active .fi-sidebar-item-btn {
    background: rgba(255, 255, 255, 0.14) !important;
    color: #ffffff !important;
}
.fi-sidebar-item.fi-active .fi-sidebar-item-label,
.fi-sidebar-item.fi-active .fi-sidebar-item-icon {
    color: #ffffff !important;
}
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-btn {
    color: #ffffff !important;
}
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label,
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-icon {
    color: #ffffff !important;
}
.fi-sidebar-item:not(.fi-active) .fi-sidebar-item-btn:hover {
    background: rgba(255, 255, 255, 0.08) !important;
}
.fi-sidebar-group-label {
    color: rgba(255, 255, 255, 0.85) !important;
}
.fi-sidebar-group-collapse-btn {
    color: rgba(255, 255, 255, 0.85) !important;
}
.fi-section-header-heading, h1.fi-header-heading {
    color: var(--pdc-primary) !important;
}
.fi-btn-color-primary, button.fi-btn-primary,
.fi-btn.fi-bg-color-400.fi-color-primary,
.fi-btn.fi-color-primary,
.fi-btn.fi-color-primary.fi-bg-color-400,
.fi-simple-page button.fi-btn.fi-color-primary {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
    color: #ffffff !important;
}
.fi-bg-color-400 {
    background-color: var(--pdc-primary) !important;
    border-color: var(--pdc-primary) !important;
}
button.fi-btn-primary:hover,
.fi-btn.fi-bg-color-400.fi-color-primary:hover,
.fi-btn.fi-color-primary:hover,
.fi-btn.fi-color-primary.fi-bg-color-400:hover,
.fi-bg-color-400:hover,
.fi-simple-page button.fi-btn.fi-color-primary:hover {
    background-color: var(--pdc-primary-darker) !important;
    border-color: var(--pdc-primary-darker) !important;
}
.fi-btn.fi-color-success:hover,
.fi-btn.fi-color-success.fi-bg-color-400:hover,
.fi-bg-color-400.fi-color-success:hover {
    background-color: #0a5c47 !important;
    border-color: #0a5c47 !important;
}
.fi-modal-close-btn, .fi-input-wrp {
    border-color: #d1d5db;
}
.fi-link {
    color: var(--pdc-primary) !important;
}
.fi-sidebar-item-badge-ctn .fi-badge,
.fi-sidebar-item-badge-ctn .fi-badge-label,
.fi-sidebar-item-badge-ctn .fi-badge-label-ctn {
    color: #ffffff !important;
}
.fi-btn.fi-color-primary,
.fi-btn.fi-color-primary *,
.fi-badge.fi-color-primary,
.fi-badge.fi-color-primary *,
.fi-bg-color-400.fi-color-primary,
.fi-bg-color-400.fi-color-primary *,
.fi-btn.fi-color-success,
.fi-btn.fi-color-success *,
.fi-bg-color-400.fi-color-success,
.fi-bg-color-400.fi-color-success *,
.fi-btn.fi-color-warning,
.fi-btn.fi-color-warning *,
.fi-bg-color-400.fi-color-warning,
.fi-bg-color-400.fi-color-warning *,
.fi-btn.fi-color-danger,
.fi-btn.fi-color-danger *,
.fi-bg-color-400.fi-color-danger,
.fi-bg-color-400.fi-color-danger *,
.fi-btn.fi-color-info,
.fi-btn.fi-color-info *,
.fi-bg-color-400.fi-color-info,
.fi-bg-color-400.fi-color-info * {
    color: #ffffff !important;
}
.fi-ta-text-item span.fi-size-md.fi-font-bold {
    letter-spacing: 0.01em;
}
.fi-ta-ctn {
    max-width: 100% !important;
    min-width: 0 !important;
}
.fi-ta-content-ctn,
.fi-ta-content {
    max-width: 100% !important;
    min-width: 0 !important;
    overflow-x: hidden !important;
}
.fi-ta-table {
    table-layout: auto !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}
.fi-ta-table td,
.fi-ta-table th {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    min-width: 0 !important;
}
.fi-ta-table .fi-ta-text {
    overflow: visible !important;
    text-overflow: clip !important;
}
.fi-ta-actions {
    flex-wrap: wrap !important;
    max-width: 100% !important;
}
@media (min-width: 640px) {
    .fi-ta-table td:has(> .fi-ta-actions) {
        width: 1% !important;
        min-width: 8rem !important;
        white-space: nowrap !important;
    }
    .fi-ta-table td > .fi-ta-actions {
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        gap: 0.75rem !important;
        white-space: nowrap !important;
    }
}
</style>
