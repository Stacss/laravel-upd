<?php

declare(strict_types=1);

namespace Stacss\LaravelUpd\Support;

class MoneyToWordsRu
{
    protected array $units = [
        [
            ['ноль'],
            ['один', 'одна'],
            ['два', 'две'],
            ['три'],
            ['четыре'],
            ['пять'],
            ['шесть'],
            ['семь'],
            ['восемь'],
            ['девять'],
        ],
        [
            'десять',
            'одиннадцать',
            'двенадцать',
            'тринадцать',
            'четырнадцать',
            'пятнадцать',
            'шестнадцать',
            'семнадцать',
            'восемнадцать',
            'девятнадцать',
        ],
        [
            '',
            'десять',
            'двадцать',
            'тридцать',
            'сорок',
            'пятьдесят',
            'шестьдесят',
            'семьдесят',
            'восемьдесят',
            'девяносто',
        ],
        [
            '',
            'сто',
            'двести',
            'триста',
            'четыреста',
            'пятьсот',
            'шестьсот',
            'семьсот',
            'восемьсот',
            'девятьсот',
        ],
    ];

    protected array $forms = [
        ['рубль', 'рубля', 'рублей', 0],
        ['тысяча', 'тысячи', 'тысяч', 1],
        ['миллион', 'миллиона', 'миллионов', 0],
        ['миллиард', 'миллиарда', 'миллиардов', 0],
    ];

    public function convert(float $amount): string
    {
        $amount  = round(max(0, $amount), 2);
        $rubles  = (int) floor($amount);
        $kopecks = (int) round(($amount - $rubles) * 100);

        if ($kopecks === 100) {
            $rubles++;
            $kopecks = 0;
        }

        $words       = $this->convertInteger($rubles);
        $rublesLabel = $this->plural($rubles, $this->forms[0][0], $this->forms[0][1], $this->forms[0][2]);

        return trim(sprintf('%s %s %02d копеек', $words, $rublesLabel, $kopecks));
    }

    protected function convertInteger(int $value): string
    {
        if ($value === 0) {
            return 'ноль';
        }

        $parts  = [];
        $chunks = [];

        while ($value > 0) {
            $chunks[] = $value % 1000;
            $value    = (int) floor($value / 1000);
        }

        foreach (array_reverse($chunks, true) as $index => $chunk) {
            if ($chunk === 0) {
                continue;
            }

            $parts[] = $this->convertChunk($chunk, $this->forms[$index][3] ?? 0);

            if ($index > 0) {
                $parts[] = $this->plural(
                    $chunk,
                    $this->forms[$index][0],
                    $this->forms[$index][1],
                    $this->forms[$index][2]
                );
            }
        }

        return trim(implode(' ', array_filter($parts)));
    }

    protected function convertChunk(int $value, int $gender): string
    {
        $words     = [];
        $hundreds  = (int) floor($value / 100);
        $tensUnits = $value % 100;
        $tens      = (int) floor($tensUnits / 10);
        $units     = $tensUnits % 10;

        if ($hundreds > 0) {
            $words[] = $this->units[3][$hundreds];
        }

        if ($tensUnits >= 10 && $tensUnits < 20) {
            $words[] = $this->units[1][$tensUnits - 10];

            return implode(' ', $words);
        }

        if ($tens > 0) {
            $words[] = $this->units[2][$tens];
        }

        if ($units > 0) {
            $unit    = $this->units[0][$units];
            $words[] = is_array($unit) ? $unit[$gender] : $unit;
        }

        return implode(' ', $words);
    }

    protected function plural(int $value, string $one, string $few, string $many): string
    {
        $value = abs($value) % 100;
        $tail  = $value      % 10;

        if ($value > 10 && $value < 20) {
            return $many;
        }

        if ($tail > 1 && $tail < 5) {
            return $few;
        }

        if ($tail === 1) {
            return $one;
        }

        return $many;
    }
}
