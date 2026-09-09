<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'setup_mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->string('currency_code', 3);
            $table->string('currency_name');
            $table->decimal('forex_buying', 18, 6)->nullable();
            $table->decimal('forex_selling', 18, 6)->nullable();
            $table->decimal('banknote_buying', 18, 6)->nullable();
            $table->decimal('banknote_selling', 18, 6)->nullable();
            $table->timestamps();
            $table->unique(['rate_date', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('exchange_rates');
    }
};
