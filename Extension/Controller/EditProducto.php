<?php
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;


class EditProducto
{
    public function createViews(): Closure
    {
        return function () {
            // tu código aquí
            // createViews() se ejecuta una vez realizado el createViews() del controlador.
            parent::createViews();
            $this->addListView('ListImpuestoProducto', 'ImpuestoProducto', 'taxes', 'fa-solid fa-money-check-alt');
        };
    }

    public function execAfterAction(): Closure
    {
        return function ($action) {
            // tu código aquí
            // execAfterAction() se ejecuta tras el execAfterAction() del controlador.
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {
            // tu código aquí
            // execPreviousAction() se ejecuta después del execPreviousAction() del controlador.
            // Si devolvemos false detenemos la ejecución del controlador.
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            // tu código aquí
            // loadData() se ejecuta tras el loadData() del controlador. Recibe los parámetros $viewName y $view.
            switch ($viewName) {
                case 'ListImpuestoProducto':
                    $where = [new DataBaseWhere('idproducto', $this->getModel()->id())];
                    $view->loadData('', $where);
                    break;

                default:
                    parent::loadData($viewName, $view);
                    break;
            }
        };
    }
}
