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
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reserved_by',36)->index();
            $table->string('space_id',36)->index();
            $table->string('status_id',36)->index()->comment('los estados posibles para la reserva son: agendada, confirmada, cancelada, finalizada');
            $table->string('event_name',500)->index();
            $table->text('event_description')->nullable()->default(null);
            $table->date('event_date')->index();
            $table->time('start_time')->index();
            $table->time('end_time')->index();
            $table->decimal('event_price',16,2)->default(0);
            $table->string('pricing_rule_id',36)->index();
            $table->text('cancellation_reason')->nullable()->default(null);
            $table->string('cancellation_by',36)->nullable()->default(null);
            $table->timestamps();
            $table->softDeletes();

            //foreings keys
            $table->foreign('reserved_by')->references('uuid')->on('users')->onDelete('cascade');
            $table->foreign('space_id')->references('uuid')->on('spaces')->onDelete('cascade');
            $table->foreign('status_id')->references('uuid')->on('status')->onDelete('cascade');
            $table->foreign('pricing_rule_id')->references('uuid')->on('pricing_rules')->onDelete('cascade');            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation',function(Blueprint $table){
            $table->dropForeign(['reserved_by']);
            $table->dropForeign(['space_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['pricing_rule_id']);
        });
        Schema::dropIfExists('reservation');
    }
};
