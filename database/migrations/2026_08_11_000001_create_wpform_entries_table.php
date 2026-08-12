<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wpform_entries', function (Blueprint $t) {
            $t->id();
            $t->string('external_id')->unique(); // == "form_id:entry_id" from WPForms
            $t->unsignedBigInteger('entry_id')->index();
            $t->unsignedBigInteger('form_id')->index();
            $t->string('form_name', 200)->nullable();
            $t->string('status', 60)->nullable()->index();
            $t->boolean('viewed')->default(false);
            $t->boolean('starred')->default(false);
            $t->string('ip_address', 45)->nullable();
            $t->json('fields')->nullable();
            $t->timestamp('created_at')->nullable()->index(); // WPForms entry date
            $t->timestamp('updated_at')->nullable();
            $t->index(['form_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wpform_entries');
    }
};
