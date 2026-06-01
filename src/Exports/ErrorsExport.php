<?php

namespace NIN\RequestLogAnalyzer\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use NIN\RequestLogAnalyzer\Models\Error;

class ErrorsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Error::query()
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Request ID',
            'Exception Class',
            'Message',
            'File',
            'Line',
            'Severity',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->request_id,
            $row->exception_class,
            $row->message,
            $row->file,
            $row->line,
            $row->severity,
            $row->created_at?->toDateTimeString(),
        ];
    }
}
