<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Supplier;
use App\Models\Unidade;
use App\Support\ManagementScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManager($user);

        $activeUnit = $this->resolveUnit($request);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $canFilterList = ManagementScope::isAdmin($user);
        $today = Carbon::today()->toDateString();
        $filterUnits = collect();
        $selectedUnitId = null;
        $filters = [
            'start_date' => trim((string) $request->query('start_date', $today)),
            'end_date' => trim((string) $request->query('end_date', $today)),
            'unit_id' => 'all',
        ];
        $expensesQuery = Expense::with([
            'supplier:id,name',
            'unit:tb2_id,tb2_nome',
            'user:id,name',
        ])
            ->leftJoin('tb2_unidades', 'tb2_unidades.tb2_id', '=', 'expenses.unit_id')
            ->orderBy('tb2_unidades.tb2_nome')
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($canFilterList) {
            $filterUnits = ManagementScope::managedUnits($user, ['tb2_id', 'tb2_nome'])
                ->map(fn (Unidade $unit) => [
                    'id' => (int) $unit->tb2_id,
                    'name' => $unit->tb2_nome,
                ])
                ->values();

            $selectedUnitId = $this->resolveSelectedUnitId($request->query('unit_id'), $filterUnits);
            $filters['unit_id'] = $selectedUnitId !== null ? (string) $selectedUnitId : 'all';

            if ($selectedUnitId !== null) {
                $expensesQuery->where('unit_id', $selectedUnitId);
            } elseif ($filterUnits->isNotEmpty()) {
                $expensesQuery->whereIn('unit_id', $filterUnits->pluck('id'));
            } else {
                $expensesQuery->whereRaw('1 = 0');
            }

            if ($filters['start_date'] !== '') {
                $expensesQuery->whereDate('expense_date', '>=', $filters['start_date']);
            }

            if ($filters['end_date'] !== '') {
                $expensesQuery->whereDate('expense_date', '<=', $filters['end_date']);
            }
        } elseif ($activeUnit) {
            $expensesQuery->where('unit_id', $activeUnit['id']);
        } else {
            $expensesQuery->whereRaw('1 = 0');
        }

        $currentUserId = (int) $user->id;

        $expenses = $expensesQuery->get([
            'expenses.id',
            'expenses.supplier_id',
            'expenses.unit_id',
            'expenses.user_id',
            'expenses.expense_date',
            'expenses.amount',
            'expenses.notes',
        ])->map(function (Expense $expense) use ($currentUserId) {
            $expense->setAttribute('can_delete', (int) $expense->user_id === $currentUserId);

            return $expense;
        });

        return Inertia::render('Finance/ExpenseIndex', [
            'suppliers' => $suppliers,
            'expenses' => $expenses,
            'activeUnit' => $activeUnit,
            'canFilterList' => $canFilterList,
            'filterUnits' => $filterUnits,
            'filters' => $filters,
            'listTotalAmount' => round((float) $expenses->sum('amount'), 2),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager($request->user());

        $activeUnit = $this->resolveUnit($request);
        if (! $activeUnit) {
            return back()->with('error', 'Unidade ativa nao definida para registrar o gasto.');
        }

        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        Expense::create(array_merge($data, [
            'unit_id' => $activeUnit['id'],
            'user_id' => $request->user()->id,
        ]));

        return back()->with('success', 'Gasto cadastrado com sucesso!');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->ensureManager($request->user());
        $activeUnit = $this->resolveUnit($request);

        if (
            ! $activeUnit
            || (int) $expense->unit_id !== (int) $activeUnit['id']
            || (int) $expense->user_id !== (int) $request->user()->id
        ) {
            abort(403);
        }

        $expense->delete();

        return back()->with('success', 'Gasto removido com sucesso!');
    }

    private function ensureManager($user): void
    {
        if (! $user || ! in_array((int) $user->funcao, [0, 1, 3], true)) {
            abort(403);
        }
    }

    private function resolveUnit(Request $request): ?array
    {
        $sessionUnit = $request->session()->get('active_unit');

        if (is_array($sessionUnit)) {
            $unitId = $sessionUnit['id'] ?? $sessionUnit['tb2_id'] ?? null;
            if ($unitId) {
                return [
                    'id' => (int) $unitId,
                    'name' => (string) ($sessionUnit['name'] ?? $sessionUnit['tb2_nome'] ?? ''),
                ];
            }
        }

        if (is_object($sessionUnit)) {
            $unitId = $sessionUnit->id ?? $sessionUnit->tb2_id ?? null;
            if ($unitId) {
                return [
                    'id' => (int) $unitId,
                    'name' => (string) ($sessionUnit->name ?? $sessionUnit->tb2_nome ?? ''),
                ];
            }
        }

        $user = $request->user();
        $unitId = (int) ($user?->tb2_id ?? 0);

        if ($unitId <= 0) {
            return null;
        }

        $unit = Unidade::find($unitId, ['tb2_id', 'tb2_nome']);

        return [
            'id' => (int) $unitId,
            'name' => $unit?->tb2_nome ?? ('Unidade #' . $unitId),
        ];
    }

    private function resolveSelectedUnitId(mixed $value, Collection $filterUnits): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        $unitId = (int) $value;

        if ($unitId <= 0) {
            return null;
        }

        return $filterUnits->contains(fn (array $unit) => (int) $unit['id'] === $unitId)
            ? $unitId
            : null;
    }
}
