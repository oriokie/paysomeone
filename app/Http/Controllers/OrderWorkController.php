<?php

namespace App\Http\Controllers;

use App\Events\DeskEvent;
use App\Exceptions\LegacyWriteBlocked;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\ReviewConsent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderRating;
use App\Models\OrderRevision;
use App\Services\Desk\Notifier;
use App\Services\Orders\OrderFileStore;
use App\Services\Payments\CheckoutService;
use App\Support\Departments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderWorkController extends Controller
{
    public function upload(Request $request, Order $order, OrderFileStore $files, Notifier $notifier): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $this->writable();
        $data = $request->validate([
            'upload' => ['required', 'file', 'max:15360'],
            'description' => ['nullable', 'string', 'max:250'],
        ]);

        $file = $files->store($order, (int) $customer->customer_id, $data['upload'], (string) ($data['description'] ?? ''));
        $notifier->staff('file', 'file', (string) $order->order_id, 'A file was added to order #'.$order->order_id, (int) $customer->customer_id);

        return back()->with('status', 'File saved as '.$file->source.'. The current site can open it from the shared uploads folder.')->with('tab', 'files');
    }

    public function download(Request $request, Order $order, OrderFile $file, OrderFileStore $files): StreamedResponse
    {
        $this->owns($request, $order);
        abort_unless((int) $file->order_id === (int) $order->order_id, 404);

        return $files->download($file);
    }

    public function message(Request $request, Order $order, Notifier $notifier): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $this->writable();
        $data = $request->validate([
            'to' => ['required', 'in:support,writer'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $to = $data['to'] === 'writer' ? Departments::WRITER : Departments::SUPPORT;
        if ($to === Departments::WRITER && ! $order->writer_id) {
            return back()->withErrors(['body' => 'A writer has not been assigned yet. Write to support instead.']);
        }

        $message = new OrderMessage;
        $message->order_id = $order->order_id;
        $message->sender_id = $customer->customer_id;
        $message->parent_id = 0;
        $message->from_department = Departments::CUSTOMER;
        $message->to_department = $to;
        $message->subject = 'Order #'.$order->order_id;
        $message->body = $data['body'];
        $message->date_add = now();
        $message->date_upd = now();
        $message->save();

        $notifier->staff('message', 'new_message', (string) $order->order_id, 'New message on order #'.$order->order_id, (int) $customer->customer_id);
        $recipientId = $to === Departments::WRITER ? $order->writer_id : $order->editor_id;
        $recipient = $recipientId ? Employee::query()->find($recipientId) : null;
        if ($recipient && $recipient->email) {
            $notifier->email('customer_message', (string) $recipient->email, [
                'order_id' => $order->order_id,
                'excerpt' => mb_substr($data['body'], 0, 180),
            ], 'New customer message on order #'.$order->order_id, 'messages', false, route('office.orders.show', $order));
        }
        $notifier->email('order_message', (string) $customer->email, [
            'order_id' => $order->order_id,
            'message' => $data['body'],
            'from_department' => 'Customer',
            'to_department' => Departments::NAMES[$to],
        ], 'Message saved on order #'.$order->order_id);

        DeskEvent::dispatch('order.message', (int) $order->order_id, ['message_id' => (int) $message->message_id]);

        return back()->with('status', 'Message sent.')->with('tab', 'messages');
    }

    public function revision(Request $request, Order $order, Notifier $notifier): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $this->writable();
        if (! in_array((int) $order->status_id, [Order::DELIVERED, Order::FINISHED], true)) {
            return back()->withErrors(['instructions' => 'A revision can be requested once the work has been delivered.']);
        }

        $data = $request->validate([
            'instructions' => ['required', 'string', 'min:10', 'max:5000'],
            'hours' => ['required', 'integer', 'in:3,6,12,24,48,72'],
            'file_id' => ['nullable', 'integer'],
        ]);

        if (OrderRevision::query()->where('order_id', $order->order_id)->count() >= 2) {
            return back()->withErrors(['instructions' => 'This order already has two revision requests. Write to support if something is still wrong.']);
        }

        $free = true;
        if ((int) $order->status_id === Order::FINISHED && $order->approved_at) {
            $free = \Illuminate\Support\Carbon::parse($order->approved_at)->gt(now()->subDays(14));
        }

        $revision = new OrderRevision;
        $revision->order_id = $order->order_id;
        $revision->file_id = (int) ($data['file_id'] ?? 0);
        $revision->instructions = $data['instructions'];
        $revision->client_deadline = (int) $data['hours'];
        $revision->free_revision = $free ? 1 : 0;
        $revision->date_add = now();
        $revision->date_upd = now();
        $revision->save();

        $order->status_id = Order::REVISION;
        $order->date_upd = now();
        $order->save();

        DeskEvent::dispatch('order.revision', (int) $order->order_id, ['revision_id' => (int) $revision->revision_id]);
        $writer = $order->writer_id ? Employee::query()->find($order->writer_id) : null;
        if ($writer && $writer->email) {
            $notifier->email('revision_requested', (string) $writer->email, [
                'order_id' => $order->order_id,
                'title' => $order->title,
            ], 'Revision requested on order #'.$order->order_id, 'orders', false, route('office.orders.show', $order));
        }
        $notifier->email('revision_customer', (string) $customer->email, [
            'order_id' => $order->order_id,
            'title' => $order->title,
        ], 'We received your revision request', 'orders', false, route('desk.order', $order));

        return back()->with('status', $free ? 'Free revision requested.' : 'Revision requested. It may be billed because the free window has passed.')->with('tab', 'revision');
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $this->owns($request, $order);
        $this->writable();
        if ((int) $order->status_id !== Order::DELIVERED) {
            return back()->withErrors(['order' => 'The delivery can be approved once the editor has released it.']);
        }
        $order->status_id = Order::FINISHED;
        $order->approved_at = now();
        $order->finished_at = now();
        $order->date_upd = now();
        $order->save();
        $customer = $request->user('customer');
        if ($customer) {
            app(Notifier::class)->email('order_completed', (string) $customer->email, [
                'order_id' => $order->order_id,
                'title' => $order->title,
            ], 'Order #'.$order->order_id.' is finished', 'orders', false, route('desk.order', $order));
        }

        return back()->with('status', 'Delivery approved. The order is finished.');
    }

    public function rate(Request $request, Order $order): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $this->writable();
        $data = $request->validate([
            'service_rating' => ['required', 'integer', 'between:1,5'],
            'paper_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'paper_on_time' => ['nullable', 'boolean'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $rating = OrderRating::query()->firstOrNew([
            'order_id' => $order->order_id,
            'user_type' => 'customer',
            'user_id' => $customer->customer_id,
        ]);
        $rating->service_rating = $data['service_rating'];
        $rating->paper_rating = $data['paper_rating'];
        $rating->comment = $data['comment'] ?? '';
        $rating->paper_on_time = (bool) ($data['paper_on_time'] ?? false);
        $rating->date_add = $rating->date_add ?: now();
        $rating->date_upd = now();
        $rating->save();
        ReviewConsent::query()->updateOrCreate(
            ['rating_id' => $rating->rating_id],
            ['customer_id' => $customer->customer_id, 'consented' => $request->boolean('publish')],
        );

        return back()->with('status', $request->boolean('publish')
            ? 'Thank you. The note can appear on the public reviews page without your name.'
            : 'Thank you. The review stays on this order and is not public.')->with('tab', 'payment');
    }

    public function tip(Request $request, Order $order, CheckoutService $checkout): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:500']]);

        return back()->with('status', $checkout->tipFromWallet($order, $customer, (float) $data['amount']))->with('tab', 'payment');
    }

    private function owns(Request $request, Order $order): Customer
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        abort_unless((int) $order->customer_id === (int) $customer->customer_id, 403);

        return $customer;
    }

    private function writable(): void
    {
        if (config('legacy.read_only')) {
            throw new LegacyWriteBlocked('prox_order');
        }
    }
}
