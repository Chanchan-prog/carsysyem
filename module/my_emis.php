<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$u = current_user();
if (!$u['id']) { require_login(); }

// Fetch all EMIs for the current user's approved loans
$conn->query("UPDATE emis SET status='late' WHERE status='due' AND due_date < CURDATE()");
$stmt = $conn->prepare("SELECT e.id, e.installment_no, e.due_date, e.principal_component, e.interest_component, e.amount, e.status, l.id AS loan_id, l.car_id, c.model, c.plate_no
                        FROM emis e
                        JOIN loans l ON l.id=e.loan_id
                        JOIN cars c ON c.id=l.car_id
                        WHERE l.user_id=? AND l.status='approved'
                        ORDER BY e.status='paid', e.due_date ASC, e.installment_no ASC");
$stmt->bind_param('i', $u['id']);
$stmt->execute();
$emis = $stmt->get_result();
?>
<div class="pagetitle"><h1>My Payments</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">EMI Schedule</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Loan #</th>
              <th>Car</th>
              <th>Installment</th>
              <th>Due Date</th>
              <th>Principal</th>
              <th>Interest</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($emis && $emis->num_rows>0): while($row=$emis->fetch_assoc()): ?>
              <tr>
                <td><?php echo (int)$row['loan_id']; ?></td>
                <td><?php echo htmlspecialchars(($row['model'] ?? '') . (isset($row['plate_no']) && $row['plate_no']? ' ('.$row['plate_no'].')':'')); ?></td>
                <td><?php echo (int)$row['installment_no']; ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td>₱<?php echo number_format($row['principal_component'],2); ?></td>
                <td>₱<?php echo number_format($row['interest_component'],2); ?></td>
                <td>₱<?php echo number_format($row['amount'],2); ?></td>
                <td>
                  <span class="badge bg-<?php echo $row['status']==='paid'?'success':($row['status']==='late'?'danger':'warning'); ?> payment-status-badge" 
                        data-emi-id="<?php echo (int)$row['id']; ?>" 
                        data-current-status="<?php echo $row['status']; ?>"
                        style="cursor: pointer;" 
                        title="Click to toggle payment status">
                    <?php echo strtoupper($row['status']); ?>
                  </span>
                </td>
                <td>
                  <?php if ($row['status']==='paid'): ?>
                    <button class="btn btn-sm btn-info view-payment-btn" data-emi-id="<?php echo (int)$row['id']; ?>" data-amount="<?php echo $row['amount']; ?>" title="View Payment Record">
                      <i class="bi bi-eye"></i> View Payment
                    </button>
                  <?php else: ?>
                    <span class="text-muted">
                      <i class="bi bi-clock"></i> Not Paid
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="9" class="text-center">No EMIs found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Payment Record Modal -->
<div class="modal fade" id="paymentRecordModal" tabindex="-1" aria-labelledby="paymentRecordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentRecordModalLabel">
          <i class="bi bi-receipt"></i> Payment Record Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="paymentRecordContent">
          <!-- Payment record details will be loaded here -->
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading payment record...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Close
        </button>
        <button type="button" class="btn btn-success" id="downloadReceipt">
          <i class="bi bi-download"></i> Download Receipt
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

    // Handle view payment record button clicks
    document.querySelectorAll('.view-payment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const emiId = this.dataset.emiId;
            const amount = this.dataset.amount;
            viewPaymentRecord(emiId, amount);
        });
    });

    // Handle download receipt button
    document.getElementById('downloadReceipt').addEventListener('click', function() {
        const emiId = document.getElementById('paymentRecordModal').dataset.currentEmiId;
        if (emiId) {
            downloadReceipt(emiId);
        }
    });

    function viewPaymentRecord(emiId, amount) {
        // Show modal and loading state
        const modal = new bootstrap.Modal(document.getElementById('paymentRecordModal'));
        document.getElementById('paymentRecordModal').dataset.currentEmiId = emiId;
        modal.show();

        // Fetch payment record details
        fetch(`module/get_payment_record.php?emi_id=${emiId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPaymentRecord(data.record, data.emi, data.loan, data.car);
                } else {
                    document.getElementById('paymentRecordContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Error loading payment record: ${data.message || 'Unknown error'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('paymentRecordContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error loading payment record. Please try again.
                    </div>
                `;
            });
    }

    function displayPaymentRecord(payment, emi, loan, car) {
        const content = `
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success">
                        <h6 class="mb-2"><i class="bi bi-check-circle"></i> Payment Confirmation</h6>
                        <p class="mb-0">Payment received successfully!</p>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="bi bi-car-front"></i> Car Information
                    </h6>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Car Model</label>
                    <input type="text" class="form-control" value="${car.model}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Plate Number</label>
                    <input type="text" class="form-control" value="${car.plate_no}" readonly>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-warning mb-3">
                        <i class="bi bi-calendar-event"></i> Installment Details
                    </h6>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Installment #</label>
                    <input type="text" class="form-control" value="${emi.installment_no}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Due Date</label>
                    <input type="text" class="form-control" value="${emi.due_date}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Principal</label>
                    <input type="text" class="form-control" value="₱${parseFloat(emi.principal_component).toFixed(2)}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Interest</label>
                    <input type="text" class="form-control" value="₱${parseFloat(emi.interest_component).toFixed(2)}" readonly>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-success mb-3">
                        <i class="bi bi-credit-card"></i> Payment Information
                    </h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method</label>
                    <input type="text" class="form-control" value="${payment.method.toUpperCase()}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount Paid</label>
                    <input type="text" class="form-control" value="₱${parseFloat(payment.amount).toFixed(2)}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Date</label>
                    <input type="text" class="form-control" value="${payment.created_at}" readonly>
                </div>
            </div>

            ${payment.method === 'cash' ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-info mb-3">
                        <i class="bi bi-cash-coin"></i> Cash Payment
                    </h6>
                    <p class="text-muted">Payment received in cash</p>
                </div>
            </div>
            ` : ''}

            ${payment.method === 'gcash' ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-info mb-3">
                        <i class="bi bi-phone"></i> GCash Payment
                    </h6>
                    <p class="text-muted">Payment made via GCash</p>
                </div>
            </div>
            ` : ''}

            ${payment.method === 'bank' ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-info mb-3">
                        <i class="bi bi-bank"></i> Bank Transfer
                    </h6>
                    <p class="text-muted">Payment made via bank transfer</p>
                </div>
            </div>
            ` : ''}

            ${payment.method === 'check' ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-info mb-3">
                        <i class="bi bi-receipt"></i> Check Payment
                    </h6>
                    <p class="text-muted">Payment made by check</p>
                </div>
            </div>
            ` : ''}

            ${payment.method === 'manual' ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-info mb-3">
                        <i class="bi bi-person-check"></i> Manual Payment
                    </h6>
                    <p class="text-muted">Payment recorded manually</p>
                </div>
            </div>
            ` : ''}

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6 class="mb-2">
                            <i class="bi bi-info-circle"></i> Payment Summary
                        </h6>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <strong>Maintenance Fee:</strong><br>
                                ₱${parseFloat(emi.interest_component).toFixed(2)}
                            </div>
                            <div class="col-md-4">
                                <strong>Monthly Payment:</strong><br>
                                ₱${parseFloat(emi.principal_component).toFixed(2)}
                            </div>
                            <div class="col-md-4">
                                <strong>Total Amount:</strong><br>
                                <span class="fs-5 text-primary fw-bold">₱${parseFloat(payment.amount).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('paymentRecordContent').innerHTML = content;
    }

    function downloadReceipt(emiId) {
        // Create a new window to download receipt
        window.open(`module/download_receipt.php?emi_id=${emiId}`, '_blank');
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

.view-payment-btn {
    transition: all 0.3s ease;
}

.view-payment-btn:hover {
    background-color: #17a2b8;
    color: white;
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

.alert-success, .alert-info {
    border-left: 4px solid #28a745;
    background-color: #f8f9fa;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}
</style>


