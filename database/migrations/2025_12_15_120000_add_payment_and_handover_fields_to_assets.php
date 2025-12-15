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
        Schema::table('assets', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'partial', 'completed', 'failed'])
                ->nullable()
                ->after('status');
            $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_status');
            $table->timestamp('payment_completed_at')->nullable()->after('payment_amount');

            $table->enum('handover_status', ['pending', 'completed'])
                ->nullable()
                ->after('payment_completed_at');
            $table->timestamp('handover_date')->nullable()->after('handover_status');
            $table->text('handover_notes')->nullable()->after('handover_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_amount',
                'payment_completed_at',
                'handover_status',
                'handover_date',
                'handover_notes'
            ]);
        });
    }
};
