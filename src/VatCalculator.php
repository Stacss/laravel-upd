<?php

namespace Stacss\LaravelUpd;

class VatCalculator
{
    /**
     * Рассчитать суммы по строкам и итоги.
     *
     * @param array $items
     *                     Каждый элемент:
     *                     [
     *                     'name'       => string,               // наименование
     *                     'code'       => string|null,         // код товара
     *                     'unit'       => string,              // условное обозначение единицы (шт, усл и т.п.)
     *                     'unit_code'  => string|null,         // ОКЕИ (796 и т.п.)
     *                     'quantity'   => float|int,           // количество
     *                     'price'      => float|int,           // цена за единицу
     *                     'vat_rate'   => float|int|null,      // ставка НДС, например 20, 10, 0 или null
     *                     'price_type' => 'net'|'gross'|null,  // 'net' = цена без НДС, 'gross' = цена с НДС, null = default_price_type
     *                     ]
     *
     * @return array
     *               [
     *               'items'  => [
     *               [
     *               ...исходные поля,
     *               'amount_net'   => float, // сумма без НДС
     *               'amount_vat'   => float, // сумма НДС
     *               'amount_gross' => float, // сумма с НДС
     *               'vat_rate'     => float, // нормализованная ставка
     *               ],
     *               ...
     *               ],
     *               'totals' => [
     *               'net'   => float, // итого без НДС
     *               'vat'   => float, // итого НДС
     *               'gross' => float, // итого с НДС
     *               ],
     *               ]
     */
    public function calculate(array $items): array
    {
        $defaultVatRate   = (float) config('upd.default_vat_rate', 20.0);
        $defaultPriceType = (string) config('upd.default_price_type', 'net');

        $resultItems = [];
        $totalNet    = 0.0;
        $totalVat    = 0.0;
        $totalGross  = 0.0;

        foreach ($items as $row) {
            $qty   = (float) ($row['quantity'] ?? 0);
            $price = (float) ($row['price'] ?? 0);

            // защита от мусора
            if ($qty < 0) {
                $qty = 0;
            }
            if ($price < 0) {
                $price = 0;
            }

            $vatRateRaw   = $row['vat_rate']   ?? $defaultVatRate;
            $priceTypeRaw = $row['price_type'] ?? $defaultPriceType;

            // нормализуем ставку НДС
            $vatRate = $vatRateRaw !== null ? (float) $vatRateRaw : 0.0;
            if ($vatRate < 0) {
                $vatRate = 0.0;
            }

            // нормализуем тип цены
            $priceType = $priceTypeRaw !== null ? strtolower((string) $priceTypeRaw) : $defaultPriceType;
            if (! in_array($priceType, ['net', 'gross'], true)) {
                $priceType = 'net';
            }

            if ($priceType === 'gross') {
                // цена с НДС
                $amountGross = $qty * $price;

                if ($vatRate > 0) {
                    $amountNet = $amountGross / (1 + $vatRate / 100);
                    $amountVat = $amountGross - $amountNet;
                } else {
                    $amountNet = $amountGross;
                    $amountVat = 0.0;
                }
            } else {
                // цена без НДС (net, по умолчанию)
                $amountNet = $qty * $price;

                if ($vatRate > 0) {
                    $amountVat   = $amountNet * $vatRate / 100;
                    $amountGross = $amountNet + $amountVat;
                } else {
                    $amountVat   = 0.0;
                    $amountGross = $amountNet;
                }
            }

            // округление до двух знаков
            $amountNet   = round($amountNet, 2);
            $amountVat   = round($amountVat, 2);
            $amountGross = round($amountGross, 2);

            $totalNet   += $amountNet;
            $totalVat   += $amountVat;
            $totalGross += $amountGross;

            $resultItems[] = array_merge($row, [
                'amount_net'   => $amountNet,
                'amount_vat'   => $amountVat,
                'amount_gross' => $amountGross,
                'vat_rate'     => $vatRate,
                'price_type'   => $priceType,
            ]);
        }

        return [
            'items'  => $resultItems,
            'totals' => [
                'net'   => round($totalNet, 2),
                'vat'   => round($totalVat, 2),
                'gross' => round($totalGross, 2),
            ],
        ];
    }
}
