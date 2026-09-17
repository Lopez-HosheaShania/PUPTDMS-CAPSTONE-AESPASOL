<?php

namespace Tests\Feature;

use App\Models\ReservedBookingPeriod;
use App\Models\ServiceType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservedBookingPeriodNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function period(array $overrides = []): ReservedBookingPeriod
    {
        return ReservedBookingPeriod::create(array_merge([
            'title' => 'Faculty clinic',
            'reserved_date' => '2026-10-05',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'booking_mode' => 'date_only',
            'target_patient_type' => 'faculty',
            'max_capacity' => 10,
            'is_active' => false,
        ], $overrides));
    }

    public function test_service_list_preserves_null_empty_order_and_public_payload(): void
    {
        $period = $this->period();
        $this->assertFalse(Schema::hasColumn('reserved_booking_periods', 'allowed_services'));
        $this->assertNull($period->fresh()->allowed_services);
        $this->assertTrue($period->allowsService('Any service'));

        $period->update(['allowed_services' => []]);
        $this->assertSame([], $period->fresh()->allowed_services);
        $this->assertFalse($period->allowsService('Any service'));

        $names = ['Oral Check-up', 'Cleaning'];
        $period->update(['allowed_services' => $names]);
        $this->assertSame($names, $period->fresh()->allowed_services);
        $this->assertTrue($period->allowsService('  oral CHECK-UP '));
        $this->assertFalse($period->allowsService('Extraction'));
        $payload = $period->fresh()->toArray();
        $this->assertSame($names, $payload['allowed_services']);
        $this->assertArrayNotHasKey('service_options', $payload);
        $this->assertArrayNotHasKey('restrict_services', $payload);

        $period->update(['title' => 'Renamed']);
        $this->assertSame($names, $period->fresh()->allowed_services);
        $period->update(['allowed_services' => ['Cleaning']]);
        $this->assertSame(['Cleaning'], $period->fresh()->allowed_services);
        $this->assertDatabaseCount('reserved_booking_period_services', 1);
        $period->update(['allowed_services' => null]);
        $this->assertNull($period->fresh()->allowed_services);
        $this->assertDatabaseCount('reserved_booking_period_services', 0);
    }

    public function test_soft_delete_restore_and_force_delete_preserve_expected_services(): void
    {
        $period = $this->period(['is_active' => true, 'allowed_services' => ['Cleaning']]);
        $period->delete();
        $this->assertDatabaseCount('reserved_booking_period_services', 1);
        $this->assertNull($period->fresh()->active_reserved_date);
        $period->restore();
        $this->assertSame(['Cleaning'], $period->fresh()->allowed_services);
        $this->assertSame('2026-10-05', $period->fresh()->active_reserved_date->format('Y-m-d'));
        $period->forceDelete();
        $this->assertDatabaseCount('reserved_booking_period_services', 0);
    }

    public function test_catalog_rename_and_delete_do_not_rewrite_existing_restrictions(): void
    {
        $service = ServiceType::create(['name' => 'Cleaning']);
        $period = $this->period(['allowed_services' => ['Cleaning']]);
        $service->update(['name' => 'Renamed cleaning']);
        $this->assertFalse($period->fresh()->allowsService('Renamed cleaning'));
        $service->delete();
        $this->assertSame(['Cleaning'], $period->fresh()->allowed_services);
    }

    public function test_refresh_discards_unsaved_service_changes(): void
    {
        $period = $this->period(['allowed_services' => ['Cleaning']]);
        $period->allowed_services = null;
        $this->assertNull($period->allowed_services);
        $period->refresh();
        $this->assertSame(['Cleaning'], $period->allowed_services);
    }

    public function test_failed_child_write_rolls_back_parent_and_original_services(): void
    {
        $period = $this->period(['allowed_services' => ['Cleaning']]);
        DB::statement("CREATE TRIGGER reject_reserved_service BEFORE INSERT ON reserved_booking_period_services BEGIN SELECT RAISE(ABORT, 'simulated write failure'); END");
        try {
            $period->update(['title' => 'Should roll back', 'allowed_services' => ['Extraction']]);
            $this->fail('Expected child write failure.');
        } catch (QueryException $exception) {
            $this->assertSame('Faculty clinic', $period->fresh()->title);
            $this->assertSame(['Cleaning'], $period->fresh()->allowed_services);
        } finally {
            DB::statement('DROP TRIGGER reject_reserved_service');
        }
    }

    public function test_backfill_and_rollback_preserve_all_periods_and_slot_links(): void
    {
        $periods = collect([
            $this->period(),
            $this->period(['allowed_services' => []]),
            $this->period(['allowed_services' => ['Retired service', 'Cleaning', 'Cleaning']]),
        ]);
        $periods->last()->delete();
        $slot = $periods->first()->slots()->create(['slot_time' => '09:00:00', 'max_capacity' => 1]);
        $before = $periods->map(fn ($period) => $period->fresh()->toArray())->all();
        $migration = require database_path('migrations/2026_09_14_000001_normalize_reserved_booking_period_services.php');
        $detailsMigration = require database_path('migrations/2026_09_14_000002_separate_reserved_booking_period_details.php');
        $detailsMigration->down();
        $migration->down();
        $this->assertNull(DB::table('reserved_booking_periods')->where('id', $periods[0]->id)->value('allowed_services'));
        $this->assertSame('[]', DB::table('reserved_booking_periods')->where('id', $periods[1]->id)->value('allowed_services'));
        $migration->up();
        $detailsMigration->up();
        $this->assertEquals($before, $periods->map(fn ($period) => $period->fresh()->toArray())->all());
        $this->assertSame($periods->first()->id, $slot->fresh()->reserved_booking_period_id);
        $this->assertDatabaseCount('reserved_booking_period_services', 3);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_invalid_legacy_json_is_rejected_before_schema_changes(): void
    {
        $period = $this->period();
        $migration = require database_path('migrations/2026_09_14_000001_normalize_reserved_booking_period_services.php');
        $detailsMigration = require database_path('migrations/2026_09_14_000002_separate_reserved_booking_period_details.php');
        $detailsMigration->down();
        $migration->down();
        DB::table('reserved_booking_periods')->where('id', $period->id)->update(['allowed_services' => '{"unexpected":"object"}']);
        try {
            $migration->up();
            $this->fail('Expected invalid legacy data to stop migration.');
        } catch (\RuntimeException $exception) {
            $this->assertTrue(Schema::hasColumn('reserved_booking_periods', 'allowed_services'));
            $this->assertFalse(Schema::hasTable('reserved_booking_period_services'));
            $this->assertSame('{"unexpected":"object"}', DB::table('reserved_booking_periods')->where('id', $period->id)->value('allowed_services'));
        }
    }

    public function test_empty_legacy_table_without_json_column_can_be_migrated(): void
    {
        $migration = require database_path('migrations/2026_09_14_000001_normalize_reserved_booking_period_services.php');
        $detailsMigration = require database_path('migrations/2026_09_14_000002_separate_reserved_booking_period_details.php');
        $detailsMigration->down();
        $migration->down();
        Schema::table('reserved_booking_periods', fn ($table) => $table->dropColumn('allowed_services'));
        $migration->up();
        $detailsMigration->up();
        $this->assertNull($this->period()->fresh()->allowed_services);
        $this->assertSame(['Cleaning'], $this->period(['allowed_services' => ['Cleaning']])->fresh()->allowed_services);
    }
}
