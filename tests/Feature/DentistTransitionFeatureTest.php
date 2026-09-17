<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentistTransition;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DentistTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DentistTransitionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_create_a_transition(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.store'), [
                'dentist_id' => $departingDentist->id,
                'transition_type' => 'retirement',
                'default_successor_dentist_id' => $successor->id,
                'last_working_date' => '2026-07-20',
                'access_ends_at' => '2026-07-20 17:00:00',
                'handover_notes' => 'Ready for handover.',
                'remarks' => 'No additional remarks.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('dentist_transitions', [
            'dentist_id' => $departingDentist->id,
            'transition_type' => 'retirement',
            'default_successor_dentist_id' => $successor->id,
        ]);
    }

    public function test_duplicate_active_transitions_are_prevented(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();

        DentistTransition::create([
            'dentist_id' => $departingDentist->id,
            'transition_type' => 'retirement',
            'default_successor_dentist_id' => $successor->id,
            'last_working_date' => '2026-07-20',
            'access_ends_at' => '2026-07-20 17:00:00',
            'status' => 'draft',
            'initiated_by' => $admin->id,
        ]);

        $response = $this->from(route('admin.dentist-transitions.create'))
            ->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.store'), [
                'dentist_id' => $departingDentist->id,
                'transition_type' => 'retirement',
                'default_successor_dentist_id' => $successor->id,
                'last_working_date' => '2026-07-21',
                'access_ends_at' => '2026-07-21 17:00:00',
            ]);

        $response->assertRedirect(route('admin.dentist-transitions.create'));
        $response->assertSessionHasErrors('dentist_id');
    }

    public function test_dentist_cannot_be_their_own_successor(): void
    {
        [$admin, $departingDentist] = $this->makeCoreUsers();

        $response = $this->from(route('admin.dentist-transitions.create'))
            ->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.store'), [
                'dentist_id' => $departingDentist->id,
                'transition_type' => 'retirement',
                'default_successor_dentist_id' => $departingDentist->id,
                'last_working_date' => '2026-07-20',
                'access_ends_at' => '2026-07-20 17:00:00',
            ]);

        $response->assertRedirect(route('admin.dentist-transitions.create'));
        $response->assertSessionHasErrors('default_successor_dentist_id');
    }

    public function test_inactive_dentist_cannot_be_selected_as_successor(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();
        $successor->update(['status' => 'inactive']);

        $response = $this->from(route('admin.dentist-transitions.create'))
            ->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.store'), [
                'dentist_id' => $departingDentist->id,
                'transition_type' => 'retirement',
                'default_successor_dentist_id' => $successor->id,
                'last_working_date' => '2026-07-20',
                'access_ends_at' => '2026-07-20 17:00:00',
            ]);

        $response->assertRedirect(route('admin.dentist-transitions.create'));
        $response->assertSessionHasErrors('default_successor_dentist_id');
    }

    public function test_finalize_fails_when_required_checklist_items_are_incomplete(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();
        $transition = $this->createTransitionWithAppointment($admin, $departingDentist, $successor);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.finalize', $transition));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('dentist_transitions', [
            'id' => $transition->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_future_appointments_are_reassigned_and_original_dentist_is_preserved(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();
        $transition = $this->createTransitionWithAppointment($admin, $departingDentist, $successor);

        DB::table('dentist_transition_checklist_items')
            ->where('dentist_transition_id', $transition->id)
            ->update([
                'is_completed' => true,
                'completed_by' => $admin->id,
                'completed_at' => now(),
            ]);

        $item = DB::table('dentist_transition_items')->where('dentist_transition_id', $transition->id)->first();

        $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->put(route('admin.dentist-transitions.assignments', $transition), [
                'default_successor_dentist_id' => $successor->id,
                'items' => [
                    $item->id => [
                        'successor_dentist_id' => $successor->id,
                        'transfer_status' => 'ready',
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.finalize', $transition))
            ->assertSessionHas('success');

        $appointment = Appointment::firstOrFail();

        $this->assertSame($successor->id, $appointment->dentist_id);
        $this->assertSame($departingDentist->id, $appointment->original_dentist_id);
        $this->assertSame('retirement', $appointment->transfer_reason);
    }

    public function test_deactivation_command_disables_expired_dentist_and_revokes_sessions(): void
    {
        [$admin, $departingDentist, $successor] = $this->makeCoreUsers();
        $transition = $this->createTransitionWithAppointment($admin, $departingDentist, $successor);

        DB::table('dentist_transition_checklist_items')
            ->where('dentist_transition_id', $transition->id)
            ->update([
                'is_completed' => true,
                'completed_by' => $admin->id,
                'completed_at' => now(),
            ]);

        \App\Models\DentistTransitionItem::query()
            ->where('dentist_transition_id', $transition->id)
            ->get()->each->update([
                'successor_dentist_id' => $successor->id,
                'transfer_status' => 'ready',
            ]);

        $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->post(route('admin.dentist-transitions.finalize', $transition));

        $transition->refresh();
        $transition->update([
            'status' => 'scheduled',
            'access_ends_at' => now()->subMinute(),
        ]);

        $departingDentist->update([
            'access_ends_at' => now()->subMinute(),
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $departingDentist->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('payload'),
            'last_activity' => now()->timestamp,
        ]);

        // Session revocation is enabled only for the database session driver.
        config(['session.driver' => 'database', 'concurrent-sessions.enabled' => true]);

        $this->artisan('dentists:deactivate-expired')
            ->expectsOutput('Processed 1 expired dentist transition(s).')
            ->assertSuccessful();

        $departingDentist->refresh();

        $this->assertSame('inactive', $departingDentist->status);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'test-session-id',
        ]);
    }

    private function createTransitionWithAppointment(User $admin, User $departingDentist, User $successor): DentistTransition
    {
        $patient = Patient::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'patient@example.com',
            'phone' => '09123456789',
            'birthdate' => '2000-01-01',
            'gender' => 'Male',
            'password' => bcrypt('password'),
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $departingDentist->id,
            'service_type' => 'Cleaning',
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        return app(DentistTransitionService::class)->createTransition([
            'dentist_id' => $departingDentist->id,
            'transition_type' => 'retirement',
            'default_successor_dentist_id' => $successor->id,
            'last_working_date' => now()->toDateString(),
            'access_ends_at' => now()->addDay()->toDateTimeString(),
            'handover_notes' => 'Prepared for transfer.',
        ], $admin);
    }

    private function makeCoreUsers(): array
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $dentistRole = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);

        foreach ([
            'manage_dentist_accounts',
            'view_dentist_transitions',
            'create_dentist_transitions',
            'update_dentist_transitions',
            'assign_dentist_successors',
            'finalize_dentist_transitions',
            'cancel_dentist_transitions',
            'extend_dentist_access',
        ] as $slug) {
            Permission::create([
                'name' => ucwords(str_replace('_', ' ', $slug)),
                'slug' => $slug,
                'module' => 'Dentist Continuity',
            ]);
        }

        $adminRole->permissions()->sync(Permission::pluck('id'));

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $departingDentist = User::create([
            'name' => 'Dr. Leaving',
            'email' => 'leaving@example.com',
            'password' => bcrypt('password'),
            'role_id' => $dentistRole->id,
            'status' => 'active',
        ]);

        $successor = User::create([
            'name' => 'Dr. Successor',
            'email' => 'successor@example.com',
            'password' => bcrypt('password'),
            'role_id' => $dentistRole->id,
            'status' => 'active',
        ]);

        return [$admin, $departingDentist, $successor];
    }

    private function adminSession(User $admin): array
    {
        return [
            'role' => 'admin',
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
        ];
    }
}
