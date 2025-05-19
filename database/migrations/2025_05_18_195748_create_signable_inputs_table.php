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
        Schema::create('signable_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_field_id')->nullable()->constrained()->onDelete('set null');
            // The `label` field in signable_inputs can now be inherited from template_fields.key_name or template_fields.label
            // The `value` field will store the actual data entered or pre-filled.

            // $table->foreignId('assigned_signer_id')->nullable()->constrained('users')->onDelete('set null'); // Future: for assigning to specific signers
            $table->string('type'); // e.g., 'text', 'signature', 'date', 'initials', 'checkbox'
            $table->integer('pos_x'); // X coordinate on the page image
            $table->integer('pos_y'); // Y coordinate on the page image
            $table->text('value')->nullable(); // The actual data entered by the signer
            $table->json('settings')->nullable(); // For width, height, font, required status, placeholder, etc.
            $table->string('label')->nullable(); // A label for the input field
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signable_inputs');
    }
};
