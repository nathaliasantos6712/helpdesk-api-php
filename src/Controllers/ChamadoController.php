<?php
// src/Controllers/ChamadoController.php
//
// Contém a lógica de negócio das rotas relacionadas a chamados:
// listar, detalhar, criar, atualizar, fechar e gerar relatório.
// Cada método corresponde a uma rota e é responsável por:
//   1. Validar os dados de entrada
//   2. Chamar o Model correspondente
//   3. Devolver a resposta no formato e status code corretos

class ChamadoController
{
    private Chamado $chamadoModel;
    private Comentario $comentarioModel;

    public function __construct(PDO $db)
    {
        $this->chamadoModel = new Chamado($db);
        $this->comentarioModel = new Comentario($db);
    }

    /**
     * GET /chamados?status=aberto&categoria_id=2
     */
    public function listar(): void
    {
        $filtros = [
            'status'       => $_GET['status'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
        ];

        $chamados = $this->chamadoModel->listar($filtros);

        Response::json(['dados' => $chamados, 'total' => count($chamados)], 200);
    }

    /**
     * GET /chamados/{id}
     */
    public function detalhar(int $id): void
    {
        $chamado = $this->chamadoModel->buscarPorId($id);

        if (!$chamado) {
            Response::erro('Chamado não encontrado', 404);
        }

        Response::json($chamado, 200);
    }

    /**
     * POST /chamados
     * Body esperado: { "titulo": "...", "descricao": "...", "categoria_id": 1 }
     * Requer autenticação. cliente_id vem do usuário logado.
     */
    public function criar(array $usuarioLogado): void
    {
        $dados = json_decode(file_get_contents('php://input'), true);

        if (empty($dados['titulo'])) {
            Response::erro('O campo "titulo" é obrigatório', 422);
        }

        $dados['cliente_id'] = $usuarioLogado['id'];

        $id = $this->chamadoModel->criar($dados);

        Response::json(['id' => $id, 'mensagem' => 'Chamado criado com sucesso'], 201);
    }

    /**
     * PUT /chamados/{id}
     * Body esperado: { "status": "em_andamento", "responsavel_id": 2 }
     * Apenas agentes e admins podem alterar.
     */
    public function atualizar(int $id, array $usuarioLogado): void
    {
        AuthMiddleware::autorizar($usuarioLogado, ['agente', 'admin']);

        if (!$this->chamadoModel->existe($id)) {
            Response::erro('Chamado não encontrado', 404);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        $statusValidos = ['aberto', 'em_andamento', 'resolvido', 'fechado'];
        if (isset($dados['status']) && !in_array($dados['status'], $statusValidos, true)) {
            Response::erro('Status inválido. Use: ' . implode(', ', $statusValidos), 422);
        }

        $atualizou = $this->chamadoModel->atualizar($id, $dados);

        if (!$atualizou) {
            Response::erro('Nenhum dado válido para atualizar foi enviado', 422);
        }

        Response::json(['mensagem' => 'Chamado atualizado com sucesso'], 200);
    }

    /**
     * DELETE /chamados/{id}
     * "Exclusão" lógica: apenas marca o chamado como 'fechado'.
     */
    public function fechar(int $id, array $usuarioLogado): void
    {
        AuthMiddleware::autorizar($usuarioLogado, ['agente', 'admin']);

        if (!$this->chamadoModel->existe($id)) {
            Response::erro('Chamado não encontrado', 404);
        }

        $this->chamadoModel->fechar($id);

        Response::json(['mensagem' => 'Chamado fechado com sucesso'], 200);
    }

    /**
     * POST /chamados/{id}/comentarios
     * Body esperado: { "texto": "..." }
     */
    public function adicionarComentario(int $id, array $usuarioLogado): void
    {
        if (!$this->chamadoModel->existe($id)) {
            Response::erro('Chamado não encontrado', 404);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (empty($dados['texto'])) {
            Response::erro('O campo "texto" é obrigatório', 422);
        }

        $idComentario = $this->comentarioModel->criar($id, $usuarioLogado['id'], $dados['texto']);

        Response::json(['id' => $idComentario, 'mensagem' => 'Comentário adicionado'], 201);
    }

    /**
     * GET /relatorios/por-categoria
     */
    public function relatorioPorCategoria(): void
    {
        $relatorio = $this->chamadoModel->relatorioPorCategoria();

        Response::json(['dados' => $relatorio], 200);
    }
}
