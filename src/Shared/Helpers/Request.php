<?php
declare(strict_types=1);

namespace App\Shared\Helpers;

final class Request
{
    /**
     * Devuelve el payload JSON como array asociativo.
     * Lanza InvalidArgumentException si no viene application/json o JSON inválido.
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function getJson(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');

        // Aceptar "application/json" y variantes "application/json; charset=utf-8"
        if (stripos((string)$contentType, 'application/json') === false) {
            throw new \InvalidArgumentException('Content-Type must be application/json');
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return []; // body vacío -> cliente se encarga de validar campos obligatorios
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return is_array($data) ? $data : [];
    }
}
