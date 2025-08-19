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
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->longText('body');
            $table->enum('visibility', ['public', 'department', 'internal'])->default('public');
            $table->integer('page_number')->nullable();
            $table->decimal('pos_x', 5, 4)->nullable(); // Normalized 0-1
            $table->decimal('pos_y', 5, 4)->nullable(); // Normalized 0-1
            $table->json('box_style')->nullable(); // Color, size, etc.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('forwarded_to_type')->nullable(); // 'user' or 'department'
            $table->unsignedBigInteger('forwarded_to_id')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
            $table->index(['created_by', 'created_at']);
            $table->index(['forwarded_to_type', 'forwarded_to_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minutes');
    }
};