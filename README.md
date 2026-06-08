# ⚛️ Atomicamente — Plataforma de Ensino de Química para o ENEM

> **Projeto de Extensão Académica** desenvolvido para aproximar os conceitos da ciência química dos estudantes do Ensino Médio através de uma abordagem visual, organizada e interativa.

**Status:** ✅ Pronto para produção | **Versão:** 1.0.0 | **Última atualização:** Junho 2026

---

## 🚀 Sobre o Projeto

O **Atomicamente** combina uma interface institucional atrativa com um ecossistema de aprendizagem robusto inspirado no modelo da **Khan Academy**. O objetivo é transformar a jornada de preparação para o ENEM em uma experiência visual, gamificada e baseada em dados.

Desenvolvido como **Projeto Integrador** entre alunos de **Química** e **Informática** do IFSULDEMINAS - Campus Pouso Alegre.

---

## ✨ Funcionalidades Principais

### 🌐 Portal Institucional & Prática
* **Simulador de pH Integrado:** Módulo interativo que simula em tempo real o comportamento ácido/básico de soluções com base na escala de hidrónio e hidróxido.
* **Design Responsivo:** Identidade visual moderna com tema claro/escuro, baseada em tons de roxo profundo, priorizando acessibilidade e legibilidade.
* **Mini Desafio ENEM:** Questão aleatória do banco integrada na página inicial para engajamento imediato.

### 📊 Área da Aluna (Dashboard Analítico)
* **Gráficos de Proficiência:** Integração com `Chart.js` para exibir o desempenho em tempo real por Grande Área da Química.
* **Motor de Recomendação Inteligente:** Sistema reativo que detecta pontos fracos (ex: acertos abaixo de 60%) e sugere revisões imediatas.
* **Árvore do Conhecimento:** Visualização de tópicos em barras de progresso percentual (estilo Khan Academy).
* **Sala de Aula Isolada:** Workspace dividido em abas assíncronas para **Texto Base**, **Videoaulas** e **Banco de Questões**.

### 🎛️ Painel Administrativo (Professores)
* **Gestão de Utilizadores:** Controlo de permissões e listagem académica com whitelist de emails.
* **Criador de Aulas Multimédia:** Interface para publicação de conteúdos teóricos, incorporação de vídeos do YouTube e listagem de fontes científicas.
* **Banco de Fixação:** Formulário estruturado para cadastrar questões com mapeamento de alternativas certas e erradas.

### 🔐 Segurança & Produção
* **Variáveis de Ambiente:** Configurações sensíveis isoladas em `.env` (não versionadas).
* **Proteção Apache:** Arquivo `.htaccess` com bloqueio de acesso a arquivos sensíveis.
* **Headers de Segurança:** Proteção contra XSS, clickjacking e MIME sniffing.
* **PDO Seguro:** Prepared statements contra SQL injection.
* **Cache Otimizado:** Compressão GZIP e cache de recursos estáticos.

---

## 🛠️ Tecnologias Utilizadas

| Camada | Tecnologia |
|--------|-----------|
| **Backend** | PHP 8.x (Estruturação modular com PDO) |
| **Base de Dados** | MySQL 8.0+ / MariaDB 10.5+ |
| **Frontend** | HTML5, CSS3 (Custom Properties), JavaScript ES6 |
| **Gráficos** | Chart.js (via CDN) |
| **Servidor** | Apache 2.4+ com mod_rewrite |
| **Segurança** | SSL/TLS, Headers HTTP, Environment variables |

---

## 📦 Instalação e Execução

### Pré-requisitos
* **Servidor local:** VertrigoServ, XAMPP, WampServer ou similar (PHP 8.0+, MySQL 8.0+)
* **Git** configurado na máquina
* **Composer** (opcional, para futuras dependências)

### Instalação Local (Desenvolvimento)

#### 1. Clonar o Repositório
```bash
git clone https://github.com/ghsnts/atomicamente.git
cd atomicamente
```

#### 2. Configurar Variáveis de Ambiente
```bash
# Criar arquivo .env a partir do exemplo
cp .env.example .env

# Editar .env com suas credenciais locais
nano .env
# ou abrir com editor de sua preferência
```

**Conteúdo do `.env` para desenvolvimento:**
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

#### 3. Preparar Base de Dados

