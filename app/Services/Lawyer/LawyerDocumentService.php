<?php

declare(strict_types=1);

namespace App\Services\Lawyer;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Models\LawyerRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Mail\EmailSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Juristam nosūtāmo dokumentu pieprasījumu loģika: lauku definīcijas
 * (config/crm.php), e-pasta saturs un nosūtīšanas vēsture.
 *
 * E-pasts tiek būvēts no datu masīva (context), tāpēc to pašu saturu var
 * parādīt priekšskatījumā pirms nosūtīšanas — gan no saglabātajiem datiem,
 * gan no vēl nesaglabātām formas vērtībām.
 */
class LawyerDocumentService
{
    /** @return array<string, array{label: string, required: array<int, string>}> */
    public function documentTypes(): array
    {
        return config('crm.lawyer.documents', []);
    }

    /** @return array<string, string> key => label */
    public function documentOptions(): array
    {
        return array_map(fn (array $doc): string => $doc['label'], $this->documentTypes());
    }

    /** @return array<string, array<string, mixed>> */
    public function fields(): array
    {
        return config('crm.lawyer.fields', []);
    }

    /** @return array<int, string> */
    public function requiredKeys(string $type): array
    {
        return $this->documentTypes()[$type]['required'] ?? [];
    }

    public function documentLabel(string $type): string
    {
        return $this->documentTypes()[$type]['label'] ?? $type;
    }

