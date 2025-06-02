<div>
    <form wire:submit.prevent="save" class="space-y-6 p-4 bg-white shadow-md rounded-lg">
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
            <label for="documentTitle" class="block text-sm font-medium text-gray-700">Document Title (Optional)</label>
            <input type="text" wire:model.defer="documentTitle" id="documentTitle"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                   placeholder="e.g., Lease Agreement">
            @error('documentTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="pdfFile" class="block text-sm font-medium text-gray-700">PDF File</label>
            <input type="file" wire:model="pdfFile" id="pdfFile" accept=".pdf"
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
                    wire:target="save, pdfFile"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="save, pdfFile">Upload and Process Document</span>
                <span wire:loading wire:target="save, pdfFile">Processing...</span>
            </button>
        </div>
    </form>
</div>