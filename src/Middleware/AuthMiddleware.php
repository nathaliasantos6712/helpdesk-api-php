<?php
// src/Middleware/AuthMiddleware.php
//
// Verifica se a requisição contém um token JWT válido no cabeçalho
// "Authorization: Bearer <token>". Se não houver token, ou o token
// for inválido/expirado, responde com 401 e interrompe a execução.
//
// Se o token for válido, retorna os dados do usuário (id, nome, tipo)
// que estavam dentro do payload do JWT.

class AuthMiddleware
{
    public static function autenticar(): array
    {
        $headers = self::getAuthorizationHeader();

        if (!$headers || !preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            Response::erro('Token de autenticação não fornecido', 401);
        }

        $token = $matches[1];
        $payload = JWT::validar($token);

        if (!$payload) {
            Response::erro('Token inválido ou expirado', 401);
        }

        return $payload; // contém: id, nome, tipo, iat, exp
    }

    /**
     * Garante que o usuário autenticado tem um dos tipos (papéis) permitidos.
     * Deve ser chamado depois de autenticar().
     */
    public static function autorizar(array $usuario, array $tiposPermitidos): void
    {
        if (!in_array($usuario['tipo'], $tiposPermitidos, true)) {
            Response::erro('Você não tem permissão para executar esta ação', 403);
        }
    }

    /**
     * Obtém o cabeçalho Authorization de forma compatível com
     * diferentes configurações de servidor (Apache/Nginx/CLI).
     */
    private static function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    return $value;
                }
            }
        }

        return null;
    }
}
