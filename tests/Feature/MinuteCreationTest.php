<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Document;
use App\Models\Minute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinuteCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->user->assignRole('user');
        
        $this->document = Document::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to_user_id' => $this->user->id,
            'status' => Document::STATUS_RECEIVED,
        ]);
    }

    public function test_user_can_create_minute_with_overlay_coordinates()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$this->document->id}/minutes", [
                'body' => 'This is a test minute with overlay coordinates.',
                'visibility' => 'public',
                'page_number' => 1,
                'pos_x' => 0.5,
                'pos_y' => 0.3,
            ]);

        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('minutes', [
            'document_id' => $this->document->id,
            'body' => 'This is a test minute with overlay coordinates.',
            'created_by' => $this->user->id,
            'page_number' => 1,
            'pos_x' => 0.5,
            'pos_y' => 0.3,
        ]);
    }

    public function test_user_can_create_minute_with_forwarding()
    {
        $targetUser = User::factory()->create(['department_id' => $this->department->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$this->document->id}/minutes", [
                'body' => 'Please review this document.',
                'visibility' => 'public',
                'forwarded_to_type' => 'user',
                'forwarded_to_id' => $targetUser->id,
            ]);

        $response->assertJson(['success' => true]);
        
        // Check minute was created
        $this->assertDatabaseHas('minutes', [
            'document_id' => $this->document->id,
            'forwarded_to_type' => 'user',
            'forwarded_to_id' => $targetUser->id,
        ]);
        
        // Check document was reassigned
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'assigned_to_user_id' => $targetUser->id,
            'status' => Document::STATUS_IN_PROGRESS,
        ]);
        
        // Check route was created
        $this->assertDatabaseHas('document_routes', [
            'document_id' => $this->document->id,
            'from_user_id' => $this->user->id,
            'to_type' => 'user',
            'to_id' => $targetUser->id,
        ]);
    }

    public function test_minute_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$this->document->id}/minutes", []);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body', 'visibility']);
    }

    public function test_user_cannot_create_minute_for_inaccessible_document()
    {
        $otherUser = User::factory()->create();
        $otherDocument = Document::factory()->create([
            'created_by' => $otherUser->id,
            'assigned_to_user_id' => $otherUser->id,
        ]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$otherDocument->id}/minutes", [
                'body' => 'This should not be allowed.',
                'visibility' => 'public',
            ]);

        $response->assertStatus(403);
    }

    public function test_minute_with_forwarding_to_department()
    {
        $targetDepartment = Department::factory()->create();
        
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$this->document->id}/minutes", [
                'body' => 'Forward to procurement department.',
                'visibility' => 'public',
                'forwarded_to_type' => 'department',
                'forwarded_to_id' => $targetDepartment->id,
            ]);

        $response->assertJson(['success' => true]);
        
        // Check document was reassigned to department
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'assigned_to_user_id' => null,
            'assigned_to_department_id' => $targetDepartment->id,
            'status' => Document::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_minute_coordinates_are_normalized()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/documents/{$this->document->id}/minutes", [
                'body' => 'Test minute.',
                'visibility' => 'public',
                'page_number' => 1,
                'pos_x' => 1.5, // Invalid coordinate > 1
                'pos_y' => 0.5,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pos_x']);
    }
}