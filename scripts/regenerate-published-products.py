#!/usr/bin/env python3
"""Regenerate docs/published-products.md + docs/spec-sku-audit.md from data/catalog.json."""
import json, re
from pathlib import Path
from datetime import date

ROOT = Path(__file__).resolve().parents[1]
cat = json.loads((ROOT / 'data/catalog.json').read_text(encoding='utf-8'))
series_list = cat.get('series') or []
today = date.today().isoformat()

def money(cents):
    return f"${int(cents)/100:.2f}"

def norm_label(x):
    x = str(x).lower().replace('＋', '+')
    return re.sub(r'\s+', ' ', x).strip()

changed = []
for s in series_list:
    variants = s.get('variants') or []
    if not variants:
        continue
    labels = [v.get('label') or 'Standard' for v in variants]
    cents = [int(v['price_cents']) for v in variants if v.get('price_cents') is not None]
    specs = dict(s.get('specs') or {})
    conf_new = ' / '.join(labels)
    conf_old = str(specs.get('configurations') or '')
    if conf_old.strip() != conf_new.strip():
        if not conf_old or set(map(norm_label, re.split(r'\s*/\s*', conf_old))) != set(map(norm_label, labels)):
            specs['configurations'] = conf_new
            changed.append((s['id'], 'configurations', conf_old, conf_new))
    if cents:
        start_new = money(min(cents))
        start_old = str(specs.get('starting_price') or '')
        m = re.search(r'\$?\s*([0-9]+(?:\.[0-9]{2})?)', start_old)
        old_val = float(m.group(1)) if m else None
        new_val = min(cents) / 100
        if old_val is None or abs(old_val - new_val) > 0.001:
            specs['starting_price'] = start_new
            changed.append((s['id'], 'starting_price', start_old, start_new))
    if specs != (s.get('specs') or {}):
        s['specs'] = specs

if changed:
    cat['updated'] = today
    text = json.dumps(cat, ensure_ascii=False, indent=2) + '\n'
    (ROOT / 'data/catalog.json').write_text(text, encoding='utf-8')
    pub = ROOT / 'public/data/catalog.json'
    if pub.parent.exists():
        pub.write_text(text, encoding='utf-8')

# audit
audit = [
    '# Spec / SKU audit', '', f'Generated: {today} from `data/catalog.json`', '',
    '## Summary', '',
    f'- Series: **{len(series_list)}**',
    f'- Variants: **{sum(len(s.get("variants") or []) for s in series_list)}**',
    f'- Specs auto-synced this run: **{len(changed)}** field updates', '',
    '## Rules checked', '',
    '1. `specs.configurations` lists match variant `label`s.',
    '2. `specs.starting_price` equals lowest `price_cents`.',
    '3. Every variant has `sku`, `label`, `price_cents`.', '',
    '## Issues remaining (manual review)', '',
]
remaining = []
for s in series_list:
    for v in s.get('variants') or []:
        if not v.get('sku'):
            remaining.append(f"- `{s['id']}` missing SKU on `{v.get('id')}`")
        if v.get('price_cents') is None:
            remaining.append(f"- `{s['id']}` `{v.get('sku')}` missing price_cents")
if remaining:
    audit.extend(remaining)
else:
    audit.append('_None. Configurations and starting prices are aligned with live variants._')
audit += ['', '## Auto-sync log', '']
if changed:
    for sid, field, old, new in changed:
        audit.append(f'- `{sid}` **{field}**: `{old}` → `{new}`')
else:
    audit.append('_No field changes required._')
(ROOT / 'docs/spec-sku-audit.md').write_text('\n'.join(audit) + '\n', encoding='utf-8')

# matrix
lines = [
    '# Published Product Matrix', '', f'Last updated: {today}', '',
    'Auto-generated from `data/catalog.json` (source of truth). Do not hand-edit prices here — change catalog then run:',
    '', '```bash', 'python3 scripts/regenerate-published-products.py', '```', '',
    '## Published Product Lines', '',
    '| Product Line | Brand | Tier | Category | Published Configurations | Variant Count | From |',
    '|---|---|---|---|---|---:|---:|',
]
for s in series_list:
    if s.get('status') and s.get('status') not in ('published', 'publish'):
        continue
    variants = s.get('variants') or []
    confs = '<br>'.join(v.get('label') or 'Standard' for v in variants) or '—'
    cents = [int(v['price_cents']) for v in variants if v.get('price_cents') is not None]
    from_p = money(min(cents)) if cents else '—'
    lines.append(f"| {s.get('name','')} | {s.get('brand','')} | {s.get('tier','')} | {s.get('category','')} | {confs} | {len(variants)} | {from_p} |")

lines += ['', '## Published Variants', '',
    '| Product Line | SKU | Configuration | Cost RMB | Active Price USD | Compare-at USD | Product ID |',
    '|---|---|---|---:|---:|---:|---|']
for s in series_list:
    if s.get('status') and s.get('status') not in ('published', 'publish'):
        continue
    for v in s.get('variants') or []:
        price = money(v['price_cents']) if v.get('price_cents') is not None else '—'
        compare = money(v['compare_at_cents']) if v.get('compare_at_cents') is not None else '—'
        rmb = v.get('rmb_price')
        rmb_s = str(rmb) if rmb is not None else '—'
        lines.append(f"| {s.get('name','')} | {v.get('sku','')} | {v.get('label','')} | {rmb_s} | {price} | {compare} | {v.get('id','')} |")

lines += ['', '## Notes', '',
    '- Active prices reflect the 2026-07-16 tiered promo (≥$40 ≈ −10% to x.99; under $40 protected).',
    '- Guest checkout shipping remains separate (typically $12).',
    '- See `docs/pricing-system.md` and `docs/spec-sku-audit.md`.', '']
(ROOT / 'docs/published-products.md').write_text('\n'.join(lines), encoding='utf-8')
print(f'OK series={len(series_list)} changed={len(changed)}')
