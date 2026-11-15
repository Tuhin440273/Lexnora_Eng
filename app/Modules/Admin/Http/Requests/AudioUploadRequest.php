<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AudioUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('audio.manage');
    }

    /**
     * The extension must be allowed AND the MIME type detected from the file
     * content must be an audio type, so renaming a file is not enough.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                'extensions:'.implode(',', config('learning.audio.extensions')),
                'mimetypes:'.implode(',', config('learning.audio.mimetypes')),
                'max:'.config('learning.audio.max_kb'),
            ],
            'make_primary' => ['sometimes', 'boolean'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'voice_label' => ['nullable', 'string', 'max:60'],
        ];
    }
}
