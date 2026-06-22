<?php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include_once("../../config/app.php");

// Security: only staff can access
if (!isset($_SESSION['user']) || $_SESSION['role'] !== "staff") {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Handle session messages (set by payments.php or other handlers)
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// --- Filters and Pagination setup ---
$search = trim($_GET['q'] ?? '');
$from_date = trim($_GET['from_date'] ?? '');
$to_date = trim($_GET['to_date'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE clauses dynamically and parameters for prepared statement
$where = [];
$types = '';
$params = [];

// Search across invoice_no, student name, reg_number
if ($search !== '') {
    $where[] = "(i.invoice_no LIKE ? OR s.name LIKE ? OR s.reg_number LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Date range filter (created_at)
if ($from_date !== '' && $to_date !== '') {
    // ensure valid dates (basic)
    $where[] = "(DATE(i.created_at) BETWEEN ? AND ?)";
    $types .= 'ss';
    $params[] = $from_date;
    $params[] = $to_date;
} elseif ($from_date !== '') {
    $where[] = "(DATE(i.created_at) >= ?)";
    $types .= 's';
    $params[] = $from_date;
} elseif ($to_date !== '') {
    $where[] = "(DATE(i.created_at) <= ?)";
    $types .= 's';
    $params[] = $to_date;
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) AS total
              FROM invoices i
              LEFT JOIN students s ON i.student_id = s.student_id
              $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$totalPages = max(1, ceil($total / $perPage));

// Fetch invoices with limit/offset
$sql = "SELECT i.*, s.name AS student_name, s.reg_number
        FROM invoices i
        LEFT JOIN students s ON i.student_id = s.student_id
        $where_sql
        ORDER BY i.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// bind params (existing types + two integers for limit & offset)
if ($types === '') {
    // only limit & offset
    $stmt->bind_param('ii', $perPage, $offset);
} else {
    // merge types and params
    $full_types = $types . 'ii';
    $stmt->bind_param($full_types, ...array_merge($params, [$perPage, $offset]));
}

$stmt->execute();
$invoices = $stmt->get_result();
?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar already included above -->
     <?php 
        include("../../includes/staff_sidebar.php");
    ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2 class="fw-bold"><i class="fas fa-file-invoice-dollar"></i> Invoices</h2>
        <small class="text-muted">Manage student invoices</small>
      </div>

      <!-- Alerts -->
      <?php if ($successMsg) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
          <?php echo htmlspecialchars($successMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if ($errorMsg) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
          <?php echo htmlspecialchars($errorMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Filters -->
      <div class="card mb-3 shadow-sm">
        <div class="card-body">
          <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label small">Search (Invoice No, Student, Reg No)</label>
              <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search...">
            </div>
            <div class="col-md-2">
              <label class="form-label small">From</label>
              <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label small">To</label>
              <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="form-control">
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
              <a href="<?php echo BASE_URL; ?>modules/staff/invoices.php" class="btn btn-outline-secondary">Reset</a>
              <div class="ms-auto text-muted align-self-center">Showing <?php echo ($total==0?0:($offset+1)); ?> - <?php echo min($offset+$perPage, $total); ?> of <?php echo $total; ?></div>
            </div>
          </form>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-list"></i> All Invoices
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Invoice No</th>
                  <th>Student</th>
                  <th>Reg Number</th>
                  <th class="text-end">Amount</th>
                  <th class="text-end">Paid</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Expires</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = $offset + 1; if ($invoices->num_rows === 0) : ?>
                  <tr><td colspan="10" class="text-center text-muted">No invoices found.</td></tr>
                <?php else: while ($row = $invoices->fetch_assoc()) : ?>
                <tr>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($row['invoice_no']); ?></td>
                  <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['reg_number']); ?></td>
                  <td class="text-end"><?php echo number_format($row['amount'], 2); ?></td>
                  <td class="text-end"><?php echo number_format($row['paid_amount'], 2); ?></td>
                  <td>
                    <?php
                      $badge = "bg-secondary";
                      if ($row['status'] === "PAID") $badge = "bg-success";
                      elseif ($row['status'] === "PARTIAL") $badge = "bg-warning text-dark";
                      elseif ($row['status'] === "PENDING") $badge = "bg-danger";
                    ?>
                    <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                  <td><?php echo htmlspecialchars($row['expires_at']); ?></td>
                  <td>
                    <?php
                      $balance = number_format(floatval($row['amount']) - floatval($row['paid_amount']), 2, '.', '');
                    ?>
                    <button class="btn btn-sm btn-success pay-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#payInvoiceModal"
                            data-invoice-id="<?php echo intval($row['invoice_id']); ?>"
                            data-invoice-no="<?php echo htmlspecialchars($row['invoice_no']); ?>"
                            data-student="<?php echo htmlspecialchars($row['student_name']); ?>"
                            data-balance="<?php echo $balance; ?>">
                      <i class="fas fa-credit-card"></i> Pay
                    </button>
                  </td>
                </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <nav aria-label="Invoices pagination">
            <ul class="pagination justify-content-center mt-3">
              <?php
                // Build base URL for pagination preserving filters
                $queryParams = $_GET;
                function pageLink($p, $label, $disabled = false, $active = false) {
                    global $queryParams;
                    $queryParams['page'] = $p;
                    $qs = http_build_query($queryParams);
                    $href = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $qs);
                    $class = 'page-link';
                    if ($disabled) $class .= ' disabled';
                    if ($active) $class .= ' active';
                    return "<a class=\"$class\" href=\"$href\">$label</a>";
                }

                $prev = max(1, $page - 1);
                $next = min($totalPages, $page + 1);
              ?>
              <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php $queryParams['page']=$page-1; echo htmlspecialchars($_SERVER['PHP_SELF'].'?'.http_build_query($queryParams)); ?>">Previous</a>
              </li>

              <?php
                // show up to 7 page links centered on current
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                for ($p = $start; $p <= $end; $p++) {
                    $activeClass = ($p === $page) ? 'active' : '';
                    $queryParams['page'] = $p;
                    $href = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($queryParams));
                    echo "<li class=\"page-item $activeClass\"><a class=\"page-link\" href=\"$href\">$p</a></li>";
                }
              ?>

              <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php $queryParams['page']=$page+1; echo htmlspecialchars($_SERVER['PHP_SELF'].'?'.http_build_query($queryParams)); ?>">Next</a>
              </li>
            </ul>
          </nav>

        </div>
      </div>
    </main>
  </div>
</div>

<!-- Reusable Pay Invoice Modal (single instance) -->
<div class="modal fade" id="payInvoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="payInvoiceForm" method="POST" action="<?php echo BASE_URL; ?>modules/staff/payments.php">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-credit-card"></i> Pay Invoice</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="invoice_id" id="modal_invoice_id" value="">
          <div class="mb-3">
            <label class="form-label">Invoice No</label>
            <input type="text" id="modal_invoice_no" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Student</label>
            <input type="text" id="modal_student" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Balance</label>
            <input type="text" id="modal_balance" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Amount</label>
            <input type="number" step="0.01" name="amount" id="modal_amount" class="form-control" min="0.01" required>
            <div class="form-text text-danger d-none" id="modal_amount_error"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="pay_invoice" class="btn btn-success">Confirm Payment</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Client-side JS: prefill modal and validate -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Prefill modal when pay button clicked
  document.querySelectorAll('.pay-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const invoiceId = this.getAttribute('data-invoice-id');
      const invoiceNo = this.getAttribute('data-invoice-no');
      const student = this.getAttribute('data-student');
      const balance = parseFloat(this.getAttribute('data-balance') || 0);

      document.getElementById('modal_invoice_id').value = invoiceId;
      document.getElementById('modal_invoice_no').value = invoiceNo;
      document.getElementById('modal_student').value = student;
      document.getElementById('modal_balance').value = balance.toFixed(2);
      document.getElementById('modal_amount').value = balance.toFixed(2);

      // hide previous error
      const err = document.getElementById('modal_amount_error');
      err.classList.add('d-none');
      err.innerText = '';
    });
  });

  // Validate before submit
  const form = document.getElementById('payInvoiceForm');
  form.addEventListener('submit', function(e) {
    const amountEl = document.getElementById('modal_amount');
    const balance = parseFloat(document.getElementById('modal_balance').value || 0);
    const amount = parseFloat(amountEl.value || 0);
    const err = document.getElementById('modal_amount_error');

    if (isNaN(amount) || amount <= 0) {
      e.preventDefault();
      err.innerText = 'Please enter a valid amount (greater than 0).';
      err.classList.remove('d-none');
      amountEl.focus();
      return false;
    }
    if (amount > balance) {
      e.preventDefault();
      err.innerText = 'Payment exceeds remaining balance (' + balance.toFixed(2) + ').';
      err.classList.remove('d-none');
      amountEl.focus();
      return false;
    }
    return true;
  });

  // Auto fade alerts after 3s
  setTimeout(function() {
    let alertBox = document.getElementById('alertBox');
    if (alertBox) {
      let alert = new bootstrap.Alert(alertBox);
      alert.close();
    }
  }, 3000);
});
</script>

<?php
$stmt->close();
include("../../includes/footer.php");
?>
