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
        Schema::create('vlan_pools', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('dn');
            $table->unsignedInteger('start');
            $table->unsignedInteger('end');
            $table->boolean('project_pool')->nullable();
            $table->timestamps();

        });
        Schema::create('vlans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('dn');
            $table->unsignedInteger('vlan_id')->unique();
            $table->timestamps();
            $table->unsignedBigInteger('vlan_pool_id');
            $table->foreign('vlan_pool_id')->references('id')->on('vlan_pools')->onDelete('cascade');
        });
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
            $table->unsignedBigInteger('vlan_id');
            $table->foreign('vlan_id')->references('id')->on('vlans')->onDelete('cascade');
        });
        Schema::table('racks', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vlan_pools');
        Schema::dropIfExists('vlans');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('racks');
    }
};
