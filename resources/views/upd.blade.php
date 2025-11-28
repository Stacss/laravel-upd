@php
    /** @var array      $document */
    /** @var array      $seller */
    /** @var array      $buyer */
    /** @var array|null $payment */
    /** @var array|null $totals */

    $document  = $document  ?? [];
    $seller    = $seller    ?? [];
    $buyer     = $buyer     ?? [];
    $payment   = $payment   ?? null;
    $basis     = $basis     ?? ($document['basis'] ?? '');
    $transport = $transport ?? ($document['transport'] ?? '');

    // номер и дата документа
    $code    = $document['number'] ?? '';
    $dateRaw = $document['date']   ?? '';

    if ($dateRaw instanceof \DateTimeInterface) {
        $date = $dateRaw->format('d.m.Y');
    } elseif (is_string($dateRaw)) {
        $date = trim($dateRaw);
    } else {
        $date = '';
    }

    // статус УПД: 1 или 2
    $status = $document['status'] ?? 1;

    // номер платежно-расчетного документа (updpp)
    $updpp =
        $payment['number']            // явный блок payment
        ?? $document['payment_document'] // обратная совместимость
        ?? $document['updpp']            // старое имя
        ?? '';

    // продавец
    $companyname             = $seller['name']              ?? '';
    $companyaddress          = $seller['address']           ?? '';
    $companyinn              = $seller['inn']               ?? '';
    $companykpp              = $seller['kpp']               ?? '';
    $companyogrn             = $seller['ogrn']              ?? '';
    $companydirectorname     = $seller['director_name']     ?? '';
    $companydirectorposition = $seller['director_position'] ?? '';

    // покупатель
    $clientname    = $buyer['name']    ?? '';
    $clientaddress = $buyer['address'] ?? '';
    $clientinn     = $buyer['inn']     ?? '';
    $clientkpp     = $buyer['kpp']     ?? '';

    // дата отгрузки
    $shipDateRaw = $ship_date ?? ($document['ship_date'] ?? null);

    if ($shipDateRaw instanceof \DateTimeInterface) {
        $ship_date_formatted = $shipDateRaw->format('d.m.Y');
    } elseif (is_string($shipDateRaw)) {
        $ship_date_formatted = trim($shipDateRaw);
    } else {
        // по умолчанию как дата документа
        $ship_date_formatted = $date;
    }

    // количество страниц
    $pages_count = $pages_count ?? ($document['pages_count'] ?? 2);

    // итоговые суммы
    $totals = $totals ?? [];

    // Приоритет:
    // 1) отдельные переменные ($total_without_vat и т.п.), если их явно передали
    // 2) ключи внутри $totals (новые + старые имена для совместимости)
    $total_without_vat = $total_without_vat
        ?? ($totals['net']
            ?? $totals['sum_without_vat']
            ?? $totals['total_without_vat']
            ?? 0.0);

    $total_vat = $total_vat
        ?? ($totals['vat']
            ?? $totals['vat_sum']
            ?? $totals['total_vat']
            ?? 0.0);

    $total_with_vat = $total_with_vat
        ?? ($totals['gross']
            ?? $totals['sum_with_vat']
            ?? $totals['total_with_vat']
            ?? 0.0);
@endphp

        <!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>УПД № {{ $code }} от {{ $date }}</title>