**Via phpMyAdmin:**
1. Acesse `http://localhost/phpmyadmin/`
2. Crie novo banco: `atomicamente_db`
3. Vá em **Importar** e selecione `database/schema.sql`
4. Clique em **Executar**

**Via Terminal:**
```bash
mysql -u root -p
CREATE DATABASE atomicamente_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atomicamente_db;
source database/schema.sql;
```

#### 4. Acessar a Plataforma
```
http://localhost/atomicamente/index.php
```

---

## 🚀 Deploy em Produção

### Hostinger (ou similar)

Para um guia **completo e passo-a-passo** de deployment no Hostinger, consulte:

📖 **[DEPLOYMENT.md](./DEPLOYMENT.md)**

**Resumo rápido:**
1. Criar banco de dados em produção
2. Clonar repositório ou fazer upload via FTP
3. Criar arquivo `.env` com credenciais de produção
4. Importar `database/schema.sql`
5. Ativar SSL automático
6. Acessar `https://seu_dominio.com`

---

## 📋 Estrutura do Projeto

```
atomicamente/
├── assets/                 # Imagens, ícones, favicons
├── css/                   # Estilos CSS
├── js/                    # Scripts JavaScript
├── database/
│   └── schema.sql        # Estrutura do banco de dados
├── config.php            # ⚠️ Gitignored - use .env
├── .env.example          # Modelo de variáveis (versionado)
├── .env                  # ⚠️ Gitignored - credenciais reais
├── .gitignore           # Protege arquivos sensíveis
├── .htaccess            # Segurança Apache
├── index.php            # Página inicial
├── login.php            # Autenticação
├── dashboard.php        # Painel da aluna
├── admin.php            # Painel administrativo
├── prova.php            # Sistema de provas
├── README.md            # Este arquivo
└── DEPLOYMENT.md        # Guia de deployment
```

---

## 🔐 Segurança

### Checklist de Segurança ✅
- [x] Credenciais em variáveis de ambiente (`.env`)
- [x] Arquivo `.env` não versionado (`.gitignore`)
- [x] Proteção de arquivos sensíveis (`.htaccess`)
- [x] Headers HTTP de segurança
- [x] Prepared statements (PDO)
- [x] HTTPS readiness
- [x] Cache seguro
- [x] Modo desenvolvimento/produção

### Práticas Implementadas
```php
// ✅ Seguro - Usar variáveis de ambiente
$db_user = getenv('DB_USER');

// ❌ Inseguro - Credenciais hardcoded
$db_user = 'root';
```

---

## 🤝 Contribuindo

Este é um projeto académico desenvolvido por:

### Equipa de Desenvolvimento
* **Gustavo Santos** (Backend/DevOps) — [@ghsnts](https://github.com/ghsnts)
* **Alunas de Química do IFSULDEMINAS** — Conteúdo e Conceitos

### Como Contribuir
1. Faça fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📄 Licença

Este projeto é **código aberto** para fins educacionais. 

**Uso permitido:**
- ✅ Fins educacionais
- ✅ Modificações pessoais
- ✅ Distribuição em repositórios da instituição

**Uso não permitido sem autorização:**
- ❌ Uso comercial
- ❌ Hospedagem com fins lucrativos sem menção de créditos

---

## 📞 Suporte

- **Issues:** [GitHub Issues](https://github.com/ghsnts/atomicamente/issues)
- **Discussões:** [GitHub Discussions](https://github.com/ghsnts/atomicamente/discussions)
- **Email:** gustavo4.santos@alunos.ifsuldeminas.edu.br

---

## 📊 Status do Projeto

| Componente | Status | Notas |
|-----------|--------|-------|
| Portal | ✅ Completo | Interface responsiva pronta |
| Dashboard | ✅ Completo | Gráficos e analytics implementados |
| Admin Panel | ✅ Completo | Gestão de conteúdo funcional |
| Segurança | ✅ Otimizado | Env vars, SSL, headers HTTP |
| Testes | 🔄 Em progresso | Unit tests a adicionar |
| Deploy | ✅ Documentado | Guia Hostinger pronto |

---

## 🎓 Créditos Institucionais

**Instituto Federal de Educação, Ciência e Tecnologia do Sul de Minas Gerais**
- Campus: Pouso Alegre
- Cursos: Informática e Química
- Ano: 2026

---

**Construído com ❤️ por alunos para alunos.**
