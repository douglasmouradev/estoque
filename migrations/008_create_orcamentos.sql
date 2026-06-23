CREATE TABLE IF NOT EXISTS orcamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero INT UNSIGNED NOT NULL,
    versao SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    cliente_id INT UNSIGNED NOT NULL,
    veiculo_id INT UNSIGNED NOT NULL,
    status ENUM('rascunho', 'enviado', 'aprovado', 'reprovado', 'convertido') NOT NULL DEFAULT 'rascunho',
    desconto_geral_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    desconto_geral_valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacao_interna TEXT NULL,
    observacao_cliente TEXT NULL,
    aprovado_em DATETIME NULL,
    reprovado_em DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    UNIQUE KEY uk_orcamento_numero_versao (numero, versao),
    KEY idx_orcamentos_status (status),
    KEY idx_orcamentos_cliente (cliente_id),
    CONSTRAINT fk_orc_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orc_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orc_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orcamento_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orcamento_id INT UNSIGNED NOT NULL,
    tipo ENUM('peca', 'servico') NOT NULL,
    peca_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    desconto_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    desconto_valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_oi_orcamento (orcamento_id),
    CONSTRAINT fk_oi_orcamento FOREIGN KEY (orcamento_id) REFERENCES orcamentos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_oi_peca FOREIGN KEY (peca_id) REFERENCES pecas (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orcamento_versoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orcamento_id INT UNSIGNED NOT NULL,
    versao_anterior SMALLINT UNSIGNED NOT NULL,
    snapshot JSON NOT NULL COMMENT 'Cópia do orçamento + itens antes da edição',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    KEY idx_ov_orcamento (orcamento_id),
    CONSTRAINT fk_ov_orcamento FOREIGN KEY (orcamento_id) REFERENCES orcamentos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_ov_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
