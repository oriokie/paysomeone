<?php

namespace App\Http\Controllers\Office;

use App\Models\Employee;
use App\Models\OfficeSetting;
use App\Models\Order;
use App\Models\OrderBid;
use App\Models\OrderEmployee;
use App\Models\OrderMessage;
use App\Models\OrderNote;
use App\Services\Office\Access;
use App\Services\Office\Board;
use App\Services\Orders\OrderFileStore;
use App\Support\Departments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkController extends OfficeController
{
    public function show(Request $request, Order $order): View
    {
        $staff = $this->staff($request);
        $this->visible($staff, $order);
        $order->load(['files', 'messages', 'revisions', 'bids.writer', 'customer', 'writer', 'editor', 'deadline', 'assignments.employee']);

        return view('office.order', [
            'staff' => $staff,
            'order' => $order,
            'customer' => Board::customer($staff, $order->customer),
            'notes' => OrderNote::query()->where('order_id', $order->order_id)->with('employee')->orderByDesc('id')->get(),
            'writers' => Access::allows($staff, 'orders.assign')
                ? Employee::query()->where('employee_group', Employee::GROUP_WRITER)->where('is_banned', 0)->orderBy('first_name')->limit(100)->get()
                : collect(),
            'editors' => Access::allows($staff, 'orders.assign')
                ? Employee::query()->where('employee_group', Employee::GROUP_EDITOR)->where('is_banned', 0)->orderBy('first_name')->limit(50)->get()
                : collect(),
        ]);
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'orders.assign');
        $data = $request->validate([
            'writer_id' => ['nullable', 'integer'],
            'editor_id' => ['nullable', 'integer'],
        ]);
        if (! empty($data['writer_id'])) {
            $writer = Employee::query()->where('employee_group', Employee::GROUP_WRITER)->findOrFail($data['writer_id']);
            $order->writer_id = $writer->employee_id;
            $order->writer_assigned = 1;
            $order->writer_assigned_at = now();
            $order->status_id = Order::WRITER_ASSIGNED;
            $this->seat($order, $writer, 'writer');
        }
        if (! empty($data['editor_id'])) {
            $editor = Employee::query()->where('employee_group', Employee::GROUP_EDITOR)->findOrFail($data['editor_id']);
            $order->editor_id = $editor->employee_id;
            $order->editor_assigned = 1;
            $order->editor_assigned_at = now();
            $this->seat($order, $editor, 'editor');
        }
        $order->date_upd = now();
        $order->save();
        $this->audit($staff, 'order.assign', 'order', (string) $order->order_id, $data);

        return back()->with('status', 'Assignment saved.');
    }

    public function approveBid(Request $request, Order $order, OrderBid $bid): RedirectResponse
    {
        $staff = $this->permit($request, 'orders.bids');
        abort_unless((int) $bid->order_id === (int) $order->order_id && ! $bid->is_deleted, 404);
        $writer = Employee::query()->findOrFail($bid->writer_id);
        $bid->is_assigned = 1;
        $bid->date_upd = now();
        $bid->save();
        $order->writer_id = $writer->employee_id;
        $order->writer_assigned = 1;
        $order->writer_assigned_at = now();
        $order->status_id = Order::WRITER_ASSIGNED;
        $order->date_upd = now();
        $order->save();
        $this->seat($order, $writer, 'writer');
        $this->audit($staff, 'order.bid_approved', 'order', (string) $order->order_id, ['bid_id' => $bid->bid_id]);

        return back()->with('status', 'Bid approved. The writer is assigned.');
    }

    public function deadline(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'orders.deadlines');
        $data = $request->validate([
            'customer_deadline' => ['required', 'date'],
            'writer_deadline' => ['required', 'date'],
        ]);
        $order->customer_deadline = $data['customer_deadline'];
        $order->writer_deadline = $data['writer_deadline'];
        $order->date_upd = now();
        $order->save();
        $this->audit($staff, 'order.deadline', 'order', (string) $order->order_id);

        return back()->with('status', 'Deadlines updated.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->staff($request);
        abort_unless(Access::allows($staff, 'orders.cancel') || Access::allows($staff, 'orders.refund'), 403);
        $limit = (float) OfficeSetting::get('subadmin_refund_limit', '150');
        if ((int) $staff->employee_group === Employee::GROUP_SUB_ADMIN && (float) $order->total > $limit) {
            abort(403, 'This order is above the cancellation limit set by an admin.');
        }
        $order->status_id = Order::CANCELLED;
        $order->cancelled_at = now();
        $order->date_upd = now();
        $order->save();
        $this->audit($staff, 'order.cancel', 'order', (string) $order->order_id, ['total' => $order->total]);

        return back()->with('status', 'Order cancelled.');
    }

    public function note(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->staff($request);
        $this->visible($staff, $order);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        OrderNote::query()->create([
            'order_id' => $order->order_id,
            'employee_id' => $staff->employee_id,
            'body' => $data['body'],
        ]);
        $this->audit($staff, 'order.note', 'order', (string) $order->order_id);

        return back()->with('status', 'Internal note saved.');
    }

    public function message(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'messages.staff');
        $this->visible($staff, $order);
        $allowed = [Departments::SUPPORT, Departments::WRITER];
        if (Access::allows($staff, 'messages.customer') || (int) $staff->employee_group === Employee::GROUP_WRITER) {
            $allowed[] = Departments::CUSTOMER;
        }
        $data = $request->validate([
            'to' => ['required', 'integer', 'in:'.implode(',', $allowed)],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $from = (int) $staff->employee_group === Employee::GROUP_WRITER
            ? Departments::WRITER
            : Departments::SUPPORT;
        $message = new OrderMessage;
        $message->order_id = $order->order_id;
        $message->sender_id = $staff->employee_id;
        $message->parent_id = 0;
        $message->from_department = $from;
        $message->to_department = (int) $data['to'];
        $message->subject = 'Order #'.$order->order_id;
        $message->body = $data['body'];
        $toCustomer = (int) $data['to'] === Departments::CUSTOMER;
        $message->is_verified = $toCustomer && Access::allows($staff, 'messages.customer') ? 1 : 0;
        $message->date_add = now();
        $message->date_upd = now();
        $message->save();
        $this->audit($staff, 'order.message', 'order', (string) $order->order_id, ['to' => $data['to']]);

        $note = $toCustomer && ! $message->is_verified
            ? 'Message held for an admin to release to the customer.'
            : 'Message sent.';

        return back()->with('status', $note);
    }

    public function release(Request $request, Order $order, OrderMessage $message): RedirectResponse
    {
        $staff = $this->permit($request, 'messages.customer');
        abort_unless((int) $message->order_id === (int) $order->order_id, 404);
        $message->is_verified = 1;
        $message->verified_at = now();
        $message->date_upd = now();
        $message->save();
        $this->audit($staff, 'message.release', 'order', (string) $order->order_id, ['message_id' => $message->message_id]);

        return back()->with('status', 'Message released to the customer.');
    }

    public function upload(Request $request, Order $order, OrderFileStore $files): RedirectResponse
    {
        $staff = $this->permit($request, 'files.upload');
        $this->visible($staff, $order);
        $data = $request->validate([
            'upload' => ['required', 'file', 'max:15360'],
            'description' => ['nullable', 'string', 'max:250'],
        ]);
        $files->store($order, (int) $staff->employee_id, $data['upload'], (string) ($data['description'] ?? ''));
        $this->audit($staff, 'order.file', 'order', (string) $order->order_id);

        return back()->with('status', 'File uploaded.');
    }

    public function download(Request $request, Order $order, int $file, OrderFileStore $files): StreamedResponse
    {
        $staff = $this->permit($request, 'files.download');
        $this->visible($staff, $order);
        $row = $order->files()->where('file_id', $file)->firstOrFail();

        return $files->download($row);
    }

    public function submitReview(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'files.upload');
        abort_unless((int) $order->writer_id === (int) $staff->employee_id, 403);
        abort_unless(in_array((int) $order->status_id, [Order::WRITER_ASSIGNED, Order::WRITER_ACCEPTED, Order::REVISION], true), 422);
        $order->status_id = Order::DONE;
        $order->completed_at = now();
        $order->date_upd = now();
        $order->save();
        $this->audit($staff, 'order.submit_review', 'order', (string) $order->order_id);

        return back()->with('status', 'Sent to the editor queue.');
    }

    public function approve(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'files.approve');
        abort_unless((int) $order->status_id === Order::DONE, 422);
        $order->status_id = Order::DELIVERED;
        $order->delivered_at = now();
        $order->date_upd = now();
        $order->save();
        $this->audit($staff, 'order.editor_approve', 'order', (string) $order->order_id);

        return back()->with('status', 'Approved and marked delivered.');
    }

    public function returnToWriter(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'files.approve');
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $order->status_id = Order::REVISION;
        $order->date_upd = now();
        $order->save();
        OrderNote::query()->create([
            'order_id' => $order->order_id,
            'employee_id' => $staff->employee_id,
            'body' => 'Returned to the writer: '.$data['body'],
        ]);
        $message = new OrderMessage;
        $message->order_id = $order->order_id;
        $message->sender_id = $staff->employee_id;
        $message->parent_id = 0;
        $message->from_department = Departments::SUPPORT;
        $message->to_department = Departments::WRITER;
        $message->subject = 'Revision on #'.$order->order_id;
        $message->body = $data['body'];
        $message->date_add = now();
        $message->date_upd = now();
        $message->save();
        $this->audit($staff, 'order.editor_return', 'order', (string) $order->order_id);

        return back()->with('status', 'Returned to the writer.');
    }

    private function visible(Employee $staff, Order $order): void
    {
        abort_unless(Board::query($staff)->where('order_id', $order->order_id)->exists(), 403);
    }

    private function seat(Order $order, Employee $person, string $type): void
    {
        OrderEmployee::query()->create([
            'order_id' => $order->order_id,
            'employee_type' => $type,
            'employee_id' => $person->employee_id,
            'is_employee_assigned' => 1,
            'employee_assigned_at' => now(),
            'date_add' => now(),
            'date_upd' => now(),
        ]);
    }
}
