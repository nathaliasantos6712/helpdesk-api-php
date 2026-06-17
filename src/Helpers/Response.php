<?php
// src/Helpers/Response.php
//
// Funções utilitárias para padronizar as respostas da API em JSON,
// incluindo o status code HTTP correto.

class Response
{
    /**
     * Envia uma resposta JSON e termina a execução do script.
     *
     * @param mixed $data       Dados a serem retornados (serão convertidos em JSON)
     * @param int   $statusCode Código de status HTTP (200, 201, 400, 404, etc.)
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function erro(string $mensagem, int $statusCode = 400): void
    {
        self::json(['erro' => $mensagem], $statusCode);
    }
}
