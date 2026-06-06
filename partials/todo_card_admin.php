<?php
$priorityColor = match ($todo['priority'] ?? 'medium') {
    'high'   => 'danger',
    'low'    => 'success',
    default  => 'warning',
};

$progress = (int)($todo['progress'] ?? 0);
$progressColor = $progress >= 80
    ? 'bg-success'
    : ($progress >= 50 ? 'bg-warning' : 'bg-danger');

$fullHtml = trim((string)($todo['description'] ?? ''));
$shortText = mb_strimwidth(strip_tags($fullHtml), 0, 160, '...', 'UTF-8');
?>

<div class="col-xl-4 col-lg-4 col-md-4">
    <div class="card h-100 shadow-sm border-start border-2 border-<?= $priorityColor ?><?= !empty($todo['done']) ? ' opacity-75' : '' ?>">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="badge bg-<?= $priorityColor ?>">
                <?= htmlspecialchars(ucfirst((string)($todo['priority'] ?? 'medium')), ENT_QUOTES, 'UTF-8') ?>
            </span>

            <?php if (!empty($todo['done'])): ?>
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> <?= $languageService->get('label_finished') ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="card-body d-flex flex-column">
            <h6 class="fw-bold mb-2">
                <?= htmlspecialchars((string)($todo['task'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </h6>

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

                    <small class="text-primary"><?= $languageService->get('toggle_text') ?></small>
                </div>
            <?php endif; ?>

            <div class="mt-auto pt-3 border-top">
                <div class="row">
                    <div class="col-5">
                        <ul class="list-unstyled small mb-0">
                            <li>
                                <strong><?= $languageService->get('label_responsible') ?>:</strong>
                                <?php if (!empty($todo['assigned_name'])): ?>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars((string)$todo['assigned_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= $languageService->get('option_not_assigned') ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_created_by') ?>:</strong>
                                <?= htmlspecialchars((string)($todo['creator_name'] ?? $languageService->get('unknown')), ENT_QUOTES, 'UTF-8') ?>
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_priority') ?>:</strong>
                                <span class="badge bg-<?= $priorityColor ?>">
                                    <?= htmlspecialchars((string)($todo['priority'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_due') ?>:</strong>
                                <?=
                                    !empty($todo['due_date'])
                                        ? date('d.m.Y', strtotime((string)$todo['due_date']))
                                        : '<span class="text-muted fst-italic">' . htmlspecialchars($languageService->get('not_set'), ENT_QUOTES, 'UTF-8') . '</span>'
                                ?>
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_finished') ?>:</strong>
                                <?=
                                    !empty($todo['done'])
                                        ? '<span class="badge bg-success">' . htmlspecialchars($languageService->get('yes'), ENT_QUOTES, 'UTF-8') . '</span>'
                                        : '<span class="badge bg-danger">' . htmlspecialchars($languageService->get('no'), ENT_QUOTES, 'UTF-8') . '</span>'
                                ?>
                            </li>
                        </ul>
                    </div>

                    <div class="col-7">
                        <ul class="list-unstyled small mb-0">
                            <li>
                                <strong><?= $languageService->get('label_progress') ?>:</strong>
                                <div class="progress my-1" style="height:6px;">
                                    <div class="progress-bar <?= $progressColor ?>" style="width: <?= $progress ?>%"></div>
                                </div>
                                <?= $progress ?>%
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_created') ?>:</strong>
                                <?= date('d.m.Y H:i', strtotime((string)$todo['created_at'])) ?>
                            </li>
                            <li>
                                <strong><?= $languageService->get('label_updated') ?>:</strong>
                                <?= date('d.m.Y H:i', strtotime((string)$todo['updated_at'])) ?>
                                <small class="text-muted">
                                    <?= $languageService->get('by') ?> <?= htmlspecialchars((string)($todo['updated_by_name'] ?? $todo['creator_name']), ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-1 mt-3">
                    <?php if (empty($todo['done'])): ?>
                        <a href="admincenter.php?site=admin_todo&done_id=<?= (int)$todo['id'] ?>"
                           class="btn btn-success btn-sm"
                           title="<?= $languageService->get('mark_done') ?>">
                            <i class="bi bi-check"></i>
                        </a>
                    <?php endif; ?>

                    <a href="admincenter.php?site=admin_todo&action=edit&edit_id=<?= (int)$todo['id'] ?>"
                       class="btn btn-warning btn-sm"
                       title="<?= $languageService->get('edit') ?>">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <a href="admincenter.php?site=admin_todo&del_id=<?= (int)$todo['id'] ?>"
                       onclick="return confirm('<?= $languageService->get('confirm_delete') ?>')"
                       class="btn btn-danger btn-sm"
                       title="<?= $languageService->get('delete') ?>">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
