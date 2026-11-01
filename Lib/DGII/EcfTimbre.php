<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use FacturaScripts\Core\Tools;
use Throwable;

/**
 * Timbre (código QR) de la representación impresa de un e-CF, según el Informe Técnico e-CF v1.0 (§18.2.3) y la
 * Descripción Técnica de los Servicios de la DGII (consulta timbre).
 *
 * Hay dos formas, según el comprobante:
 *  - e-CF: https://ecf.dgii.gov.do/{ambiente}/ConsultaTimbre?RncEmisor=&RncComprador=&ENCF=&FechaEmision=&MontoTotal=&FechaFirma=&CodigoSeguridad=
 *  - Factura de consumo (tipo 32) menor de RD$250,000, que se informa como resumen (RFCE):
 *    https://fc.dgii.gov.do/{ambiente}/ConsultaTimbreFC?RncEmisor=&ENCF=&MontoTotal=&CodigoSeguridad=
 *
 * El ambiente es `testecf`, `certecf` o `ecf` (producción). El código de seguridad son los seis primeros caracteres del
 * SignatureValue de la firma del e-CF; la fecha de emisión va como dd-MM-aaaa y la de firma como dd-MM-aaaa HH:mm:ss.
 *
 * Es una clase sin base de datos para que las reglas se puedan probar en un solo lugar.
 */
class EcfTimbre
{
    public const ENVIRONMENTS = ['testecf', 'certecf', 'ecf'];

    private const HOST_ECF = 'https://ecf.dgii.gov.do/';

    private const HOST_SUMMARY = 'https://fc.dgii.gov.do/';

    /** La DGII indica la versión 8 del QR; si la dirección no cabe se sube de versión, nunca se baja. */
    public const QR_MIN_VERSION = 8;

    /** Longitud del código de seguridad: los seis primeros caracteres del SignatureValue. */
    public const SECURITY_CODE_LENGTH = 6;

    /** Una factura de consumo (32) por debajo de este monto total se informa como resumen (RFCE). */
    public const SUMMARY_LIMIT = 250000.0;

    /** Monto con dos decimales, punto decimal y sin separador de miles. */
    public static function amount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /** Fecha como dd-MM-aaaa; si no se reconoce el formato devuelve cadena vacía. */
    public static function date(?string $value): string
    {
        $date = self::parse($value);

        return $date === null ? '' : $date->format('d-m-Y');
    }

    /** Fecha y hora como dd-MM-aaaa HH:mm:ss; si no se reconoce el formato devuelve cadena vacía. */
    public static function dateTime(?string $value): string
    {
        $date = self::parse($value);

        return $date === null ? '' : $date->format('d-m-Y H:i:s');
    }

    /** Ambiente de la DGII; lo que no sea testecf o certecf es producción. */
    public static function environment(?string $value): string
    {
        $value = strtolower(trim((string)$value));

        return in_array($value, self::ENVIRONMENTS, true) ? $value : 'ecf';
    }

    /**
     * Datos del timbre de una factura de FacturaScripts con e-NCF: dirección del QR, código de seguridad y fecha de firma tal
     * como se imprimen debajo del QR.
     *
     * @param object $invoice factura de venta (numeroncf, fecha, totalplustaxes o total, cifnif, ecf_fecha_firma y ecf_codigo_seguridad)
     * @return array{url: string, code: string, signedAt: string, summary: bool}
     */
    public static function forInvoice(object $invoice, string $rncEmisor, ?string $environment = null): array
    {
        $total = self::invoiceTotal($invoice);
        $encf = (string)($invoice->numeroncf ?? '');
        $signedAt = self::dateTime((string)($invoice->ecf_fecha_firma ?? ''));

        return [
            'url' => self::url(
                (string)$environment,
                $rncEmisor,
                (string)($invoice->cifnif ?? ''),
                $encf,
                (string)($invoice->fecha ?? ''),
                $total,
                $signedAt,
                (string)($invoice->ecf_codigo_seguridad ?? '')
            ),
            'code' => self::securityCode((string)($invoice->ecf_codigo_seguridad ?? '')),
            'signedAt' => $signedAt,
            'summary' => self::isSummary(strtoupper(trim($encf)), $total),
        ];
    }

