<?php
session_start();
include 'db.php';

// Check admin logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// ---------- Handle Single Actions (Accept / Decline / Delete) ----------
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE submissions SET status = 'ACCEPTED' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'decline') {
        $stmt = $conn->prepare("UPDATE submissions SET status = 'DECLINED' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete') {
        // Delete submission row and files
        $stmt = $conn->prepare("DELETE FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $files = $conn->query("SELECT file_path FROM submission_files WHERE submission_id = $id");
        while ($f = $files->fetch_assoc()) {
            if (file_exists($f['file_path'])) {
                @unlink($f['file_path']);
            }
        }
        $conn->query("DELETE FROM submission_files WHERE submission_id = $id");
    }

    header("Location: admin.php?tab=submissions");
    exit;
}

// ---------- Handle Bulk Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['selected'])) {
    $ids = $_POST['selected'];
    $action = $_POST['bulk_action'];

    foreach ($ids as $id) {
        $id = intval($id);

        if ($action === 'accept') {
            $stmt = $conn->prepare("UPDATE submissions SET status = 'ACCEPTED' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'decline') {
            $stmt = $conn->prepare("UPDATE submissions SET status = 'DECLINED' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM submissions WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $files = $conn->query("SELECT file_path FROM submission_files WHERE submission_id = $id");
            while ($f = $files->fetch_assoc()) {
                if (file_exists($f['file_path'])) {
                    @unlink($f['file_path']);
                }
            }
            $conn->query("DELETE FROM submission_files WHERE submission_id = $id");
        }
    }

    header("Location: admin.php?tab=submissions");
    exit;
}

// ---------- Search & Fetch Submissions ----------
$search = "";
$sql = "SELECT * FROM submissions";
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = $conn->real_escape_string($_GET['search']);
    $sql .= " WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR ip_type LIKE '%$search%'";
}
$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);

