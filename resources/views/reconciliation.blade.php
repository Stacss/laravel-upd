<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Акт сверки</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
        }

        .page {
           width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mt-5 {
            margin-top: 5px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mt-30 {
            margin-top: 30px;
        }

        .title-main {
            font-weight: bold;
            font-size: 16pt;
        }

        .title-sub {
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        table.reconcile {
            margin-top: 15px;
            font-size: 10pt;
        }

        table.reconcile th,
        table.reconcile td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        table.reconcile th {
            text-align: center;
            font-weight: bold;
        }

        .nowrap {
            white-space: nowrap;
        }

        .summary-table {
            width: 100%;
            margin-top: 20px;
            font-size: 10pt;
        }

        .summary-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        .signature-table {
            width: 100%;
            margin-top: 25px;
            font-size: 10pt;
        }

        .signature-table td {
            padding: 6px 8px;
            vertical-align: top;
        }

        .small {
            font-size: 9pt;
        }

        em {
            font-style: italic;
        }

        @media print {
            body {
                margin: 0;
            }

            .page {
                margin: 0;
                padding: 15mm 15mm;
            }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="text-center">
        <div class="title-main">Акт сверки</div>
        <div class="title-sub mt-5">
            взаимных расчетов по состоянию на {{ $actDate }}<br>
            между {{ $companyLeft['full_name'] }} и {{ $companyRight['full_name'] }}
        </div>
    </div>

    <div class="mt-20">
        {{ $companyLeft['full_name'] }} в лице {{ $companyLeft['director_post'] }}
        {{ $companyLeft['director_full'] }}, действующего на основании {{ $companyLeft['basis'] }},
        с одной стороны и {{ $companyRight['full_name'] }} в лице {{ $companyRight['director_post'] }}
        {{ $companyRight['director_full'] }}, действующего на основании {{ $companyRight['basis'] }},
        с другой стороны, совместно именуемые «Стороны», составили настоящий акт сверки.
    </div>

    <div class="mt-10">
        Состояние взаимных расчетов по данным учета следующее:
    </div>

    <table class="reconcile mt-10">
        <thead>
        <tr>
            <th colspan="4">По данным {{ $companyLeft['short_name'] }}, руб.</th>
            <th colspan="4">По данным {{ $companyRight['short_name'] }}, руб.</th>
        </tr>
        <tr>
            <th style="width:5%">№ п/п</th>
            <th style="width:25%">Документ</th>
            <th style="width:10%">Дебет</th>
            <th style="width:10%">Кредит</th>
            <th style="width:5%">№ п/п</th>
            <th style="width:25%">Документ</th>
            <th style="width:10%">Дебет</th>
            <th style="width:10%">Кредит</th>
        </tr>
        </thead>
        <tbody>
        {{-- Сальдо на начало периода --}}
        <tr>
            <td></td>
            <td>Сальдо на {{ $periodStart }}</td>
            <td class="text-right">{{ $openingSaldoLeftDebit }}</td>
            <td class="text-right">{{ $openingSaldoLeftCredit }}</td>

            <td></td>
            <td>Сальдо на {{ $periodStart }}</td>
            <td class="text-right">{{ $openingSaldoRightDebit }}</td>
            <td class="text-right">{{ $openingSaldoRightCredit }}</td>
        </tr>

        {{-- Операции --}}
        @foreach($operations as $index => $op)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $op['title'] }}</td>
                <td class="text-right">{{ $op['left_debit'] }}</td>
                <td class="text-right">{{ $op['left_credit'] }}</td>

                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $op['title'] }}</td>
                <td class="text-right">{{ $op['right_debit'] }}</td>
                <td class="text-right">{{ $op['right_credit'] }}</td>
            </tr>
        @endforeach

        {{-- Обороты за период --}}
        <tr>

            <td colspan="2">Обороты за период</td>
            <td class="text-right">{{ $turnoverLeftDebit }}</td>
            <td class="text-right">{{ $turnoverLeftCredit }}</td>

            <td colspan="2">Обороты за период</td>
            <td class="text-right">{{ $turnoverRightDebit }}</td>
            <td class="text-right">{{ $turnoverRightCredit }}</td>
        </tr>

        {{-- Сальдо на конец периода --}}
        <tr>
            <td colspan="2">Сальдо на {{ $actDate }}</td>
            <td class="text-right">{{ $closingSaldoLeftDebit }}</td>
            <td class="text-right">{{ $closingSaldoLeftCredit }}</td>

            <td colspan="2">Сальдо на {{ $actDate }}</td>
            <td class="text-right">{{ $closingSaldoRightDebit }}</td>
            <td class="text-right">{{ $closingSaldoRightCredit }}</td>
        </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>
                По данным {{ $companyLeft['short_name'] }}, на {{ $actDate }} задолженность
                в пользу {{ $beneficiaryName }} - {{ $closingSaldoText }} рублей.
            </td>
            <td>
                По данным {{ $companyRight['short_name'] }}, на {{ $actDate }} задолженность
                в пользу {{ $beneficiaryName }} - {{ $closingSaldoText }} рублей.
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                От {{ $companyLeft['short_name'] }}<br>
                {{ $companyLeft['director_post'] }}<br>
                <em>{{ $companyLeft['director_sign_note'] }}</em><br>
                {{ $companyLeft['director_short'] }}<br>
                М. П.
            </td>
            <td>
                От {{ $companyRight['short_name'] }}<br>
                {{ $companyRight['director_post'] }}<br>
                <em>{{ $companyRight['director_sign_note'] }}</em><br>
                {{ $companyRight['director_short'] }}<br>
                М. П.
            </td>
        </tr>
    </table>

</div>
</body>
</html>
