<?php

namespace Stacss\LaravelUpd\Tests;

use Barryvdh\DomPDF\PDF as DomPdf;
use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Orchestra\Testbench\TestCase;
use Stacss\LaravelUpd\Providers\UpdServiceProvider;
use Stacss\LaravelUpd\ReconciliationRenderer;

class ReconciliationRendererTest extends TestCase
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

        $app['config']->set('upd.reconciliation_view', 'laravel-upd::reconciliation');
    }

    public function test_can_generate_reconciliation_pdf_and_save_file(): void
    {
        /** @var ReconciliationRenderer $renderer */
        $renderer = $this->app->make(ReconciliationRenderer::class);

        $actDate = now()->setDate(2025, 8, 1)->startOfDay();

        $data = [
            'act_date' => $actDate,
            'period'   => [
                'from' => $actDate->copy()->setDate(2025, 1, 1),
                'to'   => $actDate,
            ],
            'left' => [
                'full_name'          => 'ООО «Орион»',
                'short_name'         => 'ООО «Орион»',
                'director_post'      => 'генерального директора',
                'director_full'      => 'Лавренова Олега Сергеевича',
                'director_short'     => 'Лавренов О. С.',
                'director_sign_note' => 'Лавренов ' . $actDate->format('d.m.Y'),
                'basis'              => 'Устава',
            ],
            'right' => [
                'full_name'          => 'ООО «Кентавр»',
                'short_name'         => 'ООО «Кентавр»',
                'director_post'      => 'генерального директора',
                'director_full'      => 'Сомова Артема Андреевича',
                'director_short'     => 'Сомов А. А.',
                'director_sign_note' => 'Сомов ' . $actDate->format('d.m.Y'),
                'basis'              => 'Устава',
            ],
            'opening' => [
                'date'    => $actDate->copy()->setDate(2025, 1, 1),
                'balance' => 0,
            ],
            'closing' => [
                'date'    => $actDate,
                'balance' => 60000,
            ],
            'operations' => [
                [
                    'title'  => 'Накладная от 12.05.2025 № 8',
                    'debit'  => 700000,
                    'credit' => 0,
                ],
                [
                    'title'  => 'Акт от 15.06.2025 № 34',
                    'debit'  => 0,
                    'credit' => 80000,
                ],
                [
                    'title'  => 'Платежное поручение от 18.07.2025',
                    'debit'  => 0,
                    'credit' => 560000,
                ],
            ],
        ];

        $pdf = $renderer->pdf($data);

        $this->assertInstanceOf(DomPdf::class, $pdf);

        $outputDir  = __DIR__ . '/../build';
        $outputFile = $outputDir . '/test-reconciliation.pdf';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $pdf->save($outputFile);

        $this->assertFileExists($outputFile);
        $this->assertGreaterThan(0, filesize($outputFile));
    }
}
