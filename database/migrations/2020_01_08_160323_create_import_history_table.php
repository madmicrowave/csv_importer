<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('remote_disk');
            $table->integer('remote_disks_id')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('file_modification_time');
            $table->integer('file_processing_time');
            $table->integer('records_in_file');
            $table->string('last_error');
            $table->timestamps();

            $table->unique(['remote_disks_id', 'file_path'], 'idx_remote_disks_id_file_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_history');
    }
}
