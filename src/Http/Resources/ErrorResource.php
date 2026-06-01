<?php

namespace NIN\RequestLogAnalyzer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'request_id'       => $this->request_id,
            'exception_class'  => $this->exception_class,
            'message'          => $this->message,
            'file'             => $this->file,
            'line'             => $this->line,
            'severity'         => $this->severity,
            'request'          => $this->whenLoaded('request', fn () => [
                'id'          => $this->request->id,
                'method'      => $this->request->method,
                'uri'         => $this->request->uri,
                'status_code' => $this->request->status_code,
            ]),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
