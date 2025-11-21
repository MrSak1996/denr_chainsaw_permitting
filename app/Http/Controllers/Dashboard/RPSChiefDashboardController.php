<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RPSChiefDashboardController extends Controller
{
    // Define status constants
    const STATUS_DRAFT = -1;
    const STATUS_RETURN_FOR_COMPLIANCE = 0;
    const STATUS_FOR_REVIEW_EVALUATION = 1;
    const STATUS_ENDORSED_CENRO = 2;
    const STATUS_ENDORSED_PENRO = 3;
    const STATUS_ENDORSED_RO = 4;
    const STATUS_APPROVED = 5;

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
    ];

    /**
     * Fetch applications dynamically by status
     */
    public function getApplicationsByStatus(Request $request)
    {
        // ✅ Validate incoming request
        $request->validate([
            'status' => 'required|integer',
        ]);

        $status = (int) $request->input('status');

        // ✅ Determine which statuses to filter
        $statusFilter = [$status];

        // If status is 2 (CENRO), include PENRO and R.O (2, 3, 4)
        if ($status === self::STATUS_ENDORSED_CENRO) {
            $statusFilter = [
                self::STATUS_ENDORSED_CENRO,
                self::STATUS_ENDORSED_PENRO,
                self::STATUS_ENDORSED_RO,
            ];
        }

        // ✅ Fetch applications matching filtered statuses
        $applicationDetails = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->leftJoin('tbl_status as s', 'ac.application_status', '=', 's.id')
            ->select(
                'ac.id',
                'ac.return_reason',
                's.status_title',
                'ac.application_status',
                'ac.application_type',
                'ac.application_no',
                'ci.permit_chainsaw_no',
                'ci.brand',
                'ci.model',
                'ci.quantity',
                'ci.purpose',
                'ap.official_receipt',
                'ap.permit_fee',
                'ap.date_of_payment',
                'ci.permit_validity',
                'ac.created_at',
                'ac.date_applied'
            )
            ->whereIn('ac.application_status', $statusFilter)
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
            'status' => $statusFilter,
            'status_labels' => array_map(function ($code) {
                return $this->statusMap[$code] ?? 'Unknown Status';
            }, $statusFilter),
            'total_count' => $applicationDetails->count(),
            'status_summary' => $completeCounts, // ✅ Summary counts for each status
            'data' => $applicationDetails,
        ]);
    }
}
