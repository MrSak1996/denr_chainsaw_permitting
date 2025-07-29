<?php

namespace App\Http\Controllers\Chainsaw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chainsaw\ChainsawInformation;
use Illuminate\Support\Facades\Storage;

class ChainsawController extends Controller
{
    public function insertChainsawInfo(Request $request)
    {
        $validated = $request->validate([
            'application_id'       => 'required|integer',
            'file_id'              => 'nullable|integer',
            'permit_chainsaw_no'   => 'required|string|max:100',
            'brand'                => 'required|string|max:100',
            'model'                => 'required|string|max:100',
            'quantity'             => 'required|integer|min:1',
            'supplier_name'        => 'required|string|max:255',
            'purpose'              => 'required|string|max:255',
            'permit_validity'      => 'required|date',
            'others_details'       => 'nullable|string|max:1000',
        ]);

        $chainsaw = ChainsawInformation::create([
            'applicant_id'        => $request->input('applicant_id'),
            'file_id'             => $request->input('file_id'),
            'permit_chainsaw_no'  => $request->input('permit_chainsaw_no'),
            'brand'               => $request->input('brand'),
            'model'               => $request->input('model'),
            'quantity'            => $request->input('quantity'),
            'supplier_name'       => $request->input('supplier_name'),
            'purpose'             => $request->input('purpose'),
            'permit_validity'     => $request->input('permit_validity'),
            'other_details'       => $request->input('other_details'),
        ]);
        return response()->json([
            'message' => 'Chainsaw information inserted successfully',
            'data'    => $chainsaw,
        ], 201);
    }
}
