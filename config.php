<?php
// ============================================================================
// 1. SECURITY: Prevent Direct Script Access
// ============================================================================
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// ============================================================================
// 2. INFRASTRUCTURE & OPENROUTER API CONFIGURATION
// ============================================================================
define('OPENROUTER_API_KEY', 'sk-...');
define('DEFAULT_MODEL', 'google/gemini-2.5-flash-lite');
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('SITE_NAME', 'کارگاه تعاملی');

// ============================================================================
// 3. GLOBAL UI SETTINGS (shared across all lessons)
// ============================================================================

// Default browser tab title (shown on the lesson menu page)
define('PAGE_TITLE', 'کارگاه‌های سقراطی هوش مصنوعی');

// Mentor name shown in the chat header, for every lesson
define('CONTACT_NAME', 'مربی سقراطی');

// Input box placeholder text
define('MESSAGE_PLACEHOLDER', 'پاسخ یا نظر خود را بنویسید...');

// ============================================================================
// 4. LESSON REGISTRY
// Each lesson lives in its own file under lessons/, addressed by a URL slug:
//   https://domain.com/workshop/?l=prompt-engineering
// To add a new lesson: copy lessons/_template.php, rename it, fill it in,
// then add one line below mapping its slug to the filename.
// ============================================================================
define('LESSONS_DIR', __DIR__ . '/lessons');

define('AVAILABLE_LESSONS', [
    'itro' => 'intro.php',
    'instruction' => 'instruction.php',
    'context' => 'context.php',
    'output' => 'output.php',
    'input' => 'input.php',
    'structure' => 'structure.php',
    
    // 'your-slug-here'  => 'your-file.php',
]);

// ============================================================================
// 5. UNIVERSAL SOCRATIC ENGINE (Sandwich Prompt Architecture) ⚙️
// This section is course-agnostic and remains unchanged across different topics.
// ============================================================================

// Preamble: Sets persona, subject specialization, and strict language policy
define('PROMPT_PRE_CURRICULUM', <<<PROMPT_PRE
# Role & Identity
You are an Elite Socratic Educator and Subject Mentor facilitating a workshop on: "{COURSE_TITLE}".
Target Audience: {TARGET_AUDIENCE}.
Pedagogy: Guided Discovery (Socratic Method). Your goal is never to lecture or provide ready-made answers, but to lead the learner to discover concepts through strategic questioning and hands-on validation.

## Language Requirement (STRICT)
- The learner is a native Persian speaker.
- ALL of your responses, validations, hints, and questions MUST be 100% in natural, fluent, and respectful Persian.
- Even though these system guidelines are written in English, NEVER converse in English.
- Use French quotation marks « » if quotation marks are needed.
PROMPT_PRE
);

// Postamble: Enforces strict operational rules, blueprint formatting, and roadmap tracking
define('PROMPT_POST_CURRICULUM', <<<PROMPT_POST
# Strict Operational Constraints & Pedagogical Protocol

1. **No Direct Answers:** NEVER solve exercises or provide full explanations/definitions directly. Always guide through deductive questions and structured hints.
2. **One Action per Turn:** Ask exactly ONE focused question or challenge per turn.
3. **Structured Response Blueprint (3-Part Output):**
   - **Part 1 (Validation):** Warmly acknowledge and evaluate the learner's previous response (1-2 sentences).
   - **Part 2 (Bridge/Hint):** If they struggle or give a partial answer, provide a subtle scaffold or analogy.
   - **Part 3 (The Question):** The single Socratic question driving the next step.
4. **3-Tier Dynamic Scaffolding:**
   - *Tier 1 (First Struggle):* Ask a reflective question about their reasoning.
   - *Tier 2 (Second Struggle):* Offer a comparative choice (e.g., contrasting two short snippets).
   - *Tier 3 (Third Struggle):* Break the concept down to the simplest possible analogy and ask them to complete it.

5. **Instructor-Defined Progression Roadmap (CRITICAL):**
   Follow this exact sequential roadmap step-by-step. Do not skip or jump between steps until the learner demonstrates intuitive and practical mastery of the current milestone:
   [ROADMAP]
   {PROGRESSION_STRATEGY}
   [/ROADMAP]

6. **Tone & Empathy:** Maintain a warm, encouraging, and collaborative tone with high respect suitable for {TARGET_AUDIENCE}.
7. **Formatting:** Use Markdown, and emojis (maximum 1-2 per turn: ✅, 💡, 🎯, 🏆).
PROMPT_POST
);

// ============================================================================
// 6. SYSTEM HELPER FUNCTIONS (used by api.php and index.php)
// ============================================================================

// Loads a single lesson's data array by its URL slug.
// Returns null if the slug is unknown or the file is missing.
function loadLesson(string $slug): ?array {
    if (!isset(AVAILABLE_LESSONS[$slug])) {
        return null;
    }
    $path = LESSONS_DIR . '/' . AVAILABLE_LESSONS[$slug];
    if (!is_file($path)) {
        return null;
    }
    $lesson = require $path;
    $lesson['slug'] = $slug;
    return $lesson;
}

// Returns [slug => course_title] for every available lesson,
// used to render the lesson selection menu.
function getLessonList(): array {
    $list = [];
    foreach (array_keys(AVAILABLE_LESSONS) as $slug) {
        $lesson = loadLesson($slug);
        if ($lesson !== null) {
            $list[$slug] = $lesson['course_title'] ?? $slug;
        }
    }
    return $list;
}

// Slug of the first registered lesson (safe fallback default).
function getFirstLessonSlug(): string {
    $keys = array_keys(AVAILABLE_LESSONS);
    return $keys[0] ?? '';
}

// Assembles the full system prompt for a specific lesson.
function getSystemPrompt(array $lesson): string {
    $fullPrompt = PROMPT_PRE_CURRICULUM . "\n\n" .
                  "[EXCLUSIVE_CURRICULUM]\n" .
                  trim($lesson['curriculum_text']) . "\n" .
                  "[/EXCLUSIVE_CURRICULUM]\n\n" .
                  PROMPT_POST_CURRICULUM;

    return strtr($fullPrompt, [
        '{COURSE_TITLE}'         => $lesson['course_title'],
        '{TARGET_AUDIENCE}'      => $lesson['target_audience'],
        '{PROGRESSION_STRATEGY}' => trim($lesson['progression_strategy']),
    ]);
}

// Returns the hidden opening trigger message for a specific lesson.
function getInitialTriggerMessage(array $lesson): string {
    return $lesson['opening_hook'];
}