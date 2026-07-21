//WORKING VERSION

//GLOBAL

let CURRENT_LEAVE_PAGE = 1;

let CURRENT_VIEW_LEAVE_ID = null;

document.addEventListener("DOMContentLoaded", function () {
  loadLeaveCards();
  filterLeaveRequests(); // ★ IMPORTANT: load table on page load
  // 🔹 FIX: Populate dropdowns
  if (document.getElementById("leaveSummaryDepartmentFilter")) {
    loadLeaveDepartments();
  }

  if (document.getElementById("leaveUserDropdown")) {
    loadLeaveUsers();
  }
});

//FOR ATTACHMENT PREVIEW MODAL TO RETURN TO VIEW MODAL
document
  .getElementById("attachmentPreviewModal")
  .addEventListener("hidden.bs.modal", () => {
    const viewModalEl = document.getElementById("viewLeaveRequestModal");

    new bootstrap.Modal(viewModalEl).show();
  });

function getStatusBadge(status) {
  const s = String(status).toLowerCase(); // normalize

  switch (s) {
    case "pending":
      return `<span class="badge bg-warning">Pending</span>`;
    case "approved":
      return `<span class="badge bg-success">Approved</span>`;
    case "rejected":
      return `<span class="badge bg-danger">Rejected</span>`;
    case "cancelled":
      return `<span class="badge bg-secondary">Cancelled</span>`;
    default:
      return `<span class="badge bg-dark">${status}</span>`;
  }
}

/* -------------------------------
   FORMAT HELPER FOR LEAVE CARD BALANCE
-------------------------------- */

function formatLeave(value) {
  const num = parseFloat(value);
  return Number.isInteger(num) ? num : num.toFixed(1);
}

function isPreviewable(url) {
  return /\.(pdf|jpg|jpeg|png|webp)$/i.test(url);
}

/* -------------------------------
   SET FIELD INPUTS DEFAULT
-------------------------------- */

function setViewFormDisabled(disabled = true) {
  document
    .querySelectorAll(
      "#viewLeaveRequestModal input, #viewLeaveRequestModal select, #viewLeaveRequestModal textarea",
    )
    .forEach((el) => {
      el.disabled = disabled;
    });
}

/* -------------------------------
   CLICK HANDLER
-------------------------------- */

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".viewRequest");
  if (!btn) return;

  openViewLeaveModal(btn.dataset.id);
});

function loadRecipientsLeaveForView(selectedId) {
  fetch(
    `../backend/dtr-requests/get_recipients.php?leave_id=${CURRENT_VIEW_LEAVE_ID}`,
  )
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success") return;

      const dropdown = document.getElementById("viewLeaveRecipientSelect");
      dropdown.innerHTML = "";

      data.recipients.forEach((r) => {
        dropdown.innerHTML += `
          <option value="${r.id}" ${r.id == selectedId ? "selected" : ""}>
            ${r.username} (${r.role.toUpperCase()})
          </option>
        `;
      });
    });
}

