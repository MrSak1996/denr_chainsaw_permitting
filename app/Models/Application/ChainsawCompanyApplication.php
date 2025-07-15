<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawCompanyApplication extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = '';

    protected $fillable = [];
}
