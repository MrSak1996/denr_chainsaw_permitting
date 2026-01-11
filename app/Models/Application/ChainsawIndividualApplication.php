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
        'application_status',
        'application_type',
        'transaction_type',
        'classification',
        'application_no',
        'permit_no',
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
        'company_name',
        'company_address',
        'authorized_representative',
        'company_c_province',
        'company_c_city_mun',
        'company_c_barangay',
        'is_rps_chief_received',
        'is_tsd_chief_received',
        'is_fus_received',
        'is_ardts_received',
        'is_red_received',
        'date_applied',
        'date_received_tsd_chief',
        'date_received_rps_chief',
        'date_received_ardts',
        'date_received_penro_chief',
        'date_received_fus',
        'date_received_red',
        'date_endorsed_tsd_chief',
        'date_endorsed_penro',
        'date_endorsed_fus',
        'date_endorsed_ardts',
        'date_endorsed_red',
        'date_returned',
        'updated_at',
        'created_at'
    ];
}
