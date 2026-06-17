<?php
// public/index.php
//
// Ponto de entrada da aplicação (front controller).
// Todas as requisições passam por aqui (configurado via .htaccess
// ou pelo servidor embutido do PHP). Este arquivo:
//   1. Carrega as dependências (autoload + variáveis de ambiente)
//   2. Abre a conexão com o banco
//   3. Lê o método HTTP e a URL da requisição
//   4. Direciona (roteia) para o Controller/método correto
//   5. Devolve 404 se a rota não existir

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers/Response.php';
require_once __DIR__ . '/../src/Helpers/JWT.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Models/Usuario.php';
require_once __DIR__ . '/../src/Models/Chamado.php';
require_once __DIR__ . '/../src/Models/Comentario.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ChamadoController.php';

// Carrega variáveis de ambiente do arquivo .env (se existir)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (parse_ini_file($envPath) as $key => $value) {
        $_ENV[$key] = $value;
    }
}

$db = Database::getConnection();

// Método HTTP da requisição (GET, POST, PUT, DELETE...)
$metodo = $_SERVER['REQUEST_METHOD'];

// Remove o caminho base e a query string para obter apenas a rota.
// Ex.: /helpdesk-api/public/chamados/5?foo=bar  ->  chamados/5
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/index.php'; // ajuste se necessário, dependendo do servidor
$rota = trim(str_replace($basePath, '', $uri), '/');
$partes = explode('/', $rota); // ex.: ['chamados', '5']

// ===================================================
// Roteamento
// ===================================================

try {
    // ---------- AUTENTICAÇÃO ----------
    // POST /auth/login
    if ($metodo === 'POST' && $rota === 'auth/login') {
        (new AuthController($db))->login();
    }

    // ---------- CHAMADOS ----------
    // GET /chamados
    elseif ($metodo === 'GET' && $rota === 'chamados') {
        (new ChamadoController($db))->listar();
    }

    // GET /chamados/{id}
    elseif ($metodo === 'GET' && $partes[0] === 'chamados' && isset($partes[1]) && is_numeric($partes[1]) && !isset($partes[2])) {
        (new ChamadoController($db))->detalhar((int) $partes[1]);
    }

    // POST /chamados  (requer autenticação)
    elseif ($metodo === 'POST' && $rota === 'chamados') {
        $usuario = AuthMiddleware::autenticar();
        (new ChamadoController($db))->criar($usuario);
    }

    // PUT /chamados/{id}  (requer autenticação + papel agente/admin)
    elseif ($metodo === 'PUT' && $partes[0] === 'chamados' && isset($partes[1]) && is_numeric($partes[1]) && !isset($partes[2])) {
        $usuario = AuthMiddleware::autenticar();
        (new ChamadoController($db))->atualizar((int) $partes[1], $usuario);
    }

    // DELETE /chamados/{id}  (requer autenticação + papel agente/admin)
    elseif ($metodo === 'DELETE' && $partes[0] === 'chamados' && isset($partes[1]) && is_numeric($partes[1]) && !isset($partes[2])) {
        $usuario = AuthMiddleware::autenticar();
        (new ChamadoController($db))->fechar((int) $partes[1], $usuario);
    }

    // POST /chamados/{id}/comentarios  (requer autenticação)
    elseif ($metodo === 'POST' && $partes[0] === 'chamados' && isset($partes[1]) && is_numeric($partes[1]) && ($partes[2] ?? null) === 'comentarios') {
        $usuario = AuthMiddleware::autenticar();
        (new ChamadoController($db))->adicionarComentario((int) $partes[1], $usuario);
    }

    // ---------- RELATÓRIOS ----------
    // GET /relatorios/por-categoria
    elseif ($metodo === 'GET' && $rota === 'relatorios/por-categoria') {
        (new ChamadoController($db))->relatorioPorCategoria();
    }

    // ---------- ROTA NÃO ENCONTRADA ----------
    else {
        Response::erro('Rota não encontrada', 404);
    }
} catch (Throwable $e) {
    // Captura qualquer erro inesperado e devolve como 500,
    // evitando que detalhes internos (stack trace) sejam expostos.
    Response::json([
        'erro' => 'Erro interno do servidor',
        'detalhe' => $e->getMessage(),
    ], 500);
}
