<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id('chapter_id');
            $table->unsignedBigInteger('plot_id');
            $table->integer('chapter_number');
            $table->text('chapter_text');
            $table->text('chapter_summary')->nullable();
            $table->string('status')->default('inactive');
            $table->timestamps();

            $table->foreign('plot_id')->references('plot_id')->on('global_plots')->onDelete('cascade');
            $table->unique(['plot_id', 'chapter_number']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chapters');
    }
};
