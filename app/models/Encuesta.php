<?php

class Encuesta{
    public $id;
    public $comandaAsociada;
    public $puntajeMesa;
    public $puntajeRestaurante;
    public $puntajeMozo;
    public $puntajeCocinero;
    public $comentario;
    public $promedio;

    public function crearEncuesta(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO encuesta (comandaAsociada, puntajeMesa, puntajeRestaurante, puntajeMozo, puntajeCocinero, comentario, promedio) 
        VALUES (:comandaAsociada, :puntajeMesa, :puntajeRestaurante, :puntajeMozo, :puntajeCocinero, :comentario, :promedio)');
        $consulta->bindValue(':comandaAsociada', $this->comandaAsociada);
        $consulta->bindValue(':puntajeMesa', $this->puntajeMesa);
        $consulta->bindValue(':puntajeRestaurante', $this->puntajeRestaurante);
        $consulta->bindValue(':puntajeMozo', $this->puntajeMozo);
        $consulta->bindValue(':puntajeCocinero', $this->puntajeCocinero);
        $consulta->bindValue(':comentario', $this->comentario);
        $consulta->bindValue(':promedio', $this->comentario);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function verificarDatosAlta($comentario)
    {
        $isOk = true;
        if (strlen($comentario) >= 66) {
            $isOk = false;
            throw new Exception('El comentario es demasiado largo. El limite es de 66 caracteres');
        }
        return $isOk;
    }

    public static function obtenerMejores($cantidad){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('SELECT * FROM encuesta ORDER BY promedio DESC LIMIT :cantidad');
        $consulta->bindParam(':cantidad', $cantidad);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Encuesta');
    }
}