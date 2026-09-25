<?php
// ============================================================================
// TEMPLATE — copy this file to create a new lesson.
// 1. Rename it to a URL-friendly slug, e.g. "critical-thinking.php"
// 2. Register that same slug in config.php -> AVAILABLE_LESSONS
// 3. Fill in the five fields below
// The lesson will then be reachable at: ?l=your-slug-name
// ============================================================================
return [
    // Title shown in the lesson menu and in the chat header
    'course_title'         => 'Lesson title goes here',

    // Target audience description (told to the AI in the system prompt)
    'target_audience'      => 'Describe the target audience here',

    // Hidden trigger message sent to the AI to open the session.
    // Should reference an analogy/hook relevant to THIS lesson's topic.
    'opening_hook'         => 'Start the workshop now. Warmly welcome the learner and ask an opening Socratic question based on a fitting analogy for this topic.',

    // Step-by-step roadmap the AI must follow in order
    'progression_strategy' => <<<ROADMAP
1. First learning milestone for this lesson.
2. Second learning milestone for this lesson.
3. Continue adding milestones...
4. Practical exercise validation phase.
5. Final wrap-up and mastery certification.
ROADMAP,

    // Full curriculum text: concepts, examples, and exercises (Markdown is fine)
    'curriculum_text'      => <<<'CURRICULUM_EOT'
Write the full curriculum content here — concepts, weak/strong examples,
and the exercises the AI should guide the learner through.
CURRICULUM_EOT,
];
