<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\PdfToImage\Pdf;
use Spatie\PdfToImage\Enums\OutputFormat; // Required for v2+ and v3+ API

class DocumentUploadForm extends Component
{
    use WithFileUploads;

    public $pdfFile;
    public $documentTitle;

    protected $rules = [
        'pdfFile' => 'required|file|mimes:pdf|max:20480', // Max 20MB
        'documentTitle' => 'nullable|string|max:255',
    ];

    public function updatedPdfFile()
    {
        $this->validateOnly('pdfFile');
    }

    public function save()
    {
        $this->validate();

        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to upload documents.');
            return redirect()->route('login');
        }

        $user = Auth::user();
        $originalFilename = $this->pdfFile->getClientOriginalName();
        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($originalFilename, PATHINFO_FILENAME));
        $timestamp = time();
        $uniqueFilename = $timestamp . '_' . $sanitizedFilename . '.' . $this->pdfFile->getClientOriginalExtension();

        $pdfPath = $this->pdfFile->storeAs('uploads/pdfs/' . $user->id, $uniqueFilename, 'public');

        if (!$pdfPath) {
            session()->flash('error', 'Failed to store PDF file.');
            Log::error('Failed to store PDF file for user: ' . $user->id);
            return;
        }

        $document = Document::create([
            'user_id' => $user->id,
            'title' => $this->documentTitle ?: $originalFilename,
            'original_filename' => $originalFilename,
            'storage_path' => $pdfPath,
            'status' => 'draft',
        ]);

        try {
            $pdfFullPath = Storage::disk('public')->path($pdfPath);
            $spatiePdf = new Pdf($pdfFullPath);

            // Get the total number of pages in the pdf (using v2+/v3+ method)
            $numberOfPages = $spatiePdf->pageCount();

            $pageImageDir = 'uploads/documents/' . $document->id . '/pages';
            Storage::disk('public')->makeDirectory($pageImageDir);

            for ($pageNumber = 1; $pageNumber <= $numberOfPages; $pageNumber++) {
                $pageImageName = 'page_' . $pageNumber . '.jpg';
                $pageImagePathRelative = $pageImageDir . '/' . $pageImageName;
                $pageImageFullPath = Storage::disk('public')->path($pageImagePathRelative);

                // Using the v2+/v3+ fluent API
                $spatiePdf
                    ->selectPage($pageNumber)             // Select the page
                    ->format(OutputFormat::Jpg)         // Set the output format
                    ->backgroundColor('white')          // Set background to white (for transparency issues)
                    // ->quality(90)                     // Optional: set output quality
                    // ->resolution(150)                 // Optional: set DPI
                    ->save($pageImageFullPath);           // Save the image (v2+/v3+ method)

                DocumentPage::create([
                    'document_id' => $document->id,
                    'page_number' => $pageNumber,
                    'image_path' => $pageImagePathRelative,
                ]);
            }

            session()->flash('success', 'Document uploaded and processed successfully!');
            return redirect()->route('document.editor', ['document' => $document->id]);

        } catch (\Throwable $e) { // Catching Throwable for broader error catching
            Storage::disk('public')->delete($pdfPath);
            Storage::disk('public')->deleteDirectory('uploads/documents/' . $document->id);
            if ($document && $document->exists) {
                $document->forceDelete();
            }
            Log::error('PDF processing failed: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            session()->flash('error', 'Failed to process PDF: ' . $e->getMessage() . '. Please ensure Ghostscript and Imagick are correctly installed and configured. Check Imagick policy.xml if "not allowed by the security policy" errors occur. Also, verify spatie/pdf-to-image package version and compatibility.');
        }

        $this->reset(['pdfFile', 'documentTitle']);
    }

    public function render()
    {
        return view('livewire.document-upload-form')->layout('layouts.app');
    }
}
