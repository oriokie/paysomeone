<?php

namespace App\Support;

class Services
{
    public const ARTICLES = 'articles';

    public const RESUMES = 'resumes';

    public const EDITING = 'editing';

    public const REWRITING = 'rewriting';

    public const SOFTWARE = 'software';

    /** Labels used on the public pricing page. */
    public const ALL = [
        self::ARTICLES => 'Articles and blog posts',
        self::RESUMES => 'Resumes and professional profiles',
        self::EDITING => 'Editing and proofreading',
        self::REWRITING => 'Rewriting',
    ];

    /**
     * Services a customer can place. Software is on the order form only.
     * The public pricing page stays on ALL.
     *
     * @var array<string, string>
     */
    public const ORDERABLE = [
        self::ARTICLES => 'Article writing',
        self::RESUMES => 'Resume',
        self::EDITING => 'Editing and proofreading',
        self::REWRITING => 'Rewriting',
        self::SOFTWARE => 'Programming',
    ];

    public static function usesWords(string $service): bool
    {
        return $service === self::ARTICLES;
    }

    public static function known(string $service): bool
    {
        return isset(self::ORDERABLE[$service]);
    }
}
