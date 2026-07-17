# 燕文开放平台 API 对接设计

Last updated: 2026-07-17  
官方文档入口：https://opendocs.yw56.com.cn/webfile/6993833547773513728/

本文描述 **CRTLU 独立站 → 燕文** 的接口设计、日常用法与能力边界。  
实现：`api/yanwen-client.php` + 后台 `admin/yanwen.php`。  
**正式密钥已连通；P2 一键创建运单 + P4 客户查轨迹已实现（2026-07-17）**。须配置 `YANWEN_CHANNEL_ID`；CSV/手填仍可兜底。

---

## 0. 直接回答运营问题

### 有了 API，还要不要手填快递单号？

| 现状（P2 已上线） | 说明 |
|---|---|
| **可免填（推荐）** | 订单页点 **「创建燕文运单」** → 调 `express.order.create` → 自动写回 `orders.yanwen_tracking`，状态变为 shipped。 |
| 前提 | `YANWEN_USER_ID` + `YANWEN_API_TOKEN` + **`YANWEN_CHANNEL_ID`** 已配置；收件国家/地址完整。 |
| 仍可手填 | 失败时或 CSV 制单时，继续在订单页手填运单号。 |
| 强制重建 | 已有运单号时勾选「强制重建」才会再次创建。 |

### 客户能不能时时查看快递情况？

| 现状（P4 已上线） | 说明 |
|---|---|
| **可查轨迹节点** | 登录 `/account/` → 有运单号的订单点 **View tracking** → 展示 checkpoints。 |
| 接口 | `POST /api/yanwen-track.php`（需会员登录，且订单属于该会员）。 |
| 非推送 | 客户点击时拉取；无扫描时可能暂时无节点。 |

**一句话：** 配好产品 ID 后后台可一键下单；客户登录账户可查看轨迹节点。

---

## 1. 目标能力（分阶段）

| 阶段 | 能力 | 状态 |
|---|---|---|
| P0 | 公共签名客户端 + 配置探测 | **已完成** |
| P1 | 交货仓 / 已开通产品列表 | **已完成**（`express.channel.getlist`） |
| P2 | 订单页一键创建运单 → 写回 tracking | **已完成**（需 `YANWEN_CHANNEL_ID`） |
| P3 | 打印标签 PDF/URL | 方法预留，UI 未做 |
| P4 | 轨迹查询 + 前台账户展示 | **已完成** |
| 兜底 | CSV + 手填运单号 | 仍可用 |

---

## 2. 环境与鉴权（文档已确认）

| 环境 | URL |
|---|---|
| 正式 | `https://open.yw56.com.cn/api/order` |
| 测试 | `https://open-fat.yw56.com.cn/api/order` |

| 配置项 | 含义 |
|---|---|
| 账号 `user_id` | 燕文客户号 |
| 秘钥 `apitoken` | 客户中心 → 账号管理 → 制单账号管理 |

### 签名（文档原文规则）

```text
1) 拼接: user_id + data + format + method + timestamp + version
2) 头尾加 apitoken: apitoken + 上一步 + apitoken
3) MD5 32 位小写
```

- 请求：`POST`，`Content-Type: application/json`
- **公共参数放 URL query**：`user_id, method, format, timestamp, sign, version`
- **业务参数放 body**：紧凑 JSON（即签名里的 `data`）
- `timestamp`：毫秒时间戳

实现：`yanwen_sign()` / `yanwen_request()` in `api/yanwen-client.php`。

---

## 3. 方法清单（开放平台目录）

| 业务 | 设计 method 名（可配置覆盖） | 客户端函数 |
|---|---|---|
| 通达国家 | `common.country.getlist` | `yanwen_country_list()` |
| 交货仓 | `common.warehouse.getlist` | `yanwen_warehouse_list($channelId?)` |
| 已开通产品 | `common.channel.getlist` | `yanwen_channel_list()` |
| 创建运单 | `express.order.create`（可用 `YANWEN_METHOD_CREATE` 覆盖） | `yanwen_create_order()` |
| 打印标签 | `express.order.label.get` | `yanwen_print_label()` |
| 取消运单 | `express.order.cancel` | `yanwen_cancel_order()` |
| 运单详情 | `express.order.get` | `yanwen_order_detail()` |
| 轨迹 | `express.order.track.get` | `yanwen_track()` |

