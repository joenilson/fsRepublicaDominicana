<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Tools;


class ListImpuestoProducto extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Impuestos y Articulos";
        $data["menu"] = "accounting";
        $data["icon"] = "fa-solid fa-money-check-alt";
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsImpuestoProducto();
    }

    protected function createViewsImpuestoProducto(string $viewName = "ListImpuestoProducto")
    {
        $this->addView($viewName, "ImpuestoProducto", "Impuestos y Articulos");
        // Esto es un ejemplo ... debe de cambiarlo según los nombres de campos del modelo
        // $this->addOrderBy($viewName, ["id"], "id", 2);
        // $this->addOrderBy($viewName, ["name"], "name");
        
        // Esto es un ejemplo ... debe de cambiarlo según los nombres de campos del modelo
        // $this->addSearchFields($viewName, ["id", "name"]);
    }
}
