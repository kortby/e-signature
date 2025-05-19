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
            $table->foreignId('document_template_id')->nullable()->constrained()->onDelete('set null')->after('status');
            $table->string('recipient_name')->nullable()->after('document_template_id'); // Example for prefill
            $table->string('recipient_email')->nullable()->after('recipient_name'); // For sending/tracking
            $table->json('prefilled_data')->nullable()->after('recipient_email');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner of the document
            $table->string('title')->nullable();
            $table->string('original_filename');
            $table->string('storage_path'); // Relative path in storage
            $table->string('status')->default('draft'); // e.g., draft, pending_signature, completed, voided
            $table->timestamps();
            $table->softDeletes(); // Optional: if you want soft delete functionality
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
