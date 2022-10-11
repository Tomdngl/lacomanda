<?php
require_once './models/Comanda.php';

class ComandaController extends Comanda
{
    public function CargarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();

        if (isset($params['mesa_id']) && isset($params['nombreCliente'])) {
            $mesa_id = $params['mesa_id'];
            $nombreCliente = $params['nombreCliente'];

            try {
                Comanda::verificarDatosAlta($mesa_id, $nombreCliente);
                $comanda = new Comanda();
                $comanda->mesa_id = $mesa_id;
                $comanda->nombreCliente = $nombreCliente;

                $comandaId = $comanda->crearComanda();

                if ($comandaId > 0) {
                    $comanda = Comanda::obtenerComanda($comandaId);
                    $payload = json_encode(array("mensaje" => "Comanda insertada en la base de datos correctamente", "comanda" => json_encode($comanda)));
                    $comanda->id = $comandaId;
                    echo $comandaId;
                    Comanda::SubirArchivo($request, $comanda, 0);
                    Mesa::EntraCliente($mesa_id);
                    //Registro accion
                    $usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
                    $area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
                    Archivos::insertarAccion($usuario, "Carga comanda", $area);
                } else {
                    $payload = json_encode(array("error" => "Ha ocurrido un error al insertar la comanda en la base de datos"));
                }
            } catch (Exception $e) {
                $payload = json_encode(array("error" => $e->getMessage()));
            }
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function AgregarFoto($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $foto = $request->getUploadedFiles();

        if (isset($params['id'])) {
            $id = $params['id'];

            $comanda = Comanda::obtenerComanda($id);
            Comanda::SubirArchivo($request, $comanda, 0);
            $payload = json_encode(array("mensaje" => "Foto agregada."));
            //Registro accion
            $usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
            $area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
            Archivos::insertarAccion($usuario, "Carga foto", $area);
        } else {
            $payload = json_encode(array("mensaje" => "Ocurrio un error al agregar la foto."));
        }
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Comanda::obtenerTodos();
        $payload = json_encode(array("listaComandas" => $lista));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $id = $params['id'];
        if (isset($params['id'])) {
            $comanda = Comanda::obtenerComanda($id);
            try {
                if (Comanda::borrarComanda($id) > 0) {
                    Mesa::Liberar($comanda->mesaId);
                    $payload = json_encode(array("mensaje" => "Comanda borrada de la base de datos"));
                } else {
                    $payload = json_encode(array("mensaje" => "Ocurrio un error al borrar la comanda de la base de datos"));
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

    public function ObtenerTiempoRestante($request, $response, $args)
    {
        $params = $request->getParsedBody();

        $codigoMesa = $params['codigo_mesa'];
        $codigoComanda = $params['codigo_comanda'];

        $comanda = Comanda::obtenerComanda($codigoComanda);
        $mesa = Mesa::obtenerMesa($codigoMesa);

        if ($mesa != false && $comanda != false && $comanda->mesaId == $mesa->id) {
            $productos = Producto::TraerProductos($codigoComanda);
            $tiempoMax = new DateTime('2000-01-01 01:01:01');
            $tiempoMaxUnix =  date_timestamp_get($tiempoMax);
            //Obtengo el tiempo esperando mas largo de los productos de la comanda
            foreach ($productos as $item => $valor) {
                if ($valor->estado == "Pendiente") {
                    $payload = json_encode(array("mensaje" => "Su pedido aun se encuentra en la lista de pendientes, disculpe la demora."));
                    break;
                }

                $tiempoStr = $valor->horaEstimada;
                $tiempoActual = new DateTime($tiempoStr);
                $tiempoActualUnix =  date_timestamp_get($tiempoActual);

                if ($tiempoActualUnix > $tiempoMaxUnix) {
                    $tiempoMaxUnix = $tiempoActualUnix;
                    $tiempoMax = $tiempoActual;
                }
                //$payload = json_encode(array("productos" => $productos));
            }
            //Obtengo el tiempo del ultimo producto cambiado a preparacion
            $ultimoPreparado = Producto::obtenerUltimoPreparado($codigoComanda);
            $tiempoInicio = new DateTime($ultimoPreparado->horaInicio);
            //Calculo la diferencia de tiempo
            $intervalo = $tiempoInicio->diff($tiempoMax);
            /*
            Asi puedo mostrar el horario aproximado de salida del pedido
            $fecha = new DateTime("@$tiempoMaxUnix");
            $payload = json_encode(array("mensaje" => "Su pedido estara listo aproximadamente a las " . $fecha->format('H:i:s')));
            */
            //Muestro las horas / minutos restantes para que salga el pedido
            $payload = json_encode(array("mensaje" => "El tiempo aproximado para que salga su pedido es de " .  $intervalo->format('%H horas %i minutos %s segundos')));
        } else {
            $payload = json_encode(array("mensaje" => "El id de la comanda no coincide con el codigo de la mesa."));
        }
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function VerificarListos($request, $response, $args)
    {
        //Verifico cuales comandas tienen clientes esperando pedido
        $idActivos = Comanda::VerificarEstadoMesa();
        $contador = 0;

        foreach ($idActivos as $fila => $id) {
            if (count(Producto::VerificarListos($id->id)) == count(Producto::TraerProductos($id->id))) {
                Mesa::Servir(Comanda::ObtenerMesaRelacionada($id->id)[0]->id);
                $contador++;
            }
        }
        if ($contador == 0) {
            $payload = json_encode(array("mensaje" => "No hay pedidos listos para servir."));
        } else {
            $payload = json_encode(array("mensaje" => "Se han servido " . $contador . " mesas."));
        }
        //Registro accion
        $usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
        $area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
        Archivos::insertarAccion($usuario, "Verifica listos", $area);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public static function CobrarCuenta($request, $response, $args)
    {
        $params = $request->getParsedBody();
        if (isset($params['comandaId'])) {
            $comandaId = $params['comandaId'];
            $productos = Producto::TraerProductos($comandaId);
            $total = 0;
            foreach ($productos as $item => $producto) {
                $total += $producto->precio;
                Producto::Expirar($producto->id);
            }

            Mesa::Pagar(Comanda::ObtenerMesaRelacionada($comandaId)[0]->id);

            $payload = json_encode(array("mensaje" => "Comanda saldada por un total de " . $total . "$"));
            //Registro accion
            $usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
            $area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
            Archivos::insertarAccion($usuario, "Cobra cuenta", $area);
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public static function FueraDeTiempoEstipulado($request, $response, $args)
    {
        $productos = Producto::TraerFinalizados();
        $productosFueraDeTiempo = array();
        foreach ($productos as $item => $producto) {
            $tiempoEstimadoStr = $producto->horaEstimada;
            $tiempoEstimado = new DateTime($tiempoEstimadoStr);
            $horaEstimadaUnix =  date_timestamp_get($tiempoEstimado);
            $tiempoSalidaStr = $producto->horaSalida;
            $tiempoSalida = new DateTime($tiempoSalidaStr);
            $horaSalidaUnix =  date_timestamp_get($tiempoSalida);

            if ($horaEstimadaUnix < $horaSalidaUnix) {
                array_push($productosFueraDeTiempo, $producto);
            }
        }

        $payload = json_encode(array("listadoProductos" => $productosFueraDeTiempo));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
