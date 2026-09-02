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
