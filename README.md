# Laravel UPD

`stacss/laravel-upd` — универсальный пакет для формирования и печати УПД (универсального передаточного документа) в проектах Laravel. Поддерживает НДС, разные типы цен (с НДС / без НДС), Blade-шаблон PDF и тестируемую математику расчётов.

## Возможности

- Формирование УПД в PDF (DomPDF)
- Поддержка НДС: 20%, 10%, 0% и индивидуальные ставки
- Работа с ценами `net` (без НДС) и `gross` (с НДС)
- Автоматический расчет суммы без НДС, суммы НДС и суммы с НДС
- Готовый Blade-шаблон УПД
- Конфигурация реквизитов продавца
- Unit-тесты для расчётов
- Laravel Auto-Discovery

## Требования

- PHP >= 8.1
- Laravel 10 или 11
- barryvdh/laravel-dompdf

## Установка

### 1. Добавить репозиторий

```bash
composer config repositories.stacss-laravel-upd vcs https://github.com/Stacss/laravel-upd
```

### 2. Установить пакет

```bash
composer require stacss/laravel-upd:dev-main
```

### 3. Опубликовать конфиг и шаблон

```bash
php artisan vendor:publish --provider="Stacss\LaravelUpd\Providers\UpdServiceProvider" --tag=upd-config
php artisan vendor:publish --provider="Stacss\LaravelUpd\Providers\UpdServiceProvider" --tag=upd-views
```

## Конфигурация

Файл `config/upd.php` содержит настройки продавца, поведение НДС и режим цен.

Пример `.env`:

```
UPD_SELLER_NAME="АВТОиностранец"
UPD_SELLER_INN="1234567890"
UPD_SELLER_KPP="123456789"
UPD_SELLER_ADDRESS="Кострома, Никитская 88"
UPD_SELLER_PHONE="+79103743335"
```

## Использование

Пример контроллера:

```php
use Stacss\LaravelUpd\UpdRenderer;

public function upd(Order $order, UpdRenderer $upd)
{
    $order->load(['client', 'items.product']);

    $items = $order->items->map(function ($item) {
        return [
            'name'       => $item->product->name ?? 'Товар',
            'unit'       => 'шт',
            'quantity'   => (float) $item->quantity,
            'price'      => $item->price_cents / 100,
            'vat_rate'   => 20.0,
            'price_type' => 'net',
        ];
    })->all();

    $data = [
        'document' => [
            'number' => $order->number ?? $order->uuid,
            'date'   => $order->created_at,
            'status' => 1,
        ],
        'seller' => config('upd.seller'),
        'buyer'  => [
            'name'    => $order->client->name ?? '',
            'inn'     => $order->client->inn  ?? '',
            'kpp'     => $order->client->kpp  ?? '',
            'address' => $order->client->address ?? '',
        ],
        'items' => $items,
    ];

    return $upd->pdf($data)->stream('UPD.pdf');
}
```

## Структура входных данных

Каждая позиция:

```php
[
  'name'       => string,
  'code'       => string|null,
  'unit'       => string,
  'unit_code'  => string|null,
  'quantity'   => float|int,
  'price'      => float,
  'vat_rate'   => float|null,
  'price_type' => 'net'|'gross',
]
```

## Тестирование

Пакет включает unit-тесты для расчета НДС.

Запуск:

```bash
vendor/bin/phpunit
```

## Публикация шаблона

Если нужен кастомный дизайн УПД:

```bash
php artisan vendor:publish --tag=upd-views
```

Blade-файл будет доступен по пути:

```
resources/views/vendor/laravel-upd/upd.blade.php
```

## Лицензия

MIT License

---

Автор: **Stacss**