<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outgoing-email history. Kept intentionally lean so it does not bloat the DB:
 * only text metadata and the body are stored, attachments are recorded as an
 * id list and a count (never the files themselves).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('subject');
            $table->mediumText('body')->nullable();
            $table->string('context')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->unsignedBigInteger('task_id')->nullable()->index();
            $table->unsignedBigInteger('viewing_id')->nullable()->index();
            $table->string('recipient_role')->nullable();
            $table->json('attachment_ids')->nullable();
            $table->unsignedInteger('attachment_count')->default(0);
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
