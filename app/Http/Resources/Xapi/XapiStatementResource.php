<?php

namespace App\Http\Resources\Xapi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\XapiStatement
 */
class XapiStatementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->statement_id,
            'actor' => [
                'mbox' => $this->actor_mbox,
                'name' => $this->actor_name,
            ],
            'verb' => [
                'id' => $this->verb_id,
                'display' => ['en-US' => $this->verb_display],
            ],
            'object' => [
                'objectType' => $this->object_type,
                'id' => $this->object_id,
                'definition' => [
                    'name' => $this->object_name ? ['en-US' => $this->object_name] : null,
                ],
            ],
            'result' => $this->when($this->hasResult(), fn () => array_filter([
                'score' => array_filter([
                    'raw' => $this->result_score_raw,
                    'min' => $this->result_score_min,
                    'max' => $this->result_score_max,
                    'scaled' => $this->result_score_scaled,
                ], fn ($v) => $v !== null),
                'success' => $this->result_success,
                'completion' => $this->result_completion,
                'duration' => $this->result_duration,
            ], fn ($v) => $v !== null && $v !== [])),
            'context' => $this->when($this->hasContext(), fn () => array_filter([
                'registration' => $this->context_registration,
                'extensions' => $this->context_extensions,
            ], fn ($v) => $v !== null)),
            'timestamp' => $this->timestamp?->toISOString(),
            'stored' => $this->stored?->toISOString(),
        ];
    }

    protected function hasResult(): bool
    {
        return $this->result_score_raw !== null
            || $this->result_success !== null
            || $this->result_completion !== null
            || $this->result_duration !== null;
    }

    protected function hasContext(): bool
    {
        return $this->context_registration !== null
            || $this->context_extensions !== null;
    }
}
