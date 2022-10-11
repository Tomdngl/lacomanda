<?php
use Firebase\JWT\JWT;

class JWTAuth {

    private static $claveSecreta = 'J2jSD0';
    private static $encriptacion = ['HS256'];

    public static function crearToken($data) {
        $time_now = time();
        $payload = array(
            'iat' => $time_now,
            'exp' => $time_now + 3600*24,
            'aud' => self::Aud(),
            'data' => $data,
            'app' => "Test JWT"
        );
        return JWT::encode($payload, self::$claveSecreta);
    }

    public static function verifyToken($token) {
        if (empty($token)) {
            throw new Exception("El token esta vacío.");
        }
        try {
            $decoded = JWT::decode(
                $token,
                self::$claveSecreta,
                self::$encriptacion
            );

        } catch (Exception $e) {
            throw $e;
        }
        if ($decoded->aud !== self::Aud()) {
            throw new Exception("Usuario no válido");
        }
    }

    public static function getPayload($token) {
        if (empty($token)) {
            throw new Exception("El token esta vacío.");
        }
        return JWT::decode(
            $token,
            self::$claveSecreta,
            self::$encriptacion
        );
    }

    public static function obtenerDatos($token) {
        $array = JWT::decode(
            $token,
            self::$claveSecreta,
            self::$encriptacion
        )->data;
        return $array;
    }

    private static function Aud() {
        $aud = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud = $_SERVER['REMOTE_ADDR'];
        }

        $aud .= @$_SERVER['HTTP_USER_AGENT'];
        $aud .= gethostname();

        return sha1($aud);
    }
}
?>