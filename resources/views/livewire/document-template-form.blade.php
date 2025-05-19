<div class="p-24">
    <form wire:submit.prevent="saveTemplate" class="space-y-6 p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-semibold text-gray-700">Create New Document Template</h2>
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

        <div>
            <label for="templateName" class="block text-sm font-medium text-gray-700">Template Name*</label>
            <input type="text" wire:model.defer="templateName" id="templateName" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @error('templateName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="templateDescription" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea wire:model.defer="templateDescription" id="templateDescription" rows="3"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            @error('templateDescription') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="pdfFile" class="block text-sm font-medium text-gray-700">Base PDF File*</label>
            <input type="file" wire:model="pdfFile" id="pdfFile" accept=".pdf" required
                   class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-md file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100">
            <div wire:loading wire:target="pdfFile" class="mt-1 text-sm text-gray-500">Uploading...</div>
            @error('pdfFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveTemplate, pdfFile"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="saveTemplate, pdfFile">Create Template & Add Fields</span>
                <span wire:loading wire:target="saveTemplate, pdfFile">Processing...</span>
            </button>
        </div>
    </form>
</div>