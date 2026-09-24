@auth
    @if (! auth()->user()?->isPhoto())
        <livewire:notice-popup />
    @endif
@endauth
