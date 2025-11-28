# Laravel-UPD

**Laravel-UPD** --- пакет для генерации PDF УПД (универсальный
передаточный документ, форма Постановления №1137) под Laravel.

Пакет предоставляет:

-   шаблон УПД в ландшафтной ориентации
-   расчёт НДС (net/gross)
-   рендер PDF через `barryvdh/laravel-dompdf`
-   поддержку Laravel 10 и 11
-   тесты (PHPUnit + Testbench)
-   стиль кода через php‑cs‑fixer

------------------------------------------------------------------------

## Установка

``` bash
composer require stacss/laravel-upd
```

Публикация конфигурации и шаблонов:

``` bash
php artisan vendor:publish --tag=upd-config
php artisan vendor:publish --tag=upd-views
```

------------------------------------------------------------------------

## Пример использования

``` php
use Stacss\LaravelUpd\UpdRenderer;

$renderer = app(UpdRenderer::class);

$pdf = $renderer->pdf([
    'document' => [
        'number' => '123',
        'date'   => now(),
        'status' => 1
    ],
    'seller' => config('upd.seller'),
    'buyer' => [
        'name' => 'ООО Покупатель',
        'inn'  => '1234567890',
        'kpp'  => '123456789',
        'address' => 'г. Москва'
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
            'price_type' => 'net'
        ]
    ]
]);

$pdf->save(storage_path('upd.pdf'));
```

------------------------------------------------------------------------

## Конфигурация

Файл `config/upd.php`:

``` php
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
];
```

------------------------------------------------------------------------

## Тестирование

``` bash
composer test
```

------------------------------------------------------------------------

## Стиль кода

Проверка:

``` bash
composer cs
```

Исправление:

``` bash
composer cs-fix
```

------------------------------------------------------------------------

## Лицензия

MIT License.