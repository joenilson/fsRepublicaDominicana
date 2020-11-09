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

use FacturaScripts\Core\Controller\EditFacturaProveedor as ParentClass;

/**
 * Description of EditFacturaCliente
 *
 * @author joenilson
 */
class EditFacturaProveedor extends ParentClass
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
        //var_dump($this->views[$this->active]->model);
//        if($this->views[$this->active]->model->codcliente !== null) {
//            $columnToModify = $this->views[$this->active]->columnForName('codsubtipodoc');
//            if($columnToModify) {
//                $columnToModify->widget->setCustomValue('02');
//            }
//        }
    }
    
    protected function subjectChangedAction()
    {
        $this->setTemplate(false);

        /// loads model
        $data = $this->getBusinessFormData();
        $data['form']['codsubtipodoc'] = "02";
        $data['form']['codoperaciondoc'] = "11";
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
