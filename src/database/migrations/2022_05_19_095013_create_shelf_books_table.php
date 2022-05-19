<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShelfBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shelf_books', function (Blueprint $table) {
            $table->bigIncrements('id')->unique();
            $table->bigInteger('user_id')->nullable(true)->default(NULL);
            $table->bigInteger('file_id')->nullable(true)->default(NULL);
            $table->bigInteger('identifier_id')->nullable(true)->default(NULL);
            $table->bigInteger('ref_id')->nullable(true)->default(NULL);
            $table->string('type', 45)->nullable(true)->default(NULL);
            $table->enum('downloaded', ['true', 'false'])->default('false');
            $table->enum('unzipped', ['true', 'false'])->default('false');
            $table->enum('readable', ['true', 'false'])->default('false');
            $table->enum('checked', ['true', 'false'])->default('false');
            $table->string('pathAndName', 100)->nullable(true)->default(NULL);
            $table->string('session_id', 100)->nullable(true)->default(NULL);
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
        Schema::dropIfExists('shelf_books');
    }
}
