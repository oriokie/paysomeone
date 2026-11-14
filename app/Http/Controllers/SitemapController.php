<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            route('home'),
            route('services'),
            route('how'),
            route('pricing'),
            route('about'),
            route('faq'),
            route('contact'),
            route('reviews'),
            route('order.create'),
            route('blog.index'),
            route('legal', 'privacy'),
            route('legal', 'terms'),
            route('legal', 'refunds'),
        ];

        try {
            foreach (Post::query()->published()->pluck('slug') as $slug) {
                $urls[] = route('blog.show', $slug);
            }
        } catch (\Throwable) {
            // The blog table is created by the app migration. A bare legacy
            // database can still serve the rest of the sitemap.
        }

        $body = view('sitemap', ['urls' => $urls])->render();

        return response($body, 200, ['Content-Type' => 'application/xml']);
    }
}
