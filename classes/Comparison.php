<?php
namespace App;

class Comparison
{
    public static function compare(array $rowsA, array $rowsB, array $options): array
    {
        $compareKeys = $options['compare'] ?? ['name','ic'];
        $mode = $options['mode'] ?? 'smart';

        $mapB = [];
        foreach ($rowsB as $i => $r) {
            $key = self::buildKey($r, $compareKeys, $mode);
            $mapB[$key][] = ['index' => $i, 'row' => $r];
        }

        $results = [];
        $matchedCount = 0;
        foreach ($rowsA as $i => $a) {
            $key = self::buildKey($a, $compareKeys, $mode);
            if (isset($mapB[$key]) && count($mapB[$key]) > 0) {
                $b = array_shift($mapB[$key]);
                $status = 'match';
                $diff = self::diffRow($a, $b['row']);
                if (!empty($diff)) $status = 'modified';
                $results[] = compact('a','b','status','diff');
                $matchedCount++;
            } else {
                $results[] = ['a'=>$a,'b'=>null,'status'=>'missing','diff'=>[]];
            }
        }

        // Remaining in mapB are extra in B
        $extra = [];
        foreach ($mapB as $k => $items) {
            foreach ($items as $it) {
                $extra[] = ['a'=>null,'b'=>$it,'status'=>'extra','diff'=>[]];
            }
        }

        return [
            'rows' => $results,
            'extra' => $extra,
            'matched' => $matchedCount,
        ];
    }

    private static function buildKey(array $row, array $keys, string $mode): string
    {
        $parts = [];
        foreach ($keys as $k) {
            $v = $row[$k] ?? '';
            if ($k === 'name') {
                $v = $mode === 'smart' ? Normalizer::normalizeName($v) : trim(mb_strtolower((string)$v));
            }
            if ($k === 'ic') {
                $v = Normalizer::normalizeIC($v);
            }
            if (in_array($k, ['email','student_id','phone'])) {
                $v = trim(mb_strtolower((string)$v));
            }
            $parts[] = $v;
        }
        return implode('||', $parts);
    }

    private static function diffRow(array $a, array $b): array
    {
        $diff = [];
        $fields = array_unique(array_merge(array_keys($a), array_keys($b)));
        foreach ($fields as $f) {
            $va = $a[$f] ?? null;
            $vb = $b[$f] ?? null;
            if ((string)$va !== (string)$vb) $diff[$f] = ['a'=>$va,'b'=>$vb];
        }
        return $diff;
    }
}
