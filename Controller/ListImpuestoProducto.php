<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;


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
    }
}
