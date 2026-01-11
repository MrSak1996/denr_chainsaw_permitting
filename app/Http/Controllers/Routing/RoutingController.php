<?php

namespace App\Http\Controllers\Routing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function show($applicationId)
    {
        $routes = DB::table('tbl_application_routing as ar')
            ->select(
                'ac.id',
                'ac.application_no',
                'ar.route_order',
                'ar.comments',
                'r.role_title as sender_role',
                'u.name as sender',
                'rr.role_title as receiver_role',
                'uu.name as receiver',
                'ar.action',
                'ar.remarks',
                'ar.created_at',
                'ac.date_received_rps_chief',
                'ac.date_endorsed_tsd_chief',
                'ac.date_received_tsd_chief',
                'ac.date_endorsed_penro',
                'ac.date_received_penro_chief',
                'ac.date_endorsed_fus',
                'ac.date_received_fus',
                'ac.date_endorsed_ardts',
                'ac.date_received_ardts',
                'ac.date_received_red'
            )
            ->leftJoin('users as u', 'u.id', '=', 'ar.sender_id')
            ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')
            ->leftJoin('users as uu', 'uu.id', '=', 'ar.receiver_id')
            ->leftJoin('tbl_roles as rr', 'rr.id', '=', 'uu.role_id')
            ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'ar.application_id')
            ->where('ar.application_id', $applicationId)
            ->orderBy('ar.route_order', 'asc')
            ->get();

        return response()->json($routes);
    }

    public function getCommentsByID($applicationId)
    {
        $data = DB::table('tbl_application_routing as ar')
            ->select([
                'ar.id',
                'ac.application_no',
                'u.name as action_officer',
                'r.role_title as sender_role',
                'ar.comments',
                's.status_title',
                'ac.date_returned',
            ])
            ->leftJoin('users as u', 'u.id', '=', 'ar.sender_id')
            ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'ar.application_id')
            ->leftJoin('tbl_status as s', 's.id', '=', 'ac.application_status')
            ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')

            ->where('ar.application_id', $applicationId)
            ->where('ar.route_order', 0)
            ->get();

        return response()->json($data);
    }
}
