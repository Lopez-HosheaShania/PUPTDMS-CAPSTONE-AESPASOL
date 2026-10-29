<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentRequestPatientIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public static function identifiers(): array
    {
        return [
            'student' => [['student_no' => '2023-00128-TG-0'], '2023-00128-TG-0', '2023-00128-TG-0'],
            'faculty' => [['faculty_code' => 'FAC-001'], 'Faculty: FAC-001', 'FAC-001'],
            'no number' => [[], 'No identity number', 'Identifier Patient'],
        ];
    }

    #[DataProvider('identifiers')]
    public function test_lists_and_search_use_patient_identity_numbers_instead_of_database_ids(array $identity, string $expected, string $search): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = Permission::create(['name' => 'View Document Requests', 'slug' => 'view_document_requests']);
        $role->permissions()->attach($permission);
        $user = User::create(['name' => 'Admin', 'email' => 'identifier-admin@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => 'admin']);
        $patient = Patient::create(array_merge([
            'name' => 'Identifier Patient', 'email' => 'identifier@example.test', 'password' => bcrypt('password'),
        ], $identity));
        $record = DocumentRequest::create([
            'patient_id' => $patient->id, 'reference_number' => 'DOC-IDENTIFIER',
            'document_type' => 'dental_health_record', 'purpose' => 'Personal Record',
            'request_date' => today()->toDateString(), 'request_time' => '09:00:00', 'status' => 'pending',
        ]);

        foreach (['', $search] as $query) {
            $request = Request::create('/document-requests', 'GET', ['search' => $query]);
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $responses = [
                app(\App\Http\Controllers\Admin\DocumentRequestController::class)->index($request),
                app(\App\Http\Controllers\DocumentRequestController::class)->dentistData($request),
            ];
            foreach ($responses as $response) {
                $rows = $response->getData(true)['requests'];
                $this->assertCount(1, $rows);
                $this->assertSame($record->id, $rows[0]['id']);
                $this->assertSame($expected, $rows[0]['patient_identifier']);
                $this->assertSame($expected, $rows[0]['sub_label']);
            }
        }
    }
}