    /** ¿Es una factura de consumo (32) menor de RD$250,000, que se informa como resumen (RFCE)? */
    public static function isSummary(string $encf, float $montoTotal): bool
    {
        return str_starts_with(strtoupper(trim($encf)), 'E32') && round($montoTotal, 2) < self::SUMMARY_LIMIT;
    }

    /** Imagen PNG en base64 (data URI) del QR, o cadena vacía si no se pudo generar. */
    public static function qrImage(string $url): string
    {
        try {
            $options = new QROptions([
                'version' => QRCode::VERSION_AUTO,
                'versionMin' => self::QR_MIN_VERSION,
                'versionMax' => 40,
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 10,
                'imageBase64' => true,
            ]);

            return (new QRCode($options))->render($url);
        } catch (Throwable $exception) {
            Tools::log()->error('error-generating-qr-code', [
                '%message%' => $exception->getMessage(),
                '%url%' => $url,
            ]);

            return '';
        }
    }

    /** Los seis primeros caracteres del código (sin espacios) tal como la DGII lo espera en el QR y bajo el QR. */
    public static function securityCode(?string $value): string
    {
        return substr((string)preg_replace('/\s+/', '', (string)$value), 0, self::SECURITY_CODE_LENGTH);
    }

    /**
     * Dirección que codifica el QR. Para un 32 menor de RD$250,000 es la del resumen; para el resto, la del e-CF.
     *
     * @param string $environment testecf, certecf o ecf (otro valor se toma como producción)
     * @param string $rncEmisor RNC del emisor (solo dígitos)
     * @param string $rncComprador RNC o cédula del comprador (solo dígitos; puede ir vacío)
     * @param string $encf e-NCF del comprobante
     * @param string $fechaEmision fecha de emisión en cualquier formato habitual (se normaliza a dd-MM-aaaa)
     * @param float $montoTotal monto total del comprobante
     * @param string $fechaFirma fecha y hora de la firma (se normaliza a dd-MM-aaaa HH:mm:ss)
     * @param string $codigoSeguridad código de seguridad (se toman sus seis primeros caracteres)
     */
    public static function url(
        string $environment,
        string $rncEmisor,
        string $rncComprador,
        string $encf,
        string $fechaEmision,
        float $montoTotal,
        string $fechaFirma,
        string $codigoSeguridad
    ): string {
        $environment = self::environment($environment);
        $rncEmisor = self::digits($rncEmisor);
        $encf = strtoupper(trim($encf));
        $total = self::amount($montoTotal);
        $code = rawurlencode(self::securityCode($codigoSeguridad));

        if (self::isSummary($encf, $montoTotal)) {
            return self::HOST_SUMMARY . $environment . '/ConsultaTimbreFC?RncEmisor=' . $rncEmisor
                . '&ENCF=' . $encf
                . '&MontoTotal=' . $total
                . '&CodigoSeguridad=' . $code;
        }

        // El espacio de la fecha de firma va como %20; los dos puntos de la hora quedan tal cual, como en el ejemplo de la DGII.
        $signedAt = str_replace(' ', '%20', self::dateTime($fechaFirma));

        return self::HOST_ECF . $environment . '/ConsultaTimbre?RncEmisor=' . $rncEmisor
            . '&RncComprador=' . self::digits($rncComprador)
            . '&ENCF=' . $encf
            . '&FechaEmision=' . self::date($fechaEmision)
            . '&MontoTotal=' . $total
            . '&FechaFirma=' . $signedAt
            . '&CodigoSeguridad=' . $code;
    }

    private static function digits(string $value): string
    {
        return (string)preg_replace('/\D/', '', $value);
    }

    private static function invoiceTotal(object $invoice): float
    {
        $total = (float)($invoice->totalplustaxes ?? 0.0);
        if ($total !== 0.0) {
            return $total;
        }

        return (float)($invoice->neto ?? 0.0) + (float)($invoice->totaliva ?? 0.0) + (float)($invoice->totaladdedtaxes ?? 0.0);
    }

    private static function parse(?string $value): ?\DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }
}
