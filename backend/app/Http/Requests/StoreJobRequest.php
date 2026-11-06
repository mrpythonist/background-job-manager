<?php

namespace App\Http\Requests;

use App\Services\JobDispatcher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queue' => ['required', 'string', 'max:100', 'exists:queues,name'],
            'handler' => ['required', 'string', Rule::in(JobDispatcher::HANDLERS)],
            'payload' => ['sometimes', 'array'],
            'payload.simulate.duration_ms' => ['sometimes', 'integer', 'min:0', 'max:60000'],
            'payload.simulate.should_fail' => ['sometimes', 'boolean'],
            'payload.simulate.fail_message' => ['sometimes', 'string', 'max:500'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'max_attempts' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'delay_seconds' => ['sometimes', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function messages(): array
    {
        return [
            'queue.exists' => 'The queue does not exist. Create it first with POST /api/queues.',
            'handler.in' => 'The handler must be one of: ' . implode(', ', JobDispatcher::HANDLERS) . '.',
        ];
    }
}
