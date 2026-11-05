<?php

namespace App\Exceptions;

use RuntimeException;

class LegacyWriteBlocked extends RuntimeException
{
    public function __construct(public readonly string $target)
    {
        parent::__construct('This preview is read-only, so that change was not saved to the live database.');
    }
}
