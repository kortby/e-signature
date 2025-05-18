<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\DocumentUploadForm;
use App\Livewire\DocumentEditor;
use App\Models\Document; // Make sure to import your Document model

// Route for displaying the upload form
Route::get('/upload-document', DocumentUploadForm::class)->name('document.upload')->middleware('auth');

// Route for the document editor
// This uses route-model binding to automatically fetch the Document
Route::get('/documents/{document}/edit', DocumentEditor::class)->name('document.editor')->middleware('auth');

// A simple dashboard or document list page (example)
Route::get('/', function () {
    // You would typically fetch documents for the logged-in user
    $documents = Document::where('user_id', auth()->id())->latest()->get();
    return view('dashboard', ['documents' => $documents]);
})->middleware(['auth'])->name('dashboard');

Route::get('/php', function () {
    return phpinfo();
});
