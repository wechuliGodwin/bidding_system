<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disposal_events', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('closed_at');
            $table->boolean('winners_notified')->default(false)->after('completed_at');
            $table->timestamp('winners_notified_at')->nullable()->after('winners_notified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposal_events', function (Blueprint $table) {
            $table->dropColumn([
                'closed_at',
                'completed_at',
                'winners_notified',
                'winners_notified_at'
            ]);
        });
    }
};
