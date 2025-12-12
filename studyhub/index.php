<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>나의 학습 관리 웹</title>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: 0.2s;
    }

    /* --------------------------
       메인 카드
    --------------------------- */
    .container {
        width: 420px;
        text-align: center;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 0 12px rgba(0,0,0,0.15);
        transition: 0.2s;
    }

    h1 {
        margin-bottom: 25px;
        font-size: 24px;
        font-weight: bold;
    }

    ul {
        list-style: none;
        margin-top: 15px;
    }

    li {
        margin-bottom: 15px;
    }

    a {
        display: block;
        padding: 12px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.2s;
        border: 1px solid;
        font-size: 16px;
        font-weight: 500;
    }

    /* --------------------------
       라이트 모드 스타일
    --------------------------- */
    body.light-mode {
        background: #f5f5f5;
        color: #111;
    }

    body.light-mode .container {
        background: #fff;
        border: 1px solid #ddd;
    }

    body.light-mode a {
        background: #f9f9f9;
        border-color: #ccc;
        color: #000;
    }

    body.light-mode a:hover {
        background: #0066ff;
        color: #fff;
        border-color: #0066ff;
    }

    /* --------------------------
       다크 모드 스타일
    --------------------------- */
    body.dark-mode {
        background: #1e1e1e;
        color: #f1f1f1;
    }

    body.dark-mode .container {
        background: #2a2a2a;
        border: 1px solid #444;
        color: #fff;
    }

    body.dark-mode a {
        background: #333;
        border-color: #555;
        color: #fff;
    }

    body.dark-mode a:hover {
        background: #db4c3f;
        color: #fff;
        border-color: #db4c3f;
    }

    /* --------------------------
       모드 토글 버튼
    --------------------------- */
    .mode-btn {
        margin-top: 20px;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid;
        font-size: 14px;
    }

    body.light-mode .mode-btn {
        background: #0066ff;
        color: #fff;
        border-color: #0066ff;
    }

    body.dark-mode .mode-btn {
        background: #db4c3f;
        color: #fff;
        border-color: #db4c3f;
    }

</style>
</head>

<body>

<div class="container">
    <h1>나의 학습 관리 웹</h1>

    <ul>
        <li><a href="todos.php">✅ To-do 리스트</a></li>
        <li><a href="lectures.php">📚 강의/스터디 관리</a></li>
    </ul>

    <button class="mode-btn" onclick="toggleMode()">모드 전환</button>
</div>

<script>
function toggleMode() {
    const mode = document.body.classList.contains('light-mode') ? 'dark' : 'light';
    localStorage.setItem('mode', mode);
    applyMode();
}

function applyMode() {
    const mode = localStorage.getItem('mode') || 'light';
    document.body.classList.remove('light-mode', 'dark-mode');
    document.body.classList.add(mode + '-mode');
}

applyMode(); // 페이지 로드시 즉시 적용
</script>

</body>
</html>
