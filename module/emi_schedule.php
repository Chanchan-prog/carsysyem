<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$loanId = (int)($_GET['loan_id'] ?? 0);
if (!$loanId) { echo '<div class="alert alert-danger">Invalid loan.</div>'; return; }

// Get loan and car details
$stmt = $conn->prepare("SELECT l.*, c.model, c.plate_no FROM loans l JOIN cars c ON c.id=l.car_id WHERE l.id=?");
$stmt->bind_param("i", $loanId);
$stmt->execute();
$loanDetails = $stmt->get_result()->fetch_assoc();

// Debug: Check if loan details are fetched
if (!$loanDetails) {
    echo '<div class="alert alert-warning">Loan details not found. Please check the loan ID.</div>';
    return;
}

$emis = list_loan_emis($loanId);
?>
<div class="pagetitle"><h1>EMI Schedule</h1></div>

<!-- Debug Information (remove this in production) -->
<?php if (isset($loanDetails) && $loanDetails): ?>
<div class="alert alert-info">
  <strong>Debug Info:</strong> 
  Car Model: <?php echo htmlspecialchars($loanDetails['model'] ?? 'N/A'); ?> | 
  Plate: <?php echo htmlspecialchars($loanDetails['plate_no'] ?? 'N/A'); ?>
</div>
<?php endif; ?>

