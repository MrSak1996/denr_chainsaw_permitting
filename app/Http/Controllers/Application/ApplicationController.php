<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Application\ChainsawIndividualApplication;
use App\Models\ApplicantAttachments\AttachmentsModel;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;
use Inertia\Inertia;
use Carbon\Carbon;




class ApplicationController extends Controller
{
    // Define status constants
    const STATUS_RETURN_FOR_COMPLIANCE = 0;
    const STATUS_FOR_REVIEW_EVALUATION = 1;
    const STATUS_ENDORSED_CENRO = 2;
    const STATUS_ENDORSED_PENRO = 3;
    const STATUS_ENDORSED_RO = 4;
    const STATUS_APPROVED = 5;

    // IMPLEMENTING PENRO
    const TECHNICAL_STAFF = 1;
    const CHIEF_RPS = 8;
    const CHIEF_TSD = 10;
    const IMPLEMENTING_PENRO = 3;
    /**
     * Mapping of statuses to their labels
     */
    protected $statusMap = [
        self::STATUS_RETURN_FOR_COMPLIANCE => 'Return for Compliance',
        self::STATUS_FOR_REVIEW_EVALUATION => 'For Review / Evaluation',
        self::STATUS_ENDORSED_CENRO => 'Endorsed to CENRO',
        self::STATUS_ENDORSED_PENRO => 'Endorsed to PENRO',
        self::STATUS_ENDORSED_RO => 'Endorsed to R.O',
        self::STATUS_APPROVED => 'Approved',

        self::TECHNICAL_STAFF => ''
    ];



