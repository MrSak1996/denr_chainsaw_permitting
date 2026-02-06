<?php

namespace App\Models\Chainsaw;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawBrand extends Model
{
     use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'chainsaw_brands';

    protected $fillable = [
        'id',
        'application_id',
        'brand_name',
        'created_at',
        'updated_at',
    ];
}
