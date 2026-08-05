<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin & College Authorities (Dean Sir)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'dean', 'college_authority'])) {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Handle Status Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_status'])) {
    $propId = trim($_POST['proposal_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'pending');

    if ($propId && in_array($newStatus, ['approved', 'rejected', 'pending'])) {
        try {
            $stmt = $db->prepare("UPDATE club_proposals SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $propId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'PROPOSAL_STATUS_CHANGED', 'proposal', $propId, "Updated proposal $propId status to $newStatus");
            $message = "Proposal status updated to " . strtoupper($newStatus) . " successfully!";
        } catch (Exception $e) {
            $error = "Failed to update proposal status: " . $e->getMessage();
        }
    }
}

// Handle Delete Proposal
if (isset($_GET['delete_id'])) {
    $delId = trim($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM club_proposals WHERE id = ?");
        $stmt->execute([$delId]);
        log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'PROPOSAL_DELETED', 'proposal', $delId, "Deleted proposal ID: $delId");
        header('Location: proposals.php?msg=Deleted');
        exit;
    } catch (Exception $e) {
        $error = "Error deleting proposal: " . $e->getMessage();
    }
}

// Fetch all proposals
$proposals = $db->query("SELECT * FROM club_proposals ORDER BY created_at DESC")->fetchAll();

// Statistics
$totalCount   = count($proposals);
$pendingCount = count(array_filter($proposals, fn($p) => $p['status'] === 'pending'));
$approvedCount= count(array_filter($proposals, fn($p) => $p['status'] === 'approved'));
$studentCount = count(array_filter($proposals, fn($p) => !empty($p['is_uit_student'])));
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club & Event Proposals | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }
        .proposal-card {
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #ffffff;
            padding: 20px 22px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .proposal-card:hover {
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }
        .proposal-card i, .proposal-card svg {
            margin-right: 6px;
            display: inline-block;
        }
        .student-badge {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #ffffff;
        }
        .id-card-preview {
            max-height: 240px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        .table-custom th {
            font-size: 0.72rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 18px;
            white-space: nowrap;
        }
        .table-custom td {
            padding: 16px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Top Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1 fw-bold small" style="background:#f5f3ff; color:#7c3aed;">DEAN GOVERNANCE PORTAL</span>
                <h2 class="fw-bold mb-1 text-dark mt-2">Club & Campus Event Proposals</h2>
                <p class="text-secondary small mb-0">Review student and faculty proposals, verify registered UIT college student credentials, and issue official Dean approvals.</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-warning-subtle text-warning border rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history m-0"></i> Pending Review: <?= $pendingCount ?>
                </span>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> Proposal record deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- KPI Metric Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card proposal-card shadow-xs">
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-2" style="font-size:0.7rem;">TOTAL SUBMISSIONS</span>
                    <h3 class="fw-bold text-dark mb-0"><?= $totalCount ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card proposal-card shadow-xs">
                    <span class="text-warning small fw-bold text-uppercase d-block mb-2" style="font-size:0.7rem;">PENDING DEAN REVIEW</span>
                    <h3 class="fw-bold text-warning mb-0"><?= $pendingCount ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card proposal-card shadow-xs">
                    <span class="text-purple small fw-bold text-uppercase d-block mb-2" style="font-size:0.7rem; color:#7c3aed;">REGISTERED UIT STUDENTS</span>
                    <h3 class="fw-bold mb-0" style="color:#7c3aed;"><?= $studentCount ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card proposal-card shadow-xs">
                    <span class="text-success small fw-bold text-uppercase d-block mb-2" style="font-size:0.7rem;">APPROVED PROPOSALS</span>
                    <h3 class="fw-bold text-success mb-0"><?= $approvedCount ?></h3>
                </div>
            </div>
        </div>

        <!-- Proposals List Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-check text-primary me-2"></i>Submitted Proposal Submissions</h5>

                <!-- Search & Advanced Filters Bar -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="text" id="proposalSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search applicant, title, ID..." style="width: 200px;">
                    
                    <!-- Filter 1: Proposal Type -->
                    <select id="typeFilter" class="form-select form-select-sm rounded-pill px-3" style="width: 150px;">
                        <option value="all">All Types</option>
                        <option value="new_club">New Club</option>
                        <option value="new_event">Campus Event</option>
                    </select>

                    <!-- Filter 2: Applicant Affiliation (College Student vs External Outsider) -->
                    <select id="affiliationFilter" class="form-select form-select-sm rounded-pill px-3" style="width: 160px;">
                        <option value="all">All Applicants</option>
                        <option value="student">UIT College Students</option>
                        <option value="outsider">External / Outsiders</option>
                    </select>

                    <!-- Filter 3: Status -->
                    <select id="statusFilter" class="form-select form-select-sm rounded-pill px-3" style="width: 130px;">
                        <option value="all">All Status</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <?php if (empty($proposals)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary"></i>
                    No proposals submitted yet. Submissions from <code>contact.html#proposal</code> will automatically land here.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="proposalsTable" style="font-size:0.85rem;">
                        <thead class="table-light">
                            <tr class="small text-secondary">
                                <th>TYPE & TITLE</th>
                                <th>APPLICANT / VERIFICATION</th>
                                <th>ACADEMIC & ID DETAILS</th>
                                <th>SUBMITTED DATE</th>
                                <th>STATUS</th>
                                <th class="text-end">DEAN ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proposals as $prop): ?>
                                <tr data-title="<?= e($prop['proposed_title']) ?>" 
                                    data-applicant="<?= e($prop['applicant_name']) ?>" 
                                    data-status="<?= e($prop['status']) ?>" 
                                    data-type="<?= e($prop['proposal_type']) ?>"
                                    data-student="<?= !empty($prop['is_uit_student']) ? 'student' : 'outsider' ?>"
                                    data-id="<?= e($prop['student_id_number']) ?>">
                                    <td>
                                        <div class="mb-1">
                                            <?php if ($prop['proposal_type'] === 'new_club'): ?>
                                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1" style="font-size:0.68rem;"><i class="bi bi-trophy-fill me-1"></i>New Club</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1" style="font-size:0.68rem;"><i class="bi bi-calendar-event-fill me-1"></i>New Event</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-bold text-dark font-monospace" style="font-size:0.9rem;"><?= e($prop['proposed_title']) ?></div>
                                        <?php if ($prop['faculty_mentor']): ?>
                                            <div class="text-secondary small" style="font-size:0.72rem;"><i class="bi bi-person-workspace me-1"></i>Mentor: <?= e($prop['faculty_mentor']) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark"><?= e($prop['applicant_name']) ?></div>
                                        <div class="text-muted small" style="font-size:0.73rem;"><i class="bi bi-envelope me-1"></i><?= e($prop['applicant_email']) ?></div>
                                        <?php if ($prop['applicant_phone']): ?>
                                            <div class="text-muted small" style="font-size:0.73rem;"><i class="bi bi-telephone me-1"></i><?= e($prop['applicant_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($prop['is_uit_student'])): ?>
                                            <span class="badge student-badge rounded-pill px-2.5 py-1 mb-1" style="font-size:0.68rem;">
                                                <i class="bi bi-mortarboard-fill me-1"></i> Registered UIT Student
                                            </span>
                                            <div class="fw-semibold text-dark" style="font-size:0.78rem;">
                                                ID: <code class="text-purple"><?= e($prop['student_id_number'] ?: 'N/A') ?></code>
                                            </div>
                                            <div class="text-secondary" style="font-size:0.73rem;">
                                                <?= e($prop['department_branch']) ?>
                                            </div>
                                            <div class="text-muted" style="font-size:0.7rem;">
                                                <?= e($prop['academic_year']) ?> (<?= e($prop['current_semester']) ?>)
                                            </div>
                                            <?php if (!empty($prop['student_id_photo'])): ?>
                                                <button type="button" class="btn btn-xs btn-outline-purple rounded-pill px-2.5 py-0.5 mt-1 small fw-bold" style="font-size:0.68rem; color:#7c3aed; border-color:#c084fc;"
                                                        data-bs-toggle="modal" data-bs-target="#idModal_<?= e($prop['id']) ?>">
                                                    <i class="bi bi-card-image me-1"></i> Inspect ID Card
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size:0.68rem;">
                                                <i class="bi bi-person me-1"></i> External / Non-Student
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-secondary small">
                                        <?= date('d M Y', strtotime($prop['created_at'])) ?>
                                        <div class="text-muted" style="font-size:0.7rem;"><?= date('h:i A', strtotime($prop['created_at'])) ?></div>
                                    </td>

                                    <td>
                                        <?php if ($prop['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                                        <?php elseif ($prop['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold"><i class="bi bi-hourglass-split me-1"></i>Pending Review</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <!-- Detail View Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-light rounded-circle shadow-xs" data-bs-toggle="modal" data-bs-target="#viewModal_<?= e($prop['id']) ?>" title="View Full Details">
                                                <i class="bi bi-eye text-primary"></i>
                                            </button>

                                            <!-- Approve Button -->
                                            <?php if ($prop['status'] !== 'approved'): ?>
                                                <form action="proposals.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action_status" value="1">
                                                    <input type="hidden" name="proposal_id" value="<?= e($prop['id']) ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 fw-bold shadow-xs" style="font-size:0.72rem;" onclick="return confirm('Approve this proposal?')">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Reject Button -->
                                            <?php if ($prop['status'] !== 'rejected'): ?>
                                                <form action="proposals.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action_status" value="1">
                                                    <input type="hidden" name="proposal_id" value="<?= e($prop['id']) ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 fw-bold" style="font-size:0.72rem;" onclick="return confirm('Reject this proposal?')">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Delete Button -->
                                            <a href="proposals.php?delete_id=<?= e($prop['id']) ?>" class="btn btn-sm btn-light rounded-circle text-danger shadow-xs" onclick="return confirm('Permanently delete this proposal record?')" title="Delete Record">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal: View Full Details -->
                                <div class="modal fade" id="viewModal_<?= e($prop['id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content rounded-4 border-0 shadow-lg">
                                            <div class="modal-header bg-dark text-white rounded-top-4 p-4" style="background:#0f172a !important;">
                                                <div>
                                                    <span class="badge bg-purple text-white rounded-pill px-3 py-1 small text-uppercase mb-1" style="background:#7c3aed;">PROPOSAL DOSSIER</span>
                                                    <h5 class="fw-bold text-white mb-0"><?= e($prop['proposed_title']) ?></h5>
                                                </div>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 bg-light">
                                                <div class="card p-4 border-0 shadow-xs rounded-4 bg-white mb-3">
                                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-vcard text-primary me-2"></i>Applicant Details</h6>
                                                    <div class="row g-3 small">
                                                        <div class="col-md-6"><strong>Applicant Name:</strong> <?= e($prop['applicant_name']) ?></div>
                                                        <div class="col-md-6"><strong>Email:</strong> <?= e($prop['applicant_email']) ?></div>
                                                        <div class="col-md-6"><strong>Phone:</strong> <?= e($prop['applicant_phone'] ?: 'N/A') ?></div>
                                                        <div class="col-md-6"><strong>Faculty Mentor:</strong> <?= e($prop['faculty_mentor'] ?: 'None Specified') ?></div>
                                                    </div>
                                                </div>

                                                <?php if (!empty($prop['is_uit_student'])): ?>
                                                <div class="card p-4 border-0 shadow-xs rounded-4 mb-3" style="background:#faf5ff; border:1px solid #c084fc !important;">
                                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="color:#6b21a8;"><i class="bi bi-mortarboard-fill me-2"></i>Verified UIT Student Credentials</h6>
                                                    <div class="row g-3 small">
                                                        <div class="col-md-6"><strong>College ID Number:</strong> <code><?= e($prop['student_id_number']) ?></code></div>
                                                        <div class="col-md-6"><strong>Department / Branch:</strong> <?= e($prop['department_branch']) ?></div>
                                                        <div class="col-md-6"><strong>Academic Year:</strong> <?= e($prop['academic_year']) ?></div>
                                                        <div class="col-md-6"><strong>Current Semester:</strong> <?= e($prop['current_semester']) ?></div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <div class="card p-4 border-0 shadow-xs rounded-4 bg-white mb-3">
                                                    <h6 class="fw-bold text-dark mb-2 border-bottom pb-2"><i class="bi bi-card-text text-primary me-2"></i>Objectives, Vision & Scope</h6>
                                                    <p class="small text-secondary mb-0" style="white-space: pre-wrap; line-height: 1.6;"><?= e($prop['objective']) ?></p>
                                                </div>

                                                <?php if (!empty($prop['proposal_pdf'])): ?>
                                                    <div class="card p-3 border-0 shadow-xs rounded-4 bg-white d-flex flex-row align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>
                                                            <div>
                                                                <div class="fw-bold text-dark small mb-0">Official Proposal PDF / Attachment</div>
                                                                <span class="text-muted font-monospace" style="font-size:0.7rem;">Document Presentation</span>
                                                            </div>
                                                        </div>
                                                        <a href="../../<?= e($prop['proposal_pdf']) ?>" target="_blank" class="btn btn-sm btn-danger rounded-pill px-3.5 fw-bold">
                                                            <i class="bi bi-eye-fill me-1"></i> View / Download Document
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer bg-white border-top p-3 d-flex align-items-center justify-content-between">
                                                <span class="small text-muted">Submitted on <?= date('d M Y, h:i A', strtotime($prop['created_at'])) ?></span>
                                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close Dossier</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal: View ID Card Image -->
                                <?php if (!empty($prop['student_id_photo'])): ?>
                                <div class="modal fade" id="idModal_<?= e($prop['id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow-lg">
                                            <div class="modal-header bg-purple text-white p-3.5" style="background:#7c3aed !important;">
                                                <h6 class="fw-bold text-white mb-0"><i class="bi bi-card-image me-2"></i>Student ID Card Photo – <?= e($prop['applicant_name']) ?></h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center p-4 bg-light">
                                                <img src="../../<?= e($prop['student_id_photo']) ?>" class="img-fluid rounded-4 shadow-sm border" style="max-height: 420px; object-fit: contain;" alt="Student ID Card">
                                                <div class="mt-3 small text-muted font-monospace">ID Number: <?= e($prop['student_id_number']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('proposalSearchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const affiliationFilter = document.getElementById('affiliationFilter');
    const rows = document.querySelectorAll('#proposalsTable tbody tr');

    function filterTable() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const status = statusFilter ? statusFilter.value : 'all';
        const type = typeFilter ? typeFilter.value : 'all';
        const affiliation = affiliationFilter ? affiliationFilter.value : 'all';

        rows.forEach(row => {
            const title = (row.dataset.title || '').toLowerCase();
            const applicant = (row.dataset.applicant || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowType = row.dataset.type || '';
            const rowStudent = row.dataset.student || '';
            const idNum = (row.dataset.id || '').toLowerCase();

            const matchesQuery = !query || title.includes(query) || applicant.includes(query) || idNum.includes(query);
            const matchesStatus = status === 'all' || rowStatus === status;
            const matchesType = type === 'all' || rowType === type;
            const matchesAffiliation = affiliation === 'all' || rowStudent === affiliation;

            row.style.display = (matchesQuery && matchesStatus && matchesType && matchesAffiliation) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (typeFilter) typeFilter.addEventListener('change', filterTable);
    if (affiliationFilter) affiliationFilter.addEventListener('change', filterTable);
    filterTable();
});
</script>
</body>
</html>
