<?php
require_once './models/Empleado.php';

class EmpleadoController extends Empleado
{
    public function CargarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();

        if (
            isset($params['usuario']) && isset($params['clave'])
            && isset($params['esAdmin']) && isset($params['tipo'])
            && isset($params['nombre'])
        ) {
            $usuarioStr = $params['usuario'];
            $clave = $params['clave'];
            $esAdmin = $params['esAdmin'];
            $estado = "Activo";
            $nombre = $params['nombre'];
            $tipo = $params['tipo'];
            $fechaAlta = date('Y-m-d H:i:s');

            try {
                Empleado::verificarDatosAlta($usuarioStr, $clave, $nombre, $esAdmin, $tipo, $estado);
                $empleado = new Empleado();
                $empleado->usuario = $usuarioStr;
                $empleado->nombre = $nombre;
                $empleado->clave = $clave;
                $empleado->esAdmin = $esAdmin;
                $empleado->tipo = $tipo;
                $empleado->estado = $estado;
                $empleado->fechaAlta = $fechaAlta;

                $empleado->id = $empleado->crearEmpleado();

                if ($empleado->id > 0) {
                    $payload = json_encode(array("mensaje" => "Empleado insertado en la base de datos correctamente", "empleado" => json_encode($empleado)));
                } else {
                    $payload = json_encode(array("error" => "Ha ocurrido un error al insertar el empleado en la base de datos"));
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

    public function TraerTodos($request, $response, $args)
    {
        $lista = Empleado::obtenerTodos();
        $payload = json_encode(array("listaUsuario" => $lista));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $usuarioId = $params['id'];
        $empleado = Empleado::obtenerEmpleado($usuarioId);
        if (isset($params['id'])) {
            if ($empleado != null) {
                Empleado::borrarUsuario($usuarioId);
                $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));
            } else {
                $payload = json_encode(array("mensaje" => "No existe un empleado con ese id"));
            }
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $usuarioId = $params['id'];

        if (
            isset($params['usuario']) && isset($params['clave'])
            && isset($params['esAdmin']) && isset($params['tipo'])
            && isset($params['nombre'])
        ) {
            $usuarioStr = $params['usuario'];
            $clave = $params['clave'];
            $esAdmin = $params['esAdmin'];
            $estado = "Activo";
            $nombre = $params['nombre'];
            $tipo = $params['tipo'];
            $fechaAlta = date('Y-m-d H:i:s');

            Empleado::verificarDatosAlta($usuarioStr, $clave, $nombre, $esAdmin, $tipo, $estado);
            $empleado = new Empleado();
            $empleado->usuario = $usuarioStr;
            $empleado->nombre = $nombre;
            $empleado->clave = $clave;
            $empleado->esAdmin = $esAdmin;
            $empleado->tipo = $tipo;
            $empleado->estado = $estado;
            $empleado->fechaAlta = $fechaAlta;
        }

        if (!$empleado->modificarUsuario($usuarioId)) {
            $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));
        } else {
            $payload = json_encode(array("mensaje" => "Usuario no modificado"));
        }
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function SuspenderUno($request, $response, $args)
    {
        $params = $request->getParsedBody();

        $usuarioId = $params['id'];
        Empleado::suspenderUsuario($usuarioId);

        $payload = json_encode(array("mensaje" => "Usuario suspendido con exito"));

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public static function OperacionesPorSector($request, $response, $args)
    {
        $params = $request->getParsedBody();

        if (isset($params['sector'])) {
            $sector = $params['sector'];
            $operaciones = Archivos::obtenerOperacionesSector($sector);
            $payload = json_encode(array("operaciones" => $operaciones));
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public static function OperacionesAgrupadas($request, $response, $args)
    {
        $params = $request->getParsedBody();

        $operaciones = Archivos::obtenerOperacionesAgrupadas();
        if ($operaciones != null) {
            $payload = json_encode(array("operaciones" => $operaciones));
        } else {
            $payload = json_encode(array("mensaje" => "Ocurrio un error al traer las operaciones."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public static function ObtenerDiasIngreso($request, $response, $args)
    {
        $params = $request->getParsedBody();

        if (isset($params['usuario'])) {
            $usuario = $params['usuario'];
            $dias = Archivos::obtenerDias($usuario);
            $payload = json_encode(array("Ingresos" => $dias));
        } else {
            $payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function verificarEmpleado($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $usuario = $params['usuario'];
        $clave = $params['clave'];

        $empleado = Empleado::obtenerEmpleado($usuario);
        $payload = json_encode(array('error' => 'El empleado no existe o la clave no coincide'));
        if (!is_null($empleado)) {
            if (password_verify($clave, $empleado->clave)) {
                $datosempleado = array(
                    'id' => $empleado->id,
                    'usuario' => $empleado->usuario,
                    'clave' => $empleado->clave,
                    'esAdmin' => $empleado->esAdmin,
                    'tipo' => $empleado->tipo
                );
                $payload = json_encode(array(
                    'Token' => JWTAuth::crearToken($datosempleado)
                ));

                if(Archivos::insertarLogin($empleado) > 0){
                    echo "Login registrado.";
                }
            }
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
