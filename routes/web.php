<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\DocumentUploadForm;
use App\Livewire\DocumentEditor;
use App\Models\Document; // Make sure to import your Document model
use App\Livewire\DocumentTemplateList;
use App\Livewire\DocumentTemplateForm;
use App\Livewire\DocumentTemplateEditor;
use App\Models\DocumentTemplate; // Import this
use App\Livewire\UseDocumentTemplateForm; // Add this use statement

Route::middleware(['auth'])->group(function () {
    Route::get('/templates', DocumentTemplateList::class)->name('templates.index');
    Route::get('/templates/create', DocumentTemplateForm::class)->name('templates.create');
    Route::get('/templates/{documentTemplate}/edit', DocumentTemplateEditor::class)->name('templates.edit');
    Route::get('/templates/use', UseDocumentTemplateForm::class)->name('templates.use');
    // Add other document routes if they are separate
    // Route::get('/upload-document', DocumentUploadForm::class)->name('document.upload');
    // Route::get('/documents/{document}/edit', DocumentEditor::class)->name('document.editor');
    // Route::get('/dashboard', function () {
    //     $documents = \App\Models\Document::where('user_id', auth()->id())->latest()->get();
    //     return view('dashboard', ['documents' => $documents]);
    // })->name('dashboard');
});


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

Route::get('/dashboard', function () {
    // You would typically fetch documents for the logged-in user
    $documents = Document::where('user_id', auth()->id())->latest()->get();
    return view('dashboard', ['documents' => $documents]);
})->middleware(['auth'])->name('dashboard');

Route::get('/php', function () {
    return phpinfo();
});
