ALTER TABLE tiki_auth_tokens ADD createUser CHAR(1) DEFAULT 'n';
ALTER TABLE tiki_auth_tokens ADD userPrefix VARCHAR(200) DEFAULT '_token';
