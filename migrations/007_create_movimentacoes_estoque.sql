CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    peca_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL,
    motivo ENUM('compra', 'devolucao', 'uso_os', 'ajuste', 'cancelamento_os') NOT NULL,
    ordem_servico_id INT UNSIGNED NULL,
    observacao VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    KEY idx_mov_peca_data (peca_id, created_at),
    KEY idx_mov_os (ordem_servico_id),
    CONSTRAINT fk_mov_peca FOREIGN KEY (peca_id) REFERENCES pecas (id) ON DELETE RESTRICT,
    CONSTRAINT fk_mov_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
