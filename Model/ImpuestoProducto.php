<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;


use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
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

    public function clear(): void
    {
        parent::clear();
        $this->compra = false;
        $this->creationdate = Tools::dateTime();
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
        $this->codimpuesto = Tools::noHtml($this->codimpuesto);
        $this->lastnick = Tools::noHtml($this->lastnick);
        $this->nick = Tools::noHtml($this->nick);
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
        $this->lastupdate = Tools::dateTime();
        $this->lastnick = Session::get('user')->nick ?? null;
        return parent::saveUpdate($values);
    }

    public function getTaxByProduct($idproducto, $rdtaxid, $use = 'venta'): ?ImpuestoProducto
    {
        $dataBase = new DataBase();
        $sql = "SELECT * FROM impuestosproductos WHERE idproducto = " .
                (int)$idproducto .
                " AND codimpuesto = '" . $dataBase->escapeString($rdtaxid) . "'" .
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
