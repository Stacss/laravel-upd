<?php

declare(strict_types=1);

namespace Stacss\LaravelUpd;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use DateTimeInterface;
use Stacss\LaravelUpd\Support\MoneyToWordsRu;

class InvoiceRenderer
{
    public function __construct(
        protected ?VatCalculator $calculator = null,
        protected ?MoneyToWordsRu $moneyToWords = null,
    ) {
        $this->calculator       ??= new VatCalculator();
        $this->moneyToWords     ??= new MoneyToWordsRu();
    }

    public function pdf(array $data): DomPdf
    {
        $view = (string) config('upd.invoice_view', 'laravel-upd::invoice');

        return Pdf::loadView($view, $this->prepareData($data))
            ->setPaper('a4', 'portrait');
    }

    protected function prepareData(array $data): array
    {
        $documentDefaults = (array) config('upd.invoice_defaults', []);
        $sellerDefaults   = (array) config('upd.seller', []);
        $bankDefaults     = (array) config('upd.bank_details', []);

        $document   = array_merge($documentDefaults, (array) ($data['document'] ?? []));
        $seller     = array_merge($sellerDefaults, (array) ($data['seller'] ?? []));
        $sellerBank = array_merge($bankDefaults, (array) ($data['seller_bank'] ?? []));
        $buyer      = (array) ($data['buyer'] ?? []);
        $payment    = array_merge(
            [
                'purpose'  => '',
                'vat_text' => '',
                'comment'  => (string) ($documentDefaults['comment'] ?? ''),
            ],
            (array) ($data['payment'] ?? [])
        );
        $signatures = array_merge(
            [
                'director'   => '',
                'accountant' => '',
            ],
            (array) ($data['signatures'] ?? [])
        );

        $items         = (array) ($data['items'] ?? []);
        $calculated    = $this->calculator->calculate($items);
        $preparedItems = $calculated['items']  ?? [];
        $totals        = $calculated['totals'] ?? ['net' => 0.0, 'vat' => 0.0, 'gross' => 0.0];

        $formattedDocument = [
            'number'   => trim((string) ($document['number'] ?? '')),
            'date'     => $this->formatDate($document['date'] ?? null),
            'due_date' => $this->formatDate($document['due_date'] ?? null),
            'base'     => trim((string) ($document['base'] ?? '')),
            'contract' => trim((string) ($document['contract'] ?? '')),
        ];

        $payment['purpose']  = $this->resolvePaymentPurpose($payment, $formattedDocument);
        $payment['vat_text'] = $this->resolveVatText($payment, $totals);
        $payment['comment']  = trim((string) ($payment['comment'] ?? ''));

        $amountInWords = $this->moneyToWords->convert((float) ($totals['gross'] ?? 0.0));

        return [
            'document'         => $formattedDocument,
            'seller'           => $this->normalizeParty($seller),
            'seller_bank'      => $this->normalizeBank($sellerBank),
            'buyer'            => $this->normalizeParty($buyer),
            'items'            => $preparedItems,
            'totals'           => $totals,
            'payment'          => $payment,
            'signatures'       => $signatures,
            'amount_in_words'  => $amountInWords,
            'invoice_defaults' => $documentDefaults,
        ];
    }

    protected function normalizeParty(array $party): array
    {
        return [
            'name'       => trim((string) ($party['name'] ?? '')),
            'short_name' => trim((string) ($party['short_name'] ?? ($party['name'] ?? ''))),
            'inn'        => trim((string) ($party['inn'] ?? '')),
            'kpp'        => trim((string) ($party['kpp'] ?? '')),
            'ogrn'       => trim((string) ($party['ogrn'] ?? '')),
            'address'    => trim((string) ($party['address'] ?? '')),
            'phone'      => trim((string) ($party['phone'] ?? '')),
            'email'      => trim((string) ($party['email'] ?? '')),
        ];
    }

    protected function normalizeBank(array $bank): array
    {
        return [
            'bank_name'    => trim((string) ($bank['bank_name'] ?? '')),
            'bik'          => preg_replace('/\D+/', '', (string) ($bank['bik'] ?? ''))          ?? '',
            'account'      => preg_replace('/\D+/', '', (string) ($bank['account'] ?? ''))      ?? '',
            'corr_account' => preg_replace('/\D+/', '', (string) ($bank['corr_account'] ?? '')) ?? '',
        ];
    }

    protected function resolvePaymentPurpose(array $payment, array $document): string
    {
        $purpose = trim((string) ($payment['purpose'] ?? ''));
        if ($purpose !== '') {
            return $purpose;
        }

        $number = $document['number'] ?? '';
        $date   = $document['date']   ?? '';

        if ($number === '' && $date === '') {
            return '';
        }

        return trim(sprintf('Оплата счета №%s от %s', $number, $date));
    }

    protected function resolveVatText(array $payment, array $totals): string
    {
        $vatText = trim((string) ($payment['vat_text'] ?? ''));
        if ($vatText !== '') {
            return $vatText;
        }

        $vat = round((float) ($totals['vat'] ?? 0.0), 2);
        if ($vat <= 0) {
            return 'Без НДС';
        }

        return 'В том числе НДС: ' . $this->formatMoney($vat) . ' руб.';
    }

    protected function formatDate(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value;
        }

        return '';
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ');
    }
}
