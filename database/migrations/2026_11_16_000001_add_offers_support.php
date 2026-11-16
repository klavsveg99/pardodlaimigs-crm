<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('scraped_listings', 'contact_email')) {
            Schema::table('scraped_listings', function (Blueprint $table): void {
                $table->string('contact_email')->nullable()->after('address');
            });
        }

        if (! Schema::hasColumn('scraped_listings', 'offer_sent_at')) {
            Schema::table('scraped_listings', function (Blueprint $table): void {
                $table->timestamp('offer_sent_at')->nullable()->after('contact_email')->index();
            });
        }

        if (! Schema::hasColumn('email_logs', 'scraped_listing_id')) {
            Schema::table('email_logs', function (Blueprint $table): void {
                $table->unsignedBigInteger('scraped_listing_id')->nullable()->after('client_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('email_logs', 'scraped_listing_id')) {
            Schema::table('email_logs', function (Blueprint $table): void {
                $table->dropColumn('scraped_listing_id');
            });
        }

        if (Schema::hasColumn('scraped_listings', 'offer_sent_at')) {
            Schema::table('scraped_listings', function (Blueprint $table): void {
                $table->dropColumn('offer_sent_at');
            });
        }

        if (Schema::hasColumn('scraped_listings', 'contact_email')) {
            Schema::table('scraped_listings', function (Blueprint $table): void {
                $table->dropColumn('contact_email');
            });
        }
    }
};
