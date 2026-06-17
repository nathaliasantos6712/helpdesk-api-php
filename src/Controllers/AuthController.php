<?php
// src/Controllers/AuthController.php
//
// Responsável pelo login do usuário. Recebe email + senha,
// valida no banco e, se corretos, gera um token JWT.

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct(PDO $db)
    {
        $this->usuarioModel = new Usuario($db);
    }

    /**
     * POST /auth/login
     * Body esperado: { "email": "...", "senha": "..." }
     */
    public function login(): void
    {
        $dados = json_decode(file_get_contents('php://input'), true);

        if (empty($dados['email']) || empty($dados['senha'])) {
            Response::erro('Os campos "email" e "senha" são obrigatórios', 422);
        }

        $usuario = $this->usuarioModel->buscarPorEmail($dados['email']);

        // Mesma mensagem de erro tanto para "usuário não existe" quanto para
        // "senha errada" -> evita que um atacante descubra emails válidos.
        if (!$usuario || !password_verify($dados['senha'], $usuario['senha'])) {
            Response::erro('Email ou senha inválidos', 401);
        }

        $token = JWT::gerar([
            'id'   => $usuario['id'],
            'nome' => $usuario['nome'],
            'tipo' => $usuario['tipo'],
        ]);

        Response::json([
            'token' => $token,
            'usuario' => [
                'id'   => $usuario['id'],
                'nome' => $usuario['nome'],
                'tipo' => $usuario['tipo'],
            ],
        ], 200);
    }
}
