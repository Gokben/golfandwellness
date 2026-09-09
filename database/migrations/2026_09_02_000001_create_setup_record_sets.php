<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'setup_mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('setup_record_sets', function (Blueprint $table) {
            $table->string('kind', 32)->primary();
            $table->json('records');
            $table->unsignedInteger('version')->default(1);
            $table->string('import_hash', 64)->nullable();
            $table->timestamps();
        });
        Schema::connection($this->connection)->create('setup_record_backups', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 32);
            $table->unsignedInteger('version');
            $table->json('records');
            $table->timestamp('created_at');
            $table->unique(['kind', 'version']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('setup_record_backups');
        Schema::connection($this->connection)->dropIfExists('setup_record_sets');
    }
};
