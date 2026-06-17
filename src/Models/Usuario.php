<?php
// src/Models/Usuario.php
//
// Responsável por todas as queries relacionadas à tabela `usuarios`.

class Usuario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Busca um usuário pelo e-mail. Retorna null se não encontrar.
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome, email, tipo FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }
}
