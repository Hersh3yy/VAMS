<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base form request for all entities providing common validation
 * Extend this class for entity-specific validation
 */
abstract class BaseEntityRequest extends FormRequest
{
    /**
     * Get the entity model class name
     */
    abstract protected function getEntityModelClass(): string;

    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by policies
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $entityModelClass = $this->getEntityModelClass();

        return $entityModelClass::getValidationRules();
    }

    /**
     * Get custom error messages for validator errors
     */
    public function messages(): array
    {
        $entityModelClass = $this->getEntityModelClass();

        return $entityModelClass::getValidationMessages();
    }
}
