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
        Schema::create('document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('page_number');
            $table->string('image_path'); // Path to the generated image of the page
            $table->text('original_content_path')->nullable(); // Optional: if you store extracted text or original page data
            $table->timestamps();

            $table->unique(['document_id', 'page_number']); // Ensure page numbers are unique per document
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_pages');
    }
};
