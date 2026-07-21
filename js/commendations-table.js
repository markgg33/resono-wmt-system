// =======================================================
// ✅ COMMENDATIONS TABLE + VIEW MODAL (SAFE GLOBAL VERSION)
// =======================================================

// Global holder for currently viewed commendation request
let currentCommend = null;

// =======================================================
// 0) LOADER HELPERS (USES EXISTING GLOBAL LOADER IF PRESENT)
// =======================================================
function commendShowLoading() {
  // Prefer your existing global functions (do NOT override them)
  if (typeof showLoading === "function") return showLoading();
  if (typeof showLoadingOverlay === "function") return showLoadingOverlay();
  try {
    document.body.style.cursor = "progress";
  } catch (e) {}
  return null;
}

function commendHideLoading(handle = null) {
  if (typeof hideLoading === "function") return hideLoading(handle);
  if (handle && typeof hideLoadingOverlay === "function")
    return hideLoadingOverlay(handle);

  try {
    document.body.style.cursor = "default";
  } catch (e) {}
}

// =======================================================
// ✅ ATTACHMENT PREVIEW HELPERS (LEAVE-STYLE)
// =======================================================
function isPreviewable(url) {
  return /\.(pdf|png|jpe?g|gif|webp)$/i.test(url || "");
}

function buildCommendationFilesArray(c) {
  if (!c) return [];

  // single filename from backend
  if (c.attachment) {
    return [
      {
        filename: c.attachment,
        url: `../uploads/commendations/${c.attachment}`,
      },
    ];
  }

  // future-proof: if backend returns array
  if (Array.isArray(c.attachments)) {
    return c.attachments.map((a) => ({
      filename: a.filename,
      url: a.url,
    }));
  }

  return [];
}

