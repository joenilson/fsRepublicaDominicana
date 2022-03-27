<?php
/**
 * Copyright (C) 2022 Joe Nilson <joenilson at gmail dot com>
 * 
 * fsRepublicaDominicana is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * fsRepublicaDominicana is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with fsRepublicaDominicana. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use Cassandra\Date;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Base\DownloadTools;

/**
 * Description of NCFDGIIDB
 * This table will be filled with the DGII RNC file download from the DGII web page
 * @author "Joe Nilson <joenilson at gmail dot com>"
 */
class RNCDGIIDB extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var string
     */
    private $dgiiUrl = 'https://dgii.gov.do/app/WebApps/Consultas/RNC/DGII_RNC.zip';

    /**
     * @var string
     */
    private $fileName = 'MyFiles/Cache/DGII_RNC.zip';

    /**
     * @var string
     */
    private $fileNameTxt = 'MyFiles/Cache/TMP/DGII_RNC.TXT';

    /**
     * @var string
     */
    public $rnc;

    /**
     * @var string
     */
    public $nombre;

    /**
     * @var string
     */
    public $razonsocial;

    /**
     * @var string
     */
    public $categoria;

    /**
     * @var date
     */
    public $inicioactividad;

    /**
     * @var string
     */
    public $estado;

    /**
     * @var string
     */
    public $regimenpagos;

    /**
     * @return string
     */
    public static function primaryColumn()
    {
        return 'rnc';
    }
    
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'rd_rncdgiidb';
    }
    
    /**
     * @return string
     */
    public function install()
    {
        parent::install();
        $sql = "";
        return($sql);
    }

    public function updateFile()
    {
        $this->downloadFile();
    }

    private function downloadFile()
    {
        if (file_exists($this->fileName)) {
            unlink($this->fileName);
        }
        if (file_exists($this->fileNameTxt)) {
            unlink($this->fileNameTxt);
        }

        DownloadTools::download($this->dgiiUrl, $this->fileName, '12600');
        $zip = new \ZipArchive();
        $zip->open($this->fileName);
        $zip->extractTo('MyFiles/Cache/');
        $zip->close();
        $this->slowParser();
    }

    private function slowParser()
    {
        $dataBase = new DataBase();
        $sqlDelete = "DELETE FROM " . $this->tableName() . ";";
        $dataBase->exec($sqlDelete);

        [$handle, $totalLines] = $this->utf8FopenRead($this->fileNameTxt);
        $lineNumber = 0;
        $maxLine = 2000;
        while (($raw_string = fgets($handle)) !== false) {
            $totalLines--;
            if ($maxLine === 2000) {
                $query = 'INSERT INTO ' . $this->tableName() .
                    ' (rnc, nombre, razonsocial, categoria, inicioactividad, estado, regimenpagos) VALUES ';
            }
            $linea = str_getcsv($raw_string, "|");
            if (count($linea) === 11) {
                if ($maxLine !== 1 && $totalLines !== 1) {
                    $query .= $this->queryConstruct($linea, false);
                }

                if ($maxLine === 1 || $totalLines === 1) {
                    $query .= $this->queryConstruct($linea, true);
                    $dataBase->exec($query);
                    $maxLine = 2001;
                }
                $maxLine--;
                $lineNumber++;
            } else {
                $this->toolBox()->i18nLog()->error("Error RNC TXT Linea: ".count($linea) . " - " . $raw_string);
            }
        }
        fclose($handle);
    }

    private function queryConstruct($colArray, $last = false)
    {
        $semiColon = ($last) ? "" : ",";
        $fecha = ($colArray[8] === '00/00/0000')
            ? "2000-01-01"
            : \date('Y-m-d', strtotime(str_replace("/", "-", $colArray[8])));
        return "('" .
            $colArray[0] . "','" .
            str_replace('"', '', str_replace("'", "''", str_replace("  ", " ", $colArray[1]))) . "','" .
            str_replace('"', '', str_replace("'", "''", str_replace("  ", " ", $colArray[2]))) . "','" .
            $colArray[3] . "','" .
            $fecha . "','" .
            $colArray[9] . "','" .
            $colArray[10] . "')" . $semiColon;
    }

    private function utf8FopenRead($fileName)
    {
        $fc = iconv('ISO-8859-15', 'UTF-8', file_get_contents($fileName));
        $handle = fopen("php://memory", "rw");
        $handle2 = fopen($fileName, "r");
        $lineCount = 0;
        while (!feof($handle2)) {
            $line = fgets($handle2);
            $lineCount++;
        }
        fclose($handle2);
        fwrite($handle, $fc);
        fseek($handle, 0);
        return [$handle, $lineCount];
    }
}
