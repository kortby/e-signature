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
            $table->unsignedInteger('page_number');
            $table->string('type');
            $table->string('key_name')->comment('Unique key for this field within the template');
            $table->string('label')->nullable()->comment('User-friendly label');
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->json('settings')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_prefillable')->default(true);
            $table->boolean('is_readonly_after_prefill')->default(false);
            $table->string('data_source_mapping')->nullable()->comment('e.g., user.name, user.email, property.address. Null for manual.'); // New field
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
