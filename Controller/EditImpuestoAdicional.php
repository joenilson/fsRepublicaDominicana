<?php

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Este es un controlador específico para ediciones. Permite una o varias pestañas.
 * Cada una con un xml y modelo diferente, puede ser de tipo edición, listado, html o panel.
 * Además, hace uso de archivos de XMLView para definir qué columnas mostrar y cómo.
 *
 * https://facturascripts.com/publicaciones/editcontroller-642
 */
class EditImpuestoAdicional extends EditController
{
    public function getModelClassName(): string
    {
        return 'ImpuestoAdicional';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'ImpuestoAdicional';
        $data['icon'] = 'fa-solid fa-search';
        return $data;
    }
}
