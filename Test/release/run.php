<?php
/**
 * Pruebas de lanzamiento de fsRepublicaDominicana (flujo completo compras -> ventas -> reportes DGII).
 *
 * Crea datos con prefijo propio (RL) sobre una instalación de FacturaScripts con el plugin activo
 * y deja la evidencia en JSON para que evidence.mjs la complete con capturas y TXT.
 *
 * Uso (desde la carpeta de FacturaScripts o con FS_FOLDER):
 *   FS_FOLDER=/var/www/html php Plugins/fsRepublicaDominicana/Test/release/run.php [carpeta-evidencias]
 *
 * ADVERTENCIA: escribe datos reales en la base de datos. Úsese solo en una instalación de pruebas.
 */

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\CommonFunctionsDominicanRepublic;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Join\FiscalReport606;
use FacturaScripts\Dinamic\Model\Join\FiscalReport607;
use FacturaScripts\Dinamic\Model\Join\FiscalReport608;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;

if (PHP_SAPI !== 'cli') {
    exit('Solo por línea de comandos.');
}

define('FS_FOLDER', rtrim(getenv('FS_FOLDER') ?: getcwd(), '/'));
require_once FS_FOLDER . '/vendor/autoload.php';
require_once FS_FOLDER . '/config.php';
date_default_timezone_set('America/Santo_Domingo');
Kernel::init();
Plugins::init();
\FacturaScripts\Core\Cache::clear(); // traducciones y esquemas de tablas al día
$outDir = rtrim($argv[1] ?? (__DIR__ . '/evidencias/' . date('Ymd-His')), '/');
if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fwrite(STDERR, "No se pudo crear $outDir\n");
    exit(2);
}

final class ReleaseSuite
{
    public array $steps = [];
    public array $ctx = [];
    private ?array $current = null;
    public string $tag;

    public function __construct()
    {
        $this->tag = 'R' . strtoupper(base_convert((string)(time() % 1679616), 10, 36));
    }

    /** Ejecuta un caso; cualquier excepción o check fallido lo marca como FALLA. */
    public function step(string $id, string $title, callable $fn, array $needs = []): void
    {
        foreach ($needs as $key) {
            if (empty($this->ctx[$key])) {
                $this->steps[] = [
                    'id' => $id, 'titulo' => $title, 'estado' => 'OMITIDA',
                    'motivo' => "Falta el dato previo '$key' (falló un caso anterior)", 'checks' => [], 'docs' => [], 'logs' => [],
                ];
                $this->line('SKIP', $id, $title);
                return;
            }
        }
        MiniLog::clear();
        $this->current = ['id' => $id, 'titulo' => $title, 'estado' => 'OK', 'checks' => [], 'docs' => [], 'logs' => []];
        try {
            $fn($this);
        } catch (Throwable $e) {
            $this->current['estado'] = 'FALLA';
            $this->current['checks'][] = ['ok' => false, 'detalle' => 'Excepción: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'];
        }
        foreach (MiniLog::read('', ['error', 'critical', 'warning']) as $log) {
            $this->current['logs'][] = $log['message'];
        }
        foreach ($this->current['checks'] as $c) {
            if (!$c['ok'] && empty($c['aviso'])) {
                $this->current['estado'] = 'FALLA';
            } elseif (!$c['ok'] && $this->current['estado'] === 'OK') {
                $this->current['estado'] = 'ADVERTENCIA';
            }
        }
        $this->steps[] = $this->current;
        $label = ['OK' => 'OK  ', 'FALLA' => 'FAIL', 'ADVERTENCIA' => 'WARN'][$this->current['estado']];
        $this->line($label, $id, $title);
        foreach ($this->current['checks'] as $c) {
            if (!$c['ok']) {
                $this->line('     ', '', (empty($c['aviso']) ? '✗ ' : '⚠ ') . $c['detalle']);
            }
        }
        if ($this->current['estado'] === 'FALLA') {
            foreach (array_unique($this->current['logs']) as $log) {
                $this->line('     ', '', '· FS: ' . $log);
            }
        }
        $this->current = null;
    }

    public function check(bool $ok, string $detail): bool
    {
        $this->current['checks'][] = ['ok' => $ok, 'detalle' => $detail];
        return $ok;
    }

    /**
     * Comprobación de una carencia conocida del sistema: si no se cumple se informa como ADVERTENCIA y no
     * impide el lanzamiento, pero queda en el informe.
     */
    public function warn(bool $ok, string $detail): bool
    {
        $this->current['checks'][] = ['ok' => $ok, 'detalle' => $detail, 'aviso' => true];
        return $ok;
    }

    /** Registra el documento como evidencia (el script de capturas lo abre por URL). */
    public function doc(string $kind, $model, array $extra = []): void
    {
        $listing = [
            'FacturaCliente' => 'EditFacturaCliente', 'FacturaProveedor' => 'EditFacturaProveedor',
            'PedidoCliente' => 'EditPedidoCliente', 'PedidoProveedor' => 'EditPedidoProveedor',
            'AlbaranCliente' => 'EditAlbaranCliente', 'AlbaranProveedor' => 'EditAlbaranProveedor',
            'Cliente' => 'EditCliente', 'Proveedor' => 'EditProveedor', 'Producto' => 'EditProducto',
        ];
        $this->current['docs'][] = array_merge([
            'tipo' => $kind,
            'controlador' => $listing[$kind] ?? null,
            'id' => $model->id(),
            'codigo' => $model->codigo ?? $model->codcliente ?? $model->codproveedor ?? $model->referencia ?? null,
            'ncf' => $model->numeroncf ?? null,
            'tipocomprobante' => $model->tipocomprobante ?? null,
            'divisa' => $model->coddivisa ?? null,
            'total' => isset($model->total) ? (float)$model->total : null,
        ], $extra);
    }

    private function line(string $status, string $id, string $text): void
    {
        echo sprintf("%s %-4s %s\n", $status, $id, $text);
    }
}

$s = new ReleaseSuite();
$tag = $s->tag;
$today = date('Y-m-d');
$empresa = 1;

/** Línea de producto con cantidad y precio, y recálculo del documento. */
function addLine($doc, string $ref, float $qty, ?float $price = null): void
{
    $line = $doc->getNewProductLine($ref);
    $line->cantidad = $qty;
    if ($price !== null) {
        $line->pvpunitario = $price;
    }
    if (!$line->save()) {
        throw new RuntimeException("No se pudo guardar la línea de $ref");
    }
    $lines = $doc->getLines();
    Calculator::calculate($doc, $lines, true);
}

function setCurrency($doc, string $code): void
{
    $div = new Divisa();
    if (!$div->load($code)) {
        throw new RuntimeException("Divisa $code no existe");
    }
    $doc->coddivisa = $code;
    $doc->tasaconv = $div->tasaconv;
}

function generateNext($from, string $class, string $label): object
{
    $gen = new BusinessDocumentGenerator();
    if (!$gen->generate($from, $class)) {
        throw new RuntimeException("No se pudo generar $label");
    }
    $docs = $gen->getLastDocs();
    if (empty($docs)) {
        throw new RuntimeException("El generador no devolvió $label");
    }
    return $docs[0];
}

function statusByName($doc, string $name): ?int
{
    foreach ($doc->getAvailableStatus() as $st) {
        if ($st->nombre === $name) {
            return (int)$st->idestado;
        }
    }
    return null;
}