/* -------------------------------
   CLICK HANDLER FOR OPENING VIEW MODAL
-------------------------------- */
function openViewLeaveModal(leaveId) {
  fetch(`../backend/leave-request/get_single_leave_request.php?id=${leaveId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success") {
        alert(data.message);
        return;
      }

      CURRENT_VIEW_LEAVE_ID = leaveId;

      const { leave, current_user, permissions } = data;

      console.log("Recipient from DB:", leave.recipient_id);

      const container = document.getElementById("viewLeaveEntryContainer");
      container.innerHTML = "";

      if (!Array.isArray(leave.items)) {
        console.warn("No leave items found");
        leave.items = [];
      }

      leave.items.forEach((item) => {
        const div = document.createElement("div");
        div.className = "leave-entry row g-3 mb-3";
        div.innerHTML = `
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" class="form-control leave-date" value="${item.leave_date}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Leave Type</label>
            <select class="form-select leave-type"></select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Availment</label>
            <select class="form-select leave-availment">
              <option value="whole-day">Whole Day</option>
              <option value="half-day">Half Day</option>
            </select>
          </div>
        `;
        container.appendChild(div);

        const typeSelect = div.querySelector(".leave-type");

        populateLeaveTypeOptions(
          typeSelect,
          current_user.role,
          item.leave_type,
          "view"
        );
        typeSelect.value = item.leave_type;

        div.querySelector(".leave-availment").value =
          item.availment == 1 ? "whole-day" : "half-day";
      });

      // Reason
      document.getElementById("viewLeaveReason").value = leave.reason;

      // Recipient
      loadRecipientsLeaveForView(leave.recipient_id);

      const attBox = document.getElementById("existingLeaveAttachments");
      attBox.innerHTML = "";

      if (Array.isArray(leave.attachments) && leave.attachments.length) {
        const row = document.createElement("div");
        row.className = "row g-3";

        leave.attachments.forEach((f) => {
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

        attBox.appendChild(row);

        attBox
          .querySelectorAll(".attachment-preview-btn, .attachment-preview-link")
          .forEach((el) => {
            el.addEventListener("click", function () {
              const url = this.dataset.url;
              if (!isPreviewable(url)) return;

              const body = document.getElementById("attachmentPreviewBody");
              const title = document.getElementById("attachmentPreviewTitle");

              body.innerHTML = "";
              title.textContent = "Attachment Preview";

              if (url.match(/\.pdf$/i)) {
                body.innerHTML = `
        <iframe
          src="${url}"
          style="width:100%;height:80vh;border:none;"
        ></iframe>
      `;
              } else {
                body.innerHTML = `
        <img
          src="${url}"
          class="img-fluid"
          style="max-height:80vh;"
        >
      `;
              }

              /*new bootstrap.Modal(
                document.getElementById("attachmentPreviewModal")
              ).show();*/
              const parentModalEl = document.getElementById(
                "viewLeaveRequestModal",
              );
              const previewModalEl = document.getElementById(
                "attachmentPreviewModal",
              );

              // hide parent first
              const parentModal = bootstrap.Modal.getInstance(parentModalEl);
              if (parentModal) parentModal.hide();

              // show preview AFTER parent closes
              setTimeout(() => {
                new bootstrap.Modal(previewModalEl, {
                  backdrop: "static",
                  keyboard: true,
                }).show();
              }, 300);
            });
          });

        // 🔄 Download loading handler
        attBox.querySelectorAll(".attachment-download-btn").forEach((btn) => {
          btn.addEventListener("click", function () {
            const url = this.dataset.url;
            const originalHTML = this.innerHTML;

            this.disabled = true;
            this.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Downloading...
      `;

            // trigger download
            const a = document.createElement("a");
            a.href = url;
            a.download = "";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            // restore button (no reliable download completion event)
            setTimeout(() => {
              this.disabled = false;
              this.innerHTML = originalHTML;
            }, 2000);
          });
        });
      } else {
        attBox.innerHTML = `
    <div class="text-muted fst-italic">
      No attachments uploaded
    </div>
  `;
      }

      console.log("RBAC DEBUG", permissions, current_user);
      applyRBACForViewModal(permissions);

      new bootstrap.Modal(
        document.getElementById("viewLeaveRequestModal"),
      ).show();
    });
}

/* -------------------------------
   POULATE LEAVE TYPE OPTIONS FOR HR
-------------------------------- */

/*function populateLeaveTypeOptions(select) {
  select.innerHTML = "";

  select.innerHTML += `<option value="vacation">Vacation Leave</option>`;
  select.innerHTML += `<option value="sick">Sick Leave</option>`;
  select.innerHTML += `<option value="emergency">Emergency Leave</option>`;
  select.innerHTML += `<option value="compassionate">Compassionate Leave</option>`;

  /*if (role === "hr") {
    add role beside select when needed again
  }
}*/

//OLD VERSION (UNCOMMENT FAILSAFE)

/*function populateLeaveTypeOptions(select, role) {
  
  select.innerHTML = "";

  // Always allowed
  select.innerHTML += `<option value="vacation">Vacation Leave</option>`;
  select.innerHTML += `<option value="sick">Sick Leave</option>`;

  // Higher roles only
  if (["admin", "hr", "executive"].includes(role)) {
    select.innerHTML += `<option value="emergency">Emergency Leave</option>`;
    select.innerHTML += `<option value="compassionate">Compassionate Leave</option>`;
  }
}*/

//WORKING VERSION OF THIS FUNCTION (DELETE COMMENT IF NOT WORKING)

