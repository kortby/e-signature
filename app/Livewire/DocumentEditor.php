<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\SignableInput;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi; // For PDF generation with TCPDF
// If TCPDF_FONTS is not found, you might need to ensure TCPDF is correctly autoloaded
// or define the path to TCPDF's fonts directory if using custom fonts.
// For standard fonts like helvetica, it should generally work.
// use TCPDF_FONTS; // This might not be necessary if using standard fonts

class DocumentEditor extends Component
{
    public Document $document;

    public $selectedInputType = null;
    public $newInputData = [];

    // For Signature Modal
    public $showSignatureModal = false;
    public $signingInputId = null;
    public $typedSignature = '';

    protected function rules()
    {
        return [
            'typedSignature' => 'required|string|min:2|max:100',
            // Add other rules if needed for adding new inputs via modal
        ];
    }

    public function mount(Document $document)
    {
        $this->document = $document->load('pages.signableInputs');
    }

    // Adding a new input field (e.g., by an admin preparing the document)
    public function addSignableInput($pageId, $type, $x, $y, $settings = [])
    {
        if ($this->document->status === 'completed') {
            session()->flash('error', 'Document is completed and cannot be modified.');
            return;
        }
        $page = $this->document->pages()->find($pageId);
        if (!$page) {
            session()->flash('error', 'Page not found.');
            return;
        }

        SignableInput::create([
            'document_page_id' => $pageId,
            'type' => $type,
            'pos_x' => $x,
            'pos_y' => $y,
            'settings' => $settings ?: ['width' => '150px', 'height' => '30px'],
            'label' => ucfirst($type) . ' Field',
        ]);
        $this->document->refresh()->load('pages.signableInputs');
        session()->flash('message', 'Input field added.');
    }

    // Updating an existing input's position (e.g., by an admin)
    public function updateInputPosition($inputId, $x, $y)
    {
        if ($this->document->status === 'completed') return; // No changes if completed

        $input = SignableInput::find($inputId);
        if ($input && $input->documentPage->document_id === $this->document->id) {
            $input->update(['pos_x' => $x, 'pos_y' => $y]);
            $this->document->refresh()->load('pages.signableInputs');
        }
    }

    // Updating an input's value (e.g., text, date - by signer)
    public function updateInputValue($inputId, $value)
    {
        if ($this->document->status === 'completed') return;

        $input = SignableInput::find($inputId);
        if ($input && $input->documentPage->document_id === $this->document->id) {
            $input->update(['value' => $value]);
            $this->document->refresh()->load('pages.signableInputs');
        }
    }

    // Handling Checkbox Updates
    public function updateCheckboxValue($inputId, $isChecked)
    {
        if ($this->document->status === 'completed') return;

        $input = SignableInput::find($inputId);
        if ($input && $input->type === 'checkbox' && $input->documentPage->document_id === $this->document->id) {
            $input->update(['value' => $isChecked ? '1' : '0']); // Store as '1' or '0'
            $this->document->refresh()->load('pages.signableInputs');
        }
    }

    // Signature Modal Methods
    public function openSignatureModal($inputId)
    {
        if ($this->document->status === 'completed') return;

        $this->signingInputId = $inputId;
        $input = SignableInput::find($inputId);
        $this->typedSignature = $input ? $input->value : '';
        $this->showSignatureModal = true;
        $this->resetErrorBag('typedSignature');
    }

    public function saveSignature()
    {
        if ($this->document->status === 'completed') return;

        $this->validateOnly('typedSignature');

        $input = SignableInput::find($this->signingInputId);
        if ($input && $input->type === 'signature' && $input->documentPage->document_id === $this->document->id) {
            $input->update(['value' => $this->typedSignature]);
            $this->document->refresh()->load('pages.signableInputs');
            session()->flash('message', 'Signature applied.');
        } else {
            session()->flash('error', 'Could not apply signature.');
        }
        $this->closeSignatureModal();
    }

    public function closeSignatureModal()
    {
        $this->showSignatureModal = false;
        $this->signingInputId = null;
        $this->typedSignature = '';
    }

    // Mark Document as Completed
    public function markAsCompleted()
    {
        if ($this->document->status !== 'completed') {
            $this->document->update(['status' => 'completed']);
            session()->flash('success', 'Document marked as completed!');
            $this->document->refresh();
        }
    }

