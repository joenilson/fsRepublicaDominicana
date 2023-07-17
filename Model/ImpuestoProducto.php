<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;


use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\Impuesto;

class ImpuestoProducto extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $codimpuesto;

    /** @var bool */
    public $compra;

    /** @var string */
    public $creationdate;

    /** @var int */
    public $id;

    /** @var int */
    public $idempresa;

    /** @var int */
    public $idproducto;

    /** @var string */
    public $lastnick;

    /** @var string */
    public $lastupdate;

    /** @var string */
    public $nick;

    /** @var bool */
    public $venta;

    /** @var @var int */
    public $porcentaje;

    public function clear()
    {
        parent::clear();
        $this->compra = false;
        $this->creationdate = date(self::DATETIME_STYLE);
        $this->idempresa = 0;
        $this->idproducto = null;
        $this->nick = Session::get('user')->nick ?? null;
        $this->venta = false;
        $this->porcentaje = 0;
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "impuestosproductos";
    }

    public function test(): bool
    {
        $this->codimpuesto = $this->toolBox()->utils()->noHtml($this->codimpuesto);
        $this->lastnick = $this->toolBox()->utils()->noHtml($this->lastnick);
        $this->nick = $this->toolBox()->utils()->noHtml($this->nick);
        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        $this->lastupdate = null;
        $this->lastnick = null;
        return parent::saveInsert($values);
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->lastupdate = date(self::DATETIME_STYLE);
        $this->lastnick = Session::get('user')->nick ?? null;
        return parent::saveUpdate($values);
    }

    public function getTaxByProduct($idproducto, $rdtaxid, $use = 'venta'): ?ImpuestoProducto
    {
        $dataBase = new DataBase();
        $sql = "SELECT * FROM impuestosproductos WHERE idproducto = " .
                self::toolBox()->utils()->intval($idproducto) .
                " AND codimpuesto = '" . self::toolBox()->utils()->normalize($rdtaxid) . "'" .
                " AND " . $use . " = true" . ";";
        $data = $dataBase->select($sql);
        if (empty($data) === true || in_array($data[0], [null, ''], true)) {
            return null;
        }
        $impuestos = new Impuesto();
        $impuesto = $impuestos->get($data[0]['codimpuesto']);
        $data[0]['porcentaje'] = $impuesto->iva;
        return new ImpuestoProducto($data[0]);
    }
}
