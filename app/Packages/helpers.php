<?php
/**
 * File: helpers.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

use App\Packages\Stub;
use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

if (!defined('BREADCRUMB_CATALOG')) {
    define('BREADCRUMB_CATALOG', ['url' => '/catalog', 'title' => 'Catalog']);
}

if (!defined('BREADCRUMB_SHOPS')) {
    define('BREADCRUMB_SHOPS', ['url' => '/shop', 'title' => 'Shops']);
}

if (!defined('BREADCRUMB_ORDERS')) {
    define('BREADCRUMB_ORDERS', ['url' => '/orders', 'title' => 'Orders']);
}

if (!defined('BREADCRUMB_MANAGEMENT_GOODS')) {
    define('BREADCRUMB_MANAGEMENT_GOODS', ['url' => '/shop/management/goods', 'title' => 'Goods']);
}

if (!defined('BREADCRUMB_MANAGEMENT_ORDERS')) {
    define('BREADCRUMB_MANAGEMENT_ORDERS', ['url' => '/shop/management/orders', 'title' => 'Orders']);
}

if (!function_exists('btc2usd')) {
    function btc2usd($amount, $decimals = 2)
    {
        $amount = BitcoinUtils::btcToUsd($amount);
        return is_numeric($amount) ? round($amount, $decimals, PHP_ROUND_HALF_UP) : $amount;
    }
}

if (!function_exists('btc2rub')) {
    function btc2rub($amount, $decimals = 2)
    {
        $amount = BitcoinUtils::btcToRub($amount);
        return is_numeric($amount) ? round($amount, $decimals, PHP_ROUND_HALF_UP) : $amount;
    }
}

if (!function_exists('usd2btc')) {
    function usd2btc($amount, $decimals = 6)
    {
        $amount = BitcoinUtils::usdToBtc($amount);
        return is_numeric($amount) ? round($amount, $decimals, PHP_ROUND_HALF_UP) : $amount;
    }
}

if (!function_exists('rub2btc')) {
    function rub2btc($amount, $decimals = 6)
    {
        $amount = BitcoinUtils::rubToBtc($amount);
        return is_numeric($amount) ? round($amount, $decimals, PHP_ROUND_HALF_UP) : $amount;
    }
}

if (!function_exists('plural')) {
    /**
     * @param $n
     * @param $forms 1 - 2 - 10:  array('арбуз', 'арбуза', 'арбузов')
     * @return mixed
     */
    function plural($n, $forms)
    {
        return $n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2]);
    }
}

if (!function_exists('trim_zeros')) {
    function trim_zeros($value)
    {
        if (Str::contains($value, '.')) { // only if float
            $value = rtrim($value, '0'); // remove zeros at the end
            $value = rtrim($value, '.'); // remove dot if it exists only removing zeroes
        }

        return $value;
    }
}

if (!function_exists('round_price')) {
    function round_price($price, $currency)
    {
        if ($currency === BitcoinUtils::CURRENCY_BTC) {
            return round($price, 6, PHP_ROUND_HALF_UP);
        } else {
            return round($price, 2, PHP_ROUND_HALF_UP);
        }
    }
}

if (!function_exists('human_price')) {
    function human_price($price, $currency)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return '-';
        }

        return Formatters::getHumanPrice($price, $currency);
    }
}

if (!function_exists('stub')) {
    function stub($class, array $properties)
    {
        return new Stub($class, $properties);
    }
}

