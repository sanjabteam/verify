<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSanjabVerifiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sanjab_verifies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('method');
            $table->string('receiver')->index();
            $table->string('ip');
            $table->text('agent')->nullable();
            $table->string('code');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sanjab_verifies');
    }
}
