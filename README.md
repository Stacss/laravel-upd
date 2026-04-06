# Laravel-UPD

**Laravel-UPD** — пакет для генерации PDF документов:

- УПД (универсальный передаточный документ, Постановление №1137)
- Акта сверки взаиморасчётов за период
- Счета на оплату для РФ

Пакет предоставляет:

- готовый шаблон УПД (ландшафт)
- шаблон акта сверки (портрет, A4)
- шаблон счета на оплату (портрет, A4)
- расчёт НДС (net/gross)
- сумма прописью на русском языке
- рендер PDF через `barryvdh/laravel-dompdf`
- поддержку Laravel 10, 11 и 12
- тесты (PHPUnit + Testbench)
- единый стиль кода (php-cs-fixer)

---

## Установка

```bash
composer require stacss/laravel-upd
```

Публикация конфигурации и шаблонов:

```bash
php artisan vendor:publish --tag=upd-config
php artisan vendor:publish --tag=upd-views
```

---

## Пример использования: УПД

```php
use Stacss\LaravelUpd\UpdRenderer;

$renderer = app(UpdRenderer::class);

$pdf = $renderer->pdf([
    'document' => [
        'number' => '123',
        'date'   => now(),
        'status' => 1,
    ],
    'seller' => config('upd.seller'),
    'buyer' => [
        'name'    => 'ООО Покупатель',
        'inn'     => '1234567890',
        'kpp'     => '123456789',
        'address' => 'г. Москва',
    ],
    'items' => [
        [
            'name'       => 'Тестовый товар',
            'code'       => 'TST-001',
            'unit'       => 'шт',
            'unit_code'  => '796',
            'quantity'   => 2,
            'price'      => 100,
            'vat_rate'   => 20,
            'price_type' => 'net',
        ],
    ],
]);

$pdf->save(storage_path('upd.pdf'));
```

---

## Пример использования: Акт сверки

```php
use Stacss\LaravelUpd\ReconciliationRenderer;

$renderer = app(ReconciliationRenderer::class);

$pdf = $renderer->pdf([
    'contract' => [
        'number' => 'CTR-12/34',
        'date'   => now()->subYear(),
    ],
    'period' => [
        'from' => now()->startOfMonth(),
        'to'   => now()->endOfMonth(),
    ],
    'left' => [
        'name' => config('upd.seller.name'),
        'inn'  => config('upd.seller.inn'),
        'kpp'  => config('upd.seller.kpp'),
        'rs'   => '40702810900000000001',
        'bank' => 'ПАО "Тестбанк"',
        'bik'  => '044525225',
    ],
    'right' => [
        'name' => 'ООО Поставщик',
        'inn'  => '9988776655',
        'kpp'  => '112233445',
        'rs'   => '40702810900000000002',
        'bank' => 'ПАО "Поставщикбанк"',
        'bik'  => '044525111',
    ],
    'opening' => [
        'date'    => now()->startOfMonth()->subDay(),
        'debit'   => 0,
        'credit'  => 0,
        'balance' => 0,
    ],
    'closing' => [
        'date'    => now()->endOfMonth(),
        'debit'   => 10000,
        'credit'  => 0,
        'balance' => 10000,
    ],
    'totals' => [
        'debit'   => 10000,
        'credit'  => 0,
        'balance' => 10000,
    ],
    'summary' => [
        'debt_left'  => '10 000,00 руб.',
        'debt_right' => '0,00 руб.',
    ],
    'rows' => [
        [
            'date'        => now()->format('d.m.Y'),
            'document'    => 'Поступление №1',
            'description' => 'Поставка товара',
            'debit'       => '10 000,00',
            'credit'      => '0,00',
            'balance'     => '10 000,00',
        ],
    ],
]);

$pdf->save(storage_path('reconciliation.pdf'));
```

---

## Пример использования: Счет на оплату

