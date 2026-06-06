<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../system/config.inc.php';
global $_database;

$filter = $_GET['filter'] ?? 'all';

$where = match ($filter) {
    'open' => 'WHERE t.done=0 AND t.progress<100',
    'full' => 'WHERE t.done=0 AND t.progress=100',
    'done' => 'WHERE t.done=1',
    default => ''
};

$res = $_database->query("
    SELECT t.*, u.username
    FROM plugins_todo t
    LEFT JOIN users u ON u.userID = t.userID
    $where
    ORDER BY
        CASE
            WHEN t.done=0 AND t.progress<100 THEN 1
            WHEN t.done=0 AND t.progress=100 THEN 2
            WHEN t.done=1 THEN 3
        END,
        t.created_at DESC
");

echo '<div class="row g-4">';

while ($todo = $res->fetch_assoc()) {

    $progress = (int)$todo['progress'];
    $progressColor = $progress >= 80 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger');

    $task = mb_strimwidth($todo['task'], 0, 40, '…', 'UTF-8');
    $desc = mb_strimwidth($todo['description'] ?? '', 0, 120, '…', 'UTF-8');
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100 shadow-sm <?= $todo['done'] ? 'opacity-75' : '' ?>">

            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="badge bg-secondary"><?= htmlspecialchars($todo['priority']) ?></span>
                <?php if ($todo['done']): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> Erledigt
                    </span>
                <?php endif; ?>
            </div>

            <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($task) ?></h6>

                <?php if ($desc): ?>
                    <p class="small text-muted mb-2 todo-desc-fixed">
                        <?= htmlspecialchars($desc) ?>
                    </p>
                <?php endif; ?>

                <div class="small text-muted mb-2">
                    <i class="bi bi-calendar"></i>
                    <?= htmlspecialchars($todo['due_date'] ?: 'Kein Datum') ?><br>
                    <i class="bi bi-person"></i>
                    <?= htmlspecialchars($todo['username']) ?>
                </div>

                <div class="mt-auto">
                    <div class="progress mb-1" style="height:6px">
                        <div class="progress-bar <?= $progressColor ?>" style="width:<?= $progress ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $progress ?>%</small>
                </div>
            </div>
        </div>
    </div>
    <?php
}

echo '</div>';
