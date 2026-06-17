<?php
// src/Models/Comentario.php
//
// Responsável pelas queries relacionadas à tabela `comentarios`.

class Comentario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function criar(int $chamadoId, int $usuarioId, string $texto): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO comentarios (chamado_id, usuario_id, texto)
            VALUES (:chamado_id, :usuario_id, :texto)
        ");

        $stmt->execute([
            'chamado_id' => $chamadoId,
            'usuario_id' => $usuarioId,
            'texto'      => $texto,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