> 若你账号文档中 **创建运单 method 字符串不同**，只改 `config.local.php` 的 `YANWEN_METHOD_*`，无需改业务代码。

---

## 4. 与本站订单模型的映射

### 本站已有

- 表 `orders`：客户、金额、状态、`shipping_address_json`、`yanwen_tracking`、`phone` 等  
- 表 `order_items`：商品名（含插头文案）、数量、单价  
- 人工流：订单页填追踪号；`export-yanwen.php` 导出 CSV  

### 创建运单 payload（`yanwen_build_create_payload`）

从订单行生成骨架字段：

| 燕文字段（设计） | 本站来源 |
|---|---|
| `orderNumber` | `CRTLU-{orders.id}` |
| `channelId` | 配置 `YANWEN_CHANNEL_ID` |
| `warehouseCode` | 配置 `YANWEN_WAREHOUSE_CODE` |
| `receiverName` | `shipping_name` / `customer_name` |
| `phone` / `email` | `phone` / `customer_email` |
| `countryCode/state/city/zipCode/addressLine*` | `shipping_address_json` |
| `goodsList[]` | `order_items`（品名、数量、申报价；重量/HS 码先用默认） |

联调时对照「创建运单」文档，若字段名不一致，只改 `yanwen_build_create_payload()` 一处。

### 成功回写

```text
orders.yanwen_tracking = 燕文运单号 / 追踪号
orders.status = shipped   （可选，建议人工确认后再改）
```

---

## 5. config.local.php 模板（申请通过后粘贴）

```php
// 燕文开放平台（小包专线）
define('YANWEN_USER_ID', '你的客户号');
define('YANWEN_API_TOKEN', '你的apitoken');
// define('YANWEN_API_BASE', 'https://open-fat.yw56.com.cn/api/order'); // 测试
define('YANWEN_API_BASE', 'https://open.yw56.com.cn/api/order');        // 正式
define('YANWEN_API_VERSION', 'V1.0');
define('YANWEN_CHANNEL_ID', '');      // 已开通产品编号，探测产品列表后填
define('YANWEN_WAREHOUSE_CODE', '');  // 交货仓 code，探测仓列表后填
define('YANWEN_DEFAULT_HSCODE', '851762');
// 若 method 名与默认不同再取消注释：
// define('YANWEN_METHOD_CREATE', 'express.order.create');
```

**切勿**把真实 token 提交到 Git。

---

## 6. 「签名错误 / 秘钥已失效」排查

燕文原文示例：`签名错误,可能秘钥已失效,请获取新秘钥对接`（常为 HTTP 401 纯文本，非 JSON）。

| 检查项 | 说明 |
|---|---|
| 签名算法 | 本站已与官方示例一致：`MD5(token+user_id+data+format+method+timestamp+version+token)`，`data` 空业务为 `{}`。用官方沙箱 `100000` + 文档 token 打 `open-fat` 可通。 |
| user_id | 必须是客户中心客户号，与 apitoken **同一制单账号**。 |
| apitoken | 来自「账号管理 → 制单账号管理」秘钥；失效则重置后重填 `YANWEN_API_TOKEN`。 |
| 环境 | 正式密钥 ↔ `open.yw56.com.cn`；测试密钥 ↔ `open-fat.yw56.com.cn`。勿混用。 |
| 粘贴 | 去掉首尾空格/引号/换行；代码侧已 trim。 |

后台「测试连通」失败时会显示燕文原文 + 配置摘要（user_id、token 长度/前后缀、api_base），**不会**输出完整 token。

---

## 7. 以后怎么用（运营手册）

### 7.1 入口

| 页面 | 路径（Serv00） | 用途 |
|---|---|---|
| 燕文 API | `/admin/yanwen.php` | 测试连通、拉国家/产品/仓 |
| 燕文 CSV | `/admin/export-yanwen.php` | 导出待发货订单人工制单 |
| 订单 | `/admin/orders.php` | 改状态、**手填** `yanwen_tracking` |
| 前台账户 | `shop.crtlu.me/account/` | 登录后看到运单号（若已填） |

配置文件（**仅服务器，勿提交 Git**）：`api/config.local.php` 中的 `YANWEN_*`。

