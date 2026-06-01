<?php

namespace NIN\RequestLogAnalyzer\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use NIN\RequestLogAnalyzer\Models\Query;

class QueriesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Query::query()
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Request ID',
            'Connection',
            'SQL',
            'Time (ms)',
            'Is Slow',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->request_id,
            $row->connection,
            $row->sql,
            $row->time_ms,
            $row->is_slow ? 'Yes' : 'No',
            $row->created_at?->toDateTimeString(),
        ];
    }
}
