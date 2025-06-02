<?php

namespace App\Livewire;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentTemplateList extends Component
{
    use WithPagination;

    public $search = '';
    protected $queryString = ['search'];

    public function render()
    {
        $templates = DocumentTemplate::where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        // Create this file at: resources/views/livewire/document-template-list.blade.php
        return view('livewire.document-template-list', [
            'templates' => $templates,
        ])->layout('layouts.app');;
    }
}
