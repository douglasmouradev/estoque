-- Senha padrão: admin123 (alterar no primeiro login em produção)
INSERT INTO users (nome, email, password_hash, perfil, ativo, created_by)
SELECT 'Administrador', 'admin@oficina.local',
    '$2y$12$hRi7tcZ3wbzcUvg7vwl8L.clGRA2EQ7kDcTFgmY4lStQ9FDaU8UP6',
    'admin', 1, NULL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@oficina.local');
