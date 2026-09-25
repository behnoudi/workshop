<?php
session_start();
require_once __DIR__ . '/config.php';

// ===================== Decide: show the lesson menu, or a specific lesson's chat =====================
$showMenu = false;
$requestedSlug = isset($_GET['l']) ? trim((string) $_GET['l']) : null;

if (isset($_GET['menu'])) {
    // Explicit request to go back to the lesson list
    $showMenu = true;
} elseif ($requestedSlug !== null && isset(AVAILABLE_LESSONS[$requestedSlug])) {
    // Direct link to a specific lesson, e.g. ?l=prompt-engineering
    $_SESSION['current_lesson_slug'] = $requestedSlug;
} elseif (!isset($_SESSION['current_lesson_slug'])) {
    // No lesson chosen yet -> show the menu
    $showMenu = true;
}

$currentLesson = null;
if (!$showMenu) {
    $currentLesson = loadLesson($_SESSION['current_lesson_slug']);
    if ($currentLesson === null) {
        // Stale/invalid slug left in the session
        $showMenu = true;
    }
}

$pageTitle = $showMenu
    ? PAGE_TITLE
    : htmlspecialchars($currentLesson['course_title'], ENT_QUOTES, 'UTF-8') . ' | ' . PAGE_TITLE;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-shell">

        <?php if ($showMenu): ?>

            <!-- =============== Lesson Selection Menu =============== -->
            <header class="chat-header">
                <div class="header-contact">
                    <div class="avatar">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="header-text">
                        <h1 class="contact-name"><?php echo htmlspecialchars(CONTACT_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <span class="status-text">انتخاب کارگاه آموزشی</span>
                    </div>
                </div>
            </header>

            <main class="lessons-menu">
                <h2 class="lessons-menu-title">کدام کارگاه را می‌خواهید شروع کنید؟</h2>
                <p class="lessons-menu-subtitle">یکی از کارگاه‌های تعاملی زیر را انتخاب کنید</p>
                <div class="lessons-list">
                    <?php foreach (getLessonList() as $slug => $title): ?>
                        <a class="lesson-card" href="index.php?l=<?php echo urlencode($slug); ?>">
                            <div class="lesson-card-icon"><i class="bi bi-mortarboard-fill"></i></div>
                            <div class="lesson-card-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                            <i class="bi bi-chevron-left lesson-card-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </main>

        <?php else: ?>

            <!-- =============== Top Chat Header Bar =============== -->
            <header class="chat-header">
                <div class="header-contact">
                    <div class="avatar">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="header-text">
                        <h1 class="contact-name"><?php echo htmlspecialchars($currentLesson['course_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <span id="statusText" class="status-text">آنلاین</span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?menu=1" class="header-icon-btn" title="بازگشت به فهرست دروس">
                        <i class="bi bi-grid-fill header-icon"></i>
                    </a>
                    <button class="header-icon-btn" onclick="resetChat()" title="شروع مجدد همین کارگاه">
                        <i class="bi bi-three-dots-vertical header-icon"></i>
                    </button>
                </div>
            </header>

            <!-- Chat Message Bubbles Container -->
            <main id="chatContainer" class="chat-body">
                <!-- Messages are dynamically injected here via JavaScript -->
            </main>

            <!-- Bottom Chat Input Form -->
            <footer class="chat-footer">
                <form id="chatForm" class="chat-input-row">
                    <div class="input-pill">
                        <i class="bi bi-emoji-smile input-icon" title="ایموجی (نمایشی)"></i>
                        <textarea
                            id="messageInput"
                            rows="1"
                            placeholder="<?php echo htmlspecialchars(MESSAGE_PLACEHOLDER, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            autocomplete="off"></textarea>
                        <i class="bi bi-paperclip input-icon" title="پیوست (نمایشی)"></i>
                    </div>
                    <button type="submit" id="sendBtn" class="send-btn">
                        <i id="sendIcon" class="bi bi-mic-fill"></i>
                    </button>
                </form>
            </footer>

        <?php endif; ?>

    </div>

    <?php if (!$showMenu): ?>
    <!-- External Markdown Parser & HTML Sanitizer Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>

    <!-- Core Application Logic -->
    <script src="api.js"></script>
    <?php endif; ?>
</body>
</html>
