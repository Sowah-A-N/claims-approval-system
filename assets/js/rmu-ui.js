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

  // ── Auto-associate visible labels with their controls (a11y, WCAG 1.3.1/4.1.2) ──
  // Many .rmu-form-group blocks show a <label class="rmu-label"> but never linked it
  // to the field. Link each unlinked label to its control so screen readers announce
  // it, and give bare checkboxes an accessible name. Idempotent (skips label[for]).
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.rmu-form-group').forEach(function (g) {
      var label = g.querySelector('label:not([for])');
      if (!label) return;
      var ctl = g.querySelector('input:not([type="hidden"]), select, textarea');
      if (!ctl) return;
      if (!ctl.id) ctl.id = 'fld-' + Math.random().toString(36).slice(2, 8);
      label.setAttribute('for', ctl.id);
    });
    document.querySelectorAll('input[type="checkbox"]:not([aria-label])').forEach(function (cb) {
      if (cb.labels && cb.labels.length) return;
      cb.setAttribute('aria-label', cb.getAttribute('title') || (cb.closest('th') ? 'Select all rows' : 'Select row'));
    });
  });

  // ── Reusable table search + sort (opt-in: <table class="rmu-table" data-enhance>) ──
  // Charter: tables should support search and sort. This enhances any opted-in
  // table without per-page code. Search filters visible rows; headers become
  // keyboard-operable sort controls with aria-sort. Empty-state rows (a single
  // colspan cell) are never filtered or sorted. On server-paginated tables it
  // operates within the loaded page.
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('table.rmu-table[data-enhance]').forEach(function (table) {
      var head = table.tHead && table.tHead.rows[0];
      var body = table.tBodies[0];
      if (!head || !body) return;
      var colCount = head.cells.length;
      var wrap = table.closest('.rmu-table-wrap') || table;

      function dataRows() {
        return Array.prototype.filter.call(body.rows, function (r) {
          return !r.querySelector('td[colspan]') && r.cells.length >= colCount - 1;
        });
      }

      // Search box (unless the page opted out with data-search="off")
      if (table.getAttribute('data-search') !== 'off') {
        var id = 'ts-' + Math.random().toString(36).slice(2, 8);
        var bar = document.createElement('div');
        bar.className = 'rmu-table-search';
        bar.innerHTML =
          '<label class="rmu-sr-only" for="' + id + '">Search this table</label>' +
          '<i class="ti ti-search" aria-hidden="true"></i>' +
          '<input id="' + id + '" type="search" class="rmu-input" placeholder="Search…" autocomplete="off">';
        wrap.parentNode.insertBefore(bar, wrap);
        var empty = document.createElement('div');
        empty.className = 'rmu-table-empty';
        empty.hidden = true;
        empty.textContent = 'No rows match your search.';
        wrap.parentNode.insertBefore(empty, wrap.nextSibling);
        bar.querySelector('input').addEventListener('input', function () {
          var q = this.value.trim().toLowerCase(), vis = 0;
          dataRows().forEach(function (r) {
            var show = !q || r.textContent.toLowerCase().indexOf(q) !== -1;
            r.style.display = show ? '' : 'none';
            if (show) vis++;
          });
          empty.hidden = !(q && vis === 0);
        });
      }

      // Sortable, keyboard-operable headers
      Array.prototype.forEach.call(head.cells, function (th, i) {
        if (th.getAttribute('data-sort') === 'off') return;
        th.classList.add('rmu-th-sort');
        th.tabIndex = 0;
        th.setAttribute('role', 'button');
        th.setAttribute('aria-sort', 'none');
        var dir = 0;
        function sortNow() {
          dir = dir === 1 ? -1 : 1;
          Array.prototype.forEach.call(head.cells, function (o) { if (o !== th) o.setAttribute('aria-sort', 'none'); });
          th.setAttribute('aria-sort', dir === 1 ? 'ascending' : 'descending');
          var rows = dataRows();
          rows.sort(function (a, b) {
            var x = (a.cells[i] ? a.cells[i].textContent : '').trim();
            var y = (b.cells[i] ? b.cells[i].textContent : '').trim();
            // dd/mm/yyyy → sortable timestamp
            var dm = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            var mx = x.match(dm), my = y.match(dm);
            if (mx && my) return dir * (new Date(mx[3], mx[2] - 1, mx[1]) - new Date(my[3], my[2] - 1, my[1]));
            var nx = parseFloat(x.replace(/[^0-9.\-]/g, '')), ny = parseFloat(y.replace(/[^0-9.\-]/g, ''));
            if (!isNaN(nx) && !isNaN(ny) && /\d/.test(x) && /\d/.test(y)) return dir * (nx - ny);
            return dir * x.localeCompare(y, undefined, { numeric: true, sensitivity: 'base' });
          });
          rows.forEach(function (r) { body.appendChild(r); });
        }
        th.addEventListener('click', sortNow);
        th.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); sortNow(); }
        });
      });
    });
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
