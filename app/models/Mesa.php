<?php
class Mesa{
    public $id;
    public $estado;
    public $codigoMesa;

    public function crearMesa(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO mesas (estado, codigoMesa) 
        VALUES (:estado, :codigoMesa)');
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':codigoMesa', $this->codigoMesa);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function borrarMesa($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM `mesas` WHERE id = :id");
        $consulta->bindParam(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function obtenerMesa($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id = :id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }

    public static function EntraCliente($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Con cliente esperando pedido");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }
 
    public static function Liberar($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Libre");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function Servir($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Con cliente comiendo");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function Pagar($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Con cliente pagando");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function Cerrar($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', "Cerrada");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function ObtenerMesaMasUsada($cantidad)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT
        comandas.mesaId,
        COUNT(comandas.mesaId) AS `vecesUsada`       
      FROM
        comandas      
      GROUP BY 
        comandas.mesaId      
      ORDER BY 
        `vecesUsada` DESC      
      LIMIT :cantidad");
        $consulta->bindValue(':cantidad', $cantidad);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");      
    }
}
