/*
 * RMU shared UI behaviours.
 *
 * Button loading state (#5): on click/submit a button is disabled and shows a
 * spinner, preventing double-submits and signalling progress.
 *
 *  - Any <form> submit auto-disables its submit button(s) with a spinner.
 *  - Any element with [data-loading] shows the spinner on click (with a
 *    fail-safe reset in case no navigation/callback follows).
 *  - window.rmuBtnLoading(el, on) toggles the state manually from AJAX handlers.
 */
(function () {
  'use strict';

  function setLoading(el, on) {
    if (!el) return;
    if (on !== false) {
      if (el.getAttribute('data-rmu-loading') === '1') return;
      el.setAttribute('data-rmu-loading', '1');
      el.setAttribute('aria-busy', 'true');
      el.classList.add('is-loading');
      if ('disabled' in el) { el.disabled = true; } else { el.setAttribute('aria-disabled', 'true'); }
    } else {
      el.removeAttribute('data-rmu-loading');
      el.removeAttribute('aria-busy');
      el.classList.remove('is-loading');
      if ('disabled' in el) { el.disabled = false; } else { el.removeAttribute('aria-disabled'); }
    }
  }
  window.rmuBtnLoading = setLoading;

  // Auto loader for real form submissions (login, register, etc.). Disabling in
  // the submit handler still submits the button's value, and the subsequent
  // navigation/redirect resets the control.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.nodeName !== 'FORM') return;
    var controls = form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])');
    Array.prototype.forEach.call(controls, function (b) { setLoading(b, true); });
  }, true);

  // Opt-in loader for AJAX/action buttons that don't submit a form.
  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-loading]') : null;
    if (!el || el.getAttribute('data-rmu-loading') === '1') return;
    setLoading(el, true);
    // Fail-safe: release if nothing (navigation / callback) has reset it.
    window.setTimeout(function () { setLoading(el, false); }, 12000);
  }, true);

  // Mobile off-canvas sidebar backdrop (#1, #6): dim the page when the sidebar
  // is open and let a tap outside (or Escape) close it. Works in every portal
  // area since the sidebar toggles a single `.open` class.
  document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('rmu-sidebar');
    if (!sidebar) return;
    var backdrop = document.createElement('div');
    backdrop.className = 'rmu-sidebar-backdrop';
    document.body.appendChild(backdrop);

    var close = function () { sidebar.classList.remove('open'); };
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) close();
    });

    var sync = function () {
      document.body.classList.toggle('rmu-sidebar-open', sidebar.classList.contains('open'));
    };
    new MutationObserver(sync).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    sync();
  });

  // ── Profile menu: make the avatar toggle keyboard-operable (a11y) ──────────
  // The markup ships a non-focusable <div id="profile-toggle">; enhance it into
  // a real button in-place so Enter/Space open the menu and screen readers
  // announce it, without touching every role's header partial.
  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('profile-toggle');
    var dd     = document.getElementById('profile-dropdown');
    if (!toggle) return;
    if (!toggle.hasAttribute('role'))     toggle.setAttribute('role', 'button');
    if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
    toggle.setAttribute('aria-haspopup', 'menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        toggle.click();
      }
    });
    if (dd) {
      new MutationObserver(function () {
        toggle.setAttribute('aria-expanded', dd.classList.contains('open') ? 'true' : 'false');
      }).observe(dd, { attributes: true, attributeFilter: ['class'] });
    }
  });

  // ── Skip link + main-content anchor + consistent footer (a11y / content) ───
  // Every portal page renders a <header id="rmu-header">, so we can insert a
  // "Skip to content" link as the first focusable element, drop a focus anchor
  // right after the header, and append a standard footer to any page that
  // lacks one (the claimant pages already ship _footer.php).
  document.addEventListener('DOMContentLoaded', function () {
    var header = document.getElementById('rmu-header');

    if (header && !document.querySelector('.rmu-skip-link')) {
      var anchor = document.getElementById('rmu-main');
      if (!anchor) {
        anchor = document.createElement('span');
        anchor.id = 'rmu-main';
        anchor.tabIndex = -1;
        header.insertAdjacentElement('afterend', anchor);
      }
      var skip = document.createElement('a');
      skip.className = 'rmu-skip-link';
      skip.href = '#rmu-main';
      skip.textContent = 'Skip to content';
      skip.addEventListener('click', function (e) {
        e.preventDefault();
        anchor.focus();
        anchor.scrollIntoView({ block: 'start' });
      });
      document.body.insertBefore(skip, document.body.firstChild);
    }

    if (!document.querySelector('footer')) {
      var host = header ? header.parentNode
                        : document.querySelector('.main-panel, .body-wrapper');
      if (host) {
        var f = document.createElement('footer');
        f.className = 'rmu-footer';
        f.textContent = '© ' + new Date().getFullYear() +
          ' Regional Maritime University · Claims Management System';
        host.appendChild(f);
      }
    }
  });

  // ── Focus management for custom modals (a11y, WCAG 2.4.3) ──────────────────
  // The .rmu-modal-backdrop dialogs open by toggling a `.open` class. Move
  // focus into the dialog on open, trap Tab within it, and restore focus to the
  // trigger on close.
  document.addEventListener('DOMContentLoaded', function () {
    var SEL = 'a[href],button:not([disabled]),textarea:not([disabled]),' +
              'input:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])';
    var restoreTo = null;

    function focusables(modal) {
      return Array.prototype.slice.call(modal.querySelectorAll(SEL))
        .filter(function (el) { return el.offsetParent !== null; });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var modal = document.querySelector('.rmu-modal-backdrop.open');
      if (!modal) return;
      var f = focusables(modal);
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    Array.prototype.forEach.call(document.querySelectorAll('.rmu-modal-backdrop'), function (modal) {
      new MutationObserver(function () {
        if (modal.classList.contains('open')) {
          restoreTo = document.activeElement;
          var f = focusables(modal);
          if (f.length) f[0].focus();
        } else if (restoreTo && restoreTo.focus) {
          restoreTo.focus();
          restoreTo = null;
        }
      }).observe(modal, { attributes: true, attributeFilter: ['class'] });
    });
  });
})();
