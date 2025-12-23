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
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('description', 255)->nullable()->default(null);
            $table->integer('capacity');
            $table->string('spaces_type_id', 36)->index();
            $table->string('status_id', 36)->index();
            //$table->integer('location_id')->index();
            $table->string('pricing_rule_id', 36)->index();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 36)->index();
            $table->string('updated_by', 36)->index();

            //foreings
            $table->foreign('status_id')->references('uuid')->on('status')->onDelete('cascade');
            $table->foreign('spaces_type_id')->references('uuid')->on('space_types')->onDelete('cascade');
            $table->foreign('pricing_rule_id')->references('uuid')->on('pricing_rules')->onDelete('cascade');
            $table->foreign('created_by')->references('uuid')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('uuid')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('space_images', function (Blueprint $table) {
            $table->string('space_id')->unique();
            $table->string('image', 1000);
            $table->string('is_main')->default(false);

            //foreings
            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
        });

        Schema::create('space_features', function (Blueprint $table) {
            $table->string('space_id', 36)->index();
            $table->string('feature_id', 36)->index();

            //foreings
            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
            $table->foreign('feature_id')->references('uuid')->on('features')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // Primero eliminar las tablas hijas
        Schema::dropIfExists('space_features');
        Schema::dropIfExists('space_images');

        // Luego eliminar las claves foráneas de spaces
        Schema::table('spaces', function (Blueprint $table) {
            // Use Laravel's naming convention: {table}_{column}_foreign
            $table->dropForeign(['status_id']);
            $table->dropForeign(['spaces_type_id']);
            $table->dropForeign(['pricing_rule_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
        // Finalmente, eliminar la tabla spaces
        Schema::dropIfExists('spaces');
    }
};
