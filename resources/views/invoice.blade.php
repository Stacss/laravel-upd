@php
    $document = $document ?? [];
    $seller = $seller ?? [];
    $sellerBank = $seller_bank ?? [];
    $buyer = $buyer ?? [];
    $items = $items ?? [];
    $totals = $totals ?? ['net' => 0, 'vat' => 0, 'gross' => 0];
    $payment = $payment ?? [];
    $signatures = $signatures ?? [];

    $formatMoney = static function ($value): string {
        return number_format((float) $value, 2, ',', ' ');
    };

    $sellerMeta = array_filter([
        $seller['address'] ?? '',
        $seller['phone'] ?? '',
        $seller['email'] ?? '',
    ]);

    $buyerMeta = array_filter([
        $buyer['address'] ?? '',
        $buyer['phone'] ?? '',
        $buyer['email'] ?? '',
    ]);

    $hasBrandColumn = collect($items)->contains(static fn ($item) => !empty($item['brand']));
    $hasCodeColumn = collect($items)->contains(static fn ($item) => !empty($item['code']));
    $emptyItemsColspan = 7 + ($hasBrandColumn ? 1 : 0) + ($hasCodeColumn ? 1 : 0);
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Счет № {{ $document['number'] ?? '' }} от {{ $document['date'] ?? '' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            margin: 0;
            color: #000;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .box {
            border: 1px solid #000;
            margin-bottom: 8px;
        }

        .box td {
            padding: 4px 6px;
        }

        .label {
            width: 34%;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            margin: 10px 0 4px;
        }

        .items {
            margin-top: 8px;
        }

        .items th,
        .items td {
            border: 1px solid #000;
            padding: 4px 5px;
        }

        .items th {
            text-align: center;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            width: 48%;
            margin-left: auto;
            margin-top: 8px;
        }

        .totals td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .totals .label-cell {
            font-weight: bold;
            width: 68%;
        }

        .amount-words {
            margin-top: 8px;
            padding: 6px 8px;
            border: 1px solid #000;
            font-weight: bold;
        }

        .footer-box {
            margin-top: 10px;
        }

        .footer-box td {
            padding: 4px 6px;
        }

        .signatures {
            margin-top: 18px;
        }

        .signatures td {
            width: 50%;
            padding-right: 18px;
        }

        .sign-line {
            border-bottom: 1px solid #000;
            height: 18px;
            margin-top: 18px;
        }

        .small {
            font-size: 9px;
        }

    </style>
</head>
<body>
    <div class="title">СЧЕТ НА ОПЛАТУ № {{ $document['number'] ?? '' }} от {{ $document['date'] ?? '' }}</div>

    @if(!empty($document['due_date']) || !empty($document['contract']) || !empty($document['base']))
        <div class="subtitle">
            @if(!empty($document['due_date']))
                Срок оплаты: {{ $document['due_date'] }}
            @endif
            @if(!empty($document['contract']))
                {{ !empty($document['due_date']) ? ' | ' : '' }}{{ $document['contract'] }}
            @endif
            @if(!empty($document['base']))
                {{ (!empty($document['due_date']) || !empty($document['contract'])) ? ' | ' : '' }}{{ $document['base'] }}
            @endif
        </div>
    @endif

    <table class="box">
        <tr>
            <td class="label">Поставщик</td>
            <td>
                {{ $seller['name'] ?? '' }}
                @if(!empty($seller['inn']) || !empty($seller['kpp']))
                    , ИНН {{ $seller['inn'] ?? '' }}@if(!empty($seller['kpp'])) / КПП {{ $seller['kpp'] }}@endif
                @endif
                @if(!empty($seller['ogrn']))
                    , ОГРН {{ $seller['ogrn'] }}
                @endif
                @if(!empty($sellerMeta))
                    <br>{{ implode(', ', $sellerMeta) }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Банк получателя</td>
            <td>
                {{ $sellerBank['bank_name'] ?? '' }}<br>
                р/с {{ $sellerBank['account'] ?? '' }}
                @if(!empty($sellerBank['corr_account']))
                    , к/с {{ $sellerBank['corr_account'] }}
                @endif
                @if(!empty($sellerBank['bik']))
                    , БИК {{ $sellerBank['bik'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Покупатель</td>
            <td>
                {{ $buyer['name'] ?? '' }}
                @if(!empty($buyer['inn']) || !empty($buyer['kpp']))
                    , ИНН {{ $buyer['inn'] ?? '' }}@if(!empty($buyer['kpp'])) / КПП {{ $buyer['kpp'] }}@endif
                @endif
                @if(!empty($buyer['ogrn']))
                    , ОГРН {{ $buyer['ogrn'] }}
                @endif
                @if(!empty($buyerMeta))
                    <br>{{ implode(', ', $buyerMeta) }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th style="width: 5%;">№</th>
            <th style="width: {{ $hasBrandColumn || $hasCodeColumn ? '27%' : '40%' }};">Наименование</th>
            @if($hasBrandColumn)
                <th style="width: 12%;">Бренд</th>
            @endif
            @if($hasCodeColumn)
                <th style="width: 11%;">Код</th>
            @endif
            <th style="width: 8%;">Ед.</th>
            <th style="width: 8%;">Кол-во</th>
            <th style="width: 12%;">Цена</th>
            <th style="width: 9%;">НДС</th>
            <th style="width: {{ $hasBrandColumn || $hasCodeColumn ? '8%' : '18%' }};">Сумма</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $item['name'] ?? '' }}
                    @if(!empty($item['unit_code']))
                        <div class="small">ОКЕИ: {{ $item['unit_code'] }}</div>
                    @endif
                </td>
                @if($hasBrandColumn)
                    <td class="text-center">{{ $item['brand'] ?? '' }}</td>
                @endif
                @if($hasCodeColumn)
                    <td class="text-center">{{ $item['code'] ?? '' }}</td>
                @endif
                <td class="text-center">{{ $item['unit'] ?? '' }}</td>
                <td class="text-right">{{ rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 3, '.', ' '), '0'), '.') }}</td>
                <td class="text-right">{{ $formatMoney($item['price'] ?? 0) }}</td>
                <td class="text-center">
                    @if((float) ($item['vat_rate'] ?? 0) > 0)
                        {{ rtrim(rtrim(number_format((float) $item['vat_rate'], 2, '.', ''), '0'), '.') }}%
                    @else
                        Без НДС
                    @endif
                </td>
                <td class="text-right">{{ $formatMoney($item['amount_gross'] ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $emptyItemsColspan }}" class="text-center">Нет позиций</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label-cell">Итого без НДС</td>
            <td class="text-right">{{ $formatMoney($totals['net'] ?? 0) }}</td>
        </tr>
        <tr>
            <td class="label-cell">НДС</td>
            <td class="text-right">{{ $formatMoney($totals['vat'] ?? 0) }}</td>
        </tr>
        <tr>
            <td class="label-cell">Итого к оплате</td>
            <td class="text-right">{{ $formatMoney($totals['gross'] ?? 0) }}</td>
        </tr>
    </table>

    <div class="amount-words">Всего наименований {{ count($items) }}, на сумму {{ $amount_in_words ?? '' }}</div>

    <table class="box footer-box">
        <tr>
            <td class="label">Назначение платежа</td>
            <td>{{ $payment['purpose'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">НДС</td>
            <td>{{ $payment['vat_text'] ?? '' }}</td>
        </tr>
        @if(!empty($payment['comment']))
            <tr>
                <td class="label">Комментарий</td>
                <td>{{ $payment['comment'] }}</td>
            </tr>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td>
                Руководитель
                <div class="sign-line"></div>
                <div class="small">{{ $signatures['director'] ?? '' }}</div>
            </td>
            <td>
                Главный бухгалтер
                <div class="sign-line"></div>
                <div class="small">{{ $signatures['accountant'] ?? '' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
