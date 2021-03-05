<?php

namespace Spartan\Rest\Test;

use PHPUnit\Framework\TestCase;
use Spartan\Rest\Transform\HashId;

class HashidTest extends TestCase
{
    public function testHash()
    {
        $config = [
            'salt' => 'This is a salt',
            'pad'  => 6,
            'alphabet' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
        ];
        $value  = 100;

        $encoded = HashId::encode($value, $config);
        $decoded = HashId::decode($encoded, $config);

        $this->assertSame($value, $decoded);
    }
}
