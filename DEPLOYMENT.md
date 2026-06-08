# 🚀 Guia de Deploy - Atomicamente no Hostinger

## Etapa 1: Preparação Antes do Deploy

### 1.1 Remover Credenciais do Repositório
✅ **Já foi feito automaticamente!**
- Arquivo `.env` foi adicionado ao `.gitignore`
- `config.php` agora usa variáveis de ambiente
- Arquivo `.env.example` criado como referência

### 1.2 Clonar o Repositório Localmente
```bash
git clone https://github.com/ghsnts/atomicamente.git
cd atomicamente
```

### 1.3 Criar Arquivo `.env` Local (NÃO commitar!)
```bash
cp .env.example .env
```

Edite `.env` com suas credenciais de **desenvolvimento**:
```
DB_HOST=localhost
DB_NAME=atomicamente_db
DB_USER=root
DB_PASS=sua_senha_local
DB_CHARSET=utf8mb4
APP_ENV=development
FORCE_HTTPS=false
ADMIN_EMAILS=seu_email@local.com
```

---

## Etapa 2: Preparar Banco de Dados

### 2.1 Criar Script SQL de Inicialização
Se você ainda não tem o arquivo `database/schema.sql`, crie-o com a estrutura do banco:

```sql
-- database/schema.sql
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('aluno', 'professor') DEFAULT 'aluno',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enunciado TEXT NOT NULL,
    topico_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS alternatives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    letra VARCHAR(1) NOT NULL,
    texto_alternativa TEXT NOT NULL,
    eh_correta TINYINT(1) DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Adicione as outras tabelas necessárias conforme seu aplicativo...
```

---

## Etapa 3: Deploy no Hostinger

### 3.1 Acessar hPanel (Painel do Hostinger)
1. Acesse: https://hpanel.hostinger.com
2. Faça login com suas credenciais

### 3.2 Criar Banco de Dados em Produção

**Opção A: Via hPanel (Recomendado)**
1. Vá em **Banco de Dados**
2. Clique em **Criar Novo Banco de Dados**
3. Nome: `atomicamente_db` (ou seu domínio sem pontos)
4. Crie um usuário específico (NÃO use root em produção!)
   - Usuário: `seu_user_prod`
   - Senha: `gerar_senha_super_segura`
5. Anote host, user e password

**Opção B: Via SSH (Avançado)**
```bash
mysql -u seu_user_prod -p
CREATE DATABASE atomicamente_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3.3 Importar Banco de Dados

**Via phpMyAdmin (Hostinger):**
1. Acesse phpMyAdmin no painel
2. Selecione seu banco `atomicamente_db`
3. Vá em **Importar**
4. Faça upload do arquivo `database/schema.sql`
5. Clique em **Executar**

**Via SSH (Terminal):**
```bash
ssh seu_usuario@seu_server.hostinger.com
cd ~/public_html
mysql -h localhost -u seu_user_prod -p atomicamente_db < database/schema.sql
```

### 3.4 Fazer Upload dos Arquivos

**Opção A: Git Clone (Recomendado)**
```bash
# SSH no Hostinger
ssh seu_usuario@seu_server.hostinger.com

# Entrar no diretório público
cd ~/public_html

# Clonar o repositório
git clone https://github.com/ghsnts/atomicamente.git .
```

**Opção B: FTP (FileZilla)**
1. Baixe FileZilla: https://filezilla-project.org
2. Use credenciais FTP do Hostinger
3. Carregue todos os arquivos para `public_html`
4. **NÃO carregue pasta `.git` ou arquivo `.env`**

### 3.5 Criar Arquivo `.env` em Produção

**Via SSH:**
```bash
cd ~/public_html
nano .env
```

Preencha com dados de PRODUÇÃO:
```
DB_HOST=localhost
DB_NAME=atomicamente_db
DB_USER=seu_user_prod
DB_PASS=sua_senha_super_segura
DB_CHARSET=utf8mb4
APP_ENV=production
FORCE_HTTPS=true
ADMIN_EMAILS=seu_email_admin@dominio.com
```

Salve com `Ctrl+X` → `Y` → `Enter`

### 3.6 Verificar Permissões de Arquivos

```bash
# Pasta public_html deve ser 755
chmod 755 ~/public_html

# Arquivos PHP devem ser 644
find ~/public_html -type f -name "*.php" -exec chmod 644 {} \;

# Arquivo .env deve ser 600 (apenas leitura do proprietário)
chmod 600 ~/public_html/.env
```

---

## Etapa 4: Configurar SSL/HTTPS (Hostinger)

1. No **hPanel**, vá em **SSL/TLS**
2. Hostinger oferece SSL **grátis** com AutoSSL
3. Ative o AutoSSL
4. Aguarde 5-30 minutos para ativar

Depois, descomente no `.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

---

## Etapa 5: Teste Final

### 5.1 Acessar o Site
- Abra: `https://seu_dominio.com`
- Deve aparecer a página inicial do Atomicamente

### 5.2 Testar Funcionalidades
1. ✅ Página inicial carrega
2. ✅ Botão "Cadastre-se" funciona
3. ✅ Banco de dados conecta (tente fazer login)
4. ✅ Dashboard carrega (se autenticado)
5. ✅ Simulador de pH funciona
6. ✅ Painél admin carregável

### 5.3 Checklist de Segurança
- ❌ Arquivo `.env` não visível publicamente
- ❌ Arquivo `config.php` não visível
- ❌ Pasta `database/` não acessível
- ✅ HTTPS está ativo
- ✅ Headers de segurança presentes

---

## Etapa 6: Monitoramento e Manutenção

### 6.1 Verificar Logs de Erro
```bash
# Via SSH
tail -f ~/public_html/error_log
```

### 6.2 Backup Automático

**Via SSH (cron job):**
```bash
# Editar crontab
crontab -e

# Adicionar backup diário às 2 AM
0 2 * * * mysqldump -u seu_user -p'sua_senha' atomicamente_db | gzip > ~/backups/db_$(date +\%Y\%m\%d).sql.gz
```

### 6.3 Atualizar código em produção
```bash
cd ~/public_html
git pull origin main
```

---

## 🆘 Solução de Problemas

### "Erro crítico de ligação"
- Verifica credenciais em `.env`
- Confirma que banco foi criado
- Tenta acessar phpMyAdmin manualmente

### "Arquivo não encontrado (404)"
- Verifica permissões: `chmod 755 ~/public_html`
- Confirma que `index.php` existe
- Tenta acessar `https://seu_dominio.com/index.php`

### "Permission Denied"
```bash
chmod 755 ~/public_html
find ~/public_html -type f -name "*.php" -exec chmod 644 {} \;
chmod 600 ~/public_html/.env
```

### "HTTPS não funciona"
- Espera até 30 minutos para AutoSSL ativar
- Limpa cache do navegador
- Tenta em modo anônimo

---

## 📞 Suporte Hostinger

- **Chat ao vivo**: Ativo 24/7 no painel
- **Base de conhecimento**: https://support.hostinger.com/pt_BR
- **Email**: support@hostinger.com

---

**Parabéns! Seu site está no ar! 🎉**

Para dúvidas, consulte os logs ou contate o suporte do Hostinger.
