<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'ocr_text')) {
                $table->dropColumn('ocr_text');
            }
        });
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('ocr_text')->nullable();
        });
    }

};
