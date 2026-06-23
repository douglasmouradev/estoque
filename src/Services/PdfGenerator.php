<?php

declare(strict_types=1);

namespace App\Services;

/** Geração de PDF com Dompdf (UTF-8) e fallback minimalista. */
final class PdfGenerator
{
    private string $buffer = '';
    private int $y = 780;

    /** @param array<string, mixed> $orcamento */
    /** @param list<array<string, mixed>> $itens */
    /** @param array<string, mixed> $totais */
    /** @param array<string, string> $oficina */
    public static function orcamento(array $orcamento, array $itens, array $totais, array $oficina, string $destino): void
    {
        if (self::useDompdf()) {
            self::renderHtmlToPdf(self::htmlOrcamento($orcamento, $itens, $totais, $oficina), $destino);
            return;
        }
        $pdf = new self();
        $pdf->text($oficina['nome'] ?: 'Oficina Mecânica', 14);
        if ($oficina['cnpj'] ?? '') {
            $pdf->text('CNPJ: ' . $oficina['cnpj']);
        }
        $pdf->text('ORCAMENTO #' . $orcamento['numero'] . '  v' . $orcamento['versao'], 13);
        $pdf->text('Cliente: ' . ($orcamento['cliente_nome'] ?? ''));
        $pdf->text('Veiculo: ' . ($orcamento['placa'] ?? '') . ' - ' . ($orcamento['marca'] ?? '') . ' ' . ($orcamento['modelo'] ?? ''));
        $pdf->text('----------------------------------------');
        foreach ($itens as $item) {
            $pdf->text(sprintf(
                '%s  |  Qtd: %s  |  R$ %s',
                mb_substr((string) $item['descricao'], 0, 40),
                $item['quantidade'],
                number_format((float) $item['preco_unitario'], 2, ',', '.')
            ));
        }
        $pdf->text('TOTAL: R$ ' . number_format((float) ($totais['total'] ?? 0), 2, ',', '.'), 13);
        $pdf->outputFile($destino);
    }

    /** @param array<string, mixed> $os */
    /** @param array<string, string> $oficina */
    public static function ordemServico(array $os, array $itens, array $oficina, string $destino): void
    {
        if (self::useDompdf()) {
            self::renderHtmlToPdf(self::htmlOs($os, $itens, $oficina), $destino);
            return;
        }
        $pdf = new self();
        $pdf->text($oficina['nome'] ?: 'Oficina Mecânica', 14);
        $pdf->text('ORDEM DE SERVICO #' . $os['numero'], 13);
        $pdf->text('Cliente: ' . ($os['cliente_nome'] ?? ''));
        $pdf->text('Placa: ' . ($os['placa'] ?? ''));
        foreach ($itens as $item) {
            $ok = !empty($item['concluido']) ? '[X]' : '[ ]';
            $pdf->text("{$ok} {$item['descricao']} x{$item['quantidade']}");
        }
        $pdf->outputFile($destino);
    }

