<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use App\Models\Minute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        $this->document = Document::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to_user_id' => $this->user->id,
            'status' => Document::STATUS_RECEIVED,
        ]);
        
        // Create some minutes
        Minute::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'created_by' => $this->user->id,
            'visibility' => 'public',
        ]);
    }

    public function test_user_can_export_document_with_appendix()
    {
        $response = $this->actingAs($this->user)
            ->get("/documents/{$this->document->id}/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_user_can_export_document_with_overlay_mode()
    {
        $response = $this->actingAs($this->user)
            ->get("/documents/{$this->document->id}/export?mode=overlay");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorized_user_cannot_export_document()
    {
        $otherUser = User::factory()->create();
        $otherDocument = Document::factory()->create([
            'created_by' => $otherUser->id,
            'assigned_to_user_id' => $otherUser->id,
        ]);
        
        $response = $this->actingAs($this->user)
            ->get("/documents/{$otherDocument->id}/export");

        $response->assertStatus(403);
    }

    public function test_export_logs_activity()
    {
        $this->actingAs($this->user)
            ->get("/documents/{$this->document->id}/export");

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Document::class,
            'subject_id' => $this->document->id,
            'description' => 'document.exported',
        ]);
    }

    public function test_user_without_export_permission_cannot_export()
    {
        $userWithoutPermission = User::factory()->create();
        // Don't assign any role - user won't have export permission
        
        $document = Document::factory()->create([
            'created_by' => $userWithoutPermission->id,
            'assigned_to_user_id' => $userWithoutPermission->id,
        ]);
        
        $response = $this->actingAs($userWithoutPermission)
            ->get("/documents/{$document->id}/export");

        $response->assertStatus(403);
    }
}