</head>
<body>
<style>
    @page {
        size: 297mm 210mm;
        margin: 10mm 10mm 10mm 10mm;
    }

    body {
        margin: 0;
        width: 100%;
        height: 100%;
        background: #FFF;
    }

    @media screen {
        body {
            padding: 0;
        }
    }

    * {
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
    }

    body,
    td,
    th {
        font-family: "DejaVu Sans", sans-serif;
        font-weight: normal;
        color: #000;
        font-size: 10px;
    }

    body .upd,
    .upd td,
    .upd th {
        font-size: 10px;
        vertical-align: top;
    }

    .bold {
        font-weight: bold;
    }

    a {
        color: #000;
        text-decoration: underline !important;
    }

    a img {
        border: none;
    }

    img.full_width {
        width: 100%;
        height: auto;
    }

    h1,
    h2,
    h3 {
        font-weight: bold;
    }

    h1 {
        font-size: 30px;
    }

    h2 {
        font-size: 24px;
    }

    h3 {
        font-size: 18px;
    }

    .gap {
        height: 30px;
    }

    .nowrap {
        white-space: nowrap;
    }

    .clear {
        clear: both;
        height: 0;
        line-height: 0;
        font-size: 0;
    }

    .clearfix {
        overflow: hidden;
    }

    .vertical_centered_content {
        display: -webkit-flex;
        -webkit-align-items: center;
        display: flex;
        align-items: center;
    }

    .vertical_centered_content > .inner {
        display: inline;
        width: 100%;
    }

    .horizontal_centered_content {
        display: -webkit-flex;
        -webkit-justify-content: center;
        display: flex;
        justify-content: center;
        text-align: center;
    }

    .horizontal_centered_content > .inner {
        display: inline;
        width: 100%;
    }

    @media screen {
        .doc.landscape {
            width: 1080px;
        }

        .doc.portrait {
            width: 720px;
        }
    }

    /* таблица УПД под dompdf */
    table.updorderlist {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 8px;
        margin-left: -4.3px;
    }

    .updorderlist td,
    .updorderlist th {
        border: 1px solid #000;
        font-size: 8px;
    }

    .updskeleton,
    .updskeleton > tbody > tr > td {
        border: 1px solid #FFF;
    }

    table {
        border-collapse: collapse;
    }

    .upd-compact {
        width: 100%;
        border-collapse: collapse;
    }

    .upd-compact td {
        padding: 1px 2px;
        font-size: 9px;
        line-height: 1.1;
        vertical-align: middle;
    }

    .upd-parties {
        width: 100%;
        border-collapse: collapse;
    }

    .upd-parties td {
        padding: 1px 2px;
        font-size: 9px;
        line-height: 1.1;
        vertical-align: top;
    }

    .upd-parties .label {
        width: 165px;
        white-space: nowrap;
    }

    .upd-parties .value {
        border-bottom: 1px solid #000;
    }

    .upd-parties .code {
        width: 22px;
        text-align: right;
        white-space: nowrap;
    }

    .col-n {
        width: 25px;
    }

    .col-code {
        width: 90px;
    }

    .col-name {
        width: 260px;
    }

    .col-type {
        width: 35px;
    }

    .col-unit-code {
        width: 35px;
    }

    .col-unit-name {
        width: 30px;
    }

    .col-qty {
        width: 60px;
    }

    .col-price {
        width: 60px;
    }

    .col-sum-no-vat {
        width: 65px;
    }

    .col-excise {
        width: 45px;
    }

    .col-vat-rate {
        width: 45px;
    }

    .col-vat-sum {
        width: 65px;
    }

    .col-sum-with-vat {
        width: 65px;
    }

    .col-country-code {
        width: 30px;
    }

    .col-country-name {
        width: 30px;
    }

    .col-decl {
        width: 55px;
    }
</style>