    private static function useDompdf(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    private static function renderHtmlToPdf(string $html, string $destino): void
    {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dir = dirname($destino);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($destino, $dompdf->output());
    }

    /** @param array<string, mixed> $orcamento */
    /** @param list<array<string, mixed>> $itens */
    /** @param array<string, mixed> $totais */
    /** @param array<string, string> $oficina */
    private static function htmlOrcamento(array $orcamento, array $itens, array $totais, array $oficina): string
    {
        $rows = '';
        foreach ($itens as $item) {
            $rows .= '<tr><td>' . self::e((string) $item['descricao']) . '</td><td>' . self::e((string) $item['quantidade'])
                . '</td><td>R$ ' . number_format((float) $item['preco_unitario'], 2, ',', '.') . '</td></tr>';
        }
        $nome = self::e($oficina['nome'] ?: 'Oficina Mecânica');
        return self::htmlWrap(
            "<h1>{$nome}</h1>"
            . self::htmlOficina($oficina)
            . '<h2>Orçamento #' . (int) $orcamento['numero'] . ' (v' . (int) $orcamento['versao'] . ')</h2>'
            . '<p><strong>Cliente:</strong> ' . self::e((string) ($orcamento['cliente_nome'] ?? '')) . '</p>'
            . '<p><strong>Veículo:</strong> ' . self::e((string) ($orcamento['placa'] ?? '')) . ' — '
            . self::e(trim(($orcamento['marca'] ?? '') . ' ' . ($orcamento['modelo'] ?? ''))) . '</p>'
            . '<p><strong>Data:</strong> ' . date('d/m/Y') . '</p>'
            . '<table><thead><tr><th>Descrição</th><th>Qtd</th><th>Preço</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p class="total">Subtotal: R$ ' . number_format((float) ($totais['subtotal'] ?? 0), 2, ',', '.') . '</p>'
            . '<p>Desconto: R$ ' . number_format((float) ($totais['desconto_geral'] ?? 0), 2, ',', '.') . '</p>'
            . '<p class="total">TOTAL: R$ ' . number_format((float) ($totais['total'] ?? 0), 2, ',', '.') . '</p>'
        );
    }

    /** @param array<string, mixed> $os */
    /** @param list<array<string, mixed>> $itens */
    /** @param array<string, string> $oficina */
    private static function htmlOs(array $os, array $itens, array $oficina): string
    {
        $rows = '';
        foreach ($itens as $item) {
            $chk = !empty($item['concluido']) ? '☑' : '☐';
            $rows .= '<tr><td>' . $chk . '</td><td>' . self::e((string) $item['descricao'])
                . '</td><td>' . self::e((string) $item['quantidade']) . '</td></tr>';
        }
        $nome = self::e($oficina['nome'] ?: 'Oficina Mecânica');
        return self::htmlWrap(
            "<h1>{$nome}</h1>"
            . self::htmlOficina($oficina)
            . '<h2>Ordem de Serviço #' . (int) $os['numero'] . '</h2>'
            . '<p><strong>Cliente:</strong> ' . self::e((string) ($os['cliente_nome'] ?? '')) . '</p>'
            . '<p><strong>Placa:</strong> ' . self::e((string) ($os['placa'] ?? '')) . '</p>'
            . '<p><strong>Status:</strong> ' . self::e((string) ($os['status'] ?? '')) . '</p>'
            . '<table><thead><tr><th></th><th>Item</th><th>Qtd</th></tr></thead><tbody>' . $rows . '</tbody></table>'
        );
    }

    /** @param array<string, string> $oficina */
    private static function htmlOficina(array $oficina): string
    {
        $out = '';
        if ($oficina['cnpj'] ?? '') {
            $out .= '<p>CNPJ: ' . self::e($oficina['cnpj']) . '</p>';
        }
        if ($oficina['telefone'] ?? '') {
            $out .= '<p>Tel: ' . self::e($oficina['telefone']) . '</p>';
        }
        if ($oficina['endereco'] ?? '') {
            $out .= '<p>' . self::e($oficina['endereco']) . '</p>';
        }
        return $out;
    }

    private static function htmlWrap(string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#222;margin:40px}
            h1{font-size:18px;margin:0 0 8px}h2{font-size:14px;margin:16px 0 8px}
            table{width:100%;border-collapse:collapse;margin:12px 0}
            th,td{border:1px solid #ccc;padding:6px;text-align:left}
            th{background:#f5f5f5}.total{font-size:14px;font-weight:bold}
        </style></head><body>' . $body . '</body></html>';
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function text(string $line, int $size = 12): void
    {
        $this->y -= ($size + 6);
        if ($this->y < 60) {
            $this->y = 780;
        }
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::pdfSafe($line));
        $this->buffer .= "({$safe}) Tj\n0 -" . ($size + 4) . " Td\n";
    }

    private static function pdfSafe(string $text): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false && $converted !== '') {
            return $converted;
        }
        return (string) preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    private function outputFile(string $path): void
    {
        $header = "BT /F1 11 Tf 50 780 Td\n";
        $stream = $header . $this->buffer . "ET";
        $len = strlen($stream);
        $pdf = "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>>>>>>>endobj\n"
            . "4 0 obj<</Length {$len}>>stream\n{$stream}\nendstream endobj\n"
            . "trailer<</Size 5/Root 1 0 R>>\nstartxref\n0\n%%EOF";
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $pdf);
    }
}