if (!function_exists('traverse')) {
    function traverse($object, $path)
    {
        try {
            $result = null;
            eval('$result = $object->' . $path . ';');
            return $result;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('noavatar')) {
    function noavatar()
    {
        return '/assets/img/no-avatar.gif';
    }
}

if (!function_exists('transliterate')) {
    function transliterate($title, $separator = '-')
    {
        $matrix = [
            'й' => 'i', 'ц' => 'c', 'у' => 'u', 'к' => 'k', 'е' => 'e',
            'н' => 'n', 'г' => 'g', 'ш' => 'sh', 'щ' => 'sch', 'з' => 'z',
            'х' => 'h', 'ъ' => '', 'ф' => 'f', 'ы' => 'y', 'в' => 'v',
            'а' => 'a', 'п' => 'p', 'р' => 'r', 'о' => 'o', 'л' => 'l',
            'д' => 'd', 'ж' => 'zh', 'э' => 'e', 'ё' => 'e', 'я' => 'ya',
            'ч' => 'ch', 'с' => 's', 'м' => 'm', 'и' => 'i', 'т' => 't',
            'ь' => '', 'б' => 'b', 'ю' => 'yu', 'ү' => 'u', 'қ' => 'k',
            'ғ' => 'g', 'ә' => 'e', 'ң' => 'n', 'ұ' => 'u', 'ө' => 'o',
            'Һ' => 'h', 'һ' => 'h', 'і' => 'i', 'ї' => 'ji', 'є' => 'je',
            'ґ' => 'g', 'Й' => 'I', 'Ц' => 'C', 'У' => 'U', 'Ұ' => 'U',
            'Ө' => 'O', 'К' => 'K', 'Е' => 'E', 'Н' => 'N', 'Г' => 'G',
            'Ш' => 'SH', 'Ә' => 'E', 'Ң ' => 'N', 'З' => 'Z', 'Х' => 'H',
            'Ъ' => '', 'Ф' => 'F', 'Ы' => 'Y', 'В' => 'V', 'А' => 'A',
            'П' => 'P', 'Р' => 'R', 'О' => 'O', 'Л' => 'L', 'Д' => 'D',
            'Ж' => 'ZH', 'Э' => 'E', 'Ё' => 'E', 'Я' => 'YA', 'Ч' => 'CH',
            'С' => 'S', 'М' => 'M', 'И' => 'I', 'Т' => 'T', 'Ь' => '',
            'Б' => 'B', 'Ю' => 'YU', 'Ү' => 'U', 'Қ' => 'K', 'Ғ' => 'G',
            'Щ' => 'SCH', 'І' => 'I', 'Ї' => 'YI', 'Є' => 'YE', 'Ґ' => 'G',
        ];
        foreach ($matrix as $from => $to) {
            $title = mb_eregi_replace($from, $to, $title);
        }

        $pattern = '![^' . preg_quote($separator) . '\pL\pN\s]+!u';
        $title = preg_replace($pattern, '', ucwords($title));
        $flip = $separator == '-' ? '_' : '-';
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);
        return trim($title, $separator);
    }
}

if (!function_exists('qrcode')) {
    function qrcode($text, $size = 200)
    {
        $renderer = new Png();
        $renderer->setWidth($size);
        $renderer->setHeight($size);

        $writer = new Writer($renderer);
        $data = $writer->writeString($text, 'utf-8');

        return 'data:image/png;base64,' . base64_encode($data);
    }
}

if (!function_exists('autofocus_on_desktop')) {
    function autofocus_on_desktop()
    {
        $agent = new Agent();
        if ($agent->isDesktop()) {
            return 'autofocus';
        }

        return '';
    }
}

if (!function_exists('collection_paginate')) {
    /**
     * @param \Illuminate\Support\Collection $items
     * @param int $perPage
     * @param string $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    function collection_paginate($items, $perPage, $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $count = $items->count();
        $items = $items->slice(($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($items, $count, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
}

if (!function_exists('catalog_jump_url')) {
    function catalog_jump_url($shopId, $route, $fromRoot = false)
    {
        $data = encrypt([
            'shop_id' => $shopId,
            'route' => $route,
            'from_root' => $fromRoot
        ]);

        return '/catalog/jump/' . $data;
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str)
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
    }
}

if (!function_exists('create_range')) {
    function create_range($n, $limits = array(50, 250, 500)): string
    {
        $previousLimit = 0;

        foreach ($limits as $limit) {
            if ($n < $limit) {
                return 'от ' . $previousLimit . ' до ' . $limit;
            }

            $previousLimit = $limit;
          }

          return 'более ' . $previousLimit;
    }
}

if (!function_exists('human_filesize')) {
    function human_filesize($bytes, $decimals = 2): string
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}