/**
 * EcoSprout – Custom JavaScript
 * Handles: sidebar toggle, delete confirmation (Bootstrap modal),
 *           flash auto-dismiss, form validation, plant image preview & remove,
 *           table search, modal edit population, cart adjustments.
 */

'use strict';

/* ══════════════════════════════════════════════════════════════
   DOM Ready – bootstrap all features
   ══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initFlashAutoDismiss();
    initImagePreview();
    initFormValidation();
    initTableSearch();
    initTablePagination();
    initDeleteModal();        // must run last (builds modal DOM)
});

/* ══════════════════════════════════════════════════════════════
   Sidebar Toggle
   ══════════════════════════════════════════════════════════════ */
function initSidebar() {
    const toggleBtn   = document.getElementById('sidebarToggle');
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    if (!toggleBtn || !sidebar) return;

    // Create overlay for mobile (if not already in DOM)
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    toggleBtn.addEventListener('click', () => {
        const isMobile = window.innerWidth < 992;
        if (isMobile) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
        }
    });

    // Close on overlay click (mobile)
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });

    // Restore correct state on resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        } else {
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('expanded');
        }
    });
}

/* ══════════════════════════════════════════════════════════════
   Flash Message Auto-Dismiss (5 s)
   ══════════════════════════════════════════════════════════════ */
function initFlashAutoDismiss() {
    document.querySelectorAll('.alert.fade.show').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
}

/* ══════════════════════════════════════════════════════════════
   Plant Image Preview + Remove Button
   ══════════════════════════════════════════════════════════════ */
function initImagePreview() {
    document.querySelectorAll('input[type="file"][name="image"]').forEach(imageInput => {
        const form = imageInput.closest('form');
        if (!form) return;
        const preview = form.querySelector('[id$="ImagePreview"]');
        const previewWrap = form.querySelector('[id$="ImagePreviewWrap"]');
        const fileNameEl = form.querySelector('[id$="ImageFileName"]');
        const imageUrl = form.querySelector('[id$="ImageUrl"]');
        const removeBtn = form.querySelector('[id$="RemoveImageBtn"]');
        const removeFlag = form.querySelector('[id$="RemoveImageFlag"]');
        const currentBadge = form.querySelector('[id$="CurrentImageBadge"]');

        imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (!file) { _resetImagePreview(form); return; }

        // Client-side type check
        if (!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)) {
            showToast('Only JPG, PNG, GIF or WebP images are allowed.', 'danger');
            imageInput.value = '';
            return;
        }
        // 2 MB size check
        if (file.size > 2 * 1024 * 1024) {
            showToast('Image must be smaller than 2 MB.', 'danger');
            imageInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            if (preview)     { preview.src = e.target.result; }
            if (imageUrl)    { imageUrl.href = e.target.result; imageUrl.classList.remove('d-none'); }
            if (previewWrap) { previewWrap.classList.remove('d-none'); }
            if (fileNameEl)  { fileNameEl.textContent = `${file.name} (${(file.size/1024).toFixed(1)} KB)`; }
            if (removeBtn)   { removeBtn.classList.remove('d-none'); }
            if (removeFlag)  { removeFlag.value = '0'; }   // new upload – don't remove
            if (currentBadge){ currentBadge.classList.add('d-none'); }
        };
        reader.readAsDataURL(file);
        });

        if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            imageInput.value = '';
            _resetImagePreview(form);
            if (removeFlag)  { removeFlag.value = '1'; }   // tell server to clear image
            if (currentBadge){ currentBadge.classList.add('d-none'); }
            showToast('Image removed. Save the form to apply changes.', 'info');
        });
        }
    });
}