    // Download Completed Document
    public function downloadCompletedDocument()
    {
        if ($this->document->status !== 'completed') {
            session()->flash('error', 'Document must be completed before download.');
            return null;
        }

        $originalPdfPath = Storage::disk('public')->path($this->document->storage_path);
        if (!Storage::disk('public')->exists($this->document->storage_path)) {
            session()->flash('error', 'Original PDF not found.');
            Log::error("Original PDF not found for document ID {$this->document->id} at path {$this->document->storage_path}");
            return null;
        }

        $this->document->load('pages.signableInputs');

        try {
            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set the source file for FPDI *before* importing pages
            $pageCount = $pdf->setSourceFile($originalPdfPath); // This also returns page count

            for ($i = 1; $i <= $pageCount; $i++) {
                $documentPage = $this->document->pages->firstWhere('page_number', $i);
                // It's possible a document might not have entries for all pages if no inputs are on them
                // but we still want to import the original PDF page.

                $templateId = $pdf->importPage($i); // Import page by number
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Coordinate Transformation Constants (NEEDS ACCURATE VALUES)
                $imageDisplayWidthPx = 800; // Max width of image in editor (example)
                $scaleFactorX = $size['width'] / $imageDisplayWidthPx;
                $scaleFactorY = $scaleFactorX; // Simplification

                if ($documentPage) { // Process inputs only if we have a corresponding DocumentPage record
                    foreach ($documentPage->signableInputs as $input) {
                        if (empty($input->value) && $input->type !== 'checkbox') continue;

                        $pdfX = $input->pos_x * $scaleFactorX;
                        $pdfY = $input->pos_y * $scaleFactorY;
                        
                        $inputHeightSettings = $input->settings['height'] ?? '30px';
                        $inputHeightPx = (int) filter_var($inputHeightSettings, FILTER_SANITIZE_NUMBER_INT);
                        // Convert input height from pixels to points for PDF cell height
                        $inputCellHeightPoints = $inputHeightPx * $scaleFactorY; 
                        // Default font size in points
                        $fontSizePoints = 10; 


                        $pdf->SetFont('helvetica', '', $fontSizePoints);
                        $pdf->SetTextColor(0, 0, 0);

                        if ($input->type === 'text' || $input->type === 'date') {
                            $pdf->SetXY($pdfX, $pdfY);
                            // Use MultiCell for better text wrapping if needed, Cell for simple line
                            // Adjusting cell height to be slightly larger than font for padding
                            $pdf->Cell(0, $inputCellHeightPoints, $input->value, 0, 0, 'L');
                        } elseif ($input->type === 'signature') {
                            $pdf->SetFont('helvetica', 'I', 12); // Italic for signature
                            $pdf->SetXY($pdfX, $pdfY);
                            $pdf->Cell(0, $inputCellHeightPoints, $input->value, 0, 0, 'L');
                        } elseif ($input->type === 'checkbox') {
                            // Ensure checkbox size is reasonable in PDF points
                            $checkboxDrawSize = 4; // Size of checkbox in points (e.g., 4pt x 4pt box)
                            // Adjust Y position slightly for checkbox to align better if needed
                            $pdf->Rect($pdfX, $pdfY + ($inputCellHeightPoints - $checkboxDrawSize)/2 , $checkboxDrawSize, $checkboxDrawSize, 'D'); 
                            if ($input->value == '1') {
                                // Draw an 'X' or a checkmark
                                $pdf->SetFont('zapfdingbats', '', 10);
                                // The character for a checkmark in ZapfDingbats is typically 52.
                                // You might need to adjust position slightly to center it.
                                $pdf->Text($pdfX + $checkboxDrawSize * 0.1, $pdfY + ($inputCellHeightPoints - $checkboxDrawSize)/2 + $checkboxDrawSize * 0.1, \TCPDF_FONTS::unichr(52));
                            }
                        }
                    }
                }
            }

            $filename = Str::slug($this->document->title ?: 'completed-document') . '.pdf';
            // Output the PDF to the browser for download
            // Using 'D' will force download, 'I' will attempt to display inline
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->Output('S'); // 'S' returns the PDF as a string
            }, $filename, ['Content-Type' => 'application/pdf']);

        } catch (\Exception $e) {
            Log::error("PDF Generation Error for Document ID {$this->document->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            session()->flash('error', 'Could not generate PDF: ' . $e->getMessage());
            return null;
        }
    }


    public function render()
    {
        return view('livewire.document-editor', [
            'pages' => $this->document->pages,
            'isCompleted' => $this->document->status === 'completed',
        ])->layout('layouts.app');
    }
}
