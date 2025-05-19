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
        Schema::create('document_template_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained()->onDelete('cascade');
            $table->integer('page_number');
            $table->string('image_path'); // Path to the generated image of the template page
            $table->timestamps();

            $table->unique(['document_template_id', 'page_number'], 'template_page_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_pages');
    }
};
