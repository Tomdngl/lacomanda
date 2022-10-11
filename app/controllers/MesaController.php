<?php
require_once './models/Mesa.php';

class MesaController extends Mesa
{
    public function CargarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();

        $codigoMesa = $params['codigoMesa'];

        $mesa = new Mesa();
        $mesa->estado = "Libre";
        $mesa->codigoMesa = $codigoMesa;

        $payload = json_encode($mesa);
        $mesaId = $mesa->crearmesa();
        if ($mesaId > 0) {
            $mesa->id = $mesaId;
            $payload = json_encode(array("mensaje" => "Mesa creada con exito"));
        } else {
            $response->getBody()->write("Ocurrio un error al intentar cargar la mesa.");
        }
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesas" => $lista));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function TraerFacturas($request, $response, $args)
    {
        $comandas = Comanda::obtenerTodos();
        $cantidadComandas = 0;
        $mesas = array();

        foreach ($comandas as $comanda) {
            $productos = Producto::TraerProductos($comanda->id);
            $precioTotal = 0;
            $finalizada = true;
            foreach ($productos as $producto) {
                if ($producto->estado != "Listo para servir" && $producto->estado != "Expirado") {
                    $finalizada = false;
                    break;
                } else {
                    $precioTotal += $producto->precio;
                }
            }
            if ($finalizada == true) {
                array_push($mesas, array("PrecioTotal" => $precioTotal, "MesaId" => $comanda->mesaId));
            }
        }
        sort($mesas);

        $payload = json_encode(array("mensaje" => $mesas));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $id = $params['id'];
        if (isset($params['id'])) {
            try {
                if (Mesa::borrarMesa($id) > 0) {
                    $payload = json_encode(array("mensaje" => "Mesa borrada de la base de datos"));
                } else {
                    $payload = json_encode(array("mensaje" => "Ocurrio un error al borrar la mesa de la base de datos"));
                }
            } catch (Exception $ex) {
                $ex->getMessage();
            }
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function LiberarMesa($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $id = $params['id'];
        if (isset($params['id'])) {
            if (Mesa::Liberar($id) > 0) {
                $payload = json_encode(array("mensaje" => "La mesa ahora se encuentra libre"));
            } else {
                $payload = json_encode(array("mensaje" => "Ocurrio un error al liberar la mesa"));
            }
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function TraerEntreFechas($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $fechaInicio = new DateTime($params['desde']);
        $fechaFin = new DateTime($params['hasta']);
        $fechaInicioUnix = date_timestamp_get($fechaInicio);
        $fechaFinUnix = date_timestamp_get($fechaFin);
        $comandas = Comanda::obtenerTodos();
        $mesas = array();

        foreach ($comandas as $comanda) {
            $productos = Producto::TraerProductos($comanda->id);
            $precioTotal = 0;
            $finalizada = true;
            $tiempoMax = new DateTime('2000-01-01 01:01:01');
            $tiempoMaxUnix =  date_timestamp_get($tiempoMax);
            foreach ($productos as $producto) {
                if ($producto->estado != "Listo para servir" && $producto->estado != "Expirado") {
                    $finalizada = false;
                    break;
                } else {
                    $precioTotal += $producto->precio;
                    $tiempoStr = $producto->horaEstimada;
                    $tiempoActual = new DateTime($tiempoStr);
                    $tiempoActualUnix =  date_timestamp_get($tiempoActual);

                    if ($tiempoActualUnix > $tiempoMaxUnix) {
                        $tiempoMaxUnix = $tiempoActualUnix;
                        $tiempoMax = $tiempoActual;
                    }
                }
            }
            if ($finalizada == true && $tiempoMaxUnix > $fechaInicioUnix && $tiempoMaxUnix < $fechaFinUnix) {
                array_push($mesas, array("PrecioTotal" => $precioTotal, "MesaId" => $comanda->mesaId, "Tiempo entrega" => $tiempoMax));
            }
        }
        sort($mesas);

        $payload = json_encode(array("mensaje" => $mesas));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function CerrarMesa($request, $response, $args)
    {
        $params = $request->getParsedBody();

        if (isset($params['idMesa'])) {
            $id = $params['idMesa'];
            Mesa::Cerrar($id);
            $payload = json_encode(array("mensaje" => "Se ha cerrado la mesa."));
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function MesaMasUsada($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $payload = json_encode(array("message" => 'Error al traer la mesa mas usada'));
        if (isset($params['cantidad'])) {
            $cantidad = $params['cantidad'];
            $mesa = Mesa::ObtenerMesaMasUsada($cantidad);
            $payload = json_encode(array("Mesa" => $mesa));
        } else {
            $payload = json_encode(array("message" => 'Debe insertar la cantidad de mesas que desea traer'));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