/** Número de comprobante digitado por el proveedor (11 caracteres: B + tipo + 8 dígitos). */
function supplierNcf(string $tipo = '01'): string
{
    return 'B' . $tipo . str_pad((string)random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
}

/**
 * Factura de compra directa con líneas [referencia, cantidad, precio, [propiedades de la línea]].
 * Si se indica $orig, es una rectificativa de esa factura (nota de crédito o débito).
 */
function newPurchase(ReleaseSuite $s, Proveedor $prov, array $lines, string $tipo = '01', $orig = null, bool $emitir = true): FacturaProveedor
{
    $fp = new FacturaProveedor();
    $s->check($fp->setSubject($prov), 'Proveedor asignado');
    $fp->tipocomprobante = $tipo;
    $fp->numeroncf = supplierNcf($tipo);
    $fp->numproveedor = $fp->numeroncf;
    $fp->ncftipopago = '01';
    $fp->ncftipomovimiento = '09';
    if ($orig) {
        $fp->codigorect = $orig->codigo;
        $fp->idfacturarect = $orig->idfactura;
    }
    $_REQUEST['numeroncfr'] = $fp->numeroncf; // el NCF de una compra lo digita el usuario en el formulario
    $saved = $fp->save();
    unset($_REQUEST['numeroncfr']);
    $s->check($saved, "Factura de compra tipo $tipo guardada");
    foreach ($lines as [$ref, $qty, $price, $props]) {
        $line = $ref ? $fp->getNewProductLine($ref) : $fp->getNewLine();
        if (!$ref) {
            $line->descripcion = $props['descripcion'] ?? 'Ajuste por importe';
            $line->codimpuesto = 'ITBIS18';
            $line->iva = 18;
        }
        $line->cantidad = $qty;
        $line->pvpunitario = $price;
        foreach ($props as $prop => $value) {
            if ($prop !== 'descripcion') {
                $line->{$prop} = $value;
            }
        }
        if (!$line->save()) {
            throw new RuntimeException('No se pudo guardar una línea de compra');
        }
    }
    $all = $fp->getLines();
    Calculator::calculate($fp, $all, true);
    if ($emitir) {
        $s->check(emit($fp), 'Factura de compra recibida');
    }
    $fp->loadFromCode($fp->id());
    return $fp;
}

/** ¿FacturaScripts registró un error con alguno de estos textos durante el caso actual? */
function logged(string ...$needles): bool
{
    foreach (MiniLog::read('', ['error', 'critical', 'warning']) as $log) {
        foreach ($needles as $needle) {
            if (stripos((string)$log['message'], $needle) !== false) {
                return true;
            }
        }
    }
    return false;
}

/** Factura de venta directa con líneas [referencia, cantidad, precio, [propiedades de la línea]]. */
function newSale(ReleaseSuite $s, Cliente $cli, array $lines, string $tipo = '01', $orig = null, bool $emitir = true): FacturaCliente
{
    $f = new FacturaCliente();
    $s->check($f->setSubject($cli), 'Cliente asignado');
    $f->tipocomprobante = $tipo;
    $f->ncftipopago = '17';
    $f->ncftipomovimiento = '1';
    if ($orig) {
        $f->codigorect = $orig->codigo;
        $f->idfacturarect = $orig->idfactura;
    }
    $s->check($f->save(), "Factura de venta tipo $tipo guardada");
    foreach ($lines as [$ref, $qty, $price, $props]) {
        $line = $ref ? $f->getNewProductLine($ref) : $f->getNewLine();
        if (!$ref) {
            $line->descripcion = $props['descripcion'] ?? 'Ajuste por importe';
            $line->codimpuesto = 'ITBIS18';
            $line->iva = 18;
        }
        $line->cantidad = $qty;
        $line->pvpunitario = $price;
        foreach ($props as $prop => $value) {
            if ($prop !== 'descripcion') {
                $line->{$prop} = $value;
            }
        }
        if (!$line->save()) {
            throw new RuntimeException('No se pudo guardar una línea de venta');
        }
    }
    $all = $f->getLines();
    Calculator::calculate($f, $all, true);
    if ($emitir) {
        $s->check(emit($f), 'Factura de venta emitida');
    }
    $f->loadFromCode($f->id());
    return $f;
}

/** Proveedor o cliente nuevo con retención por defecto. */
function newSubject(ReleaseSuite $s, string $kind, string $code, string $name, ?string $retention, string $tipoComprobante = '01')
{
    $m = $kind === 'prov' ? new Proveedor() : new Cliente();
    $codeField = $kind === 'prov' ? 'codproveedor' : 'codcliente';
    $m->{$codeField} = $code;
    $m->nombre = $m->razonsocial = $name;
    $m->cifnif = '1' . str_pad((string)random_int(10000000, 99999999), 8, '0');
    $m->tipoidfiscal = 'RNC';
    $m->codretencion = $retention;
    if ($kind === 'prov') {
        $m->ncftipopago = '04';
    } else {
        $m->tipocomprobante = $tipoComprobante;
        $m->ncftipopago = '17';
    }
    $s->check($m->save(), ($kind === 'prov' ? 'Proveedor ' : 'Cliente ') . "$name guardado" . ($retention ? " con retención $retention" : ''));
    return $m;
}

/** Emite el documento (estado 'Emitida' o 'Recibida'), como cuando el usuario pulsa el botón de estado. */
function emit($doc): bool
{
    $doc->loadFromCode($doc->id());
    $actual = new EstadoDocumento();
    // Ventas: 'Emitida'; compras: 'Recibida'
    $names = ['Emitida', 'Recibida'];
    if ($actual->load($doc->idestado) && in_array($actual->nombre, $names, true)) {
        return true;
    }
    $id = null;
    foreach ($names as $name) {
        $id = $id ?? statusByName($doc, $name);
    }
    if ($id === null) {
        return false;
    }
    $doc->idestado = $id;
    return $doc->save();
}

/** Rangos de NCF necesarios; se crean o se amplían si faltan o les quedan pocos números. */
function ensureRanges(): void
{
    foreach (['01', '02', '03', '04', '14', '15'] as $tipo) {
        $r = new NCFRango();
        $existing = $r->getByTipoComprobante(1, $tipo);
        if ($existing) {
            if ($existing->secuenciafin - $existing->correlativo < 60) {
                $existing->secuenciafin = $existing->correlativo + 500;
                $existing->save();
            }
            continue;
        }
        $any = $r->all([Where::eq('idempresa', 1), Where::eq('tipocomprobante', $tipo), Where::eq('serie', 'B')]);
        $r = $any[0] ?? $r;
        $r->idempresa = 1;
        $r->serie = 'B';
        $r->tipocomprobante = $tipo;
        $r->autorizacion = $r->autorizacion ?: 123456789;
        $r->solicitud = $r->solicitud ?: 987654321;
        $r->secuenciainicio = $r->secuenciainicio ?: 1;
        $r->correlativo = $r->correlativo ?: 1;
        $r->secuenciafin = $r->correlativo + 500;
        $r->puntoemision = $r->puntoemision ?: '001';
        $r->areaimpresion = $r->areaimpresion ?: '001';
        $r->estado = true;
        $r->fechacreacion = $r->fechacreacion ?: date('Y-m-d');
        $r->fechavencimiento = (date('Y') + 1) . '-12-31';
        $r->usuariocreacion = $r->usuariocreacion ?: 'admin';
        $r->save();
    }
}
ensureRanges();

// ---------------------------------------------------------------- COMPRAS
$s->step('C01', 'Creación de proveedores', function (ReleaseSuite $s) use ($tag) {
    $p1 = new Proveedor();
    $p1->codproveedor = $tag . 'P1';
    $p1->nombre = $p1->razonsocial = 'Suministros Release SRL';
    $p1->cifnif = '1' . str_pad((string)random_int(10000000, 99999999), 8, '0');
    $p1->tipoidfiscal = 'RNC';
    $p1->ncftipopago = '04';
    $s->check($p1->save(), 'Proveedor con RNC guardado');
    $s->doc('Proveedor', $p1);

    $p2 = new Proveedor();
    $p2->codproveedor = $tag . 'P2';
    $p2->nombre = $p2->razonsocial = 'Proveedor Persona Física Release';
    $p2->cifnif = '001' . str_pad((string)random_int(1000000, 9999999), 8, '0');
    $p2->tipoidfiscal = 'Cédula';
    $p2->ncftipopago = '04';
    $s->check($p2->save(), 'Proveedor con cédula guardado');
    $s->doc('Proveedor', $p2);
    $s->ctx['prov'] = $p1;
    $s->ctx['prov2'] = $p2;
});

$s->step('C02', 'Creación de artículos: mercancía y servicio', function (ReleaseSuite $s) use ($tag) {
    $items = [
        ['mercancia', $tag . '-M', 'Mercancía de prueba de lanzamiento', 1000.0, false],
        ['servicio', $tag . '-S', 'Servicio de prueba de lanzamiento', 2500.0, true],
    ];
    foreach ($items as [$key, $ref, $desc, $price, $nostock]) {
        $p = new Producto();
        $p->referencia = $ref;
        $p->descripcion = $desc;
        $p->precio = $price;
        $p->nostock = $nostock;
        $p->esservicio = $nostock;
        $p->codimpuesto = 'ITBIS18';
        $s->check($p->save(), "Producto $ref (" . ($nostock ? 'servicio, sin stock' : 'mercancía, con stock') . ') guardado');
        $s->check($p->codimpuesto === 'ITBIS18', "$ref con ITBIS 18%");
        $s->doc('Producto', $p);
        $s->ctx[$key] = $p;
    }
}, []);

$s->step('C03', 'Creación de pedido de compra', function (ReleaseSuite $s) {
    $pedido = new PedidoProveedor();
    $s->check($pedido->setSubject($s->ctx['prov']), 'Proveedor asignado');
    $s->check($pedido->save(), 'Pedido guardado');
    addLine($pedido, $s->ctx['mercancia']->referencia, 10, 800.0);
    addLine($pedido, $s->ctx['servicio']->referencia, 1, 2000.0);
    $pedido->loadFromCode($pedido->id());
    $s->check(abs($pedido->neto - 10000.0) < 0.01, "Neto del pedido = 10,000.00 (obtenido {$pedido->neto})");
    $s->check(abs($pedido->total - 11800.0) < 0.01, "Total con ITBIS 18% = 11,800.00 (obtenido {$pedido->total})");
    $s->doc('PedidoProveedor', $pedido);
    $s->ctx['pedidoprov'] = $pedido;
}, ['prov', 'mercancia', 'servicio']);

$s->step('C04', 'Creación de albarán de compra (desde el pedido)', function (ReleaseSuite $s) {
    $alb = generateNext($s->ctx['pedidoprov'], 'AlbaranProveedor', 'el albarán de compra');
    $s->check($alb instanceof AlbaranProveedor, 'Se generó un AlbaranProveedor');
    $s->check(abs($alb->total - 11800.0) < 0.01, "Total heredado = 11,800.00 (obtenido {$alb->total})");
    $s->doc('AlbaranProveedor', $alb);
    $s->ctx['albprov'] = $alb;
}, ['pedidoprov']);

$s->step('C05', 'Creación de factura de compra en pesos dominicanos', function (ReleaseSuite $s) use ($tag) {
    $fp = generateNext($s->ctx['albprov'], 'FacturaProveedor', 'la factura de compra');
    $fp->tipocomprobante = '01';
    $fp->numeroncf = 'B01' . str_pad((string)random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    $fp->numproveedor = $fp->numeroncf;
    $fp->ncftipopago = '01';
    $fp->ncftipomovimiento = '09';
    $s->check($fp->save(), 'Factura de compra DOP con NCF del proveedor guardada');
    $fp->loadFromCode($fp->id());
    $s->check($fp->coddivisa === 'DOP' || $fp->coddivisa === (string)\FacturaScripts\Core\Tools::settings('default', 'coddivisa'), "Divisa {$fp->coddivisa}");
    $s->check(abs($fp->total - 11800.0) < 0.01, "Total = 11,800.00 (obtenido {$fp->total})");
    $s->doc('FacturaProveedor', $fp);
    $s->ctx['fprov'] = $fp;
}, ['albprov']);

$s->step('C06', 'Creación de factura de compra en dólares', function (ReleaseSuite $s) {
    $fp = new FacturaProveedor();
    $s->check($fp->setSubject($s->ctx['prov']), 'Proveedor asignado');
    setCurrency($fp, 'USD');
    $fp->tipocomprobante = '01';
    $fp->numeroncf = 'B01' . str_pad((string)random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    $fp->numproveedor = $fp->numeroncf;
    $fp->ncftipopago = '01';
    $fp->ncftipomovimiento = '09';
    $s->check($fp->save(), 'Factura de compra USD guardada');
    addLine($fp, $s->ctx['mercancia']->referencia, 2, 100.0);
    $s->check(emit($fp), 'Factura de compra emitida');
    $fp->loadFromCode($fp->id());
    $s->check($fp->coddivisa === 'USD', 'Divisa USD');
    $s->check(abs($fp->total - 236.0) < 0.01, "Total = 236.00 USD (obtenido {$fp->total})");
    $s->doc('FacturaProveedor', $fp);
    $s->ctx['fprovusd'] = $fp;
}, ['prov', 'mercancia']);

$s->step('C07', 'Pago de factura de compra', function (ReleaseSuite $s) {
    $fp = $s->ctx['fprov'];
    $fp->loadFromCode($fp->id());
    $receipts = $fp->getReceipts();
    $s->check(count($receipts) > 0, 'La factura tiene recibos');
    foreach ($receipts as $r) {
        $r->pagado = true;
        $s->check($r->save(), "Recibo {$r->idrecibo} marcado como pagado");
    }
    $fp->loadFromCode($fp->id());
    $s->check((bool)$fp->pagada, 'La factura quedó pagada');
    $s->doc('FacturaProveedor', $fp, ['nota' => 'pagada']);
}, ['fprov']);

$s->step('C08', 'Creación de nota de crédito de compra', function (ReleaseSuite $s) {
    $orig = $s->ctx['fprovusd'];
    $nc = new FacturaProveedor();
    $nc->loadFromData($orig->toArray(), $orig::dontCopyFields());
    $nc->codigorect = $orig->codigo;
    $nc->idfacturarect = $orig->idfactura;
    $nc->tipocomprobante = '04';
    $nc->numeroncf = 'B04' . str_pad((string)random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    $nc->numproveedor = $nc->numeroncf;
    $_REQUEST['numeroncfr'] = $nc->numeroncf; // el NCF de una compra lo digita el usuario en el formulario
    $s->check($nc->save(), 'Nota de crédito de compra guardada');
    unset($_REQUEST['numeroncfr']);
    foreach ($orig->getLines() as $line) {
        $nl = $nc->getNewLine($line->toArray());
        $nl->cantidad = -1 * (float)$line->cantidad;
        $nl->idlinearect = $line->idlinea;
        $s->check($nl->save(), 'Línea negativa guardada');
    }
    $lines = $nc->getLines();
    Calculator::calculate($nc, $lines, true);
    $s->check(emit($nc), 'Nota de crédito de compra emitida');
    $nc->loadFromCode($nc->id());
    $s->check($nc->total < 0, "Total negativo (obtenido {$nc->total})");
    $s->check($nc->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->doc('FacturaProveedor', $nc);
    $s->ctx['ncprov'] = $nc;
}, ['fprovusd']);

$s->step('C09', 'Compra con impuestos adicionales: ISC (cigarrillos) y CDT (servicio)', function (ReleaseSuite $s) {
    $fp = newPurchase($s, $s->ctx['prov'], [
        [$s->ctx['mercancia']->referencia, 10, 100.0, ['rdtaxcodisc' => '019']],
        [$s->ctx['servicio']->referencia, 1, 2000.0, ['rdtaxcodcdt' => '002']],
    ]);
    $lines = $fp->getLines();
    $s->check(abs($lines[0]->rdtaxiscespecifico - 535.10) < 0.01, "ISC específico = 10 × 53.51 = 535.10 (obtenido {$lines[0]->rdtaxiscespecifico})");
    $s->check(abs($lines[0]->rdtaxiscadvalorem - 200.0) < 0.01, "ISC ad valorem 20 % de 1,000 = 200.00 (obtenido {$lines[0]->rdtaxiscadvalorem})");
    $s->check(abs($lines[1]->rdtaxcdtmonto - 40.0) < 0.01, "CDT 2 % de 2,000 = 40.00 (obtenido {$lines[1]->rdtaxcdtmonto})");
    $s->check(abs($fp->neto - 3000.0) < 0.01 && abs($fp->totaliva - 540.0) < 0.01, "Neto 3,000.00 e ITBIS 540.00 (obtenido {$fp->neto} / {$fp->totaliva})");
    $s->check(abs($fp->totaladdedtaxes - 775.10) < 0.01, "Impuestos adicionales del documento = 775.10 (obtenido {$fp->totaladdedtaxes})");
    $s->doc('FacturaProveedor', $fp);
    $s->ctx['fprovisc'] = $fp;
}, ['prov', 'mercancia', 'servicio']);

$s->step('C10', 'Compra con retención de ITBIS 30 %', function (ReleaseSuite $s) use ($tag) {
    $prov = newSubject($s, 'prov', $tag . 'P3', 'Proveedor Retención ITBIS Release', 'RETEN30');
    $s->doc('Proveedor', $prov);
    $fp = newPurchase($s, $prov, [[$s->ctx['mercancia']->referencia, 5, 1000.0, []]]);
    $s->check(abs($fp->totaliva - 900.0) < 0.01, "ITBIS 900.00 (obtenido {$fp->totaliva})");
    $s->check(abs($fp->rdtotalretitbis - 270.0) < 0.01, "ITBIS retenido = 30 % del ITBIS = 270.00 (obtenido {$fp->rdtotalretitbis})");
    $s->check(abs($fp->rdtotalretisr) < 0.01, 'Sin retención de ISR');
    $s->check(abs($fp->total - 5630.0) < 0.01, "Total = 5,900.00 − 270.00 = 5,630.00 (obtenido {$fp->total})");
    $s->doc('FacturaProveedor', $fp);
    $s->ctx['fprovret30'] = $fp;
}, ['mercancia']);

$s->step('C11', 'Compra de servicios con retención de ISR (honorarios 10 %)', function (ReleaseSuite $s) use ($tag) {
    $prov = newSubject($s, 'prov', $tag . 'P4', 'Proveedor Honorarios Release', 'RETISR2');
    $s->doc('Proveedor', $prov);
    $fp = newPurchase($s, $prov, [[$s->ctx['servicio']->referencia, 1, 10000.0, []]]);
    $s->check(abs($fp->rdtotalretisr - 1000.0) < 0.01, "ISR retenido = 10 % del neto = 1,000.00 (obtenido {$fp->rdtotalretisr})");
    $s->check($fp->rdtiporetisr === '2', "Tipo de retención en ISR = 2 (honorarios), obtenido '{$fp->rdtiporetisr}'");
    $s->check(abs($fp->rdtotalretitbis) < 0.01, 'Sin retención de ITBIS');
    $s->check(abs($fp->total - 10800.0) < 0.01, "Total = 11,800.00 − 1,000.00 = 10,800.00 (obtenido {$fp->total})");
    $s->doc('FacturaProveedor', $fp);
    $s->ctx['fprovisr'] = $fp;
}, ['servicio']);

$s->step('C12', 'Nota de crédito de compra por importe (sin producto)', function (ReleaseSuite $s) {
    $orig = $s->ctx['fprov'];
    $nc = newPurchase($s, $s->ctx['prov'], [[null, -1, 200.0, ['descripcion' => 'Descuento por pronto pago']]], '04', $orig);
    $s->check(abs($nc->neto + 200.0) < 0.01 && abs($nc->total + 236.0) < 0.01, "Neto −200.00 y total −236.00 (obtenido {$nc->neto} / {$nc->total})");
    $s->check($nc->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->check(str_starts_with((string)$nc->numeroncf, 'B04'), "NCF de nota de crédito B04… (obtenido {$nc->numeroncf})");
    $s->doc('FacturaProveedor', $nc);
    $s->ctx['ncprovimporte'] = $nc;
}, ['prov', 'fprov']);

$s->step('C13', 'Nota de débito de compra por importe (sin producto)', function (ReleaseSuite $s) {
    $orig = $s->ctx['fprov'];
    $nd = newPurchase($s, $s->ctx['prov'], [[null, 1, 150.0, ['descripcion' => 'Intereses por mora']]], '03', $orig);
    $s->check(abs($nd->neto - 150.0) < 0.01 && abs($nd->total - 177.0) < 0.01, "Neto 150.00 y total 177.00 (obtenido {$nd->neto} / {$nd->total})");
    $s->check($nd->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->check(str_starts_with((string)$nd->numeroncf, 'B03'), "NCF de nota de débito B03… (obtenido {$nd->numeroncf})");
    $s->doc('FacturaProveedor', $nd);
    $s->ctx['ndprovimporte'] = $nd;
}, ['prov', 'fprov']);

// ----------------------------------------------------------------- VENTAS
$s->step('V01', 'Cliente tipo consumidor final', function (ReleaseSuite $s) use ($tag) {
    $c = new Cliente();
    $c->codcliente = $tag . 'C2';
    $c->nombre = $c->razonsocial = 'Consumidor Final Release';
    $c->cifnif = '001' . str_pad((string)random_int(1000000, 9999999), 8, '0');
    $c->tipoidfiscal = 'Cédula';
    $c->tipocomprobante = '02';
    $c->ncftipopago = '17';
    $s->check($c->save(), 'Cliente consumidor final guardado');
    $s->check($c->tipocomprobante === '02', 'Tipo de comprobante por defecto 02');
    $s->doc('Cliente', $c);
    $s->ctx['cli02'] = $c;
});

$s->step('V02', 'Cliente con factura con valor fiscal (crédito fiscal)', function (ReleaseSuite $s) use ($tag) {
    $c = new Cliente();
    $c->codcliente = $tag . 'C1';
    $c->nombre = $c->razonsocial = 'Comercial Release SRL';
    $c->cifnif = '1' . str_pad((string)random_int(10000000, 99999999), 8, '0');
    $c->tipoidfiscal = 'RNC';
    $c->tipocomprobante = '01';
    $c->ncftipopago = '17';
    $s->check($c->save(), 'Cliente de crédito fiscal guardado');
    $s->doc('Cliente', $c);
    $s->ctx['cli01'] = $c;
});

$s->step('V03', 'Cliente con factura de regímenes especiales', function (ReleaseSuite $s) use ($tag) {
    $c = new Cliente();
    $c->codcliente = $tag . 'C14';
    $c->nombre = $c->razonsocial = 'Zona Franca Release SRL';
    $c->cifnif = '1' . str_pad((string)random_int(10000000, 99999999), 8, '0');
    $c->tipoidfiscal = 'RNC';
    $c->tipocomprobante = '14';
    $c->ncftipopago = '17';
    $s->check($c->save(), 'Cliente de regímenes especiales guardado');
    $s->doc('Cliente', $c);
    $s->ctx['cli14'] = $c;
});

$s->step('V04', 'Creación de pedido de venta', function (ReleaseSuite $s) {
    $p = new PedidoCliente();
    $s->check($p->setSubject($s->ctx['cli01']), 'Cliente asignado');
    $s->check($p->save(), 'Pedido de venta guardado');
    addLine($p, $s->ctx['mercancia']->referencia, 5, 1000.0);
    addLine($p, $s->ctx['servicio']->referencia, 1, 2500.0);
    $p->loadFromCode($p->id());
    $s->check(abs($p->total - 8850.0) < 0.01, "Total = 8,850.00 (obtenido {$p->total})");
    $s->doc('PedidoCliente', $p);
    $s->ctx['pedidocli'] = $p;
}, ['cli01', 'mercancia', 'servicio']);

$s->step('V05', 'Creación de albarán de venta (desde el pedido)', function (ReleaseSuite $s) {
    $a = generateNext($s->ctx['pedidocli'], 'AlbaranCliente', 'el albarán de venta');
    $s->check($a instanceof AlbaranCliente, 'Se generó un AlbaranCliente');
    $s->check(abs($a->total - 8850.0) < 0.01, "Total heredado = 8,850.00 (obtenido {$a->total})");
    $s->doc('AlbaranCliente', $a);
    $s->ctx['albcli'] = $a;
}, ['pedidocli']);

/** Factura de venta directa de un tipo de comprobante (opcionalmente en otra divisa). */
$invoice = static function (ReleaseSuite $s, string $clientKey, string $tipo, ?string $currency, string $ctxKey): FacturaCliente {
    $f = new FacturaCliente();
    $s->check($f->setSubject($s->ctx[$clientKey]), 'Cliente asignado');
    if ($currency) {
        setCurrency($f, $currency);
    }
    $f->tipocomprobante = $tipo;
    $f->ncftipopago = '17';
    $f->ncftipomovimiento = '1';
    $s->check($f->save(), "Factura tipo $tipo guardada");
    addLine($f, $s->ctx['mercancia']->referencia, 2, 1000.0);
    $s->check(emit($f), 'Factura emitida');
    $f->loadFromCode($f->id());
    $s->check(strlen((string)$f->numeroncf) === 11 && str_starts_with((string)$f->numeroncf, 'B' . $tipo), "NCF asignado B$tipo… (obtenido {$f->numeroncf})");
    $s->check(abs($f->total - 2360.0) < 0.01, "Total = 2,360.00 (obtenido {$f->total})");
    if ($currency) {
        $s->check($f->coddivisa === $currency, "Divisa $currency");
    }
    $s->doc('FacturaCliente', $f);
    $s->ctx[$ctxKey] = $f;
    return $f;
};

$s->step('V06', 'Factura de consumidor final (02)', fn(ReleaseSuite $s) => $invoice($s, 'cli02', '02', null, 'f02'), ['cli02', 'mercancia']);
$s->step('V07', 'Factura de crédito fiscal (01)', fn(ReleaseSuite $s) => $invoice($s, 'cli01', '01', null, 'f01'), ['cli01', 'mercancia']);
$s->step('V08', 'Factura de regímenes especiales (14)', fn(ReleaseSuite $s) => $invoice($s, 'cli14', '14', null, 'f14'), ['cli14', 'mercancia']);
$s->step('V09', 'Factura de consumidor final en dólares', fn(ReleaseSuite $s) => $invoice($s, 'cli02', '02', 'USD', 'f02usd'), ['cli02', 'mercancia']);
$s->step('V10', 'Factura de consumidor final en euros', fn(ReleaseSuite $s) => $invoice($s, 'cli02', '02', 'EUR', 'f02eur'), ['cli02', 'mercancia']);

$s->step('V11', 'Nota de crédito de venta (04)', function (ReleaseSuite $s) {
    $orig = $s->ctx['f01'];
    $nc = new FacturaCliente();
    $nc->loadFromData($orig->toArray(), $orig::dontCopyFields());
    $nc->codigorect = $orig->codigo;
    $nc->idfacturarect = $orig->idfactura;
    $nc->ncfcodigomodificacion = null;
    $nc->tipocomprobante = null; // como la acción de reembolso del núcleo: el plugin debe asignar el 04
    $s->check($nc->save(), 'Nota de crédito de venta guardada');
    foreach ($orig->getLines() as $line) {
        $nl = $nc->getNewLine($line->toArray());
        $nl->cantidad = -1 * (float)$line->cantidad;
        $nl->idlinearect = $line->idlinea;
        $s->check($nl->save(), 'Línea negativa guardada');
    }
    $lines = $nc->getLines();
    Calculator::calculate($nc, $lines, true);
    $s->check(emit($nc), 'Nota de crédito emitida');
    $nc->loadFromCode($nc->id());
    $s->check($nc->total < 0, "Total negativo (obtenido {$nc->total})");
    $s->check(str_starts_with((string)$nc->numeroncf, 'B04'), "NCF de nota de crédito B04… (obtenido {$nc->numeroncf})");
    $s->check($nc->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->doc('FacturaCliente', $nc);
    $s->ctx['ncvta'] = $nc;
}, ['f01']);

$s->step('V12', 'Nota de débito de venta (03)', function (ReleaseSuite $s) {
    $orig = $s->ctx['f01'];
    $nd = new FacturaCliente();
    $s->check($nd->setSubject($s->ctx['cli01']), 'Cliente asignado');
    $nd->codigorect = $orig->codigo;
    $nd->idfacturarect = $orig->idfactura;
    $nd->tipocomprobante = '03';
    $nd->ncftipopago = '17';
    $nd->ncftipomovimiento = '1';
    $s->check($nd->save(), 'Nota de débito de venta guardada');
    addLine($nd, $s->ctx['servicio']->referencia, 1, 500.0);
    $s->check(emit($nd), 'Nota de débito emitida');
    $nd->loadFromCode($nd->id());
    $s->check($nd->total > 0, "Total positivo (obtenido {$nd->total})");
    $s->check(str_starts_with((string)$nd->numeroncf, 'B03'), "NCF de nota de débito B03… (obtenido {$nd->numeroncf})");
    $s->doc('FacturaCliente', $nd);
    $s->ctx['ndvta'] = $nd;
}, ['f01', 'cli01', 'servicio']);

$s->step('V13', 'Anulación de factura de venta con valor fiscal', function (ReleaseSuite $s) {
    $f = new FacturaCliente();
    $s->check($f->setSubject($s->ctx['cli01']), 'Cliente asignado');
    $f->tipocomprobante = '01';
    $f->ncftipopago = '17';
    $f->ncftipomovimiento = '1';
    $s->check($f->save(), 'Factura 01 a anular guardada');
    addLine($f, $s->ctx['servicio']->referencia, 1, 1000.0);
    $s->check(emit($f), 'Factura emitida antes de anularla');
    $f->loadFromCode($f->id());
    $ncf = $f->numeroncf;
    $s->ctx['ncf_anulado'] = $ncf;
    $statusId = statusByName($f, 'Anulada');
    $s->check($statusId !== null, "Estado 'Anulada' disponible");
    $f->idestado = $statusId;
    $s->check($f->save(), 'Factura anulada');
    $f->loadFromCode($f->id());
    $estado = new EstadoDocumento();
    $estado->load($f->idestado);
    $s->check($estado->nombre === 'Anulada', "Estado final: {$estado->nombre}");
    $s->doc('FacturaCliente', $f, ['nota' => 'anulada', 'ncf_anulado' => $ncf]);
    $s->ctx['fanulada'] = $f;
}, ['cli01', 'servicio']);

$s->step('V14', 'Venta con impuestos adicionales: ISC (cigarrillos) y propina legal (servicio)', function (ReleaseSuite $s) {
    $f = newSale($s, $s->ctx['cli01'], [
        [$s->ctx['mercancia']->referencia, 10, 100.0, ['rdtaxcodisc' => '019']],
        [$s->ctx['servicio']->referencia, 1, 2000.0, ['rdtaxcodlegaltip' => '001']],
    ]);
    $lines = $f->getLines();
    $s->check(abs($lines[0]->rdtaxiscespecifico - 535.10) < 0.01, "ISC específico = 535.10 (obtenido {$lines[0]->rdtaxiscespecifico})");
    $s->check(abs($lines[0]->rdtaxiscadvalorem - 200.0) < 0.01, "ISC ad valorem = 200.00 (obtenido {$lines[0]->rdtaxiscadvalorem})");
    $s->check(abs($lines[1]->rdtaxlegaltipmonto - 200.0) < 0.01, "Propina legal 10 % de 2,000 = 200.00 (obtenido {$lines[1]->rdtaxlegaltipmonto})");
    $s->check(abs($f->neto - 3000.0) < 0.01 && abs($f->totaliva - 540.0) < 0.01, "Neto 3,000.00 e ITBIS 540.00 (obtenido {$f->neto} / {$f->totaliva})");
    $s->check(abs($f->totaladdedtaxes - 935.10) < 0.01, "Impuestos adicionales del documento = 935.10 (obtenido {$f->totaladdedtaxes})");
    $s->doc('FacturaCliente', $f);
    $s->ctx['fvtaisc'] = $f;
}, ['cli01', 'mercancia', 'servicio']);

$s->step('V15', 'Venta con retención de ITBIS 100 %', function (ReleaseSuite $s) use ($tag) {
    $cli = newSubject($s, 'cli', $tag . 'C5', 'Agente de Retención ITBIS Release', 'RETEN100');
    $s->doc('Cliente', $cli);
    $f = newSale($s, $cli, [[$s->ctx['mercancia']->referencia, 2, 1000.0, []]]);
    $s->check(abs($f->rdtotalretitbis - 360.0) < 0.01, "ITBIS retenido = 100 % del ITBIS = 360.00 (obtenido {$f->rdtotalretitbis})");
    $s->check(abs($f->total - 2000.0) < 0.01, "Total = 2,360.00 − 360.00 = 2,000.00 (obtenido {$f->total})");
    $s->doc('FacturaCliente', $f);
    $s->ctx['fvtaret100'] = $f;
}, ['mercancia']);

$s->step('V16', 'Venta de servicios con retención de ISR (honorarios 10 %)', function (ReleaseSuite $s) use ($tag) {
    $cli = newSubject($s, 'cli', $tag . 'C6', 'Cliente Retención ISR Release', 'RETISR2');
    $s->doc('Cliente', $cli);
    $f = newSale($s, $cli, [[$s->ctx['servicio']->referencia, 1, 5000.0, []]]);
    $s->check(abs($f->rdtotalretisr - 500.0) < 0.01, "ISR retenido = 10 % del neto = 500.00 (obtenido {$f->rdtotalretisr})");
    $s->check($f->rdtiporetisr === '2', "Tipo de retención en ISR = 2, obtenido '{$f->rdtiporetisr}'");
    $s->check(abs($f->total - 5400.0) < 0.01, "Total = 5,900.00 − 500.00 = 5,400.00 (obtenido {$f->total})");
    $lines = $f->getLines();
    $servicios = 0.0;
    $bienes = 0.0;
    foreach ($lines as $line) {
        $prod = new Producto();
        $prod->loadWhereEq('referencia', $line->referencia);
        $prod->esservicio ? $servicios += $line->pvptotal : $bienes += $line->pvptotal;
    }
    $s->check(abs($servicios - 5000.0) < 0.01 && abs($bienes) < 0.01, "Clasificación de la venta: servicios 5,000.00 y bienes 0.00 (obtenido $servicios / $bienes)");
    $s->doc('FacturaCliente', $f);
    $s->ctx['fvtaret10'] = $f;
}, ['servicio']);

$s->step('V17', 'Nota de crédito de venta por importe (sin producto)', function (ReleaseSuite $s) {
    $orig = $s->ctx['fvtaisc'];
    $nc = newSale($s, $s->ctx['cli01'], [[null, -1, 500.0, ['descripcion' => 'Descuento comercial posterior']]], '04', $orig);
    $s->check(abs($nc->neto + 500.0) < 0.01 && abs($nc->total + 590.0) < 0.01, "Neto −500.00 y total −590.00 (obtenido {$nc->neto} / {$nc->total})");
    $s->check($nc->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->check(str_starts_with((string)$nc->numeroncf, 'B04'), "NCF de nota de crédito B04… (obtenido {$nc->numeroncf})");
    $s->check(!empty($nc->idasiento), 'Genera su asiento contable');
    $s->doc('FacturaCliente', $nc);
    $s->ctx['ncvtaimporte'] = $nc;
}, ['fvtaisc', 'cli01']);

$s->step('V18', 'Nota de débito de venta por importe (sin producto)', function (ReleaseSuite $s) {
    $orig = $s->ctx['f01'];
    $nd = newSale($s, $s->ctx['cli01'], [[null, 1, 300.0, ['descripcion' => 'Intereses por mora']]], '03', $orig);
    $s->check(abs($nd->neto - 300.0) < 0.01 && abs($nd->total - 354.0) < 0.01, "Neto 300.00 y total 354.00 (obtenido {$nd->neto} / {$nd->total})");
    $s->check($nd->idfacturarect == $orig->idfactura, 'Referencia a la factura original');
    $s->check(str_starts_with((string)$nd->numeroncf, 'B03'), "NCF de nota de débito B03… (obtenido {$nd->numeroncf})");
    $s->doc('FacturaCliente', $nd);
    $s->ctx['ndvtaimporte'] = $nd;
}, ['f01', 'cli01']);

$s->step('V19', 'Controles de la nota de crédito de venta (importe positivo, mayor que la factura y acumulado)', function (ReleaseSuite $s) {
    // Importe positivo: una nota de crédito debe restar
    $pos = newSale($s, $s->ctx['cli01'], [[null, 1, 500.0, ['descripcion' => 'Descuento con signo positivo']]], '04', $s->ctx['f02'], false);
    $s->check($pos->neto <= 0, "La nota de crédito con importe positivo no se guarda (neto en base de datos {$pos->neto})");
    $s->check(logged('debe restar', 'must subtract'), 'Se avisa al usuario que la nota debe restar');
    $pos->delete();
    // Importe mayor que la factura original
    $orig = $s->ctx['f02'];
    $big = newSale($s, $s->ctx['cli01'], [[null, -1, 50000.0, ['descripcion' => 'Descuento mayor que la factura']]], '04', $orig, false);
    $s->check(abs($big->total) <= abs($orig->total) && abs($big->total) < 0.01, "La nota de crédito mayor que la factura ({$orig->total}) no se guarda (total en base de datos {$big->total})");
    $s->check(logged('supera el saldo', 'exceeds the balance'), 'Se avisa al usuario que supera la factura');
    $big->delete();
    // Acumulado: dos notas que juntas superan la factura
    $first = newSale($s, $s->ctx['cli01'], [[null, -1, 1500.0, ['descripcion' => 'Primera nota (1,770.00)']]], '04', $orig, false);
    $s->check(abs($first->total + 1770.0) < 0.01, "Primera nota de 1,770.00 sobre la factura de {$orig->total} se guarda (obtenido {$first->total})");
    $second = newSale($s, $s->ctx['cli01'], [[null, -1, 600.0, ['descripcion' => 'Segunda nota (708.00)']]], '04', $orig, false);
    $s->check(abs($second->total) < 0.01, "La segunda nota de 708.00 supera el saldo de 590.00 y no se guarda (total en base de datos {$second->total})");
    $s->check(logged('supera el saldo', 'exceeds the balance'), 'Se avisa del saldo acreditado');
    $second->delete();
    $first->delete();
}, ['f02', 'cli01']);

$s->step('C14', 'Controles de la nota de crédito de compra (importe positivo y mayor que la factura)', function (ReleaseSuite $s) {
    $orig = $s->ctx['fprovusd'];
    $pos = newPurchase($s, $s->ctx['prov'], [[null, 1, 20.0, ['descripcion' => 'Descuento con signo positivo']]], '04', $orig, false);
    $s->check($pos->neto <= 0, "La nota de crédito de compra con importe positivo no se guarda (neto en base de datos {$pos->neto})");
    $pos->delete();
    $big = newPurchase($s, $s->ctx['prov'], [[null, -1, 5000.0, ['descripcion' => 'Descuento mayor que la factura']]], '04', $orig, false);
    $s->check(abs($big->total) < 0.01, "La nota de crédito de compra mayor que la factura no se guarda (total en base de datos {$big->total})");
    $s->check(logged('supera el saldo', 'exceeds the balance'), 'Se avisa al usuario que supera la factura');
    $big->delete();
}, ['prov', 'fprovusd']);

// --------------------------------------------------------------- REPORTES
$s->step('R01', 'Reportes 606, 607 y 608 (incluida la anulación) y archivos TXT', function (ReleaseSuite $s) use ($today, $outDir) {
    $month = date('m');
    $year = date('Y');
    $range = static fn(string $table) => [Where::gte("$table.fecha", date('Y-m-01')), Where::lte("$table.fecha", date('Y-m-t'))];
    $ncfs = static fn(array $rows, string $field) => array_map(static fn($r) => $r->{$field}, $rows);

    $r606 = (new FiscalReport606())->all($range('facturasprov'));
    $r607 = (new FiscalReport607())->all($range('facturascli'));
    $r608 = (new FiscalReport608())->all($range('facturascli'));
    $n606 = $ncfs($r606, 'ncf');
    $n607 = $ncfs($r607, 'ncf');
    $n608 = $ncfs($r608, 'ncf');

    $s->check(in_array($s->ctx['fprov']->numeroncf, $n606, true), '606 incluye la factura de compra en pesos');
    $s->check(in_array($s->ctx['fprovusd']->numeroncf, $n606, true), '606 incluye la factura de compra en dólares');
    $s->check(in_array($s->ctx['ncprov']->numeroncf, $n606, true), '606 incluye la nota de crédito de compra');
    foreach (['f02' => '02', 'f01' => '01', 'f14' => '14', 'f02usd' => '02 USD', 'f02eur' => '02 EUR', 'ncvta' => 'nota de crédito', 'ndvta' => 'nota de débito'] as $key => $label) {
        $s->check(in_array($s->ctx[$key]->numeroncf, $n607, true), "607 incluye la factura $label ({$s->ctx[$key]->numeroncf})");
    }
    $anulado607 = array_values(array_filter($r607, static fn($r) => $r->ncf === $s->ctx['ncf_anulado']))[0] ?? null;
    $s->check($anulado607 === null || ((float)$anulado607->base == 0.0 && (float)$anulado607->itbis == 0.0), '607 no suma montos del NCF anulado');
    $s->check(in_array($s->ctx['ncf_anulado'], $n608, true), "608 incluye el NCF anulado ({$s->ctx['ncf_anulado']})");
    $s->check(count($n608) >= 1, 'El 608 solo lista documentos anulados');

    $common = new CommonFunctionsDominicanRepublic();
    $rnc = (string)\FacturaScripts\Core\Tools::settings('default', 'cifnif') ?: '131000001';
    $files = [];
    foreach (['606' => 'facturasprov', '607' => 'facturascli', '608' => 'facturascli'] as $code => $table) {
        $file = "$outDir/DGII_F_{$code}_{$year}{$month}.TXT";
        $common->exportTXT($code, $file, $rnc, $year, $month, $range($table));
        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $s->check(count($lines) >= 2, "TXT $code generado con " . count($lines) . ' líneas (cabecera + detalle)');
        $s->check(str_starts_with($lines[0] ?? '', $code . '|'), "TXT $code inicia con el identificador del formato");
        $files[] = basename($file);
    }
    $txt607 = (string)file_get_contents("$outDir/DGII_F_607_{$year}{$month}.TXT");
    $ncLine = array_values(array_filter(explode("\n", $txt607), static fn($l) => str_contains($l, '|' . $s->ctx['ncvta']->numeroncf . '|')))[0] ?? '';
    $s->check((explode('|', $ncLine)[3] ?? '') === $s->ctx['f01']->numeroncf, 'El TXT del 607 enlaza la nota de crédito con el NCF modificado');
    $s->check(!str_contains($txt607, $s->ctx['ncf_anulado']), 'El TXT del 607 NO contiene el NCF anulado');
    $txt608 = file_get_contents("$outDir/DGII_F_608_{$year}{$month}.TXT");
    $s->check(str_contains($txt608, $s->ctx['ncf_anulado']), 'El TXT del 608 contiene el NCF anulado');
    $GLOBALS['report_summary'] = [
        '606' => count($r606), '607' => count($r607), '608' => count($r608), 'txt' => $files,
        'periodo' => "$year-$month",
    ];
});

$s->step('R02', 'Reportes: notas positivas, impuestos adicionales, retenciones y servicios/bienes', function (ReleaseSuite $s) use ($outDir) {
    $range = static fn(string $table) => [Where::gte("$table.fecha", date('Y-m-01')), Where::lte("$table.fecha", date('Y-m-t'))];
    $find = static function (array $rows, string $ncf) {
        foreach ($rows as $row) {
            if ($row->ncf === $ncf) {
                return $row;
            }
        }
        return null;
    };
    $near = static fn($value, float $expected) => $value !== null && abs((float)$value - $expected) < 0.01;
    $r606 = (new FiscalReport606())->all($range('facturasprov'));
    $r607 = (new FiscalReport607())->all($range('facturascli'));
    $month = date('Ym');
    $txt606 = array_map(static fn($l) => explode('|', rtrim($l)), file("$outDir/DGII_F_606_$month.TXT") ?: []);
    $txt607 = array_map(static fn($l) => explode('|', rtrim($l)), file("$outDir/DGII_F_607_$month.TXT") ?: []);
    $line = static function (array $txt, string $ncf, int $col) {
        foreach ($txt as $fields) {
            if (($fields[$col] ?? null) === $ncf) {
                return $fields;
            }
        }
        return [];
    };

    // --- 606: notas en positivo y clasificación de servicios y bienes
    $row = $find($r606, $s->ctx['ncprov']->numeroncf);
    $s->check($row && $near($row->base, 200.0) && $near($row->itbis, 36.0), "606: la nota de crédito de compra va en positivo (base {$row?->base}, ITBIS {$row?->itbis})");
    $s->check($row && $near($row->totalbienes, 200.0) && $near($row->totalservicios, 0.0), '606: la nota de crédito clasifica 200.00 en bienes y 0.00 en servicios');
    $t = $line($txt606, $s->ctx['ncprov']->numeroncf, 3);
    $s->check(($t[9] ?? '') === '200.00' && ($t[10] ?? '') === '36.00' && ($t[8] ?? '') === '200.00', 'TXT 606: la nota de crédito sale con base, ITBIS y bienes en positivo');
    $row = $find($r606, $s->ctx['fprov']->numeroncf);
    $s->check($row && $near($row->totalservicios, 2000.0) && $near($row->totalbienes, 8000.0), "606: factura mixta = servicios 2,000.00 y bienes 8,000.00 (obtenido {$row?->totalservicios} / {$row?->totalbienes})");

    // --- 606: impuestos adicionales
    $row = $find($r606, $s->ctx['fprovisc']->numeroncf);
    $s->check($row && $near($row->isc, 735.10), "606: ISC = 735.10 (obtenido {$row?->isc})");
    $s->check($row && $near($row->otrosimpuestos, 40.0), "606: otros impuestos (CDT) = 40.00 (obtenido {$row?->otrosimpuestos})");
    $s->check($row && $near($row->totalservicios, 2000.0) && $near($row->totalbienes, 1000.0), '606: ISC/CDT con servicios 2,000.00 y bienes 1,000.00');

    // --- 606: retenciones
    $row = $find($r606, $s->ctx['fprovret30']->numeroncf);
    $s->check($row && $near($row->itbisretenido, 270.0), "606: ITBIS retenido = 270.00 (obtenido {$row?->itbisretenido})");
    $t = $line($txt606, $s->ctx['fprovret30']->numeroncf, 3);
    $s->check(($t[11] ?? '') === '270.00', 'TXT 606: ITBIS retenido en la posición 12');
    $row = $find($r606, $s->ctx['fprovisr']->numeroncf);
    $s->check($row && $near($row->rentaretenido, 1000.0) && (string)$row->tiporetencionisr === '2', "606: renta retenida 1,000.00 con tipo 2 (obtenido {$row?->rentaretenido} / {$row?->tiporetencionisr})");
    $s->check($row && $near($row->totalservicios, 10000.0) && $near($row->totalbienes, 0.0), '606: la compra de servicios clasifica 10,000.00 en servicios');
    $t = $line($txt606, $s->ctx['fprovisr']->numeroncf, 3);
    $s->check(($t[16] ?? '') === '2' && ($t[17] ?? '') === '1000.00', 'TXT 606: tipo de retención (17) y renta retenida (18)');

    // --- 606: notas por importe
    $row = $find($r606, $s->ctx['ncprovimporte']->numeroncf);
    $s->check($row && $near($row->base, 200.0) && $row->ncfmodifica === $s->ctx['fprov']->numeroncf, "606: la nota de crédito por importe va en positivo y modifica {$s->ctx['fprov']->numeroncf}");
    $row = $find($r606, $s->ctx['ndprovimporte']->numeroncf);
    $s->check($row && $near($row->base, 150.0) && $row->ncfmodifica === $s->ctx['fprov']->numeroncf, '606: la nota de débito por importe va en positivo y modifica la factura');
    $s->check($row && $near($row->totalbienes + $row->totalservicios, 150.0), '606: una nota por importe sin producto se informa como bienes (clasificación por defecto)');

    // --- 607: impuestos adicionales, retenciones y notas por importe
    $row = $find($r607, $s->ctx['fvtaisc']->numeroncf);
    $s->check($row && $near($row->isc, 735.10) && $near($row->propinalegal, 200.0), "607: ISC 735.10 y propina legal 200.00 (obtenido {$row?->isc} / {$row?->propinalegal})");
    $row = $find($r607, $s->ctx['fvtaret100']->numeroncf);
    $s->check($row && $near($row->itbisretenido, 360.0) && !empty($row->fecharetencion), "607: ITBIS retenido por terceros 360.00 con fecha de retención (obtenido {$row?->itbisretenido})");
    $t = $line($txt607, $s->ctx['fvtaret100']->numeroncf, 2);
    $s->check(($t[9] ?? '') === '360.00' && ($t[6] ?? '') !== '', 'TXT 607: ITBIS retenido (10) y fecha de retención (7)');
    $row = $find($r607, $s->ctx['fvtaret10']->numeroncf);
    $s->check($row && $near($row->rentaretenido, 500.0), "607: renta retenida por terceros 500.00 (obtenido {$row?->rentaretenido})");
    $t = $line($txt607, $s->ctx['fvtaret10']->numeroncf, 2);
    $s->check(($t[11] ?? '') === '500.00', 'TXT 607: renta retenida en la posición 12');
    $row = $find($r607, $s->ctx['ncvtaimporte']->numeroncf);
    $s->check($row && $near($row->base, 500.0) && $row->ncfmodifica === $s->ctx['fvtaisc']->numeroncf, "607: la nota de crédito por importe va en positivo y modifica {$s->ctx['fvtaisc']->numeroncf}");
    $row = $find($r607, $s->ctx['ndvtaimporte']->numeroncf);
    $s->check($row && $near($row->base, 300.0) && $row->ncfmodifica === $s->ctx['f01']->numeroncf, '607: la nota de débito por importe va en positivo y modifica la factura');
}, ['fprov', 'ncprov', 'fprovisc', 'fprovret30', 'fprovisr', 'ncprovimporte', 'ndprovimporte', 'fvtaisc', 'fvtaret100', 'fvtaret10', 'ncvtaimporte', 'ndvtaimporte']);

// ----------------------------------------------------------------- SALIDA
$summary = ['OK' => 0, 'ADVERTENCIA' => 0, 'FALLA' => 0, 'OMITIDA' => 0];
foreach ($s->steps as $st) {
    $summary[$st['estado']]++;
}
$result = [
    'fecha' => date('c'),
    'prefijo' => $tag,
    'plugin' => @parse_ini_file(dirname(__DIR__, 2) . '/facturascripts.ini') ?: null,
    'resumen' => $summary,
    'reportes' => $GLOBALS['report_summary'] ?? null,
    'pasos' => $s->steps,
];
file_put_contents("$outDir/resultados.json", json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo sprintf("\nResumen: %d OK, %d ADVERTENCIA, %d FALLA, %d OMITIDA — %s/resultados.json\n", $summary['OK'], $summary['ADVERTENCIA'], $summary['FALLA'], $summary['OMITIDA'], $outDir);
exit($summary['FALLA'] > 0 ? 1 : 0);
