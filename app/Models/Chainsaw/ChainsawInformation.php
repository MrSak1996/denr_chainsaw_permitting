<?php

namespace App\Models\Chainsaw;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawInformation extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tbl_chainsaw_information';

    protected $fillable = [
        'id',
        'application_id',
        'file_id',
        'permit_chainsaw_no',
        'brand',
        'model',
        'quantity',
        'supplier_name',
        'purpose',
        'permit_validity',
        'others_details',
        'updated_at',
        'created_at'
    ];
    
}
