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
        Schema::create('space_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('space_id')->index();
            $table->uuid('user_id')->index();
            $table->text('comment');
            $table->integer('rating');
            $table->timestamps();

            // Foreign keys
            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
            $table->foreign('user_id')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_comments');
    }
};
