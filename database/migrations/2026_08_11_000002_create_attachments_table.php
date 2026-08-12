<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->string('attachable_type');
            $t->unsignedBigInteger('attachable_id');
            $t->string('disk')->default('public');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['attachable_type', 'attachable_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
