<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Document;
use App\Models\Department;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentPolicy $policy;
    protected User $admin;
    protected User $user;
    protected User $otherUser;
    protected Department $department;
    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new DocumentPolicy();
        $this->department = Department::factory()->create();
        
        // Create users
        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $this->admin->assignRole('admin');
        
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->user->assignRole('user');
        
        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('user');
        
        // Create document
        $this->document = Document::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to_user_id' => $this->user->id,
        ]);
    }

    public function test_admin_can_view_any_document()
    {
        $result = $this->policy->view($this->admin, $this->document);
        $this->assertTrue($result);
    }

    public function test_user_can_view_own_created_document()
    {
        $result = $this->policy->view($this->user, $this->document);
        $this->assertTrue($result);
    }

    public function test_user_can_view_assigned_document()
    {
        $result = $this->policy->view($this->user, $this->document);
        $this->assertTrue($result);
    }

    public function test_user_can_view_department_assigned_document()
    {
        $document = Document::factory()->create([
            'created_by' => $this->admin->id,
            'assigned_to_department_id' => $this->department->id,
        ]);
        
        $result = $this->policy->view($this->user, $document);
        $this->assertTrue($result);
    }

    public function test_user_cannot_view_unrelated_document()
    {
        $result = $this->policy->view($this->otherUser, $this->document);
        $this->assertFalse($result);
    }

    public function test_only_creator_or_admin_can_update_document()
    {
        // Creator can update
        $this->assertTrue($this->policy->update($this->user, $this->document));
        
        // Admin can update
        $this->assertTrue($this->policy->update($this->admin, $this->document));
        
        // Other user cannot update
        $this->assertFalse($this->policy->update($this->otherUser, $this->document));
    }

    public function test_only_creator_or_admin_can_delete_document()
    {
        // Creator can delete
        $this->assertTrue($this->policy->delete($this->user, $this->document));
        
        // Admin can delete
        $this->assertTrue($this->policy->delete($this->admin, $this->document));
        
        // Other user cannot delete
        $this->assertFalse($this->policy->delete($this->otherUser, $this->document));
    }

    public function test_user_can_forward_viewable_document()
    {
        $result = $this->policy->forward($this->user, $this->document);
        $this->assertTrue($result);
        
        $result = $this->policy->forward($this->otherUser, $this->document);
        $this->assertFalse($result);
    }

    public function test_user_can_export_viewable_document()
    {
        $result = $this->policy->export($this->user, $this->document);
        $this->assertTrue($result);
        
        $result = $this->policy->export($this->otherUser, $this->document);
        $this->assertFalse($result);
    }
}