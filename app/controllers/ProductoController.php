<?php
require_once './models/Producto.php';

class ProductoController extends Producto
{
	public function CargarUno($request, $response, $args)
	{
		$params = $request->getParsedBody();

		if (
			isset($params['comandaAsociada']) && isset($params['descripcion'])
			&& isset($params['area']) && isset($params['precio']) &&
			isset($params['cantidad'])
		) {
			$comandaAsociada = $params['comandaAsociada'];
			$descripcion = $params['descripcion'];
			$area = $params['area'];
			$precio = $params['precio'];
			$cantidad = $params['cantidad'];
			$estado = "Pendiente";
			$horaInicio = date("Y-m-d H:i:s");
			$horaSalida = null;
			$horaEstimada = null;

			try {
				Producto::verificarDatosAlta($comandaAsociada, $descripcion, $area, $precio);
				$producto = new Producto();
				$producto->comandaAsociada = $comandaAsociada;
				$producto->descripcion = $descripcion;
				$producto->area = $area;
				$producto->precio = $precio;
				$producto->cantidad = $cantidad;
				$producto->estado = $estado;
				$producto->horaInicio = $horaInicio;
				$producto->horaSalida = $horaSalida;

				$productoId = $producto->crearProducto();

				if ($productoId > 0) {
					$payload = json_encode(array("mensaje" => "Producto insertado en la base de datos correctamente", "producto" => json_encode($producto)));
					//Registro accion
					$usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
					$area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
					Archivos::insertarAccion($usuario, "Agrega producto", $area);
				} else {
					$payload = json_encode(array("error" => "Ha ocurrido un error al insertar el producto en la base de datos"));
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

	public function verPendientes($request, $response, $args)
	{
		$tipoEmpleado = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
		echo $tipoEmpleado;
		switch ($tipoEmpleado) {
			case 'Cocinero':
				$lista = Producto::obtenerPorTipo("Cocina");
				break;
			case 'Cervezero':
				$lista = Producto::obtenerPorTipo("CervezaArtesanal");
				break;
			case 'Bartender':
				$lista = Producto::obtenerPorTipo("Barra");
				break;
			default:
				//Si es default significa que es socio por lo que puede ver todos los productos pendientes
				Producto::obtenerTodos();
				break;
		}
		$payload = json_encode(array("pendientes" => $lista));
		//Registro accion
		$usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
		$area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
		Archivos::insertarAccion($usuario, "Mira Pendientes", $area);

		$response->getBody()->write($payload);
		return $response
			->withHeader('Content-Type', 'application/json');
	}

	public function Preparar($request, $response, $args)
	{
		$params = $request->getParsedBody();
		$tipoEmpleado = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;

		if (isset($params['idProducto']) && isset($params['tiempoEstimado'])) {
			$id = $params['idProducto'];
			$minutosAñadidos = $params['tiempoEstimado'];
			$errorAreaMsj = "Este pedido no corresponde a su area por lo que no puede prepararlo";
			$exitoMsj = "Este pedido ahora se encuentra en preparacion. El tiempo estimado para su finalizacion es ";

			$producto = Producto::ObtenerProducto($id);

			$horaEstimada = $producto->horaInicio;
			$dt = new DateTime($horaEstimada);
			$dt->modify("+{$minutosAñadidos} minutes");

			switch ($tipoEmpleado) {
				case 'Cocinero':
					if ($producto->area == "Cocina" || $producto->area == "Candybar") {
						Producto::ActualizarEstado($dt, $id);
						$payload = json_encode(array("mensaje" => $exitoMsj . $horaEstimada));
					} else {
						$payload = json_encode(array("mensaje" => $errorAreaMsj));
					}
					break;
				case 'Cervezero':
					if ($producto->area == "CervezaArtesanal") {
						Producto::ActualizarEstado($dt, $id);
						$payload = json_encode(array("mensaje" => $exitoMsj . $horaEstimada));
					} else {
						$payload = json_encode(array("mensaje" => $errorAreaMsj));
					}
					break;
				case 'Bartender':
					if ($producto->area == "Barra") {
						Producto::ActualizarEstado($dt, $id);
						$payload = json_encode(array("mensaje" => $exitoMsj . $horaEstimada));
					} else {
						$payload = json_encode(array("mensaje" => $errorAreaMsj));
					}
					break;
				default:
					$payload = json_encode(array("mensaje" => "Solo un empleado capacitado puede preparar un pedido."));
					break;
			}
		} else {
			$payload = json_encode(array("mensaje" => "Faltan parametros para realizar esta accion."));
		}

		//Registro accion
		$usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
		$area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
		Archivos::insertarAccion($usuario, "Prepara pedido", $area);

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
                if (Producto::borrarProducto($id) > 0) {
                    $payload = json_encode(array("mensaje" => "Producto borrada de la base de datos"));
                } else {
                    $payload = json_encode(array("mensaje" => "Ocurrio un error al borrar el producto de la base de datos"));
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

	public function ListoParaServir($request, $response, $args)
	{
		$params = $request->getParsedBody();
		$tipoEmpleado = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;

		if (isset($params['idProducto'])) {
			$id = $params['idProducto'];
			$errorAreaMsj = "Este pedido no corresponde a su area por lo que no puede prepararlo";
			$exitoMsj = "Este pedido ahora se encuentra listo. Despachado a las ";

			$producto = Producto::ObtenerProducto($id);
			if($producto->estado == "En preparacion")
			{
				switch ($tipoEmpleado) {
					case 'Cocinero':
						if ($producto->area == "Cocina" || $producto->area == "Candybar") {
							Producto::Listo($id);
							$producto = Producto::ObtenerProducto($id);
							$payload = json_encode(array("mensaje" => $exitoMsj . $producto->horaSalida));
						} else {
							$payload = json_encode(array("mensaje" => $errorAreaMsj));
						}
						break;
					case 'Cervezero':
						if ($producto->area == "CervezaArtesanal") {
							Producto::Listo($id);
							$producto = Producto::ObtenerProducto($id);
							$payload = json_encode(array("mensaje" => $exitoMsj . $producto->horaSalida));
						} else {
							$payload = json_encode(array("mensaje" => $errorAreaMsj));
						}
						break;
					case 'Bartender':
						if ($producto->area == "Barra") {
							Producto::Listo($id);
							$producto = Producto::ObtenerProducto($id);
							$payload = json_encode(array("mensaje" => $exitoMsj . $producto->horaSalida));
						} else {
							$payload = json_encode(array("mensaje" => $errorAreaMsj));
						}
						break;
					default:
						$payload = json_encode(array("mensaje" => "Solo el empleado a cargo de este pedido puede realizar la acción."));
						break;
				}
				//Registro accion
				$usuario = Empleado::obtenerInformacionToken($request, $response, $args)->usuario;
				$area = Empleado::obtenerInformacionToken($request, $response, $args)->tipo;
				Archivos::insertarAccion($usuario, "Entrega pedido", $area);
			}
			else{
				$payload = json_encode(array("mensaje" => "Este pedido no ha sido tomado."));
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
		$lista = Producto::obtenerTodos();
		$payload = json_encode(array("Productos" => $lista));

		$response->getBody()->write($payload);
		return $response
			->withHeader('Content-Type', 'application/json');
	}

	public function MasVendidos($request, $response, $args)
	{
		$lista = Producto::TraerMasVendidos();
		$payload = json_encode(array("Productos" => $lista));

		$response->getBody()->write($payload);
		return $response
			->withHeader('Content-Type', 'application/json');
	}
}
