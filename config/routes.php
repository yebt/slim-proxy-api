<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

return function (App $app) {

    // Proxy genérico: recibe cualquier ruta y la envía a example.com
    $app->any('/proxy/{uri:.*}', function (Request $request, Response $response, array $args) {
        if (!env("WRAP_URL", false)) {
            $response->getBody()->write(
                json_encode([
                    'message' => 'Not WRAP_URL ALLOWED'
                ], JSON_UNESCAPED_UNICODE)
            );
            return $response->withStatus(501)->withHeader('Content-type', "application/json");
        }

        $client = new Client([
            'http_errors' => false, // para no lanzar excepciones en 4xx/5xx
            // 'timeout' => 10,       // opcional: timeout
        ]);

        // Reconstruir URL destino
        $uriPath = $args['uri'];
        $query   = $request->getUri()->getQuery();
        $targetBase = env('WRAP_URL');
        $targetUrl  = rtrim($targetBase, '/') . '/' . $uriPath . ($query ? "?$query" : '');

        // Preparar opciones para Guzzle
        $options = [
            'headers' => $request->getHeaders(),
            'body'    => (string) $request->getBody(),
        ];

        // Enviar petición al servidor destino
        $res = $client->request($request->getMethod(), $targetUrl, $options);

        // Copiar status
        $response = $response->withStatus($res->getStatusCode());

        // Copiar headers (except hop-by-hop per RFC; ajustable según necesidades)
        $headersToSkip = ['connection', 'keep-alive', 'proxy-authenticate', 'transfer-encoding', 'upgrade'];
        // $forwardHeaders = array_filter(
        //     $res->getHeaders(),
        //     function ($hVal) use ($headersToSkip) {
        //         if (is_string($hVal)) {
        //             return !in_array($hVal, $headersToSkip);
        //         }
        //         return true;
        //     },
        //     ARRAY_FILTER_USE_KEY // use the keys
        // );
        foreach ($res->getHeaders() as $name => $values) {
            if (!is_string($name)) {
                continue;
            }
            if (in_array(strtolower($name), $headersToSkip)) {
                continue;
            }
            foreach ($values as $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        // Escribir el cuerpo
        $response->getBody()->write((string) $res->getBody());

        return $response;
    });
};
