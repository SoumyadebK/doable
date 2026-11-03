<?php
/** Admin: view contact/trial leads and demo requests (read-only). */
require_once __DIR__ . '/auth.php';

$tab = ($_GET['tab'] ?? 'contact') === 'demo' ? 'demo' : 'contact';

$contacts = [];
$demos    = [];
try { $contacts = db()->query('SELECT * FROM contact_submissions ORDER BY created_at DESC')->fetchAll(); }
catch (Throwable $e) { $contacts = []; }
try { $demos = db()->query('SELECT * FROM demo_requests ORDER BY created_at DESC')->fetchAll(); }
catch (Throwable $e) { $demos = []; }

function fmt_date($v): string {
    if (!$v) return '';
    $ts = strtotime((string) $v);
    return $ts ? date('M j, Y g:i A', $ts) : (string) $v;
}

$admin_page = 'leads'; $admin_title = 'Leads';
include __DIR__ . '/layout-header.php';
?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Leads</h1>
    <p class="text-gray-500">Everyone who reached out through your website forms.</p>
  </div>
</div>

<div class="flex gap-2 mb-6 border-b border-gray-200">
  <a href="<?= $base ?>/admin/leads.php?tab=contact"
     class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px <?= $tab === 'contact' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
    Contact &amp; Trial (<?= count($contacts) ?>)
  </a>
  <a href="<?= $base ?>/admin/leads.php?tab=demo"
     class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px <?= $tab === 'demo' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
    Demo Requests (<?= count($demos) ?>)
  </a>
</div>

<?php if ($tab === 'contact'): ?>
  <?php if (!$contacts): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-500">
      No contact or trial submissions yet.
    </div>
  <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="px-4 py-3 font-semibold">Name</th>
            <th class="px-4 py-3 font-semibold">Email</th>
            <th class="px-4 py-3 font-semibold">Business</th>
            <th class="px-4 py-3 font-semibold">Type</th>
            <th class="px-4 py-3 font-semibold">Phone</th>
            <th class="px-4 py-3 font-semibold">Message</th>
            <th class="px-4 py-3 font-semibold">SMS</th>
            <th class="px-4 py-3 font-semibold whitespace-nowrap">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($contacts as $r): ?>
            <tr class="align-top hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap"><?= e($r['name']) ?></td>
              <td class="px-4 py-3 text-emerald-700"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['business_name'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['business_type'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700 whitespace-nowrap"><?= e($r['phone'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-600 max-w-xs"><?= nl2br(e($r['message'])) ?></td>
              <td class="px-4 py-3"><?= !empty($r['sms_consent']) ? '<span class="text-emerald-600 font-semibold">Yes</span>' : '<span class="text-gray-400">No</span>' ?></td>
              <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php else: ?>
  <?php if (!$demos): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-500">
      No demo requests yet.
    </div>
  <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="px-4 py-3 font-semibold">Name</th>
            <th class="px-4 py-3 font-semibold">Email</th>
            <th class="px-4 py-3 font-semibold">Business</th>
            <th class="px-4 py-3 font-semibold">Type</th>
            <th class="px-4 py-3 font-semibold">Phone</th>
            <th class="px-4 py-3 font-semibold">Preferred</th>
            <th class="px-4 py-3 font-semibold">Staff</th>
            <th class="px-4 py-3 font-semibold">Current tool</th>
            <th class="px-4 py-3 font-semibold">Message</th>
            <th class="px-4 py-3 font-semibold whitespace-nowrap">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($demos as $r): ?>
            <tr class="align-top hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap"><?= e($r['name']) ?></td>
              <td class="px-4 py-3 text-emerald-700"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['business_name'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['business_type'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700 whitespace-nowrap"><?= e($r['phone'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700 whitespace-nowrap"><?= e(trim(($r['preferred_date'] ?? '') . ' ' . ($r['preferred_time'] ?? '')) ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['number_of_staff'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-700"><?= e($r['current_solution'] ?: '—') ?></td>
              <td class="px-4 py-3 text-gray-600 max-w-xs"><?= nl2br(e($r['message'] ?? '')) ?></td>
              <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/layout-footer.php'; ?>
