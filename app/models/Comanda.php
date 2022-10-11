<?php
require_once './models/Mesa.php';

class Comanda{
    public $id;
    public $mesaId;
    public $nombreCliente;
    public $foto;

    public function verificarDatosAlta($mesaId, $nombreCliente)
    {
        $isOk = true;
        if(strlen($nombreCliente) >= 50)
        {
            $isOk = false;
            throw new Exception('La longitud del nombre del cliente es demasiado larga. El limite es de 50 caracteres');
        }
        if(Mesa::obtenerMesa($mesaId) == null)
        {
            $isOk = false;
            throw new Exception('No existe una mesa con ese ID.');
        }
        return $isOk;
    }

    public function crearComanda(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO comandas (mesaId, nombreCliente, foto) 
        VALUES (:mesaId, :nombreCliente, :foto)');
        $consulta->bindValue(':mesaId', $this->mesa_id);
        $consulta->bindValue(':nombreCliente', $this->nombreCliente);
        $consulta->bindValue(':foto', null);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarComanda($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM `comandas` WHERE id = :id");
        $consulta->bindParam(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function obtenerComanda($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id = :id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();

        return $consulta->fetchObject('Comanda');
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Comanda');
    }

    public static function SubirArchivo($request, $comanda, $modif)
    {
        $archivos = $request->getUploadedFiles();

        if (!empty($archivos)) {
            $destino = __DIR__ . "/../FotosComanda/";
            $destinoBackup = __DIR__ . "/../FotosComanda/backup/";

            $nombreAnterior = $archivos['foto']->getClientFilename();
            $extension = explode(".", $nombreAnterior);
            $extension = array_reverse($extension);

            if ($modif) {
                $newPath = $comanda->id . $comanda->nombreCliente . "Backup" . "." . $extension[0];
                $archivos['foto']->moveTo($destinoBackup . $newPath);
            } else {
                $newPath = $comanda->id . $comanda->nombreCliente . "." . $extension[0];
                $archivos['foto']->moveTo($destino . $newPath);
            }

            self::ActualizarFotoBD($newPath, $comanda->id);
        }
    }

    public static function ObtenerMesaRelacionada($id)
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT DISTINCT mesas.id FROM comandas INNER JOIN mesas ON comandas.mesaId = mesas.id WHERE comandas.id = :comandaId");
        $consulta->bindValue(':comandaId', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");
    }

    public static function ActualizarFotoBD($newPath, $id)
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("UPDATE comandas SET foto = :foto WHERE id = :id");

        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':foto', $newPath, PDO::PARAM_STR);

        return $consulta->execute();
    }

    public static function VerificarEstadoMesa()
    {
        $accesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $accesoDatos->prepararConsulta("SELECT comandas.id, mesas.estado FROM comandas INNER JOIN mesas ON comandas.mesaId = mesas.id WHERE mesas.estado = 'Con cliente esperando pedido'");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");
    }
}
?>