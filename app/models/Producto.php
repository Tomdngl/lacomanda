<?php

use Illuminate\Support\Facades\Date;

class Producto
{
    public $id;
    public $comandaAsociada;
    public $descripcion;
    public $area;
    public $precio;
    public $estado;
    public $horaInicio;
    public $horaEstimada;
    public $horaSalida;

    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO productos (comandaAsociada, descripcion, area, precio, estado, cantidad, horaInicio) 
        VALUES (:comandaAsociada, :descripcion, :area, :precio, :estado, :cantidad, :horaInicio)');
        $consulta->bindValue(':comandaAsociada', $this->comandaAsociada);
        $consulta->bindValue(':descripcion', $this->descripcion);
        $consulta->bindValue(':area', $this->area);
        $consulta->bindValue(':precio', $this->precio);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':cantidad', $this->cantidad);
        $consulta->bindValue(':horaInicio', $this->horaInicio);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function verificarDatosAlta($comandaAsociada, $descripcion, $area, $precio)
    {
        $isOk = true;
        if (strlen($descripcion) >= 50) {
            $isOk = false;
            throw new Exception('La descripcion del producto es demasiado larga. El limite es de 50 caracteres');
        }
        if (Comanda::obtenerComanda($comandaAsociada) == null) {
            $isOk = false;
            throw new Exception('No existe una comanda con ese ID.');
        }
        if ($area != "Cocina" && $area != "Barra" && $area != "Candybar" && $area != "CervezaArtesanal") {
            $isOk = false;
            throw new Exception('El valor del parametro "area" debe ser uno de los siguientes: Cocina - Barra - Candybar - CervezaArtesanal');
        }
        if ($precio == 0) {
            $isOk = false;
            throw new Exception('El precio debe ser un valor numérico.');
        }
        return $isOk;
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerProducto($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE id = :id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }

    public static function obtenerPorTipo($area)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        if ($area == "Cocina" || $area == "Candybar") {
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE area = 'Cocina' AND estado = 'Pendiente' UNION SELECT * FROM productos WHERE area = 'Candybar' AND estado = 'Pendiente'");
        } else if ($area == "CervezaArtesanal") {
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE area = 'CervezaArtesanal' AND estado = 'Pendiente'");
        } else {
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE area = 'Barra' AND estado = 'Pendiente'");
        }
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerUltimoPreparado($id)
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT * FROM productos WHERE horaInicio = (SELECT MAX(horaInicio ) FROM productos) AND comandaAsociada = :comandaAsociada");
        $consulta->bindValue(':comandaAsociada', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }

    public static function borrarProducto($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM `productos` WHERE id = :id");
        $consulta->bindParam(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function ActualizarEstado($horaEstimada, $id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos SET horaEstimada = :horaEstimada, estado = :estado WHERE id = :id");
        $consulta->bindValue(':horaEstimada', date_format($horaEstimada, "Y-m-d H:i:s"));
        $consulta->bindValue(':estado', "En preparacion");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function Expirar($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Expirado");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function Listo($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos SET horaSalida = :horaSalida, estado = :estado WHERE id = :id");
        $consulta->bindValue(':horaSalida', date("Y-m-d H:i:s"));
        $consulta->bindValue(':estado', "Listo para servir");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function VerificarListos($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE productos.estado = 'Listo para servir' AND productos.comandaAsociada = :comandaAsociada");
        $consulta->bindValue(':comandaAsociada', $id);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "Producto");
    }

    public static function TraerProductos($comandaAsociada)
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT * FROM productos WHERE comandaAsociada = :comandaAsociada");
        $consulta->bindValue(':comandaAsociada', $comandaAsociada, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "Producto");
    }

    public static function TraerMasVendidos()
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT descripcion, SUM(cantidad) as vecesPedido FROM productos GROUP BY descripcion ORDER BY `vecesPedido` DESC");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");
    }

    public static function TraerFinalizados()
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT * FROM productos WHERE horaSalida IS NOT NULL");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "Producto");
    }
}
