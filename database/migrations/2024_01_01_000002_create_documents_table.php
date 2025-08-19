<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference_number')->nullable()->index();
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('checksum', 64)->index();
            $table->integer('pages')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['quarantined', 'scanning', 'infected', 'received', 'in_progress', 'completed'])->default('quarantined')->index();
            $table->longText('ocr_text')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->datetime('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index(['assigned_to_department_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};