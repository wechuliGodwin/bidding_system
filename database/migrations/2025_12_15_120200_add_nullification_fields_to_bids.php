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
        Schema::table('bids', function (Blueprint $table) {
            $table->text('nullification_reason')->nullable()->after('status');
            $table->foreignId('nullified_by')->nullable()->constrained('users')->after('nullification_reason');
            $table->timestamp('nullified_at')->nullable()->after('nullified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropForeign(['nullified_by']);
            $table->dropColumn(['nullification_reason', 'nullified_by', 'nullified_at']);
        });
    }
};
