# WhatsApp Chat Setup

Last updated: 2026-07-07

The storefront includes a floating WhatsApp chat button. It is intentionally configuration-driven so the phone number is not hardcoded into source files.

## Cloudflare Pages Variable

Set this environment variable in Cloudflare Pages:

```text
PUBLIC_CRTLU_WHATSAPP_NUMBER=819012345678
```

Use the real WhatsApp number in international format:

- Japan example: `819012345678`
- Taiwan example: `886912345678`
- China example: `8613812345678`

Do not include:

- `+`
- spaces
- brackets
- hyphens

## Where It Appears

When `PUBLIC_CRTLU_WHATSAPP_NUMBER` is configured, WhatsApp entry points appear in:

- Global floating button on every storefront page.
- Footer customer support area.
- Contact page.

If the variable is empty or missing, the WhatsApp UI is hidden automatically.

## Source Files

- `src/lib/contact.ts`: support email, WhatsApp number normalization, WhatsApp URL helper.
- `src/components/WhatsAppChat.astro`: floating WhatsApp chat widget.
- `src/layouts/Layout.astro`: sitewide widget injection.
- `src/components/Footer.astro`: footer WhatsApp link.
- `src/pages/contact.astro`: contact page WhatsApp link.
