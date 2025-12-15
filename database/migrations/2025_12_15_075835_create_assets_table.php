<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposal_event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('starting_price', 12, 2);
            $table->decimal('current_highest_bid', 12, 2)->default(0);
            $table->foreignId('winner_bidder_id')
                  ->nullable()
                  ->constrained('bidders')
                  ->nullOnDelete();
            $table->enum('status', ['available', 'sold', 'withdrawn'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
