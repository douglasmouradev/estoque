<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UnidadePeca;
use App\Models\Peca;

/** Importação CSV linha a linha com relatório de erros */
final class CsvPecaImporter
{
    /** @return array{importados: int, erros: list<array{linha: int, mensagem: string}>} */
    public static function importar(string $caminho, ?int $userId): array
    {
        $handle = fopen($caminho, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o arquivo CSV.');
        }

        $importados = 0;
        $erros = [];
        $linha = 0;
        $cabecalho = fgetcsv($handle, 0, ';');
        if ($cabecalho === false) {
            fclose($handle);
            return ['importados' => 0, 'erros' => [['linha' => 0, 'mensagem' => 'Arquivo vazio']]];
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linha++;
            $data = array_combine(
                array_map('trim', $cabecalho),
                array_map('trim', $row)
            );
            if ($data === false) {
                $erros[] = ['linha' => $linha, 'mensagem' => 'Colunas inconsistentes'];
                continue;
            }

            try {
                self::validarLinha($data);
                Peca::criar([
                    'codigo_interno' => $data['codigo_interno'],
                    'codigo_oem' => $data['codigo_oem'] ?? null,
                    'descricao' => $data['descricao'],
                    'unidade' => $data['unidade'] ?? 'un',
                    'marca' => $data['marca'] ?? null,
                    'localizacao' => $data['localizacao'] ?? null,
                    'estoque_minimo' => $data['estoque_minimo'] ?? 0,
                    'preco_venda' => $data['preco_venda'] ?? 0,
                    'estoque_inicial' => $data['estoque_inicial'] ?? 0,
                ], $userId);
                $importados++;
            } catch (\Throwable $e) {
                $erros[] = ['linha' => $linha, 'mensagem' => $e->getMessage()];
            }
        }

        fclose($handle);
        return ['importados' => $importados, 'erros' => $erros];
    }

    /** @param array<string, string> $data */
    private static function validarLinha(array $data): void
    {
        if (empty($data['codigo_interno']) || empty($data['descricao'])) {
            throw new \InvalidArgumentException('codigo_interno e descricao são obrigatórios.');
        }
        $unidade = $data['unidade'] ?? 'un';
        if (!in_array($unidade, array_column(UnidadePeca::cases(), 'value'), true)) {
            throw new \InvalidArgumentException("Unidade inválida: {$unidade}");
        }
    }
}
