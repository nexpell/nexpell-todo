<?php
/**
 * Erwartet:
 * $todo (Array)
 * $languageService
 */

$priorityColor = match ($todo['priority'] ?? 'medium') {
    'high'   => 'danger',
    'low'    => 'success',
    default  => 'warning',
};

$progress = (int)($todo['progress'] ?? 0);
$progressColor = $progress >= 80
    ? 'bg-success'
    : ($progress >= 50 ? 'bg-warning' : 'bg-danger');

/* Beschreibung vorbereiten */
$fullHtml  = trim((string)($todo['description'] ?? ''));
$shortText = mb_strimwidth(strip_tags($fullHtml), 0, 160, '…', 'UTF-8');
?>

<div class="col-xl-6 col-lg-6 col-md-6">
    <div class="card h-100 shadow-sm border-start border-1 border-<?= $priorityColor ?>
        <?= !empty($todo['done']) ? 'opacity-75' : '' ?>">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="badge bg-<?= $priorityColor ?>">
                <?= htmlspecialchars(ucfirst($todo['priority'])) ?>
            </span>

            <?php if (!empty($todo['done'])): ?>
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> Erledigt
                </span>
            <?php endif; ?>
        </div>

        <!-- BODY -->
        <div class="card-body d-flex flex-column">

            <!-- Titel -->
            <h6 class="fw-bold mb-2">
                <?= htmlspecialchars($todo['task']) ?>
            </h6>

            <!-- Beschreibung (toggle) -->
            <?php if ($fullHtml !== ''): ?>
                <div class="small mb-2">
                    <div class="todo-message todo-toggle p-2 rounded"
                         data-short="<?= htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8') ?>"
                         data-full="<?= htmlspecialchars($fullHtml, ENT_QUOTES, 'UTF-8') ?>"
                         onclick="toggleTodoMessage(this)">

                        <i class="bi bi-chat-left-text me-1 text-primary"></i>

                        <span class="todo-text">
                            <?= htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span class="float-end text-primary">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </div>

                    <small class="text-primary">Text anzeigen / ausblenden</small>
                </div>
            <?php endif; ?>

            <!-- FOOTER (IMMER UNTEN) -->
            <div class="mt-auto pt-3 border-top">

                <div class="row">
                    <div class="col-5">
                        <ul class="list-unstyled small mb-0">
                            <li>
                                <strong>Verantwortlich:</strong>
                                <?php if (!empty($todo['assigned_name'])): ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-person-check me-1"></i>
                                        <?= htmlspecialchars($todo['assigned_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-person-dash me-1"></i>
                                        Nicht zugewiesen
                                    </span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <strong>Erstellt von:</strong>
                                <?= htmlspecialchars($todo['creator_name'] ?? 'Unbekannt') ?>
                            </li>

                            <li>
                                <strong>Priorität:</strong>
                                <span class="badge bg-<?= $priorityColor ?>">
                                    <?= htmlspecialchars($todo['priority']) ?>
                                </span>
                            </li>
                            <li>
                                <strong>Fällig:</strong>
                                <?= !empty($todo['due_date'])
                                    ? date('d.m.Y', strtotime($todo['due_date']))
                                    : '<span class="text-muted fst-italic">Nicht gesetzt</span>' ?>
                            </li>
                            <li>
                                <strong>Erledigt:</strong>
                                <?= !empty($todo['done'])
                                    ? '<span class="badge bg-success">Ja</span>'
                                    : '<span class="badge bg-danger">Nein</span>' ?>
                            </li>
                        </ul>
                    </div>

                    <div class="col-7">
                        <ul class="list-unstyled small mb-0">
                            <li>
                                <strong>Fortschritt:</strong>
                                <div class="progress my-1" style="height:6px;">
                                    <div class="progress-bar <?= $progressColor ?>"
                                         style="width: <?= $progress ?>%"></div>
                                </div>
                                <?= $progress ?>%
                            </li>
                            <li>
                                <strong>Erstellt:</strong>
                                <?= date('d.m.Y H:i', strtotime($todo['created_at'])) ?>
                            </li>
                            <li>
                                <strong>Zuletzt bearbeitet:</strong>
                                <?= date("d.m.Y H:i", strtotime($todo['updated_at'])) ?>
                                
                                <small class="text-muted">
                                    von <?= htmlspecialchars($todo['updated_by_name'] ?? $todo['creator_name']) ?>
                                </small>
                            </li>
                        </ul>
                    </div>
                </div>

                

            </div>
        </div>
    </div>
</div>
