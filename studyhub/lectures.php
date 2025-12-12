<?php
require_once 'includes/db.php';

$search = $_GET['search'] ?? '';
$date = $_GET['date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['heading'])) {
    $heading = $conn->real_escape_string($_POST['heading']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $instructor = $conn->real_escape_string($_POST['instructor']);
    $schedule = $conn->real_escape_string($_POST['schedule']);
    $progress = $conn->real_escape_string($_POST['progress']);

    $conn->query("INSERT INTO lectures (heading, subject, instructor, schedule, progress)
                  VALUES ('$heading', '$subject', '$instructor', '$schedule', '$progress')");
    header("Location: lectures.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM lectures WHERE id = $id");
    header("Location: lectures.php");
    exit;
}

$where = [];
if ($search) {
    $safeSearch = $conn->real_escape_string($search);
    $where[] = "(heading LIKE '%$safeSearch%' OR subject LIKE '%$safeSearch%' OR instructor LIKE '%$safeSearch%')";
}
if ($date) {
    $safeDate = $conn->real_escape_string($date);
    $where[] = "DATE(schedule) = '$safeDate'";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
/* ==========================
   페이징 계산
========================== */
$perPage = 12; // 한 페이지당 12개
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// 전체 개수
$countResult = $conn->query("SELECT COUNT(*) AS cnt FROM lectures $whereSQL");
$totalCount = $countResult->fetch_assoc()['cnt'];
$totalPages = max(1, ceil($totalCount / $perPage));

/* ==========================
   LIMIT 적용된 강의 목록
========================== */
$lectures = $conn->query("
    SELECT *
    FROM lectures
    $whereSQL
    ORDER BY id DESC
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
		position: fixed;   /* relative → fixed */
		top: 0;
		left: 0;
		bottom: 0;
		height: 100vh;
	}

	.main {
		flex: 1;
		padding: 30px;
		margin-left: 220px;  /* 사이드바 폭만큼 오른쪽으로 밀기 */
		min-height: 100vh;
		overflow-y: auto;
	}

	
	.sidebar h2 { font-size: 16px; margin-bottom: 15px; }
	.sidebar ul { list-style: none; }
	.sidebar li { margin-bottom: 10px; }
	.sidebar a {
		text-decoration: none;
		display: block;
		padding: 8px 12px;
		border-radius: 6px;
		transition: 0.2s;
	}
	.sidebar a:hover { opacity: 0.8; }
	.sidebar a.active { font-weight: bold; }
	

	.main {
		flex: 1;
		padding: 30px;
		overflow-y: auto;
	}
	
	h1 { font-size: 22px; margin-bottom: 20px; }
	.divider { border-bottom: 1px solid #ccc; margin-bottom: 15px; }
	
	/* ---- TOOLBAR ---- */
	.toolbar {
		display: flex;
		justify-content: space-between;
		gap: 10px;
		margin-bottom: 20px;
		flex-wrap: wrap;
	}
	
	.toolbar-left, .toolbar-right {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
	}
	
	.toolbar input[type="text"],
	.toolbar input[type="date"] {
		padding: 5px;
		border: none;
		border-radius: 6px;
	}
	.toolbar input[type="text"] { min-width: 210px; }
	.toolbar input[type="date"] { min-width: 160px; }
	
	body.dark-mode .toolbar input[type="text"],
	body.dark-mode .toolbar input[type="date"] {
		background: #2a2a2a;
		color: #fff;
		border: 1px solid #444;
	}
	
	body.light-mode .toolbar input[type="text"],
	body.light-mode .toolbar input[type="date"] {
		background: #f1f1f1;
		color: #000;
		border: 1px solid #ccc;
	}
	
	.toolbar button {
		padding: 5px 16px;
		border-radius: 6px;
		cursor: pointer;
		border: none;
		background: #0066ff;
		color: #fff;
	}
	
	/* ---- LECTURE LIST ---- */
	.lecture-list {
		display: flex;
		flex-direction: column;
		gap: 12px;
	
		height: 550px;       /* 적당한 높이 */
		overflow-y: auto;    /* 내부 스크롤 */
		padding-right: 10px; /* 스크롤바 때문에 내용 잘리는 것 방지용 */
	}

	
	.lecture-item {
		background: #f5f5f5;
		padding: 16px;
		border-radius: 8px;
		display: flex;
		flex-direction: column;
		gap: 6px;
		position: relative;
	}
	
	.lecture-item .title { font-size: 16px; font-weight: bold; }
	.lecture-item .meta { font-size: 13px; color: #555; }
	.lecture-item .delete {
		position: absolute;
		top: 10px;
		right: 14px;
		color: #ff4d4d;
		text-decoration: none;
		font-weight: bold;
	}
	
	/* ---- MODE TOGGLE ---- */
	.mode-toggle button {
		padding: 6px 12px;
		border-radius: 6px;
		font-size: 14px;
		cursor: pointer;
		border: 1px solid;
	}
	
	.mode-toggle {
		position: absolute;
		bottom: 20px;
		left: 20px;
	}
	
	/* ---- MODAL (등록) ---- */
	#modal {
		display: none;
		position: fixed;
		top: 15%;
		left: 50%;
		transform: translateX(-50%);
		background: #ffffff;
		padding: 24px;
		border-radius: 12px;
		box-shadow: 0 0 12px rgba(0,0,0,0.25);
		z-index: 999;
		width: 700px;
	}
	
	#modal .input-row {
		display: flex;
		gap: 10px;
		margin-bottom: 12px;
	}
	
	#modal .input-row input { flex: 1; }
	
	#modal input,
	#modal textarea {
		width: 100%;
		padding: 12px;
		margin-bottom: 12px;
		border-radius: 6px;
		border: none;
	}
	
	#modal textarea {
		height: 160px;
		font-size: 15px;
	}
	
	#modal button {
		width: 100%;
		padding: 12px;
		border-radius: 6px;
		border: none;
		cursor: pointer;
		font-size: 15px;
	}
	

	body.light-mode #modal {
		background: #ffffff;
	}
	body.light-mode #modal input,
	body.light-mode #modal textarea {
		background: #f5f5f5;
		color: #000;
		border: 1px solid #ccc;
	}
	body.light-mode #modal button {
		background: #0066ff;
		color: #fff;
	}
	
	body.dark-mode #modal {
		background: #2a2a2a;
	}
	body.dark-mode #modal input,
	body.dark-mode #modal textarea {
		background: #3a3a3a;
		color: #fff;
		border: 1px solid #555;
	}
	body.dark-mode #modal button {
		background: #db4c3f;
		color: #fff;
	}
	
	#viewModal {
		display: none;
		position: fixed;
		top: 20%;
		left: 50%;
		transform: translateX(-50%);
		background: #ffffff;
		padding: 24px;
		border-radius: 12px;
		box-shadow: 0 0 12px rgba(0,0,0,0.25);
		z-index: 1001;
		max-width: 600px;
		width: 90%;
	}

	#viewModal h3 {
		margin-bottom: 12px;
		font-size: 16px;
		font-weight: bold;
		}

	#viewModal #viewContent {
		white-space: pre-wrap;
		line-height: 1.6;
		font-size: 14px;
		background: #fafafa;
		padding: 12px;
		border-radius: 8px;
		border: 1px solid #ddd;
		color: #333;
		max-height: 300px;
		overflow-y: auto;
	}

