<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Admin\Support\SettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return SettingsSchema::rules();
    }
}
