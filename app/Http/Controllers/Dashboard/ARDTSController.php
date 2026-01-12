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
  

    public function receivedApplication(Request $request)
    {
        $user = auth()->user();
        $id = $request->id;

        $app = ChainsawIndividualApplication::findOrFail($request->id);
        $app->application_status = self::STATUS_RECEIVED_ARDTS;
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
            throw new \Exception("No accounts found in office_id {$user->office_id}");
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
