<?php
/**
 * Datos SINTÉTICOS para las capturas de la documentación.
 * Ningún dato corresponde a una empresa, persona o RNC reales.
 *
 * Uso: docker compose -f docs-tools/docker/docker-compose.yml exec -T web \
 *      php Plugins/fsRepublicaDominicana/docs-tools/docker/seed.php
 */

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;

const FS_FOLDER = '/var/www/html';
require_once FS_FOLDER . '/vendor/autoload.php';
require_once FS_FOLDER . '/config.php';
date_default_timezone_set('America/Santo_Domingo');
Kernel::init();
Plugins::init();

$ok = static fn(bool $r, string $what) => print(($r ? 'OK   ' : 'FAIL ') . $what . PHP_EOL);

// Rangos de NCF (serie B física, serie E electrónica)
$rangos = [
    ['B', '01', 1000], ['B', '02', 1000], ['B', '03', 100], ['B', '04', 100],
    ['B', '14', 100], ['B', '15', 100], ['B', '11', 100], ['B', '13', 100],
    ['E', '31', 1000], ['E', '32', 1000], ['E', '33', 100], ['E', '34', 100],
    ['E', '41', 100], ['E', '43', 100],
];
foreach ($rangos as [$serie, $tipo, $fin]) {
    $r = new NCFRango();
    if ($r->getByTipoComprobante(1, $tipo)) {
        continue;
    }
    $r->idempresa = 1;
    $r->serie = $serie;
    $r->tipocomprobante = $tipo;
    $r->autorizacion = 123456789;
    $r->solicitud = 987654321;
    $r->secuenciainicio = 1;
    $r->secuenciafin = $fin;
    $r->correlativo = 1;
    $r->puntoemision = '001';
    $r->areaimpresion = '001';
    $r->estado = true;
    $r->fechacreacion = date('Y-m-d');
    $r->fechavencimiento = '2027-12-31';
    $r->usuariocreacion = 'admin';
    $ok($r->save(), "rango $serie$tipo");
}

// Clientes
$clientes = [
    ['CLI0001', 'Comercial Demo SRL', '131000001', '01'],
    ['CLI0002', 'Cliente Final de Prueba', '00100000001', '02'],
];
foreach ($clientes as [$cod, $nombre, $cif, $tipo]) {
    $c = new Cliente();
    if ($c->load($cod)) {
        continue;
    }
    $c->codcliente = $cod;
    $c->nombre = $c->razonsocial = $nombre;
    $c->cifnif = $cif;
    $c->tipoidfiscal = strlen($cif) === 9 ? 'RNC' : 'Cédula';
    $c->tipocomprobante = $tipo;
    $ok($c->save(), "cliente $cod");
}

// Proveedor
$prov = new Proveedor();
if (!$prov->load('PRO0001')) {
    $prov->codproveedor = 'PRO0001';
    $prov->nombre = $prov->razonsocial = 'Suministros Demo SRL';
    $prov->cifnif = '132000002';
    $prov->tipoidfiscal = 'RNC';
    $ok($prov->save(), 'proveedor PRO0001');
}

// Productos
foreach ([['PRD-001', 'Producto de prueba', 1000.0], ['SRV-001', 'Servicio de consultoría', 2500.0]] as [$ref, $desc, $price]) {
    $p = new Producto();
    if ((new Producto())->loadWhereEq('referencia', $ref)) {
        continue;
    }
    $p->referencia = $ref;
    $p->descripcion = $desc;
    $p->precio = $price;
    $p->codimpuesto = 'ITBIS18';
    $ok($p->save(), "producto $ref");
}

// Facturas de venta (solo si aún no hay ninguna, para poder re-ejecutar el script)
foreach (FacturaCliente::count() > 0 ? [] : [['CLI0001', '01'], ['CLI0001', '31'], ['CLI0002', '02'], ['CLI0001', '33'], ['CLI0001', '34']] as [$cod, $tipo]) {
    $cliente = new Cliente();
    $cliente->load($cod);
    $f = new FacturaCliente();
    $f->setSubject($cliente);
    $f->tipocomprobante = $tipo;
    $f->ncftipopago = '17';
    $f->ncftipomovimiento = '1';
    if (!$f->save()) {
        $ok(false, "factura venta $tipo");
        continue;
    }
    if (in_array($tipo, ['33', '34'], true) && isset($ecf31)) {
        // Nota de débito/crédito electrónica: factura modificada + código de modificación
        $f->idfacturarect = $ecf31->idfactura;
        $f->codigorect = $ecf31->codigo;
        $f->ncfcodigomodificacion = $tipo === '33' ? '3' : '1';
        $f->save();
    }
    $line = $f->getNewProductLine('PRD-001');
    $line->cantidad = $tipo === '33' ? 1 : 2;
    $ok($line->save(), "linea venta $tipo");
    $lines = $f->getLines();
    Calculator::calculate($f, $lines, true);
    if ($tipo === '31') {
        $ecf31 = $f;
    }
    $ok(true, 'factura venta ' . $f->codigo . ' ' . $f->numeroncf);
}

// Factura de compra
$proveedor = new Proveedor();
$proveedor->load('PRO0001');
$fp = new FacturaProveedor();
if ($fp->loadWhereEq('numproveedor', 'B0100000001')) {
    exit(0);
}
$fp->setSubject($proveedor);
$fp->tipocomprobante = '01';
$fp->numeroncf = 'B0100000001';
$fp->numproveedor = 'B0100000001';
$fp->ncftipopago = '01';
$fp->ncftipomovimiento = '09';
if ($fp->save()) {
    $line = $fp->getNewProductLine('PRD-001');
    $line->cantidad = 5;
    $line->pvpunitario = 800.0;
    $line->save();
    $lines = $fp->getLines();
    Calculator::calculate($fp, $lines, true);
    $ok(true, 'factura compra ' . $fp->codigo);
} else {
    $ok(false, 'factura compra');
}
