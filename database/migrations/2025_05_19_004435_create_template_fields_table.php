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
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained()->onDelete('cascade');
            // $table->foreignId('document_template_page_id')->constrained()->onDelete('cascade'); // Link to the specific template page
            $table->unsignedInteger('page_number'); // Page number within the template
            $table->string('type'); // e.g., 'text', 'signature', 'date', 'initials', 'checkbox'
            $table->string('key_name')->comment('Unique key for this field within the template, e.g., tenant_full_name, lease_start_date'); // Critical for mapping
            $table->string('label')->nullable()->comment('User-friendly label shown in UI, e.g., Tenant Full Name');
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->json('settings')->nullable(); // width, height, font, required, etc.
            $table->text('default_value')->nullable();
            $table->boolean('is_prefillable')->default(true)->comment('Can this field be pre-filled with instance-specific data?');
            $table->boolean('is_readonly_after_prefill')->default(false)->comment('If pre-filled, can the signer change it?');
            // $table->string('assigned_to_role')->nullable()->comment('E.g., tenant, landlord. For multi-signer workflows.');
            $table->timestamps();

            $table->unique(['document_template_id', 'key_name'], 'template_field_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
