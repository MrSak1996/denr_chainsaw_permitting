<!DOCTYPE html>
<html>

<head>
    <title>Permit to Purchase Chainsaw</title>
    <style>
        @page {
            size: A4;
            margin: 0mm 25.4mm 25.4mm 25.4mm;
            /* top right bottom left */
        }


        body {
            font-family: "Times New Roman", serif;
            position: relative;
        }

        /* Watermark placed behind all content */
        body::before {
            content: "";
            position: absolute;
            top: 40%;
            left: 50%;
            width: 90%;
            height: 90%;
            background-image: url("{{ public_path('images/denr_logo.png') }}");

            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.25;
            /* 🔥 Increase this for darker watermark */
            transform: translate(-50%, -50%);
            z-index: -1;
        }


        /* HEADER */
        .header-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0;
            margin-top: 0;
            /* 20px bottom border */
            padding-bottom: 10px;
        }

        .header-logo img {
            width: 90px;
        }

        .header-text {
            flex: 1;
            text-align: center;
            line-height: 1.2;
            margin: 0;
        }

        .header-text h2,
        .header-text h3 {
            margin: 2px 0;
            font-weight: bold;
        }

        .header-text h2 {
            font-size: 18px;
        }

        .header-text h3 {
            font-size: 16px;
        }

        /* TITLE SECTION */
        .title-section {
            font-size: 22px;
            text-align: center;
        }

        .title-section h2 {
            font-size: 22px;
            font-weight: bold;
        }

        .field {
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 140px;
            padding: 0 4px;
            white-space: normal;
            word-break: break-word;
        }

        /* Table Layout */
        .info-table {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
        }

        .chainsaw-details td:first-child {
            padding-left: 35px;
            font-weight: bold;
            width: 140px;
        }

        .signature {
            margin-top: 40px;
            text-align: left;
        }



        .signature p {
            margin: 2px 0;
        }

        .signature {
            margin-top: 20px;
            text-align: right;
            /* ⭐ Move signature to the right */
        }

        .fees {
            margin-top: 20px;
            text-align: right;
            /* ⭐ Move permit fee, OR No, date to the right */
        }

        /* APPLICATION FORM AND SIGNATORIES */
        .signatory-section {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .evaluated-by {
            /* Container for the Evaluator and Date on the same line */
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            /* Align the bottom of the text */
            margin-bottom: 20px;
            font-size: 11pt;
        }

        .evaluator-details {
            /* Aligns the name and title */
            text-align: center;
            line-height: 1.2;
            margin-left: 200px;
            /* Adjust left margin to position it centrally */
        }

        .signatory-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            display: block;
            margin-top: 20px;
            /* Space for the signature line */
        }

        .review-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 20px;
        }

        .review-table th,
        .review-table td {
            border: 1px solid black;
            padding: 8px 5px;
            vertical-align: top;
            text-align: center;
        }

        .review-table th {
            font-weight: bold;
            background-color: #f0f0f0;
            /* Light background for the header row */
        }

        /* Specific styling for the signature line within the table */
        .reviewed-by-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            display: block;
            margin-top: 30px;
            /* Space for the signature */
        }

        .reviewed-by-title {
            display: block;
            font-style: italic;
            /* Titles are typically not bold in the table */
        }

        /* Style for the remarks to align left */
        .remarks-cell {
            text-align: left !important;
            font-style: italic;
            white-space: pre-line;
            /* Allows line breaks in the text */
        }

        /* The final signatory below the table */
        .final-signatory {
            text-align: center;
            line-height: 1.2;
            margin-top: 40px;
            font-size: 11pt;
        }

        .approved-signature {
            position: relative;
            margin-top: 40px;
        }

        .approved-img {
            position: absolute;
            right: 20px;
            /* 🔥 adjust horizontally */
            top: -50px;
            /* 🔥 adjust vertically */
            width: 160px;
            /* signature size */
            opacity: 0.25;
            /* optional */
        }
        
    </style>
</head>

