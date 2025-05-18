<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-white p-4 shadow-lg space-y-4 overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-700">Add Fields</h3>
        <div id="input-palette" class="space-y-2">
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab"
                 draggable="true"
                 data-input-type="text">
                 Text Box
            </div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab"
                 draggable="true"
                 data-input-type="signature">
                 Signature
            </div>
            <div class="p-2 border rounded-md bg-gray-50 hover:bg-gray-200 cursor-grab"
                 draggable="true"
                 data-input-type="date">
                 Date
            </div>
            </div>
        <hr>
        <div class="mt-4">
             <h4 class="text-md font-semibold text-gray-700 mb-2">Document Info</h4>
             <p class="text-sm text-gray-600"><strong>Title:</strong> {{ $document->title }}</p>
             <p class="text-sm text-gray-600"><strong>Status:</strong> {{ Str::title($document->status) }}</p>
             <p class="text-sm text-gray-600"><strong>Pages:</strong> {{ $document->pages->count() }}</p>
        </div>
    </div>

    <div class="flex-1 p-6 overflow-y-auto" x-data="documentEditor()">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ $document->title ?: 'Document Editor' }}</h2>

        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-md">{{ session('message') }}</div>
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
                                 class="signable-input absolute p-1 border border-blue-500 bg-blue-100 bg-opacity-50 hover:bg-opacity-75 cursor-move select-none"
                                 style="left: {{ $input->pos_x }}px; top: {{ $input->pos_y }}px; width: {{ $input->settings['width'] ?? '150px' }}; height: {{ $input->settings['height'] ?? '30px' }};"
                                 data-input-id="{{ $input->id }}"
                                 draggable="true">

                                 @if ($input->type === 'text')
                                    <input type="text"
                                           placeholder="{{ $input->label ?? 'Text' }}"
                                           value="{{ $input->value }}"
                                           wire:change="updateInputValue({{ $input->id }}, $event.target.value)"
                                           class="w-full h-full text-sm border-none bg-transparent focus:ring-0 p-1">
                                @elseif ($input->type === 'signature')
                                    <div class="w-full h-full flex items-center justify-center text-sm text-gray-500 italic p-1">
                                        {{ $input->value ? 'Signed: ' . Str::limit($input->value, 10) : ($input->label ?? 'Signature') }}
                                        {{-- For debugging: ({{ $input->pos_x }}, {{ $input->pos_y }}) --}}
                                    </div>
                                @elseif ($input->type === 'date')
                                     <input type="date"
                                           value="{{ $input->value }}"
                                           wire:change="updateInputValue({{ $input->id }}, $event.target.value)"
                                           class="w-full h-full text-sm border-none bg-transparent focus:ring-0 p-1">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-sm p-1">
                                        {{ $input->label ?? Str::title($input->type) }}
                                        @if($input->value) : {{ Str::limit($input->value, 15) }} @endif
                                        {{-- For debugging: ({{ $input->pos_x }}, {{ $input->pos_y }}) --}}
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
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('documentEditor', () => ({
        init() {
            // console.log('Alpine document editor initialized.');
            this.initPaletteDrag();
            this.initPageDropzones();
            this.initExistingInputsDrag(); // Initialize existing inputs

            // Observer for elements added by Livewire
            const editorArea = this.$el; // The element with x-data
            if (editorArea) {
                const observer = new MutationObserver((mutationsList) => {
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
        },

        initPaletteDrag() {
            const paletteItems = document.querySelectorAll('#input-palette [draggable="true"]');
            paletteItems.forEach(item => {
                item.addEventListener('dragstart', (event) => {
                    event.dataTransfer.setData('inputType', event.target.dataset.inputType);
                    event.dataTransfer.setData('source', 'palette');
                    event.dataTransfer.effectAllowed = "copy";
                    // console.log('Drag Start Palette:', { type: event.target.dataset.inputType });
                });
            });
        },

        initPageDropzones() {
            const dropzones = document.querySelectorAll('.page-dropzone');
            dropzones.forEach(zone => {
                zone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    // Set drop effect based on the source
                    const source = event.dataTransfer.types.includes('source') ? event.dataTransfer.getData('source') : 'unknown';
                    if (event.dataTransfer.getData('source') === 'palette') {
                        event.dataTransfer.dropEffect = 'copy';
                    } else {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                zone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    const pageId = event.currentTarget.dataset.pageId;
                    const source = event.dataTransfer.getData('source');
                    const rect = event.currentTarget.getBoundingClientRect();
                    let x = event.clientX - rect.left;
                    let y = event.clientY - rect.top;

                    if (source === 'palette') {
                        const inputType = event.dataTransfer.getData('inputType');
                        // console.log('Drop from Palette:', { pageId, inputType, x, y });
                        if (inputType) {
                            this.$wire.addSignableInput(pageId, inputType, Math.round(x), Math.round(y), {width: '150px', height: '30px'});
                        }
                    } else if (source === 'page') {
                        const inputId = event.dataTransfer.getData('inputId');
                        const offsetX = parseFloat(event.dataTransfer.getData('offsetX')) || 0;
                        const offsetY = parseFloat(event.dataTransfer.getData('offsetY')) || 0;
                        
                        let finalX = Math.round(x - offsetX);
                        let finalY = Math.round(y - offsetY);

                        // console.log('Drop from Page:', { inputId, finalX, finalY, rawX: x, rawY: y, offsetX, offsetY });
                        if (inputId) {
                           this.$wire.updateInputPosition(inputId, finalX, finalY);
                        }
                    }
                });
            });
        },

        initExistingInputsDrag() {
            // Call this on initial load for elements already in the DOM
            document.querySelectorAll('.signable-input').forEach(el => this.makeSingleInputDraggable(el));
        },
        
        makeSingleInputDraggable(inputEl) {
            // Use a flag to ensure listeners are only attached once
            if (inputEl._alpineDragStartListenerAttached) return;

            inputEl.setAttribute('draggable', 'true'); // Ensure it's draggable

            inputEl.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('inputId', event.target.closest('.signable-input').dataset.inputId);
                event.dataTransfer.setData('source', 'page');
                
                const rect = event.target.closest('.signable-input').getBoundingClientRect();
                const offsetX = event.clientX - rect.left;
                const offsetY = event.clientY - rect.top;
                
                event.dataTransfer.setData('offsetX', offsetX.toString());
                event.dataTransfer.setData('offsetY', offsetY.toString());
                event.dataTransfer.effectAllowed = "move";
                // console.log('Drag Start Existing Input:', { inputId: event.target.closest('.signable-input').dataset.inputId, offsetX, offsetY });
                event.stopPropagation(); // Important to prevent parent drag events if nested
            });
            inputEl._alpineDragStartListenerAttached = true;
        }
    }));
});
</script>