function _resetImagePreview(form) {
    const preview = form.querySelector('[id$="ImagePreview"]');
    const previewWrap = form.querySelector('[id$="ImagePreviewWrap"]');
    const removeBtn = form.querySelector('[id$="RemoveImageBtn"]');
    const removeFlag = form.querySelector('[id$="RemoveImageFlag"]');
    const imageUrl = form.querySelector('[id$="ImageUrl"]');
    const currentBadge = form.querySelector('[id$="CurrentImageBadge"]');
    if (preview)     { preview.src = ''; }
    if (previewWrap) { previewWrap.classList.add('d-none'); }
    if (removeBtn)   { removeBtn.classList.add('d-none'); }
    if (removeFlag)  { removeFlag.value = '0'; }
    if (imageUrl)    { imageUrl.href = '#'; imageUrl.classList.add('d-none'); }
    if (currentBadge){ currentBadge.classList.add('d-none'); }
}

/* ══════════════════════════════════════════════════════════════
   Bootstrap Form Validation
   ══════════════════════════════════════════════════════════════ */
function initFormValidation() {
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

/* ══════════════════════════════════════════════════════════════
   Client-side Table Search
   ══════════════════════════════════════════════════════════════ */
function initTableSearch() {
    const searchInput = document.getElementById('tableSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase().trim();
        document.querySelectorAll('.searchable-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
        window.ecoRefreshTablePagination?.(true);
    });
}

function initTablePagination() {
    const pageSize = 10;
    const paginators = [];
    document.querySelectorAll('table').forEach(table => {
        const body = table.querySelector('tbody');
        const rows = body ? Array.from(body.querySelectorAll(':scope > tr')) : [];
        if (!body || rows.length <= pageSize) return;
        const container = table.closest('.eco-table-wrap, .table-responsive') || table.parentElement;
        const nav = document.createElement('nav');
        nav.className = 'eco-pagination mt-3';
        nav.setAttribute('aria-label', 'Table pagination');
        container.insertAdjacentElement('afterend', nav);
        const paginator = { page: 1, rows, nav };
        const render = (reset = false) => {
            if (reset) paginator.page = 1;
            const visible = rows.filter(row => row.style.display !== 'none');
            const totalPages = Math.max(1, Math.ceil(visible.length / pageSize));
            paginator.page = Math.min(paginator.page, totalPages);
            rows.forEach(row => { row.hidden = true; });
            visible.slice((paginator.page - 1) * pageSize, paginator.page * pageSize).forEach(row => { row.hidden = false; });
            if (visible.length === 0 || totalPages <= 1) { nav.innerHTML = ''; return; }
            const items = [];
            const add = (label, page, disabled = false, active = false) => items.push(`<li class="page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}"><button type="button" class="page-link" data-page="${page}"${disabled ? ' disabled' : ''}>${label}</button></li>`);
            add('&laquo; Prev', paginator.page - 1, paginator.page === 1);
            for (let page = 1; page <= totalPages; page += 1) {
                if (totalPages <= 7 || page === 1 || page === totalPages || Math.abs(page - paginator.page) <= 1) add(page, page, false, page === paginator.page);
                else if (page === 2 || page === totalPages - 1) items.push('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
            add('Next &raquo;', paginator.page + 1, paginator.page === totalPages);
            nav.innerHTML = `<ul class="pagination pagination-sm justify-content-end mb-0">${items.join('')}</ul>`;
            nav.querySelectorAll('[data-page]').forEach(button => button.addEventListener('click', () => { paginator.page = Number(button.dataset.page); render(); }));
        };
        paginator.render = render;
        paginators.push(paginator);
        render();
    });
    window.ecoRefreshTablePagination = reset => paginators.forEach(paginator => paginator.render(reset));
}

/* ══════════════════════════════════════════════════════════════
   Global Delete Confirmation Modal
   ──────────────────────────────────────────────────────────────
   Replaces browser confirm() everywhere with a polished modal.

   Declarative HTML usage:
     <button type="button"
             data-delete-form="myFormId"
             data-delete-label="Peace Lily"
             data-delete-icon="🌿">Delete</button>
     <form id="myFormId" method="POST" action="…">…</form>

   Programmatic (PHP onclick="…"):
     confirmDelete('myFormId')
     confirmDelete('myFormId', 'Custom message HTML', '🌿')

   Auto forms (add attribute to any delete form):
     <form data-eco-delete="this plant">…</form>
   ══════════════════════════════════════════════════════════════ */
function initDeleteModal() {
    if (document.getElementById('ecoDeleteModal')) return;   // already built

    /* Build the modal */
    document.body.insertAdjacentHTML('beforeend', `
    <div class="modal fade" id="ecoDeleteModal" tabindex="-1"
         aria-labelledby="ecoDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content border-0 overflow-hidden" style="border-radius:18px;">

          <!-- Header -->
          <div class="modal-header border-0 pb-1 px-4 pt-4">
            <div class="d-flex align-items-start gap-3 w-100">
              <div id="ecoDeleteIcon"
                   style="width:56px;height:56px;border-radius:16px;flex-shrink:0;
                          background:rgba(220,53,69,.1);display:flex;
                          align-items:center;justify-content:center;font-size:1.6rem;">
                🗑️
              </div>
              <div class="pt-1">
                <h5 class="modal-title fw-bold text-danger mb-0" id="ecoDeleteModalLabel">
                  Confirm Delete
                </h5>
                <p class="small text-muted mb-0">This action cannot be undone.</p>
              </div>
            </div>
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                    data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <!-- Body -->
          <div class="modal-body px-4 pt-3 pb-2">
            <p class="text-secondary mb-0" id="ecoDeleteModalMsg" style="font-size:.9rem;line-height:1.55;">
              Are you sure you want to permanently delete this record?
            </p>
          </div>

          <!-- Footer -->
          <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
            <button type="button" class="btn btn-light fw-medium px-4 rounded-3"
                    data-bs-dismiss="modal">
              <i class="bi bi-x-lg me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-danger fw-semibold px-4 rounded-3"
                    id="ecoDeleteConfirmBtn">
              <i class="bi bi-trash3 me-1"></i>Yes, Delete
            </button>
          </div>
        </div>
      </div>
    </div>`);

    /* Declarative data-delete-form buttons */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-delete-form]');
        if (!btn) return;
        e.preventDefault();
        const formId = btn.dataset.deleteForm;
        const label  = btn.dataset.deleteLabel || 'this record';
        const icon   = btn.dataset.deleteIcon  || '🗑️';
        const msg    = btn.dataset.deleteMsg
                     || `Are you sure you want to permanently delete <strong>${escapeHtml(label)}</strong>?`;
        _showDeleteModal(formId, msg, icon);
    });

    /* Auto-wire forms with data-eco-delete attribute */
    document.querySelectorAll('form[data-eco-delete]').forEach(form => {
        const label  = form.dataset.ecoDelete || 'this record';
        const icon   = form.dataset.ecoIcon   || '🗑️';
        if (!form.id) form.id = 'eco-del-' + Math.random().toString(36).slice(2);
        const fid    = form.id;

        form.querySelectorAll('[type=submit]').forEach(btn => {
            btn.type = 'button';
            btn.addEventListener('click', () =>
                confirmDelete(fid,
                    `Are you sure you want to permanently delete <strong>${escapeHtml(label)}</strong>?`,
                    icon)
            );
        });
    });
}

