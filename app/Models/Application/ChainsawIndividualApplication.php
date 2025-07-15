<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawIndividualApplication extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tbl_application_checklist';

    protected $fillable = [
        'id',
        'application_type',
        'transaction_type',
        'application_no',
        'encoded_by',
        'applicant_lastname',
        'applicant_firstname',
        'applicant_middlename',
        'sex',
        'government_id',
        'gov_id_number',
        'applicant_contact_details',
        'applicant_telephone_no',
        'applicant_email_address',
        'applicant_province_c',
        'applicant_city_mun_c',
        'applicant_brgy_c',
        'applicant_complete_address',
        'operation_province_c',
        'operation_city_mun_c',
        'operation_brgy_c',
        'operation_complete_address',
        'updated_at',
        'created_at'
    ];
}
