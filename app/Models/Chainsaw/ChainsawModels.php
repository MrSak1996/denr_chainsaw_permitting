<?php

namespace App\Models\Chainsaw;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawModels extends Model
{
      use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'chainsaw_models';

    protected $fillable = [
        'id',
        'brand_id',
        'model',
        'quantity',
        'created_at',
        'updated_at',
    ];
}
