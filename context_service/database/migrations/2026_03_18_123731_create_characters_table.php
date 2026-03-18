<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id('character_id');
            $table->unsignedBigInteger('plot_id');
            $table->string('name');
            $table->text('attitude_to_player')->default('нейтральное');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->text('memory')->nullable();
            $table->string('role')->default('персонаж');
            $table->timestamps();

            $table->foreign('plot_id')->references('plot_id')->on('global_plots')->onDelete('cascade');
            $table->index('plot_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('characters');
    }
};
