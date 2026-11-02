<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaterialHistory::with(['material', 'createdBy'])->latest();
        $search = $request->string('search')->trim()->toString();

        $hasFilter = false;

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
            $hasFilter = true;
        }

        if ($search !== '') {
            $query->whereHas('material', function ($query) use ($search): void {
                $query->where('material_name', 'like', "%{$search}%")
                    ->orWhere('material_description', 'like', "%{$search}%");
            });

            $hasFilter = true;
        }

        if ($request->filled('quarter')) {
            $quarter = $request->input('quarter');
            $year = $request->input('year', date('Y'));

            $months = match ($quarter) {
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
                default => null,
            };

            if ($months) {
                $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', '>=', $months[0])
                    ->whereMonth('created_at', '<=', $months[1]);
                $hasFilter = true;
            }
        }

        $histories = $query->paginate(15)->withQueryString();

        $materials = Material::with('histories')
            ->orderBy('material_name')
            ->get()
            ->map(function (Material $material): array {
                $openingBalance = $material->histories->last()?->balance_before ?? 0;

                return [
                    'material' => $material,
                    'opening_balance' => $openingBalance,
                    'current_balance' => $material->quantity,
                ];
            });

        return view('material-histories.index', [
            'histories' => $histories,
            'materials' => $materials,
        ]);
    }

    public function export(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $query = MaterialHistory::with(['material', 'createdBy'])->latest();
        $search = $request->string('search')->trim()->toString();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        if ($search !== '') {
            $query->whereHas('material', function ($query) use ($search): void {
                $query->where('material_name', 'like', "%{$search}%")
                    ->orWhere('material_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('quarter')) {
            $quarter = $request->input('quarter');
            $year = $request->input('year', date('Y'));

            $months = match ($quarter) {
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
                default => null,
            };

            if ($months) {
                $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', '>=', $months[0])
                    ->whereMonth('created_at', '<=', $months[1]);
            }
        }

        $histories = $query->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-histories.export', [
                'histories' => $histories,
                'title' => 'Material History Report',
            ]);

            return $pdf->download('material_history.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material History');

        $sheet->fromArray([
            ['Date', 'Material', 'Event', 'Quantity Change', 'Balance Before', 'Balance After', 'Description', 'Recorded By'],
        ], null, 'A1');

        $row = 2;

        foreach ($histories as $history) {
            $sheet->fromArray([
                $history->created_at->format('Y-m-d H:i:s'),
                $history->material?->material_name,
                $this->eventLabel($history->event_type),
                $history->quantity_change,
                $history->balance_before,
                $history->balance_after,
                $history->description,
                $history->createdBy?->name,
            ], null, "A{$row}");

            $row++;
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="material_history.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'created' => 'Created',
            'imported' => 'Imported',
            'stock_added' => 'Head Office Stock Added',
            'assigned_to_project' => 'Assigned to Project',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }
}
