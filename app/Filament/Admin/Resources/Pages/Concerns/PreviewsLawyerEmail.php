<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Models\CrmProperty;
use App\Services\Lawyer\LawyerDocumentService;

/**
 * E-pasta priekšskatījums "Jurista dokuments" darbības pēdējā solī.
 *
 * Wizard soļi tiek renderēti visi uzreiz (vēl pirms lietotājs kaut ko
 * ievada), tāpēc 2. solis nevar nolasīt 1. soļa vērtības renderēšanas
 * laikā. Priekšskatījumu tāpēc ģenerē pēc pieprasījuma, kad lietotājs
 * nospiež pogu — tad darbības datos jau ir visas aizpildītās vērtības.
 */
trait PreviewsLawyerEmail
{
    /**
     * @return array{to: string, jurist: string, subject: string, html: string}
     */
    public function previewLawyerEmail(): array
    {
        $action = $this->getMountedAction();

        if (! $action || $action->getName() !== 'lawyer_document') {
            return ['to' => '', 'jurist' => '', 'subject' => '', 'html' => ''];
        }

        $data = $action->getData();

        $property = $action->getRecord() instanceof CrmProperty
            ? $action->getRecord()
            : CrmProperty::query()->find($data['record'] ?? null);

        if (! $property) {
            return ['to' => '', 'jurist' => '', 'subject' => '', 'html' => ''];
        }

        $type = (string) ($data['document_type'] ?? '');

        return app(LawyerDocumentService::class)->preview([
            'document_type' => $type,
            'jurist_id' => $data['jurist_id'] ?? null,
            'legal' => is_array($data['legal'] ?? null) ? $data['legal'] : [],
            'seller' => is_array($data['seller'] ?? null) ? $data['seller'] : [],
            'buyer' => is_array($data['buyer'] ?? null) ? $data['buyer'] : [],
            'property' => is_array($data['property'] ?? null) ? $data['property'] : [],
        ], $property);
    }
}
