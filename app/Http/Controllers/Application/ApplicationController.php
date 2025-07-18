<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Application\ChainsawIndividualApplication;
use App\Models\Application\AttachmentsModel;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;




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
        try {

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

                'p_place_of_operation_address' => 'required|string',
                'p_province'                  => 'required|string',
                'p_city_mun'                  => 'required|string',
                'p_barangay'                  => 'required|string',
                'request_letter'  => 'required|file|mimes:jpg,png,jpeg,gif,pdf|max:2048',
                'soc_certificate' => 'required|file|mimes:jpg,png,jpeg,gif,pdf|max:2048',


            ]);

            $application = ChainsawIndividualApplication::create([
                'application_type'           => $request->input('application_type'),
                'transaction_type'           => $request->input('type_of_transaction'),
                'application_no'             => $request->input('application_no'),
                'date_applied'               => $request->input('date_applied'),
                'encoded_by'                 => $request->input('encoded_by'),
                'company_name'               => $request->input('company_name'),
                'company_address'            => $request->input('company_address'),
                'authorized_representative'  => $request->input('authorized_representative'),
                'company_c_province'         => $request->input('c_province'),
                'company_c_city_mun'         => $request->input('c_city_mun'),
                'company_c_barangay'         => $request->input('c_barangay'),
                'operation_complete_address' => $request->input('p_place_of_operation_address'),
                'operation_province_c'       => $request->input('p_province'),
                'operation_city_mun_c'       => $request->input('p_city_mun'),
                'operation_brgy_c'           => $request->input('p_barangay'),
            ]);


            $applicationNo = $application->application_no;

            $this->uploadFileToDrive($request->file('soc_certificate'), $applicationNo, "Secretary's Certificate");

            return response()->json([
                'message' => 'Company application submitted successfully.',
                'id' => $application->id,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function uploadFileToDrive($file, $applicationNo, $folderType)
    {
        try {
            //first create the folder Sec Certificate and Request Letter
            $folderPath = "CHAINSAW_PERMITTING/Company Applications/{$applicationNo}/{$folderType}";

            $fileName = $file->getClientOriginalName();
            $filePath = "{$folderPath}/{$fileName}";

            // Upload the file
            Storage::disk('google')->write($filePath, file_get_contents($file));

            // Get file metadata (to get the file ID)
            $files = Storage::disk('google')->listContents('/', true);
            $fileMeta = collect($files)->firstWhere('path', $filePath);
            $fileId = $fileMeta['extraMetadata']['id'] ?? null;

            if (!$fileId) {
                throw new \Exception("File upload failed: File ID for {$fileName} not found.");
            }

            $publicUrl = "https://drive.google.com/file/d/{$fileId}/view";

            // Store to DB
            AttachmentsModel::create([
                'file_name'      => $fileName,
                'file_path'      => $filePath,
                'application_id' => $applicationNo,
                'folder_id'      => null,
                'file_id'        => $fileId,
                'public_url'     => $publicUrl,
            ]);

            Log::info("File uploaded to Google Drive: {$fileName}");

            return [
                'name' => $fileName,
                'id'   => $fileId,
                'url'  => $publicUrl,
            ];
        } catch (\Exception $e) {
            Log::error("Google Drive Upload Failed: " . $e->getMessage());
            throw $e;
        }
    }


    // private function uploadSecCertToGoogleDrive($request, $applicationNo)
    // {
    //     try {
    //         $secFolder  = "CHAINSAW_PERMITTING/Company Applications/{$applicationNo}/Secretarys Certificate";

    //         $uploadedFiles = [];

    //         // 1. Upload request letter first
    //         if ($request->hasFile('soc_certificate')) {
    //             $uploadedFiles[] = $this->uploadSingleFileToDrive(
    //                 $request->file('soc_certificate'),
    //                 $secFolder,
    //                 $applicationNo
    //             );
    //         } else {
    //             Log::warning('request_letter not found in request');
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Files uploaded successfully!',
    //             'uploads' => $uploadedFiles,
    //         ], 200, [], JSON_UNESCAPED_SLASHES);
    //     } catch (\Exception $e) {
    //         Log::error('Google Drive Upload Error', ['error' => $e->getMessage()]);

    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'File upload failed!',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // private function uploadSingleFileToDrive($file, $folderPath, $applicationNo)
    // {
    //     $fileName = $file->getClientOriginalName();
    //     $filePath = "{$folderPath}/{$fileName}";

    //     // Upload to Google Drive
    //     Storage::disk('google')->write($filePath, file_get_contents($file));

    //     // Get file ID
    //     $files = Storage::disk('google')->listContents('', true);
    //     $fileMeta = collect($files)->where('path', $filePath)->first();
    //     $fileId = $fileMeta['extraMetadata']['id'] ?? null;

    //     if (!$fileId) {
    //         throw new \Exception("File upload failed: Unable to retrieve file ID for $fileName.");
    //     }

    //     $publicUrl = "https://drive.google.com/file/d/{$fileId}/view";

    //     // Save to DB
    //     AttachmentsModel::create([
    //         'file_name'      => $fileName,
    //         'file_path'      => $filePath,
    //         'application_id' => $applicationNo,
    //         'folder_id'      => null,
    //         'file_id'        => $fileId,
    //         'public_url'     => $publicUrl,
    //     ]);

    //     return [
    //         'name' => $fileName,
    //         'id'   => $fileId,
    //         'url'  => $publicUrl,
    //     ];
    // }


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
