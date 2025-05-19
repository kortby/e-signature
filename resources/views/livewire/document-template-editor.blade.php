<div class="flex h-screen bg-gray-100 p-8">
    <div class="w-72 bg-white p-4 shadow-lg space-y-4 overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-700">Add Template Fields</h3>
        <div id="template-field-palette" class="space-y-2">
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-field-type="text">Text Box</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-field-type="signature">Signature</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-field-type="date">Date Field</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-field-type="initials">Initials</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-field-type="checkbox">Checkbox</div>
        </div>
        <hr>
        <div class="mt-4">
             <h4 class="text-md font-semibold text-gray-700 mb-2">Template Info</h4>
             <p class="text-sm text-gray-600"><strong>Name:</strong> {{ $documentTemplate->name }}</p>
             <p class="text-sm text-gray-600"><strong>Description:</strong> {{ $documentTemplate->description ?: 'N/A' }}</p>
             <p class="text-sm text-gray-600"><strong>Pages:</strong> {{ $documentTemplate->pages->count() }}</p>
             <p class="text-sm text-gray-600"><strong>Total Fields:</strong> {{ $documentTemplate->templateFields->count() }}</p>
             <a href="{{ route('templates.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mt-2 inline-block">&larr; Back to Templates</a>
        </div>
    </div>

    <div class="flex-1 p-6 overflow-y-auto" x-data="templateEditor()">
        <h2 class="text-2xl font-semibold text-gray-800 mb-2">Editing Template: {{ $documentTemplate->name }}</h2>
        <p class="text-sm text-gray-500 mb-6">Drag fields from the left palette onto the document pages below. Click on a placed field to edit its properties or drag to reposition.</p>

        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-md">{{ session('message') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md">{{ session('error') }}</div>
        @endif

        <div class="space-y-8">
            @forelse ($pages as $templatePage)
                <div wire:key="template-page-container-{{ $templatePage->id }}"
                     class="document-template-page bg-white shadow-xl rounded-lg overflow-hidden relative border border-gray-300"
                     style="width: 100%; max-width: 800px; margin-left:auto; margin-right:auto; aspect-ratio: 8.5 / 11;">
                    <img src="{{ Storage::url($templatePage->image_path) }}"
                         alt="Template Page {{ $templatePage->page_number }}"
                         class="w-full h-full object-contain"
                         style="pointer-events: none;" draggable="false">

                    <div id="template-page-dropzone-{{ $templatePage->id }}"
                         class="absolute top-0 left-0 w-full h-full template-page-dropzone"
                         data-page-id="{{ $templatePage->id }}"
                         data-page-number="{{ $templatePage->page_number }}">

                        @foreach ($documentTemplate->templateFields->where('page_number', $templatePage->page_number) as $field)
                            <div wire:key="template-field-{{ $field->id }}"
                                 class="template-field absolute p-1 border border-purple-500 bg-purple-100 bg-opacity-60 hover:bg-opacity-80 cursor-move select-none"
                                 style="left: {{ $field->pos_x }}px; top: {{ $field->pos_y }}px; width: {{ $field->settings['width'] ?? '150px' }}; height: {{ $field->settings['height'] ?? '30px' }};"
                                 data-field-id="{{ $field->id }}"
                                 draggable="true"
                                 title="Key: {{ $field->key_name }} | Label: {{ $field->label }}"
                                 @click.stop="$wire.editField({{ $field->id }})">
                                <div class="w-full h-full flex items-center justify-center text-xs p-1 overflow-hidden">
                                   {{ $field->label ?: $field->key_name }} ({{ Str::limit($field->type, 6) }})
                                   {{-- ({{ $field->pos_x }}, {{ $field->pos_y }}) --}}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="absolute bottom-2 right-2 bg-gray-700 text-white text-xs px-2 py-1 rounded">
                        Page {{ $templatePage->page_number }}
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No pages found for this template.</p>
            @endforelse
        </div>
    </div>

    @if ($showFieldModal)
    <div class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 p-4" @keydown.escape.window="$wire.showFieldModal = false">
        <form wire:submit.prevent="saveTemplateField" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $editingFieldId ? 'Edit' : 'Add New' }} Template Field (Type: {{ Str::title($fieldType) }})</h3>
            <div>
                <label for="fieldKeyName" class="block text-sm font-medium text-gray-700">Key Name (e.g., tenant_name, signature_date)*</label>
                <input type="text" wire:model.defer="fieldKeyName" id="fieldKeyName" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only."
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                @error('fieldKeyName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 mt-1">Unique identifier for this field. Used for pre-filling data. Lowercase letters, numbers, and underscores only.</p>
            </div>
            <div>
                <label for="fieldLabel" class="block text-sm font-medium text-gray-700">Display Label (e.g., Tenant Full Name)*</label>
                <input type="text" wire:model.defer="fieldLabel" id="fieldLabel" required
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                @error('fieldLabel') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="fieldSettingsWidth" class="block text-sm font-medium text-gray-700">Width (e.g., 150px or 50%)</label>
                    <input type="text" wire:model.defer="fieldSettings.width" id="fieldSettingsWidth"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="fieldSettingsHeight" class="block text-sm font-medium text-gray-700">Height (e.g., 30px)</label>
                    <input type="text" wire:model.defer="fieldSettings.height" id="fieldSettingsHeight"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <p class="text-xs text-gray-500">Position: X={{ $fieldPosX }}, Y={{ $fieldPosY }} on Page {{ $fieldPageNumber }}</p>

            <div class="mt-6 flex justify-end space-x-3">
                @if($editingFieldId)
                <button type="button" wire:click="deleteField({{ $editingFieldId }})" onclick="return confirm('Are you sure you want to delete this field?')"
                        class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-transparent rounded-md hover:bg-red-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-red-500">
                    Delete Field
                </button>
                @endif
                <button type="button" wire:click="$set('showFieldModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ $editingFieldId ? 'Update' : 'Save' }} Field
                </button>
            </div>
        </form>
    </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('templateEditor', () => ({
        init() {
            // console.log('Alpine template editor initialized.');
            this.initPaletteDrag();
            this.initPageDropzones();
            this.initExistingFieldsDrag(); // Initialize existing fields

            const editorArea = this.$el;
            if (editorArea) {
                const observer = new MutationObserver((mutationsList) => {
                    for (const mutation of mutationsList) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1) {
                                    if (node.matches('.template-field')) {
                                        this.makeSingleFieldDraggable(node);
                                    }
                                    node.querySelectorAll('.template-field').forEach(el => this.makeSingleFieldDraggable(el));
                                }
                            });
                        }
                    }
                });
                observer.observe(editorArea, { childList: true, subtree: true });
            }
        },

        initPaletteDrag() {
            const paletteItems = document.querySelectorAll('#template-field-palette [draggable="true"]');
            paletteItems.forEach(item => {
                item.addEventListener('dragstart', (event) => {
                    event.dataTransfer.setData('fieldType', event.target.dataset.fieldType);
                    event.dataTransfer.setData('source', 'palette');
                    event.dataTransfer.effectAllowed = "copy";
                    // console.log('Drag Start Palette:', { type: event.target.dataset.fieldType });
                });
            });
        },

        initPageDropzones() {
            const dropzones = document.querySelectorAll('.template-page-dropzone');
            dropzones.forEach(zone => {
                zone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    const source = event.dataTransfer.types.includes('source') ? event.dataTransfer.getData('source') : 'unknown';
                     if (event.dataTransfer.getData('source') === 'palette') {
                        event.dataTransfer.dropEffect = 'copy';
                    } else {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                zone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    const pageId = event.currentTarget.dataset.pageId; // This is DocumentTemplatePage ID
                    // const pageNumber = event.currentTarget.dataset.pageNumber; // Actual page number
                    const source = event.dataTransfer.getData('source');
                    const rect = event.currentTarget.getBoundingClientRect();
                    let x = event.clientX - rect.left;
                    let y = event.clientY - rect.top;

                    if (source === 'palette') {
                        const fieldType = event.dataTransfer.getData('fieldType');
                        // console.log('Drop from Palette:', { pageId, fieldType, x, y });
                        if (fieldType) {
                            // Call Livewire to open modal with pre-filled data
                            this.$wire.prepareNewField(pageId, fieldType, x, y);
                        }
                    } else if (source === 'page-field') { // Changed source identifier
                        const fieldId = event.dataTransfer.getData('fieldId');
                        const offsetX = parseFloat(event.dataTransfer.getData('offsetX')) || 0;
                        const offsetY = parseFloat(event.dataTransfer.getData('offsetY')) || 0;
                        
                        let finalX = Math.round(x - offsetX);
                        let finalY = Math.round(y - offsetY);

                        // console.log('Drop Existing Field:', { fieldId, finalX, finalY });
                        if (fieldId) {
                           this.$wire.updateFieldPosition(fieldId, finalX, finalY);
                        }
                    }
                });
            });
        },
        
        initExistingFieldsDrag() {
            document.querySelectorAll('.template-field').forEach(el => this.makeSingleFieldDraggable(el));
        },

        makeSingleFieldDraggable(fieldEl) {
            if (fieldEl._alpineTemplateFieldDragStartListenerAttached) return;

            fieldEl.setAttribute('draggable', 'true');

            fieldEl.addEventListener('dragstart', (event) => {
                const targetField = event.target.closest('.template-field');
                event.dataTransfer.setData('fieldId', targetField.dataset.fieldId);
                event.dataTransfer.setData('source', 'page-field'); // Changed source identifier
                
                const rect = targetField.getBoundingClientRect();
                const offsetX = event.clientX - rect.left;
                const offsetY = event.clientY - rect.top;
                
                event.dataTransfer.setData('offsetX', offsetX.toString());
                event.dataTransfer.setData('offsetY', offsetY.toString());
                event.dataTransfer.effectAllowed = "move";
                // console.log('Drag Start Existing Field:', { fieldId: targetField.dataset.fieldId, offsetX, offsetY });
                event.stopPropagation();
            });
            fieldEl._alpineTemplateFieldDragStartListenerAttached = true;
        }
    }));
});
</script>