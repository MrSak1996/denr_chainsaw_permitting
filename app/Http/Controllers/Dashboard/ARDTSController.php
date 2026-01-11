<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Application\ChainsawIndividualApplication;


class ARDTSController extends Controller
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
        $user = auth()->user();
        $id = $request->id;

        $app = ChainsawIndividualApplication::findOrFail($request->id);
        $app->is_ardts_received = 1;
        $app->date_received_ardts = now();
        $app->save();

        DB::beginTransaction();
        // 1️⃣ FIRST ROUTE → CENRO CHIEF
        $receiver = DB::table('users')
            ->where('office_id', $user->office_id)
            ->where('role_id', self::ARD_TS)
            ->orderBy('id', 'asc')
            ->first();

        if (!$receiver) {
            throw new \Exception("No CENRO Chief found in office_id {$user->office_id}");
        }




        // Insert routing for CENRO CHIEF
        DB::table('tbl_application_routing')->insert([
            'application_id' => $id,
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'action' => 'Received by the ARD TS',
            'remarks' => 'For approval of the Regional Executive Director',
            'is_read' => 1,
            'route_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::commit();



        return response()->json(['message' => 'Application Received.']);
    }

    public function endorseApplication(Request $request)
    {
        $user = auth()->user();
        $id = $request->id;
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

        // 1️⃣ Update application as received by CENRO Chief
        $app = ChainsawIndividualApplication::findOrFail($id);
        $app->application_status = self::STATUS_ENDORSED_RED;
        $app->date_endorse_red = now();
        $app->save();

        DB::beginTransaction();

        try {
            // Validate routing
            if (!isset($officeRoutingMap[$user->office_id])) {
                throw new \Exception("Routing not defined for office_id {$user->office_id}");
            }

            // 2️⃣ Get PENRO Chief
            $red = DB::table('users')
                ->where('role_id', self::RED) // role 3 = Chief
                ->orderBy('id', 'asc')
                ->first();

            if (!$red) {
                throw new \Exception("No RED found in office_id");
            }

            // Insert routing: CENRO Chief → PENRO Chief
            DB::table('tbl_application_routing')->insert([
                'application_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $red->id,
                'action' => 'Endorsed to Regional Executive Director',
                'remarks' => 'Waiting to be approced by  Regional Executive Director',
                'is_read' => 0,
                'route_order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

          


            return response()->json([
                'message' => 'Application endorsed to ARD TS successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

    }

    //
}
