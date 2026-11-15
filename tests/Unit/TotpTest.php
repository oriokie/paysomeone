<?php

namespace Tests\Unit;

use App\Services\Office\Totp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    #[Test]
    public function a_generated_secret_is_uppercase_base32_without_padding(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $secret = Totp::secret();
            $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
            $this->assertSame(0, strlen($secret) % 8);
            $this->assertStringNotContainsString('=', $secret);
            $this->assertSame($secret, str_replace(' ', '', Totp::grouped($secret)));
        }
    }

    #[Test]
    public function the_otpauth_uri_encodes_the_label_and_keeps_a_clean_secret(): void
    {
        $uri = Totp::uri('JBSWY3DPEHPK3PXP', 'ada@example.test', 'PaySomeoneToWrite');

        $this->assertSame(
            'otpauth://totp/PaySomeoneToWrite:ada%40example.test?secret=JBSWY3DPEHPK3PXP&issuer=PaySomeoneToWrite&algorithm=SHA1&digits=6&period=30',
            $uri,
        );

        $messy = Totp::uri('jbsw y3dp-ehpk3pxp====', 'ada@example.test', 'Pay:Someone/Write & Co');
        $this->assertStringStartsWith('otpauth://totp/Pay%20Someone%20Write%20Co:ada%40example.test?secret=JBSWY3DPEHPK3PXP&', $messy);
        $this->assertStringContainsString('issuer=Pay%20Someone%20Write%20Co', $messy);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $messy);
        $this->assertStringNotContainsString('%3A', $messy);

        $fallback = Totp::uri('JBSWY3DPEHPK3PXP', 'ada:extra@example.test', ':::');
        $this->assertStringStartsWith('otpauth://totp/Office:ada%3Aextra%40example.test?', $fallback);
        $this->assertStringContainsString('issuer=Office', $fallback);

        $secret = Totp::secret();
        parse_str((string) parse_url(Totp::uri($secret, 'ada@example.test', 'Office'), PHP_URL_QUERY), $query);
        $this->assertSame($secret, $query['secret']);
        $this->assertTrue(Totp::verify($secret, Totp::at($query['secret'])));
    }
}
