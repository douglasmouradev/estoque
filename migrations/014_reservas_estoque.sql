CREATE TABLE IF NOT EXISTS estoque_reservas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    peca_id INT UNSIGNED NOT NULL,
    orcamento_id INT UNSIGNED NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL,
    status ENUM('ativa','consumida','liberada') NOT NULL DEFAULT 'ativa',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_peca FOREIGN KEY (peca_id) REFERENCES pecas (id) ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_orc FOREIGN KEY (orcamento_id) REFERENCES orcamentos (id) ON DELETE RESTRICT,
    KEY idx_reserva_status (status, peca_id)
) ENGINE=InnoDB;