//Added userId for TOIL
/*function populateLeaveTypeOptions(select, role, selectedValue = null) {
  select.innerHTML = "";

  // Always allowed
  select.innerHTML += `<option value="vacation">Vacation Leave</option>`;
  select.innerHTML += `<option value="sick">Sick Leave</option>`;

  // Higher roles
  if (["admin", "hr", "executive"].includes(role)) {
    select.innerHTML += `<option value="emergency">Emergency Leave</option>`;
    select.innerHTML += `<option value="compassionate">Compassionate Leave</option>`;
  }

  // ✅ TOIL only for USER_ID = 2
  if (USER_ID == 2) {
    select.innerHTML += `<option value="toil">Time in Lieu (TOIL)</option>`;
  }

  // 🔥 FORCE ADD if value exists but not in options (VIEW MODE FIX)
  if (
    selectedValue &&
    !select.querySelector(`option[value="${selectedValue}"]`)
  ) {
    const labelMap = {
      emergency: "Emergency Leave",
      compassionate: "Compassionate Leave",
      toil: "Time in Lieu (TOIL)", // ✅ ADD THIS
    };

    select.innerHTML += `<option value="${selectedValue}">
      ${labelMap[selectedValue] || selectedValue}
    </option>`;
  }
}*/

function populateLeaveTypeOptions(
  select,
  role,
  selectedValue = null,
  mode = "create",
) {
  select.innerHTML = "";

  // =========================
  // CREATE MODE (STRICT)
  // =========================
  if (mode === "create") {
    select.innerHTML += `<option value="vacation">Vacation Leave</option>`;
    select.innerHTML += `<option value="sick">Sick Leave</option>`;

    //ORIGINAL USER IS 14, CHANGED TO 2 FOR TESTING PURPOSES
    if (USER_ID == 2) {
      select.innerHTML += `<option value="toil">Time in Lieu Off (TOIL)</option>`;
    }
  }

  // =========================
  // VIEW MODE (ROLE-BASED)
  // =========================
  if (mode === "view") {
    // Always visible
    select.innerHTML += `<option value="vacation">Vacation Leave</option>`;
    select.innerHTML += `<option value="sick">Sick Leave</option>`;

    // 🔥 Higher roles get more options
    if (["admin", "hr", "executive"].includes(role)) {
      select.innerHTML += `<option value="emergency">Emergency Leave</option>`;
      select.innerHTML += `<option value="compassionate">Compassionate Leave</option>`;
    }

    // TOIL only for user 2
    if (USER_ID == 2) {
      select.innerHTML += `<option value="toil">Time in Lieu Off (TOIL)</option>`;
    }
  }

  // =========================
  // FORCE DISPLAY EXISTING VALUE
  // =========================
  if (
    selectedValue &&
    !select.querySelector(`option[value="${selectedValue}"]`)
  ) {
    const labelMap = {
      vacation: "Vacation Leave",
      sick: "Sick Leave",
      emergency: "Emergency Leave",
      compassionate: "Compassionate Leave",
      toil: "Time in Lieu Off (TOIL)",
    };

    select.innerHTML += `<option value="${selectedValue}">
      ${labelMap[selectedValue] || selectedValue}
    </option>`;
  }
}

/* -------------------------------
   COLLECT MODAL ROW DATA
-------------------------------- */

function collectViewModalItems() {
  let items = [];
  //const seenDates = new Set();
  const seenKeys = new Set();

  document
    .querySelectorAll("#viewLeaveEntryContainer .leave-entry")
    .forEach((row) => {
      const date = row.querySelector(".leave-date").value;
      const type = row.querySelector(".leave-type").value;
      const avail = row.querySelector(".leave-availment").value;

      if (!date || !type || !avail) return;
      //if (seenDates.has(date)) return;
      //seenDates.add(date);

      const key = `${date}|${type}`;
      if (seenKeys.has(key)) return;
      seenKeys.add(key);

      items.push({
        leave_date: date,
        leave_type: type,
        availment: avail === "whole-day" ? 1 : 0.5,
      });
    });

  return items;
}

/* -------------------------------
   RBAC FOR VIEW MODAL
-------------------------------- */

