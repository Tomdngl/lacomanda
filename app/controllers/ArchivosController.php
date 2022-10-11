<?php
 require_once './models/Archivos.php';

 class ArchivosController extends Archivos{
    public function Guardar($request, $response, $args){
        $historialLogins = Archivos::obtenerLogins();
        $filename = './Archivos/logins.csv';
        $payload = json_encode(array("Error" => 'No se pudo guardar el registro.'));
        if(Archivos::guardarLoginsCsv($historialLogins, $filename)){
            $payload = json_encode(array("mensaje" => 'Historial guardado como csv en ' . $filename));
        }
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function Leer($request, $response, $args){
        $filename = './Archivos/logins.csv';
        $datosLectura = Archivos::leerLoginsCsv($filename);
        $payload = json_encode(array("Error" => 'No se pudo leer el registro'));
        if(!is_null($datosLectura)){
            $payload = json_encode(array("mensaje" => 'Historial leido correctamente', "Ingresos"=>$datosLectura));
        }        
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function DescargarFacturaPdf($request, $response, $args){
        $params = $request->getParsedBody();
        $id = $params["id"];
        ArchivosController::CrearFacturaPDF($id)->Output($id . '.pdf', 'I');
        
        return $response->withHeader("Content-Type", "application/pdf");
    }

    public function DescargarLogoPdf($request, $response, $args){
        ArchivosController::CrearLogoPDF()->Output('restaurantLogo' . '.pdf', 'I');
        
        return $response->withHeader("Content-Type", "application/pdf");
    }

    public static function CrearFacturaPDF($id){
        $comanda = Comanda::obtenerComanda($id);
        $productos = Producto::TraerProductos($id);
        $total = 0;
        $textoProducto = "<dl>";

        foreach ($productos as $producto){
            $textoProducto .= '<dt> x' . $producto->cantidad . ' ' . $producto->descripcion . '-------$' . $producto->precio . '</dt>';
            $total += $producto->precio;
        }
        $textoProducto .= "</dl>";
        
        $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false, true);
    
        $pdf->addPage();
        $texto = '<img src="./logo/logo.jpg" alt="logo" width="120" height="120" border="0" />
                    <h1>Factura</h1> <br>
                    Cliente: ' . $comanda->nombreCliente . '<br>
                    Fecha: ' . date("d-m-Y H:i:s") . '<br>
                    Comanda N°: ' . $id . '<br>
                    Mesa N°: ' . $comanda->mesaId .'<br> 
                    <ol>
                        
            
                        
                    ' . $textoProducto . '<br><br> Total:$' . $total;
                    $pdf->writeHTML($texto, true, false, true, false, '');
        
        ob_end_clean();

        return $pdf;
    }

    public static function CrearLogoPDF(){
        $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false, true);
    
        $pdf->addPage();
        $texto = '<img src="./logo/logo.jpg" alt="logo" width="510" height="510" border="0" />';                        
                    $pdf->writeHTML($texto, true, false, true, false, '');
        
        ob_end_clean();

        return $pdf;
    }
 }
