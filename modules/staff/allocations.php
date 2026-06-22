<?php
// modules/staff/allocations.php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include_once("../../config/app.php");

// Security: only staff
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Create audit table if not exists (one-time safe)
$createAudit = "
CREATE TABLE IF NOT EXISTS allocations_audit (
  audit_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  allocation_id INT NULL,
  action VARCHAR(20) NOT NULL,
  performed_by VARCHAR(100) NOT NULL,
  details TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createAudit);

// CSRF token for forms and AJAX
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

// Messages
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Filters & pagination
$search = trim($_GET['q'] ?? '');
$room_filter = intval($_GET['room_id'] ?? 0);
$student_filter = trim($_GET['student'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE clauses
$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(s.name LIKE ? OR s.reg_number LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($room_filter > 0) {
    $where[] = "a.room_id = ?";
    $types .= 'i';
    $params[] = $room_filter;
}

if ($student_filter !== '') {
    $where[] = "s.name LIKE ?";
    $types .= 's';
    $params[] = '%' . $student_filter . '%';
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_sql = "SELECT COUNT(*) AS total
              FROM allocations a
              LEFT JOIN students s ON a.student_id = s.student_id
              LEFT JOIN rooms r ON a.room_id = r.room_id
              $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$totalPages = max(1, ceil($total / $perPage));

// Fetch allocations with limit
$sql = "SELECT a.*, s.name AS student_name, s.reg_number, r.room_number, r.capacity
        FROM allocations a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN rooms r ON a.room_id = r.room_id
        $where_sql
        ORDER BY a.allocation_date DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types === '') {
    $stmt->bind_param('ii', $perPage, $offset);
} else {
    $full_types = $types . 'ii';
    $stmt->bind_param($full_types, ...array_merge($params, [$perPage, $offset]));
}
$stmt->execute();
$allocations = $stmt->get_result();

// Fetch rooms and students for filters / modal selects
$rooms = $conn->query("SELECT room_id, room_number, capacity FROM rooms ORDER BY room_number ASC");
$students = $conn->query("SELECT student_id, name, reg_number FROM students ORDER BY name ASC");
?>

<div class="container-fluid">
  <div class="row">
    <?php include("../../includes/staff_sidebar.php"); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2 class="h4"><i class="fas fa-door-open"></i> Allocations</h2>
        <div>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#allocationModal" id="addAllocationBtn">
            <i class="fas fa-plus"></i> Add Allocation
          </button>
        </div>
      </div>

      <?php if ($successMsg) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
          <?php echo htmlspecialchars($successMsg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if ($errorMsg) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
          <?php echo htmlspecialchars($errorMsg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card mb-3 shadow-sm">
        <div class="card-body">
          <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label small">Search Student or Reg No</label>
              <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Name or Reg number">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Room</label>
              <select name="room_id" class="form-select">
                <option value="0">All rooms</option>
                <?php
                // reset pointer
                $rooms->data_seek(0);
                while($r = $rooms->fetch_assoc()): ?>
                  <option value="<?php echo $r['room_id']; ?>" <?php if($room_filter == $r['room_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($r['room_number']); ?> (cap: <?php echo intval($r['capacity']); ?>)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small">Student name</label>
              <input type="text" name="student" value="<?php echo htmlspecialchars($student_filter); ?>" class="form-control" placeholder="Partial name">
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Apply</button>
              <a href="<?php echo BASE_URL; ?>modules/staff/allocations.php" class="btn btn-outline-secondary">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-info text-white"><i class="fas fa-list"></i> Allocations</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Reg No</th>
                  <th>Room</th>
                  <th>Allocation Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = $offset + 1; if ($allocations->num_rows === 0): ?>
                  <tr><td colspan="7" class="text-center text-muted">No allocations found.</td></tr>
                <?php else: while($a = $allocations->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($a['student_name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($a['reg_number'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($a['room_number'] ?? ''); ?> (cap: <?php echo intval($a['capacity']); ?>)</td>
                  <td><?php echo htmlspecialchars($a['allocation_date'] ?? ''); ?></td>
                  <td>
                    <?php 
                        $statusClass = ($a['status'] == 'ACTIVE') ? 'bg-success' : 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($a['status'] ?? ''); ?></span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-warning edit-alloc-btn"
                            data-id="<?php echo $a['allocation_id']; ?>"
                            data-student-id="<?php echo $a['student_id']; ?>"
                            data-room-id="<?php echo $a['room_id']; ?>"
                            data-date="<?php echo $a['allocation_date']; ?>"
                            data-status="<?php echo htmlspecialchars($a['status'] ?? 'ACTIVE'); ?>"
                            data-bs-toggle="modal" data-bs-target="#allocationModal">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-alloc-btn"
                            data-id="<?php echo $a['allocation_id']; ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>
          </div>

          <nav aria-label="Allocations pagination">
            <ul class="pagination justify-content-center mt-3">
              <?php
                $queryParams = $_GET;
                $prevPage = max(1, $page - 1);
                $nextPage = min($totalPages, $page + 1);
              ?>
              <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => $page-1]))); ?>">Previous</a>
              </li>
              <?php
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                for ($p = $start; $p <= $end; $p++) {
                    $active = ($p === $page) ? 'active' : '';
                    echo '<li class="page-item '.$active.'"><a class="page-link" href="'.htmlspecialchars($_SERVER['PHP_SELF'].'?'.http_build_query(array_merge($_GET, ['page'=>$p]))).'">'.$p.'</a></li>';
                }
              ?>
              <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => $page+1]))); ?>">Next</a>
              </li>
            </ul>
          </nav>

        </div>
      </div>
    </main>
  </div>
</div>

<div class="modal fade" id="allocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="allocationForm" method="POST" action="<?php echo BASE_URL; ?>modules/staff/allocations_action.php">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="allocModalTitle"><i class="fas fa-plus"></i> Add Allocation</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="allocation_id" id="alloc_id" value="">
          <div class="mb-3">
            <label class="form-label">Student</label>
            <select name="student_id" id="alloc_student" class="form-select" required>
              <option value="">-- Select student --</option>
              <?php
                // reset pointer and fetch students again
                $students->data_seek(0);
                while($s = $students->fetch_assoc()) {
                  echo '<option value="'.intval($s['student_id']).'">'.htmlspecialchars($s['name']).' ('.htmlspecialchars($s['reg_number']).')</option>';
                }
              ?>
            </select>
            <div class="form-text text-muted">If student already has an active allocation, you will be warned.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Room</label>
            <select name="room_id" id="alloc_room" class="form-select" required>
              <option value="">-- Select room --</option>
              <?php
                // reset pointer and fetch rooms again
                $rooms->data_seek(0);
                while($r = $rooms->fetch_assoc()) {
                  echo '<option value="'.intval($r['room_id']).'">'.htmlspecialchars($r['room_number']).' (cap: '.intval($r['capacity']).')</option>';
                }
              ?>
            </select>
            <div class="form-text text-muted">Room capacity will be checked before saving.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Allocation Date</label>
            <input type="date" name="allocation_date" id="alloc_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="alloc_status" class="form-select" required>
              <option value="ACTIVE">ACTIVE</option>
              <option value="INACTIVE">INACTIVE</option>
            </select>
          </div>
          <div class="alert alert-warning d-none" id="allocWarning"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="save_allocation" class="btn btn-success" id="allocSaveBtn">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this allocation?</p>
        <div class="alert alert-danger d-none" id="deleteError"></div>
      </div>
      <div class="modal-footer">
        <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const csrfToken = '<?php echo $csrf_token; ?>';
  const allocationForm = document.getElementById('allocationForm');
  const allocModalTitle = document.getElementById('allocModalTitle');
  const allocWarning = document.getElementById('allocWarning');
  const addBtn = document.getElementById('addAllocationBtn');

  // Add button resets modal
  addBtn.addEventListener('click', function() {
    allocModalTitle.innerText = 'Add Allocation';
    document.getElementById('alloc_id').value = '';
    document.getElementById('alloc_student').value = '';
    document.getElementById('alloc_room').value = '';
    
    // Set today's date automatically for convenience
    document.getElementById('alloc_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('alloc_status').value = 'ACTIVE';
    allocWarning.classList.add('d-none');
    allocWarning.innerText = '';
  });

  // Edit buttons populate modal
  document.querySelectorAll('.edit-alloc-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      allocModalTitle.innerText = 'Edit Allocation';
      document.getElementById('alloc_id').value = this.getAttribute('data-id');
      document.getElementById('alloc_student').value = this.getAttribute('data-student-id');
      document.getElementById('alloc_room').value = this.getAttribute('data-room-id');
      document.getElementById('alloc_date').value = this.getAttribute('data-date');
      document.getElementById('alloc_status').value = this.getAttribute('data-status') || 'ACTIVE';
      allocWarning.classList.add('d-none');
      allocWarning.innerText = '';
    });
  });

  // AJAX submit for add/edit
  allocationForm.addEventListener('submit', function(e) {
    e.preventDefault();
    allocWarning.classList.add('d-none');
    allocWarning.innerText = '';

    const formData = new FormData(allocationForm);
    formData.append('ajax', '1'); // indicate AJAX

    // Client-side basic validation
    const studentId = formData.get('student_id');
    const roomId = formData.get('room_id');
    const date = formData.get('allocation_date');
    
    if (!studentId || !roomId || !date) {
      allocWarning.classList.remove('d-none');
      allocWarning.innerText = 'Tafadhali jaza mwanafunzi, chumba, na tarehe.';
      return;
    }

    fetch('<?php echo BASE_URL; ?>modules/staff/allocations_action.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        var modal = bootstrap.Modal.getInstance(document.getElementById('allocationModal'));
        if (modal) modal.hide();
        
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-success alert-dismissible fade show mt-3';
        alertBox.role = 'alert';
        alertBox.innerHTML = data.success + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.querySelector('main .d-flex').insertAdjacentElement('afterend', alertBox);
        setTimeout(()=>{ location.reload(); }, 1200);
      } else {
        allocWarning.classList.remove('d-none');
        allocWarning.innerText = data.error || 'Operation failed.';
      }
    })
    .catch(err => {
      allocWarning.classList.remove('d-none');
      allocWarning.innerText = 'Network error. Try again.';
      console.error(err);
    });
  });

  // Delete flow with confirmation modal and AJAX
  let deleteId = 0;
  document.querySelectorAll('.delete-alloc-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      deleteId = this.getAttribute('data-id');
      var delModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
      delModal.show();
    });
  });

  document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const deleteError = document.getElementById('deleteError');
    deleteError.classList.add('d-none');
    deleteError.innerText = '';

    const fd = new FormData();
    fd.append('delete_id', deleteId);
    fd.append('csrf_token', csrfToken);
    fd.append('ajax', '1');

    fetch('<?php echo BASE_URL; ?>modules/staff/allocations_action.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        var delModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        if (delModal) delModal.hide();
        
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-success alert-dismissible fade show mt-3';
        alertBox.role = 'alert';
        alertBox.innerHTML = data.success + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.querySelector('main .d-flex').insertAdjacentElement('afterend', alertBox);
        setTimeout(()=>{ location.reload(); }, 1200);
      } else {
        deleteError.classList.remove('d-none');
        deleteError.innerText = data.error || 'Delete failed.';
      }
    })
    .catch(err => {
      deleteError.classList.remove('d-none');
      deleteError.innerText = 'Network error. Try again.';
      console.error(err);
    });
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