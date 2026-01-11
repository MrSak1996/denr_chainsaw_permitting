<?php

namespace App\Http\Controllers\Chainsaw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chainsaw\ChainsawInformation;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class ChainsawController extends Controller
{
    public function insertChainsawInfo(Request $request, GoogleDriveService $driveService)
    {
        try {
            $application = ChainsawIndividualApplication::where('id', $request->input('id'))->first();
            if (!$application) {
                return response()->json(['error' => $request->input('application_no')], 404);
            }

            $application_id = $application->id;
            $application_no = $application->application_no;
            $permit_no = $application->permit_no;

            $chainsaw = ChainsawInformation::create([
                'application_id' => $application_id,
                'brand' => $request->input('brand'),
                'model' => $request->input('model'),
                'engine_serial_no' => $request->input('engine_serial_no'),
                'quantity' => $request->input('quantity'),
                'supplier_name' => $request->input('supplier_name'),
                'supplier_address' => $request->input('supplier_address'),
                'purpose' => $request->input('purpose'),
                'permit_validity' => $request->input('permit_validity'),
                'other_details' => $request->input('other_details'),
            ]);


            $filesToUpload = [
                'mayorDTI' => 'Mayors Permit',
                'affidavit' => 'Notarized Affidavit',
                'otherDocs' => 'Other supporting documents',
                'permit' => 'Permit to Sell'
            ];

            $applicantType = $request->input('applicant_type');
            $folderPath = $applicantType === 'individual'
                ? "CHAINSAW_PERMITTING/Individual Applications/{$application_no}"
                : ($applicantType === 'company'
                    ? "CHAINSAW_PERMITTING/Company Applications/{$application_no}"
                    : ($applicantType === 'government'
                        ? "CHAINSAW_PERMITTING/Government Applications/{$application_no}"
                        : "CHAINSAW_PERMITTING/Other/{$application_no}"
                    )
                );

            $result = $driveService->storeAttachments($application_no, $request, $application_id, $folderPath, $filesToUpload);

            return response()->json([
                'message' => 'Chainsaw information inserted successfully',
                'data' => $chainsaw,
                'google_drive' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                ->where('application_id',$id) // use payload instead of route param
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


    public function updateApplicationStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Perform the update
            $updateResult = DB::table('tbl_application_checklist')
                ->where('id', $id)
                ->update([
                    'application_status' => $request->input('status'), // fixed typo
                    'updated_at' => now(),
                ]);

            DB::commit();

            // Return success or error based on the update result
            if ($updateResult) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Application status updated successfully.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No updates were made. Please check the application ID.'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
