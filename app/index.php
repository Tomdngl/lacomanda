<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

require_once './db/AccesoDatos.php';

require_once './controllers/EmpleadoController.php';
require_once './controllers/ComandaController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/EncuestaController.php';
require_once './controllers/ArchivosController.php';

require_once './middlewares/MWPermisos.php';
require_once './middlewares/JWT.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();
$app->setBasePath('/app');

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

// Routes
$app->group('/empleados', function (RouteCollectorProxy $group) {
    $group->get('[/]', \EmpleadoController::class . ':TraerTodos');
    $group->post('[/]', \EmpleadoController::class . ':CargarUno');
    $group->put('/modificar', \EmpleadoController::class . ':ModificarUno');
    $group->put('[/]', \EmpleadoController::class . ':SuspenderUno');
    $group->post('/diasIngreso', \EmpleadoController::class . ':ObtenerDiasIngreso');
    $group->delete('[/]', \EmpleadoController::class . ':BorrarUno');
})->add(\MWPermisos::class . ':esAdmin');

$app->group('/comandas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ComandaController::class . ':TraerTodos')
        ->add(\MWPermisos::class . ':esMozo');
    $group->post('[/]', \ComandaController::class . ':CargarUno')
        ->add(\MWPermisos::class . ':esMozo');
    $group->post('/foto', \ComandaController::class . ':AgregarFoto')
        ->add(\MWPermisos::class . ':esMozo');
    $group->get('/listos', \ComandaController::class . ':VerificarListos')
        ->add(\MWPermisos::class . ':esMozo');
    $group->post('/cobrar', \ComandaController::class . ':CobrarCuenta')
        ->add(\MWPermisos::class . ':esMozo');
    $group->delete('[/]', \ComandaController::class . ':BorrarUno');
});

$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->post('[/]', \MesaController::class . ':CargarUno')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->get('[/]', \MesaController::class . ':TraerTodos')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->put('/cerrar', \MesaController::class . ':CerrarMesa')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->get('/facturas', \MesaController::class . ':TraerFacturas')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->post('/liberar', \MesaController::class . ':LiberarMesa')
        ->add(\MWPermisos::class . ':esMozo');
    $group->post('/traerEntreFechas', \MesaController::class . ':traerEntreFechas')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->delete('[/]', \MesaController::class . ':BorrarUno')
        ->add(\MWPermisos::class . ':esAdmin');
});

$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class . ':TraerTodos')
        ->add(\MWPermisos::class . ':esMozo');
    $group->post('[/]', \ProductoController::class . ':CargarUno')
        ->add(\MWPermisos::class . ':esMozo');
    $group->get('/pendientes', \ProductoController::class . ':VerPendientes')
        ->add(\MWPermisos::class . ':esEmpleado');
    $group->post('/preparar', \ProductoController::class . ':Preparar')
        ->add(\MWPermisos::class . ':esEmpleado');
    $group->put('/listo', \ProductoController::class . ':ListoParaServir')
        ->add(\MWPermisos::class . ':esEmpleado');
    $group->get('/masVendido', \ProductoController::class . ':masVendidos')
        ->add(\MWPermisos::class . ':esAdmin');
    $group->delete('[/]', \ProductoController::class . ':BorrarUno')
        ->add(\MWPermisos::class . ':esAdmin');
});

$app->group('/clientes', function (RouteCollectorProxy $group) {
    $group->post('/comanda', \ComandaController::class . ':ObtenerTiempoRestante');
    $group->post('/encuesta', \EncuestaController::class . ':MandarEncuesta');
});

$app->group('/socio', function (RouteCollectorProxy $group) {
    $group->post('/comentarios', \EncuestaController::class . ':MejoresComentarios');
    $group->post('/mesa', \MesaController::class . ':MesaMasUsada');
    $group->get('/tiempoFuera', \ComandaController::class . ':FueraDeTiempoEstipulado');
    $group->post('/facturapdf', \ArchivosController::class . ':DescargarFacturaPdf');
    $group->get('/logopdf', \ArchivosController::class . ':DescargarLogoPdf');
    $group->post('/traerOperacionesSector', \EmpleadoController::class . ':OperacionesPorSector');
    $group->get('/traerOperacionesAgrupadas', \EmpleadoController::class . ':OperacionesAgrupadas');
})->add(\MWPermisos::class . ':esAdmin');

$app->group('/archivos', function (RouteCollectorProxy $group) {
    $group->get('/guardar_csv', \ArchivosController::class . ':Guardar');
    $group->get('/leer_csv', \ArchivosController::class . ':Leer');
})->add(\MWPermisos::class . ':esAdmin');

$app->group('/login', function (RouteCollectorProxy $group) {
    $group->post('[/]', \EmpleadoController::class . ':verificarEmpleado');
});

$app->get('[/]', function (Request $request, Response $response) {
    $response->getBody()->write("Slim Framework 4 PHP");
    return $response;
});

$app->run();
