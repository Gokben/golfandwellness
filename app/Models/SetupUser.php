<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class SetupUser extends Authenticatable
{
    use SoftDeletes;

    public const ROLES = ['Admin', 'Rezervasyon', 'Muhasebe', 'Operasyon'];

    protected $connection = 'setup_mysql';
    protected $fillable = ['name', 'surname', 'username', 'telephone', 'email', 'active', 'role'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'version' => 'integer'];
    }
}
