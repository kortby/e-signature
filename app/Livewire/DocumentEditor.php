<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\SignableInput;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;

class DocumentEditor extends Component
{
    public Document $document;

    public $selectedInputType = null;
    public $newInputData = [];

    public function mount(Document $document)
    {
        $this->document = $document->load('pages.signableInputs');
    }

    public function addSignableInput($pageId, $type, $x, $y, $settings = [])
    {
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

    public function updateInputPosition($inputId, $x, $y)
    {
        $input = SignableInput::find($inputId);
        if ($input && $input->documentPage->document_id === $this->document->id) {
            Log::info("Attempting to update input {$inputId} to x:{$x}, y:{$y}");
            $input->update(['pos_x' => $x, 'pos_y' => $y]);
            $this->document->refresh()->load('pages.signableInputs'); // Refresh to reflect changes
            Log::info("Input {$inputId} updated. New DB pos_x: {$input->pos_x}, pos_y: {$input->pos_y}");
            // session()->flash('message', 'Input position updated.'); // Can be too noisy
        } else {
            Log::warning("Failed to update input {$inputId}: Not found or does not belong to document {$this->document->id}.");
        }
    }

    public function updateInputValue($inputId, $value)
    {
        $input = SignableInput::find($inputId);
        if ($input && $input->documentPage->document_id === $this->document->id) {
            $input->update(['value' => $value]);
        }
    }

    public function render()
    {
        return view('livewire.document-editor', [
            'pages' => $this->document->pages
        ])->layout('layouts.app');
    }
}
