<style>
    @keyframes pdc-fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pdc-fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .fi-page-content {
        animation: pdc-fadeInUp 0.25s ease-out !important;
    }

    .fi-section {
        animation: pdc-fadeIn 0.3s ease-out both !important;
    }
    .fi-section:nth-child(1) { animation-delay: 0ms !important; }
    .fi-section:nth-child(2) { animation-delay: 40ms !important; }
    .fi-section:nth-child(3) { animation-delay: 80ms !important; }
    .fi-section:nth-child(4) { animation-delay: 120ms !important; }
    .fi-section:nth-child(5) { animation-delay: 160ms !important; }
    .fi-section:nth-child(6) { animation-delay: 200ms !important; }

    .fi-sidebar-item {
        transition: background-color 0.15s ease, color 0.15s ease !important;
    }

    .fi-btn {
        transition: all 0.15s ease !important;
    }
    .fi-btn:active {
        transform: scale(0.97) !important;
    }

    [wire\:loading] {
        opacity: 0.6 !important;
        transition: opacity 0.15s ease !important;
    }
</style>