/* Internal: show the modal for a given form ID */
function _showDeleteModal(formId, message, icon, title, confirmLabel, confirmClass) {
    const modalEl    = document.getElementById('ecoDeleteModal');
    const msgEl      = document.getElementById('ecoDeleteModalMsg');
    const iconEl     = document.getElementById('ecoDeleteIcon');
    const titleEl    = document.getElementById('ecoDeleteModalLabel');
    const confirmBtn = document.getElementById('ecoDeleteConfirmBtn');
    if (!modalEl) return;

    if (msgEl)  msgEl.innerHTML = message;
    if (iconEl) iconEl.textContent = icon || '🗑️';
    if (titleEl) titleEl.textContent = title || 'Confirm Delete';

    // Swap confirm button to wipe stale listeners
    const fresh = confirmBtn.cloneNode(true);
    fresh.className = `btn ${confirmClass || 'btn-danger'} fw-semibold px-4 rounded-3`;
    fresh.innerHTML = `<i class="bi bi-check-lg me-1"></i>${confirmLabel || 'Yes, Delete'}`;
    confirmBtn.parentNode.replaceChild(fresh, confirmBtn);
    fresh.addEventListener('click', () => {
        bootstrap.Modal.getInstance(modalEl)?.hide();
        document.getElementById(formId)?.submit();
    });

    new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }).show();
}