// NEW TEST (REFACTORED)
function applyRBACForViewModal(perms) {
  const saveBtn = document.getElementById("saveLeaveChangesBtn");
  const approveBtn = document.getElementById("approveLeaveBtn");
  const rejectBtn = document.getElementById("rejectLeaveBtn");
  const addBtn = document.getElementById("viewAddLeaveEntryBtn");

  const cancelBtn = document.getElementById("cancelLeaveBtn");
  const deleteBtn = document.getElementById("deleteLeaveBtn");

  // ✅ NEW: dropdown container
  const actionsDropdown = document.getElementById("leaveActionsDropdown");

  // Hide everything first
  [saveBtn, approveBtn, rejectBtn, addBtn].forEach((b) =>
    b.classList.add("d-none"),
  );

  [cancelBtn, deleteBtn].forEach((b) => {
    if (b) b.classList.add("d-none");
  });

  if (actionsDropdown) {
    actionsDropdown.classList.add("d-none");
  }

  // 🔒 Lock form by default
  setViewFormDisabled(true);

  /* =========================
     ACTIONS DROPDOWN LOGIC
  ========================== */

  if (perms.can_cancel /*|| perms.can_delete*/) {
    if (actionsDropdown) {
      actionsDropdown.classList.remove("d-none");
    }
  }

  if (perms.can_cancel) {
    cancelBtn.classList.remove("d-none");
  }

  if (perms.can_delete && deleteBtn) {
    deleteBtn.classList.remove("d-none");
  }

  /* =========================
     MAIN ACTIONS
  ========================== */

  if (perms.can_edit) {
    saveBtn.classList.remove("d-none");
    addBtn.classList.remove("d-none");
    setViewFormDisabled(false);
  }

  if (perms.can_approve) {
    approveBtn.classList.remove("d-none");
    rejectBtn.classList.remove("d-none");
  }
}

/* -------------------------------
   LEAVE BALANCE CARDS
-------------------------------- */
function loadLeaveCards() {
  fetch("../backend/leave-request/get_leave_balance.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.error) return console.error(data.error);

      document.getElementById("vacationLeaveCount").innerText = formatLeave(
        data.vacationLeave,
      );

      document.getElementById("sickLeaveCount").innerText = formatLeave(
        data.sickLeave,
      );

      document.getElementById("emergencyLeaveCount").innerText = formatLeave(
        data.emergencyLeave,
      );

      document.getElementById("compassionateLeaveCount").innerText =
        formatLeave(data.compLeave);
    })
    .catch((err) => console.error("Fetch Error:", err));
}

/* -------------------------------
   OPEN LEAVE REQUEST MODAL
-------------------------------- */
document.getElementById("viewLeaveEntryContainer").innerHTML = "";
function openCreateLeaveModal() {
  document.getElementById("leaveRequestForm").reset();

  const container = document.getElementById("leaveEntryContainer");
  const rows = container.querySelectorAll(".leave-entry");

  rows.forEach((row, index) => {
    if (index > 0) row.remove();
  });

  loadRecipientsLeave();

  // ✅ POPULATE LEAVE TYPES HERE
  document
    .querySelectorAll("#leaveEntryContainer .leave-type")
    .forEach((select) => {
      //Added create for TOIL
      populateLeaveTypeOptions(select, USER_ROLE, null, "create");
    });

  const modal = new bootstrap.Modal(
    document.getElementById("leaveRequestModal"),
  );
  modal.show();
}

/* -------------------------------
   LOAD RECIPIENTS
-------------------------------- */
function loadRecipientsLeave() {
  fetch("../backend/dtr-requests/get_recipients.php") // ★ FIXED PATH
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success") return;

      const dropdown = document.getElementById("leaveRecipientSelect");
      dropdown.innerHTML = `<option value="">-- Select Recipient --</option>`;

      data.recipients.forEach((r) => {
        dropdown.innerHTML += `
                    <option value="${r.id}">
                        ${r.username} (${r.role.toUpperCase()})
                    </option>
                `;
      });
    })
    .catch((err) => console.error("Error loading recipients:", err));
}

/* -------------------------------
   ADD ROW (CLEAN CLONE)
-------------------------------- */
document.getElementById("addLeaveEntryBtn").addEventListener("click", () => {
  const container = document.getElementById("leaveEntryContainer");

  const template = container.querySelector(".leave-entry");
  const clone = template.cloneNode(true);

  // 🔹 CLEAR ALL INPUTS
  clone.querySelector(".leave-date").value = "";
  // Commented out for TOIL
  //clone.querySelector(".leave-type").selectedIndex = 0;
  const typeSelect = clone.querySelector(".leave-type");
  typeSelect.innerHTML = ""; // reset options
  //Added create for TOIL
  populateLeaveTypeOptions(typeSelect, USER_ROLE, null, "create");
  clone.querySelector(".leave-availment").selectedIndex = 0;

  container.appendChild(clone);
});

