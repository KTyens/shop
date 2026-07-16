import { SITE_URL } from '../lib/seo';

/**
 * Crawler policy: index storefront; keep account/checkout private.
 * Explicit Allow for major AI crawlers so product facts can appear in AI answers
 * (subject to each vendor’s opt-out policy — we choose opt-in for discovery).
 */
export function GET() {
  const lines = [
    '# CRTLU Digital storefront',
    'User-agent: *',
    'Allow: /',
    'Disallow: /account/',
    'Disallow: /success/',
    '',
    '# AI / assistant crawlers (product discovery)',
    'User-agent: GPTBot',
    'Allow: /',
    '',
    'User-agent: ChatGPT-User',
    'Allow: /',
    '',
    'User-agent: Google-Extended',
    'Allow: /',
    '',
    'User-agent: ClaudeBot',
    'Allow: /',
    '',
    'User-agent: anthropic-ai',
    'Allow: /',
    '',
    'User-agent: PerplexityBot',
    'Allow: /',
    '',
    'User-agent: Applebot-Extended',
    'Allow: /',
    '',
    'User-agent: Bytespider',
    'Allow: /',
    '',
    `Sitemap: ${SITE_URL}/sitemap.xml`,
    `# Machine-readable store summary for LLMs`,
    `# ${SITE_URL}/llms.txt`,
    '',
  ];

  return new Response(lines.join('\n'), {
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
    },
  });
}
