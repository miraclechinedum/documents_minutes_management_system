<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->user->assignRole('user');
        
        Storage::fake('local');
    }

    public function test_user_can_upload_document()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');
        
        $response = $this->actingAs($this->user)->post('/documents', [
            'title' => 'Test Document',
            'description' => 'Test description',
            'file' => $file,
            'assigned_to_type' => 'user',
            'assigned_to_id' => $this->user->id,
            'priority' => 'medium',
        ]);

        $response->assertRedirect('/documents');
        
        $this->assertDatabaseHas('documents', [
            'title' => 'Test Document',
            'created_by' => $this->user->id,
            'status' => Document::STATUS_QUARANTINED,
        ]);
        
        Storage::disk('local')->assertExists('documents/' . basename(Document::first()->file_path));
    }

    public function test_document_upload_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post('/documents', []);
        
        $response->assertSessionHasErrors(['title', 'file', 'assigned_to_type', 'assigned_to_id', 'priority']);
    }

    public function test_document_upload_validates_file_type()
    {
        $file = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');
        
        $response = $this->actingAs($this->user)->post('/documents', [
            'title' => 'Test Document',
            'file' => $file,
            'assigned_to_type' => 'user',
            'assigned_to_id' => $this->user->id,
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_unauthorized_user_cannot_upload_document()
    {
        $userWithoutPermission = User::factory()->create();
        
        $response = $this->actingAs($userWithoutPermission)->post('/documents', [
            'title' => 'Test Document',
        ]);

        $response->assertStatus(403);
    }

    public function test_document_can_be_assigned_to_department()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');
        
        $response = $this->actingAs($this->user)->post('/documents', [
            'title' => 'Test Document',
            'file' => $file,
            'assigned_to_type' => 'department',
            'assigned_to_id' => $this->department->id,
            'priority' => 'high',
        ]);

        $response->assertRedirect('/documents');
        
        $this->assertDatabaseHas('documents', [
            'title' => 'Test Document',
            'assigned_to_department_id' => $this->department->id,
            'assigned_to_user_id' => null,
            'priority' => 'high',
        ]);
    }
}