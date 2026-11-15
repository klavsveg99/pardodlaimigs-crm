<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\GeneratesAiDescription;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Models\PropertySearch;
use App\Models\ScrapedListing;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateCrmProperty extends CreateRecord
{
    use GeneratesAiDescription;
    use SyncsAttachments;

    /** Izvēlētais pārdevējs, kas tiek piesaistīts pēc ieraksta izveides. */
    protected ?int $sellerClientId = null;

    /** Atrastais objekts, no kura aizpildīta forma (?scraped_listing=). */
    protected ?ScrapedListing $scrapedListing = null;

    /**
     * Property attachments live in two collections: gallery images and
     * separate "Pielikumi" documents.
     */
    protected function attachmentCollections(): array
    {
        return ['attachments' => 'gallery', 'attachments_documents' => 'documents'];
    }

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Jauns īpašums';
    }

    /**
     * Pre-fill the form when it was opened from a found (scraped) listing.
     * Only data that the portals actually provide is copied; seller, start
     * date and partnership terms stay for the agent to choose.
     */
    protected function afterFill(): void
    {
        $this->scrapedListing = $this->resolveScrapedListing();

        if ($this->scrapedListing === null) {
            return;
        }

        $listing = $this->scrapedListing;
        $title = trim((string) $listing->title);
        $city = trim((string) $listing->location);
        $address = trim((string) $listing->address);

        $this->form->fill([
            'title' => $title !== '' ? Str::limit($title, 255, '') : null,
            'category' => match ($listing->search?->category) {
                'land' => 'Zeme',
                'houses' => 'Māja',
                'apartments' => 'Dzīvoklis',
                'commercial' => 'Komerciāls',
                default => null,
            },
            // Imported ads start as drafts: nothing goes live on the site
            // until the agent reviews the record.
            'status' => 'draft',
            'lead_source' => 'external',
            'lead_source_name' => PropertySearch::PORTALS[$listing->portal] ?? $listing->portal,
            'price_eur' => $listing->price_eur !== null ? (float) $listing->price_eur : null,
            'size_m2' => $listing->area_m2,
            'land_m2' => $listing->land_m2,
            'beds' => $this->scrapedRooms($title),
            'city' => $city !== '' ? $city : null,
            'address' => $address !== '' && $address !== $city ? $address : null,
            'owner_user_id' => $listing->user_id,
        ]);
    }

    private function resolveScrapedListing(): ?ScrapedListing
    {
        $id = (int) request()->query('scraped_listing', 0);

        if ($id <= 0) {
            return null;
        }

        return ScrapedListing::query()
            ->with('search')
            ->whereNull('duplicate_of_id')
            ->when(
                ! auth()->user()?->can('manage'),
                fn ($query) => $query->where('user_id', auth()->id()),
            )
            ->find($id);
    }

    /** Ad titles state the room count ("2 istabu dzīvokli"). */
    private function scrapedRooms(string $title): ?int
    {
        if (preg_match('/(\d{1,2})\s*istabu/iu', $title, $match) !== 1) {
            return null;
        }

        $rooms = (int) $match[1];

        return $rooms >= 1 && $rooms <= 20 ? $rooms : null;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pārdevējs nav kolonna - tiek piesaistīts pēc ieraksta izveides.
        if (filled($data['seller_client_id'] ?? null)) {
            $this->sellerClientId = (int) $data['seller_client_id'];
        }
        unset($data['seller_client_id']);

        // Agents only see their own properties, so default the owner to the
        // creator when none is chosen. Fotogrāfam aģents netiek piešķirts -
        // viņš tikai izveido īpašumu un pievieno bildes, atbildīgo aģentu
        // vēlāk norāda kāds cits.
        if (blank($data['owner_user_id'] ?? null) && ! auth()->user()?->can('manage') && ! auth()->user()?->isPhoto()) {
            $data['owner_user_id'] = auth()->id();
        }

        $this->captureAttachments($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        if ($this->sellerClientId) {
            $record->clients()->attach($this->sellerClientId, ['relation' => 'seller']);
        }

        // AI piezīmes (modalā textarea) glabājas komponentes stāvoklī, nevis
        // dehidrētājos datos - pieliekam tās pēc ieraksta izveides.
        $aiNotes = $this->data['ai_notes'] ?? null;
        if (is_array($aiNotes)) {
            $aiNotes = array_filter($aiNotes, fn ($v): bool => filled($v));
            $record->update(['ai_notes' => $aiNotes === [] ? null : $aiNotes]);
        }

        // AI rezultāts tika ģenerēts pirms ieraksta izveides (Create lapā
        // ieraksts vēl neeksistē) - saglabājam tiklīdz ieraksts ir.
        if ($this->aiResult !== []) {
            $record->update(['ai_result' => $this->aiResult]);
        }

        return $record;
    }
}
