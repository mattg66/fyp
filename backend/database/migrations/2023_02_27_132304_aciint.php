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
            $table->string('alloc_mode');
            $table->string('parent_dn');
            $table->boolean('project_pool')->nullable()->unique();
            $table->timestamps();

        });
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('description');
            $table->string('network');
            $table->string('status')->nullable();
            $table->string('subnet_mask');
            $table->string('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('vlans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('vlan_id')->unique();
            $table->timestamps();
            $table->unsignedBigInteger('vlan_pool_id');
            $table->foreign('vlan_pool_id')->references('id')->on('vlan_pools')->onDelete('cascade');
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
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
