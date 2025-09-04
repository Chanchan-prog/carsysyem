<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$user = current_user();
$isBlocked = (int)($_SESSION['blocked'] ?? 0) === 1;
$message = '';
if ($isBlocked) {
    echo '<div class="alert alert-danger">Your account is blocked. You cannot apply for a loan.</div>';
    return;
}
$cars = get_active_cars();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carId = (int)($_POST['car_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $down = (float)($_POST['down_payment'] ?? 0);
    $rate = (float)($_POST['annual_rate'] ?? 0);
    $months = (int)($_POST['term_months'] ?? 0);
    if ($carId && $price > 0 && $months > 0 && $rate >= 0 && $down >= 0 && $down <= $price) {
        $loanId = create_loan($user['id'], $carId, $price, $down, $rate, $months);
        if ($loanId) {
            $message = 'Application submitted successfully.';
        } else {
            $message = 'This car is no longer available (already sold).';
        }
    } else {
        $message = 'Please fill all fields correctly.';
    }
}
?>
<div class="pagetitle"><h1>Apply for Car Loan</h1></div>
<section class="section">
  <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Loan Details</h5>
      <form method="post" class="row g-3" id="loanForm">
        <div class="col-md-6">
          <label class="form-label">Car</label>
          <select name="car_id" id="car_id" class="form-select" required>
            <option value="">Select car</option>
            <?php foreach ($cars as $c): ?>
              <option value="<?php echo $c['id']; ?>" data-price="<?php echo $c['price']; ?>"><?php echo htmlspecialchars($c['model']); ?> - $<?php echo number_format($c['price'],2); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Car Price</label>
          <input type="number" step="0.01" class="form-control" name="price" id="price" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Down Payment</label>
          <input type="number" step="0.01" class="form-control" name="down_payment" id="down_payment" value="0" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Annual Interest Rate (%)</label>
          <input type="number" step="0.01" class="form-control" name="annual_rate" id="annual_rate" value="10" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Term</label>
          <select class="form-select" name="term_months" id="term_months" required>
            <option value="12">1 year (12 months)</option>
            <option value="24">2 years (24 months)</option>
            <option value="36" selected>3 years (36 months)</option>
          </select>
        </div>
        <div class="col-12">
          <div class="alert alert-secondary" id="emiPreview">
            <div><strong>Total Interest:</strong> ₱0</div>
            <div><strong>Total Loan with Interest:</strong> ₱0</div>
            <div><strong>Monthly Installment:</strong> ₱0</div>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Submit Application</button>
        </div>
      </form>
    </div>
  </div>
</section>
<script>
(function(){
  const priceEl = document.getElementById('price');
  const downEl = document.getElementById('down_payment');
  const rateEl = document.getElementById('annual_rate');
  const monthsEl = document.getElementById('term_months');
  const emiEl = document.getElementById('emiPreview');
  const carEl = document.getElementById('car_id');
  function formatPHP(n){
    try { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(n); } catch(e){ return '₱' + Math.round(n).toLocaleString('en-PH'); }
  }
  function syncRateWithTerm(){
    const months = parseInt(monthsEl.value)||0;
    let rate = 10;
    if (months >= 36) rate = 30; else if (months >= 24) rate = 20; else rate = 10;
    rateEl.value = rate;
  }
  function calc(){
    syncRateWithTerm();
    const price = parseFloat(priceEl.value)||0;
    const down = parseFloat(downEl.value)||0;
    const rate = parseFloat(rateEl.value)||0; // annual %
    const months = parseInt(monthsEl.value)||0;
    const principal = Math.max(0, price - down);
    const years = months/12.0;
    const totalInterest = principal * (rate/100.0) * years;
    const totalLoanWithInterest = principal + totalInterest;
    const monthlyInstallment = months>0 ? (totalLoanWithInterest / months) : 0;
    emiEl.innerHTML = '<div><strong>Total Interest:</strong> ' + formatPHP(totalInterest) + '</div>'+
                      '<div><strong>Total Loan with Interest:</strong> ' + formatPHP(totalLoanWithInterest) + '</div>'+
                      '<div><strong>Monthly Installment:</strong> ' + formatPHP(monthlyInstallment) + '</div>';
  }
  carEl.addEventListener('change', function(){
    const price = this.options[this.selectedIndex]?.getAttribute('data-price');
    if (price){ priceEl.value = price; }
    calc();
  });
  [priceEl, downEl, monthsEl].forEach(el=> el.addEventListener('input', calc));
  monthsEl.addEventListener('change', calc);
  calc();
})();
</script>


