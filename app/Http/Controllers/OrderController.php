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

        $message = 'Your brief is saved. The quoted total is $'.number_format($saved->quote_total, 2).'.';
        if (config('legacy.read_only')) {
            $message .= ' This preview does not write to the live order book, so the current website is unchanged.';
        } elseif ($saved->legacy_order_id) {
            $message .= ' Inquiry #'.$saved->legacy_order_id.' is in the order book. Payment is not taken on this preview.';
        } else {
            $message .= ' Sign in before sending if you want the inquiry filed on your existing account.';
        }

        return redirect()->route('order.create')->with('status', $message);
    }
}
