<?php
    require_once 'includes/db.php';

    /* ==========================================
       페이지네이션 설정
    ========================================== */
    $perPage = 12; 
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $perPage;

    // 전체 개수
    $totalResult = $conn->query("SELECT COUNT(*) AS total FROM todos");
    $totalRows = $totalResult->fetch_assoc()['total'];
    $totalPages = max(1, ceil($totalRows / $perPage));

    /* ==========================================
       To-do 추가
    ========================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
        $task = $conn->real_escape_string($_POST['task']);
        $due = $_POST['due_date'] ?: null;
        $priority = $_POST['priority'] ?? '보통';
        $category = $conn->real_escape_string($_POST['category'] ?? '개인');
        $conn->query("INSERT INTO todos (task, due_date, priority, category) VALUES ('$task', '$due', '$priority', '$category')");
        header("Location: todos.php?page=$page");
        exit;
    }

    /* ==========================================
       완료 처리
    ========================================== */
    if (isset($_GET['done'])) {
        $id = (int)$_GET['done'];
        $result = $conn->query("SELECT done FROM todos WHERE id = $id");
        $current = $result->fetch_assoc();
        $newStatus = $current['done'] ? 0 : 1;
        $conn->query("UPDATE todos SET done = $newStatus WHERE id = $id");
        header("Location: todos.php?page=$page");
        exit;
    }

    /* ==========================================
       삭제 처리
    ========================================== */
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $conn->query("DELETE FROM todos WHERE id = $id");
        header("Location: todos.php?page=$page");
        exit;
    }

    /* ==========================================
       현재 페이지 todo 로드
    ========================================== */
    $todos = $conn->query("
        SELECT *
        FROM todos
        ORDER BY done ASC, due_date ASC, id DESC
        LIMIT $perPage OFFSET $offset
    ");
?>

<style>
    * {
        box-sizing: border-box;
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 220px;
        padding: 20px;
        border-right: 1px solid #2a2a2a;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        height: 100vh;
    }

    .sidebar h2 {
        font-size: 16px;
        margin-bottom: 15px;
    }

    .sidebar ul {
        list-style: none;
    }

    .sidebar li {
        margin-bottom: 10px;
    }

    .sidebar a {
        text-decoration: none;
        display: block;
        padding: 8px 12px;
        border-radius: 6px;
        transition: 0.2s;
    }

    .sidebar a:hover {
        opacity: 0.8;
    }

    .sidebar a.active {
        font-weight: bold;
    }

    .main {
        flex: 1;
        padding: 30px;
        margin-left: 220px;   /* 고정 사이드바 폭만큼 오른쪽으로 밀기 */
        min-height: 100vh;
    }

    h1 {
        font-size: 22px;
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 25px;
    }

    input[type="text"],
    input[type="date"],
    select {
        padding: 10px;
        border: none;
        border-radius: 6px;
        flex: 1;
        min-width: 150px;
    }

    button {
        padding: 10px 16px;
        border-radius: 6px;
        cursor: pointer;
        border: none;
    }

    /* ===============================
       스크롤 추가 (리스트 안에서만)
    =============================== */
    .task-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        height: 550px;       /* 고정 높이 → 내부 스크롤 */
        overflow-y: auto;
        padding-right: 10px;
    }

    .task-item {
        border-radius: 6px;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .done-task {
        display: none;
    }

    .task-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-block;
        cursor: pointer;
        border: 2px solid;
    }

    .checkbox.done {
        background: currentColor;
    }

    .task-text {
        font-size: 15px;
    }

    .task-text.done {
        text-decoration: line-through;
        opacity: 0.5;
    }

    .task-right {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .priority, .category, .due {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .priority[data-value="높음"] {
        background-color: #ff5c5c;
        color: white;
    }

    .priority[data-value="보통"] {
        background-color: #ffa500;
        color: white;
    }

    .priority[data-value="낮음"] {
        background-color: #4caf50;
        color: white;
    }

    .delete-btn {
        font-size: 18px;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: 0.2s;
    }

    .mode-toggle {
        position: absolute;
        bottom: 20px;
        left: 20px;
    }

    .mode-toggle button {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        border: 1px solid;
    }

    #toggleCompletedBtn {
        margin-bottom: 15px;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        cursor: pointer;
        background: none;
        font-size: 14px;
    }

    body.dark-mode #toggleCompletedBtn {
        color: #fff;
        border-color: #666;
    }

    body.light-mode #toggleCompletedBtn {
        color: #111;
        border-color: #ccc;
    }

    /* ===============================
       다크/화이트 모드
    =============================== */
    body.dark-mode {
        background: #1e1e1e;
        color: #f1f1f1;
    }

    body.dark-mode .sidebar {
        background: #121212;
        border-color: #2a2a2a;
    }

    body.dark-mode .sidebar a {
        color: #ddd;
    }

    body.dark-mode .sidebar a.active {
        background: #333;
    }

    body.dark-mode .main {
        background: #1e1e1e;
    }

    body.dark-mode input,
    body.dark-mode select {
        background: #2a2a2a;
        color: #fff;
    }

    body.dark-mode button {
        background: #db4c3f;
        color: #fff;
    }

    body.dark-mode .task-item {
        background: #2a2a2a;
    }

    body.dark-mode .checkbox {
        border-color: #888;
        color: #db4c3f;
    }

    body.dark-mode .category {
        background: #666;
        color: #fff;
    }

    body.dark-mode .due {
        background: #555;
        color: #ddd;
    }

    body.dark-mode .delete-btn:hover {
        color: #f55;
    }

    body.light-mode {
        background: #f9f9f9;
        color: #111;
    }

    body.light-mode .sidebar {
        background: #fff;
        border-color: #ccc;
    }

    body.light-mode .sidebar a {
        color: #333;
    }

    body.light-mode .sidebar a.active {
        background: #eee;
    }

    body.light-mode .main {
        background: #fff;
    }

    body.light-mode input,
    body.light-mode select {
        background: #f1f1f1;
        color: #000;
    }

    body.light-mode button {
        background: #0066ff;
        color: #fff;
    }

    body.light-mode .task-item {
        background: #f5f5f5;
    }

    body.light-mode .checkbox {
        border-color: #666;
        color: #0066ff;
    }

    body.light-mode .category {
        background: #ddd;
        color: #333;
    }

    body.light-mode .due {
        background: #eee;
        color: #222;
    }

    body.light-mode .delete-btn:hover {
        color: #900;
    }

    /* ===============================
       페이지네이션
    =============================== */
    .pagination {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin: 20px 0;
        flex-wrap: wrap;
    }

    .page-btn {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        border: 1px solid;
        font-size: 13px;
    }

    body.light-mode .page-btn {
        background: white;
        color: #111;
        border-color: #ccc;
    }

    body.light-mode .page-btn.active {
        background: #0066ff;
        color: white;
    }

    body.dark-mode .page-btn {
        background: #1e1e1e;
        color: #fff;
        border-color: #666;
    }

    body.dark-mode .page-btn.active {
        background: #db4c3f;
        color: white;
    }
