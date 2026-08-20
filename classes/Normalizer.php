<?php
namespace App;

class Normalizer
{
    public static function normalizeName(?string $name): string
    {
        if ($name === null) return '';
        $s = mb_strtolower($name, 'UTF-8');
        $s = preg_replace('/[\s\-\/\._]+/', ' ', $s);
        $s = preg_replace('/[^\p{L}\p{N} ]+/u', '', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return $s;
    }

    public static function normalizeIC(?string $ic): string
    {
        if ($ic === null) return '';
        $s = preg_replace('/[^0-9]/', '', $ic);
        return ltrim($s, '0') === '' ? $s : $s;
    }
}
