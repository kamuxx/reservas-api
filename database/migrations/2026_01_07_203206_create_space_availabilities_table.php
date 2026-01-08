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
        Schema::create('space_availabilities', function (Blueprint $table) {
            $table->uuid('space_id');
            $table->date('available_date');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
            $table->index(['space_id', 'available_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_availabilities');
    }
};
