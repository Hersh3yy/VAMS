<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JsonException;

final class StoreEntriesJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payload') || ! $this->filled('payload_json')) {
            return;
        }

        $payloadJson = $this->string('payload_json')->toString();

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->merge(['payload' => null]);

            return;
        }

        $this->merge([
            'payload' => is_array($decoded) ? $decoded : null,
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
            'payload_json' => ['nullable', 'string'],
            'payload' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entry_type_id.required' => 'The entry type is required.',
            'entry_type_id.exists' => 'The selected entry type does not exist.',
            'payload.required' => 'JSON payload is required.',
            'payload.array' => 'JSON payload must decode to an object or an array of objects.',
        ];
    }
}
