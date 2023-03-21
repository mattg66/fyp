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
        Schema::create('project_routers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vm_id')->nullable();
            $table->string('mgmt_ip')->nullable();
            $table->string('ip');
            $table->string('subnet_mask');
            $table->string('gateway');
            $table->string('status')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('project_id')->unique();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_routers');
    }
};
