<?php

namespace App\Support;

use App\Models\ContentFeel;
use App\Models\Deliverable;
use App\Models\EnglishType;
use App\Models\PaperType;
use App\Models\SoftwareProgramming;
use App\Models\TargetAudience;
use App\Models\WritingStyle;
use App\Models\WritingTone;
use Illuminate\Support\Collection;

/**
 * Lookup rows for the customer order form.
 *
 * Academic-only choices stay in the tables and are not offered here:
 * academic level, citation format, subject, application essays, and any
 * paper type flagged is_academic_writing or titled like a student paper.
 */
class OrderFormCatalog
{
    /** @var list<string> */
    private const HIDDEN_TITLES = [
        'essay',
        'dissertation',
        'thesis',
        'coursework',
        'term paper',
        'admission',
        'annotated bibliography',
        'literature review',
        'homework',
        'assignment',
    ];

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'articles' => $this->paperTypes(true),
            'documents' => $this->paperTypes(false),
            'englishTypes' => $this->pairs(EnglishType::query()->orderBy('title')->get(), 'english_type_id'),
            'audiences' => $this->pairs(TargetAudience::query()->orderBy('title')->get(), 'target_audience_id'),
            'tones' => $this->described(WritingTone::query()->orderBy('title')->get(), 'writing_tone_id'),
            'styles' => $this->described(WritingStyle::query()->orderBy('title')->get(), 'writing_style_id'),
            'feels' => $this->described(ContentFeel::query()->orderBy('title')->get(), 'content_feel_id'),
            'software' => $this->pairs(SoftwareProgramming::query()->orderBy('title')->get(), 'software_programming_id', 'title'),
            'deliverables' => $this->pairs(Deliverable::query()->orderBy('name')->get(), 'deliverable_id', 'name'),
        ];
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function paperTypes(bool $articles): array
    {
        $query = PaperType::query()->orderBy('title');

        if ($articles) {
            $query->where('is_article_writing', 1)->where('is_academic_writing', 0);
        } else {
            $query->where('is_academic_writing', 0)->where('is_article_writing', 0);
        }

        return $query->get()
            ->reject(fn (PaperType $type) => $this->hidden((string) $type->title))
            ->map(fn (PaperType $type) => [
                'id' => (int) $type->paper_type_id,
                'title' => (string) $type->title,
            ])
            ->values()
            ->all();
    }

    public function hidden(string $title): bool
    {
        $title = strtolower($title);

        foreach (self::HIDDEN_TITLES as $needle) {
            if (str_contains($title, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return list<array{id: int, title: string}>
     */
    private function pairs(Collection $rows, string $key, string $label = 'title'): array
    {
        return $rows->map(fn ($row) => [
            'id' => (int) $row->{$key},
            'title' => (string) $row->{$label},
        ])->values()->all();
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return list<array{id: int, title: string, description: string}>
     */
    private function described(Collection $rows, string $key): array
    {
        return $rows->map(fn ($row) => [
            'id' => (int) $row->{$key},
            'title' => (string) $row->title,
            'description' => (string) ($row->description ?? ''),
        ])->values()->all();
    }
}
