<?php
// src/Helpers/JWT.php
//
// Implementação simples (e didática) de geração e validação de JWT,
// usando apenas funções nativas do PHP (sem dependências externas).
//
// Um JWT é composto por três partes separadas por ponto:
//   header.payload.assinatura
//
// header e payload são JSON codificados em Base64Url.
// A assinatura garante que o token não foi alterado (usa HMAC-SHA256
// com uma chave secreta que só o servidor conhece).

class JWT
{
    private static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'chave-secreta-de-desenvolvimento';
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Gera um token JWT a partir de um array de dados (payload).
     * Adiciona automaticamente 'iat' (emitido em) e 'exp' (expiração).
     */
    public static function gerar(array $payload, int $expiraEmSegundos = 3600): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiraEmSegundos;

        $headerEncoded  = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $assinatura = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::getSecret(),
            true
        );
        $assinaturaEncoded = self::base64UrlEncode($assinatura);

        return "{$headerEncoded}.{$payloadEncoded}.{$assinaturaEncoded}";
    }

    /**
     * Valida um token JWT. Retorna o payload decodificado se for válido,
     * ou null se o token estiver expirado, malformado ou com assinatura inválida.
     */
    public static function validar(string $token): ?array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $assinaturaEncoded] = $partes;

        $assinaturaEsperada = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::getSecret(),
            true
        );
        $assinaturaEsperadaEncoded = self::base64UrlEncode($assinaturaEsperada);

        if (!hash_equals($assinaturaEsperadaEncoded, $assinaturaEncoded)) {
            return null; // assinatura inválida -> token foi adulterado
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload || ($payload['exp'] ?? 0) < time()) {
            return null; // token expirado
        }

        return $payload;
    }
}
