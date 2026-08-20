<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use App\ExcelReader;
use App\Comparison;
use App\DuplicateDetector;
use App\Normalizer;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    exit('Invalid CSRF');
}

function moveUpload($file)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $target = __DIR__ . '/uploads/' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);
    move_uploaded_file($file['tmp_name'], $target);
    return $target;
}

$aPath = moveUpload($_FILES['file_a'] ?? null);
$bPath = moveUpload($_FILES['file_b'] ?? null);
if (!$aPath || !$bPath) exit('Upload failed');

$mode = $_POST['mode'] ?? 'smart';
$compare = $_POST['compare'] ?? ['name','ic'];

$previewA = ExcelReader::readPreview($aPath, 1000);
$previewB = ExcelReader::readPreview($bPath, 1000);

// Auto-detect headers and map by simple heuristics
function autoMap(array $row)
{
    $map = [];
    foreach ($row as $i => $v) {
        $key = strtolower(trim((string)$v));
        if (preg_match('/name|student|fullname/', $key)) $map['name'] = $i;
        if (preg_match('/ic|identity|icno|idno/', $key)) $map['ic'] = $i;
        if (preg_match('/student.?id|id$/', $key)) $map['student_id'] = $i;
        if (preg_match('/email/', $key)) $map['email'] = $i;
        if (preg_match('/phone|tel|mobile/', $key)) $map['phone'] = $i;
    }
    return $map;
}

$headersA = ExcelReader::detectHeaders($aPath, 1);
$headersB = ExcelReader::detectHeaders($bPath, 1);
$mapA = autoMap($headersA);
$mapB = autoMap($headersB);

// Convert preview rows to associative arrays based on detected headers
function toAssoc(array $rows, array $headers)
{
    $out = [];
    foreach ($rows as $r) {
        $assoc = [];
        foreach ($headers as $i => $h) {
            $key = strtolower(trim((string)$h));
            $normKey = 'col_' . $i;
            $assoc[$key] = $r[$i] ?? null;
        }
        $out[] = $assoc;
    }
    return $out;
}

$rowsA = toAssoc($previewA, $headersA);
$rowsB = toAssoc($previewB, $headersB);

// Normalize keys to standard names for comparison
function normalizeRows(array $rows, array $map)
{
    $out = [];
    foreach ($rows as $r) {
        $nr = [];
        $nr['name'] = isset($map['name']) ? ($r[$map['name']] ?? '') : '';
        $nr['ic'] = isset($map['ic']) ? ($r[$map['ic']] ?? '') : '';
        $nr['student_id'] = isset($map['student_id']) ? ($r[$map['student_id']] ?? '') : '';
        $nr['email'] = isset($map['email']) ? ($r[$map['email']] ?? '') : '';
        $nr['phone'] = isset($map['phone']) ? ($r[$map['phone']] ?? '') : '';
        $out[] = $nr;
    }
    return $out;
}

$nA = normalizeRows($rowsA, $mapA);
$nB = normalizeRows($rowsB, $mapB);

$result = Comparison::compare($nA, $nB, ['compare'=>$compare,'mode'=>$mode]);

// Duplicate detection
$dupsA = DuplicateDetector::findDuplicates($nA, $compare);
$dupsB = DuplicateDetector::findDuplicates($nB, $compare);

// Simple dashboard stats
$totalA = count($nA);
$totalB = count($nB);
$matched = $result['matched'];
$notMatched = $totalA - $matched;

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comparison Results</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <style>td.wrap{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}</style>
</head>
<body>
<div class="container py-4">
  <h2>Comparison Results</h2>
  <div class="row my-3">
    <div class="col-md-2"><div class="card p-2">Total A<br><strong><?php echo $totalA; ?></strong></div></div>
    <div class="col-md-2"><div class="card p-2">Total B<br><strong><?php echo $totalB; ?></strong></div></div>
    <div class="col-md-2"><div class="card p-2">Matched<br><strong><?php echo $matched; ?></strong></div></div>
    <div class="col-md-2"><div class="card p-2">Not Matched<br><strong><?php echo $notMatched; ?></strong></div></div>
    <div class="col-md-2"><div class="card p-2">Duplicates A<br><strong><?php echo count($dupsA); ?></strong></div></div>
    <div class="col-md-2"><div class="card p-2">Duplicates B<br><strong><?php echo count($dupsB); ?></strong></div></div>
  </div>

  <table id="resultTable" class="display table table-striped" style="width:100%">
    <thead>
      <tr>
        <th>No</th>
        <th>Name A</th>
        <th>Name B</th>
        <th>IC A</th>
        <th>IC B</th>
        <th>Student ID A</th>
        <th>Student ID B</th>
        <th>Status</th>
        <th>Difference</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($result['rows'] as $i => $r):
        $no = $i+1;
        $a = $r['a'];
        $b = $r['b'] ? $r['b']['row'] : null;
        $status = $r['status'];
        $diff = $r['diff'];
        $rowClass = $status === 'match' ? 'table-success' : ($status==='modified' ? 'table-warning' : 'table-danger');
    ?>
      <tr class="<?php echo $rowClass; ?>">
        <td><?php echo $no; ?></td>
        <td class="wrap"><?php echo htmlspecialchars($a['name'] ?? ''); ?></td>
        <td class="wrap"><?php echo htmlspecialchars($b['name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($a['ic'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($b['ic'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($a['student_id'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($b['student_id'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($status); ?></td>
        <td><?php echo htmlspecialchars(json_encode($diff)); ?></td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($result['extra'] as $r):
        $no++;
        $b = $r['b']['row'];
    ?>
      <tr class="table-primary">
        <td><?php echo $no; ?></td>
        <td></td>
        <td><?php echo htmlspecialchars($b['name'] ?? ''); ?></td>
        <td></td>
        <td><?php echo htmlspecialchars($b['ic'] ?? ''); ?></td>
        <td></td>
        <td><?php echo htmlspecialchars($b['student_id'] ?? ''); ?></td>
        <td>extra</td>
        <td></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>$(document).ready(function(){$('#resultTable').DataTable();});</script>
</body>
</html>
