<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Certificate - Annual Dental Clearance</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ffffff;
            font-family: "Arial", "Helvetica", sans-serif;
            color: #000;
        }

        @page {
            size: landscape;
            margin: 0;
        }

        .page {
            width: 11in;
            min-height: 8.5in;
            margin: 0 auto;
            padding: 34px 52px 40px;
            position: relative;
            background: #fff;
        }

        .form-code {
            position: absolute;
            top: 36px;
            right: 62px;
            text-align: left;
            font-size: 14px;
            line-height: 1.1;
            font-weight: 700;
        }

        .header {
            display: grid;
            grid-template-columns: 140px 1fr;
            align-items: start;
            column-gap: 18px;
            margin-top: 14px;
        }

        .logo-wrap {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-wrap img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            display: block;
        }

        .logo-fallback {
            width: 110px;
            height: 110px;
            border: 1px dashed #999;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 11px;
            color: #666;
            padding: 8px;
        }

        .header-text {
            text-align: center;
            padding-right: 110px;
            margin-top: 8px;
        }

        .header-text .line1 {
            font-family: "Times New Roman", serif;
            font-size: 33px;
            font-weight: 700;
            line-height: 1.05;
        }

        .header-text .line2 {
            font-family: "Times New Roman", serif;
            font-size: 33px;
            font-weight: 700;
            line-height: 1.05;
        }

        .header-text .line3 {
            font-family: "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.05;
        }

        .title {
            text-align: center;
            margin-top: 34px;
        }

        .title .main1,
        .title .main2 {
            font-family: "Times New Roman", serif;
            font-size: 46px;
            font-weight: 800;
            line-height: 1.02;
            text-transform: uppercase;
        }

        .date-row {
            margin-top: 82px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            padding-right: 18px;
        }

        .date-label {
            font-size: 24px;
            font-weight: 700;
        }

        .line-field {
            display: inline-block;
            border-bottom: 3px solid #000;
            height: 26px;
            line-height: 22px;
            vertical-align: bottom;
            font-size: 22px;
            padding: 0 4px;
        }

        .date-field {
            width: 290px;
        }

        .body {
            margin-top: 92px;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.8;
        }

        .statement {
            margin-bottom: 10px;
        }

        .student-field {
            width: 610px;
        }

        .exam-date-field {
            width: 360px;
        }

        .paragraph {
            margin-top: 88px;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.55;
        }

        .paragraph em {
            font-style: italic;
            font-weight: 800;
        }

        .signature-area {
            margin-top: 150px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-block {
            width: 430px;
        }

        .sig-line-row {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            gap: 10px;
            margin-bottom: 14px;
        }

        .sig-line {
            width: 270px;
            border-bottom: 3px solid #000;
            height: 22px;
        }

        .sig-title {
            font-size: 24px;
            font-weight: 800;
            min-width: 88px;
        }

        .lic-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }

        .lic-label {
            font-size: 24px;
            font-weight: 800;
        }

        .lic-field {
            width: 320px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="form-code">
            PUP-DCAC-6-MEDS-018<br>
            Rev.0<br>
            May 15, 2018
        </div>

        <div class="header">
            <div class="logo-wrap">
                <img src="/images/PUP.png" alt="PUP Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">
                    PUP<br>Logo
                </div>
            </div>

            <div class="header-text">
                <div class="line1">Republic of the Philippines</div>
                <div class="line2">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>
                <div class="line3">Manila</div>
            </div>
        </div>

        <div class="title">
            <div class="main1">DENTAL CERTIFICATE</div>
            <div class="main2">ANNUAL DENTAL CLEARANCE</div>
        </div>

        <div class="date-row">
            <span class="date-label">Date</span>
            <span class="line-field date-field">{{date}}</span>
        </div>

        <div class="body">
            <div class="statement">
                This is to certify that <span class="line-field student-field">{{student_name}}</span>
            </div>

            <div class="statement">
                has been examined by the undersigned at the PUP Dental Clinic on
                <span class="line-field exam-date-field">{{examination_date}}</span>.
            </div>
        </div>

        <div class="paragraph">
            This certification is issued upon request for <em>Annual Dental Clearance</em>
            but not for medical-legal purposes.
        </div>

        <div class="signature-area">
            <div class="signature-block">
                <div class="sig-line-row">
                    <span class="sig-line">{{dentist_name}}</span>
                    <span class="sig-title">D.M.D.</span>
                </div>

                <div class="lic-row">
                    <span class="lic-label">Lic. No.</span>
                    <span class="line-field lic-field">{{lic_no}}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