/* -------------------------------
   REMOVE ROW
-------------------------------- */
document
  .getElementById("leaveEntryContainer")
  .addEventListener("click", function (e) {
    if (!e.target.closest(".remove-leave-entry")) return;

    const rows = this.querySelectorAll(".leave-entry");
    if (rows.length <= 1) return;

    e.target.closest(".leave-entry").remove();
  });

/* -------------------------------
   SUBMIT LEAVE REQUEST (DEDUP + VALIDATE)
-------------------------------- */
document
  .getElementById("leaveRequestForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    let items = [];
    //const seenDates = new Set();
    const seenKeys = new Set();

    /*document.querySelectorAll(".leave-entry")*/ document
      .querySelectorAll("#leaveEntryContainer .leave-entry")
      .forEach((row) => {
        const date = row.querySelector(".leave-date").value;
        const type = row.querySelector(".leave-type").value;
        const avail = row.querySelector(".leave-availment").value;

        if (!date || !type || !avail) return; // skip empty

        // 🔹 Deduplicate same date
        // if (seenDates.has(date)) return;
        // seenDates.add(date);

        const key = `${date}|${type}`;
        if (seenKeys.has(key)) return;
        seenKeys.add(key);

        items.push({
          leave_date: date,
          leave_type: type,
          availment: avail === "whole-day" ? 1 : 0.5,
        });
      });

    const reason = document.getElementById("leaveReason").value.trim();
    const recipientId = document.getElementById("leaveRecipientSelect").value;

    if (!items.length) {
      alert("Please add at least one leave entry.");
      return;
    }
    if (!reason || !recipientId) {
      alert("Please select a recipient and provide a reason.");
      return;
    }

    if (!confirm("Are you sure you want to submit this leave request?")) return;

    const formData = new FormData();
    formData.append("items", JSON.stringify(items));
    formData.append("reason", reason);
    formData.append("recipient_id", recipientId);

    const files = document.getElementById("leaveAttachment").files;
    for (let i = 0; i < files.length; i++) {
      formData.append("attachments[]", files[i]);
    }

    fetch("../backend/leave-request/create_leave_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          alert("Leave request submitted successfully!");
          loadLeaveCards();

          // ✅ Refresh leave request table/list
          filterLeaveRequests();

          bootstrap.Modal.getInstance(
            document.getElementById("leaveRequestModal"),
          ).hide();
          document.getElementById("leaveRequestForm").reset();
          document
            .querySelectorAll(".leave-entry:not(:first-child)")
            .forEach((r) => r.remove());
        } else {
          alert(data.message);
        }
      })
      .catch((err) => console.error("Submit Error:", err));
  });

/* -------------------------------
   SAVE / UPDATE LEAVE REQUEST
-------------------------------- */

document
  .getElementById("saveLeaveChangesBtn")
  .addEventListener("click", saveLeaveChanges);

function saveLeaveChanges() {
  const items = collectViewModalItems();
  const reason = document.getElementById("viewLeaveReason").value.trim();
  const recipientId = document.getElementById("viewLeaveRecipientSelect").value;

  if (!items.length || !reason || !recipientId) {
    alert("Please complete all required fields.");
    return;
  }

  if (!confirm("Save changes to this leave request?")) return;

  fetch("../backend/leave-request/update_leave_request.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      leave_id: CURRENT_VIEW_LEAVE_ID,
      reason: reason,
      recipient_id: recipientId,
      items: items,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Leave request updated.");
        filterLeaveRequests();
        bootstrap.Modal.getInstance(
          document.getElementById("viewLeaveRequestModal"),
        ).hide();
      } else {
        alert(data.message);
      }
    })
    .catch((err) => console.error("Update error:", err));
}

/* -------------------------------
    CANCEL LEAVE REQUEST
-------------------------------- */

const cancelBtnEl = document.getElementById("cancelLeaveBtn");
if (cancelBtnEl) {
  cancelBtnEl.addEventListener("click", cancelLeaveRequest);
}

