<?php

require __DIR__ . '/auth.php';

crtlu_require_admin();

$path = dirname(__DIR__) . '/data/coupons.json';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function coupon_data(string $path): array
{
    $decoded = is_readable($path) ? json_decode((string)file_get_contents($path), true) : [];
    return is_array($decoded) ? $decoded : ['coupons' => []];
}

function save_coupon_data(string $path, array $data): void
{
    $data['coupons'] = array_values($data['coupons'] ?? []);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
}

$message = '';
$data = coupon_data($path);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save');
    $code = strtoupper(trim(preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''))));
    if ($code === '') {
        $message = 'Coupon code is required.';
    } elseif ($action === 'delete') {
        $data['coupons'] = array_values(array_filter($data['coupons'] ?? [], static fn(array $coupon): bool => strtoupper((string)($coupon['code'] ?? '')) !== $code));
        save_coupon_data($path, $data);
        $message = 'Coupon deleted.';
    } else {
        $coupon = [
            'code' => $code,
            'label' => trim((string)($_POST['label'] ?? $code)),
            'active' => isset($_POST['active']),
            'type' => $_POST['type'] === 'amount' ? 'amount' : 'percent',
            'percent_off' => $_POST['type'] === 'amount' ? null : max(0, min(80, (float)($_POST['percent_off'] ?? 0))),
            'amount_off_cents' => $_POST['type'] === 'amount' ? max(0, (int)round(((float)($_POST['amount_off'] ?? 0)) * 100)) : null,
            'min_subtotal_cents' => max(0, (int)round(((float)($_POST['min_subtotal'] ?? 0)) * 100)),
            'max_discount_cents' => max(0, (int)round(((float)($_POST['max_discount'] ?? 0)) * 100)),
            'starts_at' => trim((string)($_POST['starts_at'] ?? '')),
            'ends_at' => trim((string)($_POST['ends_at'] ?? '')),
        ];
        $replaced = false;
        foreach ($data['coupons'] as &$existing) {
            if (strtoupper((string)($existing['code'] ?? '')) === $code) {
                $existing = $coupon;
                $replaced = true;
                break;
            }
        }
        unset($existing);
        if (!$replaced) {
            $data['coupons'][] = $coupon;
        }
        save_coupon_data($path, $data);
        $message = 'Coupon saved.';
    }
}

$data = coupon_data($path);
$coupons = $data['coupons'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Coupons | CRTL U Admin</title>
<style>
body { margin: 0; font-family: system-ui, sans-serif; background: #071016; color: #f5fbff; }
main { max-width: 1180px; margin: 0 auto; padding: 28px; }
a { color: inherit; }
.topbar, .links, form.grid { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.topbar { justify-content: space-between; margin-bottom: 20px; }
.links a, button { min-height: 34px; border: 1px solid rgba(255,255,255,.18); background: #0d171f; color: #fff; padding: 0 12px; text-decoration: none; font-weight: 800; }
button.primary { background: #5de7ff; color: #001014; border: 0; }
button.danger { color: #ff7777; }
.panel, table { background: #0d171f; border: 1px solid rgba(255,255,255,.12); }
.panel { padding: 16px; margin-bottom: 18px; }
label { display: grid; gap: 5px; color: #91a1ae; font-size: 12px; text-transform: uppercase; }
input, select { min-height: 34px; border: 1px solid rgba(255,255,255,.18); background: #071016; color: #fff; padding: 0 8px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.12); text-align: left; font-size: 14px; vertical-align: top; }
th { color: #8bff85; }
.muted { color: #91a1ae; }
.message { color: #8bff85; margin: 10px 0; }
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div><h1>Coupons</h1><p class="muted">Create and edit storefront coupon codes stored in data/coupons.json.</p></div>
    <nav class="links"><a href="orders.php">Orders</a><a href="members.php">Members</a><a href="emails.php">Emails</a></nav>
  </div>
  <?php if ($message): ?><div class="message"><?= h($message) ?></div><?php endif; ?>
  <section class="panel">
    <h2>Save coupon</h2>
    <form method="post" class="grid">
      <input type="hidden" name="action" value="save">
      <label>Code<input name="code" placeholder="WELCOME5" required></label>
      <label>Label<input name="label" placeholder="Welcome 5% off"></label>
      <label>Type<select name="type"><option value="percent">Percent</option><option value="amount">Fixed amount</option></select></label>
      <label>Percent off<input name="percent_off" type="number" step="0.1" min="0" max="80" value="5"></label>
      <label>Amount off USD<input name="amount_off" type="number" step="0.01" min="0" value="0"></label>
      <label>Min subtotal USD<input name="min_subtotal" type="number" step="0.01" min="0" value="0"></label>
      <label>Max discount USD<input name="max_discount" type="number" step="0.01" min="0" value="0"></label>
      <label>Starts at<input name="starts_at" placeholder="2026-07-01"></label>
      <label>Ends at<input name="ends_at" placeholder="2026-12-31"></label>
      <label>Active<input name="active" type="checkbox" checked></label>
      <button class="primary" type="submit">Save</button>
    </form>
  </section>
  <table>
    <thead><tr><th>Code</th><th>Rule</th><th>Window</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($coupons as $coupon): ?>
      <tr>
        <td><strong><?= h($coupon['code'] ?? '') ?></strong><br><span class="muted"><?= h($coupon['label'] ?? '') ?></span></td>
        <td>
          <?= h(($coupon['type'] ?? '') === 'amount' ? '$' . number_format(((int)($coupon['amount_off_cents'] ?? 0)) / 100, 2) . ' off' : (string)($coupon['percent_off'] ?? 0) . '% off') ?><br>
          <span class="muted">Min <?= h(number_format(((int)($coupon['min_subtotal_cents'] ?? 0)) / 100, 2)) ?> / Max <?= h(number_format(((int)($coupon['max_discount_cents'] ?? 0)) / 100, 2)) ?></span>
        </td>
        <td><?= h($coupon['starts_at'] ?? '') ?><br><?= h($coupon['ends_at'] ?? '') ?></td>
        <td><?= !empty($coupon['active']) ? 'Active' : 'Inactive' ?></td>
        <td>
          <form method="post">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="code" value="<?= h($coupon['code'] ?? '') ?>">
            <button class="danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