/* 필요하면 유지, 아니면 지워도 됨 */
	#viewContent {
		height: 300px;
	}

/* 제목 + PDF 버튼을 양쪽 정렬 */
	.view-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;
	}

/* PDF로 저장 버튼 (작게) */
	#pdfBtn {
		all: unset;              /* 기본 버튼 스타일 제거 */
		display: inline-block;
		padding: 5px 10px;
		font-size: 13px;
		background: #0066ff;
		color: #fff;
		border-radius: 6px;
		cursor: pointer;
		white-space: nowrap;
		border: 1px solid transparent;
	}

	#pdfBtn:hover {
		opacity: 0.85;
	}

/* 닫기 버튼 (가로 100%) */
	#closeBtn {
		margin-top: 15px;
		padding: 8px 12px;
		width: 100%;
		background: #db4c3f;
		color: #fff;
		border: none;
		border-radius: 6px;
		cursor: pointer;
		font-size: 14px;
	}

	
	body.light-mode #pdfBtn {
		background: #0066ff;
		color: #fff;
	}
	
	body.dark-mode #pdfBtn {
		background: #db4c3f;
		color: #fff;
	}

	body.dark-mode #pdfBtn {
		background: #db4c3f;
	}
	
	/* ---- 다크 모드 viewModal ---- */
	body.dark-mode #viewModal {
		background: #2a2a2a;
	}
	body.dark-mode #viewModal #viewContent {
		background: #333;
		border: 1px solid #555;
		color: #eee;
	}
	
	body.light-mode #viewModal button {
		background: #0066ff;
		color: #fff;
	}

	body.light-mode #pdfBtn {
		background: #0066ff;
		color: #fff;
	}
	
	
	body.dark-mode { background: #1e1e1e; color: #f1f1f1; }
	body.dark-mode .sidebar { background: #121212; border-color: #2a2a2a; }
	body.dark-mode .sidebar a { color: #ddd; }
	body.dark-mode .sidebar a.active { background: #333; }
	body.dark-mode .main { background: #1e1e1e; }
	body.dark-mode button { background: #db4c3f; color: #fff; }
	body.dark-mode .lecture-item { background: #2a2a2a; color: #fff; }
	body.dark-mode .lecture-item .meta { color: #aaa; }
	body.dark-mode .lecture-item .delete { color: #ff6666; }
	

	#overlay {
		display: none;
		position: fixed;
		top: 0; left: 0;
		width: 100%; height: 100%;
		background: rgba(0,0,0,0.5);
		z-index: 998;
	}
	
	/* 화이트 모드일 때 모드 버튼 파란색 */
	body.light-mode .mode-toggle button {
		background: #0066ff !important;
		color: #fff !important;
		border-color: #0066ff !important;
	}
	
	/* 다크 모드일 때 모드 버튼 빨간색 유지 */
	body.dark-mode .mode-toggle button {
		background: #db4c3f !important;
		color: #fff !important;
		border-color: #db4c3f !important;
	}

	/* ======================
   페이지네이션 스타일
====================== */
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
	
	/* 라이트 모드에서 페이지네이션 */
	body.light-mode .page-btn {
		background: #ffffff;
		color: #111;
		border-color: #ccc;
	}
	
	body.light-mode .page-btn.active {
		background: #0066ff;
		color: #fff;
	}
	
	/* 다크 모드에서 페이지네이션 */
	body.dark-mode .page-btn {
		background: #1e1e1e;
		color: #fff;
		border-color: #666;
	}
	
	body.dark-mode .page-btn.active {
		background: #db4c3f;
		color: #fff;
	}

	
	
</style>

<div class="sidebar">
    <h2>내 페이지</h2>
    <ul>
        <li><a href="index.php">홈 페이지</a></li>
        <li><a href="todos.php">To-do</a></li>
        <li><a href="lectures.php" class="active">수업/정리</a></li>
    </ul>
    <div class="mode-toggle">
        <button onclick="toggleMode()">모드</button>
    </div>
</div>

<div class="main">
    <h2>📚 강의 / 스터디 관리</h2>
    <br>
    <div class="divider"></div>

    <div class="toolbar">
        <div class="toolbar-left">
            <button onclick="openModal()">추가</button>
        </div>
        <form method="get" class="toolbar-right">
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
            <input type="text" name="search" placeholder="과목명 또는 교수명" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">검색</button>
        </form>
    </div>

    <div class="lecture-list">
    <?php while ($row = $lectures->fetch_assoc()): ?>
        <div class="lecture-item" onclick="showProgressModal(`<?= htmlspecialchars($row['subject']) ?>`, `<?= htmlspecialchars($row['heading']) ?>`, `<?= htmlspecialchars($row['progress']) ?>`)">
            <a class="delete" href="?delete=<?= $row['id'] ?>" onclick="event.stopPropagation(); return confirm('삭제할까요?')">🗑</a>
            <div class="title"><?= htmlspecialchars($row['subject']) ?>  &nbsp|&nbsp  <?= htmlspecialchars($row['heading']) ?></div>
            <div class="meta">👨‍🏫 <?= htmlspecialchars($row['instructor']) ?> | 🕒 <?= htmlspecialchars($row['schedule']) ?></div>
        </div>
    <?php endwhile; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a class="page-btn" 
           href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
           ◀ Prev
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="page-btn <?= ($i == $page ? 'active' : '') ?>"
           href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
           <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a class="page-btn" 
           href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
           Next ▶
        </a>
    <?php endif; ?>
</div>


<!-- 기존 등록용 모달 -->
<div id="modal">
    <form method="post">
        <input type="text" name="heading" placeholder="제목" required>
        <div class="input-row">
            <input type="text" name="subject" placeholder="과목명" required>
            <input type="text" name="instructor" placeholder="교수명" required>
        </div>
        <input type="date" name="schedule" required>
        <textarea name="progress" placeholder="내용/메모"></textarea>
        <button type="submit">등록하기</button>
    </form>
</div>

<!-- ✅ 새로 추가된 "내용 보기" 모달 -->
<div id="viewModal">
    <div class="view-header">
        <h3 id="viewTitle"></h3>
        <button id="pdfBtn" onclick="exportPDF()">PDF로 저장</button>
    </div>
	<br>
    <p id="viewContent"></p>

    <button onclick="closeViewModal()" id="closeBtn">닫기</button>
</div>



<div id="overlay" onclick="closeModal(); closeViewModal();"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

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
function openModal() {
    document.getElementById('modal').style.display = 'block';
    document.getElementById('overlay').style.display = 'block';
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
}
function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
}
function showProgressModal(subject, heading, content) {
    document.getElementById('viewTitle').innerText = `${subject} | ${heading}`;
    document.getElementById('viewContent').innerText = content || '(내용 없음)';
    document.getElementById('viewModal').style.display = 'block';
    document.getElementById('overlay').style.display = 'block';
}


function exportPDF() {

    const title = document.getElementById('viewTitle').innerText;
    const content = document.getElementById('viewContent').innerText;

    const pdfWrapper = document.createElement('div');
    pdfWrapper.style.padding = "20px";
    pdfWrapper.style.lineHeight = "1.6";
    pdfWrapper.style.fontSize = "14px";

    pdfWrapper.innerHTML = `
        <h2>${title}</h2>
        <pre style="white-space: pre-wrap;">${content}</pre>
    `;

    const options = {
        margin: 10,
        filename: `${title}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().from(pdfWrapper).set(options).save();
}

applyMode();
</script>