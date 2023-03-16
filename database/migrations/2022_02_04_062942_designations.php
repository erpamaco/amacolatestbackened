<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Designations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            // $table->timestamps();
            $table->string('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            // $table->string('payment_account_id')->nullable();
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