function cancelLeaveRequest() {
  if (!confirm("Cancel this leave request?")) return;

  fetch("../backend/leave-request/cancel_leave_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ request_id: CURRENT_VIEW_LEAVE_ID }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Leave request cancelled.");
        filterLeaveRequests();
        bootstrap.Modal.getInstance(
          document.getElementById("viewLeaveRequestModal"),
        ).hide();
      } else {
        alert(data.message);
      }
    });
}

/* -------------------------------
    DELETE LEAVE REQUEST
-------------------------------- */

const deleteBtnEl = document.getElementById("deleteLeaveBtn");
if (deleteBtnEl) {
  deleteBtnEl.addEventListener("click", deleteLeaveRequest);
}
function deleteLeaveRequest() {
  if (!confirm("Delete this leave request? This will restore leave credits."))
    return;

  fetch("../backend/leave-request/delete_leave_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ request_id: CURRENT_VIEW_LEAVE_ID }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Leave request deleted.");
        loadLeaveCards(); // 🔥 IMPORTANT
        filterLeaveRequests();
        bootstrap.Modal.getInstance(
          document.getElementById("viewLeaveRequestModal"),
        ).hide();
      } else {
        alert(data.message);
      }
    });
}

/* -------------------------------
   APPROVE LEAVE REQUEST
-------------------------------- */

document
  .getElementById("approveLeaveBtn")
  .addEventListener("click", approveLeaveRequest);

function approveLeaveRequest() {
  if (!confirm("Approve this leave request?")) return;

  fetch("../backend/leave-request/approve_leave_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ request_id: CURRENT_VIEW_LEAVE_ID }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Leave approved.");
        loadLeaveCards();
        filterLeaveRequests();
        bootstrap.Modal.getInstance(
          document.getElementById("viewLeaveRequestModal"),
        ).hide();
      } else {
        alert(data.message);
      }
    });
}

/* -------------------------------
   REJECT LEAVE REQUEST
-------------------------------- */

document
  .getElementById("rejectLeaveBtn")
  .addEventListener("click", rejectLeaveRequest);

function rejectLeaveRequest() {
  const remarks = prompt("Enter rejection remarks:");

  if (!remarks) return;

  fetch("../backend/leave-request/reject_leave_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      request_id: CURRENT_VIEW_LEAVE_ID,
      remarks: remarks,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Leave rejected.");
        filterLeaveRequests();
        bootstrap.Modal.getInstance(
          document.getElementById("viewLeaveRequestModal"),
        ).hide();
      } else {
        alert(data.message);
      }
    });
}

/* -------------------------------
   FETCH + RENDER LEAVE REQUEST TABLE
-------------------------------- */

function filterLeaveRequests(page = 1) {
  CURRENT_LEAVE_PAGE = page;

  showLoading(); // 🔄 START LOADER

  const params = new URLSearchParams();
  params.append("page", page);

  const status = document.getElementById("leaveFilterStatus")?.value;
  if (status && status !== "all") {
    params.append("status", status);
  }

  const fromDate = document.getElementById("leaveStartDate")?.value;
  const toDate = document.getElementById("leaveendDate")?.value;

  if (fromDate) params.append("leave_from", fromDate);
  if (toDate) params.append("leave_to", toDate);

  const dept = document.getElementById("leaveSummaryDepartmentFilter");
  if (dept && dept.value) {
    params.append("department_id", dept.value);
  }

  const user = document.getElementById("leaveUserDropdown");
  if (user && user.value) {
    params.append("employee_id", user.value);
  }

  fetch(`../backend/leave-request/get_leave_requests.php?${params.toString()}`)
    .then((res) => res.json())
    .then((data) => {
      const rows = data.data || data.rows || data || [];
      renderLeaveRequests(rows);

      if (data.pagination) {
        renderLeavePagination(data.pagination.page, data.pagination.totalPages);
      }

      document.getElementById("noLeaveResults").style.display = rows.length
        ? "none"
        : "block";
    })
    .catch((err) => {
      console.error("Load list error:", err);
      renderLeaveRequests([]);
    })
    .finally(() => {
      hideLoading(); // ✅ ALWAYS HIDE
    });
}

function renderLeavePagination(page, totalPages) {
  const container = document.getElementById("leaveRequestsPagination");
  if (!container) return;

  container.innerHTML = "";

  if (totalPages <= 1) return;

  for (let i = 1; i <= totalPages; i++) {
    container.innerHTML += `
      <button class="btn btn-sm ${
        i === page ? "btn-primary" : "btn-outline-primary"
      } mx-1" onclick="filterLeaveRequests(${i})">
        ${i}
      </button>
    `;
  }
}

