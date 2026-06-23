<?php

declare(strict_types=1);

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testCpfValido(): void
    {
        $this->assertTrue(Validator::validarCpf('52998224725'));
    }

    public function testCpfInvalido(): void
    {
        $this->assertFalse(Validator::validarCpf('11111111111'));
    }
}
