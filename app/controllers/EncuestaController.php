<?php

require_once './models/Encuesta.php';

class EncuestaController extends Encuesta
{
    public function MandarEncuesta($request, $response, $args)
    {
        $params = $request->getParsedBody();

        
        if (isset($params['codigoComanda']) && isset($params['puntajeMesa'])
        && isset($params['puntajeCocinero']) && isset($params['puntajeMozo']) &&
        isset($params['puntajeRestaurante']) && isset($params['comentario']))
        {
            $codigoComanda = $params['codigoComanda'];
            $codigoMesa = $params['codigoMesa'];
            $puntajeMesa = $params['puntajeMesa'];
            $puntajeCocinero = $params['puntajeCocinero'];
            $puntajeMozo = $params['puntajeMozo'];
            $puntajeRestaurante = $params['puntajeRestaurante'];
            $comentario = $params['comentario'];

            $comanda = Comanda::obtenerComanda($codigoComanda);
            $mesa = Mesa::obtenerMesa($codigoMesa);

            if($mesa != false && $comanda != false && $comanda->mesaId == $mesa->id)
            {
                try {
                    Encuesta::verificarDatosAlta($comentario);
                    $encuesta = new Encuesta();
                    $encuesta->comandaAsociada = $codigoComanda;
                    $encuesta->puntajeMesa = $puntajeMesa;
                    $encuesta->puntajeCocinero = $puntajeCocinero;
                    $encuesta->puntajeMozo = $puntajeMozo;
                    $encuesta->puntajeRestaurante = $puntajeRestaurante;
                    $encuesta->comentario = $comentario;
                    $encuesta->promedio = ($puntajeMesa + $puntajeCocinero + $puntajeMozo + $puntajeRestaurante) / 4;
    
                    $encuestaId = $encuesta->crearEncuesta();
    
                    if ($encuestaId > 0) {
                        $payload = json_encode(array("mensaje" => "Encuesta insertada en la base de datos correctamente, gracias por su opinion"));
                    } else {
                        $payload = json_encode(array("error" => "Ha ocurrido un error al insertar la encuesta en la base de datos"));
                    }
                } catch (Exception $e) {
                    $payload = json_encode(array("error" => $e->getMessage()));
                }
            }
            else
            {
            $payload = json_encode(array("mensaje" => "Necesita el codigo de mesa y numero de su comanda para subir la encuesta."));
            }
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function MejoresComentarios($request, $response, $args){
        $params = $request->getParsedBody();
        $payload = json_encode(array("message" => 'Error al traer los comentarios'));
        if (isset($params['cantidad'])){
            $cantidad = $params['cantidad'];
            $comentarios = Encuesta::obtenerMejores($cantidad);
            $payload = json_encode(array("Comentarios" => $comentarios));
        }

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
