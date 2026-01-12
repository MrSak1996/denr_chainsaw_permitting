<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Application\ChainsawIndividualApplication;


class PENROController extends Controller
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

    //implementing penro
    const TECHNICAL_STAFF = 1;
    const CHIEF_RPS = 8;
    const CHIEF_TSD = 10;
    const IMPLEMENTING_PENRO = 3;
    const LPDD_FUS = 4;

    /**
     * Mapping of statuses to their labels
     */


    /**
     * Fetch applications dynamically by status
     */
    public function receivedEndorsedApplication(Request $request)
    {
        $user = auth()->user();
        $id = $request->id;

        $app = ChainsawIndividualApplication::findOrFail($request->id);
        $app->application_status = self::STATUS_RECEIVED_PENRO_CHIEF;
        $app->is_penro_chief_received = 1;
        $app->date_received_penro_chief = now();
        $app->save();

        DB::beginTransaction();
        $penroChief = DB::table('users')
            ->where('office_id', 2)   // PENRO office
            ->where('role_id', self::IMPLEMENTING_PENRO)                // PENRO CHIEF role
            ->orderBy('id', 'asc')
            ->first();

        if (!$penroChief) {
            throw new \Exception("No PENRO Chief found in office_id {$user->office_id}");
        }


        // 📝 Insert routing record
        DB::table('tbl_application_routing')->insert([
            'application_id' => $id,
            'sender_id' => $user->id,
            'receiver_id' => $penroChief->id,
            'action' => 'Received by the PENRO',
            'remarks' => 'Approve recommendation and sign endorsement to PENR Office/Regional Office',
            'is_read' => 1,
            'route_order' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        return response()->json(['message' => 'Application Received by PENRO.']);
    }

    public function endorseToFUS(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tbl_application_checklist,id',
        ]);

        $officeRoutingMap = [
            2  => 13, // PENRO Laguna → Regional Office
            6  => 2,  // Sta. Cruz → PENRO Laguna
            7  => 3,  // Lipa → PENRO Batangas
            8  => 3,  // Calaca → PENRO Batangas
            9  => 5,  // Calauag → PENRO Quezon
            10 => 5,  // Catanauan → PENRO Quezon
            11 => 5,  // Tayabas → PENRO Quezon
            12 => 5,  // Real → PENRO Quezon
            13 => 13, // Regional Office
        ];

        $user = auth()->user();

        DB::beginTransaction();

        try {
            /** 1️⃣ Validate routing */
            if (!isset($officeRoutingMap[$user->office_id])) {
                throw new \Exception("Routing not defined for office_id {$user->office_id}");
            }

            $targetOfficeId = $officeRoutingMap[$user->office_id];

            /** 2️⃣ Lock and update application */
            $app = ChainsawIndividualApplication::lockForUpdate()
                ->findOrFail($request->id);

            $app->update([
                'application_status'     => self::STATUS_ENDORSED_LPDD_FUS,
                'date_endorsed_penro'    => now(),
            ]);

            /** 3️⃣ Find LPDD/FUS receiver */
            $lpdd_fus = DB::table('users')
                ->where('office_id', $targetOfficeId)
                ->where('role_id', self::LPDD_FUS)
                ->orderBy('id')
                ->first();

            if (!$lpdd_fus) {
                throw new \Exception(
                    "No LPDD/FUS user found in office_id {$targetOfficeId}"
                );
            }

            /** 4️⃣ Insert routing record */
            DB::table('tbl_application_routing')->insert([
                'application_id' => $app->id,
                'sender_id'      => $user->id,
                'receiver_id'    => $lpdd_fus->id,
                'action'         => 'Endorsed to LPDD/FUS',
                'remarks'        => 'Waiting to be received by LPDD/FUS',
                'is_read'        => 0,
                'route_order'    => 7,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'          => 'success',
                'message'         => 'Application endorsed to LPDD/FUS successfully.',
                'application_id'  => $app->id,
                'current_status'  => $app->application_status,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function returnApplication(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'remarks' => 'required|string',
            'returnTo' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $id = $request->id;

            // 🔁 Return-to → Status mapping
            $returnStatusMap = [
                1 => 24,
                8 => 18,
                3 => 19,
                4 => 20,
                5 => 21,
                6 => 22,
                7 => 23,
                10 => 19
            ];

            if (!isset($returnStatusMap[$request->returnTo])) {
                throw new \Exception('Invalid return destination.');
            }

            $statusId = $returnStatusMap[$request->returnTo];

            // 1️⃣ Update application
            $app = ChainsawIndividualApplication::findOrFail($id);
            $app->application_status = $statusId;
            $app->date_returned = now();
            $app->save();

            // 2️⃣ Insert routing history
            DB::table('tbl_application_routing')->insert([
                'application_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $request->returnTo, // optional if returning to pool
                'action' => 'Returned by PENRO Chief',
                'comments' => $request->remarks,
                'is_read' => 0,
                'route_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Application returned successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
