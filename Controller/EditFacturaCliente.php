<?php

/**
 * Copyright (C) 2020 joenilson.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelOnChangeClass;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Core\Controller\EditFacturaCliente as ParentClass;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFRango;

/**
 * Description of EditFacturaCliente
 *
 * @author joenilson
 */
class EditFacturaCliente extends ParentClass
{
    /**
     *
     * @return bool
     */
//    protected function subjectChangedAction()
//    {
//        $this->setTemplate(false);
//
//        //Client data
//        $cliente0 = new Cliente();
//
//        /// loads model
//        $data = $this->getBusinessFormData();
//        $cliente = $cliente0->get($data['subject']['codcliente']);
//        $data['form']['codsubtipodoc'] = (isset($data['form']['codsubtipodoc'])) ? $cliente->codsubtipodoc : "02";
//        $data['form']['codoperaciondoc'] = (isset($data['form']['codoperaciondoc'])) ? "01" : "LIMPIO";
//        $data['form']['ncftipopago'] = (!isset($data['form']['ncftipopago'])) ? $cliente->ncftipopago : "";
//
//        $merged = array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);
//        $this->views[$this->active]->loadFromData($merged);
//
//        /// update subject data?
//        if (!$this->views[$this->active]->model->exists()) {
//            $this->views[$this->active]->model->updateSubject();
//        }
//
//        $this->response->setContent(json_encode($this->views[$this->active]->model));
//        return false;
//    }

    /**
     *
     * @return bool
     */
    protected function newRefundAction(): bool
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->request->get('idfactura'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return false;
        }

        $lines = [];
        $quantities = [];
        foreach ($invoice->getLines() as $line) {
            $quantity = (float) $this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (empty($quantity)) {
                continue;
            }

            $quantities[$line->primaryColumnValue()] = 0 - $quantity;
            $lines[] = $line;
        }

        if (empty($quantities)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return false;
        }

        $generator = new BusinessDocumentGenerator();
        $properties = [
            'codigorect' => $invoice->codigo,
            'codserie' => $this->request->request->get('codserie'),
            'fecha' => $this->request->request->get('fecha'),
            'idfacturarect' => $invoice->idfactura,
            'observaciones' => $this->request->request->get('observaciones'),
            'ncftipoanulacion' => $this->request->request->get('ncf-cancellation-type'),
            'codsubtipodoc' => '04'
        ];
        if ($generator->generate($invoice, $invoice->modelClassName(), $lines, $quantities, $properties)) {
            foreach ($generator->getLastDocs() as $doc) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                $this->redirect($doc->url() . '&action=save-ok');
                return true;
            }
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }
}
