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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->string('listing_type')->default('sale'); // sale | rent
            $table->string('property_type')->default('house'); // house | townhouse | apartment | shophouse | land
            $table->string('district')->nullable();
            $table->decimal('price', 15, 2);
            $table->unsignedSmallInteger('building_size_sqm')->nullable();
            $table->unsignedSmallInteger('land_size_sqm')->nullable();
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedTinyInteger('bathrooms')->default(0);
            $table->unsignedTinyInteger('carports')->default(0);
            $table->unsignedTinyInteger('floors')->default(1);
            $table->string('certificate')->nullable(); // shm | hgb | strata | girik
            $table->string('furnishing')->nullable(); // unfurnished | semi | full
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->json('amenities')->nullable();
            $table->unsignedSmallInteger('units_available')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['agency_id', 'slug']);
            $table->index(['agency_id', 'listing_type', 'property_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