//RESETS FILTERS
function resetLeaveRequests() {
  const start = document.getElementById("leaveStartDate");
  if (start) start.value = "";

  const end = document.getElementById("leaveendDate");
  if (end) end.value = "";

  const status = document.getElementById("leaveFilterStatus");
  if (status) status.value = "pending";

  const user = document.getElementById("leaveUserDropdown");
  if (user) user.value = "";

  const dept = document.getElementById("leaveSummaryDepartmentFilter");
  if (dept) dept.value = "";

  if (typeof loadLeaveEmployees === "function") {
    loadLeaveEmployees();
  }

  filterLeaveRequests(1);
}

function renderLeaveRequests(list) {
  const tbody = document.querySelector("#leave-request-table tbody");
  const noMsg = document.getElementById("noLeaveResults");

  tbody.innerHTML = "";

  if (!list || list.length === 0) {
    noMsg.style.display = "block";
    return;
  }

  noMsg.style.display = "none";

  list.forEach((req) => {
    tbody.innerHTML += `
            <tr>
                <td>${req.created_at}</td>
    <td>
  ${
    req.start_date === req.end_date
      ? req.start_date
      : `${req.start_date} – ${req.end_date}`
  }
</td>

    <td>${req.leave_type}</td>
    <td>${req.employee_name}</td>
    <td>${getStatusBadge(req.status)}</td>
    <td>${req.checked_by_name ?? "—"}</td>
    <td>${req.leave_payment_status || "—"}</td>

    <td>
                    <button class="btn btn-sm btn-success viewRequest" data-id="${
                      req.id
                    }">
                        View
                    </button>
                </td>
            </tr>
        `;
  });
}

// ================================
// LOAD LEAVE DEPARTMENTS
// ================================
function loadLeaveDepartments() {
  const deptDropdown = document.getElementById("leaveSummaryDepartmentFilter");
  if (!deptDropdown) return;

  fetch("../backend/get_departments.php")
    .then((res) => res.json())
    .then((data) => {
      deptDropdown.innerHTML = '<option value="">All Departments</option>';

      data.forEach((dept) => {
        const option = document.createElement("option");
        option.value = dept.id;
        option.textContent = dept.department_name || dept.name;
        deptDropdown.appendChild(option);
      });
    })
    .catch((err) => console.error("Error loading leave departments:", err));

  // 🔁 Reload users when department changes
  deptDropdown.addEventListener("change", () => {
    loadLeaveUsers(deptDropdown.value);
    filterLeaveRequests(1);
  });
}

// ================================
// LOAD LEAVE USERS
// ================================
function loadLeaveUsers(departmentId = "") {
  const userDropdown = document.getElementById("leaveUserDropdown");
  if (!userDropdown) return;

  let url = "../backend/get_all_users.php";

  if (departmentId) {
    url += `?department_id=${departmentId}`;
  } else if (
    USER_ROLE === "supervisor" &&
    Array.isArray(supervisorDepartments) &&
    supervisorDepartments.length
  ) {
    const deptIds = supervisorDepartments
      .map((d) => parseInt(d, 10))
      .filter((n) => n > 0);

    url += `?department_ids=${deptIds.join(",")}`;
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      userDropdown.innerHTML = '<option value="">All Employees</option>';

      data.forEach((user) => {
        const option = document.createElement("option");
        option.value = user.id;
        option.textContent = [user.first_name, user.middle_name, user.last_name]
          .filter(Boolean)
          .join(" ");
        userDropdown.appendChild(option);
      });
    })
    .catch((err) => console.error("Error loading leave users:", err));
}

// ================================
// Utility Functions
// ================================
function showLoading() {
  const loader = document.createElement("div");
  loader.id = "loading-overlay";
  Object.assign(loader.style, {
    position: "fixed",
    top: "0",
    left: "0",
    width: "100%",
    height: "100%",
    backgroundColor: "rgba(0,0,0,0.4)",
    display: "flex",
    justifyContent: "center",
    alignItems: "center",
    zIndex: "9999",
  });
  loader.innerHTML = `<div class="spinner-border text-light" role="status"></div>`;
  document.body.appendChild(loader);
}

function hideLoading() {
  const loader = document.getElementById("loading-overlay");
  if (loader) loader.remove();
}
