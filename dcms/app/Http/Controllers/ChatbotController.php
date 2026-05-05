<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Patient;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $apiKey = config('services.chatbot.api_key');

        if (!$apiKey) {
            return response()->json(
                [
                    'error' => 'Missing CHATBOT_API_KEY sa .env or config/services.php.',
                ],
                500,
            );
        }

        Log::info('Chatbot question', [
            'message' => $request->message,
            'context' => $request->context,
            'user_id' => auth()->id(),
        ]);

        $context = (string) $request->context;
        $isLoginPage = str_contains($context, '/login');

        $patient = Patient::with(['appointments', 'teeth', 'dentalHistory'])
            ->where('user_id', auth()->id())
            ->first();

        $appointment = $patient?->appointments?->sortByDesc('created_at')->first();
        $record = $patient?->dentalHistory;

        $patientContext = "
Patient Information:
    - Name: " . ($patient?->name ?? optional(auth()->user())->name ?? 'Unknown') . "
- Latest Appointment Date: " . ($appointment->appointment_date ?? 'None') . "
- Latest Appointment Time: " . ($appointment->appointment_time ?? 'None') . "
- Latest Appointment Status: " . ($appointment->status ?? 'None') . "
- Last Treatment: " . ($record->treatment ?? 'None') . "
- Last Diagnosis: " . ($record->diagnosis ?? 'None') . "
";

        $localReply = $this->getLocalSystemReply($request->message, $context, $patient, $isLoginPage);

        if ($localReply) {
            return response()->json([
                'reply' => $localReply,
            ]);
        }

        try {
            $models = ['gemini-2.0-flash', 'gemini-2.5-flash'];

            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                $response = Http::withoutVerifying()
                    ->timeout(20)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    [
                                        'text' => '
You are the official AI assistant of the PUP Taguig Dental Clinic Management System.

' . $patientContext . '

Current page/context: ' . ($context ?: 'unknown') . '

System route guide:
- /login = login page for signing in and accessing the system
- /homepage = patient dashboard
- /patient/appointments = appointments page
- /book-appointment = booking page / available dates
- /record = dental records
- /document-requests = document requests

If the current page is /login, only answer questions about signing in, login help, login buttons, or what the login page does.
Otherwise, only answer questions about appointments, booking, clinic schedule, dentist availability, document requests, odontogram, dental records, account navigation, and system features.

If the user asks about their own appointment, record, treatment, or diagnosis, answer using Patient Information above.
If the information is None, say that there is no available record yet.

Keep answers short. Maximum 2 sentences.

User message: ' . $request->message,
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 120,
                        ],
                    ]);

                if ($response->successful()) {
                    $reply = data_get($response->json(), 'candidates.0.content.parts.0.text');

                    return response()->json([
                        'reply' => $reply ?: 'Sorry, walang response mula sa AI.',
                    ]);
                }

                if (in_array($response->status(), [404, 429, 500, 503])) {
                    continue;
                }

                return response()->json(
                    [
                        'error' => 'May problema sa AI API.',
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ],
                    500,
                );
            }

            return response()->json(
                [
                    'error' => 'Temporary unavailable ang AI assistant.',
                ],
                503,
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'error' => 'Server error: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
    private function getLocalSystemReply(string $message, ?string $context = null, ?Patient $patient = null, bool $isLoginPage = false): ?string
    {
        $text = strtolower($message);

        if ($isLoginPage) {
            if (str_contains($text, 'hello') || str_contains($text, 'hi')) {
                return 'Hello! This is the login page. Please sign in using your account to access appointments, records, schedules, and document requests.';
            }

            if (str_contains($text, 'log in') || str_contains($text, 'login') || str_contains($text, 'sign in')) {
                return 'On the login page, enter your credentials and use the Log In button or SSO option to access the system.';
            }

            if (str_contains($text, 'sso') || str_contains($text, 'google') || str_contains($text, 'account')) {
                return 'You can use the available login method on this page to sign in and access the clinic system.';
            }

            if (str_contains($text, 'book') || str_contains($text, 'appointment') || str_contains($text, 'record') || str_contains($text, 'schedule') || str_contains($text, 'document')) {
                return 'Those features are available after login. Please sign in first to access appointments, records, schedules, and document requests.';
            }

            return 'This is the login page. You can sign in here to access appointments, records, schedules, and document requests.';
        }

        if (str_contains($text, 'hello') || str_contains($text, 'hi')) {
            $name = $patient?->name ?? auth()->user()->name ?? 'there';

            return "Hello {$name}! How can I assist you today regarding appointments, dental records, or system features?";
        }

        $askingLastAppointment =
            (
                str_contains($text, 'last appointment') ||
                str_contains($text, 'previous appointment') ||
                str_contains($text, 'past appointment') ||
                str_contains($text, 'last schedule') ||
                str_contains($text, 'huling appointment') ||
                str_contains($text, 'nakaraang appointment') ||
                str_contains($text, 'last appointment ko')
            );

        if ($askingLastAppointment) {
            $now = Carbon::now();

            $appointment = $patient?->appointments
                ?->filter(function ($appt) use ($now) {
                    $appointmentDateTime = Carbon::parse($appt->appointment_date . ' ' . $appt->appointment_time);

                    return $appointmentDateTime->lessThan($now)
                        || in_array($appt->status, ['completed', 'cancelled']);
                })
                ->sortByDesc(function ($appt) {
                    return $appt->appointment_date . ' ' . $appt->appointment_time;
                })
                ->first();

            if ($appointment) {
                $date = Carbon::parse($appointment->appointment_date)->format('F d, Y');
                $time = Carbon::parse($appointment->appointment_time)->format('H:i');

                return "Your last appointment was on {$date} at {$time}. Status: {$appointment->status}.";
            }

            return "You do not have any past appointment recorded in the system.";
        }

        $askingOwnAppointment =
            (
                str_contains($text, 'appointment') ||
                str_contains($text, 'appoint') ||
                str_contains($text, 'schedule') ||
                str_contains($text, 'iskedyul')
            ) &&
            (
                str_contains($text, 'my') ||
                str_contains($text, 'mine') ||
                str_contains($text, 'when') ||
                str_contains($text, 'date') ||
                str_contains($text, 'time') ||
                str_contains($text, 'do i have') ||
                str_contains($text, 'upcoming') ||
                str_contains($text, 'ko') ||
                str_contains($text, 'akin') ||
                str_contains($text, 'kailan') ||
                str_contains($text, 'oras')
            );

        if ($askingOwnAppointment) {
            $now = Carbon::now();

            $appointment = $patient?->appointments
                ?->filter(function ($appt) use ($now) {
                    $appointmentDateTime = Carbon::parse($appt->appointment_date . ' ' . $appt->appointment_time);

                    return $appointmentDateTime->greaterThanOrEqualTo($now)
                        && in_array($appt->status, ['upcoming', 'rescheduled']);
                })
                ->sortBy(function ($appt) {
                    return $appt->appointment_date . ' ' . $appt->appointment_time;
                })
                ->first();

            if ($appointment) {
                $date = Carbon::parse($appointment->appointment_date)->format('F d, Y');
                $time = Carbon::parse($appointment->appointment_time)->format('H:i');

                return "Your next appointment is on {$date} at {$time}. Status: {$appointment->status}.";
            }

            return "You do not have any upcoming appointment recorded in the system.";
        }

        if (str_contains($text, 'record') || str_contains($text, 'dental records')) {
            return 'Open the Records tab in the bottom navigation, or go to the Dental Records page to view your dental history, odontogram, treatments, and diagnosis.';
        }

        if (str_contains($text, 'odontogram')) {
            return 'You can view your odontogram on the Dental Records page. Tap Records in the bottom navigation, then look for the odontogram section.';
        }

        if (str_contains($text, 'appointment') || str_contains($text, 'book')) {
            return 'To book an appointment, tap the plus button or go to the Book Appointment page. Choose an available date and time from the calendar.';
        }

        if (str_contains($text, 'schedule') || str_contains($text, 'available')) {
            return 'You can check available appointment dates on the Book Appointment page.';
        }

        if (str_contains($text, 'document') || str_contains($text, 'clearance')) {
            return 'Go to Document Requests to request dental clearance or dental health records.';
        }

        return null;
    }
}
