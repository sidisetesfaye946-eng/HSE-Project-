<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialHistory;
use App\Models\UnitOfMeasure;
use App\Services\MaterialSpreadsheetService;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function __construct(private MaterialSpreadsheetService $spreadsheetService) {}

    public function index(Request $request): View
    {
        $query = Material::with('unitOfMeasure')->latest();
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('material_name', 'like', "%{$search}%")
                    ->orWhere('material_description', 'like', "%{$search}%")
                    ->orWhereHas('unitOfMeasure', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return view('materials.index', [
            'materials' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('materials.create', [
            'material' => new Material,
            'unitOfMeasures' => UnitOfMeasure::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_name' => ['required', 'string', 'max:255'],
            'material_description' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_of_measure_id' => ['required', 'exists:unit_of_measures,id'],
        ]);

        $material = Material::create($data);

        if ($material->quantity > 0) {
            $material->recordHistory(
                'created',
                $material->quantity,
                'Initial material creation.',
                $request->user()?->id
            );
        }

        app(WorkflowNotificationService::class)->materialCreated($material, $request->user());

        return Redirect::route('materials.index')->with('success', 'Material created successfully.');
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', [
            'material' => $material,
            'unitOfMeasures' => UnitOfMeasure::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $request->validate([
            'material_name' => ['required', 'string', 'max:255'],
            'material_description' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_of_measure_id' => ['required', 'exists:unit_of_measures,id'],
        ]);

        $oldQty = $material->quantity;
        $newQty = (int) $data['quantity'];
        $qtyChange = $newQty - $oldQty;

        // Recalculate base/initial stock pool so that:
        // initial_stock = new Head Office quantity + total stock distributed to projects so far
        $totalDistributed = (int) $material->assignments()->sum('quantity');
        $data['initial_stock'] = $newQty + $totalDistributed;

        $material->update($data);

        if ($qtyChange !== 0) {
            MaterialHistory::create([
                'material_id' => $material->id,
                'event_type' => 'stock_updated',
                'quantity_change' => $qtyChange,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'description' => 'Material stock quantity updated via edit.',
                'created_by' => $request->user()?->id,
            ]);
        }

        app(WorkflowNotificationService::class)->materialUpdated($material, $request->user());

        return Redirect::route('materials.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return Redirect::route('materials.index')->with('success', 'Material deleted successfully.');
    }

    public function importForm(): View
    {
        return view('materials.import');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->spreadsheetService->downloadTemplate();
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv,txt', 'max:10240'],
        ]);

        try {
            $result = $this->spreadsheetService->import($request->file('file'), $request->user()?->id);
        } catch (\Throwable $e) {
            return Redirect::route('materials.import')
                ->with('error', 'Failed to process import file: '.$e->getMessage());
        }

        if ($result['imported'] === 0 && ! empty($result['errors'])) {
            return Redirect::route('materials.import')
                ->with('error', implode(' ', $result['errors']));
        }

        $message = $result['imported'].' material(s) imported successfully.';
        if (! empty($result['errors'])) {
            $message .= ' Some rows were skipped: '.implode(' ', $result['errors']);
        }

        return Redirect::route('materials.index')->with('success', $message);
    }
}
