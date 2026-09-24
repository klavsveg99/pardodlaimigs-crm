<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Notices\NoticeCenter;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Augšējā labā stūra paziņojumi (līdzīgi Filament saglabāšanas paziņojumiem,
 * bet ar savu noformējumu, vairāku paziņojumu atbalstu un navigāciju).
 * Aizvēršana sinhronizējas ar "Šodien jāizdara" caur notification_dismissals.
 */
class NoticePopup extends Component
{
    #[On('notices-changed')]
    public function noticesChanged(): void
    {
        // Vienkārši izraisa atkārtotu renderēšanu.
    }

    public function dismiss(string $key): void
    {
        $user = auth()->user();

        if ($user) {
            app(NoticeCenter::class)->dismiss($user, $key);
        }

        $this->dispatch('notices-changed');
    }

    public function render()
    {
        $user = auth()->user();

        $notices = ($user && ! $user->isPhoto())
            ? app(NoticeCenter::class)->forUser($user)
            : [];

        return view('livewire.notice-popup', ['notices' => $notices]);
    }
}
