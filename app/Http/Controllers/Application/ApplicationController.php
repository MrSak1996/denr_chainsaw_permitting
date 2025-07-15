<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Application\ChainsawIndividualApplication;


class ApplicationController extends Controller
{


    public function apply(Request $request)
    {
        // Validate the incoming request; this will automatically return 422 on failure
        $validated = $request->validate([
            'geo_code'                     => 'required|string',
            'application_type'            => 'required|string',
            'type_of_transaction'         => 'required|string',
            'application_no'              => 'required|string|unique:tbl_application_checklist',
            'date_applied'                => 'required|string',
            'encoded_by'                  => 'nullable|integer',

            'last_name'                   => 'required|string',
            'first_name'                  => 'required|string',
            'middle_name'                 => 'nullable|string',
            'sex'                         => 'required|string|in:Male,Female,Other',
            'gov_id_type'                 => 'nullable|string',
            'gov_id_number'              => 'nullable|string',
            'mobile_no'                   => 'nullable|string',
            'telephone_no'               => 'nullable|string',
            'email_address'              => 'nullable|email',

            'i_province'                 => 'required|string',
            'i_city_mun'                 => 'required|string',
            'i_barangay'                 => 'required|string',
            'i_complete_address'         => 'required|string',

            'p_place_of_operation_address' => 'nullable|string',
            'p_region'                   => 'nullable|string',
            'p_province'                 => 'nullable|string',
            'p_city_mun'                 => 'nullable|string',
            'p_barangay'                 => 'nullable|string',
        ]);

        // Create the application using the validated data
        $application = ChainsawIndividualApplication::create([
            'geo_code'                   => $validated['geo_code'],
            'application_type'          => $validated['application_type'],
            'transaction_type'          => $validated['type_of_transaction'],
            'application_no'            => $validated['application_no'],
            'date_applied'              => $validated['date_applied'],
            'encoded_by'                => $validated['encoded_by'] ?? null,

            'applicant_lastname'        => $validated['last_name'],
            'applicant_firstname'       => $validated['first_name'],
            'applicant_middlename'      => $validated['middle_name'] ?? null,
            'sex'                       => $validated['sex'],
            'government_id'             => $validated['gov_id_type'] ?? null,
            'gov_id_number'             => $validated['gov_id_number'] ?? null,
            'applicant_contact_details' => $validated['mobile_no'] ?? null,
            'applicant_telephone_no'    => $validated['telephone_no'] ?? null,
            'applicant_email_address'   => $validated['email_address'] ?? null,

            'applicant_province_c'      => $validated['i_province'],
            'applicant_city_mun_c'      => $validated['i_city_mun'],
            'applicant_brgy_c'          => $validated['i_barangay'],
            'applicant_complete_address' => $validated['i_complete_address'],

            'operation_complete_address' => $validated['p_place_of_operation_address'] ?? null,
            'operation_province_c'      => $validated['p_province'] ?? null,
            'operation_city_mun_c'      => $validated['p_city_mun'] ?? null,
            'operation_brgy_c'          => $validated['p_barangay'] ?? null,
        ]);

        return response()->json([
            'message' => 'Application submitted successfully.',
            'id' => $application->id,
        ], 201);
    }

    public function company_apply(Request $request)
    {
        $validated = $request->validate([
            'geo_code'                     => 'required|string',
            'application_type'            => 'required|string',
            'type_of_transaction'         => 'required|string',
            'application_no'              => 'required|string|unique:tbl_application_checklist',
            'date_applied'                => 'required|string',
            'encoded_by'                  => 'nullable|integer',

            'company_name'                => 'required|string',
            'company_address'             => 'required|string',
            'authorized_representative'   => 'nullable|string',

            'c_province'                  => 'required|string',
            'c_city_mun'                  => 'required|string',
            'c_barangay'                  => 'required|string',

            'p_place_of_operation_address'  => 'required|string',
            'p_province'                  => 'required|string',
            'p_city_mun'                  => 'required|string',
            'p_barangay'                  => 'required|string',

        ]);

        $application = ChainsawIndividualApplication::create([
            // '' => $validated['geo_code'],
            'application_type'           => $validated['application_type'],
            'transaction_type'           => $validated['type_of_transaction'],
            'application_no'             => $validated['application_no'],
            'date_applied'               => $validated['date_applied'],
            'encoded_by'                 => $validated['encoded_by'] ?? null,

            'company_name'               => $validated['company_name'],
            'company_address'            => $validated['company_address'],
            'authorized_representative'  => $validated['authorized_representative'] ?? null,
            'company_c_province'         => $validated['c_province'],
            'company_c_city_mun'         => $validated['c_city_mun'],
            'company_c_barangay'         => $validated['c_barangay'],

            'operation_complete_address' => $validated['p_place_of_operation_address'] ?? null,
            'operation_province_c'       => $validated['p_province'] ?? null,
            'operation_city_mun_c'       => $validated['p_city_mun'] ?? null,
            'operation_brgy_c'           => $validated['p_barangay'] ?? null,
        ]);

        return response()->json([
            'message' => 'Company application submitted successfully.',
            'id' => $application->id,
        ], 201);
    }




    // This controller handles application-related functionalities, including fetching geographical data.
    public function getProvinces()
    {
        $results = DB::table('geo_map')
            ->select('prov_code', 'prov_name')
            ->where('reg_code', '04')
            ->groupBy('prov_code', 'prov_name')
            ->get();

        return response()->json($results);
    }

    public function getCitiesByProvince($provinceId)
    {
        if (!is_numeric($provinceId)) {
            return response()->json(['error' => 'Invalid province code'], 400);
        }

        $municipalities = DB::table('geo_map')
            ->select('mun_code', DB::raw('MIN(geo_code) as geo_code'), DB::raw('MIN(mun_name) as mun_name'))
            ->where('reg_name', 'REGION IV-A CALABARZON')
            ->where('reg_code', '04')
            ->where('prov_code', $provinceId)
            ->groupBy('mun_code')
            ->get();


        return response()->json($municipalities);
    }

    public function getBarangays(Request $request)
    {
        $regCode = '04';
        $provCode = $request->query('prov_code');
        $munCode = $request->query('mun_code');

        $barangays = DB::table('geo_map')
            ->select(
                'reg_code',
                'reg_name',
                'prov_code',
                'prov_name',
                'mun_code',
                'mun_name',
                'bgy_code',
                'bgy_name'
            )
            ->where('reg_code', $regCode)
            ->where('prov_code', $provCode)
            ->where('mun_code', $munCode)
            ->groupBy(
                'reg_code',
                'reg_name',
                'prov_code',
                'prov_name',
                'mun_code',
                'mun_name',
                'bgy_code',
                'bgy_name'
            )
            ->get();

        return response()->json($barangays);
    }

    public function generateApplicationNumber()
    {
        $latestApplication = DB::table('tbl_application_checklist')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestApplication) {
            $lastNumber = (int) substr($latestApplication->application_no, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        $applicationNo = 'DENR-R4A-' . date('Y') . '-' . $newNumber;

        return response()->json(['application_no' => $applicationNo]);
    }
}
