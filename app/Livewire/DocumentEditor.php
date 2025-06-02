<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\SignableInput;
use Illuminate\Support\Facades\Log; // Keep Log
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;

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
            // Could refresh partially if needed, or rely on wire:model.live for immediate feedback
            // For now, a full refresh is fine or let Livewire handle the specific model update.
            $this->document->refresh()->load('pages.signableInputs'); // Ensure view updates
             // session()->flash('message', 'Field updated.');
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
        // Pre-fill with existing signature if any
        $this->typedSignature = $input ? $input->value : '';
        $this->showSignatureModal = true;
        $this->resetErrorBag('typedSignature'); // Clear previous validation errors
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
            // Optionally, send notifications, etc.
            session()->flash('success', 'Document marked as completed!');
            $this->document->refresh(); // Refresh status for the view
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
