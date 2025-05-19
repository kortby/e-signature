<?php

namespace App\Livewire;

use App\Models\DocumentTemplate;
use App\Models\TemplateField;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;

class DocumentTemplateEditor extends Component
{
    public DocumentTemplate $documentTemplate;

    // For the modal/form to add/edit a template field
    public $showFieldModal = false;
    public $editingFieldId = null;
    public $fieldKeyName;
    public $fieldLabel;
    public $fieldType; // This will be set by the dragged item
    public $fieldPageNumber;
    public $fieldPosX;
    public $fieldPosY;
    public $fieldSettings = ['width' => '150px', 'height' => '30px']; // Default settings

    protected function rules()
    {
        return [
            'fieldKeyName' => 'required|string|max:100|regex:/^[a-z0-9_]+$/', // Snake case, alphanumeric
            'fieldLabel' => 'required|string|max:255',
            'fieldType' => 'required|string',
            'fieldPageNumber' => 'required|integer',
            'fieldPosX' => 'required|integer',
            'fieldPosY' => 'required|integer',
            'fieldSettings.width' => 'nullable|string',
            'fieldSettings.height' => 'nullable|string',
        ];
    }

    protected $validationAttributes = [
        'fieldKeyName' => 'Key Name (e.g., tenant_name)',
        'fieldLabel' => 'Label (e.g., Tenant Name)',
    ];

    public function mount(DocumentTemplate $documentTemplate)
    {
        $this->documentTemplate = $documentTemplate->load('pages', 'templateFields');
    }

    // Called by Alpine when a new field is dropped from palette
    public function prepareNewField($pageId, $type, $x, $y)
    {
        $page = $this->documentTemplate->pages()->find($pageId);
        if (!$page) {
            session()->flash('error', 'Template page not found.');
            return;
        }
        $this->resetFieldForm();
        $this->editingFieldId = null;
        $this->fieldType = $type;
        $this->fieldPageNumber = $page->page_number;
        $this->fieldPosX = round($x);
        $this->fieldPosY = round($y);
        $this->fieldLabel = Str::title(str_replace('_', ' ', $type)) . ' Field'; // Default label
        $this->fieldKeyName = Str::snake($type . '_' . uniqid()); // Default unique key name
        $this->showFieldModal = true;
    }

    // Called by Alpine when an existing field is clicked/dragged to edit
    public function editField($fieldId, $newX = null, $newY = null)
    {
        $field = TemplateField::find($fieldId);
        if (!$field || $field->document_template_id !== $this->documentTemplate->id) {
            session()->flash('error', 'Template field not found.');
            return;
        }
        $this->resetFieldForm();
        $this->editingFieldId = $field->id;
        $this->fieldKeyName = $field->key_name;
        $this->fieldLabel = $field->label;
        $this->fieldType = $field->type;
        $this->fieldPageNumber = $field->page_number;
        $this->fieldSettings = $field->settings ?? ['width' => '150px', 'height' => '30px'];

        if (!is_null($newX) && !is_null($newY)) { // Position updated by drag
            $this->fieldPosX = round($newX);
            $this->fieldPosY = round($newY);
            $this->saveTemplateField(); // Directly save if only position changed
        } else { // Open modal for other edits
            $this->fieldPosX = $field->pos_x;
            $this->fieldPosY = $field->pos_y;
            $this->showFieldModal = true;
        }
    }

    public function updateFieldPosition($fieldId, $x, $y)
    {
        $field = TemplateField::find($fieldId);
        if ($field && $field->document_template_id === $this->documentTemplate->id) {
            $field->update([
                'pos_x' => round($x),
                'pos_y' => round($y),
            ]);
            $this->documentTemplate->refresh()->load('pages', 'templateFields');
            // session()->flash('message', 'Field position updated.');
        }
    }


    public function saveTemplateField()
    {
        $this->validate();

        $data = [
            'document_template_id' => $this->documentTemplate->id,
            'page_number' => $this->fieldPageNumber,
            'type' => $this->fieldType,
            'key_name' => $this->fieldKeyName,
            'label' => $this->fieldLabel,
            'pos_x' => $this->fieldPosX,
            'pos_y' => $this->fieldPosY,
            'settings' => $this->fieldSettings,
        ];

        if ($this->editingFieldId) {
            $field = TemplateField::find($this->editingFieldId);
            if ($field) {
                $field->update($data);
                session()->flash('message', 'Template field updated successfully.');
            }
        } else {
            TemplateField::create($data);
            session()->flash('message', 'Template field added successfully.');
        }

        $this->showFieldModal = false;
        $this->documentTemplate->refresh()->load('pages', 'templateFields'); // Eager load relations again
        $this->resetFieldForm();
    }

    public function deleteField($fieldId)
    {
        $field = TemplateField::where('id', $fieldId)
            ->where('document_template_id', $this->documentTemplate->id)
            ->first();
        if ($field) {
            $field->delete();
            $this->documentTemplate->refresh()->load('pages', 'templateFields');
            session()->flash('message', 'Template field deleted.');
        } else {
            session()->flash('error', 'Could not delete field.');
        }
        $this->showFieldModal = false; // Close modal if open
    }


    private function resetFieldForm()
    {
        $this->reset(['editingFieldId', 'fieldKeyName', 'fieldLabel', 'fieldType', 'fieldPageNumber', 'fieldPosX', 'fieldPosY']);
        $this->fieldSettings = ['width' => '150px', 'height' => '30px'];
    }

    public function render()
    {
        // Create this file at: resources/views/livewire/document-template-editor.blade.php
        return view('livewire.document-template-editor', [
            'pages' => $this->documentTemplate->pages // Pass template pages
        ])->layout('layouts.app');
    }
}
