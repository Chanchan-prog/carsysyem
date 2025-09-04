<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../db/db.php';

$hasPlate = $conn->query("SHOW COLUMNS FROM cars LIKE 'plate_no'");
if (!$hasPlate || $hasPlate->num_rows === 0) {
  $conn->query("ALTER TABLE cars ADD COLUMN plate_no VARCHAR(20) UNIQUE NULL AFTER model");
}

$hasArchived = $conn->query("SHOW COLUMNS FROM cars LIKE 'archived'");
if (!$hasArchived || $hasArchived->num_rows === 0) {
  $conn->query("ALTER TABLE cars ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
}

$genPlate = function(string $model) use ($conn): string {
  $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', substr($model, 0, 3)));
  if ($prefix === '') { $prefix = 'CAR'; }
  do {
    $candidate = $prefix . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $chk = $conn->prepare('SELECT 1 FROM cars WHERE plate_no=?');
    $chk->bind_param('s', $candidate);
    $chk->execute();
    $exists = (bool)$chk->get_result()->fetch_assoc();
  } while ($exists);
  return $candidate;
};
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $model = trim($_POST['model'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($model !== '' && $price > 0) {
            $plate = $genPlate($model);
            $stmt = $conn->prepare('INSERT INTO cars (model, plate_no, price, is_active) VALUES (?,?,?,?)');
            $stmt->bind_param('ssdi', $model, $plate, $price, $isActive);
            $stmt->execute();
            $message = 'Car added.';
        } else {
            $message = 'Please provide a valid model and price.';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $model = trim($_POST['model'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($id && $model !== '') {
            // Price is fixed and cannot be edited via UI; do not update price here.
            $stmt = $conn->prepare('UPDATE cars SET model=?, is_active=? WHERE id=?');
            $stmt->bind_param('sii', $model, $isActive, $id);
            $stmt->execute();
            $message = 'Car updated.';
        } else {
            $message = 'Invalid update data.';
        }
    } elseif ($action === 'archive') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('UPDATE cars SET archived=1, is_active=0 WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = 'Car archived.';
        }
    } elseif ($action === 'restore') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('UPDATE cars SET archived=0 WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = 'Car restored.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('DELETE FROM cars WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = 'Car deleted.';
        }
    }
}

$filter = $_GET['archived'] ?? '0'; // 0=active, 1=archived, all
$where = '';
if ($q !== '') {
  $safe = '%' . $conn->real_escape_string($q) . '%';
  $where = "WHERE model LIKE '$safe'";
}
if ($filter === '0') {
  $where .= ($where? ' AND ':' WHERE ') . "archived=0";
} elseif ($filter === '1') {
  $where .= ($where? ' AND ':' WHERE ') . "archived=1";
}
$countRes = $conn->query("SELECT COUNT(*) AS c FROM cars $where");
$total = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;
$cars = $conn->query("SELECT * FROM cars $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$modelOptions = $conn->query("SELECT DISTINCT model FROM cars ORDER BY model");
$modelPricesRes = $conn->query("SELECT model, MIN(price) AS price FROM cars GROUP BY model");
$modelPrices = [];
if ($modelPricesRes) { while ($r = $modelPricesRes->fetch_assoc()) { $modelPrices[$r['model']] = (float)$r['price']; } }
?>
<div class="pagetitle"><h1>Cars</h1></div>
<section class="section">
  <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <form class="row g-2 mb-2" method="get">
    <input type="hidden" name="page" value="cars">
    <div class="col-md-4">
      <input type="text" class="form-control" name="q" placeholder="Search model" value="<?php echo htmlspecialchars($q); ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </div>
    <div class="col-md-3">
      <label class="form-label">View</label>
      <select name="archived" class="form-select" onchange="this.form.submit()">
        <option value="0" <?php echo $filter==='0'?'selected':''; ?>>Active</option>
        <option value="1" <?php echo $filter==='1'?'selected':''; ?>>Archived</option>
        <option value="all" <?php echo $filter==='all'?'selected':''; ?>>All</option>
      </select>
    </div>
  </form>
  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title">Add Car</h5>
      <form method="post" class="row g-2">
        <input type="hidden" name="action" value="create">
        <div class="col-md-6">
          <label class="form-label">Model</label>
          <input type="text" name="model" class="form-control" list="modelOptions" required>
          <datalist id="modelOptions">
            <?php if ($modelOptions): while ($opt = $modelOptions->fetch_assoc()): ?>
              <option value="<?php echo htmlspecialchars($opt['model']); ?>"></option>
            <?php endwhile; endif; ?>
          </datalist>
        </div>
        <div class="col-md-3">
          <label class="form-label">Price</label>
          <input type="number" step="0.01" name="price" class="form-control" required readonly>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="carActiveCreate" checked>
            <label class="form-check-label" for="carActiveCreate">Active</label>
          </div>
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-primary" type="submit">Add</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Archived Cars</h5>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Model</th>
              <th>Plate No.</th>
              <th>Price</th>
              <th>Sold</th>
              <th>Archived</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($cars): while ($c = $cars->fetch_assoc()): ?>
              <?php 
                $soldRes = $conn->query('SELECT 1 FROM loans WHERE car_id='.(int)$c['id'].' AND status=\'approved\' LIMIT 1');
                $isSold = $soldRes && $soldRes->fetch_assoc();
              ?>
              <tr>
                <form method="post" class="row g-2" style="--bs-gutter-x:0.5rem">
                  <td class="col-auto align-middle"><?php echo (int)$c['id']; ?></td>
                  <td class="col"><input type="text" name="model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($c['model']); ?>" required></td>
                  <td class="col-2 align-middle"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['plate_no'] ?? ''); ?></span></td>
                  <td class="col-2"><input type="number" step="0.01" class="form-control form-control-sm" value="<?php echo htmlspecialchars($c['price']); ?>" readonly></td>
                  <td class="col-1 align-middle">
                    <?php if ($isSold): ?>
                      <span class="badge bg-secondary">Sold</span>
                    <?php else: ?>
                      <span class="badge bg-success">Available</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-1 align-middle">
                    <?php if ((int)$c['archived']===1): ?>
                      <span class="badge bg-dark">Archived</span>
                    <?php else: ?>
                      <span class="badge bg-info text-dark">Active</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-1 text-center">
                    <input type="checkbox" name="is_active" <?php echo ((int)$c['is_active']) ? 'checked' : ''; ?> />
                  </td>
                  <td class="col-3">
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <button name="action" value="update" class="btn btn-sm btn-success">Save</button>
                    <?php if ((int)$c['archived']===1): ?>
                      <button name="action" value="restore" class="btn btn-sm btn-warning">Restore</button>
                    <?php else: ?>
                      <button name="action" value="archive" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Archive this car?')">Archive</button>
                    <?php endif; ?>
                    <button name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this car?')">Delete</button>
                  </td>
                </form>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="8" class="text-center">No cars found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php 
    $totalPages = (int)ceil(max(1, $total) / $perPage);
    $base = 'index.php?page=cars&q=' . urlencode($q) . '&p=';
  ?>
  <nav>
    <ul class="pagination">
      <li class="page-item <?php echo $page<=1?'disabled':''; ?>"><a class="page-link" href="<?php echo $base . max(1, $page-1); ?>">Prev</a></li>
      <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span></li>
      <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>"><a class="page-link" href="<?php echo $base . min($totalPages, $page+1); ?>">Next</a></li>
    </ul>
  </nav>
  <script>
    (function(){
      const modelPrices = <?php echo json_encode($modelPrices, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
      const modelInput = document.querySelector('input[name="model"]');
      const priceInput = document.querySelector('input[name="price"]');
      function syncPrice(){
        const v = (modelInput.value || '').trim();
        if (Object.prototype.hasOwnProperty.call(modelPrices, v)) {
          priceInput.value = modelPrices[v];
        }
      }
      if (modelInput && priceInput){
        modelInput.addEventListener('input', syncPrice);
        modelInput.addEventListener('change', syncPrice);
      }
    })();
  </script>
  <?php $archivedCars = $conn->query("SELECT * FROM cars WHERE archived=1 ORDER BY id DESC LIMIT 100"); ?>
  <!-- <div class="card mt-4">
    <div class="card-body">
      <h5 class="card-title">Archived Cars</h5>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Model</th>
              <th>Plate No.</th>
              <th>Price</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($archivedCars && $archivedCars->num_rows>0): while($ac = $archivedCars->fetch_assoc()): ?>
              <tr>
                <form method="post" class="row g-2" style="--bs-gutter-x:0.5rem">
                  <td class="col-auto align-middle"><?php echo (int)$ac['id']; ?></td>
                  <td class="col align-middle"><?php echo htmlspecialchars($ac['model']); ?></td>
                  <td class="col-2 align-middle"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ac['plate_no'] ?? ''); ?></span></td>
                  <td class="col-2 align-middle"><input type="number" step="0.01" class="form-control form-control-sm" value="<?php echo htmlspecialchars($ac['price']); ?>" readonly></td>
                  <td class="col-2 align-middle"><span class="badge bg-dark">Archived</span></td>
                  <td class="col-3">
                    <input type="hidden" name="id" value="<?php echo (int)$ac['id']; ?>">
                    <button name="action" value="restore" class="btn btn-sm btn-warning">Restore</button>
                    <button name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this car?')">Delete</button>
                  </td>
                </form>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="6" class="text-center">No archived cars.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div> -->
</section>


