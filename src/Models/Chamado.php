<?php
// src/Models/Chamado.php
//
// Responsável por todas as queries relacionadas à tabela `chamados`.
// Aqui ficam concentradas as consultas SQL (SELECT, INSERT, UPDATE, DELETE),
// incluindo os JOINs com categorias e usuários.

class Chamado
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Lista chamados com filtros opcionais (status e categoria_id).
     * Faz JOIN com categorias e usuários para já trazer os nomes,
     * evitando que o front-end precise fazer requisições extras.
     */
    public function listar(array $filtros = []): array
    {
        $sql = "
            SELECT
                c.id, c.titulo, c.descricao, c.status, c.criado_em, c.atualizado_em,
                cat.nome AS categoria,
                cliente.nome AS cliente,
                responsavel.nome AS responsavel
            FROM chamados c
            LEFT JOIN categorias cat ON cat.id = c.categoria_id
            INNER JOIN usuarios cliente ON cliente.id = c.cliente_id
            LEFT JOIN usuarios responsavel ON responsavel.id = c.responsavel_id
            WHERE 1 = 1
        ";

        $params = [];

        if (!empty($filtros['status'])) {
            $sql .= ' AND c.status = :status';
            $params['status'] = $filtros['status'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        $sql .= ' ORDER BY c.criado_em DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca um chamado pelo ID, incluindo seus comentários.
     * Retorna null se o chamado não existir.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                c.id, c.titulo, c.descricao, c.status, c.criado_em, c.atualizado_em,
                c.categoria_id, c.cliente_id, c.responsavel_id,
                cat.nome AS categoria,
                cliente.nome AS cliente,
                responsavel.nome AS responsavel
            FROM chamados c
            LEFT JOIN categorias cat ON cat.id = c.categoria_id
            INNER JOIN usuarios cliente ON cliente.id = c.cliente_id
            LEFT JOIN usuarios responsavel ON responsavel.id = c.responsavel_id
            WHERE c.id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $chamado = $stmt->fetch();

        if (!$chamado) {
            return null;
        }

        // Busca os comentários relacionados
        $stmtComentarios = $this->db->prepare("
            SELECT co.id, co.texto, co.criado_em, u.nome AS autor
            FROM comentarios co
            INNER JOIN usuarios u ON u.id = co.usuario_id
            WHERE co.chamado_id = :chamado_id
            ORDER BY co.criado_em ASC
        ");
        $stmtComentarios->execute(['chamado_id' => $id]);
        $chamado['comentarios'] = $stmtComentarios->fetchAll();

        return $chamado;
    }

    /**
     * Cria um novo chamado e retorna o ID gerado.
     */
    public function criar(array $dados): int
    {
        $sql = "
            INSERT INTO chamados (titulo, descricao, categoria_id, cliente_id, status)
            VALUES (:titulo, :descricao, :categoria_id, :cliente_id, 'aberto')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'titulo'       => $dados['titulo'],
            'descricao'    => $dados['descricao'] ?? null,
            'categoria_id' => $dados['categoria_id'] ?? null,
            'cliente_id'   => $dados['cliente_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza campos de um chamado (status e/ou responsável).
     * Retorna true se alguma linha foi alterada.
     */
    public function atualizar(int $id, array $dados): bool
    {
        $campos = [];
        $params = ['id' => $id];

        if (isset($dados['status'])) {
            $campos[] = 'status = :status';
            $params['status'] = $dados['status'];
        }

        if (isset($dados['responsavel_id'])) {
            $campos[] = 'responsavel_id = :responsavel_id';
            $params['responsavel_id'] = $dados['responsavel_id'];
        }

        if (empty($campos)) {
            return false; // nada para atualizar
        }

        $sql = 'UPDATE chamados SET ' . implode(', ', $campos) . ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Marca um chamado como 'fechado' (soft delete).
     */
    public function fechar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE chamados SET status = 'fechado' WHERE id = :id");
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica se um chamado existe (usado para retornar 404 antes de operar).
     */
    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM chamados WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetch();
    }

    /**
     * Relatório: quantidade de chamados agrupados por categoria e status.
     * Demonstra uso de JOIN + GROUP BY + COUNT.
     */
    public function relatorioPorCategoria(): array
    {
        $sql = "
            SELECT
                cat.nome AS categoria,
                c.status,
                COUNT(*) AS total
            FROM chamados c
            LEFT JOIN categorias cat ON cat.id = c.categoria_id
            GROUP BY cat.nome, c.status
            ORDER BY cat.nome, c.status
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }
}
