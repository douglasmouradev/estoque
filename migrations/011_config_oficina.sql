INSERT INTO configuracoes (chave, valor, descricao) VALUES
    ('oficina_nome', 'Oficina Mecânica', 'Nome exibido em PDFs e cabeçalho'),
    ('oficina_cnpj', '', 'CNPJ da oficina'),
    ('oficina_telefone', '', 'Telefone de contato'),
    ('oficina_email', '', 'E-mail de contato'),
    ('oficina_endereco', '', 'Endereço completo')
ON DUPLICATE KEY UPDATE valor = valor;
