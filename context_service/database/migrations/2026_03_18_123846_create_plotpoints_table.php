<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('plotpoints', function (Blueprint $table) {
            $table->id('plotpoint_id');
            $table->unsignedBigInteger('chapter_id');
            $table->integer('point_number');
            $table->text('plot_text');
            $table->text('ai_post')->nullable();
            $table->text('player_post')->nullable();
            $table->text('result')->nullable();
            $table->string('status')->default('inactive');
            $table->timestamps();

            $table->foreign('chapter_id')->references('chapter_id')->on('chapters')->onDelete('cascade');
            $table->unique(['chapter_id', 'point_number']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plotpoints');
    }
};
