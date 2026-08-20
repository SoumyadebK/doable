<?php
/**
 * Page Content editor — a single recursive form that walks the whole content
 * structure so every piece of text on the site is editable here.
 */
require_once __DIR__ . '/auth.php';

$saved = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $submitted = $_POST['c'] ?? [];
        $clean = resequence_content($submitted);
        try {
            // Merge over defaults so any brand-new default keys are still present.
            save_content(deep_merge(default_content(), $clean));
            $saved = true;
        } catch (Throwable $ex) {
            error_log('content save failed: ' . $ex->getMessage());
            $error = 'Could not save. Please check the database settings in config.php.';
        }
    }
}

$content = get_content();

/* ---- Helpers used only by this editor ---------------------------------- */
function resequence_content($a) {
    if (!is_array($a)) return $a;
    $allInt = true;
    foreach (array_keys($a) as $k) { if (!ctype_digit((string)$k)) { $allInt = false; break; } }
    $out = [];
    foreach ($a as $k => $v) { $out[$k] = resequence_content($v); }
    return $allInt ? array_values($out) : $out;
}
function human_label(string $key): string {
    $key = preg_replace('/([a-z])([A-Z])/', '$1 $2', $key);
    $key = str_replace(['_', '-'], ' ', $key);
    return ucwords(trim($key));
}
function is_list_arr($a): bool {
    return is_array($a) && ($a === [] || array_keys($a) === range(0, count($a) - 1));
}
function blank_like($v) {
    if (is_array($v)) {
        if (is_list_arr($v)) return [];
        $o = []; foreach ($v as $k => $vv) $o[$k] = blank_like($vv); return $o;
    }
    if (is_bool($v)) return false;
    return '';
}

/** Render any node (map / list / scalar) as form fields. */
function render_node(string $path, $value, string $label, int $depth = 0): string {
    if (is_array($value) && !is_list_arr($value)) {
        // associative map -> a titled group
        $inner = '';
        foreach ($value as $k => $v) {
            $inner .= render_node($path . '[' . $k . ']', $v, human_label((string)$k), $depth + 1);
        }
        $ring = $depth === 0 ? 'border-gray-200' : 'border-gray-100';
        return '<fieldset class="border ' . $ring . ' rounded-xl p-5 mb-5 bg-white">'
             . ($label !== '' ? '<legend class="px-2 text-sm font-bold text-emerald-700 uppercase tracking-wide">' . e($label) . '</legend>' : '')
             . $inner . '</fieldset>';
    }
    if (is_list_arr($value)) {
        // repeatable list
        $blank = !empty($value) ? blank_like($value[0]) : '';
        $items = '';
        foreach ($value as $i => $item) {
            $items .= list_item_wrap(render_node($path . '[' . $i . ']', $item, 'Item ' . ($i + 1), $depth + 1));
        }
        $template = list_item_wrap(render_node($path . '[__INDEX__]', $blank, 'New item', $depth + 1));
        $lid = 'list_' . substr(md5($path), 0, 8);
        return '<div class="mb-5">'
             . '<div class="flex items-center justify-between mb-2">'
             . '<span class="text-sm font-bold text-gray-700">' . e($label) . '</span>'
             . '<button type="button" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700" onclick="addListItem(\'' . $lid . '\')">+ Add</button>'
             . '</div>'
             . '<div id="' . $lid . '" data-count="' . count($value) . '">' . $items . '</div>'
             . '<template id="' . $lid . '_tpl">' . $template . '</template>'
             . '</div>';
    }
    // scalar
    if (is_bool($value)) {
        $checked = $value ? ' checked' : '';
        return '<div class="mb-4">'
             . '<input type="hidden" name="' . e($path) . '" value="0">'
             . '<label class="inline-flex items-center gap-2 text-sm text-gray-700">'
             . '<input type="checkbox" name="' . e($path) . '" value="1"' . $checked . ' class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">'
             . e($label) . '</label></div>';
    }
    $str = (string)$value;
    $len  = function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
    $long = ($len > 70) || (strpos($str, "\n") !== false);
    if ($long) {
        return '<div class="mb-4"><label class="block text-xs font-medium text-gray-600 mb-1">' . e($label) . '</label>'
             . '<textarea name="' . e($path) . '" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">' . e($str) . '</textarea></div>';
    }
    return '<div class="mb-4"><label class="block text-xs font-medium text-gray-600 mb-1">' . e($label) . '</label>'
         . '<input name="' . e($path) . '" value="' . e($str) . '" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"></div>';
}
function list_item_wrap(string $inner): string {
    return '<div class="list-item relative border border-dashed border-gray-300 rounded-lg p-4 pt-8 mb-3 bg-gray-50">'
         . '<button type="button" class="absolute top-2 right-2 text-xs font-semibold text-red-500 hover:text-red-700" onclick="removeListItem(this)">Remove</button>'
         . $inner . '</div>';
}

$admin_page = 'content'; $admin_title = 'Page Content';
include __DIR__ . '/layout-header.php';
?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Page Content</h1>
    <p class="text-gray-500">Edit any text, feature, or section shown on your website.</p>
  </div>
</div>

<?php if ($saved): ?>
  <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">Your changes have been saved. <a href="<?= $base ?>/index.php" target="_blank" class="underline font-semibold">View site</a></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST">
  <?= csrf_field() ?>
  <div class="sticky top-16 z-30 -mt-2 mb-4 flex justify-end">
    <button type="submit" class="btn-premium">Save Changes</button>
  </div>
  <?php foreach ($content as $section => $val): ?>
    <?= render_node('c[' . $section . ']', $val, human_label($section), 0) ?>
  <?php endforeach; ?>
  <div class="flex justify-end mt-6">
    <button type="submit" class="btn-premium">Save Changes</button>
  </div>
</form>

<script>
function addListItem(listId){
  var list=document.getElementById(listId);
  var tpl=document.getElementById(listId+'_tpl');
  var idx=parseInt(list.getAttribute('data-count')||'0',10);
  var html=tpl.innerHTML.split('__INDEX__').join(idx);
  var wrap=document.createElement('div');
  wrap.innerHTML=html;
  var node=wrap.firstElementChild;
  list.appendChild(node);
  list.setAttribute('data-count',idx+1);
}
function removeListItem(btn){
  var item=btn.closest('.list-item');
  if(item) item.remove();
}
</script>
<?php include __DIR__ . '/layout-footer.php'; ?>
