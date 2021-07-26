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
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Core\Controller\EditFacturaCliente as ParentClass;

/**
 * Description of EditFacturaCliente
 *
 * @author joenilson
 */
class EditFacturaCliente extends ParentClass
{
    /**
     * Shows the document opertation type selector.
     *
     * @var bool
     */
    public $showDocOperation = false;
    /**
     * Shows the document sub-type selector.
     *
     * @var bool
     */
    public $showDocSubType = true;
    
    protected function createViews()
    {
        parent::createViews();
        $this->customWidgetValues();
    }
    
    public function customWidgetValues()
    {
        //TODO
        //$this->addHtmlView('Refund', 'Tab/RefundFacturaCliente_', 'FacturaCliente', 'refunds', 'fas fa-share-square');
    }

    protected function subjectChangedAction()
    {
        $this->setTemplate(false);

        //Client data
        $cliente0 = new Cliente();

        /// loads model
        $data = $this->getBusinessFormData();
        $cliente = $cliente0->get($data['subject']['codcliente']);
        $data['form']['codsubtipodoc'] = (isset($data['form']['codsubtipodoc'])) ? $cliente->codsubtipodoc : "02";
        $data['form']['codoperaciondoc'] = (isset($data['form']['codoperaciondoc'])) ? "01" : "LIMPIO";
        $data['form']['ncftipopago'] = (!isset($data['form']['ncftipopago'])) ? $cliente->ncftipopago : "";

        $merged = array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);
        $this->views[$this->active]->loadFromData($merged);

        /// update subject data?
        if (!$this->views[$this->active]->model->exists()) {
            $this->views[$this->active]->model->updateSubject();
        }

        $this->response->setContent(json_encode($this->views[$this->active]->model));
        return false;
    }
}
