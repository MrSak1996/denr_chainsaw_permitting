<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Application\ChainsawIndividualApplication;

class RegionalExecutiveController extends Controller
{
    // Define status constants
    const STATUS_DRAFT = 0;
    const STATUS_RETURN_FOR_COMPLIANCE = 0;
    const STATUS_FOR_REVIEW_EVALUATION = 1;
    const STATUS_ENDORSED_CENRO = 2;
    const STATUS_ENDORSED_PENRO = 3;
    const STATUS_ENDORSED_RO = 4;

    const STATUS_APPROVED = 5;
    const STATUS_FOR_REVIEW_FUS = 10;

    const STATUS_ENDORSED_ARDTS = 12;
    const STATUS_ENDORSED_RED = 13;

    const STATUS_RECEIVED_CHIEF_RPS = 8;

    //implementing penro
    const TECHNICAL_STAFF = 1;
    const ARD_TS = 6;
    const RED = 7;
    const CHIEF_RPS = 8;
    const CHIEF_TSD = 10;
    const IMPLEMENTING_PENRO = 3;

    /**
     * Mapping of statuses to their labels
     */
    protected $statusMap = [
        self::STATUS_DRAFT => 'Draft Application',
        self::STATUS_RETURN_FOR_COMPLIANCE => 'Return for Compliance',
        self::STATUS_FOR_REVIEW_EVALUATION => 'For Review / Evaluation',
        self::STATUS_ENDORSED_CENRO => 'Endorsed to CENRO',
        self::STATUS_ENDORSED_PENRO => 'Endorsed to PENRO',
        self::STATUS_ENDORSED_RO => 'Endorsed to R.O',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_FOR_REVIEW_FUS => 'For Review by FUS',
        self::STATUS_ENDORSED_ARDTS => 'Endorsed to ARD TS'
    ];

    public function receivedApplication(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            $id = $request->id;

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ GET APPLICATION
            |--------------------------------------------------------------------------
            */
            $app = ChainsawIndividualApplication::lockForUpdate()->findOrFail($id);

            // Prevent re-generating permit number
            if (!empty($app->permit_no)) {
                throw new \Exception('Permit number already generated for this application.');
            }

            // Mark as received by RED
            $app->is_red_received = 1;
            $app->date_received_red = now();

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ DETERMINE PROVINCE SUFFIX
            |--------------------------------------------------------------------------
            */
            $province = strtoupper(
                $app->company_c_province ?? $app->applicant_province_c
            );

            $provinceSuffix = match ($province) {
                '21' => 'C', // Cavinti / Cavinti code?
                '34' => 'L', // Laguna
                '10' => 'B', // Batangas
                '58' => 'R', // Rizal
                '56' => 'Q', // Quezon
                default => 'X',
            };

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ GENERATE PERMIT NUMBER (INCREMENTAL PER RED RECEIPT)
            |--------------------------------------------------------------------------
            */
            $datePart = now()->format('mdY'); // 01082025

            $lastPermit = ChainsawIndividualApplication::whereNotNull('permit_no')
                ->where('permit_no', 'like', "DENR-IV-A-{$datePart}-%")
                ->orderBy('permit_no', 'desc')
                ->lockForUpdate() // 🔒 prevents duplicate sequence
                ->first();

            if ($lastPermit) {
                // Extract sequence (01 from DENR-IV-A-01082025-01B)
                preg_match('/-(\d{2})[A-Z]$/', $lastPermit->permit_no, $matches);
                $nextSequence = intval($matches[1]) + 1;
            } else {
                $nextSequence = 1;
            }

            $sequence = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            $permitNo = "DENR-IV-A-{$datePart}-{$sequence}{$provinceSuffix}";

            // Save permit number
            $app->permit_no = $permitNo;
            $app->save();

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ FIND REGIONAL EXECUTIVE DIRECTOR (RECEIVER)
            |--------------------------------------------------------------------------
            */
            $receiver = DB::table('users')
                ->where('office_id', $user->office_id)
                ->where('role_id', self::RED)
                ->orderBy('id', 'asc')
                ->first();

            if (!$receiver) {
                throw new \Exception('No Regional Executive Director found.');
            }

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ INSERT ROUTING HISTORY
            |--------------------------------------------------------------------------
            */
            DB::table('tbl_application_routing')->insert([
                'application_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $receiver->id,
                'action' => 'Received and approved by the Regional Executive Director',
                'remarks' => "Permit No generated: {$permitNo}",
                'is_read' => 1,
                'route_order' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Application received, approved, and permit number generated successfully.',
                'permit_no' => $permitNo,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
