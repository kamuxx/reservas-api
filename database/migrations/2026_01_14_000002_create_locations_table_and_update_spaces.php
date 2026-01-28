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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('city', 100);
            $table->string('country', 100);
            $table->timestamps();
        });

        Schema::table('spaces', function (Blueprint $table) {
            $table->string('location_id', 36)->nullable()->after('capacity')->index();
            
            // We use string FKs because migration 2025_12_21_070424_create_spaces_table defined referencing columns as string(36)
            // matching the referenced table's uuid column.
            // Assumption: locations.uuid is the referenced key, consistent with other tables like space_types.
            $table->foreign('location_id')->references('uuid')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::dropIfExists('locations');
    }
};
