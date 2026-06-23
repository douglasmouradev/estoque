CREATE TABLE os_pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT UNSIGNED NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','outro') NOT NULL DEFAULT 'dinheiro',
    observacao VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_os_pag (ordem_servico_id),
    CONSTRAINT fk_pag_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
