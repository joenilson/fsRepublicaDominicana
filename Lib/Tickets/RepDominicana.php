<?php
/*
 * Copyright (C) 2023-2024 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\Tickets;

use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Tickets\BaseTicket;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Ticket;
use FacturaScripts\Dinamic\Model\TicketPrinter;
use FacturaScripts\Dinamic\Model\User;
use Mike42\Escpos\Printer;

class RepDominicana extends BaseTicket
{
    use ExtensionsTrait;
    public static function print(ModelClass $model, TicketPrinter $printer, User $user, Agente $agent = null): bool
    {
        static::init();

        $ticket = new Ticket();
        $ticket->idprinter = $printer->id;
        $ticket->nick = $user->nick;
        $ticket->title = $model->codigo;

        static::setHeader($model, $printer, $ticket->title);
        static::setBody($model, $printer);
        static::setFooter($model, $printer);
        $ticket->body = static::getBody();
        $ticket->base64 = true;
        $ticket->appversion = 1;

        if ($agent) {
            $ticket->codagente = $agent->codagente;
        }

        return $ticket->save();
    }

    protected static function setHeader(ModelClass $model, TicketPrinter $printer, string $title): void
    {
        $extensionVar = new static();
        $extensionVar->pipe('setHeaderBefore', $model, $printer);

        if ($printer->print_stored_logo) {
            static::$escpos->setJustification(Printer::JUSTIFY_CENTER);
            // imprimimos el logotipo almacenado en la impresora
            static::$connector->write("\x1Cp\x01\x00\x00");
            static::$escpos->feed();
        }

        // obtenemos los datos de la empresa
        $company = $model->getCompany();

        // establecemos el tamaño de la fuente
        static::$escpos->setTextSize($printer->title_font_size, $printer->title_font_size);

        // imprimimos el nombre corto de la empresa
        if ($printer->print_comp_shortname) {
            static::$escpos->text(static::sanitize($company->nombrecorto) . "\n");
            static::$escpos->setTextSize($printer->head_font_size, $printer->head_font_size);

            // imprimimos el nombre de la empresa
            static::$escpos->text(static::sanitize($company->nombre) . "\n");
        } else {
            // imprimimos el nombre de la empresa
            static::$escpos->text(static::sanitize($company->nombre) . "\n");
            static::$escpos->setTextSize($printer->head_font_size, $printer->head_font_size);
        }

        static::$escpos->setJustification();

        // imprimimos la dirección de la empresa
        static::$escpos->text(static::sanitize($company->direccion) . "\n");
        static::$escpos->text(static::sanitize("CP: " . $company->codpostal . ', ' . $company->ciudad) . "\n");
        static::$escpos->text(static::sanitize($company->tipoidfiscal . ': ' . $company->cifnif) . "\n\n");

        if ($printer->print_comp_tlf) {
            if (false === empty($company->telefono1) && false === empty($company->telefono2)) {
                static::$escpos->text(static::sanitize($company->telefono1 . ' / ' . $company->telefono2) . "\n");
            } elseif (false === empty($company->telefono1)) {
                static::$escpos->text(static::sanitize($company->telefono1) . "\n");
            } elseif (false === empty($company->telefono2)) {
                static::$escpos->text(static::sanitize($company->telefono2) . "\n");
            }
        }

        // imprimimos el título del documento
        static::$escpos->text(static::sanitize($title) . "\n");

        static::setHeaderTPV($model, $printer);

        // si es un documento de venta
        // imprimimos la fecha y el cliente
        if (in_array($model->modelClassName(), ['PresupuestoCliente', 'PedidoCliente', 'AlbaranCliente', 'FacturaCliente'])) {
            static::$escpos->text(static::sanitize(static::$i18n->trans('date') . ': ' . $model->fecha . ' ' . $model->hora) . "\n");
            static::$escpos->text(static::sanitize(static::$i18n->trans('customer') . ': ' . $model->nombrecliente) . "\n");
            if(strlen($model->cifnif) == 9) {
                static::$escpos->text(static::sanitize(static::$i18n->trans('title-cifnif-rnc') . ': ' . $model->cifnif) . "\n\n");
            } else {
                static::$escpos->text(static::sanitize(static::$i18n->trans('title-cifnif-ci') . ': ' . $model->cifnif) . "\n\n");
            }

            if ($model->modelClassName() === 'FacturaCliente') {
                if ($model->tipocomprobante !== null && $model->tipocomprobante !== '') {
                    static::$escpos->text(static::sanitize(static::$i18n->trans('tipo_comprobante') . ': ' .$model->descripcionTipoComprobante()). "\n");
                }

                if ($model->numeroncf !== null && $model->numeroncf !== '') {
                    static::$escpos->text(static::sanitize(static::$i18n->trans('ncf-number') . ': ' . $model->numeroncf). "\n");
                }

                if ($model->ncffechavencimiento!== null && $model->ncffechavencimiento!== '') {
                    static::$escpos->text(static::sanitize(static::$i18n->trans('due-date') . ': ' . $model->ncffechavencimiento). "\n\n");
                } else {
                    static::$escpos->text("\n\n");
                }
            }
        }

        // añadimos la cabecera
        if ($printer->head) {
            static::$escpos->setJustification(Printer::JUSTIFY_CENTER);
            static::$escpos->text(static::sanitize($printer->head) . "\n\n");
            static::$escpos->setJustification();
        }

        $extensionVar->pipe('setHeaderAfter', $model, $printer);
    }

    protected static function getTipoComprobanteRD(string $numero): string
    {
        switch ($numero) {
            case '01':
                return static::$i18n->trans('desc-ncf-type-01');

            case '02':
                return static::$i18n->trans('desc-ncf-type-02');

            case '03':
                return static::$i18n->trans('desc-ncf-type-03');

            case '04':
                return static::$i18n->trans('desc-ncf-type-04');

            case '11':
                return static::$i18n->trans('desc-ncf-type-11');

            case '12':
                return static::$i18n->trans('desc-ncf-type-12');

            case '13':
                return static::$i18n->trans('desc-ncf-type-13');

            case '14':
                return static::$i18n->trans('desc-ncf-type-14');

            case '15':
                return static::$i18n->trans('desc-ncf-type-15');

            case '16':
                return static::$i18n->trans('desc-ncf-type-16');

            case '17':
                return static::$i18n->trans('desc-ncf-type-17');

            default:
                return '';
        }
    }
}