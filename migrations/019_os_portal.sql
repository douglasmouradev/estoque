ALTER TABLE ordens_servico
    ADD COLUMN token_acesso VARCHAR(64) NULL AFTER status_pagamento,
    ADD COLUMN token_expira_em DATETIME NULL AFTER token_acesso,
    ADD UNIQUE INDEX idx_os_token (token_acesso);