function renderAttachmentCards(containerEl, files = [], parentModalId) {
  if (!containerEl) return;

  containerEl.innerHTML = "";

  if (!Array.isArray(files) || files.length === 0) {
    containerEl.innerHTML = `<div class="text-muted fst-italic">No attachments uploaded</div>`;
    return;
  }

  const row = document.createElement("div");
  row.className = "row g-3";

  files.forEach((f) => {
    const col = document.createElement("div");
    col.className = "col-md-4 col-sm-6";

    col.innerHTML = `
      <div class="card shadow-sm h-100 attachment-card">
        <div class="card-body d-flex flex-column">

          <div
            class="attachment-filename text-truncate mb-3 text-primary fw-semibold attachment-preview-link"
            title="${f.filename}"
            role="button"
            data-url="${f.url}"
          >
            <i class="fa-solid fa-paperclip me-2 text-secondary"></i>
            ${f.filename}
          </div>

          <div class="mt-auto d-flex gap-2">
            <button
              type="button"
              class="btn btn-sm btn-outline-primary w-100 attachment-preview-btn"
              data-url="${f.url}"
              ${isPreviewable(f.url) ? "" : "disabled"}>
              <i class="fa-solid fa-eye me-1"></i> View
            </button>

            <button
              type="button"
              class="btn btn-sm btn-success w-100 attachment-download-btn"
              data-url="${f.url}">
              <i class="fa-solid fa-download me-1"></i> Download
            </button>
          </div>

        </div>
      </div>
    `;

    row.appendChild(col);
  });

  containerEl.appendChild(row);

  // ✅ Preview handlers (hide parent, then show preview modal)
  containerEl
    .querySelectorAll(".attachment-preview-btn, .attachment-preview-link")
    .forEach((el) => {
      el.addEventListener("click", function (e) {
        e.preventDefault(); // ✅ stop any default navigation

        const url = this.dataset.url;
        if (!isPreviewable(url)) return;

        const body = document.getElementById("commendAttachmentPreviewBody");
        const title = document.getElementById("commendAttachmentPreviewTitle");
        const previewModalEl = document.getElementById(
          "commendAttachmentPreviewModal",
        );
        if (!body || !title || !previewModalEl) return;

        body.innerHTML = "";
        title.textContent = "Attachment Preview";

        if (url.match(/\.pdf$/i)) {
          body.innerHTML = `<iframe src="${url}" style="width:100%;height:80vh;border:none;"></iframe>`;
        } else {
          body.innerHTML = `<img src="${url}" class="img-fluid" style="max-height:80vh;">`;
        }

        window.__commendLastParentModalId = parentModalId;

        const parentModalEl = document.getElementById(parentModalId);
        bootstrap.Modal.getInstance(parentModalEl)?.hide();

        setTimeout(() => {
          bootstrap.Modal.getOrCreateInstance(previewModalEl, {
            backdrop: "static",
            keyboard: true,
          }).show();
        }, 300);
      });
    });

  // ✅ Download loading
  containerEl.querySelectorAll(".attachment-download-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const url = this.dataset.url;
      const originalHTML = this.innerHTML;

      this.disabled = true;
      this.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Downloading...`;

      const a = document.createElement("a");
      a.href = url;
      a.download = "";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);

      setTimeout(() => {
        this.disabled = false;
        this.innerHTML = originalHTML;
      }, 2000);
    });
  });
}

// ✅ Return to parent modal when preview closes
/*
document.addEventListener("DOMContentLoaded", () => {
  const previewEl = document.getElementById("attachmentPreviewModal");
  if (!previewEl || window.__commendPreviewBackBind) return;
  window.__commendPreviewBackBind = true;

  previewEl.addEventListener("hidden.bs.modal", () => {
    // if admin modal exists, bring it back
    const admin = document.getElementById("viewCommendationModal");
    if (admin) bootstrap.Modal.getOrCreateInstance(admin).show();
  });
});*/

// ✅ Return to the LAST commendation modal that opened the preview
window.__commendLastParentModalId = null;

document.addEventListener("DOMContentLoaded", () => {
  const previewEl = document.getElementById("commendAttachmentPreviewModal");
  if (!previewEl || window.__commendPreviewBackBind) return;
  window.__commendPreviewBackBind = true;

  previewEl.addEventListener("hidden.bs.modal", () => {
    const parentId = window.__commendLastParentModalId;
    if (!parentId) return;

    const parentEl = document.getElementById(parentId);
    if (parentEl) bootstrap.Modal.getOrCreateInstance(parentEl).show();
  });
});

// =======================================================
// 1) INIT (ONLY RUN TABLE AUTO-LOAD ON COMMENDATION PAGE)
// =======================================================
document.addEventListener("DOMContentLoaded", () => {
  const hasTable = !!document.querySelector("#commendation-table tbody");
  const hasFilters = !!document.getElementById("commendFilterStatus");

  // ✅ If dashboard/global include: do NOTHING unless the table exists
  if (!hasTable || !hasFilters) {
    // Still allow view modal to work if modal exists (functions below)
    return;
  }

  // Defaults
  const statusEl = document.getElementById("commendFilterStatus");
  if (statusEl) statusEl.value = statusEl.value || "pending";

  const typeEl = document.getElementById("commendFilterType");
  if (typeEl) typeEl.value = typeEl.value || "commend";

  // Load values into filter dropdown if it exists
  commendLoadValueFilterDropdown();
  commendLoadUserFilterDropdown();

  // Initial table load
  loadCommendations({ status: "pending", type: "commend", page: 1, limit: 15 });
});

// =======================================================
// 2) LOAD VALUES (FOR FILTER DROPDOWN) - SAFE
// =======================================================
function commendLoadValueFilterDropdown() {
  const sel = document.getElementById("commendValueFilter");
  if (!sel) return;

  const loader = commendShowLoading();

  fetch("../backend/commendations/get_values.php")
    .then((res) => res.json())
    .then((values) => {
      sel.innerHTML = `<option value="">Value</option>`;
      (values || [])
        .filter((v) => v.status === "active")
        .forEach((v) => {
          const opt = document.createElement("option");
          opt.value = v.id;
          opt.textContent = v.name;
          sel.appendChild(opt);
        });
    })
    .catch((err) => console.error("Error loading value filter:", err))
    .finally(() => commendHideLoading(loader));
}

// =======================================================
// 3) LOAD TABLE (WITH PAGINATION) - SAFE
// =======================================================
function loadCommendations(filters = {}) {
  const tbody = document.querySelector("#commendation-table tbody");
  if (!tbody) return; // ✅ important guard for dashboard/global include

  filters.page ??= 1;
  filters.limit ??= 15;

  const params = new URLSearchParams(filters);
  const url =
    "../backend/commendations/get_commendations.php?" + params.toString();

  const loader = commendShowLoading();

  fetch(url)
    .then((res) => res.json())
    .then((resp) => {
      tbody.innerHTML = "";

      const rows = resp.data || [];
      if (rows.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="9" class="text-center text-muted">
              No commendations found
            </td>
          </tr>`;
        renderCommendPagination(resp.pagination || null, filters);
        return;
      }

      //old code <td>${row.nominator_name || "-"}</td> next to row.created_at

      rows.forEach((row) => {
        tbody.innerHTML += `
<tr>
    <td>${row.created_at || "-"}</td>

    <td>
        ${
          row.nominator_name?.includes("[EXTERNAL]")
            ? `<span class="badge bg-warning text-dark me-1">EXTERNAL</span>
               ${row.nominator_name.replace("[EXTERNAL]", "").trim()}`
            : row.nominator_name
        }
    </td>

<td>
    ${
      row.external_email
        ? row.external_email
        : '<span class="text-muted">Internal User</span>'
    }
</td>

    <td>${row.nominee_names || "-"}</td>
            <td>${row.value_name || "-"}</td>
            <td class="${
              row.type === "deduct" ? "text-danger" : "text-success"
            } fw-bold">
              ${row.type === "deduct" ? "-" : "+"}${row.points || 0}
            </td>
            <td class="text-uppercase">${row.type || "-"}</td>
            <td>
              <span class="badge bg-${statusColor(row.status)} text-uppercase">
                ${row.status || "-"}
              </span>
            </td>
            <td>
              <button class="btn btn-sm btn-success"
                onclick="viewCommendation(${row.id})">
                View
              </button>
            </td>
          </tr>
        `;
      });

      renderCommendPagination(resp.pagination || null, filters);
    })
    .catch((err) => console.error("Error loading commendations:", err))
    .finally(() => commendHideLoading(loader));
}

