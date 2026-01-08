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
        Schema::create('space_availability', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('space_id', 36)->index();

            // Fecha y horarios del slot de disponibilidad
            $table->date('available_date')->index();
            $table->time('available_from')->index();
            $table->time('available_to')->index();

            // Estado de disponibilidad
            $table->boolean('is_available')->default(true)->index();

            // Información adicional
            $table->integer('max_capacity')->nullable()->comment('Capacidad máxima para este slot específico (puede diferir de la capacidad del espacio)');
            $table->decimal('slot_price', 10, 2)->nullable()->comment('Precio específico para este slot (si difiere del precio base)');

            // Auditoría
            $table->string('created_by', 36)->index();
            $table->string('updated_by', 36)->nullable()->default(null)->index();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices compuestos para optimizar búsquedas comunes
            $table->index(['space_id', 'available_date', 'is_available'], 'idx_space_date_availability');
            $table->index(['available_date', 'available_from', 'available_to'], 'idx_date_time_range');
            $table->index(['space_id', 'is_available'], 'idx_space_available');

            // Foreign keys
            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
            $table->foreign('created_by')->references('uuid')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('space_availability', function (Blueprint $table) {
            $table->dropForeign(['space_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('space_availability');
    }
};
