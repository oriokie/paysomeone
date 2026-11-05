<?php

namespace App\Http\Controllers\Office;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\SavedView;
use App\Services\Office\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends OfficeController
{
    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        abort_unless(
            \App\Services\Office\Access::allows($staff, 'orders.view_all')
            || \App\Services\Office\Access::allows($staff, 'orders.view_review')
            || \App\Services\Office\Access::allows($staff, 'orders.view_own'),
            403
        );

        $filters = [
            'q' => (string) $request->query('q', ''),
            'status' => (string) $request->query('status', ''),
            'lane' => (string) $request->query('lane', ''),
        ];
        if ($request->filled('view')) {
            $saved = SavedView::query()->where('employee_id', $staff->employee_id)->find($request->query('view'));
            if ($saved) {
                $filters = array_merge($filters, $saved->filters ?? []);
            }
        }

        $query = Board::query($staff)->orderByDesc('order_id');
        if ($filters['q'] !== '') {
            $query->where(function ($inner) use ($filters) {
                $inner->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('order_id', $filters['q']);
            });
        }
        if ($filters['status'] !== '') {
            $query->where('status_id', (int) $filters['status']);
        }
        $lanes = Board::lanes();
        if ($filters['lane'] !== '' && isset($lanes[$filters['lane']])) {
            $query->whereIn('status_id', $lanes[$filters['lane']]);
        }

        $orders = $query->paginate(20)->withQueryString();
        $kanban = [];
        foreach ($lanes as $name => $statuses) {
            $kanban[$name] = Board::query($staff)->whereIn('status_id', $statuses)->orderByDesc('order_id')->limit(8)->get();
        }

        return view('office.board', [
            'staff' => $staff,
            'orders' => $orders,
            'kanban' => $kanban,
            'filters' => $filters,
            'views' => SavedView::query()->where('employee_id', $staff->employee_id)->orderBy('name')->get(),
            'layout' => $request->query('layout', 'table') === 'kanban' ? 'kanban' : 'table',
        ]);
    }

    public function saveView(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        SavedView::query()->create([
            'employee_id' => $staff->employee_id,
            'name' => $data['name'],
            'filters' => $request->only(['q', 'status', 'lane']),
        ]);
        $this->audit($staff, 'board.view_saved', 'saved_view', $data['name']);

        return back()->with('status', 'View saved.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'orders.view_all');
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'note' => ['required', 'string', 'max:2000'],
        ]);
        $orders = Board::query($staff)->whereIn('order_id', $data['ids'])->get();
        foreach ($orders as $order) {
            OrderNote::query()->create([
                'order_id' => $order->order_id,
                'employee_id' => $staff->employee_id,
                'body' => $data['note'],
            ]);
            $this->audit($staff, 'order.note', 'order', (string) $order->order_id);
        }

        return back()->with('status', $orders->count().' internal notes added.');
    }
}
