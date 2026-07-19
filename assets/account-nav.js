/**
 * Sitewide account menu (top-right avatar).
 * Hydrates login state from /api/account.php with credentials.
 */
(function () {
  const root = document.getElementById('accountNav');
  if (!root) return;

  const trigger = document.getElementById('accountNavTrigger');
  const menu = document.getElementById('accountNavMenu');
  const avatarIcon = document.getElementById('accountNavIcon');
  const avatarInitial = document.getElementById('accountNavInitial');
  const menuHead = document.getElementById('accountNavMenuHead');
  const menuAvatar = document.getElementById('accountNavMenuAvatar');
  const menuName = document.getElementById('accountNavMenuName');
  const menuEmail = document.getElementById('accountNavMenuEmail');
  const signOutBtn = document.getElementById('accountNavSignOut');
  const signInLink = document.getElementById('accountNavSignIn');

  function apiUrl(path) {
    if (window.crtluApiUrl) return window.crtluApiUrl(path);
    return path;
  }

  function initialFromMember(member) {
    const name = String(member?.name || '').trim();
    if (name) return name.charAt(0).toUpperCase();
    const email = String(member?.email || '').trim();
    if (email) return email.charAt(0).toUpperCase();
    return '?';
  }

  function setOpen(open) {
    if (!menu || !trigger) return;
    menu.hidden = !open;
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function renderGuest() {
    root.classList.remove('is-authenticated');
    if (avatarIcon) avatarIcon.hidden = false;
    if (avatarInitial) {
      avatarInitial.hidden = true;
      avatarInitial.textContent = '';
    }
    if (menuHead) menuHead.hidden = true;
    if (signOutBtn) signOutBtn.hidden = true;
    if (signInLink) signInLink.hidden = false;
    if (menuName) menuName.textContent = 'Guest';
    if (menuEmail) menuEmail.textContent = '';
    trigger?.setAttribute('aria-label', 'Account — Sign in');
  }

  function renderMember(member) {
    const initial = initialFromMember(member);
    const email = String(member?.email || '');
    const name = String(member?.name || '').trim() || email.split('@')[0] || 'Member';

    root.classList.add('is-authenticated');
    if (avatarIcon) avatarIcon.hidden = true;
    if (avatarInitial) {
      avatarInitial.hidden = false;
      avatarInitial.textContent = initial;
    }
    if (menuHead) menuHead.hidden = false;
    if (menuAvatar) menuAvatar.textContent = initial;
    if (menuName) menuName.textContent = name;
    if (menuEmail) menuEmail.textContent = email;
    if (signOutBtn) signOutBtn.hidden = false;
    if (signInLink) signInLink.hidden = true;
    trigger?.setAttribute('aria-label', `Account — ${email || name}`);
  }

  async function refresh() {
    try {
      const res = await fetch(apiUrl('/api/account.php'), { credentials: 'include' });
      const data = await res.json();
      if (data && data.authenticated && data.member) {
        renderMember(data.member);
        window.__crtluMember = data.member;
      } else {
        renderGuest();
        window.__crtluMember = null;
      }
    } catch {
      renderGuest();
      window.__crtluMember = null;
    }
  }

  trigger?.addEventListener('click', (event) => {
    event.stopPropagation();
    // Open when currently hidden; close when open.
    setOpen(!!menu?.hidden);
  });

  document.addEventListener('click', (event) => {
    if (!root.contains(event.target)) setOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
  });

  signOutBtn?.addEventListener('click', async () => {
    try {
      await fetch(apiUrl('/api/account-logout.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: '{}',
      });
    } catch {
      // still clear UI
    }
    renderGuest();
    setOpen(false);
    // If already on account page, reload to show login form
    if (location.pathname.replace(/\/+$/, '') === '/account') {
      location.reload();
    }
  });

  // Expose for account page after login/logout
  window.crtluRefreshAccountNav = refresh;

  renderGuest();
  refresh();
})();
