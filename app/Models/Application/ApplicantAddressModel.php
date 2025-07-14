<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ApplicantAddressModel extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'geo_map';

    protected $fillable = [
        'geo_code',
        'phcode_reg',
        'iso_reg',
        'reg_code',
        'reg_shortname',
        'reg_name',
        'phcode_prov',
        'iso_prv',
        'prov_code',
        'prov_name',
        'phcode_mun',
        'dist_code',
        'district',
        'mun_code',
        'mun_name',
        'phcode_bgy',
        'bgy_code',
        'bgy_name',
        'lat',
        'long',
    ];
}
