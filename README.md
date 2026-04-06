# Laravel-UPD

`stacss/laravel-upd` — Laravel package для генерации PDF-документов:

- УПД
- акта сверки
- счета на оплату

Пакет использует `barryvdh/laravel-dompdf`, считает НДС через встроенный `VatCalculator` и отдает готовый `Barryvdh\DomPDF\PDF`.

## Совместимость

Поддерживаются:

- PHP `8.1+`
- Laravel `10`
- Laravel `11`
- Laravel `12`

Пакет не заявляет поддержку всех версий Laravel. По текущим constraints в [composer.json](/home/stacss/dev/laravel-upd/composer.json) поддерживаются только `illuminate/support ^10|^11|^12`.

## Установка

Установка в обычный Laravel-проект:

```bash
composer require stacss/laravel-upd
```

Публикация конфига и шаблонов:

```bash
php artisan vendor:publish --tag=upd-config
php artisan vendor:publish --tag=upd-views
```

## Быстрый старт

### УПД

```php
use Stacss\LaravelUpd\UpdRenderer;

$renderer = app(UpdRenderer::class);

$pdf = $renderer->pdf([
    'document' => [
        'number' => '123',
        'date' => now(),
        'status' => 1,
    ],
    'seller' => config('upd.seller'),
    'buyer' => [
        'name' => 'ООО Покупатель',
        'inn' => '1234567890',
        'kpp' => '123456789',
        'address' => 'г. Москва',
    ],
    'items' => [
        [
            'name' => 'Тестовый товар',
            'code' => 'TST-001',
            'unit' => 'шт',
            'unit_code' => '796',
            'quantity' => 2,
            'price' => 100,
            'vat_rate' => 20,
            'price_type' => 'net',
        ],
    ],
]);

$pdf->save(storage_path('upd.pdf'));
```

### Акт сверки

```php
use Stacss\LaravelUpd\ReconciliationRenderer;

$renderer = app(ReconciliationRenderer::class);

$pdf = $renderer->pdf([
    'act_date' => now(),
    'period' => [
        'from' => now()->startOfMonth(),
        'to' => now()->endOfMonth(),
    ],
    'left' => [
        'full_name' => 'ООО Ромашка',
        'short_name' => 'ООО Ромашка',
        'director_post' => 'генерального директора',
        'director_full' => 'Иванова Ивана Ивановича',
        'director_short' => 'Иванов И. И.',
        'director_sign_note' => 'Иванов ' . now()->format('d.m.Y'),
        'basis' => 'Устава',
    ],
    'right' => [
        'full_name' => 'ООО Покупатель',
        'short_name' => 'ООО Покупатель',
        'director_post' => 'генерального директора',
        'director_full' => 'Петрова Петра Петровича',
        'director_short' => 'Петров П. П.',
        'director_sign_note' => 'Петров ' . now()->format('d.m.Y'),
        'basis' => 'Устава',
    ],
    'opening' => [
        'date' => now()->startOfMonth(),
        'balance' => 0,
    ],
    'closing' => [
        'date' => now()->endOfMonth(),
        'balance' => 10000,
    ],
    'operations' => [
        [
            'title' => 'Накладная №1',
            'debit' => 10000,
            'credit' => 0,
        ],
    ],
]);

$pdf->save(storage_path('reconciliation.pdf'));
```

### Счет на оплату

```php
use Stacss\LaravelUpd\InvoiceRenderer;

$renderer = app(InvoiceRenderer::class);

$pdf = $renderer->pdf([
    'document' => [
        'number' => '15',
        'date' => now(),
        'due_date' => now()->addDays(5),
        'base' => 'Оплата по счету за товары',
        'contract' => 'Договор №5 от 01.01.2026',
    ],
    'seller' => [
        'name' => 'ООО Ромашка',
        'short_name' => 'ООО Ромашка',
        'inn' => '7700000000',
        'kpp' => '770001001',
        'ogrn' => '1027700000000',
        'address' => 'г. Москва, ул. Пример, д. 1',
        'phone' => '+7 (999) 123-45-67',
        'email' => 'info@example.ru',
    ],
    'seller_bank' => [
        'bank_name' => 'ПАО Сбербанк',
        'bik' => '044525225',
        'account' => '40702810900000000001',
        'corr_account' => '30101810400000000225',
    ],
    'buyer' => [
        'name' => 'ООО Покупатель',
        'short_name' => 'ООО Покупатель',
        'inn' => '7800000000',
        'kpp' => '780001001',
        'address' => 'г. Санкт-Петербург, ул. Тестовая, д. 5',
    ],
    'items' => [
        [
            'name' => 'Товар 1',
            'brand' => 'ACME',
            'code' => 'ABC-001',
            'unit' => 'шт',
            'unit_code' => '796',
            'quantity' => 2,
            'price' => 1000,
            'vat_rate' => 20,
            'price_type' => 'net',
        ],
    ],
    'payment' => [
        'comment' => 'Без доверенности. Оплата означает согласие с условиями поставки.',
    ],
    'signatures' => [
        'director' => 'Иванов И.И.',
        'accountant' => 'Петров П.П.',
    ],
]);

$pdf->save(storage_path('invoice.pdf'));
```

## Формат данных для счета

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
- `seller.short_name`
- `seller.ogrn`
- `seller.phone`
- `seller.email`
- `seller_bank.corr_account`
- `items[*].brand`
- `items[*].code`
- `items[*].unit_code`
- `payment.purpose`
- `payment.vat_text`
- `payment.comment`
- `signatures.director`
- `signatures.accountant`

Примечания:

- если `buyer` не передан, счет все равно будет сгенерирован
- если `payment.purpose` не передан, пакет сформирует строку автоматически
- если `payment.vat_text` не передан, пакет сформирует его из рассчитанного НДС
- колонки `Бренд` и `Код` выводятся только если хотя бы у одной позиции есть непустые значения
- `brand` нормализуется из `brand`, `brand_name`, `manufacturer`, `vendor`
- `code` нормализуется из `code`, `sku`, `article`

## Конфигурация

Файл `config/upd.php`:

```php
return [
    'seller' => [
        'name' => 'АВТОиностранец',
        'inn' => '',
        'kpp' => '',
        'address' => '',
        'phone' => '',
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

## Если опубликованы шаблоны

Если вы уже публиковали views пакета в проекте-потребителе, Laravel будет использовать их вместо шаблонов из `vendor`.

После обновления шаблонов пакета перепубликуйте их:

```bash
php artisan vendor:publish --tag=upd-views --force
php artisan view:clear
```

Если вы меняли опубликованные шаблоны вручную, перепубликация с `--force` их перезапишет.

## Тестирование

```bash
composer test
```

## Стиль кода

Проверка:

```bash
composer cs
```

Исправление:

```bash
composer cs-fix
```

## Лицензия

MIT
