(() => {
  'use strict';

  const motion = (window.Motion && window.Motion.animate) ? window.Motion : null;
  const animate = motion ? motion.animate : null;

  // --- Live timer ticker ---------------------------------------------------
  const fmt = (totalSeconds) => {
    const s = Math.max(0, totalSeconds | 0);
    const h = String((s / 3600) | 0).padStart(2, '0');
    const m = String(((s % 3600) / 60) | 0).padStart(2, '0');
    const sec = String(s % 60).padStart(2, '0');
    return `${h}:${m}:${sec}`;
  };

  const tickTimers = () => {
    document.querySelectorAll('[data-timer-elapsed]').forEach((el) => {
      const startedISO = el.dataset.started;
      if (!startedISO) return;
      // SQLite CURRENT_TIMESTAMP is UTC, "YYYY-MM-DD HH:MM:SS" — make it explicit
      const iso = startedISO.includes('T') ? startedISO : startedISO.replace(' ', 'T') + 'Z';
      const started = new Date(iso).getTime();
      if (Number.isNaN(started)) return;
      const elapsed = (Date.now() - started) / 1000;
      el.textContent = fmt(elapsed);
    });
  };
  tickTimers();
  setInterval(tickTimers, 1000);

  // --- Form submit: spinner + disable -------------------------------------
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.noLoading === '1') return;
    const btn = form.querySelector('button[type="submit"], button:not([type])');
    if (!btn || btn.disabled) return;
    const original = btn.innerHTML;
    const label = btn.dataset.loadingLabel || 'Working…';
    btn.disabled = true;
    btn.dataset.originalLabel = original;
    btn.innerHTML = `
      <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"/>
        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
      </svg>
      <span>${label}</span>`;
    // safety: re-enable after 8s in case browser doesn't unload (back button, etc.)
    setTimeout(() => {
      if (btn.disabled) { btn.disabled = false; btn.innerHTML = original; }
    }, 8000);
  });

  // --- Toast helper (used for any future fetch-based flows) ---------------
  window.cosToast = (msg, kind = 'success') => {
    const region = document.getElementById('toast-region');
    if (!region) return;
    const el = document.createElement('div');
    el.className = 'card pointer-events-auto shadow-card max-w-sm w-full text-center ' +
      (kind === 'error' ? 'bg-rust-500 text-cream' : '');
    el.textContent = msg;
    region.appendChild(el);
    if (animate) {
      animate(el, { opacity: [0, 1], transform: ['translateY(20px)', 'translateY(0)'] }, { duration: 0.35, easing: [0.16, 1, 0.3, 1] });
      setTimeout(() => {
        animate(el, { opacity: [1, 0], transform: ['translateY(0)', 'translateY(20px)'] }, { duration: 0.25 })
          .finished.then(() => el.remove());
      }, 2400);
    } else {
      setTimeout(() => el.remove(), 2400);
    }
  };

  // --- Count-up numbers ---------------------------------------------------
  const countUp = (el) => {
    const target = parseFloat(el.dataset.countUp);
    if (Number.isNaN(target)) return;
    const decimals = parseInt(el.dataset.countDecimals || '0', 10);
    const prefix = el.dataset.countPrefix || '';
    const suffix = el.dataset.countSuffix || '';
    const duration = parseFloat(el.dataset.countDuration || '0.9');

    if (!animate) {
      el.textContent = `${prefix}${target.toFixed(decimals)}${suffix}`;
      return;
    }
    animate(0, target, {
      duration,
      easing: [0.16, 1, 0.3, 1],
      onUpdate: (v) => {
        el.textContent = `${prefix}${v.toFixed(decimals)}${suffix}`;
      },
    });
  };
  document.querySelectorAll('[data-count-up]').forEach(countUp);

  // --- Tabs (data-tab + data-tab-target) ----------------------------------
  document.querySelectorAll('[data-tabs]').forEach((root) => {
    const buttons = root.querySelectorAll('[data-tab]');
    const setActive = (key) => {
      buttons.forEach((b) => {
        const isOn = b.dataset.tab === key;
        b.className = isOn ? 'tab-active' : 'tab-inactive';
      });
      root.querySelectorAll('[data-tab-panel]').forEach((p) => {
        const on = p.dataset.tabPanel === key;
        p.hidden = !on;
        if (on && animate) {
          animate(p, { opacity: [0, 1], transform: ['translateY(8px)', 'translateY(0)'] }, { duration: 0.25 });
        }
      });
      try { history.replaceState(null, '', `#${key}`); } catch (_) {}
    };
    buttons.forEach((b) => b.addEventListener('click', () => setActive(b.dataset.tab)));
    const initial = (location.hash || '').replace('#', '') || (buttons[0] && buttons[0].dataset.tab);
    if (initial) setActive(initial);
  });

  // --- Color picker swatches ----------------------------------------------
  document.querySelectorAll('[data-color-picker]').forEach((root) => {
    const input = root.querySelector('input[type="hidden"]');
    const preview = root.querySelector('[data-color-preview]');
    const sync = (val) => {
      if (input) input.value = val;
      if (preview) preview.style.backgroundColor = val;
      root.querySelectorAll('[data-swatch]').forEach((s) => {
        s.classList.toggle('ring-4', s.dataset.swatch === val);
        s.classList.toggle('ring-rust-300', s.dataset.swatch === val);
      });
    };
    root.querySelectorAll('[data-swatch]').forEach((s) => {
      s.addEventListener('click', () => sync(s.dataset.swatch));
    });
    if (input) sync(input.value);
  });

  // --- Hamburger menu (data-menu) -----------------------------------------
  document.querySelectorAll('[data-menu]').forEach((root) => {
    const toggle = root.querySelector('[data-menu-toggle]');
    const panel = root.querySelector('[data-menu-panel]');
    if (!toggle || !panel) return;

    const setOpen = (open) => {
      panel.classList.toggle('hidden', !open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      setOpen(panel.classList.contains('hidden'));
    });

    document.addEventListener('click', (e) => {
      if (!root.contains(e.target)) setOpen(false);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setOpen(false);
    });
  });
})();
