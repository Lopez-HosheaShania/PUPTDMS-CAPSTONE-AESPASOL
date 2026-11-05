<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\User;
use App\Support\OdontogramTreatmentDisplay;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class OdontogramTreatmentDisplayTest extends TestCase
{
    public function test_it_returns_no_items_when_no_actual_treatment_is_recorded(): void
    {
        $this->assertSame([], OdontogramTreatmentDisplay::items([]));
        $this->assertSame('No treatment recorded.', OdontogramTreatmentDisplay::summary([]));
    }

    public function test_it_removes_duplicate_3d_entries_when_the_same_surface_treatment_exists(): void
    {
        $items = OdontogramTreatmentDisplay::items([
            [
                'tooth' => 11,
                'threeD' => [
                    'code' => 'J',
                    'label' => 'Jacket Crown',
                    'colorHex' => '#2563eb',
                ],
                'surfaces' => [
                    'top' => [
                        'code' => 'J',
                        'label' => 'Jacket Crown',
                        'colorHex' => '#2563eb',
                    ],
                    'left' => [
                        'code' => 'F',
                        'label' => 'Filling',
                        'colorHex' => '#10b981',
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'surface' => 'Top',
                'code' => 'J',
                'label' => 'Jacket Crown',
                'colorHex' => '#2563eb',
            ],
            [
                'surface' => 'Left',
                'code' => 'F',
                'label' => 'Filling',
                'colorHex' => '#10b981',
            ],
        ], $items);
    }

    public function test_appointment_record_card_payload_does_not_use_completion_action_as_treatment_text(): void
    {
        $appointment = new Appointment([
            'id' => 42,
            'service_type' => 'Restoration',
            'appointment_date' => '2026-09-01',
            'appointment_time' => '09:00:00',
            'status' => 'completed',
        ]);

        $appointment->setRelation('dentist', new User([
            'name' => 'Dr. Cruz',
        ]));

        $appointment->setRelation('procedure', new AppointmentProcedure([
            'completion_action' => 'finished',
            'odontogram_data' => [],
            'oral_examination' => 'Normal',
            'diagnosis' => 'Observation only',
            'prescriptions' => 'None',
        ]));

        $html = Blade::render(
            '<x-appointment-record-card :appointment="$appointment" variant="past" :show-details="true" />',
            ['appointment' => $appointment]
        );

        $this->assertStringNotContainsString('"remarks":"finished"', strtolower($html));
        $this->assertStringNotContainsString('"remarks":"Finished"', $html);
        $this->assertStringContainsString('"treatment_items":[]', $html);
    }

    public function test_record_card_accepts_homepage_arrays_and_preserves_the_edit_metadata(): void
    {
        $deadline = now()->addDays(3)->toIso8601String();
        foreach (['completed', 'cancelled'] as $status) {
            $appointment = [
                'id' => 42, 'service' => 'Cleaning', 'date' => 'October 20, 2026',
                'time' => '09:00:00', 'status' => $status,
                'dentist_name' => 'Test Dentist', 'odontogram' => [],
                'edit_locked' => false, 'edit_deadline' => $status === 'completed' ? $deadline : null,
            ];
            $html = Blade::render(
                '<x-appointment-record-card :appointment="$appointment" variant="past" :show-details="true" />',
                ['appointment' => $appointment]
            );
            preg_match("/data-record='([^']+)'/", $html, $matches);
            $this->assertNotEmpty($matches[1] ?? null);
            $payload = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($status, $payload['status']);
            $this->assertFalse($payload['edit_locked']);
            $this->assertSame($appointment['edit_deadline'], $payload['edit_deadline']);
        }
    }

    public function test_completed_array_without_edit_metadata_defaults_to_read_only(): void
    {
        $html = Blade::render(
            '<x-appointment-record-card :appointment="$appointment" :show-details="true" />',
            ['appointment' => ['id' => 42, 'status' => 'completed', 'service' => 'Cleaning']]
        );
        $this->assertStringContainsString('"edit_locked":true', $html);
        $this->assertStringContainsString('"edit_deadline":null', $html);
    }
}
