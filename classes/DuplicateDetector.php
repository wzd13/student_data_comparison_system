<?php
namespace App;

class DuplicateDetector
{
    public static function findDuplicates(array $rows, array $keys): array
    {
        $map = [];
        foreach ($rows as $i => $r) {
            $parts = [];
            foreach ($keys as $k) {
                $parts[] = $r[$k] ?? '';
            }
            $key = implode('||', $parts);
            $map[$key][] = $i;
        }
        $dups = [];
        foreach ($map as $k => $idxs) {
            if (count($idxs) > 1) $dups[$k] = $idxs;
        }
        return $dups;
    }
}
