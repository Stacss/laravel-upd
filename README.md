# Laravel-UPD

**Laravel-UPD** — пакет для генерации PDF документов:

- УПД (универсальный передаточный документ, Постановление №1137)
- Акта сверки взаиморасчётов за период

Пакет предоставляет:

- готовый шаблон УПД (ландшафт)
- шаблон акта сверки (портрет, A4)
- расчёт НДС (net/gross)
- рендер PDF через `barryvdh/laravel-dompdf`
- поддержку Laravel 10 и 11
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

    // шаблон УПД
    'view' => 'laravel-upd::upd',

    // шаблон акта сверки
    'reconciliation_view' => 'laravel-upd::reconciliation',
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