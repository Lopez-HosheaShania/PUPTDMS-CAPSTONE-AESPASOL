<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLogger;
use Illuminate\Validation\Rules\Password;

class PatientAuthController extends Controller
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated =
            $request->validate(
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
                    ],

                    'email' =>
                    'required|email|unique:patients,email|unique:users,email',

                    'phone' => [
                        'required',
                        'string',
                        'regex:/^09\d{9}$/',
                    ],

                    'birthdate' => [
                        'required',
                        'date',
                        'before_or_equal:today',
                    ],

                    'gender' =>
                    'required|string|in:Male,Female',

                    'password' => [
                        'required',
                        'string',
                        'confirmed',

                        Password::min(8)
                            ->mixedCase()
                            ->numbers()
                            ->symbols(),
                    ],
                ],
                [
                    'name.regex' =>
                    'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',

                    'phone.regex' =>
                    'Phone number must start with 09 and contain exactly 11 digits.',

                    'birthdate.before_or_equal' =>
                    'Birthdate cannot be in the future.',
                ]
            );

        DB::transaction(function () use ($validated) {
            $hashedPassword = Hash::make($validated['password']);
            $patientRole = Role::where('slug', 'patient')->first();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $hashedPassword,
                'role_id' => $patientRole?->id,
                'status' => 'active',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'birthdate' => $validated['birthdate'],
                'gender' => $validated['gender'],
                // Keep patient password synced for compatibility, but users.password is the source of truth.
                'password' => $hashedPassword,
            ]);
        });

        AuditLogger::log(
            'register',
            'patient_auth',
            "Patient registered an account"
        );

        return redirect()->route('login')->with('success', 'Account created successfully!');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->concurrentSessionService->rememberBrowserHint($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with(['role', 'patient'])
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !$user->hasRole('patient')) {
            return back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->withInput();
        }

        if (($user->status ?? 'inactive') !== 'active') {
            return back()
                ->withErrors(['email' => 'Your account is inactive.'])
                ->withInput();
        }

        if (!filled($user->password) || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->withInput();
        }

        $patient = $user->patient ?: Patient::where('email', $user->email)->first();

        if (!$patient) {
            return back()
                ->withErrors(['email' => 'Patient record not found for this account.'])
                ->withInput();
        }

        if ($patient->password !== $user->password) {
            $patient->forceFill([
                'password' => $user->password,
            ])->save();
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        session([
            'patient_id' => $patient->id,
            'role' => 'patient',
        ]);
        $this->concurrentSessionService->syncCurrentSessionMetadata($request);

        $sessionResult = $this->concurrentSessionService->enforceLimitForCurrentSession(
            $user,
            $request->session()->getId()
        );

        AuditLogger::log(
            'login',
            'patient_auth',
            "Patient logged in"
        );

        session()->flash('show_terms_modal', true);

        $redirect = redirect()->route('patient.dashboard');

        if (($sessionResult['terminated_sessions'] ?? 0) > 0) {
            $redirect->with(
                'success',
                'Logged in successfully. Older active session(s) were closed for your account.'
            );
        }

        return $redirect;
    }

    public function logout(Request $request)
    {
        $patient = Auth::user()?->patient;

        if ($patient) {
            $this->concurrentSessionService->recordLogoutActivity($patient->user, 'manual', 'patient_auth');
        }

        Auth::guard('patient')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully!');
    }

    public function dashboard()
    {
        $patient = Auth::user()?->patient;

        if ($patient) {
            AuditLogger::log(
                'view',
                'patient_dashboard',
                "Patient viewed dashboard"
            );
        }

        return view('dashboard', compact('patient'));
    }
}
