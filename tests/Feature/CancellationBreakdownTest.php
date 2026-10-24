<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AppointmentCancellationAnalytics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00'));
        $this->patient = Patient::create([
            'name' => 'Analytics Patient', 'email' => 'analytics-patient@example.test',
            'classification' => 'faculty', 'password' => bcrypt('password'),
        ]);
    }

    private function user(string $slug, bool $permitted = true): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        if ($permitted) {
            foreach (['create_report_files', 'view_reports'] as $permissionSlug) {
                $permission = Permission::firstOrCreate(['slug' => $permissionSlug], ['name' => $permissionSlug]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }

        return User::create([
            'name' => 'Analytics '.$slug, 'email' => uniqid('analytics-').'@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
    }

    private function appointment(string $status, ?string $reason = null, string $date = '2026-10-15', ?User $dentist = null): Appointment
    {
        static $sequence = 0;
        $sequence++;

        return Appointment::create([
            'patient_id' => $this->patient->id, 'dentist_id' => $dentist?->id,
            'appointment_date' => $date, 'appointment_time' => '09:'.str_pad((string) ($sequence % 60), 2, '0', STR_PAD_LEFT).':00',
            'service_type' => 'Oral Checkup', 'status' => $status, 'cancellation_reason' => $reason,
        ]);
    }

    public function test_reasons_are_normalized_and_rates_share_the_current_month_denominator(): void
    {
        foreach (['Patient request', ' patient   REQUEST ', 'Doctor unavailable', null, '   '] as $reason) {
            $this->appointment('cancelled', $reason);
        }
        $this->appointment('cancelled', 'Patient no-show');
        $this->appointment('no_show');
        $this->appointment('no-show');
        $this->appointment('confirmed'); // Past appointments are not implicitly missed.
        $this->appointment('completed');
        $this->appointment('cancelled', 'Previous month only', '2026-09-15');
        $this->appointment('no_show', null, '2026-09-15');
        $result = app(AppointmentCancellationAnalytics::class)->currentMonth();

        $this->assertTrue($result['available']);
        $this->assertSame('October 2026', $result['period']);
        $this->assertSame(10, $result['total_scheduled']);
        $this->assertSame(6, $result['total_cancelled']);
        $this->assertEquals(60, $result['cancellation_rate']);
        $this->assertSame(3, $result['total_no_shows']);
        $this->assertEquals(30, $result['no_show_rate']);
        $this->assertSame(1, $result['legacy_no_shows']);
        $reasons = collect($result['reasons'])->keyBy('reason');
        $this->assertSame(2, $reasons['Patient request']['count']);
        $this->assertEquals(33.3, $reasons['Patient request']['percentage']);
        $this->assertSame(2, $reasons['Reason not recorded']['count']);
        $this->assertEqualsWithDelta(100, $reasons->sum('percentage'), 0.2);
        $this->assertDatabaseHas('appointments', ['cancellation_reason' => ' patient   REQUEST ']);
    }

    public function test_only_explicit_no_shows_with_elapsed_scheduled_times_are_counted(): void
    {
        $this->appointment('no_show', null, '2026-10-24');
        $futureToday = $this->appointment('cancelled', 'Patient no-show', '2026-10-23');
        $futureToday->update(['appointment_time' => '13:00:00']);
        $this->appointment('upcoming');
        $this->appointment('cancelled', 'Automatically cancelled because the scheduled appointment date elapsed.');
        $this->appointment('cancelled', 'Patient NO-SHOW', '2026-10-23');

        $result = app(AppointmentCancellationAnalytics::class)->currentMonth();
        $this->assertSame(5, $result['total_scheduled']);
        $this->assertSame(1, $result['total_no_shows']);
        $this->assertEquals(20, $result['no_show_rate']);
    }

    public function test_empty_period_keeps_zero_rates_without_extra_messages(): void
    {
        $this->actingAs($this->user('admin'))->withSession(['role' => 'admin']);
        $response = $this->get(route('admin.report-files'))->assertOk()
            ->assertDontSee('No cancelled appointments were recorded for this period.')
            ->assertDontSee('No no-show appointments were recorded for this period.')
            ->assertDontSee('Rates use all appointment records scheduled for')
            ->assertSee('data-open-modal="cancellationBreakdownModal"', false);
        $result = $response->viewData('cancellationBreakdown');
        $this->assertSame(0, $result['total_scheduled']);
        $this->assertSame(0, $result['cancellation_rate']);
        $this->assertSame(0, $result['no_show_rate']);
        $this->assertEqualsCanonicalizing(Appointment::CANCELLATION_REASONS, array_column($result['reasons'], 'reason'));
        foreach ($result['reasons'] as $reason) {
            $response->assertSee($reason['reason']);
            $this->assertSame(0, $reason['count']);
            $this->assertSame(0, $reason['percentage']);
        }
    }

    public function test_dentist_card_and_modal_use_the_same_permitted_appointment_snapshot(): void
    {
        $dentist = $this->user('dentist');
        $this->appointment('cancelled', 'Patient request', dentist: $dentist);
        $this->appointment('completed', dentist: $dentist);
        // A patient assigned to another dentist must stay outside this dentist's analytics.
        $otherPatient = Patient::create(['name' => 'Other Patient', 'email' => 'other-analytics@example.test', 'classification' => 'faculty', 'password' => bcrypt('password')]);
        $other = $this->appointment('cancelled', 'Private other reason');
        $other->update(['patient_id' => $otherPatient->id, 'dentist_id' => $this->user('dentist')->id]);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);

        foreach (['dentist.dentist.report', 'dentist.report-files'] as $route) {
            $response = $this->get(route($route))->assertOk()
                ->assertSee('Cancellation Breakdown')->assertDontSee('Private other reason');
            $stats = $response->viewData('reportStats');
            $result = $response->viewData('cancellationBreakdown');
            $this->assertEquals(50, $stats['cancellation_rate']);
            $this->assertSame($result['cancellation_rate'], $stats['cancellation_rate']);
            $this->assertSame(2, $result['total_scheduled']);
        }
    }

    public function test_analytics_failure_has_a_visible_error_instead_of_zero_statistics(): void
    {
        $this->mock(AppointmentCancellationAnalytics::class)->shouldReceive('currentMonth')->once()
            ->andReturn(['available' => false, 'period' => 'October 2026', 'years' => [2026]]);
        $this->actingAs($this->user('admin'))->withSession(['role' => 'admin'])
            ->get(route('admin.report-files'))->assertOk()->assertSee('Unavailable')
            ->assertSee('Cancellation and no-show statistics could not be loaded.')
            ->assertSee('value="2025"', false)->assertSee('value="2021"', false);
    }

    public function test_existing_reports_permissions_protect_the_breakdown(): void
    {
        $this->get(route('admin.report-files'))->assertRedirect('/login');
        $this->actingAs($this->user('admin', false))->withSession(['role' => 'admin'])
            ->get(route('admin.report-files'))->assertForbidden();
        $this->actingAs($this->user('dentist', false))->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.report'))->assertForbidden();
    }

    public function test_month_and_year_filters_load_history_and_compare_no_shows_against_today(): void
    {
        $this->appointment('cancelled', 'Historical reason', '2024-02-29');
        $this->appointment('no_show', null, '2024-02-28');
        $this->appointment('completed', null, '2024-02-27');
        $this->appointment('cancelled', 'Different month', '2024-03-01');
        $this->appointment('cancelled', 'Current reason');
        $this->actingAs($this->user('admin'))->withSession(['role' => 'admin']);

        $this->getJson(route('admin.reports.cancellation-breakdown', ['month' => 2, 'year' => 2024]))
            ->assertOk()->assertJson([
                'period' => 'February 2024', 'month' => 2, 'year' => 2024,
                'total_scheduled' => 3, 'total_cancelled' => 1, 'cancellation_rate' => 33,
                'total_no_shows' => 1, 'no_show_rate' => 33.3, 'years' => [2026, 2025, 2024, 2023, 2022, 2021],
                'reasons' => [['reason' => 'Historical reason', 'count' => 1, 'percentage' => 100]],
            ])->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson(route('admin.reports.cancellation-breakdown', ['month' => 1, 'year' => 2024]))
            ->assertOk()->assertJson(['total_scheduled' => 0, 'cancellation_rate' => 0, 'no_show_rate' => 0]);
        $this->get(route('admin.report-files'))->assertOk()
            ->assertSee('id="cancellationMonth"', false)->assertSee('id="cancellationYear"', false)
            ->assertSee('value="2024"', false);
    }

    public function test_filter_endpoint_validates_input_and_handles_analytics_errors(): void
    {
        $this->actingAs($this->user('admin'))->withSession(['role' => 'admin']);
        foreach ([['month' => 0, 'year' => 2024], ['month' => 13, 'year' => 2024], ['month' => 1, 'year' => 'invalid'], ['month' => 1, 'year' => 2027], []] as $filter) {
            $this->getJson(route('admin.reports.cancellation-breakdown', $filter))->assertUnprocessable();
        }
        $this->mock(AppointmentCancellationAnalytics::class)->shouldReceive('forMonth')->once()
            ->with(2024, 2)->andReturn(['available' => false, 'period' => 'February 2024']);
        $this->getJson(route('admin.reports.cancellation-breakdown', ['month' => 2, 'year' => 2024]))
            ->assertStatus(503)->assertJson(['available' => false]);
    }

    public function test_historical_endpoint_keeps_existing_permissions_and_clinical_scope(): void
    {
        $filter = ['month' => 2, 'year' => 2024];
        $this->get(route('admin.reports.cancellation-breakdown', $filter))->assertRedirect('/login');
        $this->actingAs($this->user('admin', false))->withSession(['role' => 'admin'])
            ->getJson(route('admin.reports.cancellation-breakdown', $filter))->assertForbidden();
        $this->actingAs($this->user('dentist', false))->withSession(['role' => 'dentist'])
            ->getJson(route('dentist.reports.cancellation-breakdown', $filter))->assertForbidden();

        $dentist = $this->user('dentist');
        $this->appointment('cancelled', 'Allowed historical reason', '2024-02-10', $dentist);
        $otherPatient = Patient::create(['name' => 'Private history', 'email' => 'private-history@example.test', 'classification' => 'faculty', 'password' => bcrypt('password')]);
        $other = $this->appointment('cancelled', 'Private historical reason', '2024-02-11');
        $other->update(['patient_id' => $otherPatient->id, 'dentist_id' => $this->user('dentist')->id]);
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->getJson(route('dentist.reports.cancellation-breakdown', $filter))->assertOk()
            ->assertJson(['total_scheduled' => 1, 'total_cancelled' => 1])
            ->assertDontSee('Private historical reason');
    }
}
