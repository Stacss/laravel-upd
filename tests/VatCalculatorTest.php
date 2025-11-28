<?php

namespace Stacss\LaravelUpd\Tests;

use Orchestra\Testbench\TestCase;
use Stacss\LaravelUpd\VatCalculator;

class VatCalculatorTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('upd.default_vat_rate', 20.0);
        $app['config']->set('upd.default_price_type', 'net');
    }

    public function test_calculate_net_price_with_vat()
    {
        $calculator = new VatCalculator();

        $data = [
            [
                'name'       => 'Товар 1',
                'unit'       => 'шт',
                'quantity'   => 2,
                'price'      => 100.0,
                'vat_rate'   => 20.0,
                'price_type' => 'net',
            ],
        ];

        $result = $calculator->calculate($data);

        $item = $result['items'][0];

        $this->assertEquals(200.0, $item['amount_net']);
        $this->assertEquals(40.0, $item['amount_vat']);
        $this->assertEquals(240.0, $item['amount_gross']);
    }

    public function test_calculate_gross_price_with_vat()
    {
        $calculator = new VatCalculator();

        $data = [
            [
                'name'       => 'Товар 1',
                'unit'       => 'шт',
                'quantity'   => 2,
                'price'      => 120.0,
                'vat_rate'   => 20.0,
                'price_type' => 'gross',
            ],
        ];

        $result = $calculator->calculate($data);
        $item   = $result['items'][0];

        $this->assertEquals(200.0, $item['amount_net']);
        $this->assertEquals(40.0, $item['amount_vat']);
        $this->assertEquals(240.0, $item['amount_gross']);
    }

    public function test_calculate_zero_vat()
    {
        $calculator = new VatCalculator();

        $data = [
            [
                'name'       => 'Товар 1',
                'unit'       => 'шт',
                'quantity'   => 1,
                'price'      => 100.0,
                'vat_rate'   => 0.0,
                'price_type' => 'net',
            ],
        ];

        $result = $calculator->calculate($data);
        $item   = $result['items'][0];

        $this->assertEquals(100.0, $item['amount_net']);
        $this->assertEquals(0.0, $item['amount_vat']);
        $this->assertEquals(100.0, $item['amount_gross']);
    }
}
