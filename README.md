Helpdesk API

API REST simples para gerenciamento de chamados de suporte técnico (estilo helpdesk), desenvolvida em **PHP puro** (sem frameworks), usando **PDO** para acesso ao banco **MySQL** e autenticação via **JWT**.

## Funcionalidades

- Login com geração de token JWT
- CRUD de chamados (abertura, listagem, detalhe, atualização, encerramento)
- Filtros por status e categoria
- Comentários em chamados
- Relatório de chamados agrupados por categoria/status
- Controle de permissões por tipo de usuário (`cliente`, `agente`, `admin`)

## Requisitos

- PHP >= 8.0 com extensão `pdo_mysql`
- MySQL ou MariaDB
- (Opcional) Postman/Insomnia para testar os endpoints

## Como executar

1. Clone o repositório e entre na pasta:
   ```bash
   git clone <url-do-repo>
   cd helpdesk-api
   ```

2. Crie o banco de dados executando o script SQL:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. Copie o arquivo de configuração de ambiente:
   ```bash
   cp .env.example .env
   ```
   Edite o `.env` com as credenciais do seu MySQL.

4. Suba o servidor embutido do PHP a partir da pasta `public`:
   ```bash
   php -S localhost:8000 -t public
   ```

5. A API estará disponível em `http://localhost:8000`.

## Usuários de teste (seed)

Todos com senha: `123456`

| Email              | Tipo    |
|--------------------|---------|
| admin@empresa.com  | admin   |
| joao@empresa.com   | agente  |
| maria@cliente.com  | cliente |

## Endpoints

### Autenticação

**POST** `/auth/login`

```json
{
  "email": "maria@cliente.com",
  "senha": "123456"
}
```

Resposta:
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "usuario": { "id": 3, "nome": "Cliente Maria", "tipo": "cliente" }
}
```

Use o token retornado no header `Authorization: Bearer <token>` nas rotas protegidas.

---

### Chamados

| Método | Rota                          | Autenticação | Descrição                                    |
|--------|-------------------------------|--------------|-----------------------------------------------|
| GET    | `/chamados`                   | Não          | Lista chamados (filtros: `?status=` e `?categoria_id=`) |
| GET    | `/chamados/{id}`               | Não          | Detalhe de um chamado, com comentários        |
| POST   | `/chamados`                    | Sim          | Cria um novo chamado                          |
| PUT    | `/chamados/{id}`                | Sim (agente/admin) | Atualiza status e/ou responsável        |
| DELETE | `/chamados/{id}`                | Sim (agente/admin) | "Exclui" (marca como fechado)            |
| POST   | `/chamados/{id}/comentarios`    | Sim          | Adiciona um comentário ao chamado             |
| GET    | `/relatorios/por-categoria`     | Não          | Relatório agregado por categoria/status       |

#### Exemplo: criar chamado

**POST** `/chamados`
Header: `Authorization: Bearer <token>`
```json
{
  "titulo": "Impressora não imprime",
  "descricao": "A impressora do setor financeiro está sem resposta.",
  "categoria_id": 1
}
```

#### Exemplo: atualizar chamado

**PUT** `/chamados/1`
```json
{
  "status": "em_andamento",
  "responsavel_id": 2
}
```

## Status codes utilizados

| Código | Significado                                  |
|--------|----------------------------------------------|
| 200    | Sucesso (GET, PUT)                            |
| 201    | Recurso criado (POST)                         |
| 401    | Não autenticado / credenciais inválidas       |
| 403    | Autenticado, mas sem permissão                |
| 404    | Recurso não encontrado                        |
| 422    | Dados inválidos / faltando                    |
| 500    | Erro interno do servidor                      |

