<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment\PaymentModel;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;


class PaymentController extends Controller
{
    public function insert_payment(Request $request, GoogleDriveService $driveService)
    {
        $application = ChainsawIndividualApplication::where('application_no', $request->input('application_no'))->first();
        if (!$application) {
            return response()->json(['error' => 'Application not found.'], 404);
        }

        $application_id = $application->id;

        $payment = PaymentModel::create([
            'application_id' => $application_id,
            'official_receipt' => $request->input('official_receipt'),
            'permit_fee' => $request->input('permit_fee'),
            'date_of_payment' => now()
        ]);

          $filesToUpload = [
                'or_copy' => 'Official Receipt',
            ];

        $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $request->input('application_no');
        $result = $driveService->storeAttachments($request, $application_id, $folderPath, $filesToUpload);
        
        return response()->json([
                'message' => 'Payment inserted successfully',
                'data'  => $payment,
                'google_drive' => $result,
            ], 201);
    }
}
