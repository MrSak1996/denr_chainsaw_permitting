<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentModel extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tbl_application_payment'; 

    protected $fillable = [
        'id',
        'application_id',
        'application_attachment_id',
        'official_receipt',
        'permit_fee',
        'date_of_payment',
        'updated_at',
        'created_at',
    ];
}
