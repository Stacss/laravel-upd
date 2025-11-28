<?php

namespace Stacss\LaravelUpd;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use DateTimeInterface;

/**
 * Рендерер УПД-документа в PDF.
 *
 * Основная задача: принять массив данных, рассчитать НДС через VatCalculator,
 * собрать итоговые суммы и отрендерить PDF через DomPDF.
 */
class UpdRenderer
{
    public function __construct(
        protected ?VatCalculator $calculator = null
    ) {
        $this->calculator ??= new VatCalculator();
    }

    /**
     * Построить PDF УПД.
     *
     * Структура входных данных $data:
     *
     * [
     *   'document' => [...],
     *   'seller'   => [...],
     *   'buyer'    => [...],
     *   'items'    => [
     *       [
     *           'name'        => string,
     *           'code'        => string,
     *           'unit'        => string,
     *           'unit_code'   => string,
     *           'quantity'    => float|int,
     *           'price'       => float,
     *           'vat_rate'    => float,
     *           'price_type'  => 'net'|'gross',
     *       ],
     *       ...
     *   ],
     * ]
     *
     * Возвращает готовый DomPdf объект.
     *
     * @param  array  $data
     * @return DomPdf
     */
    public function pdf(array $data): DomPdf
    {
        $view = config('upd.view', 'laravel-upd::upd');

        $items = $data['items'] ?? [];

        // считаем суммы по позициям
        $calc = $this->calculator->calculate($items);

        // в шаблон отдаем уже посчитанные товары
        $data['items'] = $calc['items'] ?? [];

        // и totals
        $totals = $calc['totals'] ?? [];

        $data['totals'] = $totals;

        // удобные алиасы для строки "Всего к оплате"
        $data['total_without_vat'] = $totals['net']   ?? 0.0;
        $data['total_vat']         = $totals['vat']   ?? 0.0;
        $data['total_with_vat']    = $totals['gross'] ?? 0.0;

        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'landscape');
    }

    /**
     * Универсальный форматтер даты, если понадобится.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatDate(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return '';
    }
}
