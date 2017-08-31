<?php

use Illuminate\Database\Migrations\Migration;

class IncreaseLocaleLength extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('language_translations', function ($table) {
            $table->string('language_id', 10)->change();
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
