<?php

namespace App\Services;

use App\Models\Material;
use App\Models\UnitOfMeasure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialSpreadsheetService
{
    /**
     * @var list<string>
     */
    public const HEADERS = ['material_name', 'material_description', 'quantity', 'unit_of_measure'];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Materials');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->fromArray([
            ['Cement', 'High-strength cement bags', 100, 'Bag'],
        ], null, 'A2');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="materials_import_template.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function import(UploadedFile $file, ?int $createdBy = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        if ($rows === []) {
            return ['imported' => 0, 'errors' => ['The file is empty.']];
        }

        $headerRow = array_map(
            fn ($value): string => strtolower(trim((string) $value)),
            $rows[0] ?? []
        );

        if (! $this->headersAreValid($headerRow)) {
            return [
                'imported' => 0,
                'errors' => ['Invalid template. Download the template and use columns: material_name, material_description, quantity, unit_of_measure.'],
            ];
        }

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRowToData($row);

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                'material_name' => ['required', 'string', 'max:255'],
                'material_description' => ['required', 'string'],
                'quantity' => ['required', 'integer', 'min:0'],
                'unit_of_measure' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Row '.$rowNumber.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            $unitOfMeasureId = $this->resolveUnitOfMeasureId($data['unit_of_measure']);

            if ($unitOfMeasureId === null) {
                $errors[] = 'Row '.$rowNumber.': Invalid unit_of_measure. Use a template value or create the unit in the system.';

                continue;
            }

            $material = Material::create([
                'material_name' => $data['material_name'],
                'material_description' => $data['material_description'],
                'quantity' => $data['quantity'],
                'unit_of_measure_id' => $unitOfMeasureId,
            ]);

            $material->recordHistory(
                'imported',
                $material->quantity,
                'Imported from spreadsheet template.',
                $createdBy,
            );

            $imported++;
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * @param  list<mixed>  $headerRow
     */
    private function headersAreValid(array $headerRow): bool
    {
        foreach (self::HEADERS as $index => $expected) {
            if (($headerRow[$index] ?? '') !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<mixed>  $row
     * @return array{material_name: string, material_description: string, quantity: int, unit_of_measure: string}
     */
    private function mapRowToData(array $row): array
    {
        return [
            'material_name' => trim((string) ($row[0] ?? '')),
            'material_description' => trim((string) ($row[1] ?? '')),
            'quantity' => is_numeric($row[2] ?? null) ? (int) $row[2] : 0,
            'unit_of_measure' => trim((string) ($row[3] ?? '')),
        ];
    }

    /**
     * @param  array{material_name: string, material_description: string, quantity: int, unit_of_measure: string}  $data
     */
    private function rowIsEmpty(array $data): bool
    {
        return $data['material_name'] === '' && $data['material_description'] === '' && $data['quantity'] === 0 && $data['unit_of_measure'] === '';
    }

    private function resolveUnitOfMeasureId(string $name): ?int
    {
        $normalized = strtolower(trim($name));

        return UnitOfMeasure::query()
            ->get()
            ->firstWhere(fn (UnitOfMeasure $unit) => strtolower($unit->name) === $normalized)?->id;
    }
}
