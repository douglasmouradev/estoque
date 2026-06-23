-- FK de movimentacoes para OS adicionada após criar ordens_servico
CREATE TABLE IF NOT EXISTS ordens_servico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero INT UNSIGNED NOT NULL,
    orcamento_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NOT NULL,
    veiculo_id INT UNSIGNED NOT NULL,
    status ENUM('aberta', 'em_andamento', 'aguardando_peca', 'finalizada', 'cancelada') NOT NULL DEFAULT 'aberta',
    observacoes TEXT NULL,
    finalizada_em DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    UNIQUE KEY uk_os_numero (numero),
    KEY idx_os_status (status),
    KEY idx_os_orcamento (orcamento_id),
    CONSTRAINT fk_os_orcamento FOREIGN KEY (orcamento_id) REFERENCES orcamentos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_os_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_os_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_os_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE movimentacoes_estoque
    ADD CONSTRAINT fk_mov_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico (id) ON DELETE RESTRICT;

CREATE TABLE IF NOT EXISTS os_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT UNSIGNED NOT NULL,
    orcamento_item_id INT UNSIGNED NULL,
    tipo ENUM('peca', 'servico') NOT NULL,
    peca_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    concluido TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_os_itens_os (ordem_servico_id),
    CONSTRAINT fk_osi_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico (id) ON DELETE RESTRICT,
    CONSTRAINT fk_osi_peca FOREIGN KEY (peca_id) REFERENCES pecas (id) ON DELETE RESTRICT,
    CONSTRAINT fk_osi_oi FOREIGN KEY (orcamento_item_id) REFERENCES orcamento_itens (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_horas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT UNSIGNED NOT NULL,
    mecanico_id INT UNSIGNED NOT NULL,
    data_trabalho DATE NOT NULL,
    horas DECIMAL(5,2) NOT NULL,
    descricao VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    KEY idx_os_horas_os (ordem_servico_id),
    CONSTRAINT fk_osh_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico (id) ON DELETE RESTRICT,
    CONSTRAINT fk_osh_mecanico FOREIGN KEY (mecanico_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_osh_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_checklist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT UNSIGNED NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    concluido TINYINT(1) NOT NULL DEFAULT 0,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_os_checklist_os (ordem_servico_id),
    CONSTRAINT fk_osc_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
