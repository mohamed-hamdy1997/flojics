<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscalateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in(array_keys(config('notification_channels.channels', [])))],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function channels(): array
    {
        return $this->validated('channels') ?? config('notification_channels.default', []);
    }
}
