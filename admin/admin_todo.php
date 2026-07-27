<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\AccessControl;

global $_database, $languageService;

if (isset($languageService) && method_exists($languageService, 'readModule')) {
    $languageService->readModule('todo');
}

// Admin-Zugriff
AccessControl::checkAdminAccess('todo');

$userID = (int)($_SESSION['userID'] ?? 0);
if ($userID <= 0) {
    nx_alert('danger', 'alert_invalid_user', false);
    return;
}

$action = $_GET['action'] ?? '';

// POST: INSERT / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isEdit = isset($_POST['edit_id']);

    $task        = trim($_POST['task'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $due_date    = $_POST['due_date'] ?: null;
    $progress    = (int)($_POST['progress'] ?? 0);
    $done        = isset($_POST['done']) ? 1 : 0;

    $assigned_to = (int)($_POST['assigned_to'] ?? 0);


    if ($task === '') {
        nx_alert('danger', 'alert_missing_required', false);
        return;
    }

    if ($isEdit) {

        $edit_id     = (int)$_POST['edit_id'];

        if ($assigned_to > 0) {
            $stmt = $_database->prepare("
                UPDATE plugins_todo
                SET
                    task = ?,
                    description = ?,
                    priority = ?,
                    due_date = ?,
                    progress = ?,
                    done = ?,
                    assigned_to = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssiiiii",
                $task,
                $description,
                $priority,
                $due_date,
                $progress,
                $done,
                $assigned_to,
                $userID,
                $edit_id
            );
        } else {
            $stmt = $_database->prepare("
                UPDATE plugins_todo
                SET
                    task = ?,
                    description = ?,
                    priority = ?,
                    due_date = ?,
                    progress = ?,
                    done = ?,
                    assigned_to = NULL,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssiiii",
                $task,
                $description,
                $priority,
                $due_date,
                $progress,
                $done,
                $userID,
                $edit_id
            );
        }

        if (!$stmt->execute()) {
            nx_alert('danger', 'alert_db_error', false);
            return;
        }

        $stmt->close();

    }
 else {

        if ($assigned_to > 0) {
            $stmt = $_database->prepare("
                INSERT INTO plugins_todo
                (userID, assigned_to, task, description, priority, due_date, progress, done, created_at, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)
            ");

            $stmt->bind_param(
                "iissssii",
                $userID,
                $assigned_to,
                $task,
                $description,
                $priority,
                $due_date,
                $progress,
                $userID
            );
        } else {
            $stmt = $_database->prepare("
                INSERT INTO plugins_todo
                (userID, assigned_to, task, description, priority, due_date, progress, done, created_at, updated_at, updated_by)
                VALUES (?, NULL, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)
            ");

            $stmt->bind_param(
                "issssii",
                $userID,
                $task,
                $description,
                $priority,
                $due_date,
                $progress,
                $userID
            );
        }
        
        $stmt->execute();
        $stmt->close();
    }

    nx_redirect('admincenter.php?site=admin_todo', 'success', 'alert_saved', false);
}

// DONE / DELETE
if (isset($_GET['done_id'])) {
    $id = (int)$_GET['done_id'];

    $_database->query("UPDATE plugins_todo SET done=1, updated_by=$userID, updated_at=NOW() WHERE id=$id");

    nx_audit_update('admin_todo', (string)$id, true, (string)$id, 'admincenter.php?site=admin_todo');
    nx_redirect('admincenter.php?site=admin_todo', 'success', 'alert_saved', false);
}

if (isset($_GET['del_id'])) {
    $id = (int)$_GET['del_id'];

    $_database->query("DELETE FROM plugins_todo WHERE id=$id");

    nx_audit_delete('admin_todo', (string)$id, (string)$id, 'admincenter.php?site=admin_todo');
    nx_redirect('admincenter.php?site=admin_todo', 'success', 'alert_deleted', false);
}

// EDIT-TODO LADEN
$todo_edit = null;
if ($action === 'edit' && isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];

    $stmtEdit = $_database->prepare("
        SELECT
            t.*,
            ua.username AS assigned_name
        FROM plugins_todo t
        LEFT JOIN users ua ON ua.userID = t.assigned_to
        WHERE t.id = ?
        LIMIT 1
    ");
    $stmtEdit->bind_param("i", $id);
    $stmtEdit->execute();
    $todo_edit = $stmtEdit->get_result()->fetch_assoc();
    $stmtEdit->close();

    if ($todo_edit && !empty($todo_edit['due_date'])) {
        $todo_edit['due_date'] = date('Y-m-d', strtotime((string)$todo_edit['due_date']));
    }
}

// TODOS + USER
$result = $_database->query("
        SELECT
        t.*,
        u.username   AS creator_name,
        ub.username  AS updated_by_name,
        ua.username  AS assigned_name
    FROM plugins_todo t
    LEFT JOIN users u  ON u.userID  = t.userID
    LEFT JOIN users ub ON ub.userID = t.updated_by
    LEFT JOIN users ua ON ua.userID = t.assigned_to
    ORDER BY t.updated_at DESC
");

$todos = [];
while ($row = $result->fetch_assoc()) {
    $todos[] = $row;
}

// Gruppieren
$openTodos = $fullTodos = $doneTodos = [];

foreach ($todos as $t) {
    if ((int)$t['done'] === 1) {
        $doneTodos[] = $t;
    } elseif ((int)$t['progress'] === 100) {
        $fullTodos[] = $t;
    } else {
        $openTodos[] = $t;
    }
}

$sql = "
    SELECT u.userID, u.username
    FROM users u
    ORDER BY u.username
";

$stmt = $_database->prepare($sql);
$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($u = $res->fetch_assoc()) {
    $users[] = $u;
}
$stmt->close();

if ($todo_edit && !empty($todo_edit['assigned_to'])) {
    $assignedUserId = (int)$todo_edit['assigned_to'];
    $assignedExists = false;

    foreach ($users as $u) {
        if ((int)$u['userID'] === $assignedUserId) {
            $assignedExists = true;
            break;
        }
    }

    if (!$assignedExists) {
        $stmtAssigned = $_database->prepare("SELECT userID, username FROM users WHERE userID = ? LIMIT 1");
        $stmtAssigned->bind_param("i", $assignedUserId);
        $stmtAssigned->execute();
        $assignedRes = $stmtAssigned->get_result();

        if ($assignedUser = $assignedRes->fetch_assoc()) {
            $users[] = $assignedUser;
        }

        $stmtAssigned->close();
    }
}

?>

<?php if ($action === 'add' || $action === 'edit'): ?>

<!-- FORMULAR -->
<div class="card shadow-sm mt-4">
    <div class="card-header">
        <div class="card-title">
            <i class="bi bi-check-all"></i> <span><?= $languageService->get('title_todo') ?></span>
            <small class="text-muted"><?= $languageService->get($action === 'add' ? 'add' : 'edit') ?></small>
        </div>
    </div>

    <div class="card-body">
        <form method="post">
            <?php if ($todo_edit): ?>
                <input type="hidden" name="edit_id" value="<?= (int)$todo_edit['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">

                <!-- Aufgabe | Priorität -->
                <div class="col-12 col-md-6">
                <label class="form-label"><?= $languageService->get('label_task') ?></label>
                <input class="form-control" name="task" value="<?= htmlspecialchars($todo_edit['task'] ?? '') ?>" placeholder="<?= $languageService->get('placeholder_task') ?>" required>
                </div>

                <div class="col-12 col-md-6">
                <label class="form-label"><?= $languageService->get('label_priority') ?></label>
                <select name="priority" class="form-select">
                    <?php foreach (['low', 'medium', 'high'] as $p): ?>
                    <option value="<?= $p ?>" <?= (($todo_edit['priority'] ?? 'medium') === $p) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($languageService->get('select_prio_' . $p), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                </div>

                <!-- Verantwortlich | Terminiert -->
                <div class="col-12 col-md-6">
                <label class="form-label"><?= $languageService->get('label_responsible') ?></label>
                <select name="assigned_to" class="form-select">
                    <option value=""><?= $languageService->get('option_not_assigned') ?></option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['userID'] ?>" <?= ((int)($todo_edit['assigned_to'] ?? 0) === (int)$u['userID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                </div>

                <div class="col-12 col-md-6">
                <label class="form-label"><?= $languageService->get('label_date') ?></label>
                <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($todo_edit['due_date'] ?? '') ?>">
                </div>

                <!-- Beschreibung -->
                <div class="col-12">
                <label class="form-label"><?= $languageService->get('description') ?></label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($todo_edit['description'] ?? '') ?></textarea>
                </div>

                <!-- Fortschritt -->
                <div class="col-12">
                <label class="form-label d-flex justify-content-between align-items-center">
                    <span><?= $languageService->get('label_progress') ?></span>
                    <span class="badge bg-secondary"><span id="p"><?= (int)($todo_edit['progress'] ?? 0) ?></span>%</span>
                </label>
                <input type="range" name="progress" class="form-range" min="0" max="100" value="<?= (int)($todo_edit['progress'] ?? 0) ?>" oninput="p.textContent=this.value">
                </div>

                <?php if ($todo_edit): ?>
                <div class="col-12">
                    <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="done" <?= $todo_edit['done'] ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= $languageService->get('label_finished') ?></label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="col-12 mt-2">
                <button class="btn btn-primary"><?= $languageService->get('save') ?></button>
                </div>

            </div>
        </form>
    </div>
</div>

<?php return; endif; ?>

<!-- LISTE -->

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <div class="card-title">
            <i class="bi bi-check-all"></i> <span><?= $languageService->get('title_todo') ?></span>
            <small class="text-muted"><?= $languageService->get('overview') ?></small>
        </div>
        <a href="admincenter.php?site=admin_todo&action=add"
        class="btn btn-secondary">
            <?= $languageService->get('add') ?>
        </a>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-5">
        <?php foreach ($openTodos as $todo): include __DIR__.'/../partials/todo_card_admin.php'; endforeach; ?>
        <?php if (!$openTodos): ?><div class="text-muted"><?= $languageService->get('info_no_tasks') ?></div><?php endif; ?>
        </div>

        <div class="accordion mt-4" id="todoAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#doneTodos">
                        <?= $languageService->get('accordion_finished_tasks') ?>
                        <span class="badge bg-secondary ms-2"><?= count($fullTodos)+count($doneTodos) ?></span>
                    </button>
                </h2>
                <div id="doneTodos" class="accordion-collapse collapse">
                    <div class="accordion-body">

                        <?php if (!empty($fullTodos)): ?>
                            <h6>100 %</h6>
                            <div class="row g-3 mb-5">
                                <?php foreach ($fullTodos as $todo): include __DIR__.'/../partials/todo_card_admin.php'; endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doneTodos)): ?>
                            <h6><?= $languageService->get('label_finished') ?></h6>
                            <div class="row g-3">
                                <?php foreach ($doneTodos as $todo): include __DIR__.'/../partials/todo_card_admin.php'; endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($fullTodos) && empty($doneTodos)): ?>
                            <div class="text-muted">
                                <?= $languageService->get('info_no_finished_tasks') ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function toggleTodoMessage(el){
    const t = el.querySelector('.todo-text');
    el.classList.toggle('open');
    t.innerHTML = el.classList.contains('open')
        ? el.dataset.full.replace(/\n/g,'<br>')
        : el.dataset.short;
}
</script>

<style>
.todo-message{cursor:pointer;background:color-mix(in srgb, var(--nx-color-card-bg, var(--bs-card-bg, var(--bs-body-bg, #fff))) 90%, var(--bs-primary, #0d6efd) 10%)}
.todo-message.open{background:color-mix(in srgb, var(--nx-color-card-bg, var(--bs-card-bg, var(--bs-body-bg, #fff))) 82%, var(--bs-primary, #0d6efd) 18%)}
</style>
