<?php

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Session;

class ImpuestoAdicional extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $creationdate;

    /** @var int */
    public $id;

    /** @var string */
    public $codigo;

    /** @var string */
    public $tipo_impuesto_long;

    /** @var string */
    public $tipo_impuesto_short;

    /** @var string */
    public $tipo_tasa;

    /** @var float */
    public $tasa;

    /**
     * Base del cálculo cuando tipo_tasa es 'monto': 'unidad' o 'litros_alcohol_absoluto'.
     * @var string
     */
    public $base_calculo;

    /**
     * Tasa AdValorem (%) que se aplica junto con el monto específico (alcohol, cigarrillos).
     * @var float
     */
    public $tasa_advalorem;

    /**
     * Si es true, la base del AdValorem incluye el monto específico.
     * @var bool
     */
    public $advalorem_sobre_especifico;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $lastnick;

    /** @var string */
    public $lastupdate;

    /** @var string */
    public $nick;

    public function install(): string
    {
        parent::install();
        return "INSERT INTO " . self::tableName() . " (codigo, tipo_impuesto_short, tipo_impuesto_long, descripcion, tipo_tasa, tasa, nick, creationdate) VALUES " .
            "('001','Propina Legal','Propina Legal','Propina Legal','porcentaje',10,'install',CURRENT_DATE), " .
            "('002','CDT','Contribución al Desarrollo de las Telecomunicaciones','Contribución al Desarrollo de las Telecomunicaciones Ley 153-98 Art. 45 ','porcentaje',2,'install',CURRENT_DATE), " .
            "('003','ISC','Impuesto Selectivo al Consumo','Servicios Seguros en general','porcentaje',16,'install',CURRENT_DATE), " .
            "('004','ISC','Impuesto Selectivo al Consumo','Servicios de Telecomunicaciones','porcentaje',10,'install',CURRENT_DATE), " .
            "('005','Primera Placa','Impuesto sobre el Primer Registro de Vehículos (Primera Placa)','Expedición de la primera placa','porcentaje',17,'install',CURRENT_DATE), " .
            "('023','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Cerveza','porcentaje',10,'install',CURRENT_DATE), " .
            "('024','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Vinos de uva','porcentaje',10,'install',CURRENT_DATE), " .
            "('025','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Vermut y demás vinos de uvas frescas','porcentaje',10,'install',CURRENT_DATE), " .
            "('026','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Demás bebidas fermentadas','porcentaje',10,'install',CURRENT_DATE), " .
            "('027','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Alcohol Etílico sin desnaturalizar (Mayor o igual a 80%)','porcentaje',10,'install',CURRENT_DATE), " .
            "('028','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Alcohol Etílico sin desnaturalizar (inferior a 80%)','porcentaje',10,'install',CURRENT_DATE), " .
            "('029','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Aguardientes de uva','porcentaje',10,'install',CURRENT_DATE), " .
            "('030','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Whisky','porcentaje',10,'install',CURRENT_DATE), " .
            "('031','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Ron y demás aguardientes de caña','porcentaje',10,'install',CURRENT_DATE), " .
            "('032','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Gin y Ginebra','porcentaje',10,'install',CURRENT_DATE), " .
            "('033','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Vodka','porcentaje',10,'install',CURRENT_DATE), " .
            "('034','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Licores','porcentaje',10,'install',CURRENT_DATE), " .
            "('035','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Los demás (Bebidas y Alcoholes)','porcentaje',10,'install',CURRENT_DATE), " .
            "('036','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Cigarrillos que contengan tabaco cajetilla 20 unidades','porcentaje',20,'install',CURRENT_DATE), " .
            "('037','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Los demás Cigarrillos que contengan 20 unidades','porcentaje',20,'install',CURRENT_DATE), " .
            "('038','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Cigarrillos que contengan 10 unidades','porcentaje',20,'install',CURRENT_DATE), " .
            "('039','ISC','Impuesto Selectivo al Consumo (Tasa AdValorem)','Los demás Cigarrillos que contengan 10 unidades','porcentaje',20,'install',CURRENT_DATE); 
        ";
    }
    /**
     * ISC de tasa específica + AdValorem (alcohol y cigarrillos). La tasa específica se
     * ajusta cada año por inflación, por eso es editable en la pantalla de impuestos adicionales.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: float, 4: float, 5: float}>
     *         código, descripción, base de cálculo, tasa específica, tasa advalorem
     */
    public static function especificos(): array
    {
        $alcohol = 'litros_alcohol_absoluto';
        $tasaAlcohol = 632.58;
        return [
            ['006', 'Cerveza', $alcohol, $tasaAlcohol, 10.0],
            ['007', 'Vinos de uva', $alcohol, $tasaAlcohol, 10.0],
            ['008', 'Vermut y demás vinos de uvas frescas', $alcohol, $tasaAlcohol, 10.0],
            ['009', 'Demás bebidas fermentadas', $alcohol, $tasaAlcohol, 10.0],
            ['010', 'Alcohol Etílico sin desnaturalizar (Mayor o igual a 80%)', $alcohol, $tasaAlcohol, 10.0],
            ['011', 'Alcohol Etílico sin desnaturalizar (inferior a 80%)', $alcohol, $tasaAlcohol, 10.0],
            ['012', 'Aguardientes de uva', $alcohol, $tasaAlcohol, 10.0],
            ['013', 'Whisky', $alcohol, $tasaAlcohol, 10.0],
            ['014', 'Ron y demás aguardientes de caña', $alcohol, $tasaAlcohol, 10.0],
            ['015', 'Gin y Ginebra', $alcohol, $tasaAlcohol, 10.0],
            ['016', 'Vodka', $alcohol, $tasaAlcohol, 10.0],
            ['017', 'Licores', $alcohol, $tasaAlcohol, 10.0],
            ['018', 'Los demás (Bebidas y Alcoholes)', $alcohol, $tasaAlcohol, 10.0],
            ['019', 'Cigarrillos que contengan tabaco cajetilla 20 unidades', 'unidad', 53.51, 20.0],
            ['020', 'Los demás Cigarrillos que contengan 20 unidades', 'unidad', 53.51, 20.0],
            ['021', 'Cigarrillos que contengan 10 unidades', 'unidad', 26.75, 20.0],
            ['022', 'Los demás Cigarrillos que contengan 10 unidades', 'unidad', 26.75, 20.0],
        ];
    }

    /**
     * Inserta los códigos de ISC específico que falten, sin tocar los existentes.
     */
    public static function installEspecificos(): void
    {
        $db = new DataBase();

        // al actualizar el plugin la estructura puede no estar al día todavía
        if (!isset($db->getColumns(self::tableName())['base_calculo'])) {
            DbUpdater::createOrUpdateTable(self::tableName());
        }

        foreach (self::especificos() as [$codigo, $descripcion, $base, $tasa, $advalorem]) {
            $exists = $db->select('SELECT 1 FROM ' . self::tableName() . ' WHERE codigo = ' . $db->var2str($codigo));
            if (!empty($exists)) {
                continue;
            }
            $db->exec('INSERT INTO ' . self::tableName()
                . ' (codigo, tipo_impuesto_short, tipo_impuesto_long, descripcion, tipo_tasa, tasa,'
                . ' base_calculo, tasa_advalorem, advalorem_sobre_especifico, nick, creationdate) VALUES ('
                . $db->var2str($codigo) . ", 'ISC', 'Impuesto Selectivo al Consumo (Tasa Específica + AdValorem)', "
                . $db->var2str($descripcion) . ", 'monto', " . (float)$tasa . ', ' . $db->var2str($base) . ', '
                . (float)$advalorem . ", false, 'install', CURRENT_DATE);");
        }
    }

    public function clear(): void
    {
        parent::clear();
        $this->codigo = null;
        $this->tipo_impuesto_long = null;
        $this->tipo_impuesto_short = null;
        $this->tipo_tasa = 'porcentaje';
        $this->tasa = 0.00;
        $this->base_calculo = 'unidad';
        $this->tasa_advalorem = 0.00;
        $this->advalorem_sobre_especifico = false;
        $this->creationdate = Tools::dateTime();
        $this->lastupdate = Tools::dateTime();
        $this->nick = Session::user()->nick;
    }

    /**
     * Texto para listas de selección: código, descripción y si es por porcentaje o por monto.
     */
    public function optionLabel(): string
    {
        $tasa = Tools::number((float)$this->tasa, 2);
        if ($this->tipo_tasa === 'monto') {
            $detalle = Tools::trans('tax-rate-amount') . ' ' . $tasa;
            if ((float)$this->tasa_advalorem > 0) {
                $detalle .= ' + AdValorem ' . Tools::number((float)$this->tasa_advalorem, 2) . '%';
            }
        } else {
            $detalle = Tools::trans('tax-rate-percentage') . ' ' . $tasa . '%';
        }

        return $this->codigo . ' - ' . $this->descripcion . ' [' . $detalle . ']';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'rd_impuestosadicionales';
    }

    public function test(): bool
    {
        $this->creationdate = $this->creationdate ?? Tools::dateTime();
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->nick = $this->nick ?? Session::user()->nick;

        return parent::test();
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->lastnick = Session::user()->nick;
        $this->lastupdate = Tools::dateTime();

        return parent::saveUpdate($values);
    }
}
