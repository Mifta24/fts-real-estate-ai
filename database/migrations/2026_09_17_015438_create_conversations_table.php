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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->uuid('visitor_token')->unique();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('locale', 2)->default('id');
            $table->string('status')->default('active'); // active | handed_over | closed
            $table->string('current_scene', 20)->default('home');
            $table->foreignId('selected_property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignId('selected_place_id')->nullable()->constrained('agency_knowledge_items')->nullOnDelete();
            $table->json('viewing_state')->nullable();
            $table->text('handover_summary')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
