<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Deadline;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuoteAndPagesTest extends TestCase
{
    #[Test]
    public function the_public_pages_render(): void
    {
        foreach (['/', '/services', '/how-it-works', '/pricing', '/about', '/faq', '/contact', '/reviews', '/order', '/blog', '/legal/terms', '/legal/privacy', '/legal/refunds', '/login', '/register'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/sitemap.xml')->assertOk();
    }

    #[Test]
    public function the_quote_endpoint_prices_an_article_from_the_rate_card(): void
    {
        $deadline = $this->deadline(0.05, article: true);

        $this->postJson('/order/quote', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 800,
        ])->assertOk()
            ->assertJsonPath('amount_due', 40)
            ->assertJsonPath('currency', 'USD');
    }

    #[Test]
    public function a_page_quote_includes_proofreading_and_a_percent_coupon(): void
    {
        $deadline = $this->deadline(40, resume: true);
        Coupon::query()->create([
            'site_id' => 1,
            'coupon_code' => 'SAVE10',
            'coupon_type' => Coupon::PERCENT,
            'coupon_value' => 10,
            'active' => 1,
            'date_add' => now(),
            'date_upd' => now(),
        ]);

        $this->postJson('/order/quote', [
            'service' => 'editing',
            'deadline_id' => $deadline->deadline_id,
            'pages' => 2,
            'spacing' => 'double',
            'proofreading' => true,
            'coupon_code' => 'SAVE10',
        ])->assertOk()->assertJsonPath('amount_due', 90);
    }

    #[Test]
    public function a_brief_is_stored_without_touching_the_live_order_table_while_read_only(): void
    {
        $deadline = $this->deadline(0.05, article: true);
        config(['legacy.read_only' => true]);

        $this->post('/order', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 500,
            'title' => 'A note for the board',
            'brief' => 'This is a short brief for the board update, with enough detail to be useful.',
            'name' => 'Board Writer',
            'email' => 'board@example.test',
            'terms' => '1',
        ])->assertRedirect('/order');

        $this->assertDatabaseHas('app_project_requests', ['email' => 'board@example.test']);
        $this->assertDatabaseMissing('prox_order', ['title' => 'A note for the board']);
    }

    private function deadline(float $rate, bool $article = false, bool $resume = false): Deadline
    {
        return Deadline::query()->create([
            'site_id' => 1,
            'name' => '5 days',
            'price_per_page' => $rate,
            'hrs_customer' => 120,
            'hrs_writer' => 96,
            'min_pages' => 1,
            'is_article_writing' => $article ? 1 : 0,
            'is_resume' => $resume ? 1 : 0,
            'is_academic_writing' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
    }
}
