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
        Schema::create('fabric_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aci_id');
            $table->string('model');
            $table->string('role');
            $table->string('serial')->unique();
            $table->string('description');
            $table->string('dn');
            $table->timestamps();
            $table->unsignedBigInteger('rack_id')->nullable();
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('set null');
        });
        Schema::create('interfaces', function (Blueprint $table) {
            $table->id();
            $table->string('aci_id');
            $table->string('state');
            $table->string('dn');
            $table->timestamps();
            $table->unsignedBigInteger('fabric_node_id');
            $table->foreign('fabric_node_id')->references('id')->on('fabric_nodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fabric_nodes');
    }
};
