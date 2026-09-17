<?php

namespace Tests\Feature;

use App\Models\DentistTransition;
use App\Models\DentistTransitionItem;
use App\Models\User;
use App\Models\UserDeactivation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeactivationAndTransitionItemDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create(['name' => 'Detail test user', 'email' => uniqid().'@example.test', 'password' => bcrypt('password'), 'status' => 'active']);
    }

    private function item(): DentistTransitionItem
    {
        $dentist = $this->user();
        $transition = DentistTransition::create([
            'dentist_id' => $dentist->id, 'initiated_by' => $dentist->id, 'transition_type' => 'retirement',
            'last_working_date' => '2026-09-20', 'access_ends_at' => '2026-09-20 17:00:00',
        ]);

        return $transition->items()->create(['item_type' => 'appointment', 'record_id' => 123, 'original_dentist_id' => $dentist->id]);
    }

    private function event(User $user): UserDeactivation
    {
        return $user->deactivationEvents()->create([
            'deactivated_by' => $user->id, 'deactivated_at' => '2026-09-20 17:00:00', 'reason' => 'Retirement history',
            'employment_status' => 'retired', 'last_working_date' => '2026-09-20',
            'account_status' => 'inactive', 'access_ends_at' => '2026-09-20 17:00:00',
        ]);
    }

    public function test_deactivation_snapshots_preserve_history_casts_and_sparse_null_details(): void
    {
        $user = $this->user();
        $event = $this->event($user);
        $this->assertCount(7, Schema::getColumnListing('user_deactivations'));
        $this->assertFalse(Schema::hasColumn('user_deactivations', 'employment_status'));
        $user->update(['employment_status' => 'active', 'account_status' => 'active']);
        $event = $event->fresh(['deactivatedBy']);
        $this->assertSame('retired', $event->employment_status);
        $this->assertSame('2026-09-20', $event->last_working_date->format('Y-m-d'));
        $this->assertSame('2026-09-20 17:00:00', $event->access_ends_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $event->deactivatedBy->id);
        $this->assertArrayNotHasKey('employment_snapshot', $event->toArray());
        $this->assertArrayNotHasKey('access_snapshot', $event->toArray());
        $empty = $user->deactivationEvents()->create(['reason' => 'No supplied snapshot']);
        $this->assertNull($empty->fresh()->employment_status);
        $this->assertDatabaseCount('user_deactivation_employments', 1);
        $event->update(['reason' => 'Corrected reason']);
        $this->assertSame('retired', $event->fresh()->employment_status);
        $event->update(['account_status' => null, 'access_ends_at' => null]);
        $this->assertNull($event->fresh()->access_ends_at);
        $event->delete();
        $this->assertDatabaseCount('user_deactivation_employments', 0);
        $this->assertDatabaseCount('user_deactivation_accesses', 0);
    }

    public function test_item_assignments_defaults_relationships_and_successor_deletion(): void
    {
        $item = $this->item();
        $this->assertCount(7, Schema::getColumnListing('dentist_transition_items'));
        $this->assertSame('pending', $item->fresh()->transfer_status);
        $this->assertFalse($item->fresh()->is_critical);
        $successor = $this->user();
        $item->update(['successor_dentist_id' => $successor->id, 'transfer_status' => 'ready', 'is_critical' => true]);
        $item = $item->fresh(['originalDentist', 'successorDentist']);
        $this->assertSame($successor->id, $item->successorDentist->id);
        $this->assertSame($item->transition->dentist_id, $item->originalDentist->id);
        $this->assertTrue($item->toArray()['is_critical']);
        $this->assertArrayNotHasKey('assignment', $item->toArray());
        $this->assertArrayNotHasKey('transfer_state', $item->toArray());
        $successor->delete();
        $this->assertNull($item->fresh()->successor_dentist_id);
        $this->assertSame('ready', $item->fresh()->transfer_status);
        $item->update(['remarks' => 'Preserved resolution', 'transferred_by' => $item->original_dentist_id]);
        $this->assertSame($item->original_dentist_id, $item->fresh(['transferredBy'])->transferredBy->id);
        $item->delete();
        foreach (['assignments', 'states', 'resolutions'] as $suffix) {
            $this->assertDatabaseCount('dentist_transition_item_'.$suffix, 0);
        }
    }

    public function test_failed_detail_writes_roll_back_parent_and_other_details(): void
    {
        $item = $this->item();
        $before = $item->fresh()->toArray();
        try {
            $item->update(['record_id' => 456, 'transfer_status' => 'ready', 'successor_dentist_id' => 999999]);
            $this->fail('Expected invalid successor foreign key to fail.');
        } catch (QueryException $exception) {
            $this->assertSame($before, $item->fresh()->toArray());
        }
        $event = $this->event($this->user());
        DB::statement("CREATE TRIGGER reject_deactivation_access BEFORE UPDATE ON user_deactivation_accesses BEGIN SELECT RAISE(ABORT, 'simulated access write failure'); END");
        try {
            $event->update(['reason' => 'Must roll back', 'employment_status' => 'resigned', 'account_status' => 'blocked']);
            $this->fail('Expected access snapshot write failure.');
        } catch (QueryException $exception) {
            $this->assertSame('Retirement history', $event->fresh()->reason);
            $this->assertSame('retired', $event->fresh()->employment_status);
            $this->assertSame('inactive', $event->fresh()->account_status);
        } finally {
            DB::statement('DROP TRIGGER reject_deactivation_access');
        }
    }

    public function test_populated_migration_round_trip_preserves_snapshots_and_resolution_links(): void
    {
        DB::commit();
        try {
            $item = $this->item();
            $item->update(['successor_dentist_id' => $this->user()->id, 'transfer_status' => 'transferred', 'is_critical' => true, 'remarks' => 'Historical transfer', 'transferred_at' => '2026-09-20 16:00:00']);
            $event = $this->event($this->user());
            $empty = $event->user->deactivationEvents()->create(['reason' => 'Unknown historical status']);
            $before = [$item->fresh()->toArray(), $event->fresh()->toArray(), $empty->fresh()->toArray()];
            $migration = require database_path('migrations/2026_09_14_000003_separate_deactivation_and_transition_item_details.php');
            $migration->down();
            $this->assertDatabaseHas('dentist_transition_items', ['id' => $item->id, 'transfer_status' => 'transferred', 'is_critical' => true]);
            $this->assertDatabaseHas('user_deactivations', ['id' => $event->id, 'employment_status' => 'retired']);
            $this->assertSame(1, DB::table('user_deactivations')->where('id', $event->id)->whereDate('last_working_date', '2026-09-20')->count());
            $migration->up();
            $this->assertSame($before, [$item->fresh()->toArray(), $event->fresh()->toArray(), $empty->fresh()->toArray()]);
            $this->assertSame('Historical transfer', $item->fresh()->remarks);
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            RefreshDatabaseState::$migrated = false;
            DB::beginTransaction();
        }
    }
}
