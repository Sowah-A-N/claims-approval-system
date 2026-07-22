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
})();