### 7.2 一次性配置

1. 已填写 `YANWEN_USER_ID`、`YANWEN_API_TOKEN`、`YANWEN_API_BASE`（正式：`https://open.yw56.com.cn/api/order`）。  
2. 打开 `/admin/yanwen.php` → **测试连通** → 应 success。  
3. **拉取已开通产品** → 把产品编号写入 `YANWEN_CHANNEL_ID`。  
4. **拉取交货仓** → 把仓代码写入 `YANWEN_WAREHOUSE_CODE`。  
5. 可选：`YANWEN_DEFAULT_HSCODE`（默认海关编码）。

### 7.3 当前每笔订单发货流程（正式路径 · P2）

```text
客户 Stripe 付款成功
  → 后台订单列表看到已付款
  → 确认 YANWEN_CHANNEL_ID 已配置（燕文 API 页拉产品）
  → 订单行点「创建燕文运单」
  → 成功：yanwen_tracking 写回，状态 → shipped
  → 客户登录 /account/ 见运单号，点 View tracking 看轨迹
```

**兜底：** 导出燕文 CSV / 客户中心制单 → 订单页手填运单号。


### 7.4 代码调用（开发用）

```php
require_once __DIR__ . '/yanwen-client.php';

yanwen_country_list();
yanwen_channel_list();
yanwen_warehouse_list($channelId);
yanwen_create_order($payload);   // P2
yanwen_print_label($payload);    // P3
yanwen_track($payload);          // P4
yanwen_cancel_order($payload);

$payload = yanwen_build_create_payload($orderRow, $itemRows);
```

签名与 POST 封装在 `yanwen_request()`；method 名可用 `YANWEN_METHOD_*` 覆盖。

### 7.5 密钥失效时

后台再测连通；若「签名错误 / 秘钥已失效」→ 客户中心 **制单账号管理** 重取 apitoken → 更新 `config.local.php` → 再测。详见上文 §6 排查表。

---

## 8. 后台联调步骤（摘要）

1. 打开 `https://api.crtlu.me/admin/yanwen.php`（或实际 admin 域名）  
2. 点「测试连通 / 国家列表」→ 应 success  
3. 拉「产品列表」「交货仓」→ 写入 config  
4. **待开发**：订单页「创建燕文运单」→ 写回 `yanwen_tracking`  
5. **待开发**：标签打印 / 轨迹查询 UI + 前台展示  

在此之前 CSV + 手填运单号仍是正式流程。

---

## 9. 安全与风控

- 仅 admin 登录后可调用燕文客户端  
- 不在前端 / Cloudflare Pages 暴露 apitoken  
- 可选后续加 `yanwen_api_logs` 记请求摘要  
- 沙箱：`open-fat` + 文档测试号；正式：`open` + 真实客户号，勿混用  

---

## 10. 验收清单

### 已完成（2026-07-17）

- [x] 正式 `YANWEN_USER_ID` / `YANWEN_API_TOKEN` 配置在 Serv00  
- [x] `yanwen_probe()` / 国家列表 success（签名正确）  

### 已完成（P2 / P4 代码 · 2026-07-17）

- [x] 官方创建字段映射（receiverInfo / parcelInfo / productList）  
- [x] 订单页「创建燕文运单」+ 写回 tracking  
- [x] 轨迹网关 + 账户 View tracking  
- [ ] **运营**：写入 `YANWEN_CHANNEL_ID`（否则创建按钮禁用）  
- [ ] **运营**：可选 `YANWEN_WAREHOUSE_CODE`  
- [ ] 打印标签 UI（P3）  
- [ ] 用真实已付款订单做一票端到端验收  


---

## 11. 与前台的关系

| 能力 | 接口 | 现状 |
|---|---|---|
| 展示运单号 | `orders.yanwen_tracking` | 账户有则显示 |
| 自动创建运单 | `yanwen_fulfill_shop_order` / 订单页 | **P2 已接** |
| 轨迹节点 | `yanwen_track` + `/api/yanwen-track.php` | **P4 已接**（点击拉取） |
| 打印标签 | `yanwen_print_label` | 方法预留 |

**部署：** PHP 上传 Serv00；账户页前端随 Cloudflare Pages。创建密钥勿提交 Git。
