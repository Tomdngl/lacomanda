<?php
class Archivos{
    public $id;
    public $usuario;
    public $hora;
    public $accion;
    public $area;

    public static function insertarLogin($usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO registroslogin (usuario, hora) 
        VALUES (:usuario, :hora)');
        $consulta->bindValue(':usuario', $usuario->usuario, PDO::PARAM_STR);
        $consulta->bindValue(':hora', date("Y-m-d H:i:s"), PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function insertarAccion($usuario, $accion, $area)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO registrosoperacion (usuario, hora, accion, area) 
        VALUES (:usuario, :hora, :accion, :area)');
        $consulta->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $consulta->bindValue(':hora', date("Y-m-d H:i:s"), PDO::PARAM_STR);
        $consulta->bindValue(':accion', $accion, PDO::PARAM_STR);
        $consulta->bindValue(':area', $area, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarRegistros()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('DELETE FROM registroslogin WHERE 1=1');        
        $consulta->execute();
        $consulta = $objAccesoDatos->prepararConsulta('ALTER TABLE registroslogin AUTO_INCREMENT = 1');
        $consulta->execute();

        return $consulta->rowCount() > 0;
    }

    public static function obtenerLogins()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM registroslogin");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Archivos');
    }

    public static function obtenerOperacionesSector($sector)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM registrosoperacion WHERE area = :area");
        $consulta->bindValue(':area', $sector, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Archivos');
    }

    public static function obtenerOperacionesAgrupadas()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM `registrosoperacion` ORDER BY area, usuario DESC");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Archivos');
    }

    public static function obtenerDias($usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM registroslogin WHERE usuario = :usuario");
        $consulta->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");
    }

    public static function guardarLoginsCsv($lista, $filename){
        $isOk = false;
        $destino = dirname($filename, 1);
        
        try {
            if(!file_exists($destino)){
                mkdir($destino, 0777, true);
            }
            $file = fopen($filename, "w");
            if ($file) {
                foreach ($lista as $item) {
                    $linea = $item->usuario . "," . $item->hora . PHP_EOL;
                    fwrite($file, $linea);
                    $isOk = true;
                }
            }
        } catch (Exception $e) {
            echo  $e->getMessage();
        }finally{
            fclose($file);
        }

        return $isOk;
    }

    public static function LeerLoginsCsv($filename){
        $file = fopen($filename, "r");
        $array = array();
        try {
            if (!is_null($file)){
                self::borrarRegistros(); 
            }
            while (!feof($file)) {
                $linea = fgets($file);                
                if (!empty($linea)) {
                    $linea = str_replace(PHP_EOL, "", $linea);
                    $loginsArray = explode(",", $linea);
                    $registro = new Archivos();
                    $registro->usuario = $loginsArray[0];
                    $registro->hora = $loginsArray[1];
                    array_push($array, $registro);
                    Archivos::insertarLogin($registro);
                }
            }
        } catch (Exception $e) {
            echo  $e->getMessage();
        }finally{
            fclose($file);
            return $array;
        }
    }
}
?>