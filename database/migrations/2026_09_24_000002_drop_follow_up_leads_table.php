<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Līdi tiek pārvaldīti kā klienta statuss, atsevišķā tabula vairs nav. */
    public function up(): void
    {
        Schema::dropIfExists('follow_up_leads');
    }

    public function down(): void
    {
        // Tabula apzināti netiek atjaunota — funkcija ir noņemta.
    }
};
