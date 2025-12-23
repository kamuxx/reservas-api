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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name',255);
            $table->string('email',255)->unique();
            $table->string('phone',20);
            $table->string('password');
            $table->string('role_id',36)->unique();
            $table->string('status_id',36)->unique();
            $table->foreign('role_id')->references('uuid')->on('roles')->onDelete('cascade')->comment('UUID DE LA TABLA ROLES');
            $table->foreign('status_id')->references('uuid')->on('status')->onDelete('cascade')->comment('UUID DE LA TABLA STATUS');            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->default(null);
        });        

        Schema::create('user_activation_tokens', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->comment('mismo uuid de la tabla usuarios');
            $table->string('email',255)->unique();
            $table->string('token',255)->unique();
            $table->smallInteger('activation_code')->nullable()->default(6);
            $table->timestamp('expiread_at')->nullable()->default(null);
            $table->timestamp('validated_at')->nullable()->default(null);
            $table->timestamp('used_at')->nullable()->default(null);            
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->comment('mismo uuid de la tabla usuarios');
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->smallInteger('activation_code')->nullable()->default(6);
            $table->timestamp('expired_at')->nullable()->default(null);
            $table->timestamp('validated_at')->nullable()->default(null);
            $table->timestamp('used_at')->nullable()->default(null);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->comments('id del usuario');
            $table->uuid('uuid')->unique()->comments('uuid del usuario');
            $table->foreignId('user_id')->nullable()->index()->comment('uuid del usuario');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');        
        Schema::dropIfExists('user_activation_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
