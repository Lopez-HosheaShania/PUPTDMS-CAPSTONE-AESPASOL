<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Cases</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            size: legal portrait;
            margin: 0.25in 0.35in;
        }

        body {
            margin: 0;
            background: #d6cfc4;
            font-family: "Arial", Helvetica, sans-serif;
            color: #111;
        }

        .page {
            width: 8.5in;
            min-height: 14in;
            margin: 0 auto;
            padding: 0.2in 0.22in 0.18in;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        @media screen {
            .page {
                border: 1px solid #d1d5db;
                box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
            }
        }

        .masthead {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .masthead-left,
        .masthead-right {
            width: 112px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .masthead-left img,
        .masthead-right img {
            width: 92px;
            height: 92px;
            object-fit: contain;
            display: block;
        }

        .masthead-right img {
            width: 100px;
            height: 88px;
            margin-top: 6px;
        }

        .masthead-center {
            flex: 1;
            padding-top: 2px;
            line-height: 1.1;
        }

        .masthead-center .rp {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 1px;
        }

        .masthead-center .uni {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .masthead-center .ovpa,
        .masthead-center .msd,
        .masthead-center .ds {
            font-size: 12.5px;
            font-weight: 600;
        }

        .rule {
            border: none;
            border-top: 1px solid #d0d0d0;
            margin: 0.18in 0 0.12in;
        }

        .title-block {
            text-align: center;
            margin-bottom: 0.18in;
            line-height: 1.08;
        }

        .title-block .title {
            font-size: 16px;
            font-weight: 700;
        }

        .title-block .month {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .clinic-row {
            display: flex;
            justify-content: flex-start;
            margin: 0.1in 0 0.14in;
        }

        .clinic-row .clinic {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 0.08in;
        }

        .section {
            margin-top: 0.12in;
        }

        .section-title {
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        table.case-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 4px;
        }

        table.case-table th,
        table.case-table td {
            border: 1px solid #d5dfb5;
            padding: 3px 8px;
            font-size: 12px;
            vertical-align: middle;
            background: #e5edc9;
        }

        table.case-table th {
            text-align: left;
            font-weight: 400;
            color: #333;
        }

        .col-num {
            width: 38px;
            text-align: center;
        }

        .col-diagnosis {
            width: auto;
        }

        .col-cases {
            width: 150px;
            text-align: center;
        }

        .bottom-block {
            margin-top: auto;
        }

        .signatures-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin-top: 0.05in;
        }

        .signature-card {
            width: 46%;
            text-align: center;
        }

        .signature-label {
            font-size: 14px;
            text-align: left;
            margin-bottom: 18px;
        }

        .signature-image {
            width: 130px;
            height: 64px;
            object-fit: contain;
            display: block;
            margin: 0 auto 8px;
        }

        .signature-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.15;
        }

        .signature-role {
            font-size: 12px;
            line-height: 1.1;
        }

        .footer {
            margin-top: 0.16in;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            font-size: 9px;
            line-height: 1.25;
        }

        .footer-left {
            max-width: 4.9in;
        }

        .footer .tagline {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .footer-logos {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .footer-logos img {
            height: 46px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">

        <div class="masthead">
            <div class="masthead-left">
                <img src="/images/PUP.png" alt="PUP Logo" onerror="this.style.display='none'">
            </div>

            <div class="masthead-center">
                <div class="rp">Republic of the Philippines</div>
                <div class="uni">Polytechnic University of the Philippines</div>
                <div class="ovpa">Office of the Vice President for Administration</div>
                <div class="msd">Medical Services Department</div>
                <div class="ds">Dental Services</div>
            </div>

            <div class="masthead-right">
                <img src="/images/bagong-pilipinas.png" alt="Bagong Pilipinas Logo" onerror="this.style.display='none'">
            </div>
        </div>

        <hr class="rule">

        <div class="title-block">
            <div class="title">Dental Cases</div>
            <div class="month">{{report_month}}</div>
        </div>

        <div class="clinic-row">
            <div class="clinic">Taguig Dental Cases</div>
        </div>

        <div class="section">
            <div class="section-title">Students</div>
            <table class="case-table">
                <thead>
                    <tr>
                        <th class="col-num"></th>
                        <th class="col-diagnosis">DIAGNOSIS</th>
                        <th class="col-cases">Number of Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1.</td>
                        <td>{{students_diagnosis_1}}</td>
                        <td class="col-cases">{{students_cases_1}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">2.</td>
                        <td>{{students_diagnosis_2}}</td>
                        <td class="col-cases">{{students_cases_2}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">3.</td>
                        <td>{{students_diagnosis_3}}</td>
                        <td class="col-cases">{{students_cases_3}}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Faculty</div>
            <table class="case-table">
                <thead>
                    <tr>
                        <th class="col-num"></th>
                        <th class="col-diagnosis">DIAGNOSIS</th>
                        <th class="col-cases">Number of Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1.</td>
                        <td>{{faculty_diagnosis_1}}</td>
                        <td class="col-cases">{{faculty_cases_1}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">2.</td>
                        <td>{{faculty_diagnosis_2}}</td>
                        <td class="col-cases">{{faculty_cases_2}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">3.</td>
                        <td>{{faculty_diagnosis_3}}</td>
                        <td class="col-cases">{{faculty_cases_3}}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Administrative Personnel</div>
            <table class="case-table">
                <thead>
                    <tr>
                        <th class="col-num"></th>
                        <th class="col-diagnosis">DIAGNOSIS</th>
                        <th class="col-cases">Number of Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1.</td>
                        <td>{{admin_diagnosis_1}}</td>
                        <td class="col-cases">{{admin_cases_1}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">2.</td>
                        <td>{{admin_diagnosis_2}}</td>
                        <td class="col-cases">{{admin_cases_2}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">3.</td>
                        <td>{{admin_diagnosis_3}}</td>
                        <td class="col-cases">{{admin_cases_3}}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Dependents &amp; Alumni</div>
            <table class="case-table">
                <thead>
                    <tr>
                        <th class="col-num"></th>
                        <th class="col-diagnosis">DIAGNOSIS</th>
                        <th class="col-cases">Number of Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1.</td>
                        <td>{{dependents_diagnosis_1}}</td>
                        <td class="col-cases">{{dependents_cases_1}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">2.</td>
                        <td>{{dependents_diagnosis_2}}</td>
                        <td class="col-cases">{{dependents_cases_2}}</td>
                    </tr>
                    <tr>
                        <td class="col-num">3.</td>
                        <td>{{dependents_diagnosis_3}}</td>
                        <td class="col-cases">{{dependents_cases_3}}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bottom-block">
            <div class="signatures-row">
                <div class="signature-card">
                    <div class="signature-label">Prepared by:</div>
                    <img class="signature-image" src="{{prepared_by_signature}}" alt="Prepared by signature" onerror="this.style.display='none'">
                    <div class="signature-name">{{prepared_by}}</div>
                    <div class="signature-role">{{prepared_by_role}}</div>
                </div>

                <div class="signature-card">
                    <div class="signature-label">Submitted by:</div>
                    <img class="signature-image" src="{{dentist_signature}}" alt="Dentist Signature" onerror="this.style.display='none'">
                    <div class="signature-name">{{dentist_name}}</div>
                    <div class="signature-role">Dentist</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-left">
                <div>PUP A. Mabini Campus, Anonas Street, Sta. Mesa, Manila 1016</div>
                <div>Direct Line: 335-1745 | Trunk Line: 335-1787 or 335-1777 local 385 (Medical Director’s Office), 312 (Clinic)</div>
                <div>Website: www.pup.edu.ph | Email: medical@pup.edu.ph</div>
                <div class="tagline">THE COUNTRY’S 1st POLYTECHNICU</div>
            </div>

            <div class="footer-logos">
                <img src="/images/wuri.png" alt="WURI" onerror="this.style.display='none'">
                <img src="/images/bagong-pilipinas.png" alt="Bagong Pilipinas" onerror="this.style.display='none'">
            </div>
        </div>

    </div>
</body>
</html>
