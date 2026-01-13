<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Application\ChainsawIndividualApplication;


class RPSChiefDashboardController extends Controller
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
    const CHIEF_RPS = 8;
    const CHIEF_TSD = 10;
    const IMPLEMENTING_PENRO = 3;



    /**
     * Mapping of statuses to their labels
     */
    protected $statusMap = [
        // self::STATUS_DRAFT => 'Draft Application',
        // self::STATUS_RETURN_TO_TECHNICAL_STAFF => 'Return for Compliance',
        // self::STATUS_FOR_REVIEW_EVALUATION => 'For Review / Evaluation',
        // self::STATUS_ENDORSED_CENRO => 'Endorsed to CENRO',
        // self::STATUS_ENDORSED_PENRO => 'Endorsed to PENRO',
        // self::STATUS_ENDORSED_RO => 'Endorsed to R.O',
        // self::STATUS_APPROVED => 'Approved',
        // self::STATUS_RECEIVED_CHIEF_RPS => 'Received by Chief RPS'
    ];

    /**
     * Fetch applications dynamically by status
     */
    public function getApplicationsByStatus(Request $request)
    {
        // DB::enableQueryLog();

        $request->validate([
            'status' => 'required|integer',
        ]);

        $office_id = (int) $request->query('office_id');
        $status = (int) $request->input('status');
        $statusFilter = [$status];
        switch ($office_id) {
            case 2:
                $officeFilter = [6];
                break;
            case 13:
                $officeFilter = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]; // if region show all offices
                break;

            default:
                $officeFilter = [$office_id];
                break;
        }
        switch ($status) {
            case '4':
                $statusFilter = [
                    self::STATUS_RECEIVED_CHIEF_RPS,   // 11
                    self::STATUS_ENDORSED_TSD_CHIEF,   // 5
                    self::STATUS_RETURN_TO_RPS_CHIEF,  // 18
                ];
                break;
            case '5':
                $statusFilter = [
                    self::STATUS_RECEIVED_TSD_CHIEF,   // 12
                    self::STATUS_ENDORSED_TSD_CHIEF,   // 5
                    self::STATUS_ENDORSED_PENRO,   // 6
                    self::STATUS_RETURN_TO_TSD_CHIEF,  // 19
                ];
                break;
            case '6':
                $statusFilter = [
                    self::STATUS_RECEIVED_PENRO_CHIEF,   // 13
                    self::STATUS_ENDORSED_PENRO,   // 6
                    self::STATUS_ENDORSED_LPDD_FUS, //7
                    self::STATUS_RETURN_TO_PENRO,  // 20
                ];
                break;
            case '7':
                $statusFilter = [
                    self::STATUS_RECEIVED_FUS,   // 14
                    self::STATUS_ENDORSED_LPDD_FUS,   // 7
                    self::STATUS_ENDORSED_ARDTS, //8
                    self::STATUS_RETURN_TO_LPDD_FUS,  // 21
                ];
                break;

            case '8':
                $statusFilter = [
                    self::STATUS_RECEIVED_ARDTS,   // 15
                    self::STATUS_ENDORSED_ARDTS,   // 8
                    self::STATUS_APPROVED_BY_RED, //9
                    self::STATUS_RETURN_TO_ARDTS,  // 22
                ];
            case '25':
                $statusFilter = [
                    self::STATUS_RECEIVED_RED,   // 16
                    self::STATUS_APPROVED_BY_RED,   // 9
                    self::STATUS_RETURN_TO_RED,  // 23
                    self::STATUS_RETURN_TO_ARDTS,
                    25

                ];
                break;

            default:
                # code...
                break;
        }



        // }

        $applicationDetails = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->leftJoin('tbl_status as s', 'ac.application_status', '=', 's.id')
            ->leftJoin('users as u', 'u.id', '=', 'ac.encoded_by')
            ->leftJoin('tbl_office as o', 'o.id', '=', 'u.office_id')
            ->select(
                'ac.id',
                'ac.return_reason',
                'u.name as encoded_by',
                'u.office_id',
                's.status_title',
                'ac.application_status',
                'ac.application_type',
                'ac.application_no',
                'ac.transaction_type',
                'ac.classification',
                'ci.permit_chainsaw_no',
                'ci.brand',
                'ci.model',
                'ci.quantity',
                'ci.purpose',
                'ci.other_details',
                'ap.official_receipt',
                'ap.permit_fee',
                'ap.date_of_payment',
                'ci.permit_validity',
                'ac.is_rps_chief_received',
                'ac.is_tsd_chief_received',
                'ac.is_red_received',
                'ac.is_penro_chief_received',
                'ac.is_fus_received',
                'ac.created_at',
                'ac.date_applied'
            )
            ->whereIn('ac.application_status', $statusFilter)
            ->whereIn('u.office_id', $officeFilter)
            ->orderBy('ac.id', 'desc')
            ->get()

            ->map(function ($item) {
                // ✅ Format date fields
                foreach (['created_at', 'date_applied', 'date_of_payment', 'permit_validity'] as $field) {
                    $item->$field = $item->$field
                        ? Carbon::parse($item->$field)->format('F d, Y')
                        : null;
                }
                return $item;
            });

        // dd(DB::getQueryLog());

        // ✅ Get counts for every status
        $statusCounts = DB::table('tbl_application_checklist')
            ->select('application_status', DB::raw('COUNT(*) as count'))
            ->groupBy('application_status')
            ->pluck('count', 'application_status')
            ->toArray();

        // Ensure all statuses are included, even if count is 0
        $completeCounts = [];
        foreach ($this->statusMap as $code => $label) {
            $completeCounts[] = [
                'status_code' => $code,
                'status_label' => $label,
                'count' => $statusCounts[$code] ?? 0,
            ];
        }

        return response()->json([
            'status' => $status,
            'status_labels' => array_map(function ($code) {
                return $this->statusMap[$code] ?? 'Unknown Status';
            }, $statusFilter),
            'total_count' => $applicationDetails->count(),
            'status_summary' => $completeCounts, // ✅ Summary counts for each status
            'data' => $applicationDetails,
        ]);
    }
    public function getSignatories($id)
    {
        $query = DB::table('denr_chainsaw.tbl_application_comments as c')
            ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'c.application_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.signatory_id')
            ->leftJoin('tbl_signatories as s', 's.id', '=', 'u.id')
            ->leftJoin('tbl_status as ts', 'ts.id', '=', 'c.status_id')
            ->select(
                'c.id',
                'ts.status_title',
                's.complete_name',
                's.position',
                'ac.application_no',
                'c.comments',
                'c.created_at'
            );

        // 🟢 If $id is provided, filter by application_id
        if (!empty($id)) {
            $query->where('ac.id', $id);
        }

        $results = $query->orderBy('c.created_at', 'DESC')->get();

        return response()->json($results);
    }

    public function receivedApplication(Request $request)
    {
        $user = auth()->user();
        $id = $request->id;

        $app = ChainsawIndividualApplication::findOrFail($request->id);
        // $app->application_status = self::STATUS_RECEIVED_CHIEF_RPS;
        $app->is_rps_chief_received = 1;
        $app->date_received_rps_chief = now();
        $app->save();

        DB::beginTransaction();
        // Validate routing
        // 1️⃣ FIRST ROUTE → CENRO CHIEF
        $receiver = DB::table('users')
            ->where('office_id', $user->office_id)
            ->where('role_id', self::CHIEF_RPS) // chiefs only CENRO STA CRUZ
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
            'action' => 'Received by the CHIEF RPS',
            'remarks' => 'For evaluation of CHIEF TSD',
            'is_read' => 1,
            'route_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::commit();



        return response()->json(['message' => 'Application Received.']);
    }

    public function endorseApplication(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tbl_application_checklist,id',
        ]);

        $user = auth()->user();

        DB::beginTransaction();

        try {
            // 1️⃣ Fetch application ONCE
            $app = ChainsawIndividualApplication::lockForUpdate()->findOrFail($request->id);

            // 2️⃣ Update application status
            $app->update([
                'application_status' => self::STATUS_ENDORSED_TSD_CHIEF,
                'date_endorsed_rps_chief' => now(),
            ]);

            // 3️⃣ Find Chief TSD in same office
            $tsdChief = DB::table('users')
                ->where('office_id', $user->office_id)
                ->where('role_id', self::CHIEF_TSD)
                ->first();

            if (!$tsdChief) {
                throw new \Exception("No Chief TSD found in office_id {$user->office_id}");
            }

            // 4️⃣ Insert routing record
            DB::table('tbl_application_routing')->insert([
                'application_id' => $app->id,
                'sender_id' => $user->id,
                'receiver_id' => $tsdChief->id,
                'action' => 'Endorsed to Chief TSD',
                'remarks' => 'Waiting to be received by Chief TSD',
                'is_read' => 0,
                'route_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Application endorsed to Chief TSD successfully.',
                'application_id' => $app->id,
                'current_status' => $app->application_status,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