// Determine active tab (dashboard, users, submissions, workflow)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - SLSU ITSSO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      min-height: 100vh;
      background: #235601;
      color: #fff;
      padding-top: 20px;
    }
    .sidebar h4 { text-align: center; margin-bottom: 20px; font-size: 18px; }
    .sidebar a {
      color: #cfd8dc; display: block; padding: 12px 20px; margin: 5px 0; border-radius: 8px;
      text-decoration: none; transition: all 0.3s;
    }
    .sidebar a:hover, .sidebar a.active { background: #A7DBE6; color: #fff; }
    .topbar { background: #fff; border-bottom: 1px solid #dee2e6; padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; }
    .module-box {
      display: block; width:100%; max-width:180px; height:120px; margin:10px auto; border:1px solid #dee2e6;
      border-radius:10px; background:#fff; text-align:center; cursor:pointer; transition:transform .2s, background .2s; text-decoration:none; color:#000;
    }
    .module-box:hover { transform: scale(1.05); background: #f1f1f1; }
    .module-box i { font-size:36px; margin-top:20px; color: #A7DBE6; }
    .module-box span { display:block; margin-top:8px; font-size:14px; font-weight:500; }

    /* table card rounding */
    .card { border-radius: 12px; }

    @media (max-width: 767px) {
      .sidebar { position: fixed; left: -250px; width: 220px; top:0; height:100%; transition:left .3s; z-index:1050; }
      .sidebar.active { left: 0; }
      .content-area { margin-left: 0 !important; }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      <!-- Sidebar -->
      <div class="col-12 col-md-2 sidebar" id="sidebar">
        <h4>SLSU-ITSSO</h4>
        <a href="admin.php?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="admin.php?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>"><i class="fa fa-users me-2"></i> Users</a>
        <a href="admin.php?tab=submissions" class="<?= $tab === 'submissions' ? 'active' : '' ?>"><i class="fa fa-file-alt me-2"></i> Submissions</a>
        <a href="admin.php?tab=documents"><i class="fa fa-folder me-2"></i> Documents</a>
        
        <a href="admin.php?tab=messaging"><i class="fa fa-envelope me-2"></i> Messaging</a>
        <a href="admin.php?tab=audit"><i class="fa fa-shield-alt me-2"></i> Audit Trail</a>
      </div>

      <!-- Main Content -->
      <div class="col content-area">
        <!-- Topbar -->
        <div class="topbar">
          <h5>Admin Dashboard</h5>
          <div class="d-flex align-items-center">
            <button class="btn btn-outline-primary btn-sm d-md-none me-2" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <div class="me-3 text-end d-none d-md-block">
              <span class="me-3">👋 Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
              <a href="change_password.php" class="btn btn-sm btn-warning">settings</a>
              <a href="login.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
            <div class="d-md-none">
              <a href="login.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
          </div>
        </div>

        <div class="p-3">
          <!-- ---------- DASHBOARD TAB ---------- -->
          <?php if ($tab === 'dashboard'): ?>
            <div class="row">
              <div class="col-12">
                <div class="card shadow-sm p-3 mb-3 d-flex align-items-center">
      
                  <!-- Logo beside title -->
                  
                  <img src="ITSSOLOGO.png" alt="SLSU Logo" width="70" class="me-3">

                  <div>
                    <h4 class="mb-1">Welcome to SLSU-ITSSO Admin Panel</h4>
                    <p class="mb-0">Use the sidebar to navigate between modules.</p>
                </div>

              </div>
            </div>
          </div>


            <div class="row mt-3 text-center">
              <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="admin.php?tab=users" class="module-box"><i class="fa fa-users"></i><span>Users</span></a>
              </div>
              <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="admin.php?tab=submissions" class="module-box"><i class="fa fa-file-alt"></i><span>Submissions</span></a>
              </div>
              <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="admin.php?tab=documents" class="module-box"><i class="fa fa-folder"></i><span>Documents</span></a>
              </div>
            
              <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="admin.php?tab=messaging" class="module-box"><i class="fa fa-envelope"></i><span>Messaging</span></a>
              </div>
              <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="admin.php?tab=audit" class="module-box"><i class="fa fa-shield-alt"></i><span>Audit Trail</span></a>
              </div>
            </div>
          <?php endif; ?>

          <!-- ---------- USERS TAB ---------- -->
          <?php if ($tab === 'users'): ?>
            <div class="card shadow-sm mb-3">
              <div class="card-body">
                <h5>Users Management</h5>
                <div class="d-flex flex-wrap align-items-center mt-3 mb-2 p-3 bg-white rounded shadow-sm">
                  <div class="flex-grow-1">
                    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by Name or Email">
                  </div>
                  <div class="ms-2">
                    <select id="filterAZ" class="form-select">
                      <option value="">Filter A–Z</option>
                      <option value="asc">Sort A–Z</option>
                      <option value="desc">Sort Z–A</option>
                    </select>
                  </div>
                </div>

                




                <div class="mt-2 p-3 bg-white rounded shadow-sm">
                  <h6 class="mb-3">All Registered Users</h6>
                  <div class="table-responsive">
                    <table id="usersTable" class="table table-bordered table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>#</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Submitted Files</th>
                          <th>Status</th>
                          <th>Date Created</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                          // Fetch all unique users from submissions table
                          $userSearch = "";
                          $userQuery = "SELECT * FROM submissions";
                          if (isset($_GET['search']) && $_GET['search'] !== "") {
                            $userSearch = $conn->real_escape_string($_GET['search']);
                            $userQuery .= " WHERE full_name LIKE '%$userSearch%' OR email LIKE '%$userSearch%'";
                          }
                          $userQuery .= " ORDER BY created_at DESC";
                          $users = $conn->query($userQuery);

                          if ($users && $users->num_rows > 0):
                            while ($user = $users->fetch_assoc()):
                              $uid = intval($user['id']);
                              $files = $conn->query("SELECT * FROM submission_files WHERE submission_id = $uid");
                      ?>
                        <tr>
                          <td><?= $uid ?></td>
                          <td><?= htmlspecialchars($user['full_name']) ?></td>
                          <td><?= htmlspecialchars($user['email']) ?></td>
                          <td>
                            <?php if ($files->num_rows > 0): ?>
                              <?php while ($f = $files->fetch_assoc()): ?>
                                <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank"><?= htmlspecialchars($f['file_name']) ?></a><br>
                              <?php endwhile; ?>
                            <?php else: ?>
                              <span class="text-muted">No Files</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($user['status'] === "PENDING"): ?>
                              <span class="badge bg-secondary">Pending</span>
                            <?php elseif ($user['status'] === "ACCEPTED"): ?>
                              <span class="badge bg-success">Accepted</span>
                            <?php elseif ($user['status'] === "DECLINED"): ?>
                              <span class="badge bg-danger">Declined</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($user['created_at']) ?></td>
                        </tr>
                      <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No registered users found.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>


                  <div class="pagination-controls mt-3 d-flex justify-content-center">
                    <button id="prevBtn" class="btn btn-sm btn-secondary me-2">Previous</button>
                    <span id="pageInfo" class="align-self-center"></span>
                    <button id="nextBtn" class="btn btn-sm btn-secondary ms-2">Next</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- ---------- SUBMISSIONS TAB (your original admin table) ---------- -->
          <?php if ($tab === 'submissions'): ?>
            <div class="card shadow-sm">
              <div class="card-body">
                <h4 class="mb-3">📂 Submitted Files</h4>

                <!-- Search Bar -->
                <form method="GET" action="admin.php" class="mb-3 d-flex">
                  <input type="hidden" name="tab" value="submissions">
                  <input type="text" name="search" class="form-control me-2" placeholder="Search by name, email or IP type..." value="<?= htmlspecialchars($search) ?>">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="admin.php?tab=submissions" class="btn btn-secondary ms-2">Reset</a>
                </form>

                <!-- Bulk Action Form -->
                <form method="POST" action="admin.php?tab=submissions">
                  <div class="mb-2 d-flex">
                    <select name="bulk_action" class="form-select w-auto me-2" required>
                      <option value="">-- Bulk Action --</option>
                      <option value="accept">Accept Selected</option>
                      <option value="decline">Decline Selected</option>
                      <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Apply</button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th><input type="checkbox" id="selectAllCheckbox"></th>
                          <th>ID</th>
                          <th>Full Name</th>
                          <th>Email</th>
                          <th>IP Type</th>
                          <th>Files</th>
                          <th>Status</th>
                          <th>Date Submitted</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                          <?php $sub_id = intval($row['id']); ?>
                          <?php $files = $conn->query("SELECT * FROM submission_files WHERE submission_id = $sub_id"); ?>
                          <tr>
                            <td><input type="checkbox" name="selected[]" value="<?= $row['id'] ?>"></td>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['ip_type']) ?></td>
                            <td>
                              <?php while($f = $files->fetch_assoc()): ?>
                                <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank"><?= htmlspecialchars($f['file_name']) ?></a><br>
                              <?php endwhile; ?>
                            </td>
                            <td>
                              <?php if ($row['status'] == "PENDING"): ?>
                                <span class="badge bg-secondary">Pending</span>
                              <?php elseif ($row['status'] == "ACCEPTED"): ?>
                                <span class="badge bg-success">Accepted</span>
                              <?php else: ?>
                                <span class="badge bg-danger">Declined</span>
                              <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                              <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                              <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</a>
                              <a href="admin.php?action=accept&id=<?= $row['id'] ?>&tab=submissions" class="btn btn-sm btn-success">Accept</a>
                              <a href="admin.php?action=decline&id=<?= $row['id'] ?>&tab=submissions" class="btn btn-sm btn-dark">Decline</a>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>

          



          <!-- ---------- Placeholder tabs (documents, messaging, audit) ---------- -->
          <!-- ---------- DOCUMENTS, MESSAGING, AUDIT ---------- -->
          <?php if ($tab === 'documents'): ?>
            <div class="card shadow-sm p-3">
            <h4>📁 Documents Repository</h4>
            <p class="text-muted">Browse all uploaded files from users and submissions.</p>

            <!-- Search -->
            <form method="GET" class="mb-3 d-flex">
              <input type="hidden" name="tab" value="documents">
              <input type="text" name="search" class="form-control me-2" placeholder="Search by file name, user, or IP type" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
              <button type="submit" class="btn btn-primary">Search</button>
              <a href="admin.php?tab=documents" class="btn btn-secondary ms-2">Reset</a>
            </form>

            <?php
              $fileSearch = "";
              $query = "
                SELECT f.id AS file_id, f.file_name, f.file_path, s.full_name, s.email, s.ip_type, s.status, s.created_at
                FROM submission_files f
                JOIN submissions s ON f.submission_id = s.id
              ";

              if (!empty($_GET['search'])) {
                $fileSearch = $conn->real_escape_string($_GET['search']);
                $query .= "
                  WHERE f.file_name LIKE '%$fileSearch%' 
                  OR s.full_name LIKE '%$fileSearch%' 
                  OR s.email LIKE '%$fileSearch%' 
                  OR s.ip_type LIKE '%$fileSearch%'
                ";
              }

              $query .= " ORDER BY s.created_at DESC";
              $filesResult = $conn->query($query);
            ?>

            <div class="table-responsive mt-3">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                  <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Uploaded By</th>
                    <th>Email</th>
                    <th>IP Type</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Action</th>
                  </tr>
                </thead>
            <tbody>
          <?php if ($filesResult && $filesResult->num_rows > 0): ?>
            <?php $count = 1; ?>
            <?php while ($file = $filesResult->fetch_assoc()): ?>
              <tr>
                <td><?= $count++ ?></td>
                <td><?= htmlspecialchars($file['file_name']) ?></td>
                <td><?= htmlspecialchars($file['full_name']) ?></td>
                <td><?= htmlspecialchars($file['email']) ?></td>
                <td><?= htmlspecialchars($file['ip_type']) ?></td>
                <td>
                  <?php if ($file['status'] === "PENDING"): ?>
                    <span class="badge bg-secondary">Pending</span>
                  <?php elseif ($file['status'] === "ACCEPTED"): ?>
                    <span class="badge bg-success">Accepted</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Declined</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($file['created_at']) ?></td>
                <td>
                  <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa fa-download"></i> View / Download
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="8" class="text-center text-muted">No documents found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  
  <?php endif; ?>


        


        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("active");
    }

    // Select all for submissions table (single checkbox controlling items)
    document.addEventListener('DOMContentLoaded', function() {
      var selectAll = document.getElementById('selectAllCheckbox');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          var checks = document.getElementsByName('selected[]');
          for (var i=0;i<checks.length;i++){ checks[i].checked = selectAll.checked; }
        });
      }
    });

    // ---------- USERS TAB JS (client-side search + pagination + sort) ----------
    (function(){
      const rowsPerPage = 10;
      let currentPage = 1;
      function updateTable() {
        let rows = document.querySelectorAll("#usersTable tbody tr");
        let filter = (document.getElementById("searchInput") ? document.getElementById("searchInput").value.toLowerCase() : "");
        let start = (currentPage - 1) * rowsPerPage;
        let end = start + rowsPerPage;

        let visibleRows = [];
        rows.forEach(row => {
          let text = row.innerText.toLowerCase();
          if (text.includes(filter)) visibleRows.push(row);
        });

        rows.forEach(row => row.style.display = "none");
        visibleRows.slice(start, end).forEach(row => row.style.display = "");
        document.getElementById("pageInfo").innerText =
          `Page ${currentPage} of ${Math.ceil(visibleRows.length / rowsPerPage) || 1}`;

        document.getElementById("prevBtn").disabled = currentPage === 1;
        document.getElementById("nextBtn").disabled = currentPage >= Math.ceil(visibleRows.length / rowsPerPage);
      }

      if (document.getElementById("searchInput")) {
        document.getElementById("searchInput").addEventListener("keyup", () => { currentPage = 1; updateTable(); });
      }
      if (document.getElementById("filterAZ")) {
        document.getElementById("filterAZ").addEventListener("change", function() {
          let order = this.value;
          let tbody = document.querySelector("#usersTable tbody");
          let rows = Array.from(tbody.querySelectorAll("tr"));
          rows.sort((a,b) => {
            let nameA=a.cells[0].innerText.toLowerCase(), nameB=b.cells[0].innerText.toLowerCase();
            if (order==="asc") return nameA.localeCompare(nameB);
            if (order==="desc") return nameB.localeCompare(nameA);
            return 0;
          });
          tbody.innerHTML = "";
          rows.forEach(r => tbody.appendChild(r));
          currentPage = 1; updateTable();
        });
      }
      if (document.getElementById("prevBtn")) {
        document.getElementById("prevBtn").addEventListener("click", () => { if (currentPage>1) { currentPage--; updateTable(); } });
      }
      if (document.getElementById("nextBtn")) {
        document.getElementById("nextBtn").addEventListener("click", () => {
          let rows = document.querySelectorAll("#usersTable tbody tr");
          let filter = document.getElementById("searchInput").value.toLowerCase();
          let visibleRows = Array.from(rows).filter(r => r.innerText.toLowerCase().includes(filter));
          if (currentPage < Math.ceil(visibleRows.length / rowsPerPage)) { currentPage++; updateTable(); }
        });
      }
      // Initial call
      updateTable();
    })();

    // ---------- WORKFLOW TAB JS ----------
    (function(){
      let selectedUser = null;
      let userWorkflows = {};

      var userSearch = document.getElementById("userSearch");
      if (userSearch) {
        userSearch.addEventListener("change", function() {
          selectedUser = this.value;
          if (!userWorkflows[selectedUser]) {
            userWorkflows[selectedUser] = { step: 1, status: "Submitted", progress: 25 };
          }
          updateUserView();
        });
      }

      window.setStep = function(step) {
        if (!selectedUser) {
          alert("Please select a user first!");
          return;
        }

        let statusText = "";
        let progressPercent = 25;
        switch(step) {
          case 1: statusText="Submitted"; progressPercent=25; break;
          case 2: statusText="Under Review"; progressPercent=50; break;
          case 3: statusText="Approved"; progressPercent=75; break;
          case 4: statusText="Completed"; progressPercent=100; break;
        }
        userWorkflows[selectedUser] = { step, status: statusText, progress: progressPercent };
        updateUserView();
      };

      function updateUserView() {
        // reset
        document.querySelectorAll('.workflow-step').forEach(el => el.classList.remove('active'));
        if (selectedUser) {
          document.getElementById("selectedUser").innerText = selectedUser;
          document.getElementById("userStatus").innerText = userWorkflows[selectedUser].status;
          document.getElementById("progressBar").style.width = userWorkflows[selectedUser].progress + "%";
          document.getElementById("progressBar").innerText = userWorkflows[selectedUser].status;
          for (let i=1;i<=userWorkflows[selectedUser].step;i++){
            let el = document.getElementById("step"+i);
            if (el) el.classList.add("active");
          }
        }
      }
    })();
  </script>

  


  <style>
    /* workflow step styles (kept here for simplicity) */
    .workflow-step { display:flex; align-items:center; margin-bottom:10px; }
    .workflow-step .circle { width:40px; height:40px; border-radius:50%; background:#dee2e6; color:#6c757d; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:bold; transition:all .3s; }
    .workflow-step.active .circle { background:#0d6efd; color:white; }
  </style>
</body>
</html>
