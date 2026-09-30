<?php

declare(strict_types=1);

namespace App\Services\Lawyer;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Models\LawyerRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Mail\EmailSender;
use Illuminate\Support\Carbon;

/**
 * Juristam nosūtāmo dokumentu pieprasījumu loģika: lauku definīcijas
 * (config/crm.php), e-pasta saturs un nosūtīšanas vēsture.
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

    public function send(
        CrmProperty $property,
        Izpilditajs $jurist,
        string $type,
        array $legal,
        ?User $user = null,
    ): LawyerRequest {
        $property->load('clients', 'attachments');

        $documentLabel = $this->documentLabel($type);
        $address = $this->propertyAddress($property);
        $subject = 'CRM – '.$documentLabel.' – '.($address ?: ($property->title ?? ''));

        $body = $this->buildBody($property, $type, $legal);
        $attachments = $property->attachments
            ->where('collection', 'documents')
            ->values();

        app(EmailSender::class)->send(
            (string) $jurist->email,
            $subject,
            $body,
            $attachments,
            $user?->name,
        );

        $request = LawyerRequest::create([
            'crm_property_id' => $property->id,
            'izpilditajs_id' => $jurist->id,
            'document_type' => $type,
            'recipient_email' => (string) $jurist->email,
            'subject' => $subject,
            'payload' => [
                'legal' => $legal,
                'seller_id' => $property->clients->firstWhere('pivot.relation', 'seller')?->id,
                'buyer_id' => $property->clients->firstWhere('pivot.relation', 'buyer')?->id,
            ],
            'status' => 'sent',
            'sent_by_user_id' => $user?->id,
            'sent_at' => now(),
        ]);

        app(AuditLogger::class)->activity('lawyer_request_sent', [
            'property_id' => $property->id,
            'document_type' => $type,
            'to' => (string) $jurist->email,
            'subject' => $subject,
        ]);

        return $request;
    }

    public function propertyAddress(CrmProperty $property): string
    {
        return trim(implode(', ', array_filter([
            (string) $property->address,
            (string) $property->city,
        ])));
    }

    public function buildBody(CrmProperty $property, string $type, array $legal): string
    {
        $documentLabel = $this->documentLabel($type);
        $seller = $property->clients->first(fn (Client $c): bool => $c->pivot->relation === 'seller');
        $buyer = $property->clients->first(fn (Client $c): bool => $c->pivot->relation === 'buyer');

        $html = '<h2 style="margin:0 0 12px;font-size:18px;color:#285854;">'
            .e($documentLabel).'</h2>';

        $html .= $this->renderSection('Darījuma / īpašuma informācija', array_filter([
            'Īpašuma adrese' => $this->propertyAddress($property),
            'Kadastra numurs' => (string) $property->kadastra_nr,
            'Pilsēta / novads' => (string) $property->city,
            'Pasta indekss' => (string) $property->zip,
            'Mājaslapa' => $property->title,
        ], fn ($v): bool => filled($v)));

        if ($seller) {
            $html .= $this->renderClient('Pārdevējs', $seller);
        }

        if ($buyer) {
            $html .= $this->renderClient('Pircējs', $buyer);
        }

        $html .= $this->renderSection('Finanšu informācija', array_filter([
            'Pārdošanas cena' => $property->price_display,
            'Gala cena' => $property->final_price_eur ? number_format((float) $property->final_price_eur, 2, ',', ' ').' €' : null,
            'Komisija' => $property->commission_eur ? number_format((float) $property->commission_eur, 2, ',', ' ').' €' : null,
            'Pārdots' => $property->sold_at?->format('d.m.Y'),
        ], fn ($v): bool => filled($v)));

        $html .= $this->renderLegal($legal);

        $crmUrl = $this->crmUrl($property);
        $html .= '<p style="margin:18px 0 0;font-size:13px;color:#6b7280;">'
            .'Saite uz darījumu CRM: <a href="'.e($crmUrl).'" style="color:#285854;">'.e($crmUrl).'</a></p>';

        return $html;
    }

    public function crmUrl(CrmProperty $property): string
    {
        try {
            return \App\Filament\Admin\Resources\CrmPropertyResource::getUrl('view', ['record' => $property]);
        } catch (\Throwable) {
            return rtrim((string) config('app.url'), '/').'/properties/'.($property->slug ?: $property->getKey());
        }
    }

    protected function renderClient(string $heading, Client $client): string
    {
        return $this->renderSection($heading, array_filter([
            'Vārds, uzvārds' => (string) $client->name,
            'Personas kods' => (string) $client->personas_kods,
            'Dzīvesvietas adrese' => (string) $client->address,
            'Bankas konta numurs' => (string) $client->bank_account,
            'E-pasts' => (string) $client->email,
            'Tālrunis' => (string) $client->phone,
        ], fn ($v): bool => filled($v)));
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
                .'<td style="padding:3px 10px 3px 0;color:#6b7280;white-space:nowrap;vertical-align:top;">'.e($label).'</td>'
                .'<td style="padding:3px 0;color:#111827;font-weight:600;">'.e((string) $value).'</td>'
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
