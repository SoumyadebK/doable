<?php
/**
 * Tiny, dependency-free Markdown -> HTML converter.
 * Supports: #/##/### headings, unordered (-,*) and ordered lists,
 * **bold**, *italic*, `code`, [links](url), blank-line paragraphs.
 * Good enough for blog posts written in the admin editor. No Composer needed.
 */
function markdown_to_html(string $md): string {
    $md = str_replace(["\r\n", "\r"], "\n", $md);
    $lines = explode("\n", $md);
    $html = '';
    $listType = null; // 'ul' | 'ol'
    $para = [];

    $inline = function (string $t): string {
        $t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
        $t = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $t);
        $t = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $t);
        $t = preg_replace('/`(.+?)`/s', '<code>$1</code>', $t);
        $t = preg_replace('/\[([^\]]+)\]\((https?:[^)\s]+)\)/', '<a href="$2" class="text-emerald-600 underline" target="_blank" rel="noopener">$1</a>', $t);
        return $t;
    };
    $flushPara = function () use (&$para, &$html, $inline) {
        if ($para) { $html .= '<p>' . $inline(implode(' ', $para)) . '</p>' . "\n"; $para = []; }
    };
    $closeList = function () use (&$listType, &$html) {
        if ($listType) { $html .= '</' . $listType . '>' . "\n"; $listType = null; }
    };

    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '') { $flushPara(); $closeList(); continue; }

        if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
            $flushPara(); $closeList();
            $level = strlen($m[1]);
            $html .= '<h' . $level . '>' . $inline($m[2]) . '</h' . $level . '>' . "\n";
            continue;
        }
        if (preg_match('/^[-*]\s+(.*)$/', $trim, $m)) {
            $flushPara();
            if ($listType !== 'ul') { $closeList(); $html .= '<ul>' . "\n"; $listType = 'ul'; }
            $html .= '<li>' . $inline($m[1]) . '</li>' . "\n";
            continue;
        }
        if (preg_match('/^\d+\.\s+(.*)$/', $trim, $m)) {
            $flushPara();
            if ($listType !== 'ol') { $closeList(); $html .= '<ol>' . "\n"; $listType = 'ol'; }
            $html .= '<li>' . $inline($m[1]) . '</li>' . "\n";
            continue;
        }
        $closeList();
        $para[] = $trim;
    }
    $flushPara(); $closeList();
    return $html;
}
