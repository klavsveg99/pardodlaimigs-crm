<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Filament\Admin\Actions\LawyerDocumentAction;
use App\Models\CrmProperty;
use App\Services\Lawyer\LawyerDocumentService;

/**
 * "Jurista dokuments" 2. soļa priekšskatījums.
 *
 * Wizard solis renderējas vienreiz (pirms 1. soļa aizpildīšanas), tāpēc
 * e-pasts tiek ģenerēts pēc pieprasījuma no pašlaik montētās darbības datiem.
 */
trait HandlesLawyerEmail
{
    /**
     * @return array{to: string, jurist: string, subject: string, html: string}
     */
    public function previewLawyerEmail(?string $extraInfo = null): array
    {
        $action = $this->getMountedAction();
        $record = $action?->getRecord();

        $property = $record instanceof CrmProperty
            ? $record
            : ($record ? CrmProperty::query()->find($record->getKey()) : null);

        if (! $action || $action->getName() !== 'lawyer_document' || ! $property) {
            return ['to' => '', 'jurist' => '', 'subject' => '', 'html' => ''];
        }

        $data = $action->getData();
        $type = (string) ($data['document_type'] ?? '');
        $service = app(LawyerDocumentService::class);

        $preview = $service->preview([
            'document_type' => $type,
            'jurist_id' => $data['jurist_id'] ?? null,
            'legal' => LawyerDocumentAction::sanitizeLegalData($type, is_array($data['legal'] ?? null) ? $data['legal'] : []),
            'seller' => is_array($data['seller'] ?? null) ? $data['seller'] : [],
            'buyer' => is_array($data['buyer'] ?? null) ? $data['buyer'] : [],
            'property' => is_array($data['property'] ?? null) ? $data['property'] : [],
        ], $property);

        // Papildu informācija tiek pievienota priekšskatījumam tāpat kā nosūtot.
        $extraInfo = trim((string) ($extraInfo ?? ''));
        if ($extraInfo !== '') {
            $preview['html'] = \App\Services\Mail\EmailSender::renderHtml(
                $preview['body'].'<p style="margin:18px 0 0;white-space:pre-line;">'.e($extraInfo).'</p>',
                \App\Services\Mail\EmailSender::defaultSignature(),
            );
        }

        // Atlasīto pielikumu nosaukumi modāļa sarakstam.
        $ids = is_array($data['attachment_ids'] ?? null) ? $data['attachment_ids'] : [];
        $preview['attachments'] = $service->selectedAttachments($property, $ids)
            ->pluck('original_name')
            ->all();

        return $preview;
    }
}
