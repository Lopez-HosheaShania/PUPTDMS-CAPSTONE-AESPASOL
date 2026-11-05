<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Appointment Rescheduled</title>
</head>

<body
    style="
    margin: 0;
    padding: 0;
    background-color: #f4f4f5;
    font-family: Arial, Helvetica, sans-serif;
    color: #27272a;
">

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#f4f4f5;padding:40px 16px;">
        <tr>
            <td align="center">

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                    style="
                       max-width:600px;
                       background:#ffffff;
                       border-radius:12px;
                       overflow:hidden;
                       box-shadow:0 2px 8px rgba(0,0,0,0.08);
                   ">

                    <tr>
                        <td
                            style="
                        background:#5d0606;
                        padding:28px 32px;
                        text-align:center;
                        color:#ffffff;
                    ">
                            <h1 style="margin:0;font-size:22px;">
                                PUP Taguig Dental Clinic
                            </h1>

                            <p style="margin:8px 0 0;font-size:14px;">
                                Dental Clinic Management System
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">

                            <h2
                                style="
                            margin:0 0 20px;
                            font-size:22px;
                            color:#18181b;
                        ">
                                Appointment Rescheduled
                            </h2>

                            <p style="line-height:1.6;margin-bottom:24px;">
                                Hello
                                <strong>
                                    {{ $appointment->patient->name ?? 'Patient' }}
                                </strong>,
                            </p>

                            <p style="line-height:1.6;margin-bottom:24px;">
                                Your dental appointment has been rescheduled.
                                Please see your updated appointment details below.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="
                                   background:#fafafa;
                                   border:1px solid #e4e4e7;
                                   border-radius:8px;
                                   margin-bottom:24px;
                               ">

                                <tr>
                                    <td style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-bottom:1px solid #e4e4e7;
                                    color:#5d0606;
                                    font-size:13px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                "
                                        colspan="2">
                                        Previous Appointment
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:14px 18px;font-weight:bold;">
                                        Service
                                    </td>

                                    <td style="padding:14px 18px;text-align:right;">
                                        {{ $appointment->service_type_name }} </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-top:1px solid #e4e4e7;
                                ">
                                        Date
                                    </td>

                                    <td
                                        style="
                                    padding:14px 18px;
                                    text-align:right;
                                    border-top:1px solid #e4e4e7;
                                ">
                                        {{ \Carbon\Carbon::parse($oldAppointmentDate ?? $appointment->appointment_date)->format('F d, Y') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-top:1px solid #e4e4e7;
                                ">
                                        Time
                                    </td>

                                    <td
                                        style="
                                    padding:14px 18px;
                                    text-align:right;
                                    border-top:1px solid #e4e4e7;
                                ">
                                        {{ \Carbon\Carbon::parse($oldAppointmentTime ?? $appointment->appointment_time)->format('g:i A') }}
                                    </td>
                                </tr>

                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="
                                   background:#fafafa;
                                   border:1px solid #5d0606;
                                   border-radius:8px;
                                   margin-bottom:24px;
                               ">

                                <tr>
                                    <td style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-bottom:1px solid #5d0606;
                                    color:#5d0606;
                                    font-size:13px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                "
                                        colspan="2">
                                        New Appointment
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:14px 18px;font-weight:bold;">
                                        Service
                                    </td>

                                    <td style="padding:14px 18px;text-align:right;">
                                        {{ $appointment->service_type_name }} </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-top:1px solid #5d0606;
                                ">
                                        Date
                                    </td>

                                    <td
                                        style="
                                    padding:14px 18px;
                                    text-align:right;
                                    border-top:1px solid #5d0606;
                                ">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-top:1px solid #5d0606;
                                ">
                                        Time
                                    </td>

                                    <td
                                        style="
                                    padding:14px 18px;
                                    text-align:right;
                                    border-top:1px solid #5d0606;
                                ">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                    padding:14px 18px;
                                    font-weight:bold;
                                    border-top:1px solid #5d0606;
                                ">
                                        Status
                                    </td>

                                    <td
                                        style="
                                    padding:14px 18px;
                                    text-align:right;
                                    border-top:1px solid #5d0606;
                                ">
                                        Rescheduled
                                    </td>
                                </tr>

                            </table>

                            @if ($rescheduledBy)
                                <p style="line-height:1.6;margin-bottom:24px;">
                                    This appointment was rescheduled by <strong>{{ $rescheduledBy }}</strong>.
                                </p>
                            @endif

                            <p style="line-height:1.6;margin:0;">
                                Please arrive on time for your newly scheduled appointment.
                                If you have any questions or concerns, please contact the clinic.
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                        background:#fafafa;
                        border-top:1px solid #e4e4e7;
                        padding:20px 32px;
                        text-align:center;
                        font-size:12px;
                        color:#71717a;
                    ">
                            This is an automated message from the
                            PUP Taguig Dental Clinic.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>

