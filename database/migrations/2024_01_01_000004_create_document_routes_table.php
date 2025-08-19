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
        Schema::create('document_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->string('to_type'); // 'user' or 'department'
            $table->unsignedBigInteger('to_id');
            $table->foreignId('minute_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->datetime('routed_at');
            $table->timestamps();

            $table->index(['document_id', 'routed_at']);
            $table->index(['to_type', 'to_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_routes');
    }
};