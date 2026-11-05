<?php

namespace App\Http\Controllers;

use App\Models\Deadline;
use App\Services\Orders\InquiryRecorder;
use App\Services\Pricing\QuoteBuilder;
use App\Support\RateCatalog;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(RateCatalog $catalog): View
    {
        return view('pages.order', [
            'options' => [
                'defaultService' => Services::ARTICLES,
                'services' => Services::ALL,
                'deadlines' => $catalog->grouped(),
                'tiers' => WriterTiers::TIERS,
                'quoteUrl' => route('quote'),
            ],
        ]);
    }

    public function quote(Request $request, QuoteBuilder $quotes): JsonResponse
    {
        $quote = $quotes->build($request->all());

        return response()->json($quote->toArray());
    }

    public function store(Request $request, QuoteBuilder $quotes, InquiryRecorder $recorder): RedirectResponse
    {
        $data = $request->validate([
            'service' => ['required', 'string'],
            'deadline_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'brief' => ['required', 'string', 'min:30', 'max:20000'],
            'name' => [$request->user('customer') ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => [$request->user('customer') ? 'nullable' : 'required', 'email', 'max:255'],
            'words' => ['nullable', 'integer'],
            'pages' => ['nullable', 'integer'],
            'spacing' => ['nullable', 'in:single,double'],
            'writer_category_id' => ['nullable', 'integer'],
            'preferred_writer' => ['nullable', 'boolean'],
            'samples' => ['nullable', 'boolean'],
            'progressive' => ['nullable', 'boolean'],
            'sources' => ['nullable', 'boolean'],
            'complex' => ['nullable', 'boolean'],
            'proofreading' => ['nullable', 'boolean'],
            'vip' => ['nullable', 'boolean'],
            'plagiarism' => ['nullable', 'boolean'],
            'outline' => ['nullable', 'boolean'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'intent' => ['nullable', 'in:pay,inquiry'],
            'terms' => ['accepted'],
        ]);

        $customer = $request->user('customer');
        if ($customer) {
            $data['name'] = $customer->name;
            $data['email'] = $customer->email;
        }

        $quote = $quotes->build($data);
        $deadline = Deadline::query()->findOrFail($data['deadline_id']);
        $saved = $recorder->record($data, $quote, $customer, $deadline);

        if (config('legacy.read_only')) {
            return redirect()->route('order.create')->withErrors([
                'legacy' => 'Placing an order is blocked on this preview. The quote above is only a preview, and the live order book was not changed.',
            ]);
        }

        if ($saved->legacy_order_id) {
            return redirect()->route('desk.order', $saved->legacy_order_id)
                ->with('status', 'Order #'.$saved->legacy_order_id.' is in the order book. The total is $'.number_format($saved->quote_total, 2).'.');
        }

        return redirect()->route('login')->with('status', 'Sign in before sending so the order can be filed on your account.');
    }
}
