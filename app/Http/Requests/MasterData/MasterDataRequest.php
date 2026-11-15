<?php

namespace App\Http\Requests\MasterData;

use App\Support\MasterDataRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slug = (string) $this->route('resource');
        $permission = MasterDataRegistry::get($slug)['permissions']['manage'] ?? null;

        return $permission !== null && $this->user()?->can($permission) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $slug = (string) $this->route('resource');
        $config = MasterDataRegistry::get($slug);
        $rules = [];
        $recordId = $this->route('record');

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules = array_merge($fieldRules, match ($field['type']) {
                'number' => ['numeric', 'min:0'],
                'date' => ['date'],
                'color' => ['string', 'max:16'],
                'relation' => ['integer', 'exists:'.MasterDataRegistry::get($field['relation'])['model']::make()->getTable().',id'],
                'status' => ['string', Rule::in($field['options'] ?? ['active', 'inactive'])],
                default => ['string', 'max:255'],
            });

            if ($name === 'description' || $name === 'notes') {
                $fieldRules = ($field['required'] ?? false) ? ['required', 'string'] : ['nullable', 'string'];
            }

            if (isset($config['unique'][$name])) {
                $column = $config['unique'][$name];
                $unique = Rule::unique(MasterDataRegistry::get($slug)['model']::make()->getTable(), $column);
                if ($recordId) {
                    $unique->ignore($recordId);
                }
                $fieldRules[] = $unique;
            }

            if ($name === 'parent_id' && $recordId) {
                $fieldRules[] = Rule::notIn([(int) $recordId]);
            }

            $rules[$name] = $fieldRules;
        }

        return $rules;
    }
}