<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'setup_mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('setup_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('surname', 100)->default('');
            $table->string('username', 80)->unique();
            $table->string('telephone', 40)->default('');
            $table->string('email', 254)->default('');
            $table->string('password')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // Reference profile cards only: no invented passwords or login privileges.
        $profiles = [
            ['Rana', 'Sürmeli', 'rana', '54545', 'sales3@golf'],
            ['Merve', 'Kırca', 'merve', '54545', 'sales4@golf'],
            ['Özgül', 'Şekerci', 'ozgul', '54545', 'sales1@golf'],
            ['Admin', '', 'admin2', '4545454545', 'golf@golfandwellness.com.tr'],
        ];
        foreach ($profiles as [$name, $surname, $username, $telephone, $email]) {
            DB::connection($this->connection)->table('setup_users')->insert([
                'name' => $name, 'surname' => $surname, 'username' => $username,
                'telephone' => $telephone, 'email' => $email, 'password' => null,
                'active' => true, 'version' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('setup_users');
    }
};
