<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Interfaces;

interface CommonFunctionsInterface
{
    public static function ncfRango();

    public static function ncfCorrelativo(string $tipoComprobante, int $idempresa);

    public static function ncfTipoComprobante(string $tipoComprobante);

    public static function ncfTipoMovimiento(string $tipoMovimiento);

    public static function ncfTipoAnulacion(string $tipoAnulacion);

    public static function ncfFechaVencimiento();

    public static function ncfTipoPago(string $tipoPago);

    public static function ncfTipoCliente(string $cliente);

    public function exportTXT(string $report, string $fileName, string $rncCompany, string $yearReport, string $monthReport, array $whereReport);
}
