<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();
        });

        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('node_id');
            $table->timestamps();
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('node_id');
            $table->timestamps();
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');
        });
       
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('racks');
        Schema::dropIfExists('labels');
        Schema::dropIfExists('nodes');
        Schema::dropIfExists('tors');
        Schema::dropIfExists('terminal_servers');
    }
};