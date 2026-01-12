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
            ->where('role_id',self::IMPLEMENTING_PENRO)                // PENRO CHIEF role
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
        $user = auth()->user();
        $id = $request->id;

        DB::beginTransaction();

        try {
            // 1️⃣ Validate Application
            $app = ChainsawIndividualApplication::findOrFail($id);

            // Mark as endorsed by PENRO
            $app->application_status = self::STATUS_FOR_RECEIVED_FUS;
            $app->date_endorsed_penro = now();
            $app->save();

            // 2️⃣ Identify PENRO Chief (sender)
            $lpdd_fus = DB::table('users')
                ->where('role_id', self::LPDD_FUS)               
                ->orderBy('id', 'asc')
                ->first();

            if (!$lpdd_fus) {
                throw new \Exception("No PENRO Chief found in office_id {$user->office_id}");
            }

        

            // 4️⃣ Insert routing: PENRO Chief → RPS Chief
            DB::table('tbl_application_routing')->insert([
                'application_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $lpdd_fus->id,
                'action' => 'Endorsed to LPDD/FUS',
                'remarks' => 'Waiting to be received by LPDD/FUS',
                'is_read' => 0,
                'route_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // 5️⃣ Update application status
            $app->application_status = $request->status;
            $app->save();

            return response()->json([
                'message' => 'Application successfully endorsed to RPS Chief.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }




}
