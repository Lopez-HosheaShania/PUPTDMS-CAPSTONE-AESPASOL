<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureInventoryNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function dentist(string $slug = 'dentist', bool $permitted = true): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        if ($permitted) {
            foreach (['view_inventory', 'create_procedure_records', 'manage_appointments'] as $permissionSlug) {
                $permission = Permission::firstOrCreate(['slug' => $permissionSlug], ['name' => $permissionSlug]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }

        return User::create(['name' => 'Inventory Notes Dentist', 'email' => uniqid('notes-').'@example.test',
            'role_id' => $role->id, 'status' => 'active']);
    }

    private function appointment(User $dentist, string $date = '2026-10-23'): Appointment
    {
        $patient = Patient::create(['name' => 'Inventory Notes Patient', 'email' => uniqid('notes-patient-').'@example.test',
            'classification' => 'faculty', 'password' => bcrypt('password')]);
        $service = ServiceType::firstOrCreate(['name' => 'Oral Checkup']);

        return Appointment::create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id,
            'service_type_id' => $service->id, 'service_type' => $service->name,
            'appointment_date' => $date, 'appointment_time' => '09:00:00', 'status' => 'upcoming']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['odontogram_data' => [['tooth' => 11, 'status' => ['code' => 'F', 'label' => 'Filled'], 'surfaces' => []]], 'has_applied_treatment' => true,
            'procedure_started_at' => '2026-10-23T09:00:00', 'procedure_completed_at' => '2026-10-23T09:20:00', 'completion_action' => 'finished'], $overrides);
    }

    private function completed(User $dentist, string $note, string $date = '2026-10-23'): AppointmentProcedure
    {
        $appointment = $this->appointment($dentist, $date);
        $appointment->update(['status' => 'completed']);

        return AppointmentProcedure::create(['appointment_id' => $appointment->id, 'inventory_notes' => $note,
            'procedure_completed_at' => $date.' 09:20:00']);
    }

    public function test_notes_save_with_the_procedure_without_changing_stock_and_omitted_edits_preserve_them(): void
    {
        $dentist = $this->dentist();
        $appointment = $this->appointment($dentist, now()->toDateString());
        $stock = Inventory::create(['category' => 'Supplies', 'date_received' => now()->toDateString(),
            'stock_no' => '26-123', 'name' => 'Gauze', 'unit' => 'pcs', 'qty' => 100, 'used' => 5]);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $this->get(route('dentist.odontogram', $appointment))->assertOk()->assertSee('id="inventoryNotes"', false);
        $note = "2 pcs gauze\n1 cartridge lidocaine\n1 pair gloves";
        $this->postJson(route('dentist.odontogram.save', $appointment), $this->payload(['inventory_notes' => $note]))->assertOk();
        $procedure = $appointment->fresh()->procedure;
        $this->assertSame($note, $procedure->inventory_notes);
        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame(100, (int) $stock->fresh()->qty);
        $this->assertSame(5, (int) $stock->fresh()->used);
        $this->assertArrayNotHasKey('inventory_notes', $procedure->toArray());
        $reason = \App\Models\OdontogramRevisionReasonType::where('code', 'wrong_tooth')->value('id');
        $correction = $this->payload(['reason_type_id' => $reason,
            'odontogram_data' => [['tooth' => 12, 'status' => ['code' => 'F', 'label' => 'Filled'], 'surfaces' => []]]]);
        $this->postJson(route('dentist.odontogram.saved.update', $appointment), $correction)->assertOk();
        $this->assertSame($note, $procedure->fresh()->inventory_notes);
        $this->postJson(route('dentist.odontogram.saved.update', $appointment), $this->payload([
            'reason_type_id' => $reason, 'inventory_notes' => null,
        ]))->assertOk();
        $this->assertNull($procedure->fresh()->inventory_notes);
        $this->assertDatabaseCount('appointment_procedures', 1);
    }

    public function test_only_own_completed_nonempty_notes_are_returned_with_minimum_context(): void
    {
        $dentist = $this->dentist();
        $older = $this->completed($dentist, 'Older gauze note', '2026-09-01');
        $newer = $this->completed($dentist, "2 gauze\n1 lidocaine", '2026-10-22');
        $this->completed($dentist, '   ');
        $pending = $this->completed($dentist, 'Not completed');
        $pending->appointment->update(['status' => 'upcoming']);
        $this->completed($this->dentist(), 'Another dentist private note');
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);

        $this->get(route('dentist.dentist.inventory'))->assertOk()->assertSee('Procedure Inventory Notes')
            ->assertSee('data-open-modal="procedureInventoryNotesModal"', false);
        $response = $this->getJson(route('dentist.inventory.procedure-notes'))->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('data.0.procedure_id', $newer->id)
            ->assertJsonPath('data.1.procedure_id', $older->id)
            ->assertJsonPath('data.0.inventory_notes', "2 gauze\n1 lidocaine")
            ->assertJsonPath('data.0.procedure_name', 'Oral Checkup')
            ->assertJsonPath('data.0.patient_id', $newer->appointment->patient_id)
            ->assertDontSee('Another dentist private note')->assertDontSee('Not completed');
        $this->assertSame(['procedure_id', 'appointment_id', 'patient_id', 'patient_name', 'procedure_name',
            'procedure_date', 'visit_date', 'dentist_name', 'inventory_notes'], array_keys($response->json('data.0')));
    }

    public function test_all_notes_are_listed_newest_first_without_search_or_pagination(): void
    {
        $dentist = $this->dentist();
        for ($day = 1; $day <= 13; $day++) {
            $this->completed($dentist, 'Materials '.$day, '2026-10-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT));
        }
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $this->getJson(route('dentist.inventory.procedure-notes'))->assertOk()->assertJsonCount(13, 'data')
            ->assertJsonPath('data.0.inventory_notes', 'Materials 13')
            ->assertJsonPath('data.12.inventory_notes', 'Materials 1')->assertJsonPath('total', 13);
        $this->get(route('dentist.dentist.inventory'))->assertOk()
            ->assertDontSee('id="procedureInventoryNotesSearch"', false)
            ->assertDontSee('id="procedureInventoryNotesPagination"', false);
    }

    public function test_empty_notes_validation_and_authorization(): void
    {
        $this->get(route('dentist.inventory.procedure-notes'))->assertRedirect('/login');
        $dentist = $this->dentist();
        $appointment = $this->appointment($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $this->getJson(route('dentist.inventory.procedure-notes'))->assertOk()->assertJsonPath('total', 0);
        $this->postJson(route('dentist.odontogram.save', $appointment), $this->payload(['inventory_notes' => str_repeat('a', 10001)]))
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_notes');
        $this->assertDatabaseCount('appointment_procedures', 0);

        $other = $this->dentist();
        // The same patient is clinically visible, but another dentist cannot author this visit.
        $otherVisit = $this->appointment($other);
        $otherVisit->update(['patient_id' => $appointment->patient_id, 'appointment_time' => '11:00:00']);
        $this->actingAs($other)->postJson(route('dentist.odontogram.save', $appointment), $this->payload(['inventory_notes' => 'Wrong author']))->assertForbidden();
        $this->actingAs($this->dentist('admin'))->withSession(['role' => 'admin'])
            ->getJson(route('dentist.inventory.procedure-notes'))->assertForbidden();
        $dentist->role->permissions()->detach();
        $this->actingAs($dentist->fresh())->withSession(['role' => 'dentist'])
            ->getJson(route('dentist.inventory.procedure-notes'))->assertForbidden();
    }
}