    public function apply(Request $request)
    {
        // Validate the incoming request; this will automatically return 422 on failure
        $validated = $request->validate([
            // 'geo_code' => 'required|string',
            'application_type' => 'required|string',
            'type_of_transaction' => 'required|string',
            'application_no' => 'required',
            'date_applied' => 'required|string',
            'encoded_by' => 'nullable|integer',

            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'sex' => 'required|string|in:male,female,Other',
            'gov_id_type' => 'nullable|string',
            'gov_id_number' => 'nullable|string',
            'classification' => 'required',
            'i_province' => 'required|string',
            'i_city_mun' => 'required|string',
            'i_barangay' => 'required|string',
            'i_complete_address' => 'required|string',
            'mobile_no' => 'nullable|string',          // ✅ ADD
            'telephone_no' => 'nullable|string',       // ✅ ADD
            'email_address' => 'nullable|string',

            'p_place_of_operation_address' => 'nullable|string',
            'p_region' => 'nullable|string',
            'p_province' => 'nullable|string',
            'p_city_mun' => 'nullable|string',
            'p_barangay' => 'nullable|string',
        ]);

        // Create the application using the validated data
        $application = ChainsawIndividualApplication::create([
            'application_status' => null,
            // 'geo_code' => $validated['geo_code'],
            'application_type' => $validated['application_type'],
            'transaction_type' => $validated['type_of_transaction'],
            'application_no' => $validated['application_no'],
            'date_applied' => $validated['date_applied'],
            'encoded_by' => $validated['encoded_by'] ?? null,
            'classification' => $validated['classification'] ?? null,
            'applicant_lastname' => $validated['last_name'],
            'applicant_firstname' => $validated['first_name'],
            'applicant_middlename' => $validated['middle_name'] ?? null,
            'sex' => $validated['sex'],
            'government_id' => $validated['gov_id_type'] ?? null,
            'gov_id_number' => $validated['gov_id_number'] ?? null,
            'applicant_contact_details' => $validated['mobile_no'] ?? null,
            'applicant_telephone_no' => $validated['telephone_no'] ?? null,
            'applicant_email_address' => $validated['email_address'] ?? null,

            'applicant_province_c' => $validated['i_province'],
            'applicant_city_mun_c' => $validated['i_city_mun'],
            'applicant_brgy_c' => $validated['i_barangay'],
            'applicant_complete_address' => $validated['i_complete_address'],

            'operation_complete_address' => $validated['p_place_of_operation_address'] ?? null,
            'operation_province_c' => $validated['p_province'] ?? null,
            'operation_city_mun_c' => $validated['p_city_mun'] ?? null,
            'operation_brgy_c' => $validated['p_barangay'] ?? null,
        ]);

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application_id' => $application->id,
        ], 201);
    }

    public function company_apply(Request $request, GoogleDriveService $driveService)
    {
        try {
            $validated = $request->validate([
                'geo_code' => 'required|string',
                'application_type' => 'required|string',
                'type_of_transaction' => 'required|string',
                'application_no' => 'required|string|unique:tbl_application_checklist',
                'date_applied' => 'required|string',
                'encoded_by' => 'nullable|integer',

                'company_name' => 'required|string',
                'company_address' => 'required|string',
                'authorized_representative' => 'nullable|string',

                'c_province' => 'required|string',
                'c_city_mun' => 'required|string',
                'c_barangay' => 'required|string',

                // 'p_place_of_operation_address' => 'required|string',
                // 'p_province' => 'required|string',
                // 'p_city_mun' => 'required|string',
                // 'p_barangay' => 'required|string',

                'request_letter' => 'required|file|mimes:jpg,png,jpeg,gif,pdf|max:2048',
                'soc_certificate' => 'required|file|mimes:jpg,png,jpeg,gif,pdf|max:2048',
            ]);

            $application = ChainsawIndividualApplication::create([
                'application_status' => null,
                'application_type' => $request->input('application_type'),
                'transaction_type' => $request->input('type_of_transaction'),
                'application_no' => $request->input('application_no'),
                'date_applied' => $request->input('date_applied'),
                'encoded_by' => $request->input('encoded_by'),
                'company_name' => $request->input('company_name'),
                'company_address' => $request->input('company_address'),
                'authorized_representative' => $request->input('authorized_representative'),
                'company_c_province' => $request->input('c_province'),
                'company_c_city_mun' => $request->input('c_city_mun'),
                'company_c_barangay' => $request->input('c_barangay'),
                'operation_complete_address' => $request->input('p_place_of_operation_address'),
                'operation_province_c' => $request->input('p_province'),
                'operation_city_mun_c' => $request->input('p_city_mun'),
                'operation_brgy_c' => $request->input('p_barangay'),
            ]);

            $applicationNo = $application->application_no;
            $applicationId = $application->id;

            $filesToUpload = [
                'request_letter' => 'Request Letter',
                'soc_certificate' => 'Secretary Certificate'
            ];

            $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $applicationNo;
            $result = $driveService->storeAttachments($request->input('application_no'), $request, $applicationId, $folderPath, $filesToUpload);

            return response()->json([
                'application_id' => $application->id,
                'result' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    private function getFileIdWithRetry($filePath, $fileName, $maxRetries = 3)
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $files = Storage::disk('google')->listContents('/', true);
                $fileMeta = collect($files)->firstWhere('path', $filePath);

                if ($fileMeta && isset($fileMeta['extraMetadata']['id'])) {
                    return $fileMeta['extraMetadata']['id'];
                }

                sleep(2);
            } catch (\Exception $e) {
                Log::warning("Retry {$i} failed for {$fileName}: " . $e->getMessage());
                sleep(2);
            }
        }

        return null;
    }

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
                'geo_code',
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
                'geo_code',
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

        $applicationNo = 'DENR-IV-A-' . date('Y') . '-' . $newNumber;

        return response()->json(['application_no' => $applicationNo]);
    }

    public function showApplicationDetails(Request $request)
    {
        $userId = $request->query('id'); // ?id=1

        $applicationDetails = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->leftJoin('tbl_status as s', 'ac.application_status', '=', 's.id')
            ->leftJoin('users as u', 'u.id', '=', 'ac.encoded_by') // ← You missed this join!

            ->select(
                'ac.id',
                'ac.permit_no',
                'u.id as user_id',
                'u.office_id',
                's.status_title',
                'ac.sex',
                'ac.application_status',
                'ac.application_type',
                'ac.application_no',
                'ac.transaction_type',
                'ac.classification',
                'ac.applicant_complete_address',
                'ac.company_address',
                'ac.authorized_representative',
                DB::raw("CONCAT(ac.applicant_lastname, ', ', ac.applicant_firstname, ' ', ac.applicant_middlename) AS applicant_name"),
                'ci.permit_chainsaw_no',
                'ci.brand',
                'ci.model',
                'ci.quantity',
                'ci.purpose',
                'ap.official_receipt',
                'ap.permit_fee',
                'ap.date_of_payment',
                'ci.permit_validity',
                'ac.created_at',
                'ac.date_received_rps_chief',
                'ac.date_received_tsd_chief',
                'ac.date_received_penro_chief',
                'ac.date_received_fus',
                'ac.date_received_ardts',
                'ac.date_received_red',
                'ac.date_endorsed_tsd_chief',
                'ac.date_applied',
                'ac.rps_chief_comments'
            )

            ->where('u.id', $userId)
            ->orderBy('ac.id', 'desc')
            ->get()
            ->map(function ($item) {
                $item->created_at = $item->created_at
                    ? \Carbon\Carbon::parse($item->created_at)->format('F d, Y')
                    : null;

                $item->date_applied = $item->date_applied
                    ? \Carbon\Carbon::parse($item->date_applied)->format('F d, Y')
                    : null;

                $item->date_of_payment = $item->date_of_payment
                    ? \Carbon\Carbon::parse($item->date_of_payment)->format('F d, Y')
                    : null;

                $item->permit_validity = $item->permit_validity
                    ? \Carbon\Carbon::parse($item->permit_validity)->format('F d, Y')
                    : null;

                $item->date_endorsed_tsd_chief = $item->date_endorsed_tsd_chief
                    ? \Carbon\Carbon::parse($item->date_endorsed_tsd_chief)->format('F d, Y')
                    : null;

                $item->date_received_penro_chief = $item->date_received_penro_chief
                    ? \Carbon\Carbon::parse($item->date_received_penro_chief)->format('F d, Y')
                    : null;

                $item->date_received_fus = $item->date_received_fus
                    ? \Carbon\Carbon::parse($item->date_received_fus)->format('F d, Y')
                    : null;

                $item->date_received_red = $item->date_received_red
                    ? \Carbon\Carbon::parse($item->date_received_red)->format('F d, Y')
                    : null;

                return $item;
            });

        return response()->json([
            'data' => $applicationDetails
        ]);
    }


    public function getApplicationDetails($application_id)
    {
        $applicationDetails = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->leftJoin('geo_map as g', 'g.prov_code', '=', 'ac.company_c_province')
            ->leftJoin('tbl_status as s', 'ac.application_status', '=', 's.id')
            ->select(
                'ac.id',
                'ac.applicant_lastname as last_name',
                'ac.applicant_firstname as first_name',
                'ac.applicant_middlename as middle_name',
                'ac.sex',
                'ac.government_id as gov_id_type',
                'ac.gov_id_number as gov_id_number',
                'ac.applicant_contact_details as mobile_no',
                'ac.applicant_telephone_no as telephone_no',
                'ac.applicant_email_address as email_address',
                'ac.applicant_province_c as i_province',
                'ac.applicant_city_mun_c as i_city_mun',
                'ac.applicant_brgy_c as i_barangay',
                'ac.applicant_complete_address',
                'ac.classification',
                's.status_title',
                'ac.return_reason',
                'ac.application_no',
                'ac.permit_no',
                'ac.application_status',
                'ac.application_type',
                'ac.authorized_representative',
                'ac.date_applied',
                'ac.company_name',
                'ac.company_address',
                'ac.company_c_province',
                'ac.company_c_province as prov_code',
                'ac.company_c_city_mun',
                'ac.company_c_barangay',
                'ac.company_mobile_no',
                'ac.operation_complete_address',
                'ac.transaction_type as type_of_transaction',
                'ac.rps_chief_comments',
                'ac.cenro_comments',
                'ac.tsd_chief_comments',
                'ac.penro_comments',
                'ac.ro_comments',
                'ac.ardts_comments',
                'g.prov_name',
                'ci.supplier_name',
                'ci.permit_chainsaw_no',
                'ci.brand',
                'ci.model',
                'ci.quantity',
                'ci.purpose',
                'ci.other_details',
                'ap.official_receipt',
                'ap.permit_fee',
                'ap.date_of_payment',
                'ci.permit_validity',
                'ac.created_at',
            )
            ->where('ac.id', $application_id)
            ->first(); // get a single record

        if ($applicationDetails) {
            $applicationDetails->created_at = $applicationDetails->created_at
                ? \Carbon\Carbon::parse($applicationDetails->created_at)->format('F d, Y')
                : null;

            $applicationDetails->date_applied = $applicationDetails->date_applied
                ? \Carbon\Carbon::parse($applicationDetails->date_applied)->format('d/m/Y')
                : null;

            $applicationDetails->date_of_payment = $applicationDetails->date_of_payment
                ? \Carbon\Carbon::parse($applicationDetails->date_of_payment)->format('F d, Y')
                : null;

            $applicationDetails->permit_validity = $applicationDetails->permit_validity
                ? \Carbon\Carbon::parse($applicationDetails->permit_validity)->format('F d, Y')
                : null;

            // $applicationDetails->date_applied = $applicationDetails->date_applied
            //     ? \Carbon\Carbon::parse($applicationDetails->date_applied)->format('F d, Y')
            //     : null;
        }

        return response()->json([
            'data' => $applicationDetails
        ]);
    }

    public function getApplicantFile($application_id)
    {
        try {
            $data = DB::table('tbl_application_attachments as aa')
                ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'aa.application_id')
                ->select(
                    'ac.application_no',
                    'aa.id',
                    'aa.application_id',
                    'aa.file_name',
                    'aa.file_id',
                    'aa.file_url',
                    'aa.created_at'
                )
                ->where('aa.application_id', $application_id)
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No application details found.'
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching application details.',
                'error' => $e->getMessage()
            ]);
        }
    }


    // INERTIA
    public function edit($id)
    {
        $applicationDetails = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->leftJoin('geo_map as g', 'g.prov_code', '=', 'ac.company_c_province')
            ->leftJoin('tbl_status as s', 'ac.application_status', '=', 's.id')
            ->leftJoin('users as u', 'u.id', '=', 'ac.encoded_by') // ← You missed this join!
            ->leftJoin('tbl_office as o', 'o.id', '=', 'u.office_id') // ← You missed this join!

            ->select(
                'ac.id',
                'u.office_id',
                'o.office_title',
                'ac.applicant_lastname as last_name',
                'ac.applicant_firstname as first_name',
                'ac.applicant_middlename as middle_name',
                'ac.sex',
                'ac.government_id as gov_id_type',
                'ac.gov_id_number as gov_id_number',
                'ac.applicant_contact_details as mobile_no',
                'ac.applicant_telephone_no as telephone_no',
                'ac.applicant_email_address as email_address',
                'ac.applicant_province_c as i_province',
                'ac.applicant_city_mun_c as i_city_mun',
                'ac.applicant_brgy_c as i_barangay',
                'ac.applicant_complete_address as i_complete_address',
                'ac.classification',
                's.status_title',
                'ac.return_reason',
                'ac.application_no',
                'ac.permit_no',
                'ac.application_status',
                'ac.application_type',
                'ac.authorized_representative',
                'ac.date_applied',
                'ac.company_name',
                'ac.company_address',
                'ac.company_mobile_no',
                'ac.company_c_province',
                'ac.company_c_province as prov_code',
                'ac.company_c_city_mun',
                'ac.company_c_barangay',
                'ac.operation_complete_address',
                'ac.transaction_type as type_of_transaction',
                'g.prov_name',
                'ci.supplier_name',
                'ci.supplier_address',
                'ci.permit_chainsaw_no',
                'ci.brand',
                'ci.model',
                'ci.quantity',
                'ci.purpose',
                'ci.other_details',
                'ap.official_receipt',
                'ap.permit_fee',
                'ap.remarks',
                'ap.date_of_payment',
                'ci.permit_validity',
                'ac.created_at',
            )
            ->where('ac.id', $id)
            ->first();

        if (!$applicationDetails) {
            abort(404, 'Application not found.');
        }

        // Format dates (only created_at is allowed)
        $applicationDetails->created_at = $applicationDetails->created_at
            ? Carbon::parse($applicationDetails->created_at)->format('d/m/Y')
            : null;

        // Return ONLY the allowed safe fields
        return Inertia::render('applications/form_edit/index', [
            'application' => [
                'id' => $applicationDetails->id,
                'office_id' => $applicationDetails->office_id,
                'office_title' => $applicationDetails->office_title,
                'permit_no' => $applicationDetails->permit_no,
                'last_name' => $applicationDetails->last_name,
                'first_name' => $applicationDetails->first_name,
                'middle_name' => $applicationDetails->middle_name,
                'sex' => $applicationDetails->sex,
                'gov_id_type' => $applicationDetails->gov_id_type,
                'gov_id_number' => $applicationDetails->gov_id_number,
                'mobile_no' => $applicationDetails->mobile_no,
                'telephone_no' => $applicationDetails->telephone_no,
                'email_address' => $applicationDetails->email_address,
                'date_applied' => $applicationDetails->date_applied,
                'prov_code' => $applicationDetails->prov_code,
                'company_c_city_mun' => $applicationDetails->company_c_city_mun,
                'company_c_barangay' => $applicationDetails->company_c_barangay,
                'application_type' => $applicationDetails->application_type,
                'application_no' => $applicationDetails->application_no,
                'i_province' => $applicationDetails->i_province,
                'i_city_mun' => $applicationDetails->i_city_mun,
                'i_barangay' => $applicationDetails->i_barangay,
                'i_complete_address' => $applicationDetails->i_complete_address,
                'type_of_transaction' => $applicationDetails->type_of_transaction,
                'classification' => $applicationDetails->classification,
                'company_name' => $applicationDetails->company_name,
                'company_address' => $applicationDetails->company_address,
                'company_mobile_no' => $applicationDetails->company_mobile_no,
                'authorized_representative' => $applicationDetails->authorized_representative,
                'created_at' => $applicationDetails->created_at,
                'brand' => $applicationDetails->brand,
                'model' => $applicationDetails->model,
                'quantity' => $applicationDetails->quantity,
                'purpose' => $applicationDetails->purpose,
                'supplier_name' => $applicationDetails->supplier_name,
                'supplier_address' => $applicationDetails->supplier_address,
                'status_title' => $applicationDetails->status_title,
                'official_receipt' => $applicationDetails->official_receipt,
                'permit_fee' => $applicationDetails->permit_fee,
                'remarks' => $applicationDetails->remarks,
                'permit_validity' => $applicationDetails->permit_validity,
                'other_details' => $applicationDetails->other_details,
                'permit_chainsaw_no' => $applicationDetails->permit_chainsaw_no
            ],
        ]);
    }
    public function updateIndividualApplicant(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $updateResult = DB::table('tbl_application_checklist')
                ->where('id', $id)
                ->update([
                    'application_status' => 1,

                    // Applicant basic info
                    'application_type' => $request->input('application_type', 'Individual'),
                    'applicant_lastname' => $request->input('last_name'),
                    'applicant_firstname' => $request->input('first_name'),
                    'applicant_middlename' => $request->input('middle_name'),

                    // Transaction
                    'transaction_type' => $request->input('type_of_transaction'),

                    // Date Applied (formatted)
                    'date_applied' => $request->filled('date_applied')
                        ? date('Y-m-d', strtotime($request->input('date_applied')))
                        : null,

                    // IDs
                    'gov_id_number' => $request->input('gov_id_number'),
                    'government_id' => $request->input('government_id'),

                    // Sex
                    'sex' => $request->input('sex'),

                    // Contact details
                    'applicant_contact_details' => $request->input('applicant_contact_details'),
                    'applicant_telephone_no' => $request->input('applicant_telephone_no'),
                    'applicant_email_address' => $request->input('applicant_email_address'),

                    // Address fields
                    'applicant_province_c' => $request->input('applicant_province_c'),
                    'applicant_city_mun_c' => $request->input('applicant_city_mun_c'),
                    'applicant_brgy_c' => $request->input('applicant_brgy_c'),
                    'applicant_complete_address' => $request->input('applicant_complete_address'),

                    // System fields
                    'encoded_by' => $request->input('encoded_by'),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => $updateResult ? 'success' : 'error',
                'message' => $updateResult ? 'Application updated successfully.' : 'No changes were made.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCompanyApplicant(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $updateResult = DB::table('tbl_application_checklist')
                ->where('id', $id)
                ->update([
                    'application_status' => 1,
                    'application_type' => $request->input('application_type', 'Company'),
                    'transaction_type' => $request->input('type_of_transaction'),
                    'date_applied' => $request->filled('date_applied')
                        ? date('Y-m-d', strtotime($request->input('date_applied')))
                        : null,
                    'company_name' => $request->input('company_name'),
                    'classification' => $request->input('classification'),
                    'company_address' => $request->input('company_address'),
                    'company_mobile_no' => $request->input('company_mobile_no'),
                    'authorized_representative' => $request->input('authorized_representative'),
                    'company_c_province' => $request->input('c_province'),
                    'company_c_city_mun' => $request->input('c_city_mun'),
                    'company_c_barangay' => $request->input('c_barangay'),
                    'applicant_contact_details' => $request->input('applicant_contact_details'),
                    'applicant_telephone_no' => $request->input('applicant_telephone_no'),
                    'applicant_email_address' => $request->input('applicant_email_address'),
                    'applicant_province_c' => $request->input('applicant_province_c'),
                    'applicant_city_mun_c' => $request->input('applicant_city_mun_c'),
                    'applicant_brgy_c' => $request->input('applicant_brgy_c'),
                    'applicant_complete_address' => $request->input('applicant_complete_address'),
                    'encoded_by' => $request->input('encoded_by'),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => $updateResult ? 'success' : 'error',
                'message' => $updateResult ? 'Application updated successfully.' : 'No changes were made.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function updateChainsawInformation(Request $request, $id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Prepare the data you want to update
    //         $updateData = [
    //             'brand' => $request->input('brand'),
    //             'model' => $request->input('model'),
    //             'quantity' => $request->input('quantity'),
    //             'supplier_name' => $request->input('supplier_name'),
    //             'supplier_address' => $request->input('supplier_address'),
    //             'purpose' => $request->input('purpose'),
    //             'permit_validity' => $request->input('permit_validity'),
    //             'other_details' => $request->input('other_details'),
    //             'updated_at' => now(),
    //             'created_at' => now(),
    //             'permit_no' => $request->input('permit_no'),

    //         ];

    //         // Update the record
    //         $updateResult = DB::table('tbl_chainsaw_information')
    //             ->where('id', $id)
    //             ->update($updateData);

    //         DB::commit();

    //         return response()->json([
    //             'status' => $updateResult ? 'success' : 'error',
    //             'message' => $updateResult ? 'Application information updated successfully.' : 'No changes were made.',
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }









    public function updateApplicantFiles(Request $request, GoogleDriveService $driveService)
    {
        try {
            // ✅ Step 1. Validate input
            $validated = $request->validate([
                'application_id' => 'required|exists:tbl_application_checklist,id',
                'attachment_id' => 'required|integer|exists:tbl_application_attachments,id',
                'file' => 'required|file|max:2048',
                'name' => 'required|string'
            ]);

            // ✅ Step 2. Fetch application details
            $application = DB::table('tbl_application_checklist')
                ->where('id', $request->application_id)
                ->first();

            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found.'
                ], 404);
            }

            // ✅ Step 3. Fetch old attachment record
            $oldAttachment = DB::table('tbl_application_attachments')
                ->where('id', $request->attachment_id)
                ->first();

            if (!$oldAttachment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Attachment not found.'
                ], 404);
            }

            // Step 4. Folder map for file types
            $folderMap = [
                'permit' => ['folder' => 'Permit to Sell', 'prefix' => 'permit'],
                'mayors' => ['folder' => 'Mayors Permit', 'prefix' => 'mayors_permit'],
                'notarized' => ['folder' => 'Notarized Affidavit', 'prefix' => 'notarized_documents'],
                'official' => ['folder' => 'Official Receipts', 'prefix' => 'official_receipts'],
                'request' => ['folder' => 'Request Letter', 'prefix' => 'request_letter'],
                'secretary_certificate' => ['folder' => 'Secretary Certificate', 'prefix' => 'secretary_certificate'],
                'othersDocs' => ['folder' => 'Other supporting documents', 'prefix' => 'others'],
            ];

            $rawName = strtolower(trim($request->name));
            $fileType = null;

            foreach ($folderMap as $key => $map) {
                if (str_starts_with($rawName, $key)) {
                    $fileType = $key;
                    break;
                }
            }

            if (!$fileType) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid file type provided: {$rawName}",
                ], 400);
            }


            $subFolder = $folderMap[$fileType]['folder'];
            $filePrefix = $folderMap[$fileType]['prefix'];


            // ✅ Step 5. Build Google Drive folder path
            $folderPath = "CHAINSAW_PERMITTING/Company Applications/{$application->application_no}/{$subFolder}";

            // ✅ Step 6. Call service to replace file
            $result = $driveService->replaceAttachment(
                $folderPath,
                $oldAttachment->file_id,
                $request->file('file'),
                $application->application_no,
                $filePrefix
            );

            if (!$result['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Failed to replace the file on Google Drive.'
                ], 500);
            }

            // ✅ Step 7. Update database record
            DB::table('tbl_application_attachments')
                ->where('id', $request->attachment_id)
                ->update([
                    'file_name' => $result['file_name'],
                    'file_id' => $result['file_id'],
                    'file_url' => $result['file_url'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status' => true,
                'message' => 'File replaced successfully.',
                'data' => [
                    'file_name' => $result['file_name'],
                    'file_url' => $result['file_url'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating applicant file: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the file.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function returnApplication(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:tbl_application_checklist,id',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // 1️⃣ Update the application status in tbl_application_checklist
            DB::table('tbl_application_checklist')
                ->where('id', $request->application_id)
                ->update([
                    'application_status' => 0,
                    'tsd_chief_comments' => $request->reason,
                    'updated_at' => now(),
                ]);

            // 2️⃣ Insert log entry to tbl_application_comments
            DB::table('tbl_application_comments')->insert([
                'application_id' => $request->application_id,
                'signatory_id' => auth()->id(),
                'comments' => $request->reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'id' => auth()->id(),
                'status' => 'success',
                'message' => 'Application marked as returned successfully.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function view($id)
    {
        $application = ChainsawIndividualApplication::findOrFail($id);

        return Inertia::render('Applications/Form', [
            'application' => $application,
            'mode' => 'view'
        ]);
    }

    public function updateStatus(Request $request)
    {
        $app = ChainsawIndividualApplication::findOrFail($request->id);
        $app->application_status = $request->status;
        $app->save();

        return response()->json(['message' => 'Status updated successfully.']);
    }



    public function updateCompanyPayemnt(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Update the record
            $updateResult = DB::table('tbl_application_payment')
                ->where('application_id', $id) // use payload instead of route param
                ->update([
                    'official_receipt' => $request->input('official_receipt'),
                    'permit_fee' => $request->input('permit_fee'),
                    'date_of_payment' => now(),
                    'remarks' => $request->input('remarks'),
                    'updated_at' => now(),
                ]);
            DB::commit();

            return response()->json([
                'status' => $updateResult ? 'success' : 'error',
                'message' => $updateResult ? 'Payment info updated successfully' : 'No updates were made. Please check your data.',
            ], $updateResult ? 200 : 400);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function submitApplication(Request $request, $id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Update the record
    //         $updateResult = DB::table('tbl_application_checklist')
    //             ->where('id', $id) // use payload instead of route param
    //             ->update([
    //                 'application_status' => self::STATUS_FOR_REVIEW_EVALUATION,
    //                 'updated_at' => now(),
    //             ]);
    //         DB::commit();

    //         return response()->json([
    //             'status' => $updateResult ? 'success' : 'error',
    //             'message' => $updateResult ? 'Application updated successfully' : 'No updates were made. Please check your data.',
    //         ], $updateResult ? 200 : 400);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // // }
    // public function submitApplication(Request $request, $id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $user = auth()->user(); // logged-in user


    //         // Update application status
    //         $updateResult = DB::table('tbl_application_checklist')
    //             ->where('id', $id)
    //             ->update([
    //                 'application_status' => self::STATUS_FOR_REVIEW_EVALUATION,
    //                 'updated_at' => now(),
    //             ]);

    //         /**
    //          * CENRO → PENRO OFFICE MAPPING
    //          */
    //         $officeRoutingMap = [
    //             6 => 2,  // Sta. Cruz → PENRO Laguna
    //             7 => 3,  // Lipa → PENRO Batangas
    //             8 => 3,  // Calaca → PENRO Batangas
    //             9 => 5,  // Calauag → PENRO Quezon
    //             10 => 5, // Catanauan → PENRO Quezon
    //             11 => 5, // Tayabas → PENRO Quezon
    //             12 => 5, // Real → PENRO Quezon
    //         ];

    //         if (!isset($officeRoutingMap[$user->office_id])) {
    //             throw new \Exception("Routing not defined for office_id {$user->office_id}");
    //         }

    //         // Determine receiver office
    //         $receiverOfficeId = $officeRoutingMap[$user->office_id];

    //         // Get receiver user (any user from that PENRO office)
    //         $receiverUser = DB::table('users')
    //             ->where('office_id', $receiverOfficeId)
    //             ->orderBy('id', 'asc')
    //             ->first();

    //         if (!$receiverUser) {
    //             throw new \Exception("No receiver user found in office_id {$receiverOfficeId}");
    //         }

    //         // Insert routing record
    //         DB::table('tbl_application_routing')->insert([
    //             'application_id' => $id,
    //             'sender_id' => $user->id,
    //             'receiver_id' => $receiverUser->id,
    //             'action' => 'Submitted for Review',
    //             'remarks' => 'Application submitted by CENRO Staff',
    //             'is_read' => 0,
    //             'route_order' => 1,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Application submitted and routed successfully.',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function submitApplication(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user(); // logged-in user

            // Update status
            DB::table('tbl_application_checklist')
                ->where('id', $id)
                ->update([
                    'application_status' => self::STATUS_FOR_REVIEW_EVALUATION,
                    'updated_at' => now(),
                ]);

            /**
             * CENRO → PENRO OFFICE MAPPING
             */
            $officeRoutingMap = [
                6 => 2,  // Sta. Cruz → PENRO Laguna
                7 => 3,  // Lipa → PENRO Batangas
                8 => 3,  // Calaca → PENRO Batangas
                9 => 5,  // Calauag → PENRO Quezon
                10 => 5, // Catanauan → PENRO Quezon
                11 => 5, // Tayabas → PENRO Quezon
                12 => 5, // Real → PENRO Quezon
            ];

            // Validate routing
            if (!isset($officeRoutingMap[$user->office_id])) {
                throw new \Exception("Routing not defined for office_id {$user->office_id}");
            }

            // 1️⃣ FIRST ROUTE → CHIEF RPS
            $rps_chief = DB::table('users')
                ->where('office_id', $user->office_id)
                ->where('role_id', self::CHIEF_RPS) // chiefs only CENRO STA CRUZ
                ->orderBy('id', 'asc')
                ->first();

            if (!$rps_chief) {
                throw new \Exception("No CENRO Chief found in office_id {$user->office_id}");
            }

            // Insert routing for CENRO CHIEF
            DB::table('tbl_application_routing')->insert([
                'application_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $rps_chief->id,
                'action' => 'Submitted to CHIEF RPS',
                'remarks' => 'Waiting to received by CHIEF RPS',
                // 'remarks' => 'Waiting for endorsement to PENRO',
                'is_read' => 0,
                'route_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2️⃣ SECOND ROUTE → PENRO OFFICE
            // $penroOfficeId = $officeRoutingMap[$user->office_id];

            // $penroReceiver = DB::table('users')
            //     ->where('office_id', $penroOfficeId)
            //     ->orderBy('id', 'asc')
            //     ->first();

            // if (!$penroReceiver) {
            //     throw new \Exception("No PENRO user found in office_id {$penroOfficeId}");
            // }

            // Insert routing for PENRO
            // DB::table('tbl_application_routing')->insert([
            //     'application_id' => $id,
            //     'sender_id' => $cenroChief->id,
            //     'receiver_id' => $penroReceiver->id,
            //     'action' => 'Endorsed to PENRO',
            //     'remarks' => 'Endorsed by CENRO Chief',
            //     'is_read' => 0,
            //     'route_order' => 2,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Application submitted and routed successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



















    // public function updateApplication(Request $request, $id)
    // {
    //     try {
    //         DB::beginTransaction(); // Begin transaction at the start

    //         // Find the application
    //         $application = ChainsawIndividualApplication::findOrFail($id);

    //         // Clone all request data
    //         $data = $request->all();

    //         // Check and format the date_applied field using Carbon, then add 1 day
    //         if (!empty($data['date_applied'])) {
    //             $data['date_applied'] = Carbon::parse($data['date_applied'])
    //                 ->addDay()
    //                 ->format('Y-m-d');
    //         }
    //         if (!empty($data['permit_validity'])) {
    //             $data['permit_validity'] = Carbon::parse($data['permit_validity'])
    //                 ->addDay()
    //                 ->format('Y-m-d');
    //         }

    //         // Update the application table
    //         $application->update($data);

    //         // UPDATE CHAINSAW INFORMATION based on reference (e.g., application_id)
    //         $updateResult = DB::table('tbl_chainsaw_information')
    //             ->where('application_id', $id) // <-- IMPORTANT: Add WHERE condition
    //             ->update([
    //                 'permit_chainsaw_no' => $request->input('permit_chainsaw_no'),
    //                 'brand' => $request->input('brand'),
    //                 'model' => $request->input('model'),
    //                 'quantity' => $request->input('quantity'),
    //                 'updated_at' => now(),
    //             ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => $updateResult
    //                 ? 'Application and chainsaw information updated successfully.'
    //                 : 'No changes detected or record not found.',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack(); // Rollback transaction if something goes wrong

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


}
