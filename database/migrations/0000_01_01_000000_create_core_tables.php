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
        Schema::create('status', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name', 255);
            $table->timestamps();
        });

        //Roles de los usuarios, se crea la tabla con uuid para que sea facilmente escalable y conserve seguridad para el jwt
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name', 255)->comment('inicialmente seran: user, admin');
            $table->timestamps();
        });

        Schema::create('space_types', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();

            // Tipo de regla: 'early_bird', 'last_minute', 'seasonal', 'day_of_week', etc.
            $table->enum('rule_type', ['early_bird', 'last_minute', 'seasonal', 'day_of_week', 'custom'])->default('custom');

            // Configuración de la regla
            $table->integer('days_before_min')->nullable()->comment('Días mínimos antes del evento');
            $table->integer('days_before_max')->nullable()->comment('Días máximos antes del evento');
            $table->decimal('price_adjustment', 10, 2)->comment('Ajuste de precio (puede ser positivo o negativo)');
            $table->enum('adjustment_type', ['fixed', 'percentage'])->default('percentage');

            // Aplicación de la regla
            $table->json('applicable_days')->nullable()->comment('Días de la semana [1-7] donde aplica');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Prioridad (para cuando múltiples reglas podrían aplicarse)
            $table->integer('priority')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('space_types');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('features');
    }
};
