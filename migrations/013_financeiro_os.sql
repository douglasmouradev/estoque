ALTER TABLE ordens_servico
    ADD COLUMN valor_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER observacoes,
    ADD COLUMN valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER valor_total,
    ADD COLUMN status_pagamento ENUM('pendente','parcial','pago') NOT NULL DEFAULT 'pendente' AFTER valor_pago;
