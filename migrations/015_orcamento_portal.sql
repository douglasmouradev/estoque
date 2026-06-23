ALTER TABLE orcamentos
    ADD COLUMN token_acesso VARCHAR(64) NULL UNIQUE AFTER observacao_cliente,
    ADD COLUMN token_expira_em DATETIME NULL AFTER token_acesso;
