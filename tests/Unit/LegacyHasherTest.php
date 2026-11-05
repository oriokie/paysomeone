<?php

namespace Tests\Unit;

use App\Auth\LegacyHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LegacyHasherTest extends TestCase
{
    #[Test]
    public function it_accepts_the_three_formats_already_stored_and_writes_bcrypt(): void
    {
        $hasher = new LegacyHasher(4);
        $salted = '123456789'.hash('sha1', 'secret123456789');

        $this->assertTrue($hasher->check('secret', $salted));
        $this->assertFalse($hasher->check('other', $salted));
        $this->assertTrue($hasher->check('secret', md5('secret')));
        $this->assertTrue($hasher->needsRehash($salted));
        $this->assertTrue($hasher->needsRehash(md5('secret')));

        $fresh = $hasher->make('secret');

        $this->assertTrue(password_verify('secret', $fresh));
        $this->assertSame('bcrypt', password_get_info($fresh)['algoName']);
        $this->assertFalse($hasher->needsRehash($fresh));
        $this->assertTrue($hasher->check('secret', $fresh));
    }
}