<body>



    <div class="header-container">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
            <tr>

                <!-- LEFT LOGO -->
                <td style="width:50px; text-align:left; vertical-align:middle;">
                    <img src="{{ public_path('images/denr_logo.png') }}" style="width:90px;">
                </td>

                <!-- CENTER TEXT -->
                <td style="width:100%; text-align:left; vertical-align:middle; padding:0 15px;">
                    <h2 style="margin-top:10px; margin-left:85px; font-size:18px; line-height:1.3; font-weight:bold;">
                        Republic of the Philippines<br>
                        Department of Environment and Natural Resources<br>
                        Regional Office No. IV-A (CALABARZON)
                    </h2>
                </td>


                <!-- RIGHT LOGO -->
                <td style="width:50px; text-align:right; vertical-align:middle;">
                    <img src="{{ public_path('images/bp.png') }}" style="width:90px;">
                </td>

            </tr>
        </table>

        <!-- Red Line -->
        <div style="position:fixed;z-index:1000; width:100%; height:10px; background:#8D1010; margin-top:8px;"></div>
    </div>




    <!-- TITLE -->
    <div class="title-section">
        <h2>PERMIT TO PURCHASE CHAINSAW <br>NO.<span style="text-decoration:underline;">{{ $permit_number }}</span></h2>
    </div>

    <!-- BODY TEXT -->
    <p style="text-align: justify;font-size:16px !important;">
        Pursuant to the provisions of DENR Administrative Order No. 2003-24, Series of 2003 which provides the
        "Implementing Guidelines of R.A. 9175 of 2002" entitled "An Act Regulating the Possession, Ownership,
        Sale, Importation and Use of Chainsaws penalizing violations thereof and for other related purposes" and
        Department Administrative Order No. 2022-10 re: Revised DENR Manual of Authorities on Technical Matters
        dated May 30, 2022, this <b>PERMIT TO PURCHASE CHAINSAW </b>is hereby issued to:
    </p>

    <table class="info-table">
        <tr>
            <td style="width: 90px;">Name:</td>
            <td style="font-weight:bold;"><span class="field">{{ $name }}</span></td>
        </tr>
        <tr>
            <td>Address:</td>
            <td style="font-weight:bold;font-size:14px !important;"><span class="field">{{ $complete_address }}</span>
            </td>
        </tr>
    </table>

    <p>The following information and descriptions of the chainsaw subject of this permit are
        hereby enumerated:</p>

    <table class="info-table chainsaw-details" style="width: 100%;font-size: 14px !important;">
        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Quantity:</td>
            <td><span class="field">{{ $quantity }}</span></td>
        </tr>

        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Brand:</td>
            <td><span class="field">{{ $brand }}</span></td>
        </tr>

        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Model:</td>
            <td><span class="field">{{ $model }}</span></td>
        </tr>

        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Engine Serial No.:</td>
            <td><span class="field">{{ $engine_serial_no }}</span></td>
        </tr>

        <tr>
            <td colspan="2"> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Source of Chainsaw /
                Supplier:</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3"
                style="font-weight: normal !important;text-decoration:underline;font-size: 14px !important;">
                {{ $supplier_name }}<br>
                {{ $supplier_address }}
            </td>

        </tr>


        <!-- <tr>
            <td>Purchase Price:</td>
            <td><span class="field"></span></td>
        </tr> -->

        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Purpose:</td>
            <td><span style="text-decoration:underline;">{{ $purpose }}</span></td>
        </tr>

        <tr>
            <td> <img src="{{ public_path('images/bullet.png') }}" style="width:10px;">Others:</td>
            <td><span style="text-decoration:underline;">{{ $others }}</span></td>
        </tr>
    </table>

    <!-- DATES -->
    <p>Issued on <span style="text-decoration:underline;">{{ $issued_date }}</span> at Brgy. Mayapa, Calamba City,
        Laguna<br>Expiry Date: <span style="text-decoration:underline;">{{ $expiry_date }}</span></p>

    <!-- SIGNATURE -->
    <!-- SIGNATURE -->
    <div class="signature approved-signature">
        <img src="{{ public_path('images/approved.png') }}" class="approved-img">

        <p style="margin-right:50px;margin-bottom:40px;">Approved:</p>
        <p><strong>NILO B. TAMORIA, CESO III</strong></p>
        <p>Regional Executive Director</p>
    </div>


    <!-- FEES SECTION -->

    <div style="font-size:12px;margin-top:15px;">
        <p>Permit Fee: P500.00<br>
            O.R. No.: <span class="field">{{ $or_number }}</span><br>
            Date: <span class="field">{{ $or_date }}</span></p>
    </div>

    <!-- FOOTER -->
    <div style="
    position: fixed;
    bottom: -70;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 16px;
    line-height: 1.3;
    padding: 8px 20px;
    background: white;
    z-index: 1000;
">
        <div style="position:fixed;z-index:1000; width:100%; height:10px; background:#8D1010;"></div>
        <br>
        <b>DENR IV-A (CALABARZON) Compound, Mayapa Main Road (along SLEX),<br>
            Barangay Mayapa, Calamba City, Laguna</b><br>
        Trunkline No. (049) 540-DENR (3367); Mobile Nos. 0956 182 5774 / 0919 874 4369;<br>
        E-mail address: r4a@denr.gov.ph
    </div>




</body>

</html>