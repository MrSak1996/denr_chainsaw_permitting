<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PDFController extends Controller
{
    /**
     * Convert a number to words (supports 0-999)
     */
    private function numberToWords(int $number): string
    {
        $dictionary = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        ];

        if ($number < 21) {
            return $dictionary[$number];
        }

        if ($number < 100) {
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            return $dictionary[$tens] . ($units ? '-' . $dictionary[$units] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $remainder = $number % 100;
            return $dictionary[$hundreds] . ' hundred' . ($remainder ? ' and ' . $this->numberToWords($remainder) : '');
        }

        // fallback for numbers >= 1000
        return (string) $number;
    }

    /**
     * Generate PDF for a given application ID
     */
    public function generateTable(Request $request, int $id)
    {
        $application = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->where('ac.id', $id)
            ->select([
                'ac.permit_no as permit_number',
                'ac.authorized_representative',
                DB::raw("
        CONCAT_WS(' ',
            ac.applicant_firstname,
            ac.applicant_middlename,
            ac.applicant_lastname
        ) AS applicant_name
    "),
    'ac.applicant_complete_address',
                'ci.engine_serial_no',
                'ac.company_address',
                DB::raw("TRIM(CONCAT(ac.applicant_firstname, ' ', IFNULL(ac.applicant_middlename, ''), ' ', ac.applicant_lastname)) as name"),
                'ac.applicant_complete_address as address',
                'ci.quantity',
                'ci.brand',
                'ci.model',
                'ci.supplier_name',
                'ci.supplier_address',
                'ci.purpose',
                'ci.other_details',
                'ac.approved_date as issued_date',
                'ac.expiry_date',
                'ap.official_receipt',
                'ap.date_of_payment',
            ])
            ->first();

        if (!$application) {
            abort(404, 'Application not found');
        }

        // Format quantity as words + number + unit
        $quantityInWords = ucfirst($this->numberToWords((int)$application->quantity));
        $quantityText = "{$quantityInWords} ({$application->quantity}) unit" . ($application->quantity > 1 ? 's' : '') . " of Chainsaw";

        // Load PDF view
        $pdf = Pdf::loadView('pdf.table-template', [
            'permit_number' => $application->permit_number,
            'name' => $application->authorized_representative
            ?? $application->applicant_name,
            'address' => $application->address,
            'complete_address' => $application->company_address ?? $application->applicant_complete_address,
            'quantity' => $quantityText,
            'brand' => $application->brand,
            'model' => $application->model,
            'engine_serial_no' => $application->engine_serial_no,
            'supplier_name' => $application->supplier_name,
            'supplier_address' => $application->supplier_address,
            'purpose' => $application->purpose,
            'others' => $application->other_details,
            'issued_date' => $application->issued_date ? \Carbon\Carbon::parse($application->issued_date)->format('F d, Y') : null,
            'expiry_date' => $application->expiry_date ? \Carbon\Carbon::parse($application->expiry_date)->format('F d, Y') : null,
            'or_number' => $application->official_receipt,
            'or_date' => $application->date_of_payment ? \Carbon\Carbon::parse($application->date_of_payment)->format('F d, Y') : null,
        ]);

        return $pdf->stream('permit.pdf');
    }

    /**
     * Preview permit (for testing purposes)
     */
    public function previewPermit()
    {
        $quantity = 1;
        $quantityText = ucfirst($this->numberToWords($quantity)) . " ({$quantity}) unit of Chainsaw";

        $data = [
            'permit_number' => 'DEMO-12345',
            'name' => 'Sample User',
            'address' => 'Sample Address',
            'quantity' => $quantityText,
            'brand' => 'STIHL',
            'model' => 'MS 170',
            'engine_serial_no' => 'ENG12345',
            'supplier_name' => 'ABC Dealer',
            'supplier_address' => 'Laguna',
            'purpose' => 'Sample Purpose',
            'others' => '',
            'issued_date' => now()->format('F d, Y'),
            'expiry_date' => now()->addYear()->format('F d, Y'),
            'or_number' => '000111',
            'or_date' => now()->format('F d, Y'),
        ];

        return view('pdf.table-template', $data);
    }
}
