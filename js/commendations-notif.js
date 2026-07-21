// =======================================================
// ✅ COMMENDATION POPUP NOTIFICATIONS (QUEUE)
// File: commendations-notif.js
// =======================================================

let notifQueue = [];
let currentNotif = null;

document.addEventListener("DOMContentLoaded", () => {
  // Only run if modal exists on the current page/layout
  if (!document.getElementById("commendNotifModal")) return;

  loadCommendationNotifications();
  setupNotifModalCloseHandler();
});

function loadCommendationNotifications() {
  showLoading?.();

  fetch("../backend/commendations/get_unseen_received_commendations.php")
    .then((r) => r.json())
    .then((resp) => {
      if (resp.status !== "success") return;

      notifQueue = resp.data || [];
      if (notifQueue.length > 0) {
        showNextNotification();
      }
    })
    .catch(console.error)
    .finally(() => hideLoading?.());
}

function showNextNotification() {
  if (!notifQueue.length) return;

  currentNotif = notifQueue.shift();
  console.log("🔎 currentNotif:", currentNotif);

  // ✅ Define these ONCE at the top so they’re in scope everywhere
  const type = String(currentNotif.type || "").toLowerCase();
  //old nomination function
  /*const nom = (
    currentNotif.nominator_name ??
    currentNotif.nominator ??
    currentNotif.from_name ??
    currentNotif.created_by ??
    currentNotif.created_by_name ??
    currentNotif.nominated_by_name ??
    ""
  )
    .toString()
    .trim();*/

  let nom = (
    currentNotif.nominator_name ??
    currentNotif.nominator ??
    currentNotif.from_name ??
    ""
  )
    .toString()
    .trim();

  // 🔥 CLEAN EXTERNAL FORMAT
  let isExternal = nom.includes("[EXTERNAL]");

  if (isExternal) {
    nom = nom.replace("[EXTERNAL]", "").trim(); // remove tag
    nom = nom.replace(/\(.*?\)/, "").trim(); // remove email
  }

  // Title
  const title = document.getElementById("commendNotifTitle");
  if (title) {
    if (type === "deduct") {
      /*title.innerHTML =
        `You have received a <span class="text-danger fw-bold">deduction</span>. ` +
        `Please review the details.`;*/
      //New version
      title.innerHTML = `You've received a new <span class="text-success fw-bold">commendation</span> from ${
        isExternal
          ? `<span class="badge bg-warning text-dark me-1">EXTERNAL</span> ${nom}`
          : nom
      }`;
    } else {
      title.innerHTML =
        `You've received a new <span class="text-success fw-bold">commendation</span> ` +
        `from ${nom}`;
    }
  }

  // Message From label
  const fromLabel = document.getElementById("notif_message_from_label");
  if (fromLabel) {
    //fromLabel.textContent = `Message From: ${nom}`;
    fromLabel.innerHTML = `Message From: ${
      isExternal
        ? `<span class="badge bg-warning text-dark me-1">EXTERNAL</span> ${nom}`
        : nom
    }`;
  }

  // Value & Points (no +/- sign)
  const valueName = currentNotif.value_name || "-";
  const pts = parseInt(currentNotif.points || 0, 10) || 0;

  // Value (display)
  const valueEl = document.getElementById("notif_value");
  if (valueEl) {
    valueEl.textContent = valueName;
  }

  // Points (display, no +/-)
  const pointsEl = document.getElementById("notif_points");
  if (pointsEl) {
    pointsEl.textContent = String(pts);

    // optional: color the points only
    pointsEl.classList.remove("text-success", "text-danger");
    pointsEl.classList.add(type === "deduct" ? "text-danger" : "text-success");
  }

  // Textareas
  const reasonEl = document.getElementById("notif_reason");
  if (reasonEl) reasonEl.value = currentNotif.reason || "";

  const commentEl = document.getElementById("notif_comment");
  if (commentEl) commentEl.value = currentNotif.remarks || "";

  // ✅ SHOW MODAL (this must exist)
  const modalEl = document.getElementById("commendNotifModal");
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function setupNotifModalCloseHandler() {
  const modalEl = document.getElementById("commendNotifModal");
  if (!modalEl) return;

  modalEl.addEventListener("hidden.bs.modal", () => {
    // Mark current as seen then show the next one
    if (!currentNotif?.id) {
      if (notifQueue.length) showNextNotification();
      return;
    }

    showLoading?.();

    fetch("../backend/commendations/mark_commendation_seen.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: currentNotif.id }),
    })
      .then(() => {
        currentNotif = null;
        if (notifQueue.length) {
          // tiny delay looks smoother
          setTimeout(showNextNotification, 250);
        }
      })
      .catch(console.error)
      .finally(() => hideLoading?.());
  });
}

