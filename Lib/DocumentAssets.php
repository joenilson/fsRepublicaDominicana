<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Tools;

/**
 * Assets de las pantallas de documentos de compra y venta.
 *
 * El controlador limpia el AssetManager al crearse, por eso estos archivos no se pueden registrar
 * en Init: se piden desde assets() de los Mods, que el núcleo invoca al crear las vistas.
 */
class DocumentAssets
{
    private const BASE = '/Plugins/fsRepublicaDominicana/Assets/JS/';

    public static function load(): void
    {
        $route = Tools::config('route');

        // selectores con búsqueda (select2 del núcleo) para los impuestos adicionales de las líneas
        AssetManager::addCss($route . '/node_modules/select2/dist/css/select2.min.css?v=5');
        AssetManager::addCss($route . '/node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css?v=5');
        AssetManager::addJs($route . '/node_modules/select2/dist/js/select2.min.js?v=5', 2);

        foreach (['fsrd-core.js', 'CommonModals.js', 'fsrd-ncf.js', 'fsrd-ecf.js', 'fsrd-taxes.js'] as $file) {
            AssetManager::addJs($route . self::BASE . $file);
        }
    }
}