function statusColor(status) {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

// =======================================================
// 4) PAGINATION UI - SAFE
// =======================================================
function renderCommendPagination(pagination, currentFilters = {}) {
  const container = document.getElementById("commendationPagination");
  if (!container) return;

  container.innerHTML = "";

  if (!pagination || !pagination.totalPages || pagination.totalPages <= 1)
    return;

  const totalPages = parseInt(pagination.totalPages, 10) || 1;
  const currentPage =
    parseInt(pagination.page || currentFilters.page || 1, 10) || 1;

  const maxButtons = 7;

  const makeBtn = (label, page, active = false) => {
    const b = document.createElement("button");
    b.className = `btn mx-1 ${active ? "btn-success" : "btn-outline-success"}`;
    b.textContent = label;
    b.addEventListener("click", () => {
      loadCommendations({ ...currentFilters, page });
    });
    return b;
  };

  if (currentPage > 1) container.appendChild(makeBtn("Prev", currentPage - 1));

  let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
  let end = start + maxButtons - 1;
  if (end > totalPages) {
    end = totalPages;
    start = Math.max(1, end - maxButtons + 1);
  }

  for (let p = start; p <= end; p++) {
    container.appendChild(makeBtn(String(p), p, p === currentPage));
  }

  if (currentPage < totalPages)
    container.appendChild(makeBtn("Next", currentPage + 1));
}

// =======================================================
// 5) FILTER + RESET - SAFE (only if page has filters)
// =======================================================
function filterCommendations() {
  if (!document.getElementById("commendFilterStatus")) return;

  loadCommendations({
    start: document.getElementById("commendStartDate")?.value || "",
    end: document.getElementById("commendEndDate")?.value || "",
    status: document.getElementById("commendFilterStatus")?.value || "pending",
    type: document.getElementById("commendFilterType")?.value || "commend",
    user: document.getElementById("commendFilterUser")?.value || "",
    value: document.getElementById("commendValueFilter")?.value || "",
    page: 1,
    limit: 15,
  });
}

function resetCommendations() {
  if (!document.getElementById("commendFilterStatus")) return;

  const s = document.getElementById("commendStartDate");
  const e = document.getElementById("commendEndDate");
  if (s) s.value = "";
  if (e) e.value = "";

  const user = document.getElementById("commendFilterUser");
  if (user) user.value = "";

  const value = document.getElementById("commendValueFilter");
  if (value) value.value = "";

  const status = document.getElementById("commendFilterStatus");
  if (status) status.value = "pending";

  const type = document.getElementById("commendFilterType");
  if (type) type.value = "commend";

  loadCommendations({ status: "pending", type: "commend", page: 1, limit: 15 });
}

// =======================================================
// 6) VIEW MODAL HELPERS (ONLY IF MODAL EXISTS)
// =======================================================
function loadValuesIntoSelect(selectEl, values, selectedId) {
  if (!selectEl) return;
  selectEl.innerHTML = `<option value="">-- Select Value --</option>`;
  (values || []).forEach((v) => {
    const opt = document.createElement("option");
    opt.value = v.id;
    opt.textContent = v.name;
    if (String(v.id) === String(selectedId)) opt.selected = true;
    selectEl.appendChild(opt);
  });
}

function renderViewNominees(nominees) {
  const ul = document.getElementById("commendViewNominees");
  if (!ul) return;

  ul.innerHTML = "";
  if (!nominees?.length) {
    ul.innerHTML = `<li class="list-group-item text-muted">No nominees</li>`;
    return;
  }

  nominees.forEach((n) => {
    const li = document.createElement("li");
    li.className =
      "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `
      <label class="d-flex align-items-center gap-2 m-0">
        <input type="checkbox" class="form-check-input commendViewNomineeCb"
          value="${n.to_user_id}" checked>
        <span>${n.nominee_name}</span>
      </label>
      <span class="badge bg-secondary">Nominee</span>
    `;
    ul.appendChild(li);
  });
}

function setViewPoints(p) {
  const hidden = document.getElementById("commendViewPoints");
  if (hidden) hidden.value = String(p || "");

  const modal = document.getElementById("viewCommendationModal");
  if (!modal) return;

  modal.querySelectorAll(".point-btn").forEach((btn) => {
    btn.classList.remove("btn-success");
    btn.classList.add("btn-outline-success");
    if (String(btn.dataset.point) === String(p)) {
      btn.classList.add("btn-success");
      btn.classList.remove("btn-outline-success");
    }
  });
}

// Bind points only if modal exists
document.addEventListener("click", (e) => {
  if (!document.getElementById("viewCommendationModal")) return;
  const btn = e.target.closest("#viewCommendationModal .point-btn");
  if (!btn) return;
  setViewPoints(btn.dataset.point);
});

function applyCommendationRoleUI(ctx) {
  if (!document.getElementById("viewCommendationModal")) return;

  const canEdit = !!ctx?.can_edit && !ctx?.is_final;
  const canApprove = !!ctx?.can_approve && !ctx?.is_final;

  ["commendViewValue", "commendViewReason"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.disabled = !canEdit;
  });

  document.querySelectorAll(".commendViewNomineeCb").forEach((cb) => {
    cb.disabled = !canEdit;
  });

  document
    .querySelectorAll("#viewCommendationModal .point-btn")
    .forEach((b) => (b.disabled = !canEdit));

  const remarks = document.getElementById("commendViewRemarks");
  if (remarks) {
    remarks.disabled = !canApprove;
    remarks.closest(".mb-2")?.classList.toggle("d-none", !canApprove);
  }

  document
    .getElementById("saveCommendBtn")
    ?.classList.toggle("d-none", !canEdit);
  document
    .getElementById("approveCommendBtn")
    ?.classList.toggle("d-none", !canApprove);
  document
    .getElementById("rejectCommendBtn")
    ?.classList.toggle("d-none", !canApprove);
}

// =======================================================
// 7) VIEW MODAL: OPEN + FILL (SAFE GLOBAL)
// =======================================================
function viewCommendation(id) {
  const modalEl = document.getElementById("viewCommendationModal");
  if (!modalEl) {
    alert("View modal not found on this page.");
    return;
  }

  const loader = commendShowLoading();

  fetch(`../backend/commendations/get_single_commendation.php?id=${id}`)
    .then((r) => r.json())
    .then((resp) => {
      if (resp.status !== "success") {
        alert(resp.message || "Failed to load commendation.");
        return;
      }

      currentCommend = resp.data;

      document.getElementById("commendViewId").value = currentCommend.id;
      document.getElementById("commendViewEmployee").value =
        currentCommend.nominator_name || "-";
      document.getElementById("commendViewType").value =
        currentCommend.type || "-";
      document.getElementById("commendViewStatus").value =
        currentCommend.status || "-";

      loadValuesIntoSelect(
        document.getElementById("commendViewValue"),
        resp.values,
        currentCommend.value_id,
      );

      document.getElementById("commendViewReason").value =
        currentCommend.reason || "";
      document.getElementById("commendViewRemarks").value =
        currentCommend.remarks || "";

      const wrap = document.getElementById("commendViewAttachmentWrap");
      const files = buildCommendationFilesArray(currentCommend);
      renderAttachmentCards(wrap, files, "viewCommendationModal");

      renderViewNominees(currentCommend.nominees || []);
      setViewPoints(currentCommend.points);
      applyCommendationRoleUI(resp.context);

      new bootstrap.Modal(modalEl).show();
    })
    .catch((err) => {
      console.error(err);
      alert("Error loading commendation.");
    })
    .finally(() => commendHideLoading(loader));
}

// =======================================================
// 8) SAVE / APPROVE / REJECT HANDLERS (SAFE + ONE-TIME BIND)
// =======================================================

function commendGetPointsValue() {
  // supports either id="commendViewPoints" OR legacy id="commend_points"
  const el =
    document.getElementById("commendViewPoints") ||
    document.getElementById("commend_points");
  return el ? String(el.value || "").trim() : "";
}

function commendSetActionButtonsDisabled(disabled) {
  ["saveCommendBtn", "approveCommendBtn", "rejectCommendBtn"].forEach((id) => {
    const b = document.getElementById(id);
    if (b) b.disabled = !!disabled;
  });
}

// prevent duplicate bindings if this JS is included multiple times
if (!window.__commendActionBindingsDone) {
  window.__commendActionBindingsDone = true;

  document.addEventListener("click", (e) => {
    const modalExists = !!document.getElementById("viewCommendationModal");
    if (!modalExists) return;

    // ---------------------------
    // SAVE
    // ---------------------------
    const saveBtn = e.target.closest("#saveCommendBtn");
    if (saveBtn) {
      if (!currentCommend) return;

      if (!confirm("Save changes to this commendation request?")) return;

      const valueId = document.getElementById("commendViewValue")?.value || "";
      const points = commendGetPointsValue();
      const reason =
        document.getElementById("commendViewReason")?.value?.trim() || "";

      const nominees = Array.from(
        document.querySelectorAll(".commendViewNomineeCb"),
      )
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);

      const loader = commendShowLoading();
      commendSetActionButtonsDisabled(true);

      fetch("../backend/commendations/update_commendation.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          request_code: currentCommend.request_code,
          value_id: valueId,
          points: points,
          reason: reason,
          nominees: nominees,
        }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp.status === "success") {
            alert("Saved successfully.");
            // reload table if present
            if (typeof filterCommendations === "function")
              filterCommendations();
            bootstrap.Modal.getInstance(
              document.getElementById("viewCommendationModal"),
            )?.hide();
          } else {
            alert(resp.message || "Save failed.");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Server error while saving.");
        })
        .finally(() => {
          commendSetActionButtonsDisabled(false);
          commendHideLoading(loader);
        });

      return;
    }

    // ---------------------------
    // APPROVE
    // ---------------------------
    const approveBtn = e.target.closest("#approveCommendBtn");
    if (approveBtn) {
      if (!currentCommend) return;

      if (!confirm("Approve this commendation request?")) return;

      const remarks =
        document.getElementById("commendViewRemarks")?.value?.trim() || "";

      const loader = commendShowLoading();
      commendSetActionButtonsDisabled(true);

      fetch("../backend/commendations/approve_commendation.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          request_code: currentCommend.request_code,
          remarks: remarks,
        }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp.status === "success") {
            alert("Approved successfully.");
            if (typeof filterCommendations === "function")
              filterCommendations();
            bootstrap.Modal.getInstance(
              document.getElementById("viewCommendationModal"),
            )?.hide();
          } else {
            alert(resp.message || "Approve failed.");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Server error while approving.");
        })
        .finally(() => {
          commendSetActionButtonsDisabled(false);
          commendHideLoading(loader);
        });

      return;
    }

    // ---------------------------
    // REJECT
    // ---------------------------
    const rejectBtn = e.target.closest("#rejectCommendBtn");
    if (rejectBtn) {
      if (!currentCommend) return;

      if (!confirm("Reject this commendation request?")) return;

      const remarks =
        document.getElementById("commendViewRemarks")?.value?.trim() || "";

      const loader = commendShowLoading();
      commendSetActionButtonsDisabled(true);

      fetch("../backend/commendations/reject_commendation.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          request_code: currentCommend.request_code,
          remarks: remarks,
        }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp.status === "success") {
            alert("Rejected successfully.");
            if (typeof filterCommendations === "function")
              filterCommendations();
            bootstrap.Modal.getInstance(
              document.getElementById("viewCommendationModal"),
            )?.hide();
          } else {
            alert(resp.message || "Reject failed.");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Server error while rejecting.");
        })
        .finally(() => {
          commendSetActionButtonsDisabled(false);
          commendHideLoading(loader);
        });

      return;
    }
  });
}

// ================================
// MY COMMENDATIONS (USER VIEW-ONLY)
// ================================

document.addEventListener("DOMContentLoaded", () => {
  const hasTable = !!document.querySelector("#my-commendation-table tbody");
  if (!hasTable) return;

  // load values (reuse your existing endpoint)
  loadMyCommendValueFilterDropdown();

  // initial load
  loadMyCommendations({
    status: "pending",
    type: "commend",
    page: 1,
    limit: 15,
  });
});

function loadMyCommendValueFilterDropdown() {
  const sel = document.getElementById("myCommendValueFilter");
  if (!sel) return;

  fetch("../backend/commendations/get_values.php")
    .then((r) => r.json())
    .then((values) => {
      sel.innerHTML = `<option value="">Value</option>`;
      (values || [])
        .filter((v) => v.status === "active")
        .forEach((v) => {
          const opt = document.createElement("option");
          opt.value = v.id;
          opt.textContent = v.name;
          sel.appendChild(opt);
        });
    })
    .catch(console.error);
}

function loadMyCommendations(filters = {}) {
  const tbody = document.querySelector("#my-commendation-table tbody");
  if (!tbody) return;

  filters.page ??= 1;
  filters.limit ??= 15;

  // ✅ reuse your same endpoint: it already restricts to own requests for user/supervisor
  const params = new URLSearchParams(filters);
  const url =
    "../backend/commendations/get_commendations.php?" + params.toString();

  fetch(url)
    .then((r) => r.json())
    .then((resp) => {
      tbody.innerHTML = "";

      const rows = resp.data || [];
      if (rows.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center text-muted">No commendations found</td>
          </tr>`;
        renderMyCommendPagination(resp.pagination || null, filters);
        return;
      }

      rows.forEach((row) => {
        tbody.innerHTML += `
          <tr>
            <td>${row.created_at || "-"}</td>
            <td>${row.nominee_names || "-"}</td>
            <td>${row.value_name || "-"}</td>
            <td class="${row.type === "deduct" ? "text-danger" : "text-success"} fw-bold">
              ${row.type === "deduct" ? "-" : "+"}${row.points || 0}
            </td>
            <td class="text-uppercase">${row.type || "-"}</td>
            <td>
              <span class="badge bg-${myStatusColor(row.status)} text-uppercase">
                ${row.status || "-"}
              </span>
            </td>
            <td>
              <button class="btn btn-sm btn-success" onclick="viewMyCommendation(${row.id})">View</button>
            </td>
          </tr>
        `;
      });

      renderMyCommendPagination(resp.pagination || null, filters);
    })
    .catch(console.error);
}

