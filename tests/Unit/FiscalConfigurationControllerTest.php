<?php

namespace Tests\Unit;

use App\Http\Controllers\FiscalConfigurationController;
use ReflectionClass;
use Tests\TestCase;

class FiscalConfigurationControllerTest extends TestCase
{
    public function test_normalize_fiscal_csc_id_keeps_only_digits_and_removes_leading_zeros(): void
    {
        $controller = new FiscalConfigurationController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('normalizeFiscalCscId');
        $method->setAccessible(true);

        $this->assertSame('1', $method->invoke($controller, ' 0001 '));
        $this->assertSame('15', $method->invoke($controller, 'ID 015'));
        $this->assertSame('0', $method->invoke($controller, '0000'));
        $this->assertNull($method->invoke($controller, '  '));
    }

    public function test_normalize_fiscal_csc_removes_whitespace_noise(): void
    {
        $controller = new FiscalConfigurationController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('normalizeFiscalCsc');
        $method->setAccessible(true);

        $this->assertSame('ABC123TOKEN', $method->invoke($controller, " ABC 123 \n TOKEN\t"));
        $this->assertNull($method->invoke($controller, '   '));
    }

    public function test_augment_fiscal_status_message_appends_guidance_for_cstat_464(): void
    {
        $controller = new FiscalConfigurationController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('augmentFiscalStatusMessage');
        $method->setAccessible(true);

        $message = $method->invoke(
            $controller,
            'cStat 464 - Rejeicao: Codigo de Hash no QR-Code difere do calculado',
            ['csc_id' => '7']
        );

        $this->assertSame(
            'cStat 464 - Rejeicao: Codigo de Hash no QR-Code difere do calculado Confira o CSC e o CSC ID 7 cadastrados na SEFAZ para o ambiente atual da loja.',
            $message
        );
    }
}
