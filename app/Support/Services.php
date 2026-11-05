<?php

namespace App\Support;

class Services
{
    public const ARTICLES = 'articles';

    public const RESUMES = 'resumes';

    public const EDITING = 'editing';

    public const REWRITING = 'rewriting';

    public const ALL = [
        self::ARTICLES => 'Articles and blog posts',
        self::RESUMES => 'Resumes and professional profiles',
        self::EDITING => 'Editing and proofreading',
        self::REWRITING => 'Rewriting',
    ];

    public static function usesWords(string $service): bool
    {
        return $service === self::ARTICLES;
    }
}
