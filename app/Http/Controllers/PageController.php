<?php

namespace App\Http\Controllers;

use App\Models\OrderRating;
use App\Support\RateCatalog;
use App\Support\Services;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'reviews' => $this->recentReviews(3),
        ]);
    }

    public function services(): View
    {
        return view('pages.services');
    }

    public function how(): View
    {
        return view('pages.how');
    }

    public function pricing(RateCatalog $catalog): View
    {
        return view('pages.pricing', [
            'rates' => $this->safe(fn () => $catalog->grouped(), []),
            'services' => Services::ALL,
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function reviews(): View
    {
        return view('pages.reviews', [
            'reviews' => $this->recentReviews(24),
        ]);
    }

    public function legal(string $page): View
    {
        $pages = [
            'privacy' => 'Privacy',
            'terms' => 'Terms of use',
            'refunds' => 'Refunds',
        ];

        abort_unless(isset($pages[$page]), 404);

        return view('pages.legal', [
            'page' => $page,
            'heading' => $pages[$page],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrderRating>
     */
    private function recentReviews(int $limit)
    {
        return $this->safe(function () use ($limit) {
            return OrderRating::query()
                ->where('user_type', 'customer')
                ->whereNotNull('comment')
                ->where('comment', '!=', '')
                ->where('service_rating', '>=', 4)
                ->orderByDesc('date_add')
                ->limit($limit)
                ->get();
        }, collect());
    }

    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
