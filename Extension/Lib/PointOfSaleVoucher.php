<?php
/*
 * Copyright (C) 2023 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Lib;

use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Tools;

use FacturaScripts\Plugins\POS\Lib\PointOfSaleVoucher as PointOfSaleVoucherBase;

class PointOfSaleVoucher extends PointOfSaleVoucherBase
{
    public function __construct(BusinessDocument $document, int $width, bool $hidePrices = false)
    {
        parent::__construct(null);

        $this->document = $document;
        $this->hidePrices = $hidePrices;
        $this->ticketType = $document->modelClassName();
    }

    /**
     * Builds the ticket body
     */
    protected function buildBody(): void
    {
        $this->printer->text($this->document->codigo, true, true);
        $this->printer->lineSeparator('-');
        $this->printer->text('NCF: ' . $this->document->numeroncf, true, true);
        //$this->printer->text('TIPO: ' . $this->document->descripcionTipoComprobante(), true, true);
        $this->printer->text('F. VENC. NCF: ' . $this->document->ncffechavencimiento, true, true);
        $this->printer->lineSeparator('-');
        $fechacompleta = $this->document->fecha . ' ' . $this->document->hora;
        $this->printer->text($fechacompleta, true, true);

        $this->printer->text('CLIENTE: ' . $this->document->nombrecliente);
        $this->printer->lineSeparator('=');

        $this->printer->text('ARTICULO');
        $this->printer->textColumns('UNITARIO', 'IMPORTE');
        $this->printer->lineSeparator('=');

        foreach ($this->document->getLines() as $line) {
            $this->printer->text("$line->cantidad x $line->referencia - $line->descripcion");

            if (false === $this->hidePrices) {
                $this->printer->textColumns('PU', NumberTools::format($line->pvpunitario));
                $this->printer->textColumns('IMPORTE', NumberTools::format($line->pvpsindto));

                $descuento = $line->pvpsindto - ($line->pvpsindto * $line->getEUDiscount());
                $this->printer->textColumns('Descuento:', '- ' . NumberTools::format($descuento));

                $impuestoLinea = $line->pvptotal * $line->iva / 100;
                $this->printer->textColumns("Impuesto $line->iva%:", '+ ' . NumberTools::format($impuestoLinea));
                $this->printer->textColumns('Total linea:', NumberTools::format($line->pvptotal + $impuestoLinea));
            }

            $this->printer->lineBreak();
        }

        if (false === $this->hidePrices) {
            $this->printer->lineSeparator('=');
            $this->printer->textColumns('BASE', NumberTools::format($this->document->neto));
            $this->printer->textColumns('IVA', NumberTools::format($this->document->totaliva));
            $this->printer->textColumns('TOTAL DEL DOCUMENTO:', NumberTools::format($this->document->total));
        }
    }

}
