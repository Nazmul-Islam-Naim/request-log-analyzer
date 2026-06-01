<?php

namespace NIN\RequestLogAnalyzer\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use NIN\RequestLogAnalyzer\Models\Request as RlaRequest;

class RequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return RlaRequest::query()
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Method',
            'URI',
            'Status Code',
            'Response Time (ms)',
            'IP',
            'Country',
            'City',
            'User ID',
            'Memory (bytes)',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->method,
            $row->uri,
            $row->status_code,
            $row->response_time_ms,
            $row->ip,
            $row->country,
            $row->city,
            $row->user_id,
            $row->memory_usage_bytes,
            $row->created_at?->toDateTimeString(),
        ];
    }
}