<div class="doc landscape upd">

    {{-- Шапка --}}
    <table border="0" cellspacing="0" cellpadding="5">
        <tbody>
        <tr>
            <td width="9.6%">
                Универсальный передаточный документ

                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td style="vertical-align: middle !important;">Статус:</td>
                        <td style="width: 50%; border: 1px solid #000; text-align: center; padding: 5px;">
                            {{ $status }}
                        </td>
                    </tr>
                    </tbody>
                </table>

                1 - счет фактура и передаточный документ (акт)
                <br>
                2 - передаточный документ (акт)
            </td>
            <td style="border-left: 2px solid #000;">
                <table class="upd-compact" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td width="100">Счет фактура №</td>
                        <td width="100" style="border-bottom: 1px solid #000; text-align: center;">
                            {{ $code }}
                        </td>
                        <td width="20">от</td>
                        <td width="100" style="border-bottom: 1px solid #000; text-align: center;">
                            {{ $date }}
                        </td>
                        <td width="20">(1)</td>
                        <td rowspan="2" style="text-align: right; font-size: 8px; padding-left: 5px;">
                            Приложение № 1 к постановлению Правительства РФ от 26.12.2011 № 1137<br>
                            (в редакции постановления Правительства РФ от 02.04.2021 № 534)
                        </td>
                    </tr>
                    <tr>
                        <td>Исправление №</td>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td>от</td>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td>(1а)</td>
                    </tr>
                    </tbody>
                </table>

                <table class="upd-parties" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    {{-- ПРОДАВЕЦ --}}
                    <tr>
                        <td class="label"><b>Продавец:</b></td>
                        <td class="value">{{ $companyname }}</td>
                        <td class="code">(2)</td>
                    </tr>
                    <tr>
                        <td class="label">Адрес:</td>
                        <td class="value">{{ $companyaddress }}</td>
                        <td class="code">(2а)</td>
                    </tr>
                    <tr>
                        <td class="label">ИНН/КПП продавца:</td>
                        <td class="value">{{ $companyinn }}/{{ $companykpp }}</td>
                        <td class="code">(2б)</td>
                    </tr>
                    <tr>
                        <td class="label">Грузоотправитель и его адрес:</td>
                        <td class="value">{{ $companyname }}, {{ $companyaddress }}</td>
                        <td class="code">(3)</td>
                    </tr>
                    <tr>
                        <td class="label">Грузополучатель и его адрес:</td>
                        <td class="value">{{ $clientname }}, {{ $clientaddress }}</td>
                        <td class="code">(4)</td>
                    </tr>
                    <tr>
                        <td class="label">К платежно расчетному документу №</td>
                        <td class="value">{{ $updpp }}</td>
                        <td class="code">(5)</td>
                    </tr>
                    <tr>
                        <td class="label">Документ об отгрузке</td>
                        <td class="value"></td>
                        <td class="code">(5а)</td>
                    </tr>

                    {{-- ПОКУПАТЕЛЬ --}}
                    <tr>
                        <td class="label"><b>Покупатель:</b></td>
                        <td class="value">{{ $clientname }}</td>
                        <td class="code">(6)</td>
                    </tr>
                    <tr>
                        <td class="label">Адрес:</td>
                        <td class="value">{{ $clientaddress }}</td>
                        <td class="code">(6а)</td>
                    </tr>
                    <tr>
                        <td class="label">ИНН/КПП покупателя:</td>
                        <td class="value">{{ $clientinn }}/{{ $clientkpp }}</td>
                        <td class="code">(6б)</td>
                    </tr>
                    <tr>
                        <td class="label">Валюта: наименование, код</td>
                        <td class="value">Российский рубль, 643</td>
                        <td class="code">(7)</td>
                    </tr>
                    <tr>
                        <td class="label">
                            Идентификатор государственного контракта,<br>
                            договора (соглашения) (при наличии):
                        </td>
                        <td class="value"></td>
                        <td class="code">(8)</td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    {{-- Таблица товаров --}}
    <table class="updorderlist upd-compact"
           width="100%"
           border="0"
           cellspacing="0"
           cellpadding="0"
           style="table-layout: fixed;">
        <tbody>
        <tr>
            <td rowspan="2" style="width: 3%;">№ п/п</td>
            <td rowspan="2" style="width: 7%; border-right: 2px solid #000;">
                Код товара / работ, услуг
            </td>
            <td rowspan="2" style="width: 22%;">
                Наименование товара (описание выполненных работ, оказанных услуг), имущественного права
            </td>
            <td rowspan="2" style="width: 4%;">Код вида това-ра</td>
            <td colspan="2" style="width: 8%;">Единица измерения</td>
            <td rowspan="2" style="width: 6%;">Количест-во (объём)</td>
            <td rowspan="2" style="width: 6%;">Цена (тариф) за единицу измерения</td>
            <td rowspan="2" style="width: 8%;">
                Стоимость товаров (работ, услуг), имущест-венных прав без налога - всего
            </td>
            <td rowspan="2" style="width: 5%;">Сумма акциза</td>
            <td rowspan="2" style="width: 5%;">Ставка налога</td>
            <td rowspan="2" style="width: 7%;">
                Сумма налога, предъяв-ляемая покупателю
            </td>
            <td rowspan="2" style="width: 8%;">
                Стоимость товаров (работ, услуг), имущест-венных прав с налогом - всего
            </td>
            <td colspan="2" style="width: 6%; font-size: 7px;">
                Страна происхождения товара
            </td>
            <td rowspan="2" style="width: 5%; border-right: 1px solid #000;">
                Регистраци-онный номер декларации / партии
            </td>
        </tr>
        <tr>
            <td style="width: 4%;">Код</td>
            <td style="width: 4%;">Условн. обозн.</td>
            <td style="width: 3%; font-size: 7px;">Цифр. код</td>
            <td style="width: 3%; font-size: 7px;">Краткое наим.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px;">A</td>
            <td style="border-right: 2px solid #000; text-align: center; font-size: 8px;">1</td>
            <td style="text-align: center; font-size: 8px;">1а</td>
            <td style="text-align: center; font-size: 8px;">1б</td>
            <td style="text-align: center; font-size: 8px;">2</td>
            <td style="text-align: center; font-size: 8px;">2а</td>
            <td style="text-align: center; font-size: 8px;">3</td>
            <td style="text-align: center; font-size: 8px;">4</td>
            <td style="text-align: center; font-size: 8px;">5</td>
            <td style="text-align: center; font-size: 8px;">6</td>
            <td style="text-align: center; font-size: 8px;">7</td>
            <td style="text-align: center; font-size: 8px;">8</td>
            <td style="text-align: center; font-size: 8px;">9</td>
            <td style="text-align: center; font-size: 8px;">10</td>
            <td style="text-align: center; font-size: 8px;">10а</td>
            <td style="text-align: center; font-size: 8px;">11</td>
        </tr>

        @foreach ($items as $index => $item)
            <tr>
                <td style="text-align: center;">
                    {{ $index + 1 }}
                </td>
                <td style="border-right: 2px solid #000;">
                    {{ $item['code'] ?? '' }}
                </td>
                <td>
                    {{ $item['name'] ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $item['code0'] ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $item['unit_code'] ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $item['unit'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['quantity'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['price'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['amount_net'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['excise'] ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $item['vat_rate'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['amount_vat'] ?? '' }}
                </td>
                <td style="text-align: right;">
                    {{ $item['amount_gross'] ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $item['country_code'] ?? '' }}
                </td>
                <td>
                    {{ $item['country_name'] ?? '' }}
                </td>
                <td>
                    {{ $item['declaration_number'] ?? '' }}
                </td>
            </tr>
        @endforeach

        <tr>
            <td></td>
            <td style="border-right: 2px solid #000;"></td>
            <td colspan="6">Всего к оплате (9)</td>
            <td style="text-align: right;">
                {{ number_format($total_without_vat, 2, ',', ' ') }}
            </td>
            <td colspan="2" style="text-align: center;">X</td>
            <td style="text-align: right;">
                {{ number_format($total_vat, 2, ',', ' ') }}
            </td>
            <td style="text-align: right;">
                {{ number_format($total_with_vat, 2, ',', ' ') }}
            </td>
            <td colspan="3"></td>
        </tr>
        </tbody>
    </table>

    {{-- Подписи продавца --}}
    <table class="landscape" border="0" cellspacing="0" cellpadding="5" style="width: 100%">
        <tbody>
        <tr>
            <td style="padding: 5px; width: 9.6%;">
                Документ<br> составлен на<br> {{ $pages_count }} листах
            </td>
            <td style="border-left: 2px solid #000; border-bottom: 2px solid #000; padding-bottom: 5px; margin-left: 10%">

            <table class="upd-compact" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                    {{-- Руководитель --}}
                    <td width="49%">
                        <table class="upd-compact" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tbody>
                            <tr>
                                <td width="160">Руководитель организации</td>
                                <td width="100" style="border-bottom: 1px solid #000;"></td>
                                <td width="5"></td>
                                <td style="border-bottom: 1px solid #000;">{{ $companydirectorname }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td style="text-align: center; font-size: 8px;">(подпись)</td>
                                <td></td>
                                <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>

                    <td width="2%"></td>

                    <td width="49%">
                        <table class="upd-compact" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tbody>
                            <tr>
                                <td width="160">Главный бухгалтер</td>
                                <td width="100" style="border-bottom: 1px solid #000;"></td>
                                <td width="5"></td>
                                <td style="border-bottom: 1px solid #000;">{{ $companydirectorname }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td style="text-align: center; font-size: 8px;">(подпись)</td>
                                <td></td>
                                <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>

            <table class="upd-compact" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                    <td width="180">Индивидуальный предприниматель</td>
                    <td width="100" style="border-bottom: 1px solid #000;"></td>
                    <td width="5"></td>
                    <td style="border-bottom: 1px solid #000;">
                        {{ $companydirectorname }}
                    </td>
                    <td width="5"></td>
                    <td style="border-bottom: 1px solid #000; text-align: left;">
                        {{ $companyogrn }}
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td style="text-align: center; font-size: 8px;">(подпись)</td>
                    <td></td>
                    <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                    <td></td>
                    <td style="text-align: center; font-size: 8px;">
                        (реквизиты свидетельства ИП)
                    </td>
                </tr>
                </tbody>
            </table>
            </td>
        </tr>
        </tbody>
    </table>

    {{-- Основание, транспорт, подписи получателя --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
        <tbody>
        <tr>
            <td width="230">Основание передачи / получения</td>
            <td style="border-bottom: 1px solid #000;">
                {{ $basis }}
            </td>
            <td width="15" style="text-align: center;">[8]</td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: center; font-size: 8px; padding-top: 2px;">
                (договор, доверенность и др.)
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>

    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
        <tbody>
        <tr>
            <td width="200">Данные о транспортировке и грузе</td>
            <td style="border-bottom: 1px solid #000;">
                {{ $transport }}
            </td>
            <td width="15" style="text-align: center;">[9]</td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: center; font-size: 8px; padding-top: 2px;">
                (транспортная накладная, поручение экспедитору, складская расписка, масса нетто/брутто и др.)
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>

    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact"
           style="page-break-inside: avoid;">
        <tbody>
        <tr>
            {{-- Левая колонка: передал --}}
            <td width="50%" style="border-right: 2px solid #000; padding-right: 4px; vertical-align: top;">
                <div style="padding-left: 4px;">Товар передал / услуги сдал</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160" style="border-bottom: 1px solid #000;">
                            {{ $companydirectorposition }}
                        </td>
                        <td width="5"></td>
                        <td width="80" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td style="border-bottom: 1px solid #000;">
                            {{ $companydirectorname }}
                        </td>
                        <td width="10" style="text-align: center;">[10]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">(должность)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(подпись)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                        <td></td>
                    </tr>
                </table>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160">Дата отгрузки</td>
                        <td style="border-bottom: 1px solid #000;">
                            {{ $ship_date_formatted }}
                        </td>
                        <td width="10" style="text-align: center;">[11]</td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">Иные сведения</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td width="10" style="text-align: center;">[12]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">
                            (приложения, сопутствующие документы)
                        </td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">Ответственный за оформление</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160" style="border-bottom: 1px solid #000;">
                            {{ $companydirectorposition }}
                        </td>
                        <td width="5"></td>
                        <td width="80" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td style="border-bottom: 1px solid #000;">
                            {{ $companydirectorname }}
                        </td>
                        <td width="10" style="text-align: center;">[13]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">(должность)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(подпись)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">
                    Наименование экономического субъекта - составителя документа (в т.ч. комиссионера/агента)
                </div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td style="border-bottom: 1px solid #000;">
                            {{ $companyname }}, ИНН/КПП {{ $companyinn }}/{{ $companykpp }}
                        </td>
                        <td width="10" style="text-align: center;">[14]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">
                            (может быть указан ИНН/КПП)
                        </td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 50px;">М.П.</div>
            </td>

            {{-- Правая колонка: принял --}}
            <td width="50%" style="padding-left: 4px; vertical-align: top;">
                <div style="padding-left: 4px;">Товар получил / услуги принял</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td width="80" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td width="10" style="text-align: center;">[15]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">(должность)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(подпись)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                        <td></td>
                    </tr>
                </table>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160">Дата получения</td>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td width="10" style="text-align: center;">[16]</td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">Иные сведения</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td width="10" style="text-align: center;">[17]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">
                            (претензии, приложения)
                        </td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">Ответственный за оформление</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td width="160" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td width="80" style="border-bottom: 1px solid #000;"></td>
                        <td width="5"></td>
                        <td style="border-bottom: 1px solid #000;"></td>
                        <td width="10" style="text-align: center;">[18]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">(должность)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(подпись)</td>
                        <td></td>
                        <td style="text-align: center; font-size: 8px;">(ф.и.о.)</td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 4px;">Составитель документа</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="upd-compact">
                    <tr>
                        <td style="border-bottom: 1px solid #000;">
                            {{ $clientname }}, ИНН/КПП {{ $clientinn }}/{{ $clientkpp }}
                        </td>
                        <td width="10" style="text-align: center;">[19]</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 8px;">
                            (может быть указан ИНН/КПП)
                        </td>
                        <td></td>
                    </tr>
                </table>
                <div style="padding-left: 50px;">М.П.</div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
