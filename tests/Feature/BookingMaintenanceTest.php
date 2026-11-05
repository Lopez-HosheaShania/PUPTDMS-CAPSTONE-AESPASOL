<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ReservedBookingPeriod;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BookingMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT',
            fn ($date, $format) => date('Y-m', strtotime($date)), 2);
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        foreach (['book_appointments', 'access_patient_dashboard'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user = User::create([
            'name' => 'Maintenance Patient', 'email' => 'maintenance@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'classification' => 'faculty', 'password' => bcrypt('password'),
        ]);
        $this->actingAs($user)->withSession(['patient_id' => $patient->id, 'role' => 'patient']);
        SystemSetting::setSetting('maintenance_mode', '1');
    }

    public function test_dashboard_and_non_booking_pages_remain_accessible(): void
    {
        foreach (['homepage', 'patient.dashboard', 'patient.about.us', 'patient.appointment.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('id="bookingMaintenanceModal"', false);
        }
    }

    public function test_all_booking_entry_routes_are_blocked_without_side_effects(): void
    {
        $period = ReservedBookingPeriod::create([
            'title' => 'Reserved faculty booking', 'reserved_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '12:00:00', 'booking_mode' => 'date_only',
            'target_patient_type' => 'faculty', 'max_capacity' => 3, 'is_active' => true,
            'created_by' => auth()->id(), 'updated_by' => auth()->id(),
        ]);
        $this->getJson(route('book.appointment.reserved', $period))->assertStatus(503)
            ->assertJson(['code' => 'booking_maintenance']);
        foreach (['patient.book.appointment', 'book.appointment.create', 'appointment.create'] as $route) {
            $this->getJson(route($route))->assertStatus(503)->assertJson(['code' => 'booking_maintenance']);
            $this->get(route($route))->assertRedirect(route('homepage'))
                ->assertSessionHas('booking_verification_error.code', 'booking_maintenance');
        }
        foreach (['appointment.store', 'book.appointment.store'] as $route) {
            $this->postJson(route($route), ['completionPercentage' => 100])->assertStatus(503)
                ->assertJson(['code' => 'booking_maintenance', 'title' => 'Clinic Temporarily Unavailable']);
            $this->post(route($route))->assertRedirect(route('homepage'))
                ->assertSessionHas('booking_verification_error.code', 'booking_maintenance');
        }
        $this->assertDatabaseCount('appointments', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
        Http::assertNothingSent();
    }

    public function test_current_setting_is_rechecked_without_reauthentication(): void
    {
        $this->getJson(route('book.appointment.verify-personal-information'))->assertStatus(503)
            ->assertJson(['code' => 'booking_maintenance'])->assertHeader('Cache-Control', 'no-store, private');
        SystemSetting::setSetting('maintenance_mode', '0');
        $this->getJson(route('book.appointment.verify-personal-information'))->assertOk()->assertJson(['verified' => true]);
        $this->get(route('patient.book.appointment'))->assertOk()->assertViewIs('patient.book-appointment');
        foreach (['appointment.store', 'book.appointment.store'] as $route) {
            $this->postJson(route($route))->assertUnprocessable()->assertJsonValidationErrors('appointment_date');
        }
        SystemSetting::setSetting('maintenance_mode', '1');
        $this->postJson(route('book.appointment.store'))->assertStatus(503)->assertJson(['code' => 'booking_maintenance']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_guest_and_permission_checks_still_apply(): void
    {
        auth()->user()->role->permissions()->detach();
        $this->postJson(route('book.appointment.store'))->assertForbidden();
        auth()->logout();
        $this->get(route('patient.book.appointment'))->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_admin_settings_and_real_service_unavailability_are_preserved(): void
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $permission = Permission::firstOrCreate(['slug' => 'manage_system_settings'], ['name' => 'Manage System Settings']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $admin = User::create([
            'name' => 'Maintenance Admin', 'email' => 'admin-maintenance@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
        $this->actingAs($admin)->withSession(['role' => 'admin'])->get(route('admin.system_settings'))->assertOk();
        Route::middleware('web')->get('/test-service-unavailable', fn () => abort(503));
        $this->get('/test-service-unavailable')->assertStatus(503);
    }
}
