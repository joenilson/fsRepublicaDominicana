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

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\EcfTimbre;
use PHPUnit\Framework\TestCase;

final class EcfTimbreTest extends TestCase
{
    private function query(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    public function testEcfUrlFollowsTheInformeTecnicoExample(): void
    {
        $url = EcfTimbre::url('testecf', '130000001', '130000002', 'E310000000001', '10-10-2020', 2.11, '10-10-2020 09:00:00', 'dcp79q');

        // Mismo orden y forma que el ejemplo de la DGII: el espacio de la fecha de firma como %20 y los dos puntos sin escapar.
        self::assertSame(
            'https://ecf.dgii.gov.do/testecf/ConsultaTimbre?RncEmisor=130000001&RncComprador=130000002&ENCF=E310000000001'
            . '&FechaEmision=10-10-2020&MontoTotal=2.11&FechaFirma=10-10-2020%2009:00:00&CodigoSeguridad=dcp79q',
            $url
        );
    }

    public function testABuyerWithoutRncGoesWithoutTheParameter(): void
    {
        // Con `RncComprador=` vacío la consulta de la DGII no encuentra el comprobante (tipos 43, 46 y 47): sin el parámetro, sí.
        $url = EcfTimbre::url('certecf', '131000011', '', 'E430000000013', '01-11-2026', 1000.0, '01-11-2026 11:27:18', 'EQUgK0');

        self::assertStringNotContainsString('RncComprador', $url);
        self::assertStringStartsWith('https://ecf.dgii.gov.do/certecf/ConsultaTimbre?RncEmisor=131000011&ENCF=E430000000013&FechaEmision=01-11-2026', $url);
    }

    public function testEachEnvironmentHasItsOwnPathAndProductionIsTheDefault(): void
    {
        $args = ['130000001', '130000002', 'E310000000001', '10-10-2020', 100.0, '10-10-2020 09:00:00', 'abcdef'];

        self::assertStringStartsWith('https://ecf.dgii.gov.do/testecf/ConsultaTimbre?', EcfTimbre::url('testecf', ...$args));
        self::assertStringStartsWith('https://ecf.dgii.gov.do/certecf/ConsultaTimbre?', EcfTimbre::url('CERTECF', ...$args));
        self::assertStringStartsWith('https://ecf.dgii.gov.do/ecf/ConsultaTimbre?', EcfTimbre::url('ecf', ...$args));
        self::assertStringStartsWith('https://ecf.dgii.gov.do/ecf/ConsultaTimbre?', EcfTimbre::url('', ...$args));
        self::assertStringStartsWith('https://ecf.dgii.gov.do/ecf/ConsultaTimbre?', EcfTimbre::url('cualquier cosa', ...$args));
    }

    public function testAConsumptionInvoiceBelowTwoHundredFiftyThousandUsesTheSummaryUrl(): void
    {
        $url = EcfTimbre::url('testecf', '131880738', '', 'E320000000064', '01-11-2026', 6225.09, '01-11-2026 12:04:38', 'uabnyh');

        self::assertSame(
            'https://fc.dgii.gov.do/testecf/ConsultaTimbreFC?RncEmisor=131880738&ENCF=E320000000064&MontoTotal=6225.09&CodigoSeguridad=uabnyh',
            $url
        );
        $query = $this->query($url);
        self::assertSame(['RncEmisor', 'ENCF', 'MontoTotal', 'CodigoSeguridad'], array_keys($query), 'el resumen no lleva comprador ni fechas');
    }

    public function testTheSummaryUrlIsOnlyForType32BelowTheLimit(): void
    {
        self::assertTrue(EcfTimbre::isSummary('E320000000001', 249999.99));
        self::assertFalse(EcfTimbre::isSummary('E320000000001', 250000.00), 'RD$250,000 o más va como e-CF completo');
        self::assertFalse(EcfTimbre::isSummary('E310000000001', 100.0), 'un 31 pequeño nunca es resumen');
        self::assertFalse(EcfTimbre::isSummary('E340000000001', 100.0));
        self::assertStringContainsString('/ConsultaTimbre?', EcfTimbre::url('ecf', '130000001', '130000002', 'E320000000001', '01-11-2026', 250000.0, '01-11-2026 10:00:00', 'abcdef'));
    }

    public function testDatesAndAmountAreNormalizedFromWhatFacturaScriptsStores(): void
    {
        self::assertSame('01-11-2026', EcfTimbre::date('01-11-2026'));
        self::assertSame('01-11-2026', EcfTimbre::date('2026-11-01'));
        self::assertSame('01-11-2026 09:05:00', EcfTimbre::dateTime('2026-11-01T09:05'), 'datetime-local sin segundos');
        self::assertSame('01-11-2026 09:05:07', EcfTimbre::dateTime('2026-11-01 09:05:07'));
        self::assertSame('01-11-2026 09:05:07', EcfTimbre::dateTime('01-11-2026 09:05:07'));
        self::assertSame('', EcfTimbre::dateTime('no es fecha'));
        self::assertSame('', EcfTimbre::date(null));
        self::assertSame('1180.00', EcfTimbre::amount(1180.0));
        self::assertSame('1234567.89', EcfTimbre::amount(1234567.891), 'sin separador de miles');
    }

    public function testTheSecurityCodeIsTheFirstSixCharactersAndIsEscapedInTheUrl(): void
    {
        self::assertSame('Ab+/9Z', EcfTimbre::securityCode(' Ab+/9Zqwerty=='));
        self::assertSame('abc', EcfTimbre::securityCode('abc'));
        self::assertSame('', EcfTimbre::securityCode(null));

        $url = EcfTimbre::url('ecf', '130000001', '130000002', 'E310000000001', '01-11-2026', 10.0, '01-11-2026 10:00:00', 'Ab+/9Zxx');
        self::assertStringEndsWith('&CodigoSeguridad=Ab%2B%2F9Z', $url);
        self::assertSame('Ab+/9Z', $this->query($url)['CodigoSeguridad']);
    }

    public function testBuyerAndIssuerIdsKeepOnlyDigits(): void
    {
        $url = EcfTimbre::url('ecf', '1-30-00000-1', '001-1234567-8', 'e310000000001', '01-11-2026', 10.0, '01-11-2026 10:00:00', 'abcdef');
        $query = $this->query($url);

        self::assertSame('130000001', $query['RncEmisor']);
        self::assertSame('00112345678', $query['RncComprador']);
        self::assertSame('E310000000001', $query['ENCF'], 'el e-NCF va en mayúsculas');
    }

    public function testForInvoiceBuildsEverythingTheRepresentationPrints(): void
    {
        $invoice = (object)[
            'numeroncf' => 'E310000000007',
            'fecha' => '01-11-2026',
            'totalplustaxes' => 1180.0,
            'cifnif' => '131-88068-1',
            'ecf_fecha_firma' => '2026-11-01 12:04:38',
            'ecf_codigo_seguridad' => 'AbCdEfGhIj',
        ];

        $timbre = EcfTimbre::forInvoice($invoice, '40220917690', 'certecf');

        self::assertSame('AbCdEf', $timbre['code']);
        self::assertSame('01-11-2026 12:04:38', $timbre['signedAt']);
        self::assertFalse($timbre['summary']);
        $query = $this->query($timbre['url']);
        self::assertSame('1180.00', $query['MontoTotal']);
        self::assertSame('01-11-2026 12:04:38', $query['FechaFirma']);
        self::assertStringStartsWith('https://ecf.dgii.gov.do/certecf/ConsultaTimbre?', $timbre['url']);
    }

    public function testForInvoiceFallsBackToTheSumOfTheDocumentWhenThereIsNoTotalPlusTaxes(): void
    {
        $invoice = (object)['numeroncf' => 'E320000000001', 'fecha' => '2026-11-01', 'totalplustaxes' => 0.0,
            'neto' => 1000.0, 'totaliva' => 180.0, 'totaladdedtaxes' => 20.0, 'cifnif' => '',
            'ecf_fecha_firma' => '', 'ecf_codigo_seguridad' => 'xyz123'];

        $timbre = EcfTimbre::forInvoice($invoice, '40220917690');

        self::assertTrue($timbre['summary']);
        self::assertSame('1200.00', $this->query($timbre['url'])['MontoTotal']);
        self::assertStringStartsWith('https://fc.dgii.gov.do/ecf/ConsultaTimbreFC?', $timbre['url']);
    }

    public function testTheQrIsAPngOfVersionEightOrHigherThatFitsTheLongestUrl(): void
    {
        // Con RNC de 11 dígitos la dirección no cabe en la versión 8: se sube de versión en lugar de fallar.
        $url = EcfTimbre::url('certecf', '40220917690', '40220917690', 'E310000000001', '01-11-2026', 1180.0, '01-11-2026 12:04:38', 'AbCdEf');
        $image = EcfTimbre::qrImage($url);

        self::assertStringStartsWith('data:image/png;base64,', $image);
        $png = base64_decode(substr($image, strlen('data:image/png;base64,')), true);
        self::assertNotFalse($png);
        [$width, $height] = getimagesizefromstring($png);
        self::assertSame($width, $height);
        // 4 módulos de margen a cada lado; la versión V tiene 17 + 4·V módulos: a escala 10 la versión 8 mide (49 + 8) · 10 px.
        self::assertGreaterThanOrEqual((17 + 4 * EcfTimbre::QR_MIN_VERSION + 8) * 10, $width);
    }
}
