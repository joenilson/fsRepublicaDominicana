<?php

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

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

    /** @var string */
    public $descripcion;

    /** @var string */
    public $lastnick;

    /** @var string */
    public $lastupdate;

    /** @var string */
    public $name;

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
//            "('006','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Cerveza','monto',632.58,'install',CURRENT_DATE), " .
//            "('007','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Vinos de uva','monto',632.58,'install',CURRENT_DATE), " .
//            "('008','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Vermut y demás vinos de uvas frescas','monto',632.58,'install',CURRENT_DATE), " .
//            "('009','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Demás bebidas fermentadas','monto',632.58,'install',CURRENT_DATE), " .
//            "('010','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Alcohol Etílico sin desnaturalizar (Mayor o igual a 80%)','monto',632.58,'install',CURRENT_DATE), " .
//            "('011','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Alcohol Etílico sin desnaturalizar (inferior a 80%)','monto',632.58,'install',CURRENT_DATE), " .
//            "('012','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Aguardientes de uva','monto',632.58,'install',CURRENT_DATE), " .
//            "('013','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Whisky','monto',632.58,'install',CURRENT_DATE), " .
//            "('014','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Ron y demás aguardientes de caña','monto',632.58,'install',CURRENT_DATE), " .
//            "('015','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Gin y Ginebra','monto',632.58,'install',CURRENT_DATE), " .
//            "('016','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Vodka','monto',632.58,'install',CURRENT_DATE), " .
//            "('017','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Licores','monto',632.58,'install',CURRENT_DATE), " .
//            "('018','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Los demás (Bebidas y Alcoholes)','monto',632.58,'install',CURRENT_DATE), " .
//            "('019','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Cigarrillos que contengan tabaco cajetilla 20 unidades','monto',53.51,'install',CURRENT_DATE), " .
//            "('020','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Los demás Cigarrillos que contengan 20 unidades','monto',53.51,'install',CURRENT_DATE), " .
//            "('021','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Cigarrillos que contengan 10 unidades','monto',26.75,'install',CURRENT_DATE), " .
//            "('022','ISC','Impuesto Selectivo al Consumo (Tasa Específico)','Los demás Cigarrillos que contengan 10 unidades','monto',26.75,'install',CURRENT_DATE), " .
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
    public function clear(): void
    {
        parent::clear();
        $this->codigo = null;
        $this->tipo_impuesto_long = null;
        $this->tipo_impuesto_short = null;
        $this->tipo_tasa = 'porcentaje';
        $this->tasa = 0.00;
        $this->creationdate = Tools::dateTime();
        $this->lastupdate = Tools::dateTime();
        $this->nick = Session::user()->nick;
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
        $this->name = Tools::noHtml($this->name);
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
