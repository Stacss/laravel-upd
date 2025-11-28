<?php

namespace Stacss\LaravelUpd\Tests;

use Barryvdh\DomPDF\PDF as DomPdf;
use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Orchestra\Testbench\TestCase;
use Stacss\LaravelUpd\Providers\UpdServiceProvider;
use Stacss\LaravelUpd\UpdRenderer;

class RendererTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            UpdServiceProvider::class,
            DomPdfServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('upd.default_vat_rate', 20.0);
        $app['config']->set('upd.default_price_type', 'net');
        $app['config']->set('upd.seller', [
            'name'    => 'Тестовый продавец',
            'inn'     => '1234567890',
            'kpp'     => '123456789',
            'address' => 'г. Тест, ул. Проверочная, д. 1',
            'phone'   => '+7 900 000-00-00',
        ]);
    }

    public function test_can_generate_pdf_and_save_file()
    {
        /** @var UpdRenderer $renderer */
        $renderer = $this->app->make(UpdRenderer::class);

        $items = [
            [
                'name'       => 'Тестовый товар',
                'code'       => 'TEST-001',
                'unit'       => 'шт',
                'unit_code'  => '796',
                'quantity'   => 2,
                'price'      => 100.0,
                'vat_rate'   => 20.0,
                'price_type' => 'net',
            ],
        ];

        $data = [
            'document' => [
                'number' => 'TEST-UPD-1',
                'date'   => now(),
                'status' => 1,
            ],
            'seller' => config('upd.seller'),
            'buyer'  => [
                'name'    => 'Тестовый покупатель',
                'inn'     => '0987654321',
                'kpp'     => '987654321',
                'address' => 'г. Покупательск, ул. Клиентская, д. 2',
            ],
            'items' => $items,
        ];

        $pdf = $renderer->pdf($data);

        $this->assertInstanceOf(DomPdf::class, $pdf);

        $outputDir  = __DIR__ . '/../build';
        $outputFile = $outputDir . '/test-upd.pdf';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $pdf->save($outputFile);

        $this->assertFileExists($outputFile);
        $this->assertGreaterThan(0, filesize($outputFile));
    }
}
