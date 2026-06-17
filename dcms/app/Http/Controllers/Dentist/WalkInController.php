<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\Disease;
use App\Models\Patient;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\StudentApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WalkInController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::query()
            ->orderBy('name')
            ->get()
            ->map(function ($service) {
                return [
                    'name' => $service->name,
                    'desc' => $service->description ?? 'Dental service available for walk-in appointment.',
                    'img' => $service->image ?? null,
                ];
            });

        $diseases = Disease::query()
            ->orderBy('label')
            ->get();

        $schedules = ClinicSchedule::active()
            ->orderBy('id')
            ->get();

        $blockedDates = BlockedDate::orderBy('date')
            ->get();

        $startDate = now()->startOfMonth()->subMonth();
        $endDate = now()->endOfMonth()->addMonths(3);

        $appointmentCountsPerDay = Appointment::whereBetween('appointment_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('appointment_date, COUNT(*) as total')
            ->groupBy('appointment_date')
            ->pluck('total', 'appointment_date');

        $philippineHolidays = PhilippineHolidays::range(yearsBefore: 1, yearsAfter: 5);

        return view('dentist.dentist-walk-in', compact(
            'serviceTypes',
            'diseases',
            'schedules',
            'blockedDates',
            'appointmentCountsPerDay',
            'philippineHolidays'
        ));
    }

    public function searchPatient(Request $request, StudentApiService $studentApiService)
    {
        $search = trim((string) $request->query('q', ''));
        $showAll = $request->boolean('show_all');
        $limit = max(1, min((int) $request->query('limit', 30), 80));

        if ($search === '' && ! $showAll) {
            return response()->json([]);
        }

        try {
            $students = $studentApiService->searchStudents(
                search: $search !== '' ? $search : null,
                limit: $limit
            );

            $patients = collect($students)
                ->map(fn($student) => $studentApiService->normalizeStudent((array) $student))
                ->filter()
                ->map(function (array $student) {
                    return DB::transaction(function () use ($student) {
                        $user = $this->syncWalkInUser($student);
                        $patient = $this->syncWalkInPatient($user, $student, 'Student');

                        return [
                            'id' => $patient->id,
                            'name' => $student['name'],
                            'email' => $student['email'],
                            'type' => 'Student',
                            'student_number' => $student['student_number'] ?? null,
                            'program' => $student['program'] ?? null,
                        ];
                    });
                })
                ->values();

            return response()->json($patients);
        } catch (\Throwable $e) {
            Log::error('Walk-in student API search failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Unable to load student records from Guidance API.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function storeGuest(Request $request)
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $patient = DB::transaction(function () use ($validated) {
                $email = $validated['guest_email'] ?: 'guest_' . Str::uuid() . '@walkin.local';

                $studentLikeData = [
                    'name' => $validated['guest_name'],
                    'email' => strtolower($email),
                    'phone' => $validated['guest_phone'] ?? null,
                    'gender' => null,
                    'program' => null,
                    'student_number' => null,
                ];

                $user = $this->syncWalkInUser($studentLikeData);
                return $this->syncWalkInPatient($user, $studentLikeData, 'Guest');
            });

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name ?? optional($patient->user)->name,
                    'email' => $patient->email ?? optional($patient->user)->email,
                    'type' => 'Guest',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Walk-in guest creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create guest patient.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function startWalkIn(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'service_type' => ['required', 'string', 'max:255'],
            'concern' => ['nullable', 'string', 'max:1000'],
            'patient_signature' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:25600'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        $now = Carbon::now();

        $appointmentData = [
            'patient_id' => $patient->id,
            'dentist_id' => Auth::id(),
            'service_type' => $validated['service_type'],
            'appointment_date' => $now->toDateString(),
            'appointment_time' => $now->format('H:i:s'),
            'status' => 'upcoming',
        ];

        if (Schema::hasColumn('appointments', 'is_walk_in')) {
            $appointmentData['is_walk_in'] = true;
        }

        if (Schema::hasColumn('appointments', 'concern')) {
            $appointmentData['concern'] = $validated['concern'] ?? null;
        }

        $appointment = Appointment::create($appointmentData);

        return redirect()->route('dentist.odontogram', [
            'appointment' => $appointment->id,
        ]);
    }

    private function syncWalkInUser(array $data): User
    {
        $email = strtolower((string) $data['email']);

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = new User();

            $userData = [
                'name' => $data['name'],
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
            ];

            if (Schema::hasColumn('users', 'role')) {
                $userData['role'] = 'patient';
            }

            if (Schema::hasColumn('users', 'user_type')) {
                $userData['user_type'] = 'patient';
            }

            if (Schema::hasColumn('users', 'status')) {
                $userData['status'] = 'active';
            }

            $user->forceFill($userData);
            $user->save();

            return $user;
        }

        $updates = [];

        if (Schema::hasColumn('users', 'name')) {
            $updates['name'] = $data['name'];
        }

        if (! empty($updates)) {
            $user->forceFill($updates);
            $user->save();
        }

        return $user;
    }
    private function syncWalkInPatient(User $user, array $data, string $type): Patient
    {
        $patient = Patient::where('user_id', $user->id)->first();

        $patientData = [
            'user_id' => $user->id,
        ];

        if (Schema::hasColumn('patients', 'name')) {
            $patientData['name'] = $data['name'];
        }

        if (Schema::hasColumn('patients', 'email')) {
            $patientData['email'] = $data['email'];
        }

        if (Schema::hasColumn('patients', 'phone')) {
            $patientData['phone'] = $data['phone'] ?? null;
        }

        if (Schema::hasColumn('patients', 'gender')) {
            $patientData['gender'] = $data['gender'] ?? null;
        }

        if (Schema::hasColumn('patients', 'program_code')) {
            $patientData['program_code'] = $data['program'] ?? null;
        }

        if (Schema::hasColumn('patients', 'student_number')) {
            $patientData['student_number'] = $data['student_number'] ?? null;
        }

        if (Schema::hasColumn('patients', 'patient_type')) {
            $patientData['patient_type'] = $type;
        }

        if (Schema::hasColumn('patients', 'type')) {
            $patientData['type'] = $type;
        }

        if (Schema::hasColumn('patients', 'status')) {
            $patientData['status'] = 'active';
        }

        if (Schema::hasColumn('patients', 'role')) {
            $patientData['role'] = 'patient';
        }

        if (! $patient) {
            if (Schema::hasColumn('patients', 'password')) {
                $patientData['password'] = bcrypt(Str::random(32));
            }

            $patient = new Patient();
            $patient->forceFill($patientData);
            $patient->save();

            return $patient;
        }

        if (Schema::hasColumn('patients', 'password') && empty($patient->password)) {
            $patientData['password'] = bcrypt(Str::random(32));
        }

        $patient->forceFill($patientData);
        $patient->save();

        return $patient;
    }
}
