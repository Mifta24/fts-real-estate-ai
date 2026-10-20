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
        Schema::create('viewing_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->string('contact_type', 20)->nullable(); // whatsapp | phone | email
            $table->date('viewing_date');
            $table->string('viewing_time', 5);
            $table->unsignedTinyInteger('party_size')->default(1);
            $table->string('payment_plan', 20)->default('undecided'); // cash | mortgage | undecided
            $table->string('status')->default('pending'); // pending | confirmed | completed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'viewing_date', 'viewing_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viewing_appointments');
    }
};
