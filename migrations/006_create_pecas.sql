CREATE TABLE IF NOT EXISTS pecas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_interno VARCHAR(40) NOT NULL,
    codigo_oem VARCHAR(60) NULL,
    descricao VARCHAR(255) NOT NULL,
    unidade ENUM('un', 'lt', 'kg', 'm') NOT NULL DEFAULT 'un',
    categoria_id INT UNSIGNED NULL,
    marca VARCHAR(80) NULL,
    localizacao VARCHAR(80) NULL COMMENT 'Ex: prateleira A3',
    estoque_minimo DECIMAL(12,3) NOT NULL DEFAULT 0,
    preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    UNIQUE KEY uk_pecas_codigo_interno (codigo_interno),
    KEY idx_pecas_descricao (descricao),
    KEY idx_pecas_oem (codigo_oem),
    KEY idx_pecas_deleted (deleted_at),
    CONSTRAINT fk_pecas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_pecas (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pecas_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS peca_fornecedor (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    peca_id INT UNSIGNED NOT NULL,
    fornecedor_id INT UNSIGNED NOT NULL,
    preco_compra DECIMAL(10,2) NOT NULL,
    prazo_entrega_dias SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    preferencial TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_peca_fornecedor (peca_id, fornecedor_id),
    CONSTRAINT fk_pf_peca FOREIGN KEY (peca_id) REFERENCES pecas (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pf_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
