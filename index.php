<?php
session_start();
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  http_response_code(503);
  echo "<h3>Dependencies missing</h3><p>Run <code>composer install</code> in the project root (<strong>" . htmlspecialchars(__DIR__) . "</strong>) to install required dependencies.</p>";
  echo "<p>Example (Windows):<br><code>cd " . htmlspecialchars(__DIR__) . "\ncomposer install</code></p>";
  exit;
}
require_once $autoload;
use App\Normalizer;

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Data Comparison</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container py-4">
  <h1 class="mb-4">Student Data Comparison</h1>
  <form action="compare.php" method="post" enctype="multipart/form-data" class="card p-3">
    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Upload File A</label>
        <input class="form-control" type="file" name="file_a" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Upload File B</label>
        <input class="form-control" type="file" name="file_b" required>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">Comparison Mode</label>
        <select name="mode" class="form-select">
          <option value="exact">Exact Match</option>
          <option value="smart">Smart Match</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Compare By (choose one or more)</label>
        <div class="d-flex gap-2 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="compare[]" value="name" checked>
            <span class="form-check-label">Student Name</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="compare[]" value="ic" checked>
            <span class="form-check-label">IC Number</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="compare[]" value="student_id">
            <span class="form-check-label">Student ID</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="compare[]" value="email">
            <span class="form-check-label">Email</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="compare[]" value="phone">
            <span class="form-check-label">Phone</span>
          </label>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end">
      <button class="btn btn-primary" type="submit">Compare</button>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