function myStatusColor(status) {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

function renderMyCommendPagination(pagination, currentFilters = {}) {
  const container = document.getElementById("myCommendationPagination");
  if (!container) return;

  container.innerHTML = "";
  if (!pagination || !pagination.totalPages || pagination.totalPages <= 1)
    return;

  const totalPages = parseInt(pagination.totalPages, 10) || 1;
  const currentPage =
    parseInt(pagination.page || currentFilters.page || 1, 10) || 1;

  const makeBtn = (label, page, active = false) => {
    const b = document.createElement("button");
    b.className = `btn mx-1 ${active ? "btn-success" : "btn-outline-success"}`;
    b.textContent = label;
    b.addEventListener("click", () =>
      loadMyCommendations({ ...currentFilters, page }),
    );
    return b;
  };

  if (currentPage > 1) container.appendChild(makeBtn("Prev", currentPage - 1));

  const maxButtons = 7;
  let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
  let end = start + maxButtons - 1;
  if (end > totalPages) {
    end = totalPages;
    start = Math.max(1, end - maxButtons + 1);
  }

  for (let p = start; p <= end; p++) {
    container.appendChild(makeBtn(String(p), p, p === currentPage));
  }

  if (currentPage < totalPages)
    container.appendChild(makeBtn("Next", currentPage + 1));
}

function filterMyCommendations() {
  loadMyCommendations({
    start: document.getElementById("myCommendStartDate")?.value || "",
    end: document.getElementById("myCommendEndDate")?.value || "",
    status:
      document.getElementById("myCommendFilterStatus")?.value || "pending",
    type: document.getElementById("myCommendFilterType")?.value || "commend",
    value: document.getElementById("myCommendValueFilter")?.value || "",
    page: 1,
    limit: 15,
  });
}

function resetMyCommendations() {
  const s = document.getElementById("myCommendStartDate");
  const e = document.getElementById("myCommendEndDate");
  if (s) s.value = "";
  if (e) e.value = "";

  const v = document.getElementById("myCommendValueFilter");
  if (v) v.value = "";

  const st = document.getElementById("myCommendFilterStatus");
  if (st) st.value = "pending";

  const t = document.getElementById("myCommendFilterType");
  if (t) t.value = "commend";

  loadMyCommendations({
    status: "pending",
    type: "commend",
    page: 1,
    limit: 15,
  });
}

// ✅ view-only modal loader
function viewMyCommendation(id) {
  const modalEl = document.getElementById("viewCommendationModal");
  if (!modalEl) return alert("View modal not found.");

  fetch(`../backend/commendations/get_single_commendation_user.php?id=${id}`)
    .then((r) => r.json())
    .then((resp) => {
      if (resp.status !== "success") return alert(resp.message || "Failed.");

      const c = resp.data;

      // Fill same modal fields
      document.getElementById("commendViewId").value = c.id;
      document.getElementById("commendViewEmployee").value =
        c.nominator_name || "-";
      document.getElementById("commendViewType").value = c.type || "-";
      document.getElementById("commendViewStatus").value = c.status || "-";

      // values as display only
      const valueSel = document.getElementById("commendViewValue");
      if (valueSel) {
        valueSel.innerHTML = `<option value="${c.value_id}">${c.value_id}</option>`;
        valueSel.disabled = true;
      }

      const reason = document.getElementById("commendViewReason");
      if (reason) {
        reason.value = c.reason || "";
        reason.disabled = true;
      }

      // hide remarks section on user view (optional)
      const remarks = document.getElementById("commendViewRemarks");
      if (remarks) remarks.closest(".mb-2")?.classList.add("d-none");

      // nominees list view-only
      const ul = document.getElementById("commendViewNominees");
      if (ul) {
        ul.innerHTML = "";
        (c.nominees || []).forEach((n) => {
          const li = document.createElement("li");
          li.className = "list-group-item";
          li.textContent = n.nominee_name;
          ul.appendChild(li);
        });
      }

      // disable points buttons
      modalEl
        .querySelectorAll(".point-btn")
        .forEach((b) => (b.disabled = true));

      // hide action buttons
      document.getElementById("saveCommendBtn")?.classList.add("d-none");
      document.getElementById("approveCommendBtn")?.classList.add("d-none");
      document.getElementById("rejectCommendBtn")?.classList.add("d-none");

      // attachments (reuse your functions if included)
      if (
        typeof buildCommendationFilesArray === "function" &&
        typeof renderAttachmentCards === "function"
      ) {
        const wrap = document.getElementById("commendViewAttachmentWrap");
        const files = buildCommendationFilesArray(c);
        renderAttachmentCards(wrap, files, "viewCommendationModal");
      }

      new bootstrap.Modal(modalEl).show();
    })
    .catch(console.error);
}

function commendLoadUserFilterDropdown() {
  const sel = document.getElementById("commendFilterUser");
  if (!sel) return;

  const loader = commendShowLoading();

  fetch("../backend/get_all_users.php")
    .then((res) => res.json())
    .then((users) => {
      sel.innerHTML = `<option value="">All Employees</option>`;

      (users || []).forEach((u) => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = `${u.first_name} ${u.last_name}`;
        sel.appendChild(opt);
      });
    })
    .catch((err) => console.error("Error loading users:", err))
    .finally(() => commendHideLoading(loader));
}
