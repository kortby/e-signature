<?php


namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentTemplate;
use App\Models\SignableInput;
use App\Models\User; // Import User model
use App\Models\TemplateField;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Collection; // For type hinting

class UseDocumentTemplateForm extends Component
{
    public $selectedTemplateId;
    public ?DocumentTemplate $selectedTemplate = null;
    public $prefillData = [];
    public $documentTitle;

    public $recipientName;
    public $recipientEmail;
    public $selectedUserId; // For selecting a user to prefill recipient info

    public Collection $availableUsers; // To store list of users for dropdown

    // Property to pass templateId from route parameter
    public $initialTemplateId = null;

    protected function rules()
    {
        $rules = [
            'selectedTemplateId' => 'required|exists:document_templates,id',
            'documentTitle' => 'required|string|max:255',
            'selectedUserId' => 'nullable|exists:users,id', // User selection is optional
            'recipientName' => 'nullable|string|max:255',
            'recipientEmail' => 'nullable|email|max:255',
        ];

        if ($this->selectedTemplate) {
            foreach ($this->selectedTemplate->templateFields()->where('is_prefillable', true)->get() as $field) {
                $rules['prefillData.' . $field->key_name] = 'nullable|string|max:1000';
            }
        }
        return $rules;
    }

    protected $validationAttributes = [
        'selectedTemplateId' => 'template',
        'documentTitle' => 'document title',
        'selectedUserId' => 'recipient user',
        'prefillData.*' => 'field value',
    ];

    public function mount($templateId = null) // Accept templateId from route
    {
        $this->availableUsers = User::orderBy('name')->get();
        $this->prefillData = [];

        if ($templateId) {
            $this->initialTemplateId = $templateId;
            $this->selectedTemplateId = $templateId; // Pre-select template if ID is passed
            $this->updatedSelectedTemplateId($templateId); // Trigger logic to load template details
        }
    }

    public function updatedSelectedTemplateId($value)
    {
        if ($value) {
            $this->selectedTemplate = DocumentTemplate::with('templateFields.documentTemplate')->find($value); // Eager load for efficiency
            $this->prefillData = [];
            if ($this->selectedTemplate) {
                $this->documentTitle = $this->selectedTemplate->name . " - " . ($this->recipientName ?: "Instance");
                $this->applyDataSourceMappings(); // Apply mappings if a user is already selected
            }
        } else {
            $this->selectedTemplate = null;
            $this->prefillData = [];
            $this->documentTitle = '';
        }
        $this->resetValidation();
    }

