<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Configuration;

/**
 * Writer and editor shares. They do not change what the customer pays.
 * Missing configuration rows mean a zero share, which is also what the
 * current site uses when a setting has never been saved.
 */
final class PayoutRates
{
    public function __construct(
        public readonly string $writerPageMode = 'percentage',
        public readonly float $writerPageValue = 0,
        public readonly string $editorPageMode = 'percentage',
        public readonly float $editorPageValue = 0,
        public readonly float $writerProgressivePercent = 0,
        public readonly float $editorProgressivePercent = 0,
        public readonly float $writerSourcesPercent = 0,
        public readonly float $editorSourcesPercent = 0,
        public readonly float $writerComplexPercent = 0,
        public readonly float $editorComplexPercent = 0,
        public readonly float $writerProofPercent = 0,
        public readonly float $editorProofPercent = 0,
        public readonly float $writerOutlinePercent = 0,
        public readonly float $editorOutlinePercent = 0,
        public readonly float $writerSamplesPercent = 0,
        public readonly float $editorSamplesPercent = 0,
        public readonly float $writerVipPercent = 0,
        public readonly float $editorVipPercent = 0,
        public readonly float $writerPlagiarismPercent = 0,
        public readonly float $editorPlagiarismPercent = 0,
        public readonly float $writerCategoryPercent = 0,
        public readonly float $editorCategoryPercent = 0,
        public readonly float $writerPreferredPercent = 0,
        public readonly string $writerWordMode = 'percentage',
        public readonly float $writerWordValue = 0,
        public readonly string $editorWordMode = 'percentage',
        public readonly float $editorWordValue = 0,
    ) {}

    public static function fromConfiguration(): self
    {
        $get = fn (string $name, mixed $default = 0) => Configuration::read($name, $default);

        return new self(
            writerPageMode: (string) $get('WRITER_CPP_TYPE', 'percentage'),
            writerPageValue: (float) ($get('WRITER_CPP_TYPE', 'percentage') === 'fixed'
                ? $get('WRITER_AMOUNT_CPP', 0)
                : $get('WRITER_PERCENTAGE_CPP', 0)),
            editorPageMode: (string) $get('EDITOR_CPP_TYPE', 'percentage'),
            editorPageValue: (float) ($get('EDITOR_CPP_TYPE', 'percentage') === 'fixed'
                ? $get('EDITOR_AMOUNT_CPP', 0)
                : $get('EDITOR_PERCENTAGE_CPP', 0)),
            writerProgressivePercent: (float) $get('WRITER_PROGRESSIVE_DELIVERY_COST', 0),
            editorProgressivePercent: (float) $get('EDITOR_PROGRESSIVE_DELIVERY_COST', 0),
            writerSourcesPercent: (float) $get('WRITER_USED_SOURCES_COST', 0),
            editorSourcesPercent: (float) $get('EDITOR_USED_SOURCES_COST', 0),
            writerComplexPercent: (float) $get('WRITER_COMPLEX_ASSIGNMENT_COST', 0),
            editorComplexPercent: (float) $get('EDITOR_COMPLEX_ASSIGNMENT_COST', 0),
            writerProofPercent: (float) $get('WRITER_EXPERT_PROOFREADING_COST', 0),
            editorProofPercent: (float) $get('EDITOR_EXPERT_PROOFREADING_COST', 0),
            writerOutlinePercent: (float) $get('WRITER_DRAFT_OUTLINE_COST', 0),
            editorOutlinePercent: (float) $get('EDITOR_DRAFT_OUTLINE_COST', 0),
            writerSamplesPercent: (float) $get('WRITER_PROVIDE_ME_SAMPLES_COST', 0),
            editorSamplesPercent: (float) $get('EDITOR_PROVIDE_ME_SAMPLES_COST', 0),
            writerVipPercent: (float) $get('WRITER_VIP_SUPPORT_COST', 0),
            editorVipPercent: (float) $get('EDITOR_VIP_SUPPORT_COST', 0),
            writerPlagiarismPercent: (float) $get('WRITER_PLAGIARISM_REPORT_COST', 0),
            editorPlagiarismPercent: (float) $get('EDITOR_PLAGIARISM_REPORT_COST', 0),
            writerCategoryPercent: (float) $get('WRITER_W_CATEGORY_COST', 0),
            editorCategoryPercent: (float) $get('EDITOR_W_CATEGORY_COST', 0),
            writerPreferredPercent: (float) $get('WRITER_PREFFERED_WRITER_COST', 0),
            writerWordMode: (string) $get('WRITER_CPW_TYPE', 'percentage'),
            writerWordValue: (float) ($get('WRITER_CPW_TYPE', 'percentage') === 'fixed'
                ? $get('WRITER_AMOUNT_CPW', 0)
                : $get('WRITER_PERCENTAGE_CPW', 0)),
            editorWordMode: (string) $get('EDITOR_CPW_TYPE', 'percentage'),
            editorWordValue: (float) ($get('EDITOR_CPW_TYPE', 'percentage') === 'fixed'
                ? $get('EDITOR_AMOUNT_CPW', 0)
                : $get('EDITOR_PERCENTAGE_CPW', 0)),
        );
    }
}
