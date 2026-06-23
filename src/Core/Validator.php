<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $erros = [];

    public function __construct(private readonly array $dados) {}

    public function required(string $campo, string $label): self
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || (is_string($v) && trim($v) === '')) {
            $this->erros[$campo] = "{$label} é obrigatório.";
        }
        return $this;
    }

    public function email(string $campo, string $label): self
    {
        $v = (string) ($this->dados[$campo] ?? '');
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->erros[$campo] = "{$label} inválido.";
        }
        return $this;
    }

    public function min(string $campo, int $min, string $label): self
    {
        $v = $this->dados[$campo] ?? '';
        if (is_numeric($v) && (float) $v < $min) {
            $this->erros[$campo] = "{$label} deve ser no mínimo {$min}.";
        }
        return $this;
    }

    public function minLength(string $campo, int $min, string $label): self
    {
        $v = (string) ($this->dados[$campo] ?? '');
        if ($v !== '' && mb_strlen($v) < $min) {
            $this->erros[$campo] = "{$label} deve ter no mínimo {$min} caracteres.";
        }
        return $this;
    }

    public function decimalPositivo(string $campo, string $label): self
    {
        $v = $this->dados[$campo] ?? '';
        if ($v !== '' && (!is_numeric($v) || (float) $v < 0)) {
            $this->erros[$campo] = "{$label} deve ser um valor numérico válido.";
        }
        return $this;
    }

    public function inEnum(string $campo, array $valores, string $label): self
    {
        $v = (string) ($this->dados[$campo] ?? '');
        if ($v !== '' && !in_array($v, $valores, true)) {
            $this->erros[$campo] = "{$label} inválido.";
        }
        return $this;
    }

    public function cpfCnpj(string $campo, string $label): self
    {
        $doc = preg_replace('/\D/', '', (string) ($this->dados[$campo] ?? ''));
        if ($doc === '') {
            return $this;
        }

        $valido = strlen($doc) === 11 ? self::validarCpf($doc) : (strlen($doc) === 14 ? self::validarCnpj($doc) : false);
        if (!$valido) {
            $this->erros[$campo] = "{$label} inválido.";
        }
        return $this;
    }

    public static function validarCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }
        return true;
    }

    public static function validarCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $calc = static function (string $base, array $pesos): int {
            $soma = 0;
            foreach ($pesos as $i => $p) {
                $soma += (int) $base[$i] * $p;
            }
            $resto = $soma % 11;
            return $resto < 2 ? 0 : 11 - $resto;
        };
        return $calc($cnpj, $pesos1) === (int) $cnpj[12]
            && $calc($cnpj, $pesos2) === (int) $cnpj[13];
    }

    /** @return array<string, string> */
    public function erros(): array
    {
        return $this->erros;
    }

    public function falhou(): bool
    {
        return $this->erros !== [];
    }
}