    public function updatedSelectedUserId($userId)
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->recipientName = $user->name;
                $this->recipientEmail = $user->email;
                if ($this->selectedTemplate) {
                    $this->documentTitle = $this->selectedTemplate->name . " - " . $user->name;
                }
                $this->applyDataSourceMappings(); // Apply mappings for the newly selected user
            }
        } else {
            // Clear recipient fields if user is deselected
            $this->recipientName = '';
            $this->recipientEmail = '';
            if($this->selectedTemplate){
                 $this->documentTitle = $this->selectedTemplate->name . " - Instance";
            }
            $this->applyDataSourceMappings(); // Re-apply to clear user-specific data or revert to defaults
        }
        $this->resetValidation();
    }

    protected function applyDataSourceMappings()
    {
        if (!$this->selectedTemplate) {
            return;
        }

        $this->prefillData = []; // Start fresh for prefillData
        $selectedUser = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        foreach ($this->selectedTemplate->templateFields()->where('is_prefillable', true)->get() as $field) {
            $valueToSet = $field->default_value ?? ''; // Start with default

            if ($field->data_source_mapping && $selectedUser) {
                // Handle user data mapping
                if ($field->data_source_mapping === 'user.name') {
                    $valueToSet = $selectedUser->name;
                } elseif ($field->data_source_mapping === 'user.email') {
                    $valueToSet = $selectedUser->email;
                }
                // Add more 'user.attribute' mappings here if needed
                // e.g., elseif ($field->data_source_mapping === 'user.phone') { $valueToSet = $selectedUser->phone; }
            }
            // Future: Handle other data sources like 'property.address'
            // elseif (Str::startsWith($field->data_source_mapping, 'property.')) { ... }

            $this->prefillData[$field->key_name] = $valueToSet;
        }

        // Ensure dedicated recipient fields are also updated if mapped
        if ($selectedUser) {
            $this->recipientName = $selectedUser->name;
            $this->recipientEmail = $selectedUser->email;
        } elseif (!$this->selectedUserId) { // If no user is selected, clear these dedicated fields
            $this->recipientName = '';
            $this->recipientEmail = '';
        }
    }


    public function createDocumentFromTemplate()
    {
        $this->validate();

        if (!$this->selectedTemplate) {
            session()->flash('error', 'No template selected.');
            return;
        }

        $creatingUser = Auth::user(); // The user creating this document instance

        // Consolidate all prefill data.
        // Data in $this->prefillData (populated by applyDataSourceMappings or manual input) takes precedence.
        $finalPrefillData = $this->prefillData;

        // Ensure recipientName and recipientEmail are part of the prefilled_data if they are set
        // and correspond to known keys or if you want them generally available.
        // For example, if you have template fields with key_name 'recipient_name' or 'tenant_name'.
        if (!empty($this->recipientName)) {
            // If a template field exists for recipient_name, its value in $finalPrefillData would already be set by applyDataSourceMappings.
            // This is more for storing the general recipient info if not explicitly mapped via a field.
            $finalPrefillData['recipient_name_overall'] = $this->recipientName;
        }
        if (!empty($this->recipientEmail)) {
            $finalPrefillData['recipient_email_overall'] = $this->recipientEmail;
        }


        // 1. Create the Document record
        $document = Document::create([
            'user_id' => $creatingUser->id,
            'document_template_id' => $this->selectedTemplate->id,
            'title' => $this->documentTitle,
            'original_filename' => basename($this->selectedTemplate->original_pdf_storage_path),
            'storage_path' => $this->selectedTemplate->original_pdf_storage_path,
            'status' => 'draft',
            'recipient_name' => $this->recipientName, // Storing main recipient for quick access
            'recipient_email' => $this->recipientEmail, // Storing main recipient for quick access
            'prefilled_data' => $finalPrefillData, // Store all consolidated prefill data
        ]);

        // 2. Create DocumentPage records
        foreach ($this->selectedTemplate->pages as $templatePage) {
            DocumentPage::create([
                'document_id' => $document->id,
                'page_number' => $templatePage->page_number,
                'image_path' => $templatePage->image_path,
            ]);
        }
        $document->load('pages');


        // 3. Create SignableInput records from TemplateFields
        foreach ($this->selectedTemplate->templateFields as $templateField) {
            $documentPageInstance = $document->pages->firstWhere('page_number', $templateField->page_number);

            if (!$documentPageInstance) {
                Log::warning("Could not find DocumentPage instance for document {$document->id} and page number {$templateField->page_number}. Skipping SignableInput for TemplateField {$templateField->id}.");
                continue;
            }

            // Determine the value for the SignableInput
            $inputValue = $templateField->default_value ?? null; // Start with template field's default
            if ($templateField->is_prefillable && isset($finalPrefillData[$templateField->key_name])) {
                $inputValue = $finalPrefillData[$templateField->key_name]; // Override with value from prefillData array
            }

            SignableInput::create([
                'document_page_id' => $documentPageInstance->id,
                'template_field_id' => $templateField->id,
                'type' => $templateField->type,
                'pos_x' => $templateField->pos_x,
                'pos_y' => $templateField->pos_y,
                'settings' => $templateField->settings,
                'label' => $templateField->label ?: $templateField->key_name,
                'value' => $inputValue,
            ]);
        }

        session()->flash('success', 'Document created successfully from template!');
        return redirect()->route('document.editor', ['document' => $document->id]);
    }


    public function render()
    {
        $availableTemplates = DocumentTemplate::where('user_id', Auth::id()) // Or all templates if globally available
            ->orderBy('name')
            ->get();
        
        if (!isset($this->availableUsers)) {
            $this->availableUsers = User::orderBy('name')->get();
        }


        return view('livewire.use-document-template-form', [
            'availableTemplates' => $availableTemplates,
            'availableUsers' => $this->availableUsers,
        ])->layout('layouts.app');
    }
}
