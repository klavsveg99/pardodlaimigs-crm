<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-8px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .fi-main-content {
        animation: fadeInUp 0.25s ease-out;
    }

    .fi-section {
        animation: fadeIn 0.3s ease-out;
    }

    .fi-section:nth-child(1) { animation-delay: 0ms; }
    .fi-section:nth-child(2) { animation-delay: 40ms; }
    .fi-section:nth-child(3) { animation-delay: 80ms; }
    .fi-section:nth-child(4) { animation-delay: 120ms; }
    .fi-section:nth-child(5) { animation-delay: 160ms; }
    .fi-section:nth-child(6) { animation-delay: 200ms; }

    .fi-table-actions,
    .fi-ta-actions {
        animation: fadeIn 0.2s ease-out;
    }

    .fi-modal-window {
        animation: scaleIn 0.2s ease-out;
    }

    .fi-sidebar-item {
        transition: background-color 0.15s ease, color 0.15s ease, padding 0.15s ease;
    }

    .fi-table-row {
        transition: background-color 0.12s ease;
    }

    .fi-btn {
        transition: all 0.15s ease;
    }

    .fi-btn:active {
        transform: scale(0.97);
    }

    .fi-badge {
        transition: all 0.2s ease;
    }

    [wire\:loading] {
        opacity: 0.6;
        transition: opacity 0.15s ease;
    }

    [wire\:loading\.delay] {
        opacity: 0.4;
        transition: opacity 0.3s ease;
    }
</style>
