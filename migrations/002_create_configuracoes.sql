-- Configurações chave-valor (ex.: dias para relatório de peças paradas)
CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(80) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL,
    descricao VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED NULL,
    CONSTRAINT fk_config_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracoes (chave, valor, descricao) VALUES
    ('pecas_paradas_dias', '90', 'Dias sem movimentação para considerar peça parada')
ON DUPLICATE KEY UPDATE valor = valor;
