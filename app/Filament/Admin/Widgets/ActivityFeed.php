<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Task;
use App\Models\Viewing;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActivityFeed extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public const ACTION_LABELS = [
        'create' => 'Izveidots',
        'update' => 'Atjaunināts',
        'delete' => 'Dzēsts',
        'export' => 'Eksportēts',
        'erase' => 'Dzēsti dati',
        'export_request' => 'Eksporta pieprasījums',
        'erase_request' => 'Dzēšanas pieprasījums',
        'wpform_sync' => 'WP formu sinhronizācija',
        'login' => 'Pieteicies',
    ];

    public const ENTITY_LABELS = [
        'task' => 'Uzdevums',
        'viewing' => 'Apskate',
        'client' => 'Klients',
        'crm_property' => 'Īpašums',
        'property' => 'Īpašums',
        'wpform_entry' => 'WP forma',
        'user' => 'Aģents',
    ];

    public const FIELD_LABELS = [
        'title' => 'Nosaukums',
        'body' => 'Apraksts',
        'notes_md' => 'Piezīmes',
        'name' => 'Nosaukums',
        'phone' => 'Tālrunis',
        'email' => 'E-pasts',
        'source' => 'Avots',
        'source_other' => 'Avota precizējums',
        'status' => 'Statuss',
        'stage' => 'Stadija',
        'due_at' => 'Līdz',
        'completed_at' => 'Pabeigts',
        'scheduled_at' => 'Kad',
        'duration_min' => 'Ilgums (min)',
        'assigned_user_id' => 'Aģents',
        'agent_user_id' => 'Aģents',
        'owner_user_id' => 'Aģents',
        'izpilditajs_id' => 'Izpildītājs',
        'client_id' => 'Klients',
        'property_id' => 'Īpašums',
        'crm_property_id' => 'Īpašums',
        'price_eur' => 'Cena (€)',
        'final_price_eur' => 'Gala cena (€)',
        'commission_eur' => 'Komisija (€)',
        'category' => 'Kategorija',
        'city' => 'Pilsēta',
        'address' => 'Adrese',
        'kadastra_nr' => 'Kadastra nr.',
        'lead_source' => 'Pieteikuma avots',
        'lead_owner' => 'Atbildīgais aģents',
        'beds' => 'Istabas',
        'baths' => 'Vannas istabas',
        'size_m2' => 'Platība (m²)',
        'land_m2' => 'Zeme (m²)',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Uzņēmuma aktivitātes')
            ->description('Jaunākie notikumi sistēmā — kas, ko un kad izdarīja')
            ->query(fn () => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Laiks')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Darbība')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $this->actionBadge($record)),

                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Aģents')
                    ->sortable()
                    ->placeholder('Sistēma'),

                Tables\Columns\TextColumn::make('detail')
                    ->label('Detalizācija')
                    ->wrap()
                    ->limit(140)
                    ->tooltip(fn ($record) => $this->describe($record, false))
                    ->getStateUsing(fn ($record) => $this->describe($record, true)),
            ])
            ->actions([
                Actions\Action::make('atvert')
                    ->label('Atvērt')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn ($record) => $this->recordUrl($record) !== null)
                    ->url(fn ($record) => $this->recordUrl($record))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Vēl nav aktivitāšu')
            ->emptyStateDescription('Kad tiks izveidoti uzdevumi, apskates, klienti vai īpašumi, tie parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function getQuery(): Builder
    {
        return AuditLog::query()
            ->with(['actor'])
            // WPForms background sync runs constantly — noise, not company activity.
            ->where('action', '!=', 'wpform_sync')
            // Darījumi no longer exist in the system — hide their legacy rows.
            ->where('entity', '!=', 'deal')
            ->orderByDesc('created_at')
            ->limit(50);
    }

    protected function actionBadge(AuditLog $log): string
    {
        $entity = self::ENTITY_LABELS[$log->entity] ?? $log->entity;

        return $entity.' · '.$this->actionLabel($log);
    }

    /**
     * Real-estate domain language per sector instead of generic CRUD verbs.
     */
    protected function actionLabel(AuditLog $log): string
    {
        $before = is_array($log->before) ? $log->before : [];
        $after = is_array($log->after) ? $log->after : [];

        if ($log->entity === 'viewing') {
            if ($log->action === 'create') {
                return 'Pieteikta';
            }
            if ($log->action === 'delete') {
                return 'Dzēsta';
            }
            $st = $after['status'] ?? null;
            if ($log->action === 'update' && is_string($st) && ($before['status'] ?? null) !== $st) {
                return match ($st) {
                    'done' => 'Notikusi',
                    'cancelled' => 'Atcelta',
                    'no_show' => 'Neatnāca',
                    'scheduled' => 'Pārplānota',
                    default => 'Atjaunināta',
                };
            }

            return 'Atjaunināta';
        }

        if ($log->entity === 'task') {
            if ($log->action === 'create') {
                return 'Izveidots';
            }
            if ($log->action === 'delete') {
                return 'Dzēsts';
            }
            if ($log->action === 'update' && ! empty($after['completed_at']) && empty($before['completed_at'])) {
                return 'Pabeigts';
            }

            return 'Atjaunināts';
        }

        if ($log->entity === 'crm_property' || $log->entity === 'property') {
            if ($log->action === 'create') {
                return 'Pievienots';
            }
            if ($log->action === 'delete') {
                return 'Dzēsts';
            }
            $st = $after['status'] ?? null;
            if ($log->action === 'update' && is_string($st) && ($before['status'] ?? null) !== $st) {
                return CrmProperty::STATUSES[$st] ?? 'Atjaunināts';
            }

            return 'Atjaunināts';
        }

        if ($log->entity === 'client') {
            if ($log->action === 'create') {
                return 'Jauns';
            }
            if ($log->action === 'delete') {
                return 'Dzēsts';
            }

            return 'Atjaunināts';
        }

        return self::ACTION_LABELS[$log->action] ?? $log->action;
    }

    protected function describe(AuditLog $log, bool $short): string
    {
        $before = is_array($log->before) ? $log->before : [];
        $after = is_array($log->after) ? $log->after : [];
        $parts = [];

        $title = $this->entityTitle($log, $before, $after);
        if ($title !== '') {
            $parts[] = $title;
        }

        if ($log->action === 'create') {
            $extra = $this->createExtra($log, $after);
            if ($extra !== '') {
                $parts[] = $extra;
            }
        } elseif ($log->action === 'update') {
            $diff = $this->diffText($before, $after, $short ? 2 : 12);
            if ($diff !== '') {
                $parts[] = $diff;
            }
        } elseif ($log->action === 'delete') {
            $parts[] = 'Ieraksts dzēsts';
        } elseif ($log->action === 'wpform_sync') {
            $count = $after['count'] ?? 0;
            $parts[] = 'Sinhronizētas '.($this->textValue($count) ?? '0').' formas';
        } elseif (in_array($log->action, ['export', 'erase', 'export_request', 'erase_request'], true)) {
            $email = $this->textValue($after['email'] ?? $before['email'] ?? null);
            if ($email) {
                $parts[] = $email;
            }
        }

        $text = implode(' · ', array_filter($parts));

        if ($short && mb_strlen($text) > 140) {
            return mb_substr($text, 0, 137).'…';
        }

        return $text !== '' ? $text : ($log->entity.' #'.($log->entity_id ?? '—'));
    }

    protected function entityTitle(AuditLog $log, array $before, array $after): string
    {
        $data = $after + $before;
        $label = self::ENTITY_LABELS[$log->entity] ?? $log->entity;
        $id = $log->entity_id ? ' #'.$log->entity_id : '';

        $name = $data['title'] ?? $data['name'] ?? null;
        if (is_string($name) && trim($name) !== '') {
            $name = trim($name);
            if (mb_strlen($name) > 60) {
                $name = mb_substr($name, 0, 57).'…';
            }

            return $label.$id.': “'.$name.'”';
        }

        // Viewing without title — show property/client context
        if ($log->entity === 'viewing') {
            return $label.$id.$this->viewingContext($data);
        }

        return $label.$id;
    }

    protected function viewingContext(array $data): string
    {
        $bits = [];
        if (! empty($data['scheduled_at'])) {
            try {
                $bits[] = \Carbon\Carbon::parse($data['scheduled_at'])->format('d.m.Y H:i');
            } catch (\Throwable) {
            }
        }

        return $bits ? ': '.implode(', ', $bits) : '';
    }

    protected function createExtra(AuditLog $log, array $after): string
    {
        $bits = [];

        if (isset($after['client_id']) && is_numeric($after['client_id'])) {
            $client = Client::find((int) $after['client_id']);
            if ($client) {
                $bits[] = 'Klients: '.$client->name;
            }
        }

        if (isset($after['property_id']) && is_numeric($after['property_id'])) {
            $prop = CrmProperty::find((int) $after['property_id']);
            if ($prop) {
                $bits[] = 'Īpašums: '.$prop->title;
            }
        }

        foreach (['due_at', 'scheduled_at'] as $field) {
            if (! empty($after[$field])) {
                try {
                    $bits[] = (self::FIELD_LABELS[$field] ?? $field).': '.\Carbon\Carbon::parse($after[$field])->format('d.m.Y H:i');
                } catch (\Throwable) {
                }
            }
        }

        foreach (['final_price_eur', 'price_eur'] as $field) {
            if (isset($after[$field]) && (float) $after[$field] > 0) {
                $bits[] = 'Cena: '.number_format((float) $after[$field], 0, ',', ' ').' €';
                break;
            }
        }

        if (! empty($after['city']) && is_string($after['city'])) {
            $bits[] = $after['city'];
        }

        if ($log->entity === 'client') {
            $phone = $this->textValue($after['phone'] ?? null);
            if ($phone) {
                $bits[] = $phone;
            }
            $source = $this->textValue($after['source'] ?? null);
            if ($source) {
                $bits[] = 'Avots: '.$source;
            }
        }

        if (isset($after['status']) && is_string($after['status'])) {
            $status = $after['status'];
            if ($log->entity === 'crm_property' || $log->entity === 'property') {
                $status = CrmProperty::STATUSES[$status] ?? $status;
            }
            $bits[] = 'Statuss: '.$status;
        }

        return implode(' · ', $bits);
    }

    protected function diffText(array $before, array $after, int $max): string
    {
        $skip = ['updated_at', 'created_at', 'attachment_original_names', 'attachments'];
        $changes = [];

        foreach ($after as $key => $new) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            $old = $before[$key] ?? null;
            if ($this->valuesEqual($old, $new)) {
                continue;
            }
            $label = self::FIELD_LABELS[$key] ?? $key;
            $changes[] = $label.': '.$this->valueText($old).' → '.$this->valueText($new);
            if (count($changes) >= $max) {
                break;
            }
        }

        $total = $this->countChanges($before, $after, $skip);
        $text = implode('; ', $changes);
        if ($total > count($changes)) {
            $text .= ($text !== '' ? '; ' : '').'un vēl '.($total - count($changes)).'…';
        }

        return $text !== '' ? 'Mainīts — '.$text : '';
    }

    protected function countChanges(array $before, array $after, array $skip): int
    {
        $n = 0;
        foreach ($after as $key => $new) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            if (! $this->valuesEqual($before[$key] ?? null, $new)) {
                $n++;
            }
        }

        return $n;
    }

    protected function valuesEqual(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if ($a === null && $b === '') {
            return true;
        }
        if ($b === null && $a === '') {
            return true;
        }
        // Audit snapshots may hold arrays (ai_notes, image_urls, ...) —
        // comparing them as strings crashes ("Array to string conversion").
        if (is_array($a) || is_array($b)) {
            return $a == $b;
        }
        if (is_object($a) || is_object($b)) {
            return json_encode($a) === json_encode($b);
        }

        return (string) $a === (string) $b;
    }

    /**
     * Audit snapshots may hold arrays/objects — only scalars render as text.
     */
    protected function textValue(mixed $v): ?string
    {
        if (is_string($v) || is_int($v) || is_float($v)) {
            return (string) $v;
        }

        return null;
    }

    protected function valueText(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        if (is_bool($v)) {
            return $v ? 'jā' : 'nē';
        }
        if (is_array($v)) {
            return count($v).' vien.';
        }
        // Audit snapshots sometimes hold double-encoded JSON strings
        // (model getChanges() raw attributes, e.g. ai_notes) — render
        // them as item counts instead of dumping raw JSON.
        if (is_string($v) && isset($v[0]) && ($v[0] === '{' || $v[0] === '[')) {
            $decoded = json_decode($v, true);
            if (is_array($decoded)) {
                return count($decoded).' vien.';
            }
        }
        $s = (string) $v;
        // Datetime strings → short LV format
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            try {
                return \Carbon\Carbon::parse($s)->format('d.m.Y H:i');
            } catch (\Throwable) {
            }
        }
        if (mb_strlen($s) > 40) {
            return mb_substr($s, 0, 37).'…';
        }

        return $s;
    }

    protected function recordUrl(AuditLog $log): ?string
    {
        if (! $log->entity_id) {
            return null;
        }

        try {
            return match ($log->entity) {
                'task' => Task::find($log->entity_id)
                    ? route('filament.admin.resources.tasks.edit', $log->entity_id)
                    : null,
                'viewing' => Viewing::find($log->entity_id)
                    ? route('filament.admin.resources.viewings.edit', $log->entity_id)
                    : null,
                'client' => Client::find($log->entity_id)
                    ? route('filament.admin.resources.clients.view', $log->entity_id)
                    : null,
                'crm_property', 'property' => CrmProperty::find($log->entity_id)
                    ? route('filament.admin.resources.crm-properties.view', $log->entity_id)
                    : null,
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
