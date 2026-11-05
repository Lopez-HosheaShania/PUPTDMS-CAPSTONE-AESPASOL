<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DefaultRolePermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDocumentRequestPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Http::fake();
        $this->withoutVite();

        return User::create([
            'name' => 'Admin', 'email' => 'admin-documents@example.test',
            'password' => bcrypt('password'), 'status' => 'active',
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
    }

    private function optionalDocumentPermissionIds(): array
    {
        return Permission::whereIn('slug', ['approve_document_requests', 'reject_document_requests', 'request_documents'])
            ->pluck('id')->all();
    }

    public function test_admin_form_shows_optional_document_permissions_but_defaults_only_grant_view(): void
    {
        $admin = $this->administrator();
        $response = $this->actingAs($admin)->withSession(['role' => 'admin'])
            ->get(route('admin.role_permissions'))->assertOk();

        preg_match('/<form id="form-role-' . $admin->role_id . '".*?<\/form>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('data-perm-slug="view_document_requests"', $matches[0]);
        foreach (['approve_document_requests', 'reject_document_requests', 'request_documents'] as $slug) {
            $this->assertStringContainsString('data-perm-slug="' . $slug . '"', $matches[0]);
            $this->assertNotContains($slug, DefaultRolePermissions::defaultsForRole('admin'));
        }
        $this->assertFalse($admin->role->permissions()->whereIn('permissions.id', $this->optionalDocumentPermissionIds())->exists());
        $response->assertSee('data-perm-slug="approve_document_requests"', false);
        $response->assertSee('data-perm-slug="reject_document_requests"', false);
    }

    public function test_admin_can_enable_document_permissions_and_reset_turns_them_off_again(): void
    {
        $admin = $this->administrator();
        $ids = $admin->role->permissions()->pluck('permissions.id')->merge($this->optionalDocumentPermissionIds())->all();
        $this->actingAs($admin)->withSession(['role' => 'admin'])
            ->postJson(route('admin.role_permissions.update'), ['role_id' => $admin->role_id, 'permissions' => $ids])
            ->assertOk();
        foreach (['approve_document_requests', 'reject_document_requests', 'request_documents'] as $slug) {
            $this->assertTrue($admin->hasPermission($slug));
        }
        $this->postJson(route('admin.role_permissions.reset'))->assertOk();
        $this->assertFalse($admin->role->permissions()->whereIn('permissions.id', $this->optionalDocumentPermissionIds())->exists());
        $this->assertTrue($admin->hasPermission('view_document_requests'));
    }

    public function test_default_admin_cannot_approve_or_reject_even_when_impersonating_but_manual_grants_work(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->withSession(['role' => 'admin']);
        $this->assertTrue($admin->hasPermission('view_document_requests'));
        foreach (['approve', 'reject'] as $action) {
            $this->assertFalse($admin->hasPermission($action . '_document_requests'));
            $this->patchJson(route('admin.document-requests.' . $action, 999))->assertForbidden();
        }
        $this->withSession(['impersonated_role' => 'dentist', 'impersonator_role' => 'admin']);
        foreach (['approve', 'reject'] as $action) {
            $this->postJson(route('dentist.dentist.documentrequests.' . $action, 999))->assertForbidden();
        }
        $admin->role->permissions()->syncWithoutDetaching($this->optionalDocumentPermissionIds());
        foreach (['approve', 'reject'] as $action) {
            $this->assertTrue($admin->hasPermission($action . '_document_requests'));
        }
    }

    public function test_reseeding_preserves_manually_granted_admin_permissions_and_dentist_defaults(): void
    {
        $admin = $this->administrator();
        $admin->role->permissions()->syncWithoutDetaching($this->optionalDocumentPermissionIds());
        $this->seed(RolePermissionSeeder::class);
        $this->assertSame(count($this->optionalDocumentPermissionIds()), $admin->role->permissions()
            ->whereIn('permissions.id', $this->optionalDocumentPermissionIds())->count());
        $dentist = Role::where('slug', 'dentist')->firstOrFail();
        foreach (['view_document_requests', 'approve_document_requests', 'reject_document_requests'] as $slug) {
            $this->assertTrue($dentist->permissions()->where('slug', $slug)->exists());
        }
    }
}

