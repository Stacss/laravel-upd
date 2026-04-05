<?php

declare(strict_types=1);

namespace Stacss\LaravelUpd\Tests\Feature;

use Barryvdh\DomPDF\PDF as DomPdf;
use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Orchestra\Testbench\TestCase;
use Stacss\LaravelUpd\InvoiceRenderer;
use Stacss\LaravelUpd\Providers\UpdServiceProvider;
use Stacss\LaravelUpd\Support\MoneyToWordsRu;

class InvoiceRendererTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            UpdServiceProvider::class,
            DomPdfServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('upd.default_vat_rate', 20.0);
        $app['config']->set('upd.default_price_type', 'net');
        $app['config']->set('upd.invoice_view', 'laravel-upd::invoice');
        $app['config']->set('upd.seller', [
            'name'       => 'ООО Ромашка',
            'short_name' => 'ООО Ромашка',
            'inn'        => '7700000000',
            'kpp'        => '770001001',
            'ogrn'       => '1027700000000',
            'address'    => 'г. Москва, ул. Пример, д. 1',
            'phone'      => '+7 (999) 123-45-67',
            'email'      => 'info@example.ru',
        ]);
        $app['config']->set('upd.bank_details', [
            'bank_name'    => 'ПАО Сбербанк',
            'bik'          => '044525225',
            'account'      => '40702810900000000001',
            'corr_account' => '30101810400000000225',
        ]);
        $app['config']->set('upd.invoice_defaults', [
            'comment' => 'Оплата означает согласие с условиями поставки.',
        ]);
    }

    public function test_can_generate_invoice_pdf(): void
    {
        $renderer = $this->app->make(InvoiceRenderer::class);

        $pdf = $renderer->pdf($this->invoiceData());

        $this->assertInstanceOf(DomPdf::class, $pdf);

        $outputDir  = __DIR__ . '/../build';
        $outputFile = $outputDir . '/test-invoice.pdf';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $pdf->save($outputFile);

        $this->assertFileExists($outputFile);
        $this->assertGreaterThan(0, filesize($outputFile));
    }

    public function test_invoice_uses_vat_calculator_totals(): void
    {
        $renderer = new class () extends InvoiceRenderer {
            public function exposePrepareData(array $data): array
            {
                return $this->prepareData($data);
            }
        };

        $prepared = $renderer->exposePrepareData($this->invoiceData());

        $this->assertSame(2000.0, $prepared['totals']['net']);
        $this->assertSame(400.0, $prepared['totals']['vat']);
        $this->assertSame(2400.0, $prepared['totals']['gross']);
        $this->assertSame(2400.0, $prepared['items'][0]['amount_gross']);
    }

    public function test_can_prepare_invoice_without_buyer_requisites(): void
    {
        $renderer = new class () extends InvoiceRenderer {
            public function exposePrepareData(array $data): array
            {
                return $this->prepareData($data);
            }
        };

        $data = $this->invoiceData();
        unset($data['buyer']);

        $prepared = $renderer->exposePrepareData($data);

        $this->assertSame('', $prepared['buyer']['name']);
        $this->assertSame('', $prepared['buyer']['inn']);
        $this->assertSame('Оплата счета №15 от 05.04.2026', $prepared['payment']['purpose']);
    }

    public function test_money_to_words_ru_formats_rubles_and_kopecks(): void
    {
        $converter = new MoneyToWordsRu();

        $this->assertSame(
            'две тысячи четыреста рублей 50 копеек',
            $converter->convert(2400.5)
        );
    }

    protected function invoiceData(): array
    {
        return [
            'document' => [
                'number'   => '15',
                'date'     => now()->setDate(2026, 4, 5)->startOfDay(),
                'due_date' => now()->setDate(2026, 4, 10)->startOfDay(),
                'base'     => 'Оплата по счету за товары',
                'contract' => 'Договор №5 от 01.01.2026',
            ],
            'seller'      => config('upd.seller'),
            'seller_bank' => config('upd.bank_details'),
            'buyer'       => [
                'name'       => 'ООО Покупатель',
                'short_name' => 'ООО Покупатель',
                'inn'        => '7800000000',
                'kpp'        => '780001001',
                'ogrn'       => '1027800000000',
                'address'    => 'г. Санкт-Петербург, ул. Тестовая, д. 5',
            ],
            'items' => [
                [
                    'name'       => 'Товар 1',
                    'code'       => 'ABC-001',
                    'unit'       => 'шт',
                    'unit_code'  => '796',
                    'quantity'   => 2,
                    'price'      => 1000,
                    'vat_rate'   => 20,
                    'price_type' => 'net',
                ],
            ],
            'payment' => [
                'comment' => 'Без доверенности. Оплата означает согласие с условиями поставки.',
            ],
            'signatures' => [
                'director'   => 'Иванов И.И.',
                'accountant' => 'Петров П.П.',
            ],
        ];
    }

    protected function makeInspectableRenderer(): InvoiceRenderer
    {
        return new class () extends InvoiceRenderer {
            public function exposePrepareData(array $data): array
            {
                return $this->prepareData($data);
            }
        };
    }
}
