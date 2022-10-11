<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class MWPermisos
{
    public function esAdmin($request, $handler)
    {
        $header = $request->getHeaderLine('authorization');
        $response = new Response();
        if (!empty($header)) {
            $token = trim(explode("Bearer", $header)[1]);
            $data = JWTAuth::obtenerDatos($token);

            if ($data->esAdmin == 1) {
                $response = $handler->handle($request);
            } else {
                $response->getBody()->write(json_encode(array("error" => "Esta accion necesita permisos de administrador.")));
            }
        } else {
            $response->getBody()->write(json_encode(array("error" => "Necesita loguearse como administrador.")));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function esMozo($request, $handler)
    {
        $header = $request->getHeaderLine('authorization');
        $response = new Response();
        if (!empty($header)) {
            $token = trim(explode("Bearer", $header)[1]);
            $data = JWTAuth::obtenerDatos($token);
            if ($data->tipo == "Mozo" || $data->esAdmin == 1) {
                $response = $handler->handle($request);
            } else {
                $response->getBody()->write(json_encode(array("error" => "Esta accion solo puede ser realizada por mozos.")));
            }
        } else {
            $response->getBody()->write(json_encode(array("error" => "Necesita loguearse como mozo.")));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function esEmpleado($request, $handler)
    {
        $header = $request->getHeaderLine('authorization');
        $response = new Response();
        if (!empty($header)) {
            $token = trim(explode("Bearer", $header)[1]);
            $data = JWTAuth::obtenerDatos($token);
            if ($data->tipo == "Cocinero" || $data->tipo == "Bartender" || $data->tipo == "Cervezero" || $data->tipo == "Admin") {
                $response = $handler->handle($request);
            } else {
                $response->getBody()->write(json_encode(array("error" => "Esta accion solo puede ser realizada por empleados.")));
            }
        } else {
            $response->getBody()->write(json_encode(array("error" => "Necesita loguearse como empleado.")));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}
