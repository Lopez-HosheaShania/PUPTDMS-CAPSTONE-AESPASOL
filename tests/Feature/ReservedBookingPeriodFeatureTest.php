<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\DentalHistory;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\ReservedBookingPeriod;
use App\Models\ServiceType;
use App\Models\Role;
use App\Models\User;
use App\Services\DentistDutyService;
use App\Services\StudentTargetOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservedBookingPeriodFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function assertPeriodDatabaseHas(array $values): void
    {
        $query = ReservedBookingPeriod::withTrashed()
            ->withScheduleColumns()
            ->join('reserved_booking_period_configurations as config', 'config.reserved_booking_period_id', '=', 'reserved_booking_periods.id')
            ->join('reserved_booking_period_targets as target', 'target.reserved_booking_period_id', '=', 'reserved_booking_periods.id');
        foreach ($values as $field => $value) {
            $query->where($field === 'id' ? 'reserved_booking_periods.id' : $field, $value);
        }
        $this->assertTrue($query->exists(), 'Expected normalized reserved period matching '.json_encode($values));
    }

    private const RESERVED_DATE = '2026-09-07';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-01 08:00:00');

        $this->mock(StudentTargetOptionService::class, function ($mock) {
            $mock->shouldReceive('get')->andReturn(collect([
                [
                    'course_code' => 'BSIT',
                    'course_name' => 'Bachelor of Science in Information Technology',
                    'year_level' => 1,
                    'section' => '1',
                ],
            ]));
        });

        ClinicSchedule::create([
            'days_label' => 'Mon',
            'days' => ['Mon'],
            'status' => 'open',
            'open_time' => '08:00',
            'close_time' => '17:00',
            'break_time' => 'none',
            'max_slots' => 20,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_update_and_soft_delete_a_student_reserved_period(): void
    {
        $admin = $this->makeScheduleManager('admin');

        $this->from(route('admin.clinic_schedule'))
            ->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertRedirect(route('admin.clinic_schedule'))
            ->assertSessionHas('success');

        $period = ReservedBookingPeriod::firstOrFail();

        $this->assertPeriodDatabaseHas([
            'id' => $period->id,
            'title' => 'Mandatory Oral Check-up',
            'reserved_date' => self::RESERVED_DATE,
            'active_reserved_date' => self::RESERVED_DATE,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'booking_mode' => 'timeslot',
            'timeslot_duration_minutes' => 30,
            'target_patient_type' => 'student',
            'program_code' => 'BSIT',
            'year_level' => 1,
            'section' => '1',
            'max_capacity' => 4,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('reserved_booking_period_slots', [
            'reserved_booking_period_id' => $period->id,
            'slot_time' => '09:00:00',
            'max_capacity' => 1,
        ]);
        $this->assertDatabaseCount('reserved_booking_period_slots', 4);

        $updatedPayload = [
            ...$this->studentPayload(),
            'title' => 'Updated Oral Check-up',
            'timeslot_duration_minutes' => 45,
            'timeslots' => [
                ['time' => '09:00'],
                ['time' => '10:00'],
                ['time' => '11:00'],
            ],
            'reserved_period_id' => $period->id,
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->put(
                route('admin.clinic_schedule.reserved_periods.update', $period),
                $updatedPayload
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertPeriodDatabaseHas([
            'id' => $period->id,
            'title' => 'Updated Oral Check-up',
            'max_capacity' => 3,
            'timeslot_duration_minutes' => 45,
        ]);
        $this->assertDatabaseCount('reserved_booking_period_slots', 3);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->delete(route('admin.clinic_schedule.reserved_periods.destroy', $period))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('reserved_booking_periods', [
            'id' => $period->id,
        ]);
    }

    public function test_reserved_period_actions_use_the_granular_clinic_schedule_permissions(): void
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $permissions = collect([
            'view_clinic_schedule' => 'View Schedule and Dates',
            'create_clinic_schedule' => 'Create Clinic Hours',
            'update_clinic_schedule' => 'Update Clinic Hours',
            'delete_clinic_schedule' => 'Delete Clinic Hours',
        ])->mapWithKeys(function (string $name, string $slug) {
            $permission = Permission::create([
                'name' => $name,
                'slug' => $slug,
                'module' => 'Clinic Schedule',
            ]);

            return [$slug => $permission];
        });

        $role->permissions()->attach($permissions['view_clinic_schedule']);

        $admin = User::create([
            'name' => 'Granular Schedule Admin',
            'email' => 'granular-schedule-admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->get(route('admin.clinic_schedule'))
            ->assertOk()
            ->assertSee('Reserved Booking Periods')
            ->assertDontSee('>Add Period<', false)
            ->assertDontSee('aria-label="Edit reserved period"', false)
            ->assertDontSee('aria-label="Remove reserved period"', false);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertForbidden();

        $role->permissions()->attach($permissions['create_clinic_schedule']);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $period = ReservedBookingPeriod::firstOrFail();

        $role->permissions()->detach($permissions['create_clinic_schedule']);
        $role->permissions()->attach($permissions['update_clinic_schedule']);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->get(route('admin.clinic_schedule'))
            ->assertOk()
            ->assertSee('aria-label="Edit reserved period"', false)
            ->assertDontSee('aria-label="Remove reserved period"', false);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->put(
                route('admin.clinic_schedule.reserved_periods.update', $period),
                [...$this->studentPayload(), 'title' => 'Permission Updated Period']
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $role->permissions()->detach($permissions['update_clinic_schedule']);
        $role->permissions()->attach($permissions['delete_clinic_schedule']);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->get(route('admin.clinic_schedule'))
            ->assertOk()
            ->assertDontSee('aria-label="Edit reserved period"', false)
            ->assertSee('aria-label="Remove reserved period"', false);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->delete(route('admin.clinic_schedule.reserved_periods.destroy', $period))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('reserved_booking_periods', ['id' => $period->id]);
    }

    public function test_admin_and_dentist_reserved_routes_share_the_clinic_hour_permissions(): void
    {
        $expectedMiddleware = [
            'admin.clinic_schedule.reserved_periods.store' => 'permission:create_clinic_schedule',
            'admin.clinic_schedule.reserved_periods.update' => 'permission:update_clinic_schedule',
            'admin.clinic_schedule.reserved_periods.destroy' => 'permission:delete_clinic_schedule',
            'dentist.dentist.clinic_schedule.reserved_periods.store' => 'permission:create_clinic_schedule',
            'dentist.dentist.clinic_schedule.reserved_periods.update' => 'permission:update_clinic_schedule',
            'dentist.dentist.clinic_schedule.reserved_periods.destroy' => 'permission:delete_clinic_schedule',
        ];

        foreach ($expectedMiddleware as $routeName => $middleware) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }

    public function test_admin_and_dentist_can_render_the_shared_reserved_period_setup(): void
    {
        foreach (['admin', 'dentist'] as $roleSlug) {
            $manager = $this->makeScheduleManager($roleSlug);
            $routeName = $roleSlug === 'admin'
                ? 'admin.clinic_schedule'
                : 'dentist.dentist.clinic_schedule';

            $this->actingAs($manager)
                ->withSession(['role' => $roleSlug])
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('Reserved Booking Periods')
                ->assertSee('Create Reserved Booking Period');
        }
    }

    public function test_dentist_uses_the_same_setup_and_non_student_targets_store_no_student_fields(): void
    {
        $dentist = $this->makeScheduleManager('dentist');

        $payload = [
            ...$this->basePayload(),
            'target_patient_type' => 'faculty',
            'booking_mode' => 'date_only',
        ];

        $this->from(route('dentist.dentist.clinic_schedule'))
            ->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->post(
                route('dentist.dentist.clinic_schedule.reserved_periods.store'),
                $payload
            )
            ->assertRedirect(route('dentist.dentist.clinic_schedule'))
            ->assertSessionHasNoErrors(null, 'reservedPeriod')
            ->assertSessionHas('success');

        $this->assertPeriodDatabaseHas([
            'target_patient_type' => 'faculty',
            'booking_mode' => 'date_only',
            'program_code' => null,
            'year_level' => null,
            'section' => null,
            'created_by' => $dentist->id,
        ]);

        $response = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.clinic_schedule'))
            ->assertOk()
            ->assertSee('Reserved Booking Periods')
            ->assertSee('Mandatory Oral Check-up')
            ->assertSee('id="reservedPeriodsViewToggle"', false)
            ->assertSee('id="reservedPeriodsListView"', false)
            ->assertSee('id="reservedPeriodsGridView"', false)
            ->assertSee('id="reservedTitleVoiceStatus"', false)
            ->assertSee('data-voice-target="#reservedTitle"', false)
            ->assertSee('name="program_code"', false)
            ->assertSee('name="year_level"', false)
            ->assertSee('name="section"', false)
            ->assertSee('Bachelor of Science in Information Technology');

        $html = $response->getContent();
        $mainClosingPosition = strpos($html, '</main>');
        $reservedModalPosition = strpos($html, 'id="reservedPeriodModalBackdrop"');

        $this->assertSame(1, substr_count($html, 'id="reservedPeriodModalBackdrop"'));
        $this->assertNotFalse($mainClosingPosition);
        $this->assertNotFalse($reservedModalPosition);
        $this->assertGreaterThan(
            $mainClosingPosition,
            $reservedModalPosition,
            'The reserved-period modal must render outside the schedule table and page content.'
        );
    }

    public function test_student_target_requires_program_year_and_section(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $payload = $this->basePayload();
        $payload['target_patient_type'] = 'student';

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $payload
            )
            ->assertSessionHasErrors(
                ['program_code', 'year_level', 'section'],
                null,
                'reservedPeriod'
            );

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->get(route('admin.clinic_schedule'))
            ->assertOk()
            ->assertSee('Select a program for the student group.');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_reserved_period_must_leave_part_of_the_clinic_day_for_regular_booking(): void
    {
        $admin = $this->makeScheduleManager('admin');

        $payload = [
            ...$this->studentPayload(),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $payload
            )
            ->assertSessionHasErrors(['end_time'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_date_with_an_existing_reserved_period_cannot_be_selected_again(): void
    {
        $admin = $this->makeScheduleManager('admin');

        ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $sameDate = [
            ...$this->studentPayload(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'timeslots' => [
                ['time' => '14:00'],
            ],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $sameDate
            )
            ->assertSessionHasErrors(['reserved_date'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 1);
    }

    public function test_reserved_period_capacity_cannot_exceed_thirty_patients(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $dateOnlyPayload = [
            ...$this->basePayload(),
            'max_capacity' => ReservedBookingPeriod::MAX_CAPACITY + 1,
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $dateOnlyPayload)
            ->assertSessionHasErrors(['max_capacity'], null, 'reservedPeriod');

        $timeslotPayload = [
            ...$this->studentPayload(),
            'timeslot_duration_minutes' => 5,
            'timeslots' => collect(range(0, ReservedBookingPeriod::MAX_CAPACITY))
                ->map(function (int $index) {
                    $minutes = (9 * 60) + ($index * 5);

                    return [
                        'time' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
                    ];
                })
                ->all(),
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $timeslotPayload)
            ->assertSessionHasErrors(['timeslots'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_database_enforces_one_active_reserved_period_per_date(): void
    {
        $admin = $this->makeScheduleManager('admin');

        ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->expectException(QueryException::class);

        ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'title' => 'Duplicate Date Period',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_blocked_date_cannot_receive_a_reserved_period(): void
    {
        $admin = $this->makeScheduleManager('admin');

        BlockedDate::create([
            'date' => self::RESERVED_DATE,
            'reason' => 'Dentist Unavailable',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertSessionHasErrors(['reserved_date'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_working_holiday_can_receive_an_active_reserved_period(): void
    {
        $admin = $this->makeScheduleManager('admin');

        ClinicSchedule::create([
            'days_label' => 'Tue',
            'days' => ['Tue'],
            'status' => 'open',
            'open_time' => '08:00',
            'close_time' => '17:00',
            'break_time' => 'none',
            'max_slots' => 20,
            'is_active' => true,
        ]);

        $payload = [
            ...$this->basePayload(),
            'reserved_date' => '2026-09-08',
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHas('success');

        $this->assertPeriodDatabaseHas([
            'reserved_date' => '2026-09-08',
            'active_reserved_date' => '2026-09-08',
            'is_active' => true,
        ]);
    }

    public function test_date_only_capacity_can_be_increased_after_a_patient_books(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $patient = $this->makePatientUser()->patient;
        $period = ReservedBookingPeriod::create([
            'title' => 'Faculty Check-up Day',
            'reserved_date' => self::RESERVED_DATE,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'booking_mode' => 'date_only',
            'target_patient_type' => 'faculty',
            'max_capacity' => 10,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'reserved_booking_period_id' => $period->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        $payload = [
            ...$this->basePayload(),
            'title' => 'Faculty Check-up Day',
            'max_capacity' => 15,
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->put(route('admin.clinic_schedule.reserved_periods.update', $period), $payload)
            ->assertSessionHas('success');

        $this->assertSame(15, $period->fresh()->max_capacity);
    }

    public function test_new_timeslots_can_be_added_after_a_patient_books(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $patient = $this->makePatientUser()->patient;
        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'max_capacity' => 2,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $firstSlot = $period->slots()->create([
            'slot_time' => '09:00:00',
            'max_capacity' => 1,
        ]);
        $period->slots()->create([
            'slot_time' => '10:00:00',
            'max_capacity' => 1,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'reserved_booking_period_id' => $period->id,
            'reserved_booking_period_slot_id' => $firstSlot->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        $payload = [
            ...$this->studentPayload(),
            'timeslots' => [
                ['time' => '09:00'],
                ['time' => '10:00'],
                ['time' => '11:00'],
            ],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->put(route('admin.clinic_schedule.reserved_periods.update', $period), $payload)
            ->assertSessionHas('success');

        $this->assertSame(3, $period->fresh()->max_capacity);
        $this->assertSame(
            ['09:00:00', '10:00:00', '11:00:00'],
            $period->slots()->pluck('slot_time')->all()
        );
        $this->assertDatabaseHas('appointment_reserved_bookings', [
            'appointment_id' => $appointment->id,
            'reserved_booking_period_slot_id' => $firstSlot->id,
        ]);
    }

    public function test_period_with_an_existing_regular_appointment_is_rejected(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $patient = Patient::create([
            'name' => 'Existing Patient',
            'email' => 'existing-patient@example.com',
            'phone' => '09171234567',
            'birthdate' => '2000-01-01',
            'gender' => 'Female',
            'password' => bcrypt('password'),
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '10:00:00',
            'status' => 'upcoming',
        ]);

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertSessionHasErrors(['start_time'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_timeslots_must_be_unique_and_inside_the_reserved_period(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $payload = $this->studentPayload();
        $payload['timeslots'] = [
            ['time' => '08:30'],
            ['time' => '09:00'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['timeslots'], null, 'reservedPeriod');

        $payload['timeslots'] = [
            ['time' => '09:00'],
            ['time' => '09:00'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['timeslots'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
        $this->assertDatabaseCount('reserved_booking_period_slots', 0);
    }

    public function test_timeslot_duration_prevents_overlap_and_period_overrun(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $payload = $this->studentPayload();
        $payload['timeslot_duration_minutes'] = 45;
        $payload['timeslots'] = [
            ['time' => '09:00'],
            ['time' => '09:30'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['timeslots'], null, 'reservedPeriod');

        $payload['timeslots'] = [
            ['time' => '12:30'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['timeslots'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_student_target_combination_must_exist_in_external_options(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $payload = $this->studentPayload();
        $payload['section'] = '99';

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['program_code'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    public function test_eligible_patient_receives_a_reserved_booking_notification(): void
    {
        $patientUser = $this->makePatientUser();
        $admin = $this->makeScheduleManager('admin');

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(
                route('admin.clinic_schedule.reserved_periods.store'),
                $this->studentPayload()
            )
            ->assertSessionHas('success');

        $period = ReservedBookingPeriod::firstOrFail();
        $notification = $patientUser->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame($period->id, (int) data_get($notification->data, 'reserved_booking_period_id'));
        $this->assertSame(route('book.appointment.reserved', $period), data_get($notification->data, 'url'));
        $this->assertStringContainsString('is scheduled for your patient group', data_get($notification->data, 'message'));
        $this->assertStringNotContainsString('invited', data_get($notification->data, 'message'));
    }

    public function test_patient_dashboard_keeps_reserved_reminder_until_booking_or_period_end(): void
    {
        $patientUser = $this->makePatientUser();
        $patient = $patientUser->patient;
        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $patientUser->id,
            'updated_by' => $patientUser->id,
        ]);
        $period->slots()->create([
            'slot_time' => '09:00:00',
            'max_capacity' => 1,
        ]);
        MedicalHistory::create([
            'patient_id' => $patient->id,
            'emergency_person' => 'Test Contact',
            'emergency_number' => '09170000000',
            'emergency_relation' => 'Parent',
        ]);

        $dashboard = fn () => $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('homepage'));

        $dashboard()
            ->assertOk()
            ->assertSee('Reserved Appointment Reminder')
            ->assertSee('Mandatory Oral Check-up')
            ->assertSee('Book Reserved Appointment')
            ->assertSee(route('book.appointment.reserved', $period), false);

        $patientUser->fresh()->notifications()->firstOrFail()->markAsRead();

        $dashboard()->assertOk();

        $this->assertNotNull(
            $patientUser->fresh()->notifications()->firstOrFail()->read_at,
            'Dashboard reminder synchronization must preserve a notification the patient already read.'
        );

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'reserved_booking_period_id' => $period->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        $dashboard()
            ->assertOk()
            ->assertDontSee('Book Reserved Appointment');

        $appointment->delete();
        Carbon::setTestNow(self::RESERVED_DATE.' 00:00:01');

        $dashboard()
            ->assertOk()
            ->assertSee('Book Reserved Appointment');

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('book.appointment.reserved', $period))
            ->assertOk();

        Carbon::setTestNow(self::RESERVED_DATE.' 13:00:01');

        $dashboard()
            ->assertOk()
            ->assertDontSee('Book Reserved Appointment');

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('book.appointment.reserved', $period))
            ->assertRedirect(route('homepage'))
            ->assertSessionHas('error');
    }

    public function test_normal_booking_still_rejects_same_day_slot_requests(): void
    {
        $patientUser = $this->makePatientUser();
        $patient = $patientUser->patient;

        ClinicSchedule::query()->update([
            'days_label' => 'Tue',
            'days' => ['Tue'],
        ]);

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->getJson(route('book.appointment.slots', [
                'date' => Carbon::today()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_student_emergency_contact_sync_does_not_skip_the_reserved_medical_form(): void
    {
        $patientUser = $this->makePatientUser();
        $patient = $patientUser->patient;
        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $patientUser->id,
            'updated_by' => $patientUser->id,
        ]);
        $period->slots()->create([
            'slot_time' => '09:00:00',
            'max_capacity' => 1,
        ]);

        // This is the partial record created when a student's emergency contact
        // is synchronized before they have answered the medical questionnaire.
        MedicalHistory::create([
            'patient_id' => $patient->id,
            'emergency_person' => 'Student Contact',
            'emergency_number' => '09170000000',
            'emergency_relation' => 'Not specified',
        ]);

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('book.appointment.reserved', $period))
            ->assertOk()
            ->assertViewHas('hasExistingMedicalHistory', false)
            ->assertViewHas('hasExistingBookingInformation', false);
    }

    public function test_reserved_booking_uses_the_assigned_slot_and_preserves_existing_histories(): void
    {
        Mail::fake();
        Notification::fake();

        $dentist = $this->makeScheduleManager('dentist');
        app(DentistDutyService::class)->clockOut($dentist, Carbon::now());
        $patientUser = $this->makePatientUser();
        $patient = $patientUser->patient;
        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $patientUser->id,
            'updated_by' => $patientUser->id,
        ]);
        $slot = $period->slots()->create([
            'slot_time' => '10:00:00',
            'max_capacity' => 1,
        ]);

        DentalHistory::create([
            'patient_id' => $patient->id,
            'last_dental_visit' => '2026-01-15',
            'previous_dentist' => 'Dr. Existing',
        ]);
        MedicalHistory::create([
            'patient_id' => $patient->id,
            'emergency_person' => 'Existing Contact',
            'emergency_number' => '09170000000',
            'emergency_relation' => 'Mother',
            'patient_signature' => 'signatures/existing.png',
            'signature_review_status' => 'verified',
        ]);
        ServiceType::create([
            'name' => 'Oral Check-up',
            'description' => 'Mandatory oral examination.',
            'is_active_for_booking' => true,
            'is_default' => true,
        ]);

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('book.appointment.create'))
            ->assertOk()
            ->assertSee('Select Date &amp; Time', false);

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->get(route('book.appointment.reserved', $period))
            ->assertOk()
            ->assertSee('Pick a Reserved Timeslot')
            ->assertSee('appointment-slot-grid slot-grid-ui', false)
            ->assertSee('Clear selection')
            ->assertSee('Mandatory Oral Check-up');

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
            ])
            ->post(route('book.appointment.store'), [
                'reserved_booking_period_id' => $period->id,
                'reserved_booking_period_slot_id' => $slot->id,
                'appointment_date' => '2026-09-14',
                'appointment_time' => '4:00 PM',
                'service_type' => 'Oral Check-up',
                'final_confirmation' => true,
                'contact_email' => $patient->email,
                'contact_phone' => '09171234567',
                'contact_address' => 'PUP Taguig Campus',
                'emergency_person' => 'Existing Contact',
                'emergency_number' => '09170000000',
                'emergency_relation' => 'Mother',
                'diseases' => [],
            ])
            ->assertRedirect(route('homepage'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '10:00:00',
            'status' => 'upcoming',
        ]);
        $this->assertDatabaseHas('appointment_reserved_bookings', [
            'appointment_id' => Appointment::where('patient_id', $patient->id)->firstOrFail()->id,
            'booking_patient_id' => $patient->id,
            'reserved_booking_period_id' => $period->id,
            'reserved_booking_period_slot_id' => $slot->id,
        ]);
        $this->assertDatabaseHas('dental_histories', [
            'patient_id' => $patient->id,
            'previous_dentist' => 'Dr. Existing',
        ]);
        $this->assertSame(
            '2026-01-15',
            DentalHistory::where('patient_id', $patient->id)->firstOrFail()->last_dental_visit->format('Y-m-d')
        );
        $this->assertDatabaseHas('medical_histories', [
            'patient_id' => $patient->id,
            'emergency_person' => 'Existing Contact',
            'patient_signature' => 'signatures/existing.png',
        ]);
    }

    public function test_reserved_appointment_uses_the_period_window_instead_of_its_selected_time(): void
    {
        $patientUser = $this->makePatientUser();
        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $patientUser->id,
            'updated_by' => $patientUser->id,
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'reserved_booking_period_id' => $period->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '12:00:00',
            'status' => 'upcoming',
        ])->load('reservedBookingPeriod');

        $this->assertFalse($appointment->reservedProcedureWindowIsOpen(Carbon::parse('2026-09-07 08:59:59')));
        $this->assertTrue($appointment->reservedProcedureWindowIsOpen(Carbon::parse('2026-09-07 09:15:00')));
        $this->assertFalse($appointment->reservedProcedureWindowIsOpen(Carbon::parse('2026-09-07 13:00:01')));
    }

    public function test_viewing_a_patient_profile_does_not_expire_a_reserved_appointment_before_the_period_ends(): void
    {
        $patientUser = $this->makePatientUser();
        $dentist = $this->makeScheduleManager('dentist');
        $profilePermission = Permission::firstOrCreate(
            ['slug' => 'manage_patient_profiles'],
            [
                'name' => 'Manage Patient Profiles',
                'module' => 'Patients',
            ]
        );
        $dentist->role->permissions()->syncWithoutDetaching([$profilePermission->id]);

        $period = ReservedBookingPeriod::create([
            ...$this->storedStudentPayload(),
            'created_by' => $dentist->id,
            'updated_by' => $dentist->id,
        ]);
        $slot = $period->slots()->create([
            'slot_time' => '09:00:00',
            'max_capacity' => 1,
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'reserved_booking_period_id' => $period->id,
            'reserved_booking_period_slot_id' => $slot->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => self::RESERVED_DATE,
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        Carbon::setTestNow(self::RESERVED_DATE.' 10:30:00');

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.patient.profile', $patientUser->patient))
            ->assertOk();

        $this->assertSame('upcoming', $appointment->fresh()->status);
        $this->assertSame($slot->id, $appointment->fresh()->reserved_booking_period_slot_id);
    }

    public function test_reserved_period_stores_and_shows_only_its_allowed_services(): void
    {
        $patientUser = $this->makePatientUser();
        $admin = $this->makeScheduleManager('admin');

        ServiceType::create([
            'name' => 'Oral Check-up',
            'description' => 'Routine oral assessment.',
            'is_active_for_booking' => true,
            'is_default' => true,
        ]);
        ServiceType::create([
            'name' => 'Dental Surgery',
            'description' => 'Surgical dental care.',
            'is_active_for_booking' => true,
            'is_default' => true,
        ]);

        $payload = [
            ...$this->studentPayload(),
            'is_active' => '1',
            'allowed_services_present' => '1',
            'allowed_services' => ['Oral Check-up'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHas('success');

        $period = ReservedBookingPeriod::firstOrFail();

        $this->assertSame(['Oral Check-up'], $period->allowed_services);

        $this->actingAs($patientUser)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patientUser->patient->id,
            ])
            ->get(route('book.appointment.reserved', $period))
            ->assertOk()
            ->assertSee('Oral Check-up')
            ->assertDontSee('Dental Surgery');
    }

    public function test_reserved_period_rejects_an_unavailable_allowed_service(): void
    {
        $admin = $this->makeScheduleManager('admin');
        $payload = [
            ...$this->basePayload(),
            'allowed_services_present' => '1',
            'allowed_services' => ['Unavailable Service'],
        ];

        $this->actingAs($admin)
            ->withSession(['role' => 'admin'])
            ->post(route('admin.clinic_schedule.reserved_periods.store'), $payload)
            ->assertSessionHasErrors(['allowed_services.0'], null, 'reservedPeriod');

        $this->assertDatabaseCount('reserved_booking_periods', 0);
    }

    private function makeScheduleManager(string $roleSlug): User
    {
        $role = Role::create([
            'name' => ucfirst($roleSlug),
            'slug' => $roleSlug,
        ]);

        $permission = Permission::firstOrCreate(
            ['slug' => 'manage_clinic_schedule'],
            [
                'name' => 'Manage Clinic Schedule',
                'module' => 'Appointments',
            ]
        );

        $role->permissions()->attach($permission);

        return User::create([
            'name' => ucfirst($roleSlug).' User',
            'email' => $roleSlug.'-schedule@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function makePatientUser(): User
    {
        $role = Role::create([
            'name' => 'Patient',
            'slug' => 'patient',
        ]);
        $permissions = collect([
            [
                'slug' => 'book_appointments',
                'name' => 'Book Appointments',
            ],
            [
                'slug' => 'access_patient_dashboard',
                'name' => 'Access Patient Dashboard',
            ],
        ])->map(fn (array $permission) => Permission::firstOrCreate(
            ['slug' => $permission['slug']],
            [
                'name' => $permission['name'],
                'module' => 'Appointments',
            ]
        ));
        $role->permissions()->attach($permissions->pluck('id')->all());

        $user = User::create([
            'name' => 'Eligible Student',
            'email' => 'eligible-student@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        Patient::create([
            'user_id' => $user->id,
            'name' => 'Eligible Student',
            'email' => 'eligible-student@example.com',
            'phone' => '09171234567',
            'birthdate' => '2004-01-01',
            'gender' => 'Female',
            'classification' => 'student',
            'student_no' => '2023-00001-TG-0',
            'course_code' => 'BSIT',
            'course_name' => 'Bachelor of Science in Information Technology',
            'year_level' => 1,
            'section' => '1',
            'address' => 'PUP Taguig Campus',
            'password' => bcrypt('password'),
        ]);

        return $user->load('patient');
    }

    private function basePayload(): array
    {
        return [
            'title' => 'Mandatory Oral Check-up',
            'reserved_date' => self::RESERVED_DATE,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'booking_mode' => 'date_only',
            'is_active' => '1',
            'target_patient_type' => 'faculty',
            'max_capacity' => 10,
            'notes' => 'First reserved-period setup test.',
        ];
    }

    private function studentPayload(): array
    {
        $payload = [
            ...$this->basePayload(),
            'booking_mode' => 'timeslot',
            'timeslot_duration_minutes' => 30,
            'target_patient_type' => 'student',
            'program_code' => 'bsit',
            'year_level' => 1,
            'section' => '1',
            'timeslots' => [
                ['time' => '09:00'],
                ['time' => '10:00'],
                ['time' => '11:00'],
                ['time' => '12:00'],
            ],
        ];

        unset($payload['max_capacity']);

        return $payload;
    }

    private function storedStudentPayload(): array
    {
        $payload = [
            ...$this->studentPayload(),
            'program_code' => 'BSIT',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'max_capacity' => 4,
            'is_active' => true,
        ];

        unset($payload['timeslots']);

        return $payload;
    }
}
