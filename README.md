# Helpdesk API

> API REST para gerenciamento de chamados de suporte técnico, desenvolvida em **PHP puro** com autenticação **JWT** e banco de dados **MySQL**.

Este projeto simula o backend de um sistema de helpdesk, onde clientes abrem chamados de suporte, agentes respondem e acompanham o andamento, e admins gerenciam tudo. Foi desenvolvido para demonstrar domínio de **APIs REST**, **SQL com JOINs**, **autenticação com token** e **boas práticas de organização de código**.

---

## Tecnologias utilizadas

- **PHP 8+** (sem frameworks — PDO puro para acesso ao banco)
- **MySQL** — banco relacional com 4 tabelas e relacionamentos
- **JWT** — autenticação stateless via token (implementado do zero, sem bibliotecas)
- **Arquitetura MVC** — separação entre Models, Controllers e roteamento

---

## Funcionalidades

- Login com geração de token JWT (expira em 1 hora)
- CRUD completo de chamados (abrir, listar, detalhar, atualizar, encerrar)
- Filtros por status e categoria via query string
- Comentários em chamados (troca de mensagens entre cliente e agente)
- Relatório de chamados agrupados por categoria e status
- Controle de permissões por papel: `cliente`, `agente` e `admin`
- Validação de inputs e status codes HTTP corretos em todas as rotas

---

## Estrutura do projeto

```
helpdesk-api/
├── config/
│   └── database.php         # Conexão PDO com o banco (Singleton)
├── database/
│   └── schema.sql           # Criação das tabelas + dados de teste
├── src/
│   ├── Helpers/
│   │   ├── JWT.php          # Geração e validação de token JWT
│   │   └── Response.php     # Padronização de respostas JSON
│   ├── Middleware/
│   │   └── AuthMiddleware.php  # Autenticação e autorização por papel
│   ├── Models/
│   │   ├── Chamado.php      # Queries SQL de chamados (com JOINs)
│   │   ├── Comentario.php   # Queries de comentários
│   │   └── Usuario.php      # Queries de usuários
│   └── Controllers/
│       ├── AuthController.php     # Login
│       └── ChamadoController.php  # Lógica de chamados e relatórios
├── public/
│   └── index.php            # Roteador (único ponto de entrada da API)
├── .env.example             # Modelo de configuração de ambiente
└── ESTRUTURA.md             # Explicação detalhada de cada arquivo
```

> Veja [`ESTRUTURA.md`](./ESTRUTURA.md) para entender o que cada arquivo faz e o fluxo completo de uma requisição.

---

## Como executar localmente

**Pré-requisitos:** PHP >= 8.0 e MySQL (recomendo o [XAMPP](https://www.apachefriends.org/) no Windows)

**1. Clone o repositório**
```bash
git clone https://github.com/seu-usuario/helpdesk-api.git
cd helpdesk-api
```

**2. Crie o banco de dados**

Via terminal:
```bash
mysql -u root -p < database/schema.sql
```
Ou importe o arquivo `database/schema.sql` pelo phpMyAdmin (`http://localhost/phpmyadmin`).

**3. Configure o ambiente**
```bash
cp .env.example .env
```
Edite o `.env` com suas credenciais do MySQL.

**4. Suba o servidor**
```bash
php -S localhost:8000 -t public
```

A API estará disponível em `http://localhost:8000`. Teste abrindo no navegador:
```
http://localhost:8000/chamados
```

---

## Autenticação

As rotas protegidas exigem um token JWT no header:
```
Authorization: Bearer <token>
```

Para obter o token, faça login:

**POST** `/auth/login`
```json
{
  "email": "maria@cliente.com",
  "senha": "123456"
}
```

### Usuários de teste

| Email | Senha | Papel |
|---|---|---|
| admin@empresa.com | 123456 | admin |
| joao@empresa.com | 123456 | agente |
| maria@cliente.com | 123456 | cliente |

---

## Endpoints

### Autenticação
| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST | `/auth/login` | Não | Gera token JWT |

### Chamados
| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| GET | `/chamados` | Não | Lista chamados (`?status=aberto&categoria_id=2`) |
| GET | `/chamados/{id}` | Não | Detalhe com comentários |
| POST | `/chamados` | Sim — Qualquer | Abre novo chamado |
| PUT | `/chamados/{id}` | Sim — Agente/Admin | Atualiza status ou responsável |
| DELETE | `/chamados/{id}` | Sim — Agente/Admin | Encerra chamado (soft delete) |
| POST | `/chamados/{id}/comentarios` | Sim — Qualquer | Adiciona comentário |
| GET | `/relatorios/por-categoria` | Não | Relatório agrupado por categoria |

### Exemplo: abrir chamado

```http
POST /chamados
Authorization: Bearer <token>
Content-Type: application/json

{
  "titulo": "Impressora não imprime",
  "descricao": "A impressora do setor financeiro está sem resposta.",
  "categoria_id": 1
}
```

Resposta (`201 Created`):
```json
{
  "id": 3,
  "mensagem": "Chamado criado com sucesso"
}
```

---

## Status codes HTTP utilizados

| Código | Quando ocorre |
|--------|---------------|
| `200` | Sucesso em GET e PUT |
| `201` | Recurso criado com sucesso (POST) |
| `401` | Token ausente, inválido ou expirado |
| `403` | Autenticado, mas sem permissão para a ação |
| `404` | Chamado não encontrado |
| `422` | Dados inválidos ou campos obrigatórios faltando |
| `500` | Erro interno do servidor |