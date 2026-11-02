<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class MaterialQuantityController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('material-quantities.index', [
            'materials' => Material::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('material_name', 'like', "%{$search}%")
                            ->orWhere('material_description', 'like', "%{$search}%");
                    });
                })
                ->orderBy('material_name')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function edit(Material $material): View
    {
        return view('material-quantities.edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $request->validate([
            'quantity_to_add' => ['required', 'integer'],
            'adjustment_type' => ['nullable', 'string', 'in:add,subtract,deduct'],
        ]);

        $rawAmount = (int) $data['quantity_to_add'];
        $type = $data['adjustment_type'] ?? null;

        if ($type === 'subtract' || $type === 'deduct') {
            $change = -abs($rawAmount);
        } elseif ($type === 'add') {
            $change = abs($rawAmount);
        } else {
            $change = $rawAmount;
        }

        if ($change === 0) {
            return Redirect::back()
                ->withInput()
                ->withErrors(['quantity_to_add' => 'Adjustment amount cannot be zero.']);
        }

        if ($change < 0 && abs($change) > (int) $material->quantity) {
            return Redirect::back()
                ->withInput()
                ->withErrors([
                    'quantity_to_add' => 'Cannot deduct '.abs($change).' units. Current available Head Office balance is '.$material->quantity.'.',
                ]);
        }

        $material->adjustQuantity($change);
        $newQty = (int) $material->fresh()->quantity;

        $eventType = $change > 0 ? 'stock_added' : 'stock_deducted';
        $description = $change > 0
            ? 'Head office quantity added.'
            : 'Head office quantity deducted via adjustment.';

        $material->recordHistory($eventType, $change, $description, $request->user()?->id);

        app(WorkflowNotificationService::class)->materialQuantityUpdated(
            $material->fresh(),
            $change,
            $request->user(),
        );

        $actionWord = $change > 0 ? 'increased' : 'reduced';

        return Redirect::route('material-quantities.index')
            ->with('success', "Head office quantity {$actionWord} by ".abs($change)." for {$material->material_name}. New balance: {$newQty}.");
    }
}
