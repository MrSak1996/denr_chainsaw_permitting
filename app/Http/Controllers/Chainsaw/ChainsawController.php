<?php

namespace App\Http\Controllers\Chainsaw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chainsaw\ChainsawInformation;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;
use App\Models\Chainsaw\ChainsawBrand;
use App\Models\Chainsaw\ChainsawModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class ChainsawController extends Controller
{
    const STATUS_DRAFT = 1;
    const STATUS_FOR_REVIEW_EVALUATION = 2;

    const STATUS_ENDORSED_CENRO_CHIEF = 3;
    const STATUS_ENDORSED_RPS_CHIEF = 4;
    const STATUS_ENDORSED_TSD_CHIEF = 5;
    const STATUS_ENDORSED_PENRO = 6;
    const STATUS_ENDORSED_LPDD_FUS = 7;
    const STATUS_ENDORSED_ARDTS = 8;
    const STATUS_APPROVED_BY_RED = 9;

    const STATUS_RECEIVED_CENRO_CHIEF = 10;
    const STATUS_RECEIVED_CHIEF_RPS = 11;
    const STATUS_RECEIVED_TSD_CHIEF = 12;
    const STATUS_RECEIVED_PENRO_CHIEF = 13;
    const STATUS_RECEIVED_FUS = 14;
    const STATUS_RECEIVED_ARDTS = 15;
    const STATUS_RECEIVED_RED = 16;

    const STATUS_RETURN_TO_CENRO_CHIEF = 17;
    const STATUS_RETURN_TO_RPS_CHIEF = 18;
    const STATUS_RETURN_TO_TSD_CHIEF = 19;
    const STATUS_RETURN_TO_PENRO = 20;
    const STATUS_RETURN_TO_LPDD_FUS = 21;
    const STATUS_RETURN_TO_ARDTS = 22;
    const STATUS_RETURN_TO_RED = 23;
    const STATUS_RETURN_TO_TECHNICAL_STAFF = 24;
    const CHIEF_RPS = 8;


    public function insertChainsawInfo(Request $request, GoogleDriveService $driveService)
    {
        DB::beginTransaction();

        try {
            $application = ChainsawIndividualApplication::findOrFail($request->id);

            // ✅ 1. Save brands + models
            $brands = json_decode($request->brands, true);

            if (!is_array($brands)) {
                return response()->json([
                    'message' => 'Invalid brands payload'
                ], 422);
            }

            foreach ($brands as $brandData) {
                $brand = ChainsawBrand::create([
                    'application_id' => $application->id,
                    'brand_name' => $brandData['name'],
                ]);

                foreach ($brandData['models'] as $modelData) {
                    ChainsawModels::create([
                        'brand_id' => $brand->id,
                        'model' => $modelData['model'],
                        'quantity' => $modelData['quantity'],
                    ]);
                }
            }


            // ✅ 2. Upload files ONCE
            $filesToUpload = [
                'mayorDTI' => 'Mayors Permit',
                'affidavit' => 'Notarized Affidavit',
                'otherDocs' => 'Other supporting documents',
                'permit' => 'Permit to Sell'
            ];

            $folderPath = match ($request->applicant_type) {
                'Individual' => "CHAINSAW_PERMITTING/Individual Applications/{$application->application_no}",
                'Company' => "CHAINSAW_PERMITTING/Company Applications/{$application->application_no}",
                default => "CHAINSAW_PERMITTING/Other/{$application->application_no}",
            };

            $chainsaw = ChainsawInformation::create([
                'application_id' => $application->id,
                'engine_serial_no' => $request->input('engine_serial_no'),
                'quantity' => $request->input('quantity'),
                'supplier_name' => $request->input('supplier_name'),
                'supplier_address' => $request->input('supplier_address'),
                'purpose' => $request->input('purpose'),
                'permit_validity' => Carbon::parse($request->permit_validity, 'Asia/Manila')->format('Y-m-d'),
                'other_details' => $request->input('other_details'),
            ]);
            $uploadResult = $driveService->storeAttachments(
                $application->application_no,
                $request,
                $application->id,
                $folderPath,
                $filesToUpload
            );

            DB::commit();

            return response()->json([
                'message' => 'Chainsaw application saved successfully',
                'application_id' => $application->id,
                'google_drive' => $uploadResult,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to save chainsaw application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function insertChainsawInfo(Request $request, GoogleDriveService $driveService)
    // {
    //     try {
    //         $application = ChainsawIndividualApplication::where('id', $request->input('id'))->first();
    //         if (!$application) {
    //             return response()->json(['error' => $request->input('application_no')], 404);
    //         }

    //         $application_id = $application->id;
    //         $application_no = $application->application_no;
    //         $permit_no = $application->permit_no;

    //        


    //         $filesToUpload = [
    //             'mayorDTI' => 'Mayors Permit',
    //             'affidavit' => 'Notarized Affidavit',
    //             'otherDocs' => 'Other supporting documents',
    //             'permit' => 'Permit to Sell'
    //         ];

    //         $applicantType = $request->input('applicant_type');
    //         $folderPath = $applicantType === 'individual'
    //             ? "CHAINSAW_PERMITTING/Individual Applications/{$application_no}"
    //             : ($applicantType === 'company'
    //                 ? "CHAINSAW_PERMITTING/Company Applications/{$application_no}"
    //                 : ($applicantType === 'government'
    //                     ? "CHAINSAW_PERMITTING/Government Applications/{$application_no}"
    //                     : "CHAINSAW_PERMITTING/Other/{$application_no}"
    //                 )
    //             );

    //         $result = $driveService->storeAttachments($application_no, $request, $application_id, $folderPath, $filesToUpload);

    //         return response()->json([
    //             'message' => 'Chainsaw information inserted successfully',
    //             'data' => $chainsaw,
    //             'google_drive' => $result,
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while processing your request.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function updateApplicantDetails(Request $request, $id)
    {
        try {
            $application = ChainsawIndividualApplication::findOrFail($id);

            // Clone all request data
            $data = $request->all();

            if (!empty($data['date_applied'])) {
                // Parse the ISO 8601 format and add 1 day
                $data['date_applied'] = Carbon::parse($data['date_applied'])
                    ->addDay() // Add 1 day
                    ->format('Y-m-d'); // Format as Y-m-d
            }

            $application->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Applicant details updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateChainsawInformation(Request $request, $id)
    {
        try {
            DB::beginTransaction();


            $permitValidity = $request->input('permit_validity');
            if (!empty($permitValidity)) {
                $permitValidity = Carbon::parse($permitValidity)
                    ->addDay()
                    ->format('Y-m-d');
            }

            // Update the record
            $updateResult = DB::table('tbl_chainsaw_information')
                ->where('application_id', $id) // use payload instead of route param
                ->update([
                    'permit_chainsaw_no' => $request->input('permit_chainsaw_no'),
                    'brand' => $request->input('brand'),
                    'model' => $request->input('model'),
                    'quantity' => $request->input('quantity'),
                    'supplier_name' => $request->input('supplier_name'),
                    'supplier_address' => $request->input('supplier_address'),
                    'purpose' => $request->input('purpose'),
                    'permit_validity' => $permitValidity,
                    'other_details' => $request->input('other_details'),
                    'updated_at' => now(),
                ]);




            DB::commit();

            return response()->json([
                'status' => $updateResult ? 'success' : 'error',
                'message' => $updateResult ? 'Chainsaw info updated successfully' : 'No updates were made. Please check your data.',
            ], $updateResult ? 200 : 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function endorseApplication(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tbl_application_checklist,id',
        ]);

        $officeRoutingMap = [
            6 => 2,  // Sta. Cruz → PENRO Laguna
            7 => 3,  // Lipa → PENRO Batangas
            8 => 3,  // Calaca → PENRO Batangas
            9 => 5,  // Calauag → PENRO Quezon
            10 => 5, // Catanauan → PENRO Quezon
            11 => 5, // Tayabas → PENRO Quezon
            12 => 5, // Real → PENRO Quezon
            13 => 13 // Regional Office
        ];

        $user = auth()->user();
        DB::beginTransaction();

        try {
            $app = ChainsawIndividualApplication::lockForUpdate()->findOrFail($request->id);

            $app->update([
                'application_status' => self::STATUS_ENDORSED_RPS_CHIEF,
                'date_endorsed_tsd_chief' => now(),
            ]);

            $penroChief = DB::table('users')
                ->where('office_id', $user->office_id)
                ->where('role_id', 8)
                ->first();

            if (!$penroChief) {
                throw new \Exception("No PENRO found in this office.");
            }

            DB::table('tbl_application_routing')->insert([
                'application_id' => $app->id,
                'sender_id' => $user->id,
                'receiver_id' => $penroChief->id,
                'action' => 'Endorsed to RPS Chief',
                'remarks' => 'Waiting to be received by RPS Chief',
                'is_read' => 0,
                'route_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Application endorsed successfully.',
                // 'application_id' => $app->id,
                // 'current_status' => $app->application_status,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function getChainsawBrandsWithModels($applicationId)
    {
        $rows = DB::table('chainsaw_brands as cb')
            ->leftJoin('chainsaw_models as cm', 'cm.brand_id', '=', 'cb.id')
            ->where('cb.application_id', $applicationId)
            ->select(
                'cb.id as brand_id',
                'cb.brand_name',
                'cm.id as model_id',
                'cm.model',
                'cm.quantity'
            )
            ->orderBy('cb.id')
            ->get();

        // Group into Vue-friendly structure
        $brands = [];

        foreach ($rows as $row) {
            if (!isset($brands[$row->brand_id])) {
                $brands[$row->brand_id] = [
                    'name'   => $row->brand_name,
                    'models' => []
                ];
            }

            if ($row->model_id) {
                $brands[$row->brand_id]['models'][] = [
                    'model'    => $row->model,
                    'quantity' => $row->quantity
                ];
            }
        }

        // Reset array keys
        return response()->json(array_values($brands));
    }
    // public function updateApplicationStatus(Request $request, $id)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $user = auth()->user();

    //         if (!$user) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Unauthenticated user.'
    //             ], 401);
    //         }

    //         $updateResult = DB::table('tbl_application_checklist')
    //             ->where('id', $id)
    //             ->update([
    //                 'application_status' => $request->input('status'),
    //                 'updated_at' => now(),
    //             ]);

    //         if (!$updateResult) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Application not found or no changes made.'
    //             ], 400);
    //         }

    //         $receiver = DB::table('users')
    //             ->where('office_id', 6)
    //             ->where('role_id', self::CHIEF_RPS)
    //             ->orderBy('id', 'asc')
    //             ->first();

    //         if (!$receiver) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No CENRO CHIEF (RPS) found.'
    //             ], 404);
    //         }

    //         DB::table('tbl_application_routing')->insert([
    //             'application_id' => $id,
    //             'sender_id' => $user->id,
    //             'receiver_id' => $receiver->id,
    //             'action' => 'Endorsed to the CHIEF RPS',
    //             'remarks' => 'For evaluation of CHIEF RPS',
    //             'is_read' => 0,
    //             'route_order' => 1,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Application endorsed to CHIEF RPS successfully.'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
