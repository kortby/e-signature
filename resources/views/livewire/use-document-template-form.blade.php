<div>
    <form wire:submit.prevent="createDocumentFromTemplate" class="space-y-6 p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-semibold text-gray-700">Create Document from Template</h2>

        @if (session()->has('success'))
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- Select Template --}}
        <div>
            <label for="selectedTemplateId" class="block text-sm font-medium text-gray-700">Select Template*</label>
            <select wire:model.live="selectedTemplateId" id="selectedTemplateId" required
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">-- Choose a template --</option>
                @foreach ($availableTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
            @error('selectedTemplateId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        @if ($selectedTemplate)
            {{-- Document Title --}}
            <div>
                <label for="documentTitle" class="block text-sm font-medium text-gray-700">Document Title*</label>
                <input type="text" wire:model.defer="documentTitle" id="documentTitle" required
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                @error('documentTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- Select Recipient User --}}
            <div class="mt-4">
                <label for="selectedUserId" class="block text-sm font-medium text-gray-700">Select Recipient User (Optional)</label>
                <select wire:model.live="selectedUserId" id="selectedUserId"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">-- Select a user to prefill recipient info --</option>
                    @foreach ($availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('selectedUserId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- Recipient Info Fields (can be auto-populated or manually entered) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="recipientName" class="block text-sm font-medium text-gray-700">Recipient Name</label>
                    <input type="text" wire:model.defer="recipientName" id="recipientName"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    @error('recipientName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="recipientEmail" class="block text-sm font-medium text-gray-700">Recipient Email</label>
                    <input type="email" wire:model.defer="recipientEmail" id="recipientEmail"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    @error('recipientEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Pre-fill Template Fields --}}
            <h3 class="text-lg font-medium text-gray-800 mt-6 mb-2 border-b pb-2">Pre-fill Template Fields</h3>
            @if ($selectedTemplate->templateFields()->where('is_prefillable', true)->count() > 0)
                <div class="space-y-4">
                    @foreach ($selectedTemplate->templateFields()->where('is_prefillable', true)->orderBy('label')->get() as $field)
                        <div>
                            <label for="prefillData_{{ $field->key_name }}" class="block text-sm font-medium text-gray-700">
                                {{ $field->label ?: Str::title(str_replace('_', ' ', $field->key_name)) }}
                                <span class="text-xs text-gray-500">(Key: <code class="text-xs bg-gray-100 p-0.5 rounded">{{ $field->key_name }}</code>)</span>
                                @if($field->data_source_mapping)
                                    <span class="ml-1 text-blue-600 text-xs" title="Auto-filled from: {{ $dataSourceOptions[$field->data_source_mapping] ?? $field->data_source_mapping }}">🔗 Auto</span>
                                @endif
                            </label>
                            @php
                                $isReadOnly = !empty($field->data_source_mapping) && $this->selectedUserId; // Make field readonly if mapped and user selected
                            @endphp
                            @if($field->type === 'text' || $field->type === 'date' || $field->type === 'initials')
                                <input type="{{ $field->type === 'date' ? 'date' : 'text' }}"
                                       wire:model="prefillData.{{ $field->key_name }}" {{-- Use .live or .blur if needed, defer might not update immediately for display --}}
                                       id="prefillData_{{ $field->key_name }}"
                                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 {{ $isReadOnly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                       {{ $isReadOnly ? 'readonly' : '' }}>
                            @elseif($field->type === 'checkbox')
                                <div class="mt-1 flex items-center">
                                     <input type="checkbox"
                                           wire:model="prefillData.{{ $field->key_name }}"
                                           id="prefillData_{{ $field->key_name }}" value="1" {{-- Standard value for checked --}}
                                           class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 {{ $isReadOnly ? 'cursor-not-allowed' : '' }}"
                                           {{ $isReadOnly ? 'disabled' : '' }}>
                                    <label for="prefillData_{{ $field->key_name }}" class="ml-2 text-sm text-gray-700">Is Checked</label>
                                </div>
                            @else
                                <input type="text"
                                       wire:model="prefillData.{{ $field->key_name }}"
                                       id="prefillData_{{ $field->key_name }}"
                                       placeholder="Value for {{ $field->type }}"
                                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 {{ $isReadOnly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                       {{ $isReadOnly ? 'readonly' : '' }}>
                            @endif
                            @error('prefillData.' . $field->key_name) <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">This template has no pre-fillable fields defined.</p>
            @endif

            <div class="pt-5">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                    <span wire:loading.remove>Create Document</span>
                    <span wire:loading>Creating Document...</span>
                </button>
            </div>
        @else
            <p class="text-sm text-gray-500 mt-4">Select a template to see pre-fill options.</p>
        @endif
    </form>
</div>