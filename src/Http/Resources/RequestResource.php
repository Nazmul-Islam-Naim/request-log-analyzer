<?php

namespace NIN\RequestLogAnalyzer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'method'              => $this->method,
            'uri'                 => $this->uri,
            'url'                 => $this->url,
            'status_code'         => $this->status_code,
            'response_time_ms'    => $this->response_time_ms,
            'memory_usage_bytes'  => $this->memory_usage_bytes,
            'ip'                  => $this->ip,
            'country'             => $this->country,
            'city'                => $this->city,
            'user_id'             => $this->user_id,
            'tags'                => $this->tags ?? [],
            'errors_count'        => $this->whenCounted('errors'),
            'queries_count'       => $this->whenCounted('queries'),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
