<?php
class Empleado
{
    public $id;
    public $usuario;
    public $clave;
    public $nombre;
    public $tipo;
    public $estado;
    public $esAdmin;

    public function verificarDatosAlta($usuario, $clave, $nombre,$esAdmin, $tipo, $estado)
    {
        $isOk = true;
        if(strlen($usuario) >= 30 || strlen($clave) >= 30)
        {
            $isOk = false;
            throw new Exception('La longitud del usuario o contrasena es demasiado larga. El limite es de 30 caracteres');
        }
        if($esAdmin != 1 && $esAdmin != 0)
        {
            $isOk = false;
            throw new Exception('El valor del parametro "esAdmin" debe ser 0 o 1 [0 = No][1 = Si]');
        }
        if(strlen($nombre) >= 60)
        {
            $isOk = false;
            throw new Exception('La longitud del nombre es demasiado larga. El limite es de 60 caracteres');
        }
        if($tipo != "Mozo" && $tipo != "Cocinero" && $tipo != "Bartender" && $tipo != "Cervezero" && $tipo != "Socio")
        {
            $isOk = false;
            throw new Exception('El valor del parametro "tipo" debe ser uno de los siguientes: Mozo - Cocinero - Bartender - Cervezero - Socio');
        }
        if($estado != "Activo" && $estado != "Inactivo")
        {
            $isOk = false;
            throw new Exception('El valor del parametro "estado" debe ser uno de los siguientes: Activo - Inactivo');
        }
        return $isOk;
    }

    public function crearEmpleado()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO empleados (usuario, clave, nombre, tipo, estado, esAdmin, fechaAlta) VALUES (:usuario, :clave, :nombre, :tipo, :estado, :esAdmin, :fechaAlta)");
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':usuario', $this->usuario, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':esAdmin', $this->esAdmin, PDO::PARAM_STR);
        $consulta->bindValue(':fechaAlta', $this->fechaAlta, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM empleados");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Empleado');
    }

    public static function obtenerEmpleado($usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM empleados WHERE usuario = :usuario");
        $consulta->bindValue(':usuario', $usuario);
        $consulta->execute();

        return $consulta->fetchObject('Empleado');
    }

    public static function borrarUsuario($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE empleados SET fechaBaja = :fechaBaja WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', date("Y-m-d H:i:s"));
        $consulta->execute();
    }

    public function modificarUsuario($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE empleados SET usuario = :usuario, clave = :clave, nombre = :nombre, tipo= :tipo, esAdmin = :esAdmin WHERE id = :id");
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':usuario', $this->usuario, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':esAdmin', $this->esAdmin, PDO::PARAM_STR);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function suspenderUsuario($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE empleados SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':estado', "Inactivo", PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function obtenerInformacionToken($request, $response, $args)
    {
        $header = $request->getHeader('Authorization');
        $token = trim(str_replace("Bearer", "", $header[0]));
        $empleado = JWTAuth::obtenerDatos($token);
        
        return $empleado;
    }
}