</style>

<div class="sidebar">
    <h2>내 페이지</h2>
    <ul>
        <li><a href="index.php">홈 페이지</a></li>
        <li><a href="todos.php" class="active">To-do</a></li>
        <li><a href="lectures.php">수업/정리</a></li>
    </ul>
    <div class="mode-toggle">
        <button onclick="toggleMode()">모드</button>
    </div>
</div>

<div class="main">
    <h1>✅ To-do 리스트</h1>

    <form method="post">
        <input type="text" name="task" placeholder="내용을 입력하세요" required>
        <input type="date" name="due_date">
        <select name="priority">
            <option value="낮음">낮음</option>
            <option value="보통" selected>보통</option>
            <option value="높음">높음</option>
        </select>
        <select name="category">
            <option value="학교">학교</option>
            <option value="회사">회사</option>
            <option value="개인" selected>개인</option>
            <option value="운동">운동</option>
            <option value="여행">여행</option>
        </select>
        <button type="submit">추가</button>
    </form>

    <button id="toggleCompletedBtn" onclick="toggleCompleted()">완료된 항목 숨기기</button>

    <div class="task-list">
        <?php while ($row = $todos->fetch_assoc()): ?>
        <div class="task-item <?= $row['done'] ? 'done-task' : ''; ?>">
            <div class="task-left">
                <a href="?done=<?= $row['id']; ?>&page=<?= $page ?>">
                    <span class="checkbox <?= $row['done'] ? 'done' : ''; ?>"></span>
                </a>
                <span class="task-text <?= $row['done'] ? 'done' : ''; ?>">
                    <?= htmlspecialchars($row['task']); ?>
                </span>
            </div>
            <div class="task-right">
                <span class="category"><?= $row['category']; ?></span>
                <span class="priority" data-value="<?= $row['priority']; ?>">
                    <?= $row['priority']; ?>
                </span>
                <span class="due">
                    <?= $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '' ?>
                </span>
                <a class="delete-btn" href="?delete=<?= $row['id']; ?>&page=<?= $page ?>" onclick="return confirm('삭제할까요?')">🗑</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- ===============================
         페이지네이션 UI
    =============================== -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="page-btn" href="?page=<?= $page - 1 ?>">◀ Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-btn <?= ($i == $page ? 'active' : '') ?>" href="?page=<?= $i ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="page-btn" href="?page=<?= $page + 1 ?>">Next ▶</a>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleMode() {
    const body = document.body;
    const newMode = body.classList.contains('light-mode') ? 'dark' : 'light';
    localStorage.setItem('mode', newMode);
    applyMode();
}

function applyMode() {
    const mode = localStorage.getItem('mode') || 'dark';
    document.body.classList.remove('light-mode', 'dark-mode');
    document.body.classList.add(mode + '-mode');
}

function toggleCompleted() {
    const tasks = document.querySelectorAll('.done-task');
    const btn = document.getElementById('toggleCompletedBtn');
    const hidden = tasks.length && tasks[0].style.display === 'none';

    tasks.forEach(task => {
        task.style.display = hidden ? 'flex' : 'none';
    });

    btn.textContent = hidden ? '완료된 항목 숨기기' : '완료된 항목 보기';
}

applyMode();
</script>