/* ── Public: called from PHP onclick="confirmDelete('id')" ── */
function confirmDelete(formId, message, icon) {
    _showDeleteModal(
        formId,
        message || 'Are you sure you want to <strong>permanently delete</strong> this record?<br><span class="text-danger small">This cannot be undone.</span>',
        icon    || '🗑️', 'Confirm Delete', 'Yes, Delete', 'btn-danger'
    );
}

function confirmAction(formId, message, icon, title, confirmLabel) {
    _showDeleteModal(
        formId,
        message || 'Are you sure you want to continue?',
        icon || '⚙️',
        title || 'Confirm Action',
        confirmLabel || 'Continue',
        'btn-danger'
    );
}

function confirmNavigation(url, message, icon, title, confirmLabel) {
    const modalEl = document.getElementById('ecoDeleteModal');
    const msgEl = document.getElementById('ecoDeleteModalMsg');
    const iconEl = document.getElementById('ecoDeleteIcon');
    const titleEl = document.getElementById('ecoDeleteModalLabel');
    const confirmBtn = document.getElementById('ecoDeleteConfirmBtn');
    if (!modalEl || !confirmBtn) return;

    if (msgEl) msgEl.innerHTML = message || 'Are you sure you want to continue?';
    if (iconEl) iconEl.textContent = icon || '↪';
    if (titleEl) titleEl.textContent = title || 'Confirm Logout';

    const fresh = confirmBtn.cloneNode(true);
    fresh.className = 'btn btn-danger fw-semibold px-4 rounded-3';
    fresh.innerHTML = `<i class="bi bi-box-arrow-right me-1"></i>${confirmLabel || 'Yes, Logout'}`;
    confirmBtn.parentNode.replaceChild(fresh, confirmBtn);
    fresh.addEventListener('click', () => {
        window.location.href = url;
    });

    new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }).show();
}

/* Intercept legacy onsubmit="return confirm(…)" forms gracefully */
function confirmDeleteMsg(formId, message, icon) {
    confirmDelete(formId, message, icon);
    return false;   // prevent native form submit via onsubmit
}

/* ══════════════════════════════════════════════════════════════
   Modal Population for Edit
   ══════════════════════════════════════════════════════════════ */
function populateEditModal(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    Object.entries(data).forEach(([key, value]) => {
        if (['bsToggle','bsTarget'].includes(key)) return;
        const el = modal.querySelector(`[name="${key}"]`);
        if (el) el.value = value;
        const disp = modal.querySelector(`[data-field="${key}"]`);
        if (disp) disp.textContent = value;
    });
}

// Auto-wire edit buttons with data-modal-target
document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.dataset.modalTarget;
        populateEditModal(targetId, btn.dataset);
        const modal = document.getElementById(targetId);
        if (modal && btn.dataset.action) {
            const form = modal.querySelector('form');
            if (form) form.action = btn.dataset.action;
        }
    });
});

/* ══════════════════════════════════════════════════════════════
   Toast Helper
   ══════════════════════════════════════════════════════════════ */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0 rounded-3`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>`;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4500 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

/* ══════════════════════════════════════════════════════════════
   Utility
   ══════════════════════════════════════════════════════════════ */
function escapeHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

/* Cart quantity +/- buttons */
function adjustQty(inputId, delta) {
    const input = document.getElementById(inputId);
    if (!input) return;
    let val = parseInt(input.value, 10) + delta;
    if (val < 1) val = 1;
    if (input.max && val > parseInt(input.max, 10)) val = parseInt(input.max, 10);
    input.value = val;
}