<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Installments</h5>
      <table class="table table-bordered">
        <thead><tr>
          <th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Amount</th><th>Status</th><th>Action</th>
        </tr></thead>
        <tbody>
        <?php while($row = $emis->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['installment_no']; ?></td>
            <td><?php echo htmlspecialchars($row['due_date']); ?></td>
            <td>₱<?php echo number_format($row['principal_component'],2); ?></td>
            <td>₱<?php echo number_format($row['interest_component'],2); ?></td>
            <td>₱<?php echo number_format($row['amount'],2); ?></td>
            <td>
              <span class="badge bg-<?php echo $row['status']==='paid'?'success':($row['status']==='late'?'danger':'warning'); ?> payment-status-badge" 
                    data-emi-id="<?php echo $row['id']; ?>" 
                    data-current-status="<?php echo $row['status']; ?>"
                    style="cursor: pointer;" 
                    title="Click to toggle payment status">
                <?php echo strtoupper($row['status']); ?>
              </span>
            </td>
            <td>
              <?php if ($row['status']!=='paid'): ?>
                <button class="btn btn-sm btn-primary payment-btn" data-emi-id="<?php echo $row['id']; ?>" data-amount="<?php echo $row['amount']; ?>">
                  <i class="bi bi-credit-card"></i> Pay Now
                </button>
              <?php else: ?>
                <span class="text-success">
                  <i class="bi bi-check-circle"></i> Paid
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">
          <i class="bi bi-credit-card"></i> Payment Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <input type="hidden" id="emiId" name="emi_id">
          
          <!-- Car Loan Information -->
          <div class="row mb-4">
            <div class="col-12">
              <h6 class="text-primary mb-3">
                <i class="bi bi-car-front"></i> Car Loan Information
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label">Car Model</label>
              <input type="text" class="form-control" id="carModel" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Plate Number</label>
              <input type="text" class="form-control" id="plateNumber" readonly>
            </div>
          </div>

          <!-- Due Information -->
          <div class="row mb-4">
            <div class="col-12">
              <h6 class="text-warning mb-3">
                <i class="bi bi-calendar-event"></i> Due Information
              </h6>
            </div>
            <div class="col-md-4">
              <label class="form-label">Installment #</label>
              <input type="text" class="form-control" id="installmentNo" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Due Date</label>
              <input type="text" class="form-control" id="dueDate" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Amount Due</label>
              <input type="text" class="form-control" id="amountDue" readonly>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="row mb-4">
            <div class="col-12">
              <h6 class="text-success mb-3">
                <i class="bi bi-wallet2"></i> Payment Method
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label">Payment Method</label>
              <select class="form-select" id="paymentMethod" name="method" required>
                <option value="">Select Payment Method</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="bank">Bank Transfer</option>
                <option value="check">Check</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Payment Amount</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="paymentAmount" name="amount" step="0.01" min="0" required>
              </div>
            </div>
          </div>

          <!-- Cash Payment Details -->
          <div class="row mb-4" id="cashDetails" style="display: none;">
            <div class="col-12">
              <h6 class="text-info mb-3">
                <i class="bi bi-cash-coin"></i> Cash Payment Details
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cash Received</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="cashReceived" step="0.01" min="0">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Change</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="changeAmount" readonly>
              </div>
            </div>
          </div>

          <!-- GCash Payment Details -->
          <div class="row mb-4" id="gcashDetails" style="display: none;">
            <div class="col-12">
              <h6 class="text-info mb-3">
                <i class="bi bi-phone"></i> GCash Payment Details
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label">GCash Number</label>
              <input type="text" class="form-control" id="gcashNumber" placeholder="09XXXXXXXXX">
            </div>
            <div class="col-md-6">
              <label class="form-label">Reference Number</label>
              <input type="text" class="form-control" id="gcashReference" placeholder="Transaction Reference">
            </div>
          </div>

          <!-- Bank Transfer Details -->
          <div class="row mb-4" id="bankDetails" style="display: none;">
            <div class="col-12">
              <h6 class="text-info mb-3">
                <i class="bi bi-bank"></i> Bank Transfer Details
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label">Bank Name</label>
              <select class="form-select" id="bankName">
                <option value="">Select Bank</option>
                <option value="bpi">BPI</option>
                <option value="bdo">BDO</option>
                <option value="metrobank">Metrobank</option>
                <option value="security_bank">Security Bank</option>
                <option value="eastwest">EastWest Bank</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Reference Number</label>
              <input type="text" class="form-control" id="bankReference" placeholder="Transaction Reference">
            </div>
          </div>

          <!-- Check Payment Details -->
          <div class="row mb-4" id="checkDetails" style="display: none;">
            <div class="col-12">
              <h6 class="text-info mb-3">
                <i class="bi bi-receipt"></i> Check Payment Details
              </h6>
            </div>
            <div class="col-md-4">
              <label class="form-label">Check Number</label>
              <input type="text" class="form-control" id="checkNumber" placeholder="Check Number">
            </div>
            <div class="col-md-4">
              <label class="form-label">Bank Name</label>
              <input type="text" class="form-control" id="checkBank" placeholder="Bank Name">
            </div>
            <div class="col-md-4">
              <label class="form-label">Check Date</label>
              <input type="date" class="form-control" id="checkDate">
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="row">
            <div class="col-12">
              <div class="alert alert-info">
                <h6 class="mb-2">
                  <i class="bi bi-info-circle"></i> Payment Summary
                </h6>
                <div class="row">
                  <div class="col-md-6">
                    <strong>Principal:</strong> $<span id="summaryPrincipal">0.00</span>
                  </div>
                  <div class="col-md-6">
                    <strong>Interest:</strong> $<span id="summaryInterest">0.00</span>
                  </div>
                </div>
                <hr>
                <div class="text-center">
                  <h5 class="text-primary mb-0">
                    Total Amount: $<span id="summaryTotal">0.00</span>
                  </h5>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancel
        </button>
        <button type="button" class="btn btn-success" id="processPayment">
          <i class="bi bi-check-circle"></i> Process Payment
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle payment status badge clicks
    document.querySelectorAll('.payment-status-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const emiId = this.dataset.emiId;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = currentStatus === 'paid' ? 'unpaid' : 'paid';
            
            if (confirm(`Are you sure you want to mark this payment as ${newStatus.toUpperCase()}?`)) {
                togglePaymentStatus(emiId, newStatus);
            }
        });
    });

    // Handle payment button clicks
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const emiId = this.dataset.emiId;
            const amount = this.dataset.amount;
            openPaymentModal(emiId, amount);
        });
    });

    // Handle payment method change
    document.getElementById('paymentMethod').addEventListener('change', function() {
        const method = this.value;
        hideAllPaymentDetails();
        
        switch(method) {
            case 'cash':
                document.getElementById('cashDetails').style.display = 'block';
                break;
            case 'gcash':
                document.getElementById('gcashDetails').style.display = 'block';
                break;
            case 'bank':
                document.getElementById('bankDetails').style.display = 'block';
                break;
            case 'check':
                document.getElementById('checkDetails').style.display = 'block';
                break;
        }
    });

    // Handle cash received input for change calculation
    document.getElementById('cashReceived').addEventListener('input', function() {
        const cashReceived = parseFloat(this.value) || 0;
        const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
        const change = Math.max(0, cashReceived - paymentAmount);
        document.getElementById('changeAmount').value = change.toFixed(2);
    });

    // Handle payment amount input
    document.getElementById('paymentAmount').addEventListener('input', function() {
        const paymentAmount = parseFloat(this.value) || 0;
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        const change = Math.max(0, cashReceived - paymentAmount);
        document.getElementById('changeAmount').value = change.toFixed(2);
    });

    // Handle process payment button
    document.getElementById('processPayment').addEventListener('click', function() {
        processPayment();
    });

    function openPaymentModal(emiId, amount) {
        // Get EMI details from the table row
        const row = document.querySelector(`[data-emi-id="${emiId}"]`).closest('tr');
        const installmentNo = row.cells[0].textContent.trim();
        const dueDate = row.cells[1].textContent.trim();
        const principal = row.cells[2].textContent.replace('₱', '').replace(',', '').trim();
        const interest = row.cells[3].textContent.replace('₱', '').replace(',', '').trim();
        
        // Get car details from PHP variables
        const carModel = '<?php echo htmlspecialchars($loanDetails['model'] ?? 'N/A'); ?>';
        const plateNumber = '<?php echo htmlspecialchars($loanDetails['plate_no'] ?? 'N/A'); ?>';
        
        // Debug: Log the values
        console.log('EMI ID:', emiId);
        console.log('Amount:', amount);
        console.log('Car Model:', carModel);
        console.log('Plate Number:', plateNumber);
        console.log('Installment No:', installmentNo);
        console.log('Due Date:', dueDate);
        console.log('Principal:', principal);
        console.log('Interest:', interest);
        
        // Populate modal fields
        document.getElementById('emiId').value = emiId;
        document.getElementById('carModel').value = carModel;
        document.getElementById('plateNumber').value = plateNumber;
        document.getElementById('installmentNo').value = installmentNo;
        document.getElementById('dueDate').value = dueDate;
        document.getElementById('amountDue').value = '₱' + parseFloat(amount).toFixed(2);
        document.getElementById('paymentAmount').value = amount;
        document.getElementById('summaryPrincipal').textContent = parseFloat(principal).toFixed(2);
        document.getElementById('summaryInterest').textContent = parseFloat(interest).toFixed(2);
        document.getElementById('summaryTotal').textContent = parseFloat(amount).toFixed(2);
        
        // Reset payment method selection
        document.getElementById('paymentMethod').value = '';
        hideAllPaymentDetails();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    function hideAllPaymentDetails() {
        document.getElementById('cashDetails').style.display = 'none';
        document.getElementById('gcashDetails').style.display = 'none';
        document.getElementById('bankDetails').style.display = 'none';
        document.getElementById('checkDetails').style.display = 'none';
    }

    function processPayment() {
        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);
        
        // Validate required fields
        const paymentMethod = document.getElementById('paymentMethod').value;
        const paymentAmount = document.getElementById('paymentAmount').value;
        
        if (!paymentMethod) {
            alert('Please select a payment method');
            return;
        }
        
        if (!paymentAmount || parseFloat(paymentAmount) <= 0) {
            alert('Please enter a valid payment amount');
            return;
        }
        
        // Validate method-specific fields
        if (paymentMethod === 'cash') {
            const cashReceived = document.getElementById('cashReceived').value;
            if (!cashReceived || parseFloat(cashReceived) < parseFloat(paymentAmount)) {
                alert('Cash received must be greater than or equal to payment amount');
                return;
            }
        }
        
        if (paymentMethod === 'gcash') {
            const gcashNumber = document.getElementById('gcashNumber').value;
            if (!gcashNumber) {
                alert('Please enter GCash number');
                return;
            }
        }
        
        if (paymentMethod === 'bank') {
            const bankName = document.getElementById('bankName').value;
            if (!bankName) {
                alert('Please select a bank');
                return;
            }
        }
        
        if (paymentMethod === 'check') {
            const checkNumber = document.getElementById('checkNumber').value;
            const checkBank = document.getElementById('checkBank').value;
            if (!checkNumber || !checkBank) {
                alert('Please enter check number and bank name');
                return;
            }
        }
        
        // Add additional payment details to form data
        if (paymentMethod === 'cash') {
            formData.append('cash_received', document.getElementById('cashReceived').value);
            formData.append('change_amount', document.getElementById('changeAmount').value);
        }
        
        if (paymentMethod === 'gcash') {
            formData.append('gcash_number', document.getElementById('gcashNumber').value);
            formData.append('gcash_reference', document.getElementById('gcashReference').value);
        }
        
        if (paymentMethod === 'bank') {
            formData.append('bank_name', document.getElementById('bankName').value);
            formData.append('bank_reference', document.getElementById('bankReference').value);
        }
        
        if (paymentMethod === 'check') {
            formData.append('check_number', document.getElementById('checkNumber').value);
            formData.append('check_bank', document.getElementById('checkBank').value);
            formData.append('check_date', document.getElementById('checkDate').value);
        }
        
        formData.append('ajax', '1');
        formData.append('status', 'paid');
        
        // Process payment
        fetch('module/emi_toggle_paid.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment processed successfully!');
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                modal.hide();
                // Reload page to show updated status
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to process payment'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing payment. Please try again.');
        });
    }

    function togglePaymentStatus(emiId, status, method = 'manual') {
        const formData = new FormData();
        formData.append('emi_id', emiId);
        formData.append('status', status);
        formData.append('method', method);
        formData.append('ajax', '1');

        fetch('module/emi_toggle_paid.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show updated status
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update payment status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating payment status. Please try again.');
        });
    }
});
</script>

<style>
.payment-status-badge {
    transition: all 0.3s ease;
    user-select: none;
}

.payment-status-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.payment-btn {
    transition: all 0.3s ease;
}

.payment-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.modal-content {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-title {
    font-weight: 600;
}

.form-label {
    font-weight: 500;
    color: #495057;
}

.alert-info {
    border-left: 4px solid #17a2b8;
    background-color: #f8f9fa;
}

#summaryTotal {
    font-size: 1.5rem;
    font-weight: bold;
}

.payment-details-section {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

.payment-details-section h6 {
    margin-bottom: 15px;
    font-weight: 600;
}

.input-group-text {
    background-color: #e9ecef;
    border-color: #ced4da;
    font-weight: 500;
}
</style>


