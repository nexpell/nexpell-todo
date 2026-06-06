<?php
declare(strict_types=1);

/* ==========================================================
   SESSION & GRUNDLAGEN
========================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\AccessControl;

global $_database,$languageService;

$lang = $languageService->detectLanguage();
$languageService->readPluginModule('todo');

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

    // Header-Daten
    $data_array = [
        'class'    => $class,
        'title' => $languageService->get('title'),
        'subtitle' => 'Todo'
    ];
    
    echo $tpl->loadTemplate("todo", "head", $data_array, 'plugin');





/* ==========================================================
   TODOS + USER
========================================================== */
$result = $_database->query("
    SELECT
        t.*,
        uc.username AS creator_name,        -- Ersteller
        uu.username AS updated_by_name,     -- letzter Bearbeiter
        ua.username AS assigned_name        -- Verantwortlich
    FROM plugins_todo t
    LEFT JOIN users uc ON uc.userID = t.userID
    LEFT JOIN users uu ON uu.userID = t.updated_by
    LEFT JOIN users ua ON ua.userID = t.assigned_to
    ORDER BY t.updated_at DESC
");

$todos = [];
while ($row = $result->fetch_assoc()) {
    $todos[] = $row;
}


/* Gruppieren */
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
?>



<!-- ==========================================================
     LISTE
========================================================== -->


<div class="row g-3">
<?php foreach ($openTodos as $todo): include __DIR__.'/partials/todo_card.php'; endforeach; ?>
<?php if (!$openTodos): ?><div class="text-muted">Keine offenen Aufgaben</div><?php endif; ?>
</div>

<div class="accordion mt-4" id="todoAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#doneTodos">
                Abgeschlossene Aufgaben
                <span class="badge bg-secondary ms-2"><?= count($fullTodos)+count($doneTodos) ?></span>
            </button>
        </h2>
        <div id="doneTodos" class="accordion-collapse collapse">
            <div class="accordion-body">

                <h6>100 %</h6>
                <div class="row g-3 mb-3">
                    <?php foreach ($fullTodos as $todo): include __DIR__.'/partials/todo_card.php'; endforeach; ?>
                </div>

                <h6>Erledigt</h6>
                <div class="row g-3">
                    <?php foreach ($doneTodos as $todo): include __DIR__.'/partials/todo_card.php'; endforeach; ?>
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