```php
use Stacss\LaravelUpd\InvoiceRenderer;

$renderer = app(InvoiceRenderer::class);

$pdf = $renderer->pdf([
    'document' => [
        'number' => '15',
        'date' => now(),
        'due_date' => now()->addDays(5), // optional
        'base' => 'Оплата по счету за товары', // optional
        'contract' => 'Договор №5 от 01.01.2026', // optional
    ],
    'seller' => [
        'name' => 'ООО Ромашка',
        'short_name' => 'ООО Ромашка', // optional
        'inn' => '7700000000',
        'kpp' => '770001001',
        'ogrn' => '1027700000000', // optional
        'address' => 'г. Москва, ул. Пример, д. 1',
        'phone' => '+7 (999) 123-45-67', // optional
        'email' => 'info@example.ru', // optional
    ],
    'seller_bank' => [
        'bank_name' => 'ПАО Сбербанк',
        'bik' => '044525225',
        'account' => '40702810900000000001',
        'corr_account' => '30101810400000000225',
    ],
    'buyer' => [
        'name' => 'ООО Покупатель', // optional
        'short_name' => 'ООО Покупатель', // optional
        'inn' => '7800000000', // optional
        'kpp' => '780001001', // optional
        'ogrn' => '1027800000000', // optional
        'address' => 'г. Санкт-Петербург, ул. Тестовая, д. 5', // optional
        'phone' => '', // optional
        'email' => '', // optional
    ],
    'items' => [
        [
            'name' => 'Товар 1',
            'code' => 'ABC-001', // optional
            'brand' => 'ACME', // optional
            'unit' => 'шт',
            'unit_code' => '796', // optional
            'quantity' => 2,
            'price' => 1000,
            'vat_rate' => 20,
            'price_type' => 'net',
        ],
    ],
    'payment' => [
        'purpose' => 'Оплата счета №15 от 05.04.2026', // optional
        'vat_text' => 'В том числе НДС 20%', // optional
        'comment' => 'Без доверенности. Оплата означает согласие с условиями поставки.', // optional
    ],
    'signatures' => [
        'director' => 'Иванов И.И.', // optional
        'accountant' => 'Петров П.П.', // optional
    ],
]);

$pdf->save(storage_path('invoice.pdf'));
```

Что формируется в счете:

- номер и дата счета
- реквизиты продавца
- банковские реквизиты продавца
- реквизиты покупателя, если переданы
- таблица позиций
- итоги без НДС, НДС и с НДС
- сумма прописью
- назначение платежа
- блок подписей

Обязательные поля:

- `document.number`
- `document.date`
- `seller.name`
- `seller_bank.bank_name`
- `seller_bank.bik`
- `seller_bank.account`
- `items[*].name`
- `items[*].unit`
- `items[*].quantity`
- `items[*].price`

Опциональные поля:

- весь блок `buyer`
- `document.due_date`
- `document.base`
- `document.contract`
- `seller.short_name`, `seller.ogrn`, `seller.phone`, `seller.email`
- `seller_bank.corr_account`
- `items[*].code`, `items[*].brand`, `items[*].unit_code`
- `payment.purpose`, `payment.vat_text`, `payment.comment`
- `signatures.director`, `signatures.accountant`

Примечания:

- `buyer` можно не передавать целиком, счет все равно будет сгенерирован
- `payment.purpose` и `payment.vat_text` могут быть не заданы, пакет сформирует безопасные значения автоматически
- расчет сумм по позициям выполняется через `VatCalculator`, как и в УПД

---

## Конфигурация

Файл `config/upd.php`:

```php
return [
    'seller' => [
        'name'    => 'АВТОиностранец',
        'inn'     => '',
        'kpp'     => '',
        'address' => '',
        'phone'   => '',
    ],

    'default_vat_rate' => 20.0,
    'default_price_type' => 'net',

    'view' => 'laravel-upd::upd',
    'reconciliation_view' => 'laravel-upd::reconciliation',
    'invoice_view' => 'laravel-upd::invoice',

    'bank_details' => [
        'bank_name' => '',
        'bik' => '',
        'account' => '',
        'corr_account' => '',
    ],

    'invoice_defaults' => [
        'base' => '',
        'contract' => '',
        'comment' => '',
    ],

];
```

---

## Тестирование

```bash
composer test
```

---

## Стиль кода

Проверка:

```bash
composer cs
```

Исправление:

```bash
composer cs-fix
```

---

## Лицензия

MIT License.
