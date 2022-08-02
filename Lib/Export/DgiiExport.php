<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\Export;

use Symfony\Component\HttpFoundation\Response;
use FacturaScripts\Dinamic\Lib\Export\ExportBase;

class DgiiExport extends ExportBase
{

    /**
     * @inheritDoc
     */
    public function addBusinessDocPage($model): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addTablePage($headers, $rows, $options = [], $title = ''): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDoc()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function newDoc(string $title, int $idformat, string $langcode)
    {
        // TODO: Implement newDoc() method.
    }

    /**
     * @inheritDoc
     */
    public function setOrientation(string $orientation)
    {
        // TODO: Implement setOrientation() method.
    }

    /**
     * @inheritDoc
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-Type', 'text/text; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $this->getFileName() . '.txt');
        $response->setContent($this->getDoc());
    }
}