// =======================================================
// ✅ COMMENDATIONS RECEIVED LIST
// File: commendations-received-list.js
// =======================================================

document.addEventListener("DOMContentLoaded", () => {
  if (!document.getElementById("commend-list-table")) return;
  loadReceivedCommendations({ page: 1, limit: 15 });
});

function loadReceivedCommendations(filters = {}) {
  filters.page ??= 1;
  filters.limit ??= 15;

  const params = new URLSearchParams(filters);
  const url =
    "../backend/commendations/get_received_commendations.php?" +
    params.toString();

  showLoading?.();

  fetch(url)
    .then((r) => r.json())
    /* //replaced block
    .then((resp) => {
      const tbody = document.querySelector("#commend-list-table tbody");
      tbody.innerHTML = "";

      if (resp.status !== "success" || !resp.data?.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted">No received commendations yet.</td>
          </tr>`;
        renderReceivedPagination(null, filters);
        return;
      }

      resp.data.forEach((row) => {
        const pts = parseInt(row.points || 0, 10) || 0;
        const signedPts =
          String(row.type).toLowerCase() === "deduct" ? `-${pts}` : `+${pts}`;

        tbody.innerHTML += `
          <tr>
            <td>${row.date || "-"}</td>
            <td>${row.nominator_name || "-"}</td>
            <td>${row.value_name || "-"}</td>
            <td class="text-uppercase">${row.type || "-"}</td>
            <td class="${row.type === "deduct" ? "text-danger" : "text-success"} fw-bold">
              ${signedPts}
            </td>
            <td>
              <button class="btn btn-sm btn-success" onclick="viewReceivedCommendation(${row.id})">View</button>
            </td>
          </tr>
        `;
      });

      renderReceivedPagination(resp.pagination || null, filters);
    })*/

    .then((resp) => {
      const tbody = document.querySelector("#commend-list-table tbody");
      const totalEl = document.getElementById("commendTotalPoints");

      tbody.innerHTML = "";

      if (totalEl) totalEl.textContent = "0";

      if (resp.status !== "success" || !resp.data?.length) {
        tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted">No received commendations yet.</td>
      </tr>`;
        renderReceivedPagination(null, filters);
        return;
      }

      // ✅ compute totals for the currently loaded page
      //let total = 0;

      // total uses signed points
      //total += isDeduct ? -pts : pts;

      resp.data.forEach((row) => {
        const pts = parseInt(row.points || 0, 10) || 0;
        const isDeduct = String(row.type).toLowerCase() === "deduct";

        const signedPts = isDeduct ? `-${pts}` : `+${pts}`;

        /*tbody.innerHTML += `
      <tr>
        <td>${row.date || "-"}</td>
        <td>${row.nominator_name || "-"}</td>
        <td>${row.value_name || "-"}</td>
        <td class="text-uppercase">${row.type || "-"}</td>
        <td class="${isDeduct ? "text-danger" : "text-success"} fw-bold">
          ${signedPts}
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-success" onclick="viewReceivedCommendation(${row.id})">
            View
          </button>
        </td>
      </tr>
    `;
      });*/

        tbody.innerHTML += `
      <tr>
        <td>${row.date || "-"}</td>
        <td>
  ${
    row.nominator_name?.includes("[EXTERNAL]")
      ? `<span class="badge bg-warning text-dark me-1">EXTERNAL</span> ${row.nominator_name
          .replace("[EXTERNAL]", "")
          .replace(/\(.*?\)/, "")
          .trim()}`
      : row.nominator_name
  }
</td>
        <td>${row.value_name || "-"}</td>
        <td class="text-uppercase">${row.type || "-"}</td>
        <td class="${isDeduct ? "text-danger" : "text-success"} fw-bold">
          ${signedPts}
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-success" onclick="viewReceivedCommendation(${row.id})">
            View
          </button>
        </td>
      </tr>
    `;
      });

      // ✅ update total label
      /*if (totalEl) {
        totalEl.textContent = total > 0 ? `+${total}` : String(total);
        totalEl.classList.remove("text-success", "text-danger");
        totalEl.classList.add(total < 0 ? "text-danger" : "text-success");
      }*/

      if (totalEl) {
        const total = parseInt(resp.total_points ?? 0, 10) || 0;
        totalEl.textContent = total > 0 ? `+${total}` : String(total);
        totalEl.classList.remove("text-success", "text-danger");
        totalEl.classList.add(total < 0 ? "text-danger" : "text-success");
      }

      renderReceivedPagination(resp.pagination || null, filters);
    })

    .catch(console.error)
    .finally(() => hideLoading?.());
}

function renderReceivedPagination(pagination, currentFilters = {}) {
  const container = document.getElementById("commendListPagination");
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
    b.onclick = () => loadReceivedCommendations({ ...currentFilters, page });
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
// ✅ VIEW RECEIVED COMMENDATION DETAILS
// =======================================================

function viewReceivedCommendation(id) {
  const modalEl = document.getElementById("viewReceivedCommendationModal");
  if (!modalEl) {
    alert("viewReceivedCommendationModal not found on this page.");
    return;
  }

  showLoading?.();

  fetch(`../backend/commendations/get_single_commendation.php?id=${id}`)
    .then((r) => r.json())
    .then((resp) => {
      if (resp.status !== "success") {
        alert(resp.message || "Failed to load commendation.");
        return;
      }

      const c = resp.data || {};

      // Display basic fields
      document.getElementById("recvViewNominator").textContent =
        c.nominator_name || "-";

      document.getElementById("recvViewType").textContent = (
        c.type || "-"
      ).toUpperCase();

      // Value name from values list (because backend only returns value_id)
      const valueName =
        c.value_name ||
        (Array.isArray(resp.values)
          ? resp.values.find((v) => String(v.id) === String(c.value_id))
              ?.name || "-"
          : "-");

      document.getElementById("recvViewValue").textContent = valueName;

      // Points (NO +/-)
      const pts = parseInt(c.points || 0, 10) || 0;
      const ptsEl = document.getElementById("recvViewPoints");
      if (ptsEl) {
        ptsEl.textContent = String(pts);

        // optional: color points only
        ptsEl.classList.remove("text-success", "text-danger");
        ptsEl.classList.add(
          String(c.type).toLowerCase() === "deduct"
            ? "text-danger"
            : "text-success",
        );
      }

      // Comment + Message
      const reasonEl = document.getElementById("recvViewReason");
      if (reasonEl) reasonEl.value = c.reason || "";

      const remarksEl = document.getElementById("recvViewRemarks");
      if (remarksEl) remarksEl.value = c.remarks || "";

      // Approver's name (NEW)
      const approverEl = document.getElementById("recvViewApprover");
      if (approverEl) {
        approverEl.textContent =
          c.status === "pending" ? "-" : c.approver_name || "-";
      }

      // Attachments (leave-style cards + preview)
      const wrap = document.getElementById("recvViewAttachmentWrap");
      const files = buildCommendationFilesArray(c);
      renderAttachmentCards(wrap, files, "viewReceivedCommendationModal");

      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    })
    .catch((err) => {
      console.error(err);
      alert("Error loading commendation.");
    })
    .finally(() => hideLoading?.());
}

function isPreviewable(url) {
  return /\.(pdf|png|jpe?g|gif|webp)$/i.test(url || "");
}

function buildCommendationFilesArray(c) {
  if (!c) return [];

  // if backend returns a single filename
  if (c.attachment) {
    return [
      {
        filename: c.attachment,
        url: `../uploads/commendations/${c.attachment}`,
      },
    ];
  }

  // if backend returns attachments array in the future
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
    containerEl.innerHTML = `
      <div class="text-muted fst-italic">No attachments uploaded</div>
    `;
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

  // Preview handlers (same behavior as leave: hide parent then show preview)
  containerEl
    .querySelectorAll(".attachment-preview-btn, .attachment-preview-link")
    .forEach((el) => {
      el.addEventListener("click", function () {
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
          body.innerHTML = `
            <iframe src="${url}" style="width:100%;height:80vh;border:none;"></iframe>
          `;
        } else {
          body.innerHTML = `
            <img src="${url}" class="img-fluid" style="max-height:80vh;">
          `;
        }

        window.__commendLastParentModalId = parentModalId;

        const parentModalEl = document.getElementById(parentModalId);
        const parentModal = bootstrap.Modal.getInstance(parentModalEl);
        if (parentModal) parentModal.hide();

        setTimeout(() => {
          new bootstrap.Modal(previewModalEl, {
            backdrop: "static",
            keyboard: true,
          }).show();
        }, 300);
      });
    });

  // Download loading (same as leave)
  containerEl.querySelectorAll(".attachment-download-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const url = this.dataset.url;
      const originalHTML = this.innerHTML;

      this.disabled = true;
      this.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Downloading...
      `;

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

// ✅ Return to correct parent modal when COMMENDATION preview closes
document.addEventListener("DOMContentLoaded", () => {
  const previewEl = document.getElementById("commendAttachmentPreviewModal");
  if (!previewEl || window.__commendPreviewBackBind) return;
  window.__commendPreviewBackBind = true;

  previewEl.addEventListener("hidden.bs.modal", () => {
    const parentId =
      window.__commendLastParentModalId || "viewReceivedCommendationModal";
    const parentEl = document.getElementById(parentId);
    if (parentEl) bootstrap.Modal.getOrCreateInstance(parentEl).show();
  });
});
