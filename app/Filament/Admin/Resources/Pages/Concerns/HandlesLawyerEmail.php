<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Filament\Admin\Actions\LawyerDocumentAction;
use App\Models\CrmProperty;
use App\Services\Lawyer\LawyerDocumentService;
use App\Services\Mail\EmailSender;

/**
 * "Jurista dokuments" darbības pēdējā soļa e-pasts.
 *
 * Wizard soļa saturs tiek renderēts vienreiz, tāpēc 2. solis nevar
 * nolasīt 1. soļa vērtības renderēšanas laikā. Tāpēc e-pasta
 * priekšskatījums un nosūtīšana notiek pēc pieprasījuma — tad darbības
 * datos jau ir visas aizpildītās vērtības.
 */
trait HandlesLawyerEmail
{
    /**
     * E-pasta priekšskatījums no pašreizējiem darbības datiem.
     *
     * @return array{to: string, jurist: string, subject: string, html: string}
     */
    public function previewLawyerEmail(?string $editedBody = null): array
    {
        $property = $this->mountedLawyerProperty();

        if (! $property) {
            return ['to' => '', 'jurist' => '', 'subject' => '', 'html' => ''];
        }

        $data = $this->getMountedAction()?->getData() ?? [];
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

        // Ja lietotājs redaktorā ko mainījis, priekšskatījumā rāda tieši to
        // saturu, ko nosūtīs (EmailSender apvij to pašu veidni).
        if (filled($editedBody)) {
            $preview['html'] = EmailSender::renderHtml($editedBody, EmailSender::defaultSignature());
        }

        return $preview;
    }

    /**
     * Nosūta jurista e-pastu ar klienta pusē izvēlētajiem pielikumiem un
     * (iespējams) rediģēto saturu. Pēc nosūtīšanas aizver darbības modāli.
     *
     * @param  array<int, int|string>  $attachmentIds
     */
    public function sendLawyerEmail(array $attachmentIds = [], ?string $emailBody = null): void
    {
        $property = $this->mountedLawyerProperty();

        if (! $property) {
            return;
        }

        $action = $this->getMountedAction();
        $data = $action?->getData() ?? [];
        $data['attachment_ids'] = $attachmentIds;
        $data['email_body'] = $emailBody;

        LawyerDocumentAction::sendFor($property, $data);

        if (method_exists($this, 'unmountAction')) {
            $this->unmountAction();
        }
    }

    /** Īpašums no pašlaik montētās "lawyer_document" darbības. */
    private function mountedLawyerProperty(): ?CrmProperty
    {
        $action = $this->getMountedAction();

        if (! $action || $action->getName() !== 'lawyer_document') {
            return null;
        }

        $record = $action->getRecord();

        if ($record instanceof CrmProperty) {
            return $record;
        }

        return CrmProperty::query()->find($record?->getKey());
    }
}
