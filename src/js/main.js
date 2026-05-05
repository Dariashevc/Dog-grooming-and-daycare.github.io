/* =============================================
   HAPPY TAILS — SHARED JAVASCRIPT
   ============================================= */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Sticky nav ──────────────────────────── */
  const nav = document.querySelector('.site-nav');
  const onScroll = () => {
    nav.classList.toggle('scrolled', window.scrollY > 30);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── Mobile hamburger ────────────────────── */
  const hamburger   = document.querySelector('.nav-hamburger');
  const mobileNav   = document.querySelector('.mobile-nav');
  const mobileSvcBtn = document.querySelector('.mobile-service-btn');
  const mobileSub   = document.querySelector('.mobile-sub');

  hamburger?.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileNav.classList.toggle('open');
    document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
  });

  mobileSvcBtn?.addEventListener('click', () => {
    mobileSub.style.display = mobileSub.style.display === 'block' ? 'none' : 'block';
    mobileSvcBtn.querySelector('.chevron').textContent =
      mobileSub.style.display === 'block' ? '▲' : '▼';
  });

  // Close mobile nav on link click
  mobileNav?.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      hamburger.classList.remove('open');
      mobileNav.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  /* ── Set active nav link ─────────────────── */
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .mobile-nav a').forEach(a => {
    const href = a.getAttribute('href');
    if (href && (href === currentPage || (currentPage === '' && href === 'index.html'))) {
      a.classList.add('active');
    }
  });

  /* ── Scroll reveal ───────────────────────── */
  const observer = new IntersectionObserver(
    (entries) => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); }),
    { threshold: 0.12 }
  );
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  /* ── Toast utility ───────────────────────── */
  window.showToast = (message, icon = '✅') => {
    let t = document.querySelector('.toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'toast';
      document.body.appendChild(t);
    }
    t.innerHTML = `<span class="toast-icon">${icon}</span><span>${message}</span>`;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
  };
});
