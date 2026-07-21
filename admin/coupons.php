<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';

crtlu_require_admin();

$path = dirname(__DIR__) . '/data/coupons.json';

function coupon_data(string $path): array
{
    $decoded = is_readable($path) ? json_decode((string)file_get_contents($path), true) : [];
    return is_array($decoded) ? $decoded : ['coupons' => []];
}

function save_coupon_data(string $path, array $data): void
{
    $data['coupons'] = array_values($data['coupons'] ?? []);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
}

$message = '';
$messageError = false;
$data = coupon_data($path);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save');
    $code = strtoupper(trim(preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''))));
    if ($code === '') {
        $message = '请填写优惠码。';
        $messageError = true;
    } elseif ($action === 'delete') {
        $data['coupons'] = array_values(array_filter($data['coupons'] ?? [], static fn(array $coupon): bool => strtoupper((string)($coupon['code'] ?? '')) !== $code));
        save_coupon_data($path, $data);
        $message = '优惠码已删除。';
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
        $message = '优惠码已保存。';
    }
}

$data = coupon_data($path);
$coupons = $data['coupons'] ?? [];

crtlu_admin_header('优惠券', '管理结账优惠码（写入 data/coupons.json）。');
?>
  <?php if ($message): ?><p class="message<?= $messageError ? ' error' : '' ?>"><?= htmlspecialchars($message) ?></p><?php endif; ?>

  <section class="panel">
    <h2 style="margin:0 0 12px;font-size:18px;">新建 / 更新优惠码</h2>
    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;align-items:end;">
      <input type="hidden" name="action" value="save">
      <label>优惠码<input name="code" placeholder="WELCOME5" required></label>
      <label>显示名称<input name="label" placeholder="新客 5% off"></label>
      <label>类型
        <select name="type">
          <option value="percent">百分比</option>
          <option value="amount">固定金额 (USD)</option>
        </select>
      </label>
      <label>折扣百分比<input name="percent_off" type="number" step="0.1" min="0" max="80" value="5"></label>
      <label>固定减免 USD<input name="amount_off" type="number" step="0.01" min="0" value="0"></label>
      <label>最低小计 USD<input name="min_subtotal" type="number" step="0.01" min="0" value="0"></label>
      <label>最高优惠 USD<input name="max_discount" type="number" step="0.01" min="0" value="0"></label>
      <label>开始日期<input name="starts_at" placeholder="2026-07-01"></label>
      <label>结束日期<input name="ends_at" placeholder="2026-12-31"></label>
      <label style="display:flex;align-items:center;gap:8px;flex-direction:row;color:#c8d7df;">
        <input name="active" type="checkbox" checked> 启用
      </label>
      <button class="button primary" type="submit">保存</button>
    </form>
  </section>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>优惠码</th>
          <th>规则</th>
          <th>有效期</th>
          <th>状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($coupons as $coupon): ?>
        <tr>
          <td><strong><?= htmlspecialchars((string)($coupon['code'] ?? '')) ?></strong><br><span class="muted"><?= htmlspecialchars((string)($coupon['label'] ?? '')) ?></span></td>
          <td>
            <?= htmlspecialchars(($coupon['type'] ?? '') === 'amount'
              ? '$' . number_format(((int)($coupon['amount_off_cents'] ?? 0)) / 100, 2) . ' 减免'
              : (string)($coupon['percent_off'] ?? 0) . '% 折扣') ?><br>
            <span class="muted">最低 $<?= htmlspecialchars(number_format(((int)($coupon['min_subtotal_cents'] ?? 0)) / 100, 2)) ?> / 上限 $<?= htmlspecialchars(number_format(((int)($coupon['max_discount_cents'] ?? 0)) / 100, 2)) ?></span>
          </td>
          <td><?= htmlspecialchars((string)($coupon['starts_at'] ?? '—')) ?><br><?= htmlspecialchars((string)($coupon['ends_at'] ?? '—')) ?></td>
          <td><?= !empty($coupon['active']) ? '启用' : '停用' ?></td>
          <td>
            <form method="post" onsubmit="return confirm('确定删除此优惠码？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="code" value="<?= htmlspecialchars((string)($coupon['code'] ?? '')) ?>">
              <button class="button danger" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$coupons): ?>
        <tr><td colspan="5" class="muted">暂无优惠码。</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php crtlu_admin_footer(); ?>
