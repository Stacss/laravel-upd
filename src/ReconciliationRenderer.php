<?php

declare(strict_types=1);

namespace Stacss\LaravelUpd;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdf;
use DateTimeInterface;

/**
 * Рендер акта сверки "левая/правая сторона".
 *
 * Ожидаемый массив данных:
 *
 * [
 *     // Дата акта "по состоянию на"
 *     'act_date' => Carbon|DateTimeInterface|string|null, // опционально, по умолчанию берется closing.date или period.to
 *
 *     'period' => [
 *         'from' => Carbon|DateTimeInterface|string|null,
 *         'to'   => Carbon|DateTimeInterface|string|null,
 *     ],
 *
 *     'left' => [
 *         'full_name'         => 'ООО «Орион»',
 *         'short_name'        => 'ООО «Орион»',
 *         'director_post'     => 'генерального директора',
 *         'director_full'     => 'Лавренова Олега Сергеевича',
 *         'director_short'    => 'Лавренов О. С.',
 *         'director_sign_note'=> 'Лавренов 01.08.2025',
 *         'basis'             => 'Устава',
 *     ],
 *
 *     'right' => [
 *         'full_name'         => 'ООО «Кентавр»',
 *         'short_name'        => 'ООО «Кентавр»',
 *         'director_post'     => 'генерального директора',
 *         'director_full'     => 'Сомова Артема Андреевича',
 *         'director_short'    => 'Сомов А. А.',
 *         'director_sign_note'=> 'Сомов 01.08.2025',
 *         'basis'             => 'Устава',
 *     ],
 *
 *     // Начальное сальдо: balance > 0 - задолженность в пользу левой стороны,
 *     // balance < 0 - в пользу правой; по модулю попадает в "Сальдо" у нужной стороны
 *     'opening' => [
 *         'date'    => Carbon|DateTimeInterface|string|null,
 *         'balance' => 0|float|int|string,
 *     ],
 *
 *     // Конечное сальдо, логика та же
 *     'closing' => [
 *         'date'    => Carbon|DateTimeInterface|string|null,
 *         'balance' => 0|float|int|string,
 *     ],
 *
 *     // Операции за период.
 *     // debit - списано по данным левой стороны, credit - поступило по данным левой стороны.
 *     // Правая сторона зеркалится автоматически.
 *     'operations' => [
 *         [
 *             'title'  => 'Накладная от 12.05.2025 № 8',
 *             'debit'  => 700000.00,
 *             'credit' => 0.00,
 *         ],
 *         [
 *             'title'  => 'Акт от 15.06.2025 № 34',
 *             'debit'  => 0.00,
 *             'credit' => 80000.00,
 *         ],
 *         [
 *             'title'  => 'Платежное поручение от 18.07.2025',
 *             'debit'  => 0.00,
 *             'credit' => 560000.00,
 *         ],
 *     ],
 *
 *     // Для обратной совместимости можно не передавать operations, а передать rows
 *     // в старом формате. Тогда title будет сформирован из date + document.
 *     // 'rows' => [
 *     //     [
 *     //         'date'     => '12.05.2025',
 *     //         'document' => 'Накладная № 8',
 *     //         'debit'    => 700000,
 *     //         'credit'   => 0,
 *     //     ],
 *     // ],
 * ]
 */
