<?php

namespace App\Http\Controllers;

use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HseOfficerMaterialRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaterialRequest::query()
            ->with(['material', 'project', 'requester', 'approver', 'requestedEmployees', 'items.material']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->get('project_id'));
        }

        $requests = $query->latest()->get();

        $projects = Project::query()->orderBy('project_name')->get();

        return view('hse-officer.material-requests.index', [
            'requests' => $requests,
            'projects' => $projects,
        ]);
    }

    public function show(Request $request, MaterialRequest $materialRequest): View
    {
        $materialRequest->load(['material', 'project', 'requester', 'approver', 'requestedEmployees.employee', 'items.material']);

        return view('hse-officer.material-requests.show', [
            'request' => $materialRequest,
        ]);
    }

    public function approve(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        if (! $materialRequest->isPending() && ! $materialRequest->isPartiallyApproved()) {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        $hasPendingEmployees = $materialRequest->requestedEmployees()
            ->whereNull('approved_at')
            ->exists();

        $isBulkRequest = ! $hasPendingEmployees && $materialRequest->requestedEmployees()->doesntExist();

        $now = now();
        $project = $materialRequest->project()->firstOrFail();
        $material = $materialRequest->material ?? $materialRequest->items()->first()?->material;

        // ── BULK (no-employee) path ────────────────────────────────────────────
        if ($isBulkRequest) {
            $validated = $request->validate([
                'bulk_permitted_quantity' => ['nullable', 'integer', 'min:1'],
                'item_permitted_quantities' => ['nullable', 'array'],
                'item_permitted_quantities.*' => ['nullable', 'integer', 'min:0'],
            ]);

            $receiverId = $project->assigned_site_officer?->id ?? $project->site_officer_id;

            DB::transaction(function () use ($materialRequest, $project, $request, $receiverId, $now, $validated, $material): void {
                $totalApproved = 0;

                if ($materialRequest->items()->exists()) {
                    $items = $materialRequest->items()->with('material')->get();
                    foreach ($items as $item) {
                        $itemMaterial = $item->material;
                        $approvedQuantity = isset($validated['item_permitted_quantities'][$item->id])
                            ? (int) $validated['item_permitted_quantities'][$item->id]
                            : $item->quantity;

                        if ($itemMaterial && $approvedQuantity > 0) {
                            if (! $itemMaterial->hasAvailableQuantity($approvedQuantity)) {
                                throw ValidationException::withMessages([
                                    'item_permitted_quantities' => "Insufficient head office balance for {$itemMaterial->material_name}. Available: {$itemMaterial->quantity}.",
                                ]);
                            }

                            $itemMaterial->deductQuantity($approvedQuantity);
                            $itemMaterial->recordHistory(
                                'assigned_to_project',
                                -$approvedQuantity,
                                "Material request approved & distributed — project {$project->project_code}.",
                                $request->user()->id,
                            );

                            MaterialProjectAssignment::create([
                                'material_id' => $itemMaterial->id,
                                'project_id' => $project->id,
                                'quantity' => $approvedQuantity,
                                'receiver_id' => $receiverId,
                                'assigned_by' => $request->user()->id,
                            ]);

                            $totalApproved += $approvedQuantity;
                        }
                    }
                } elseif ($material) {
                    $approvedQuantity = (int) ($validated['bulk_permitted_quantity'] ?? $materialRequest->quantity);

                    if (! $material->hasAvailableQuantity($approvedQuantity)) {
                        throw ValidationException::withMessages([
                            'bulk_permitted_quantity' => "Insufficient head office balance for {$material->material_name}. Available: {$material->quantity}.",
                        ]);
                    }

                    $material->deductQuantity($approvedQuantity);
                    $material->recordHistory(
                        'assigned_to_project',
                        -$approvedQuantity,
                        "Bulk material request approved & distributed — project {$project->project_code}.",
                        $request->user()->id,
                    );

                    MaterialProjectAssignment::create([
                        'material_id' => $material->id,
                        'project_id' => $project->id,
                        'quantity' => $approvedQuantity,
                        'receiver_id' => $receiverId,
                        'assigned_by' => $request->user()->id,
                    ]);

                    $totalApproved = $approvedQuantity;
                }

                $materialRequest->update([
                    'status' => 'approved',
                    'permitted_quantity' => $totalApproved,
                    'approved_by' => $request->user()->id,
                    'approved_at' => $now,
                    'distributed_at' => $now,
                ]);
            });

            $materialName = $materialRequest->material_name;

            app(WorkflowNotificationService::class)->notifyUser(
                $materialRequest->requester,
                new WorkflowNotification(
                    category: 'material_request_approved',
                    title: 'Material request approved & distributed',
                    message: "Your request for {$materialName} on project {$project->project_code} was approved by {$request->user()->name}.",
                    actionUrl: route('site-officer.material-requests.show', $materialRequest),
                ),
            );

            return Redirect::route('hse-officer.material-requests.index')
                ->with('success', "Material request for {$materialName} approved and distributed.");
        }

        // ── PER-EMPLOYEE path ──────────────────────────────────────────────────
        if (! $hasPendingEmployees) {
            throw ValidationException::withMessages([
                'status' => 'All requested employees are already approved.',
            ]);
        }

        $validated = $request->validate([
            'permitted_quantities' => ['required', 'array'],
            'permitted_quantities.*' => ['required', 'integer', 'min:0'],
        ]);

        $pendingEmployees = $materialRequest->requestedEmployees()
            ->whereNull('approved_at')
            ->get();

        // Map submitted permitted quantities; default to 0 if not supplied
        $permittedMap = collect($validated['permitted_quantities'])
            ->mapWithKeys(fn ($qty, $empId): array => [(int) $empId => (int) $qty]);

        $approvedQuantity = $pendingEmployees->sum(
            fn ($row): int => $permittedMap->get($row->employee_id, 0)
        );

        if ($materialRequest->items()->exists()) {
            $items = $materialRequest->items()->with('material')->get();
            foreach ($items as $item) {
                if ($item->material && ! $item->material->hasAvailableQuantity($item->quantity)) {
                    throw ValidationException::withMessages([
                        'permitted_quantities' => "Insufficient head office balance for {$item->material->material_name}. Available: {$item->material->quantity}.",
                    ]);
                }
            }
        } elseif ($material && $approvedQuantity > 0 && ! $material->hasAvailableQuantity($approvedQuantity)) {
            throw ValidationException::withMessages([
                'permitted_quantities' => "Insufficient head office balance for {$material->material_name}. Available: {$material->quantity}.",
            ]);
        }

        DB::transaction(function () use ($materialRequest, $project, $material, $request, $pendingEmployees, $permittedMap, $approvedQuantity, $now): void {
            $receiverId = $project->assigned_site_officer?->id ?? $project->site_officer_id;

            if ($materialRequest->items()->exists()) {
                $items = $materialRequest->items()->with('material')->get();
                foreach ($items as $item) {
                    $itemMaterial = $item->material;
                    if ($itemMaterial && $item->quantity > 0) {
                        $itemMaterial->deductQuantity($item->quantity);
                        $itemMaterial->recordHistory(
                            'assigned_to_project',
                            -$item->quantity,
                            "Material request approved & distributed — project {$project->project_code}.",
                            $request->user()->id,
                        );

                        MaterialProjectAssignment::create([
                            'material_id' => $itemMaterial->id,
                            'project_id' => $project->id,
                            'quantity' => $item->quantity,
                            'receiver_id' => $receiverId,
                            'assigned_by' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($material && $approvedQuantity > 0) {
                $material->deductQuantity($approvedQuantity);
                $material->recordHistory(
                    'assigned_to_project',
                    -$approvedQuantity,
                    "Material request approved & distributed — project {$project->project_code}.",
                    $request->user()->id,
                );

                MaterialProjectAssignment::create([
                    'material_id' => $material->id,
                    'project_id' => $project->id,
                    'quantity' => $approvedQuantity,
                    'receiver_id' => $receiverId,
                    'assigned_by' => $request->user()->id,
                ]);
            }

            // Approve all pending rows (mark approved + save permitted qty); create assignments only for qty > 0
            foreach ($pendingEmployees as $requestEmployee) {
                $permittedQty = $permittedMap->get($requestEmployee->employee_id, 0);

                $requestEmployee->update([
                    'permitted_quantity' => $permittedQty,
                    'approved_by' => $request->user()->id,
                    'approved_at' => $now,
                ]);

                if ($material && $permittedQty > 0) {
                    MaterialEmployeeAssignment::create([
                        'material_id' => $material->id,
                        'project_id' => $project->id,
                        'employee_id' => $requestEmployee->employee_id,
                        'quantity' => $permittedQty,
                        'assigned_date' => $now->toDateString(),
                        'assigned_by' => $request->user()->id,
                    ]);
                }
            }

            // Determine if any pending employees remain after this operation
            $stillPending = $materialRequest->requestedEmployees()
                ->whereNull('approved_at')
                ->exists();

            $materialRequest->update([
                'status' => $stillPending ? 'partial_approved' : 'approved',
                'permitted_quantity' => $approvedQuantity,
                'approved_by' => $request->user()->id,
                'approved_at' => $now,
                'distributed_at' => $stillPending ? null : $now,
            ]);
        });

        $materialName = $materialRequest->material_name;

        app(WorkflowNotificationService::class)->notifyUser(
            $materialRequest->requester,
            new WorkflowNotification(
                category: 'material_request_approved',
                title: 'Material request approved & distributed',
                message: "Your request for {$materialName} on project {$project->project_code} was approved. {$approvedQuantity} unit(s) distributed by {$request->user()->name}.",
                actionUrl: route('site-officer.material-requests.show', $materialRequest),
            ),
        );

        return Redirect::route('hse-officer.material-requests.index')
            ->with('success', "Material request for {$materialName} approved and distributed ({$approvedQuantity} units).");
    }

    public function reject(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        if (! $materialRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Mark all items as rejected if they exist
        $materialRequest->items()->where('status', 'pending')->update([
            'status' => 'rejected',
            'permitted_quantity' => 0,
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $materialName = $materialRequest->material_name;
        $project = $materialRequest->project;

        app(WorkflowNotificationService::class)->notifyUser(
            $materialRequest->requester,
            new WorkflowNotification(
                category: 'material_request_rejected',
                title: 'Material request rejected',
                message: "Your request for {$materialRequest->total_quantity} of {$materialName} on project {$project->project_code} was rejected by {$request->user()->name}. Reason: {$validated['rejection_reason']}",
                actionUrl: route('site-officer.material-requests.show', $materialRequest),
            ),
        );

        return Redirect::route('hse-officer.material-requests.index')
            ->with('success', "Material request for {$materialName} rejected.");
    }

    public function decideItems(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        if (! $materialRequest->isPending() && ! $materialRequest->isPartiallyApproved()) {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.action' => ['required', 'in:approve,reject,pending'],
            'items.*.permitted_quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $project = $materialRequest->project()->firstOrFail();
        $receiverId = $project->assigned_site_officer?->id ?? $project->site_officer_id;
        $now = now();

        $itemsToApprove = [];
        $itemsToReject = [];

        foreach ($validated['items'] as $itemId => $itemData) {
            $item = $materialRequest->items()->find($itemId);
            if (! $item) {
                continue;
            }

            $action = $itemData['action'] ?? 'pending';
            if ($action === 'approve') {
                $qty = isset($itemData['permitted_quantity']) && (int) $itemData['permitted_quantity'] > 0
                    ? (int) $itemData['permitted_quantity']
                    : $item->quantity;

                $material = $item->material;
                if (! $material || ! $material->hasAvailableQuantity($qty)) {
                    $available = $material?->quantity ?? 0;
                    throw ValidationException::withMessages([
                        'items' => "Insufficient head office balance for {$item->material?->material_name}. Available: {$available}, requested to distribute: {$qty}.",
                    ]);
                }

                $itemsToApprove[] = [
                    'item' => $item,
                    'quantity' => $qty,
                    'material' => $material,
                ];
            } elseif ($action === 'reject') {
                $itemsToReject[] = [
                    'item' => $item,
                    'reason' => trim((string) ($itemData['rejection_reason'] ?? 'Rejected by HSE Officer.')),
                ];
            }
        }

        if (empty($itemsToApprove) && empty($itemsToReject)) {
            return Redirect::back()->with('error', 'No item actions were selected.');
        }

        DB::transaction(function () use ($itemsToApprove, $itemsToReject, $materialRequest, $project, $receiverId, $request, $now): void {
            // Process approvals
            foreach ($itemsToApprove as $approval) {
                $item = $approval['item'];
                $qty = $approval['quantity'];
                $material = $approval['material'];

                $material->deductQuantity($qty);
                $material->recordHistory(
                    'assigned_to_project',
                    -$qty,
                    "Material '{$material->material_name}' approved & distributed for request #{$materialRequest->id} — project {$project->project_code}.",
                    $request->user()->id,
                );

                MaterialProjectAssignment::create([
                    'material_id' => $material->id,
                    'project_id' => $project->id,
                    'quantity' => $qty,
                    'receiver_id' => $receiverId,
                    'assigned_by' => $request->user()->id,
                ]);

                $item->update([
                    'status' => 'approved',
                    'permitted_quantity' => $qty,
                    'rejection_reason' => null,
                    'approved_by' => $request->user()->id,
                    'approved_at' => $now,
                ]);
            }

            // Process rejections
            foreach ($itemsToReject as $rejection) {
                $item = $rejection['item'];
                $reason = $rejection['reason'] ?: 'Rejected by HSE Officer.';

                $item->update([
                    'status' => 'rejected',
                    'permitted_quantity' => 0,
                    'rejection_reason' => $reason,
                    'approved_by' => $request->user()->id,
                    'approved_at' => $now,
                ]);
            }

            // Determine parent status
            $allItems = $materialRequest->items()->get();
            $pendingCount = $allItems->where('status', 'pending')->count();
            $approvedCount = $allItems->where('status', 'approved')->count();
            $rejectedCount = $allItems->where('status', 'rejected')->count();

            if ($pendingCount === 0) {
                if ($approvedCount > 0 && $rejectedCount === 0) {
                    $parentStatus = 'approved';
                } elseif ($approvedCount > 0 && $rejectedCount > 0) {
                    $parentStatus = 'partial_approved';
                } else {
                    $parentStatus = 'rejected';
                }
            } else {
                $parentStatus = $approvedCount > 0 ? 'partial_approved' : 'pending';
            }

            $totalPermitted = (int) $allItems->where('status', 'approved')->sum('permitted_quantity');

            $materialRequest->update([
                'status' => $parentStatus,
                'permitted_quantity' => $totalPermitted,
                'approved_by' => $request->user()->id,
                'approved_at' => $now,
                'distributed_at' => in_array($parentStatus, ['approved', 'partial_approved'], true) ? $now : null,
            ]);
        });

        // Notifications
        $summaryParts = [];
        if (! empty($itemsToApprove)) {
            $summaryParts[] = count($itemsToApprove).' item(s) approved';
        }
        if (! empty($itemsToReject)) {
            $summaryParts[] = count($itemsToReject).' item(s) rejected';
        }
        $summaryText = implode(' and ', $summaryParts);

        app(WorkflowNotificationService::class)->notifyUser(
            $materialRequest->requester,
            new WorkflowNotification(
                category: 'material_request_approved',
                title: 'Material Request Updated',
                message: "Your request on project {$project->project_code} was reviewed by {$request->user()->name}: {$summaryText}.",
                actionUrl: route('site-officer.material-requests.show', $materialRequest),
            ),
        );

        return Redirect::route('hse-officer.material-requests.show', $materialRequest)
            ->with('success', "Decision saved successfully ({$summaryText}).");
    }
}
