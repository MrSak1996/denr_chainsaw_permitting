<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\ApplicantAttachments\AttachmentsModel;
use Illuminate\Http\Request;
use App\Models\Payment\PaymentModel;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;
use Illuminate\Support\Facades\DB;


class PaymentController extends Controller
{
    public function insert_payment(Request $request, GoogleDriveService $driveService)
    {
        $id = $request->input('application_id', $request->input('id'));

        $application = ChainsawIndividualApplication::where('id', $id)->first();
        if (!$application) {
            return response()->json(['error' => 'Application not found.'], 404);
        }
        $attachment = AttachmentsModel::where('application_id', $application->id)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
        if (!$application) {
            return response()->json(['error' => 'Application not found.'], 404);
        }
        $application_id = $application->id;
        $application_no = $application->application_no;
        $permit_no = $application->permit_no;

        $payment = PaymentModel::create([
            'application_id' => $application_id,
            'application_attachment_id' => $attachment->id,
            'official_receipt' => $request->input('official_receipt'),
            'permit_fee' => $request->input('permit_fee'),
            'remarks' => $request->input('remarks'),
            'date_of_payment' => now()
        ]);

        $filesToUpload = [
            'or_copy' => 'Official Receipt',
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
            'message' => 'Payment inserted successfully',
            'application_id' => $application_id,
            'data' => $payment,
            'google_drive' => $result,
        ], 201);
    }

    public function updatePaymentInformation(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Update the record
            $updateResult = DB::table('tbl_application_payment')
                ->where('application_id', 2) // use payload instead of route param
                ->update([
                    'official_receipt' => $request->input('official_receipt'),
                    'permit_fee' => $request->input('permit_fee'),
                    'date_of_payment' =>now(),
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

}
