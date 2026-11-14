<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderCompatibilityTest extends TestCase
{
    public function test_a_new_order_still_has_the_columns_the_old_office_reads(): void
    {
        $required = [
            'order_id', 'site_id', 'tracking_id', 'customer_id', 'status_id', 'service_type',
            'form_type_id', 'form_type', 'order_type', 'tariff_id', 'title', 'paper_details',
            'paper_type_id', 'paper_type_option', 'academic_level_id', 'topic_category_id',
            'topic_category_option', 'paper_format_id', 'paper_format_option', 'spacing',
            'words', 'pages', 'keywords', 'english_type_id', 'target_audience_id',
            'writing_tone_id', 'writing_style_id', 'content_feel_id', 'writer_category_id',
            'writer_percent', 'total', 'is_paid', 'currency_id', 'date_add',
        ];
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('prox_order');
        foreach ($required as $column) {
            $this->assertContains($column, $columns, $column);
        }
        $this->assertNotContains('deliverable_id', $columns);
    }
}