    /** Juristi — izpildītāji ar kategoriju "Jurists". */
    public function juristOptions(): array
    {
        return Izpilditajs::query()
            ->where('category', 'Jurists')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Izpilditajs $j): array => [
                $j->id => trim($j->name.($j->email ? ' ('.$j->email.')' : '')),
            ])
            ->all();
    }

    public function jurist(mixed $id): ?Izpilditajs
    {
        return Izpilditajs::query()
            ->where('category', 'Jurists')
            ->find($id);
    }

    /**
     * Atlasītie pielikumi pēc to ID (īpašuma dokumenti un klientu faili).
     *
     * @param  array<int|string>  $ids
     * @return Collection<int, Attachment>
     */
    public function selectedAttachments(CrmProperty $property, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $property->loadMissing('attachments', 'clients.attachments');

        $ids = array_map('intval', $ids);

        return $property->attachments
            ->where('collection', 'documents')
            ->concat($property->clients->flatMap(fn (Client $client): array => $client->attachments->all()))
            ->filter(fn (Attachment $attachment): bool => in_array((int) $attachment->id, $ids, true))
            ->values();
    }

    /**
     * Priekšskatījums no formas datiem: kam, temats un e-pasta pamatteksts.
     *
     * Atgriež nesatītu HTML (bez paraksta un kājenes), jo tos pievieno
     * EmailSender::send() nosūtīšanas brīdī.
     *
     * @param  array<string, mixed>  $formData
     * @return array{to: string, jurist: string, subject: string, body: string, attachment_names: array<int, string>}
     */
    public function preview(array $formData, CrmProperty $property): array
    {
        $property->loadMissing('clients', 'attachments');

        $type = (string) ($formData['document_type'] ?? '');
        $legal = is_array($formData['legal'] ?? null) ? $formData['legal'] : [];
        $context = $this->contextFromForm($formData, $property);
        $jurist = $this->jurist($formData['jurist_id'] ?? null);
        $selected = $this->selectedAttachments(
            $property,
            is_array($formData['attachment_ids'] ?? null) ? $formData['attachment_ids'] : [],
        );

        return [
            'to' => (string) ($jurist?->email ?? ''),
            'jurist' => (string) ($jurist?->name ?? ''),
            'subject' => $this->subjectFor($context, $type),
            'body' => $this->buildBody($context, $type, $legal),
            'attachment_names' => $selected->pluck('original_name')->all(),
        ];
    }

    public function send(
        CrmProperty $property,
        Izpilditajs $jurist,
        string $type,
        array $legal,
        ?User $user = null,
        array $attachmentIds = [],
        ?string $to = null,
        ?string $subject = null,
        ?string $body = null,
    ): LawyerRequest {
        $property->load('clients', 'attachments');

        // Ja pārdevējs/pircējs nav piesaistīts kā klients, datus ņemam no
        // manuāli aizpildītā bloka (tāpat kā priekšskatījumā).
        $context = $this->contextFromProperty($property);

        if (empty($context['seller']) && ! empty($legal['seller']) && is_array($legal['seller'])) {
            $context['seller'] = $this->filledContact($legal['seller']);
        }

        if (empty($context['buyer']) && ! empty($legal['buyer']) && is_array($legal['buyer'])) {
            $context['buyer'] = $this->filledContact($legal['buyer']);
        }

        // E-pasta modāļa ļauj saņēmēju, tematu un saturu labot pirms
        // nosūtīšanas; ja tie nav padoti, lieto ģenerētos.
        $recipient = filled($to) ? trim((string) $to) : (string) $jurist->email;
        $subject = filled($subject) ? trim((string) $subject) : $this->subjectFor($context, $type);
        $body = filled($body) ? (string) $body : $this->buildBody($context, $type, $legal);
        $attachments = $this->selectedAttachments($property, $attachmentIds);

        app(EmailSender::class)->send(
            $recipient,
            $subject,
            $body,
            $attachments,
            $user?->name,
            internal: true,
            context: [
                'context' => 'lawyer_request',
                'property_id' => $property->id,
                'attachment_ids' => $attachments->pluck('id')->all(),
            ],
        );

        $request = LawyerRequest::create([
            'crm_property_id' => $property->id,
            'izpilditajs_id' => $jurist->id,
            'document_type' => $type,
            'recipient_email' => $recipient,
            'subject' => $subject,
            'payload' => [
                'legal' => $legal,
                'seller_id' => $property->clients->firstWhere('pivot.relation', 'seller')?->id,
                'buyer_id' => $property->clients->firstWhere('pivot.relation', 'buyer')?->id,
                'attachment_ids' => $attachments->pluck('id')->all(),
            ],
            'status' => 'sent',
            'sent_by_user_id' => $user?->id,
            'sent_at' => now(),
        ]);

        app(AuditLogger::class)->activity('lawyer_request_sent', [
            'property_id' => $property->id,
            'document_type' => $type,
            'to' => $recipient,
            'subject' => $subject,
        ]);

        return $request;
    }

    /**
     * E-pasta saturs no saglabātā īpašuma un piesaistītajiem klientiem.
     *
     * @return array<string, mixed>
     */
    public function contextFromProperty(CrmProperty $property): array
    {
        $property->loadMissing('clients');

        $seller = $property->clients->first(fn (Client $c): bool => $c->pivot->relation === 'seller');
        $buyer = $property->clients->first(fn (Client $c): bool => $c->pivot->relation === 'buyer');

        return [
            'title' => (string) $property->title,
            'address' => (string) $property->address,
            'city' => (string) $property->city,
            'zip' => (string) $property->zip,
            'kadastra_nr' => (string) $property->kadastra_nr,
            'price_display' => (string) $property->price_display,
            'final_price_eur' => $property->final_price_eur,
            'commission_eur' => $property->commission_eur,
            'sold_at' => $property->sold_at,
            'seller' => $seller ? $this->clientFields($seller) : null,
            'buyer' => $buyer ? $this->clientFields($buyer) : null,
        ];
    }

    /**
     * E-pasta saturs no vēl nesaglabātām formas vērtībām (priekšskatījumam).
     *
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    public function contextFromForm(array $formData, CrmProperty $property): array
    {
        $context = $this->contextFromProperty($property);

        $propertyData = is_array($formData['property'] ?? null) ? $formData['property'] : [];
        foreach (['address', 'kadastra_nr', 'city', 'zip'] as $key) {
            if (filled($propertyData[$key] ?? null)) {
                $context[$key] = (string) $propertyData[$key];
            }
        }

        $seller = $this->filledContact(is_array($formData['seller'] ?? null) ? $formData['seller'] : []);
        if ($seller !== []) {
            $context['seller'] = $seller;
        }

        $buyer = $this->filledContact(is_array($formData['buyer'] ?? null) ? $formData['buyer'] : []);
        if ($buyer !== []) {
            $context['buyer'] = $buyer;
        }

        // Pircējs nav aizpildīts formā, bet ir saglabāts pie darījuma datiem.
        $legalBuyer = $formData['legal']['buyer'] ?? null;
        if ($buyer === [] && is_array($legalBuyer) && $legalBuyer !== []) {
            $context['buyer'] = $this->filledContact($legalBuyer);
        }

        return $context;
    }

    /** @return array<string, string> */
    protected function clientFields(Client $client): array
    {
        return array_filter([
            'person_type' => (string) ($client->person_type ?? 'fiziska'),
            'name' => (string) $client->name,
            'personas_kods' => (string) $client->personas_kods,
            'address' => (string) $client->address,
            'bank_account' => (string) $client->bank_account,
            'email' => (string) $client->email,
            'phone' => (string) $client->phone,
        ], fn ($v): bool => filled($v));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function filledContact(array $data): array
    {
        $contact = [];

        foreach (['person_type', 'name', 'personas_kods', 'address', 'bank_account', 'email', 'phone'] as $key) {
            $value = $data[$key] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            if (filled($value)) {
                $contact[$key] = (string) $value;
            }
        }

        return $contact;
    }

    public function subjectFor(array $context, string $type): string
    {
        $address = trim(implode(', ', array_filter([
            (string) ($context['address'] ?? ''),
            (string) ($context['city'] ?? ''),
        ])));

        return $this->documentLabel($type).' - '.($address ?: (string) ($context['title'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function buildBody(array $context, string $type, array $legal): string
    {
        $documentLabel = $this->documentLabel($type);

        $html = '<h2 style="margin:0 0 12px;font-size:18px;color:#285854;">'
            .e($documentLabel).'</h2>';

        $address = trim(implode(', ', array_filter([
            (string) ($context['address'] ?? ''),
            (string) ($context['city'] ?? ''),
        ])));

        $html .= $this->renderSection('Darījuma / īpašuma informācija', array_filter([
            'Īpašuma adrese' => $address,
            'Kadastra numurs' => (string) ($context['kadastra_nr'] ?? ''),
            'Pilsēta / novads' => (string) ($context['city'] ?? ''),
            'Pasta indekss' => (string) ($context['zip'] ?? ''),
            'Mājaslapa' => (string) ($context['title'] ?? ''),
        ], fn ($v): bool => filled($v)));

        if (! empty($context['seller'])) {
            $html .= $this->renderSection('Pārdevējs', $this->contactRows((array) $context['seller']));
        }

        if (! empty($context['buyer'])) {
            $html .= $this->renderSection('Pircējs', $this->contactRows((array) $context['buyer']));
        }

        $html .= $this->renderSection('Finanšu informācija', array_filter([
            'Pārdošanas cena' => $context['price_display'] ?? null,
            'Gala cena' => filled($context['final_price_eur'] ?? null)
                ? number_format((float) $context['final_price_eur'], 2, ',', ' ').' €'
                : null,
            'Komisija' => filled($context['commission_eur'] ?? null)
                ? number_format((float) $context['commission_eur'], 2, ',', ' ').' €'
                : null,
            'Pārdots' => ($context['sold_at'] ?? null)?->format('d.m.Y'),
        ], fn ($v): bool => filled($v)));

        $html .= $this->renderLegal($legal);

        return $html;
    }

    /** Kontaktpersonas rindas (no klienta vai manuāli aizpildītiem datiem). */
    protected function contactRows(array $data): array
    {
        $legal = ($data['person_type'] ?? 'fiziska') === 'juridiska';

        $map = [
            'name' => $legal ? 'Uzņēmuma nosaukums' : 'Vārds, uzvārds',
            'personas_kods' => $legal ? 'Reģistrācijas numurs' : 'Personas kods',
            'address' => $legal ? 'Juridiskā adrese' : 'Dzīvesvietas adrese',
            'bank_account' => 'Bankas konta numurs',
            'email' => 'E-pasts',
            'phone' => 'Tālrunis',
        ];

        $rows = [];

        foreach ($map as $key => $label) {
            if (filled($data[$key] ?? null)) {
                $rows[$label] = (string) $data[$key];
            }
        }

        return $rows;
    }

    /** @param  array<string, string|int|float|null>  $rows */
    protected function renderSection(string $heading, array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $html = '<h3 style="margin:18px 0 6px;font-size:15px;color:#285854;">'.e($heading).'</h3><table style="width:100%;border-collapse:collapse;font-size:14px;">';

        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                .'<td style="width:50%;padding:5px 12px 5px 0;color:#6b7280;vertical-align:top;">'.e($label).'</td>'
                .'<td style="width:50%;padding:5px 0;color:#111827;font-weight:600;">'.e((string) $value).'</td>'
                .'</tr>';
        }

        return $html.'</table>';
    }

    /** Manuāli aizpildītie juridiskie lauki (config kārtībā). */
    protected function renderLegal(array $legal): string
    {
        $rows = [];

        foreach ($this->fields() as $key => $definition) {
            $value = $legal[$key] ?? null;

            if (blank($value) && $value !== 0 && $value !== '0') {
                continue;
            }

            $rows[$definition['label']] = $this->formatValue($definition, $value);
        }

        return $this->renderSection('Juridiskie dati', $rows);
    }

    protected function formatValue(array $definition, mixed $value): string
    {
        return match ($definition['type']) {
            'date' => $this->formatDate($value),
            'money' => number_format((float) $value, 2, ',', ' ').' €',
            'repeater' => $this->formatRepeater($definition, is_array($value) ? $value : []),
            default => (string) $value,
        };
    }

    protected function formatRepeater(array $definition, array $rows): string
    {
        $columns = $definition['columns'] ?? [];
        $lines = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];
            foreach ($columns as $column => $label) {
                $cell = $row[$column] ?? null;
                if (blank($cell) && $cell !== 0 && $cell !== '0') {
                    continue;
                }
                $cells[] = $label.': '.$cell;
            }

            if ($cells !== []) {
                $lines[] = implode(' · ', $cells);
            }
        }

        return $lines !== [] ? implode(' | ', $lines) : '';
    }

    protected function formatDate(mixed $value): string
    {
        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
