<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\KnowledgeCourseAssignment;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Kursa vērtējums pēc visu materiālu pabeigšanas. Saglabā 1-5 zvaigznes
 * kursa piešķīrumā.
 */
class KnowledgeCourseRating extends Component
{
    public int $assignmentId;

    public ?int $rating = null;

    public function mount(int $assignmentId): void
    {
        $this->assignmentId = $assignmentId;
        $this->rating = $this->assignment()->rating;
    }

    public function save(): void
    {
        $assignment = $this->assignment();

        if ($assignment->completed_at === null) {
            return;
        }

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $assignment->update(['rating' => $this->rating]);

        Notification::make()->title('Paldies par vērtējumu')->success()->send();
    }

    public function render()
    {
        return view('livewire.knowledge-course-rating', [
            'assignment' => $this->assignment(),
        ]);
    }

    private function assignment(): KnowledgeCourseAssignment
    {
        return KnowledgeCourseAssignment::query()
            ->whereKey($this->assignmentId)
            ->where('assigned_user_id', auth()->id())
            ->firstOrFail();
    }
}
