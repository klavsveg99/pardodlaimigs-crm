<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apskates tagad mērķē TIKAI uz CRM īpašumiem (viewings.property_id →
 * crm_properties.id) — līdz šim tabula rādīja uz properties_cache (WP
 * spoguļtabulu), kas bloķēja apskates izveidi ar CRM īpašumu (integritātes
 * kļūda: viewing_booked aktivitātes FK → crm_properties).
 *
 * SQLite neatļauj nomainīt FK esošajā tabulā, tāpēc tabula tiek pārbūvēta
 * ar jauno FK un dati pārskrējas: vecais property_id (properties_cache.id
 * = WP post ID) tiek pārmapped uz CRM ierakstu (crm_properties.wp_post_id
 * = tas pats WP post ID). Ja CRM īpašums nav atrasts, property_id = NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('viewings') || Schema::hasTable('viewings_rebuilt')) {
            return;
        }

        Schema::create('viewings_rebuilt', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('property_id')->nullable()->constrained('crm_properties')->nullOnDelete();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('agent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->datetime('scheduled_at');
            $t->integer('duration_min')->default(30);
            $t->string('status')->default('scheduled');
            $t->text('notes_md')->nullable();
            $t->timestamps();
        });

        // Remapping: properties_cache.id (vecais property_id) -> CRM id
        // (crm_properties ar to pašu wp_post_id).
        $remap = DB::table('viewings')
            ->join('properties_cache', 'properties_cache.id', '=', 'viewings.property_id')
            ->join('crm_properties', 'crm_properties.wp_post_id', '=', 'properties_cache.id')
            ->whereNotNull('viewings.property_id')
            ->pluck('crm_properties.id', 'viewings.id');

        $columns = ['id', 'property_id', 'client_id', 'agent_user_id', 'scheduled_at', 'duration_min', 'status', 'notes_md', 'created_at', 'updated_at'];

        DB::table('viewings')->orderBy('id')->each(function ($viewing) use ($remap, $columns): void {
            $row = (array) $viewing;
            $new = [];
            foreach ($columns as $column) {
                $new[$column] = $row[$column] ?? null;
            }
            $mapped = $row['property_id'] !== null ? ($remap[$row['id']] ?? null) : null;
            $new['property_id'] = $mapped !== null ? (int) $mapped : null;

            DB::table('viewings_rebuilt')->insert($new);
        });

        Schema::dropIfExists('viewings');
        Schema::rename('viewings_rebuilt', 'viewings');
    }

    public function down(): void
    {
        // Vecie dati netiek atgūsti (properties_cache bija tikai cache).
    }
};
