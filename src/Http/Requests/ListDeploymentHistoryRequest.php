<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListDeploymentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string', 'max:1024'],
        ];
    }

    public function cursor(): ?string
    {
        $cursor = $this->validated('cursor');

        return is_string($cursor) && $cursor !== '' ? $cursor : null;
    }
}
