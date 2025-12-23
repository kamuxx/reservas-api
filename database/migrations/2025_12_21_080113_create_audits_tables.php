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
        // Tabla de auditoría de inicios de sesión
        Schema::create('login_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('user_uuid', 36)->nullable()->index();
            $table->string('email_attempt', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked']);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['user_uuid', 'status', 'created_at'], 'idx_login_audit_user_status_time');
            $table->index(['email_attempt', 'created_at'], 'idx_login_audit_email_time');
            $table->index(['ip_address', 'created_at'], 'idx_login_audit_ip_time');
        });

        // Tabla de auditoría de entidades
        Schema::create('entity_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('user_uuid', 36)->nullable()->index();
            $table->string('entity_name', 100);
            $table->string('entity_id', 36);
            $table->enum('operation', ['create', 'update', 'delete', 'restore']);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['entity_name', 'entity_id', 'created_at'], 'idx_entity_audit_entity_time');
            $table->index(['user_uuid', 'created_at'], 'idx_entity_audit_user_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_audit_trails');
        Schema::dropIfExists('login_audit_trails');
    }
};
