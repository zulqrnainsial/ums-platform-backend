<?php

namespace App\Modules\Assessment\Imports;

use App\Modules\Assessment\Services\QuestionEditorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionExcelImport implements ToCollection
{
    private array $result = [
        'import_batch_no' => null,
        'created' => 0,
        'failed_count' => 0,
        'failed' => [],
    ];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->result['failed'][] = [
                'row' => 0,
                'message' => 'Uploaded file is empty.',
            ];
            $this->result['failed_count'] = 1;
            return;
        }

        $headerRow = $rows->first()
            ->map(fn ($value) => $this->normalizeHeader((string) $value))
            ->toArray();

        $dataRows = [];

        foreach ($rows->slice(1)->values() as $rowIndex => $row) {
            $mapped = [];

            foreach ($headerRow as $columnIndex => $header) {
                if (!$header) {
                    continue;
                }

                $mapped[$header] = $this->cleanCell($row[$columnIndex] ?? null);
            }

            if ($this->isEmptyRow($mapped)) {
                continue;
            }

            $mapped['_excel_row_no'] = $rowIndex + 2;
            $dataRows[] = $mapped;
        }

        $service = app(QuestionEditorService::class);
        $this->result = $service->bulkImport($dataRows);
    }

    public function result(): array
    {
        return $this->result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = strtolower($header);
        $header = str_replace([' ', '-', '.'], '_', $header);
        $header = preg_replace('/[^a-z0-9_]/', '', $header);

        return $header ?: '';
    }

    private function cleanCell(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        return $value;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === '_excel_row_no') {
                continue;
            }

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}