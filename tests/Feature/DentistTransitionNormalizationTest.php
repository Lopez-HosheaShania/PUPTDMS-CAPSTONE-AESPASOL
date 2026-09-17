<?php

namespace Tests\Feature;

use App\Models\DentistTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DentistTransitionNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function transition(): DentistTransition
    {
        $user = User::create(['name' => 'Transition Test', 'email' => uniqid().'@example.test', 'password' => bcrypt('password'), 'status' => 'active']);

        return DentistTransition::create([
            'dentist_id' => $user->id, 'initiated_by' => $user->id, 'transition_type' => 'retirement',
            'last_working_date' => '2026-09-10', 'access_ends_at' => '2026-09-10 17:00:00',
            'handover_notes' => 'Keep this note', 'reviewed_by' => $user->id,
        ]);
    }

    public function test_optional_details_updates_casts_and_existing_actor_relationships(): void
    {
        $transition = $this->transition();
        $this->assertFalse(Schema::hasColumn('dentist_transitions', 'handover_notes'));
        $this->assertDatabaseCount('dentist_transition_cancellations', 0);
        $transition->update(['remarks' => 'Reviewed', 'completed_at' => '2026-09-07 10:00:00']);
        $transition = $transition->fresh(['reviewedBy']);
        $this->assertSame('Keep this note', $transition->handover_notes);
        $this->assertSame($transition->initiated_by, $transition->reviewedBy->id);
        $this->assertSame('2026-09-07 10:00:00', $transition->completed_at->format('Y-m-d H:i:s'));
        $this->assertSame('Reviewed', $transition->toArray()['remarks']);
        $this->assertArrayNotHasKey('details', $transition->toArray());
        $transition->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Cancelled by request']);
        $this->assertSame('Cancelled by request', $transition->fresh()->cancellation_reason);
        $this->assertDatabaseCount('dentist_transition_cancellations', 1);
        $transition->update(['remarks' => null]);
        $this->assertNull($transition->fresh()->remarks);
    }

    public function test_resolution_preserves_original_dentist_and_rolls_back_failed_updates(): void
    {
        $transition = $this->transition();
        $item = $transition->items()->create(['item_type' => 'appointment', 'record_id' => 123,
            'original_dentist_id' => $transition->dentist_id, 'transfer_status' => 'pending']);
        $this->assertDatabaseCount('dentist_transition_item_resolutions', 0);
        $item->update(['resolution_type' => 'manual', 'remarks' => 'Retained',
            'transferred_by' => $transition->initiated_by, 'transferred_at' => now()]);
        $this->assertSame($transition->initiated_by, $item->fresh(['transferredBy'])->transferredBy->id);
        try {
            $item->update(['transfer_status' => 'transferred', 'transferred_by' => 9999999]);
            $this->fail('Expected a foreign key failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame('pending', $item->fresh()->transfer_status);
            $this->assertSame('Retained', $item->fresh()->remarks);
        }
        $transition->delete();
        $this->assertDatabaseCount('dentist_transition_item_resolutions', 0);
        $this->assertDatabaseCount('dentist_transition_details', 0);
    }

    public function test_migration_round_trip_preserves_details_items_and_checklist(): void
    {
        DB::commit();
        try {
            $transition = $this->transition();
            $transition->update(['cancelled_at' => now(), 'cancellation_reason' => 'Preserve cancellation']);
            $item = $transition->items()->create(['item_type' => 'appointment', 'record_id' => 1,
                'original_dentist_id' => $transition->dentist_id, 'remarks' => 'Item snapshot', 'transferred_by' => $transition->initiated_by]);
            $checklist = $transition->checklistItems()->create(['checklist_key' => 'test', 'label' => 'Preserve checklist']);
            $before = $transition->fresh()->toArray();
            $beforeItem = $item->fresh()->toArray();
            $migration = require database_path('migrations/2026_09_07_130000_separate_dentist_transition_details.php');
            $migration->down();
            $this->assertDatabaseHas('dentist_transitions', ['id' => $transition->id, 'handover_notes' => 'Keep this note']);
            $migration->up();
            $this->assertSame($before, $transition->fresh()->toArray());
            $this->assertSame($beforeItem, $item->fresh()->toArray());
            $this->assertNotNull($checklist->fresh());
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
            DB::beginTransaction();
        }
    }
}
