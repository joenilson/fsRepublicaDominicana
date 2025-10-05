<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;


/**
 * Description of EditImpuestoProducto
 */
class EditImpuestoProducto extends EditController
{
    public function getModelClassName(): string
    {
        return "ImpuestoProducto";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "ImpuestoProducto";
        $data["icon"] = "fa-solid fa-search";
        return $data;
    }
}
