<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Role;
use App\Models\User;
use App\Services\FacultyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FacultyController extends Controller
{
    protected $facultyService;

    public function __construct(FacultyApiService $facultyService)
    {
        $this->facultyService = $facultyService;
    }

    public function getFacultyList()
    {
        try {
            return response()->json($this->facultyService->getFaculties());
        } catch (\Throwable $e) {
            Log::error('Failed to fetch faculty list', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch faculty list.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'faculty_json'   => 'required',
            'cms_role'       => 'required|in:patient,admin,dentist',
            'account_status' => 'required|in:Active,Inactive',
        ]);

        $facultyData = json_decode($request->faculty_json, true);

        if (!is_array($facultyData)) {
            return back()->with('error', 'Invalid faculty data.');
        }

        $facultyId = $facultyData['faculty_id'] ?? null;
        $email     = $facultyData['email'] ?? null;

        if (empty($facultyId)) {
            return back()->with('error', 'Faculty ID is missing.');
        }

        if (empty($email)) {
            return back()->with('error', 'Faculty email is missing.');
        }

        $existingFaculty = Faculty::where('fesr_user_id', $facultyId)->first();
        if ($existingFaculty) {
            return back()->with('error', 'Faculty already exists.');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return back()->with('error', 'A user with this email already exists.');
        }

        $facultyTypeMap = [
            'Full-Time' => 1,
            'Part-Time' => 2,
            'Temporary' => 3,
            'Designee'  => 4,
        ];

        $facultyTypeRaw = $facultyData['faculty_type'] ?? null;
        $facultyTypeId  = $facultyTypeMap[$facultyTypeRaw] ?? 1;

        $firstName  = trim((string) ($facultyData['first_name'] ?? ''));
        $middleName = trim((string) ($facultyData['middle_name'] ?? ''));
        $lastName   = trim((string) ($facultyData['last_name'] ?? ''));
        $suffixName = trim((string) ($facultyData['suffix_name'] ?? ''));
        $code       = $facultyData['faculty_code'] ?? null;

        $fullName = trim(implode(' ', array_filter([
            $firstName,
            $middleName,
            $lastName,
            $suffixName,
        ])));

        if ($fullName === '') {
            $fullName = $email;
        }

        $role = Role::where('slug', $request->cms_role)->first();

        if (!$role) {
            return back()->with('error', 'Selected CMS role is invalid. Role not found in database.');
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name'        => $fullName,
                'first_name'  => $firstName !== '' ? $firstName : null,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name'   => $lastName !== '' ? $lastName : null,
                'suffix_name' => $suffixName !== '' ? $suffixName : null,
                'code'        => $code,
                'email'       => $email,
                'password'    => Hash::make('password123'),
                'role_id'     => $role->id,
                'status'      => strtolower($request->account_status),
            ]);

            Faculty::create([
                'user_id'         => $user->id,
                'faculty_type_id' => $facultyTypeId,
                'fesr_user_id'    => $facultyId,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Faculty saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to save faculty', [
                'message'      => $e->getMessage(),
                'faculty_data' => $facultyData,
                'cms_role'     => $request->cms_role,
                'status'       => $request->account_status,
            ]);

            return redirect()->back()->with('error', 'Failed to save faculty. Check logs for details.');
        }
    }
}