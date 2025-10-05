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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib;

use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\Join\FiscalReport606;
use FacturaScripts\Dinamic\Model\Join\FiscalReport607;
use FacturaScripts\Dinamic\Model\Join\FiscalReport608;
use FacturaScripts\Plugins\fsRepublicaDominicana\Interfaces\CommonFunctionsInterface;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;

use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;

class CommonFunctionsDominicanRepublic implements CommonFunctionsInterface
{
    public static function ncfRango()
    {
        // TODO: Implement ncfRango() method.
    }

    public static function ncfCorrelativo($tipoComprobante, $idempresa)
    {
        $tipocomprobante = new NCFRango();
        $where = [
            new DatabaseWhere('tipocomprobante', $tipoComprobante),
            new DatabaseWhere('idempresa', $idempresa),
            new DatabaseWhere('estado', 1)
        ];
        $comprobante = $tipocomprobante->all($where);
        if ($comprobante) {
            echo json_encode(['existe' => $comprobante], JSON_THROW_ON_ERROR);
        } else {
            echo json_encode(['existe' => false], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoPago(string $tipoPago)
    {
        $where = [new DatabaseWhere('tipopago', $tipoPago)];
        $tipoPagos = new NCFTipoPago();
        $pagos = $tipoPagos->all($where);
        if ($pagos) {
            echo \json_encode(['pagos' => $pagos], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoMovimiento(string $tipoMovimiento)
    {
        $tipomovimiento = new NCFTipoMovimiento();
        $where = [new DatabaseWhere('tipomovimiento', $tipoMovimiento)];
        $movimientos = $tipomovimiento->all($where);
        if ($movimientos) {
            echo json_encode(['movimientos' => $movimientos], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    public static function ncfTipoAnulacion(string $tipoAnulacion)
    {
        $where = [new DatabaseWhere('codigo', $tipoAnulacion)];
        $tipoAnulaciones = new NCFTipoAnulacion();
        $anulaciones = $tipoAnulaciones->all($where);
        if ($anulaciones) {
            echo \json_encode(['anulaciones' => $anulaciones], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoComprobante($tipoComprobante)
    {
        $where = [new DatabaseWhere($tipoComprobante, 'Y')];
        $tipoComprobantes = new NCFTipo();
        $lista = $tipoComprobantes->all($where);
        if ($lista) {
            echo json_encode(['tipocomprobantes' => $lista], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    public static function ncfFechaVencimiento()
    {
        // TODO: Implement ncfFechaVencimiento() method.
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoCliente(string $cliente)
    {
        $NCFTipo = new NCFTipo();
        $tipoCliente = $NCFTipo->tipoCliente($cliente);
        if ($tipoCliente) {
            echo json_encode(['infocliente' => $tipoCliente], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    public static function verifyDocument($ncf, $proveedor): void
    {
        if ($ncf !== "") {
            $facturasProveedores = new FacturaProveedor();
            $where = [
                new DataBaseWhere('numeroncf', $ncf),
                new DataBaseWhere('codproveedor', $proveedor)
            ];
            $verificacion = $facturasProveedores->all($where);
            if (!$verificacion) {
                echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            } else {
                $message = "Factura: " . $verificacion[0]->idfactura . " Fecha: " . $verificacion[0]->fecha;
                echo json_encode(['error' => true, 'message' => $message], JSON_THROW_ON_ERROR);
            }
        }
    }

    public function exportTXT(
        string $report,
        string $fileName,
        string $rncCompany,
        string $yearReport,
        string $monthReport,
        array $whereReport
    ) {
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        $dataCounter = 0;
        $fp = fopen($fileName, "w");
        switch ($report) {
            case "606":
                $this->exportTXT606(
                    $fp,
                    $rncCompany,
                    $yearReport,
                    $monthReport,
                    $whereReport
                );
                break;
            case "607":
            default:
                $this->exportTXT607(
                    $fp,
                    $rncCompany,
                    $yearReport,
                    $monthReport,
                    $whereReport
                );
                break;
            case "608":
                $this->exportTXT608(
                    $fp,
                    $rncCompany,
                    $yearReport,
                    $monthReport,
                    $whereReport
                );
                break;
        }
        fclose($fp);
        return true;
    }

    /**
     * @param mixed $fp
     * @param string $rncCompany
     * @param string $yearReport
     * @param string $monthReport
     * @param array $whereReport
     * @return void
     */
    protected function exportTXT606(
        &$fp,
        string $rncCompany,
        string $yearReport,
        string $monthReport,
        array $whereReport
    ): void
    {
        $reportData = new FiscalReport606();
        $data = $reportData->all($whereReport);
        $dataCounter = count($data);
        fwrite(
            $fp,
            sprintf(
                "%s|%s|%4s%2s|%s\r\n",
                '606',
                $rncCompany,
                $yearReport,
                $monthReport,
                $dataCounter
            )
        );

        foreach ($data as $line) {
            $convertValue = (substr($line->ncf, 1, 2) === '04') ? -1 : 1;
            $ncfModifica = ($line->ncfmodifica) ? substr($line->ncfmodifica, -11, 11) : "";
            $fechaPago = ($line->fechapago === '') ? $line->fechapago : $line->fecha;
            $itbisCosto = ($line->totalservicios > 0)
                ? number_format($line->itbis, 2, ".", "")
                : 0;
            fwrite(
                $fp,
                sprintf(
                    "%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s\r\n",
                    $line->cifnif,
                    $line->tipoid,
                    $line->tipocompra,
                    substr($line->ncf, -11, 11),
                    $ncfModifica,
                    $line->fecha,
                    $fechaPago,
                    number_format($line->totalservicios * $convertValue, 2, ".", ""),
                    number_format($line->totalbienes * $convertValue, 2, ".", ""),
                    number_format($line->base, 2, ".", ""),
                    number_format($line->itbis, 2, ".", ""),
                    "", "", "",
                    number_format($itbisCosto, 2, ".", ""),
                    "", "", "", "", "", "", "", "",
                    $line->tipopago
                ));
        }
    }

    /**
     * @param mixed $fp
     * @param string $rncCompany
     * @param string $yearReport
     * @param string $monthReport
     * @param array $whereReport
     * @return void
     */
    protected function exportTXT607(
        &$fp,
        string $rncCompany,
        string $yearReport,
        string $monthReport,
        array $whereReport
    ): void
    {
        $reportData = new FiscalReport607();
        $data = $reportData->all($whereReport);
        $dataCounter = count($data);
        fwrite(
            $fp,
            sprintf(
                "%s|%s|%4s%2s|%s\r\n",
                '607',
                $rncCompany,
                $yearReport,
                $monthReport,
                $dataCounter
            )
        );
        foreach ($data as $line) {
            $convertValue = (substr($line->ncf, 1, 2) === '04') ? -1 : 1;
            fwrite(
                $fp,
                sprintf(
                    "%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s\r\n",
                    $line->cifnif,
                    $line->tipoid,
                    substr($line->ncf, -11, 11),
                    substr($line->ncfmodifica, -11, 11),
                    1,
                    $line->fecha,
                    "",
                    number_format($line->base, 2, ".", ""),
                    number_format($line->itbis, 2, ".", ""),
                    "", "", "", "", "", "", "",
                    number_format($line->totalefectivo * $convertValue, 2, ".", ""),
                    number_format($line->totalcheque * $convertValue, 2, ".", ""),
                    number_format($line->totaltarjeta * $convertValue, 2, ".", ""),
                    number_format($line->totalcredito * $convertValue, 2, ".", ""),
                    number_format($line->totalbonos * $convertValue, 2, ".", ""),
                    number_format($line->totalpermuta * $convertValue, 2, ".", ""),
                    number_format($line->totalotrasformas * $convertValue, 2, ".", "")
                ));
        }
    }

    /**
     * @param mixed $fp
     * @param string $rncCompany
     * @param string $yearReport
     * @param string $monthReport
     * @param array $whereReport
     * @return void
     */
    protected function exportTXT608(
        &$fp,
        string $rncCompany,
        string $yearReport,
        string $monthReport,
        array $whereReport
    ): void {
        $reportData = new FiscalReport608();
        $data = $reportData->all($whereReport);
        $dataCounter = count($data);
        fwrite(
            $fp,
            sprintf(
                "%s|%s|%4s%2s|%s\r\n",
                '608',
                $rncCompany,
                $yearReport,
                $monthReport,
                $dataCounter
            )
        );
        foreach ($data as $line) {
            fwrite(
                $fp,
                sprintf(
                    "%s|%s|%s\r\n",
                    substr($line->ncf, -11, 11),
                    $line->fecha,
                    $line->tipoanulacion
                )
            );
        }
    }

    public function checkDateFormat($dateValue)
    {
        $year = (substr($dateValue, 4, 1) === '-')
            ? substr($dateValue, 0, 4)
            : substr($dateValue, 6, 4);
        $month = (substr($dateValue, 4, 1) === '-')
            ? substr($dateValue, 5, 2)
            : substr($dateValue, 3, 2);

        return [$year, $month];
    }
}


