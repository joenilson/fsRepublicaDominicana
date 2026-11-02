<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use PHPUnit\Framework\TestCase;

final class NCFTipoNotaCreditoTest extends TestCase
{
    /**
     * @dataProvider tiposProvider
     */
    public function testTipoDeNotaDeCredito(?string $original, string $esperado): void
    {
        $this->assertSame($esperado, NCFTipo::tipoNotaCredito($original));
    }

    public static function tiposProvider(): array
    {
        return [
            'crédito fiscal' => ['01', '04'],
            'consumo' => ['02', '04'],
            'régimen especial' => ['14', '04'],
            'gubernamental' => ['15', '04'],
            'sin tipo' => [null, '04'],
            'e-CF crédito fiscal' => ['31', '34'],
            'e-CF consumo' => ['32', '34'],
            'e-CF régimen especial' => ['44', '34'],
            'e-CF gubernamental' => ['45', '34'],
        ];
    }
}
