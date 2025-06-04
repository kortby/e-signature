<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-white p-4 shadow-lg space-y-4 overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-700">Document Actions</h3>
        <hr class="my-2">
        <div class="mt-2">
             <h4 class="text-md font-semibold text-gray-700 mb-2">Document Info</h4>
             <p class="text-sm text-gray-600"><strong>Title:</strong> {{ $document->title }}</p>
             <p class="text-sm text-gray-600">
                <strong>Status:</strong> <span class="font-bold {{ $isCompleted ? 'text-green-600' : 'text-yellow-600' }}">{{ Str::title($document->status) }}</span>
             </p>
             <p class="text-sm text-gray-600"><strong>Pages:</strong> {{ $document->pages->count() }}</p>
        </div>
        <hr class="my-2">

        @if(!$isCompleted)
        <div class="mt-4">
            <button wire:click="markAsCompleted"
                    onclick="return confirm('Are you sure you want to mark this document as completed? Fields may become read-only.')"
                    class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                Mark as Completed
            </button>
        </div>
        @else
        <div class="mt-4 p-3 bg-green-100 text-green-700 rounded-md text-sm">
            This document is completed.
        </div>
        <div class="mt-4">
            <button wire:click="downloadCompletedDocument"
                    wire:loading.attr="disabled"
                    class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                <span wire:loading wire:target="downloadCompletedDocument">Generating...</span>
                <span wire:loading.remove wire:target="downloadCompletedDocument">Download Signed PDF</span>
            </button>
        </div>
        @endif
        
        {{-- Palette only shown if document is not completed and user is perhaps an admin/owner --}}
        {{-- This logic might need to be more sophisticated based on user roles --}}
        @if(!$isCompleted && auth()->id() == $document->user_id) 
        <hr class="my-4">
        <h3 class="text-lg font-semibold text-gray-700">Add Fields</h3>
        <p class="text-xs text-gray-500">(For document setup)</p>
        <div id="input-palette" class="space-y-2">
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-input-type="text">Text Box</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-input-type="signature">Signature</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-input-type="date">Date</div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab" draggable="true" data-input-type="checkbox">Checkbox</div>
        </div>
        @endif
    </div>

    <div class="flex-1 p-6 overflow-y-auto" x-data="documentEditor()">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ $document->title ?: 'Document Editor' }}</h2>

        @if (session()->has('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-md">{{ session('success') }}</div>
        @endif
        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-blue-100 text-blue-700 rounded-md">{{ session('message') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md">{{ session('error') }}</div>
        @endif

        <div class="space-y-8">
            @forelse ($pages as $page)
                <div wire:key="page-container-{{ $page->id }}"
                     class="document-page bg-white shadow-xl rounded-lg overflow-hidden relative border border-gray-300"
                     style="width: 100%; max-width: 800px; margin-left:auto; margin-right:auto; aspect-ratio: 8.5 / 11;">
                    <img src="{{ Storage::url($page->image_path) }}"
                         alt="Page {{ $page->page_number }}"
                         class="w-full h-full object-contain"
                         style="pointer-events: none;" draggable="false">

                    <div id="page-dropzone-{{ $page->id }}"
                         class="absolute top-0 left-0 w-full h-full page-dropzone"
                         data-page-id="{{ $page->id }}">

                        @foreach ($page->signableInputs as $input)
                            <div wire:key="input-{{ $input->id }}"
                                 class="signable-input absolute p-1 border
                                        {{ $input->type === 'signature' ? 'border-green-500 hover:bg-green-50' : ($input->type === 'checkbox' ? 'border-gray-400' : 'border-blue-500') }}
                                        bg-opacity-50 hover:bg-opacity-75
                                        {{ $isCompleted ? 'cursor-default' : ($input->type === 'signature' ? 'cursor-pointer' : 'cursor-move') }}
                                        select-none"
                                 style="left: {{ $input->pos_x }}px; top: {{ $input->pos_y }}px; width: {{ $input->settings['width'] ?? '150px' }}; height: {{ $input->settings['height'] ?? '30px' }};"
                                 data-input-id="{{ $input->id }}"
                                 draggable="{{ !$isCompleted && auth()->id() == $document->user_id ? 'true' : 'false' }}" {{-- Disable drag if completed or not owner --}}
                                 @if($input->type === 'signature' && !$isCompleted)
                                     wire:click.stop="openSignatureModal({{ $input->id }})"
                                 @endif
                                 >

                                @if ($input->type === 'text')
                                    <input type="text"
                                           placeholder="{{ $input->label ?? 'Text' }}"
                                           value="{{ $input->value }}"
                                           @if($isCompleted) readonly @else wire:change="updateInputValue({{ $input->id }}, $event.target.value)" @endif
                                           class="w-full h-full text-sm border-none bg-transparent focus:ring-0 p-1 {{ $isCompleted ? 'bg-gray-50 cursor-default' : '' }}">
                                @elseif ($input->type === 'signature')
                                    <div class="w-full h-full flex items-center justify-center text-sm italic p-1 {{ $input->value ? 'text-black font-semibold font-serif' : 'text-gray-500' }} {{ $isCompleted && !$input->value ? 'bg-gray-50' : '' }}">
                                        {{ $input->value ?: ($isCompleted ? '[Not Signed]' : '[Click to Sign]') }}
                                    </div>
                                @elseif ($input->type === 'date')
                                     <input type="date"
                                           value="{{ $input->value }}"
                                           @if($isCompleted) readonly @else wire:change="updateInputValue({{ $input->id }}, $event.target.value)" @endif
                                           class="w-full h-full text-sm border-none bg-transparent focus:ring-0 p-1 {{ $isCompleted ? 'bg-gray-50 cursor-default' : '' }}">
                                @elseif ($input->type === 'checkbox')
                                    <div class="flex items-center justify-center h-full">
                                        <input type="checkbox"
                                               id="checkbox-{{ $input->id }}"
                                               @if($isCompleted) disabled @else wire:change="updateCheckboxValue({{ $input->id }}, $event.target.checked)" @endif
                                               {{ $input->value == '1' ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 {{ $isCompleted ? 'cursor-default opacity-70' : 'cursor-pointer' }}">
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-sm p-1 {{ $isCompleted ? 'text-gray-700' : 'text-gray-500' }}">
                                        {{ $input->label ?? Str::title($input->type) }}
                                        @if($input->value) : {{ Str::limit($input->value, 15) }} @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="absolute bottom-2 right-2 bg-gray-700 text-white text-xs px-2 py-1 rounded">
                        Page {{ $page->page_number }}
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No pages found for this document.</p>
            @endforelse
        </div>
    </div>

    {{-- Signature Modal --}}
    @if ($showSignatureModal)
    <div class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 p-4" @keydown.escape.window="closeSignatureModal">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md space-y-4">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Apply Signature</h3>
            <p class="text-sm text-gray-600">Please type your full name as your signature. This will be legally binding.</p>
            <div>
                <label for="typedSignature" class="sr-only">Type your name</label>
                <input type="text" wire:model.defer="typedSignature" id="typedSignature" placeholder="Type your full name"
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                @error('typedSignature') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button type="button" wire:click="saveSignature"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                    Apply Signature
                </button>
                <button type="button" wire:click="closeSignatureModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('documentEditor', () => ({
        isDocumentCompleted: @json($isCompleted), // Get initial completed state
        isDocumentOwner: @json(auth()->id() == $document->user_id), // Check if current user is owner

        init() {
            // console.log('Alpine document editor initialized. Completed:', this.isDocumentCompleted, 'Owner:', this.isDocumentOwner);
            if (!this.isDocumentCompleted && this.isDocumentOwner) { // Only init palette drag if not completed AND owner
                this.initPaletteDrag();
            }
            if(!this.isDocumentCompleted) { // Init dropzones and input drag if not completed (for anyone to move their own fields if allowed, or admin to adjust)
                this.initPageDropzones();
                this.initExistingInputsDrag();
            }


            const editorArea = this.$el.querySelector('.flex-1.p-6.overflow-y-auto');
            if (editorArea) {
                const observer = new MutationObserver((mutationsList) => {
                    if (this.isDocumentCompleted) return;
                    for (const mutation of mutationsList) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1) {
                                    if (node.matches('.signable-input')) {
                                        this.makeSingleInputDraggable(node);
                                    }
                                    node.querySelectorAll('.signable-input').forEach(el => this.makeSingleInputDraggable(el));
                                }
                            });
                        }
                    }
                });
                observer.observe(editorArea, { childList: true, subtree: true });
            }
            
            this.$watch('isDocumentCompleted', (value) => {
                 // console.log('Document completed state changed in Alpine:', value);
                 // Re-evaluate draggable status of existing items if completion status changes
                 if(value){ // If completed, ensure no new listeners are added
                    document.querySelectorAll('.signable-input').forEach(el => {
                        el.setAttribute('draggable', 'false');
                        // Potentially remove listeners if absolutely needed, but draggable=false should suffice
                    });
                 } else {
                    // If somehow it becomes not completed again, re-init dragging
                    this.initExistingInputsDrag();
                 }
            });
        },

        initPaletteDrag() {
            const paletteItems = document.querySelectorAll('#input-palette [draggable="true"]');
            paletteItems.forEach(item => {
                item.addEventListener('dragstart', (event) => {
                    event.dataTransfer.setData('inputType', event.target.dataset.inputType);
                    event.dataTransfer.setData('source', 'palette');
                    event.dataTransfer.effectAllowed = "copy";
                });
            });
        },

        initPageDropzones() {
            const dropzones = document.querySelectorAll('.page-dropzone');
            dropzones.forEach(zone => {
                zone.addEventListener('dragover', (event) => {
                    if (this.isDocumentCompleted && !this.isDocumentOwner) return; // Allow owner to drag even if completed (for template setup)
                    event.preventDefault();
                    const source = event.dataTransfer.types.includes('source') ? event.dataTransfer.getData('source') : 'unknown';
                    if (event.dataTransfer.getData('source') === 'palette') {
                        event.dataTransfer.dropEffect = 'copy';
                    } else {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                zone.addEventListener('drop', (event) => {
                    if (this.isDocumentCompleted && !this.isDocumentOwner) return;
                    event.preventDefault();
                    const pageId = event.currentTarget.dataset.pageId;
                    const source = event.dataTransfer.getData('source');
                    const rect = event.currentTarget.getBoundingClientRect();
                    let x = event.clientX - rect.left;
                    let y = event.clientY - rect.top;

                    if (source === 'palette') {
                        if(this.isDocumentOwner) { // Only owner can add new fields
                            const inputType = event.dataTransfer.getData('inputType');
                            if (inputType) {
                                this.$wire.addSignableInput(pageId, inputType, Math.round(x), Math.round(y), {width: '150px', height: '30px'});
                            }
                        }
                    } else if (source === 'page') {
                        // Allow moving existing fields even if not owner, as long as not completed
                         if (this.isDocumentCompleted) return;

                        const inputId = event.dataTransfer.getData('inputId');
                        const offsetX = parseFloat(event.dataTransfer.getData('offsetX')) || 0;
                        const offsetY = parseFloat(event.dataTransfer.getData('offsetY')) || 0;
                        let finalX = Math.round(x - offsetX);
                        let finalY = Math.round(y - offsetY);
                        if (inputId) {
                           this.$wire.updateInputPosition(inputId, finalX, finalY);
                        }
                    }
                });
            });
        },

        initExistingInputsDrag() {
            document.querySelectorAll('.signable-input').forEach(el => this.makeSingleInputDraggable(el));
        },
        
        makeSingleInputDraggable(inputEl) {
            const isDraggable = inputEl.getAttribute('draggable') === 'true';
            if (inputEl._alpineDragStartListenerAttached || !isDraggable ) return;

            inputEl.addEventListener('dragstart', (event) => {
                if (this.isDocumentCompleted && !this.isDocumentOwner) { event.preventDefault(); return; }
                // Only allow drag if not completed OR if completed AND current user is owner (for template setup phase)
                // The draggable attribute in blade should already handle this, but double check here.
                if (this.isDocumentCompleted && !this.isDocumentOwner && inputEl.getAttribute('draggable') === 'false') {
                     event.preventDefault(); return;
                }

                const targetInput = event.target.closest('.signable-input');
                event.dataTransfer.setData('inputId', targetInput.dataset.inputId);
                event.dataTransfer.setData('source', 'page');
                const rect = targetInput.getBoundingClientRect();
                const offsetX = event.clientX - rect.left;
                const offsetY = event.clientY - rect.top;
                event.dataTransfer.setData('offsetX', offsetX.toString());
                event.dataTransfer.setData('offsetY', offsetY.toString());
                event.dataTransfer.effectAllowed = "move";
                event.stopPropagation();
            });
            inputEl._alpineDragStartListenerAttached = true;
        }
    }));
});
</script>