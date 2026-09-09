<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'setup_mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('setup_users', function (Blueprint $table) {
            // Existing profiles stay unassigned; roles do not grant permissions yet.
            $table->string('role', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('setup_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
