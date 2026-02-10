<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function saveAssessment(Request $request)
    {
        DB::beginTransaction();

        try {
            foreach ($request->assessments as $assessment) {
                DB::table('tbl_app_checklist_entry')->insert([
                    'parent_id'  => $request->id, // ✅ FIX
                    'chklist_id' => $assessment['checklist_entry_id'],
                    'answer'     => $assessment['assessment'] === 'passed' ? 'yes' : 'no',
                    'remarks'    => $assessment['remarks'],
                    'assessment' => $assessment['assessment'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('tbl_application_checklist')->updateOrInsert(
                [
                    'id' => $request->id // ✅ FIX
                ],
                [
                    'findings'        => $request->onsite['findings'],
                    'recommendations' => $request->onsite['recommendations'],
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Assessment saved successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to save assessment',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
