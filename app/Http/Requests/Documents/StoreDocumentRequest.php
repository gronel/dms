<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'folder_id'        => ['nullable', 'integer', 'exists:folders,id'],
            'status'           => ['required', 'string', 'in:draft,published'],
            'file'             => ['required', 'file', 'max:102400'], // 100 MB max
            'change_summary'   => ['nullable', 'string', 'max:1000'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['integer', 'exists:tags,id'],
            'metadata'         => ['nullable', 'array'],
            'metadata.*.key'   => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'metadata.*.value' => ['required', 'string', 'max:1000'],
            'metadata.*.value_type' => ['required', 'string', 'in:string,date,number,boolean'],
        ];
    }
}
