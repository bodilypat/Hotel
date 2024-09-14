<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBorrowersTable extends Migration 
{

    /* Run the migrations. */
    public function up()
    {
        Schema::create('borrows', function (Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    /* Reverse the migrations */
    public function down()
    {
        Schema::dropIfExists('borrowers');
    }
}