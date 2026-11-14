<?php

namespace App\Support;

use App\Models\Deadline;
use Illuminate\Support\Collection;

class RateCatalog
{
    public function deadlinesFor(string $service): Collection
    {
        if ($service === Services::ARTICLES) {
            return $this->flagged('is_article_writing');
        }

        $resumes = $this->flagged('is_resume');

        if ($resumes->isNotEmpty()) {
            return $resumes;
        }

        return $this->flagged('is_academic_writing')
            ->where('is_article_writing', false)
            ->where('is_calculations', false)
            ->where('is_programming', false)
            ->values();
    }

    public function allows(Deadline $deadline, string $service): bool
    {
        return $this->deadlinesFor($service)->contains('deadline_id', $deadline->deadline_id);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach (array_keys(Services::ALL) as $service) {
            $grouped[$service] = $this->deadlinesFor($service)->map(function (Deadline $deadline) use ($service) {
                return [
                    'id' => (int) $deadline->deadline_id,
                    'name' => $deadline->name,
                    'hours' => (int) $deadline->hrs_customer,
                    'rate' => (float) $deadline->price_per_page,
                    'unit' => Services::usesWords($service) ? 'word' : 'page',
                    'min_pages' => (int) ($deadline->min_pages ?: 1),
                ];
            })->values()->all();
        }

        return $grouped;
    }

    private function flagged(string $column): Collection
    {
        $siteId = (int) config('legacy.site_id');
        $base = Deadline::query()->where($column, 1)->orderBy('hrs_customer');
        $scoped = (clone $base)->where('site_id', $siteId);

        return ($scoped->exists() ? $scoped : $base)->get();
    }
}
