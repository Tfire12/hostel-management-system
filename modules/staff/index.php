<?php
session_start();

include("../../config/database.php");
include("../../config/app.php");

// SECURITY
if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
  header("Location: " . BASE_URL . "auth/login.php");
  exit();
}


// PAYMENT SUMMARY

$paymentSummary = $conn->query("
    SELECT
        SUM(CASE WHEN status='PAID' THEN 1 ELSE 0 END) paid,
        SUM(CASE WHEN status='PARTIAL' THEN 1 ELSE 0 END) partial,
        SUM(CASE WHEN status='PENDING' THEN 1 ELSE 0 END) pending
    FROM invoices
")->fetch_assoc();

$paid = $paymentSummary['paid'] ?? 0;
$partial = $paymentSummary['partial'] ?? 0;
$pending = $paymentSummary['pending'] ?? 0;


// TOTAL REVENUE

$revenue = $conn->query("
    SELECT SUM(amount) total
    FROM payments
    WHERE payment_status='CONFIRMED'
")->fetch_assoc();

$totalRevenue = $revenue['total'] ?? 0;



// RECENT COMPLAINTS

$recentComplaints = $conn->query("
    SELECT
        c.*,
        s.name,
        s.reg_number
    FROM complaints c
    JOIN students s
        ON c.student_id = s.student_id
    ORDER BY c.complaint_date DESC
    LIMIT 3
");

include("../../includes/header.php");
?>

<div class="container-fluid">
  <div class="row">

    <?php include("../../includes/staff_sidebar.php"); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

      <div class="d-flex justify-content-between align-items-center pt-4 mb-4">

        <div>
          <h2 class="fw-bold mb-1">
            <i class="fas fa-user-tie text-primary"></i>
            Staff Dashboard
          </h2>
        </div>
      </div>
      <!-- QUICK ACTIONS -->

      <div class="row g-4 mb-4">

        <div class="col-md-3">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="fas fa-file-invoice-dollar fa-2x text-primary mb-3"></i>
              <h6>Invoices</h6>
              <a href="<?= BASE_URL ?>modules/staff/invoices.php"
                class="btn btn-outline-primary btn-sm">
                Open
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="fas fa-credit-card fa-2x text-success mb-3"></i>
              <h6>Payments</h6>
              <a href="<?= BASE_URL ?>modules/staff/payments.php"
                class="btn btn-outline-success btn-sm">
                Open
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="fas fa-comment-dots fa-2x text-danger mb-3"></i>
              <h6>Complaints</h6>
              <a href="<?= BASE_URL ?>modules/staff/complaints.php"
                class="btn btn-outline-danger btn-sm">
                Open
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="fas fa-door-open fa-2x text-secondary mb-3"></i>
              <h6>Allocations</h6>
              <a href="<?= BASE_URL ?>modules/staff/allocations.php"
                class="btn btn-outline-secondary btn-sm">
                Open
              </a>
            </div>
          </div>
        </div>

      </div>


      <div class="row g-4">

        <!-- PAYMENT CHART -->

        <div class="col-lg-6">

          <div class="card shadow-sm border-0">

            <div class="card-header bg-primary text-white">
              <i class="fas fa-chart-pie"></i>
              Payment Status Overview
            </div>

            <div class="card-body">

              <canvas id="paymentPieChart"></canvas>

              <div class="row text-center mt-4">

                <div class="col">
                  <h5 class="text-success"><?= $paid ?></h5>
                  <small>Paid</small>
                </div>

                <div class="col">
                  <h5 class="text-warning"><?= $partial ?></h5>
                  <small>Partial</small>
                </div>

                <div class="col">
                  <h5 class="text-danger"><?= $pending ?></h5>
                  <small>Pending</small>
                </div>

              </div>

            </div>

          </div>

        </div>


        <!-- RECENT COMPLAINTS -->

        <div class="col-lg-6">

          <div class="card shadow-sm border-0">

            <div class="card-header bg-danger text-white">
              <i class="fas fa-bell"></i>
              Recent Complaints
            </div>

            <div class="card-body">

              <?php if ($recentComplaints->num_rows > 0) { ?>

                <ul class="list-group list-group-flush">

                  <?php while ($c = $recentComplaints->fetch_assoc()) { ?>

                    <li class="list-group-item">

                      <div class="fw-bold">
                        <?= htmlspecialchars($c['name']) ?>
                      </div>

                      <small class="text-muted">
                        <?= htmlspecialchars($c['reg_number']) ?>
                      </small>

                      <br>

                      <strong>
                        <?= htmlspecialchars($c['issue']) ?>
                      </strong>

                      <br>

                      <small>
                        <?= htmlspecialchars(substr($c['description'], 0, 80)) ?>...
                      </small>

                      <span class="float-end badge bg-<?= $c['status'] == 'OPEN' ? 'danger' : 'success' ?>">
                        <?= $c['status'] ?>
                      </span>

                    </li>

                  <?php } ?>

                </ul>

              <?php } else { ?>

                <div class="alert alert-info">
                  No complaints available.
                </div>

              <?php } ?>

            </div>

          </div>

        </div>

      </div>


      <!-- REVENUE CARD -->

      <div class="card shadow-sm border-0 mt-4">

        <div class="card-body text-center">

          <h5>Total Revenue Collected</h5>

          <h2 class="text-success">
            TZS <?= number_format($totalRevenue, 2) ?>
          </h2>

        </div>

      </div>

    </main>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  const ctx = document
    .getElementById('paymentPieChart')
    .getContext('2d');

  new Chart(ctx, {

    type: 'pie',

    data: {
      labels: ['Paid', 'Partial', 'Pending'],

      datasets: [{
        data: [
          <?= $paid ?>,
          <?= $partial ?>,
          <?= $pending ?>
        ],
        backgroundColor: [
          '#198754',
          '#ffc107',
          '#dc3545'
        ]
      }]
    },

    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }

  });
</script>


<style>
  .stat-card {
    border: none;
    border-radius: 15px;
    transition: .3s;
  }

  .stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, .15);
  }

  .card {
    border-radius: 15px;
  }

  .list-group-item {
    border: none;
    padding: 15px;
  }
</style>

<?php include("../../includes/footer.php"); ?>
```