<?php

namespace App\Http\Controllers\Chainsaw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chainsaw\ChainsawInformation;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;

class ChainsawController extends Controller
{
    public function insertChainsawInfo(Request $request, GoogleDriveService $driveService)
    {
        try {
            $validated = $request->validate([
                'application_no'       => 'required|string',
                'file_id'              => 'nullable|integer',
                'permit_chainsaw_no'   => 'required|string|max:100',
                'brand'                => 'required|string|max:100',
                'model'                => 'required|string|max:100',
                'quantity'             => 'required|integer|min:1',
                'supplier_name'        => 'required|string|max:255',
                'purpose'              => 'required|string|max:255',
                'permit_validity'      => 'required|date',
                'others_details'       => 'nullable|string|max:1000',
            ]);

            // 🟡 Fetch the application by application_no
            $application = ChainsawIndividualApplication::where('application_no', $request->input('application_no'))->first();
            if (!$application) {
                return response()->json(['error' => 'Application not found.'], 404);
            }

            $application_id = $application->id;

            $chainsaw = ChainsawInformation::create([
                'application_id'      => $application_id,
                'permit_chainsaw_no'  =>  $request->input('permit_chainsaw_no'),
                'brand'               =>  $request->input('brand'),
                'model'               =>  $request->input('model'),
                'quantity'            =>  $request->input('quantity'),
                'supplier_name'       =>  $request->input('supplier_name'),
                'purpose'             =>  $request->input('purpose'),
                'permit_validity'     =>  $request->input('permit_validity'),
                'other_details'       =>  $request->input('other_details'),
            ]);


            $filesToUpload = [
                'mayorDTI' => 'Mayors Permit',
                'affidavit' => 'Notarized Affidavit',
                'otherDocs' => 'Other supporting documents',
                'permit' => 'Permit to Sell'
            ];

            $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $request->input('application_no');
            $result = $driveService->storeAttachments($request, $application_id, $folderPath, $filesToUpload);

            return response()->json([
                'message' => 'Chainsaw information inserted successfully',
                'data'  => $chainsaw,
                'google_drive'    => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
