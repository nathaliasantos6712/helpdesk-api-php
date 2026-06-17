<?php
// config/database.php
//
// Responsável por criar e devolver uma conexão PDO com o banco de dados.
// Lê as credenciais de variáveis de ambiente (.env), com valores
// padrão para facilitar testes locais.

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_NAME'] ?? 'helpdesk';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Em caso de falha de conexão, devolvemos um erro 500 em JSON
                // e interrompemos a execução.
                http_response_code(500);
                echo json_encode([
                    'erro' => 'Falha na conexão com o banco de dados',
                    'detalhe' => $e->getMessage(),
                ]);
                exit;
            }
        }

        return self::$instance;
    }
}