class ReconciliationRenderer
{
    public function pdf(array $data): DomPdf
    {
        $view = config('upd.reconciliation_view', 'laravel-upd::reconciliation');

        $viewData = $this->prepareData($data);

        return PdfFacade::loadView($view, $viewData)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Нормализация входных данных в плоский набор переменных для шаблона.
     */
    protected function prepareData(array $data): array
    {
        $period  = $data['period']  ?? [];
        $left    = $data['left']    ?? [];
        $right   = $data['right']   ?? [];
        $opening = $data['opening'] ?? [];
        $closing = $data['closing'] ?? [];

        $actDate     = $this->resolveActDate($data, $period, $closing);
        $periodStart = $this->formatDate($period['from'] ?? null);

        // Описание компаний
        $companyLeft = [
            'full_name'          => (string) ($left['full_name'] ?? $left['name'] ?? ''),
            'short_name'         => (string) ($left['short_name'] ?? $left['name'] ?? ''),
            'director_post'      => (string) ($left['director_post'] ?? 'генерального директора'),
            'director_full'      => (string) ($left['director_full'] ?? ''),
            'director_short'     => (string) ($left['director_short'] ?? ''),
            'director_sign_note' => (string) ($left['director_sign_note'] ?? ''),
            'basis'              => (string) ($left['basis'] ?? 'Устава'),
        ];

        $companyRight = [
            'full_name'          => (string) ($right['full_name'] ?? $right['name'] ?? ''),
            'short_name'         => (string) ($right['short_name'] ?? $right['name'] ?? ''),
            'director_post'      => (string) ($right['director_post'] ?? 'генерального директора'),
            'director_full'      => (string) ($right['director_full'] ?? ''),
            'director_short'     => (string) ($right['director_short'] ?? ''),
            'director_sign_note' => (string) ($right['director_sign_note'] ?? ''),
            'basis'              => (string) ($right['basis'] ?? 'Устава'),
        ];

        // Операции
        [$operations, $turnoverDebit, $turnoverCredit] = $this->buildOperations($data);

        // Сальдо на начало и конец
        $openingBalance = $this->normalizeNumber($opening['balance'] ?? 0);
        $closingBalance = $this->normalizeNumber($closing['balance'] ?? 0);

        // Сальдо на начало
        $openingLeftDebit  = max($openingBalance, 0);
        $openingLeftCredit = max(-$openingBalance, 0);

        $openingRightDebit  = $openingLeftCredit;
        $openingRightCredit = $openingLeftDebit;

        // Сальдо на конец
        $closingLeftDebit  = max($closingBalance, 0);
        $closingLeftCredit = max(-$closingBalance, 0);

        $closingRightDebit  = $closingLeftCredit;
        $closingRightCredit = $closingLeftDebit;

        // Обороты за период
        $turnoverLeftDebit  = $this->formatMoney($turnoverDebit);
        $turnoverLeftCredit = $this->formatMoney($turnoverCredit);

        $turnoverRightDebit  = $this->formatMoney($turnoverCredit);
        $turnoverRightCredit = $this->formatMoney($turnoverDebit);

        // Сальдо в формате "60 000,00" и название выгодоприобретателя
        $beneficiaryName = $closingBalance >= 0
            ? $companyLeft['short_name']
            : $companyRight['short_name'];

        $closingSaldoText = $this->formatMoney(abs($closingBalance));

        return [
            'actDate'     => $actDate,
            'periodStart' => $periodStart,

            'companyLeft'  => $companyLeft,
            'companyRight' => $companyRight,

            'operations' => $operations,

            'openingSaldoLeftDebit'   => $this->formatMoney($openingLeftDebit),
            'openingSaldoLeftCredit'  => $this->formatMoney($openingLeftCredit),
            'openingSaldoRightDebit'  => $this->formatMoney($openingRightDebit),
            'openingSaldoRightCredit' => $this->formatMoney($openingRightCredit),

            'turnoverLeftDebit'   => $turnoverLeftDebit,
            'turnoverLeftCredit'  => $turnoverLeftCredit,
            'turnoverRightDebit'  => $turnoverRightDebit,
            'turnoverRightCredit' => $turnoverRightCredit,

            'closingSaldoLeftDebit'   => $this->formatMoney($closingLeftDebit),
            'closingSaldoLeftCredit'  => $this->formatMoney($closingLeftCredit),
            'closingSaldoRightDebit'  => $this->formatMoney($closingRightDebit),
            'closingSaldoRightCredit' => $this->formatMoney($closingRightCredit),

            'beneficiaryName'  => $beneficiaryName,
            'closingSaldoText' => $closingSaldoText,
        ];
    }

    protected function resolveActDate(array $data, array $period, array $closing): string
    {
        $raw = $data['act_date'] ?? $closing['date'] ?? $period['to'] ?? null;

        return $this->formatDate($raw);
    }

    /**
     * Формирование операций и оборотов.
     *
     * Возвращает [operations, totalDebit, totalCredit].
     */
    protected function buildOperations(array $data): array
    {
        $rawOperations = $data['operations'] ?? [];

        // Обратная совместимость: если operations не переданы, пробуем rows
        if (empty($rawOperations) && !empty($data['rows']) && is_array($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $titleParts = [];

                if (!empty($row['date'])) {
                    $titleParts[] = trim((string) $row['date']);
                }

                if (!empty($row['document'])) {
                    $titleParts[] = trim((string) $row['document']);
                }

                $title = trim(implode(' ', $titleParts));

                $rawOperations[] = [
                    'title'  => $title !== '' ? $title : (string) ($row['description'] ?? ''),
                    'debit'  => $row['debit']  ?? 0,
                    'credit' => $row['credit'] ?? 0,
                ];
            }
        }

        $operations  = [];
        $totalDebit  = 0.0;
        $totalCredit = 0.0;

        foreach ($rawOperations as $op) {
            $title  = (string) ($op['title'] ?? '');
            $debit  = $this->normalizeNumber($op['debit'] ?? 0);
            $credit = $this->normalizeNumber($op['credit'] ?? 0);

            $totalDebit  += $debit;
            $totalCredit += $credit;

            $operations[] = [
                'title'        => $title,
                'left_debit'   => $this->formatMoney($debit),
                'left_credit'  => $this->formatMoney($credit),
                'right_debit'  => $this->formatMoney($credit),
                'right_credit' => $this->formatMoney($debit),
            ];
        }

        return [$operations, $totalDebit, $totalCredit];
    }

    protected function formatDate(null|DateTimeInterface|string $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    /**
     * Универсальное форматирование суммы.
     *
     * Если пришла строка и это не чистое число, считаем что она уже отформатирована.
     */
    protected function formatMoney(mixed $value): string
    {
        if (is_string($value) && $value !== '' && !is_numeric($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            $numeric = 0.0;
        } else {
            $numeric = (float) $value;
        }

        return number_format($numeric, 2, ',', ' ');
    }

    /**
     * Преобразование в число, с поддержкой строк вида "700 000,00".
     */
    protected function normalizeNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = str_replace(' ', '', $value);
            $clean = str_replace(',', '.', $clean);

            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }

        return (float) $value;
    }
}
