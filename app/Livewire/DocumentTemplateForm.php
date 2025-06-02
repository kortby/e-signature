<?php

namespace App\Livewire;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplatePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\PdfToImage\Pdf as SpatiePdf; // Alias to avoid conflict if Pdf model exists
use Spatie\PdfToImage\Enums\OutputFormat;


class DocumentTemplateForm extends Component
{
    use WithFileUploads;

    public $templateName;
    public $templateDescription;
    public $pdfFile;

    protected $rules = [
        'templateName' => 'required|string|max:255',
        'templateDescription' => 'nullable|string',
        'pdfFile' => 'required|file|mimes:pdf|max:20480', // Max 20MB
    ];

    public function saveTemplate()
    {
        $this->validate();

        $user = Auth::user();
        $originalFilename = $this->pdfFile->getClientOriginalName();
        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($originalFilename, PATHINFO_FILENAME));
        $timestamp = time();
        $uniqueFilename = $timestamp . '_' . $sanitizedFilename . '.' . $this->pdfFile->getClientOriginalExtension();

        // Store the original PDF for the template
        $pdfPath = $this->pdfFile->storeAs('uploads/templates/pdfs/' . $user->id, $uniqueFilename, 'public');

        if (!$pdfPath) {
            session()->flash('error', 'Failed to store template PDF file.');
            Log::error('Failed to store template PDF file for user: ' . $user->id);
            return;
        }

        // Create DocumentTemplate record
        $documentTemplate = DocumentTemplate::create([
            'user_id' => $user->id,
            'name' => $this->templateName,
            'description' => $this->templateDescription,
            'original_pdf_storage_path' => $pdfPath,
        ]);

        // Process PDF to images for template pages
        try {
            $pdfFullPath = Storage::disk('public')->path($pdfPath);
            $spatiePdf = new SpatiePdf($pdfFullPath);
            $numberOfPages = $spatiePdf->pageCount();

            $pageImageDir = 'uploads/templates/pages/' . $documentTemplate->id;
            Storage::disk('public')->makeDirectory($pageImageDir);

            for ($pageNumber = 1; $pageNumber <= $numberOfPages; $pageNumber++) {
                $pageImageName = 'page_' . $pageNumber . '.jpg';
                $pageImagePathRelative = $pageImageDir . '/' . $pageImageName;
                $pageImageFullPath = Storage::disk('public')->path($pageImagePathRelative);

                $spatiePdf
                    ->selectPage($pageNumber)
                    ->format(OutputFormat::Jpg)
                    ->backgroundColor('white')
                    ->save($pageImageFullPath);

                DocumentTemplatePage::create([
                    'document_template_id' => $documentTemplate->id,
                    'page_number' => $pageNumber,
                    'image_path' => $pageImagePathRelative,
                ]);
            }

            session()->flash('success', 'Template base created successfully! You can now add fields.');
            return redirect()->route('templates.edit', ['documentTemplate' => $documentTemplate->id]);
        } catch (\Throwable $e) {
            // Clean up if processing fails
            Storage::disk('public')->delete($pdfPath);
            if ($documentTemplate && $documentTemplate->exists) {
                Storage::disk('public')->deleteDirectory('uploads/templates/pages/' . $documentTemplate->id);
                $documentTemplate->forceDelete();
            }
            Log::error('Template PDF processing failed: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            session()->flash('error', 'Failed to process template PDF: ' . $e->getMessage());
        }

        $this->reset(['templateName', 'templateDescription', 'pdfFile']);
    }

    public function render()
    {
        // Create this file at: resources/views/livewire/document-template-form.blade.php
        return view('livewire.document-template-form')->layout('layouts.app');
    }
}
