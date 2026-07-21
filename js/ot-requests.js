// =======================================================
// 🕒 OVERTIME REQUEST FRONTEND LOGIC
// =======================================================

document.addEventListener("DOMContentLoaded", () => {
  otloadRecipients();
  otloadDepartments();
  loadUserDropdown();
  // Ensure status filter defaults to 'pending' and load initial requests
  const statusElem = document.getElementById("otFilterStatus");
  if (statusElem) statusElem.value = statusElem.value || "pending";
  loadOTRequests({ status: "pending" });

  // ✅ Reset modal state when modal is closed
  const viewOTModal = document.getElementById("viewOTRequestModal");
  if (viewOTModal) {
    viewOTModal.addEventListener("hidden.bs.modal", function () {
      resetOTModalState();
      // Clear alert
      const alertEl = document.getElementById("viewOTAlert");
      if (alertEl) {
        alertEl.classList.add("d-none");
        alertEl.textContent = "";
      }
    });
  }
});

// =======================================================
// 🔹 LOAD DEPARTMENTS DROPDOWN
// =======================================================
function otloadDepartments() {
  const deptDropdown = document.getElementById("otSummaryDepartmentFilter");
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
    .catch((err) => console.error("Error loading departments:", err));

  // ✅ When department changes, reload user list
  deptDropdown.addEventListener("change", (e) => {
    const deptId = e.target.value;
    loadUserDropdown(deptId);
  });
}

// =======================================================
// 🔹 LOAD USERS DROPDOWN (Admins & HR Filtering)
// =======================================================

function loadUserDropdown(departmentId = "") {
  const userDropdown = document.getElementById("otUserDropdown");
  if (!userDropdown) return;

  let url = "../backend/get_all_users.php";

  if (departmentId) {
    // ✅ A department is selected from the filter dropdown
    url += `?department_id=${departmentId}`;
  } else if (
    USER_ROLE === "supervisor" &&
    Array.isArray(supervisorDepartments) &&
    supervisorDepartments.length > 0
  ) {
    // ✅ Supervisor: load only users in their departments
    // Normalize to integers in case JSON contained strings
    const deptIds = supervisorDepartments
      .map((d) => parseInt(d, 10))
      .filter((n) => !Number.isNaN(n) && n > 0);
    url += `?department_ids=${deptIds.join(",")}`;
  }

  // Debug: log URL and context when running in dev consoles
  try {
    console.debug(
      "loadUserDropdown -> fetch url:",
      url,
      "USER_ROLE:",
      USER_ROLE,
      "supervisorDepartments:",
      supervisorDepartments
    );
  } catch (e) {
    // ignore if console not available
  }
  // else: Admin/HR/Executive, no filter → load all users

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      userDropdown.innerHTML = '<option value="">All Employees</option>';
      data.forEach((user) => {
        const option = document.createElement("option");
        option.value = user.id;
        const fullname = [user.first_name, user.middle_name, user.last_name]
          .filter(Boolean)
          .join(" ");
        option.textContent = fullname;
        userDropdown.appendChild(option);
      });
    })
    .catch((err) => console.error("Error loading users:", err));
}

// =======================================================
// 🔹 OPEN MODAL
// =======================================================
function openCreateOvertimeModal() {
  const form = document.getElementById("overtimeRequestForm");
  form.reset();

  const alertBox = document.getElementById("otRequestAlert");
  alertBox.classList.add("d-none");

  // reset hours display
  const hoursInput = document.getElementById("otHours");
  if (hoursInput) hoursInput.value = "";

  const modal = new bootstrap.Modal(
    document.getElementById("overtimeRequestModal")
  );
  modal.show();
}

// =======================================================
// 🔹 LOAD RECIPIENTS (Supervisors/Admins/HR/Executives)
// =======================================================
function otloadRecipients() {
  fetch("../backend/dtr-requests/get_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      const recipientSelect = document.getElementById("otRecipient");
      if (!recipientSelect) return;

      recipientSelect.innerHTML = '<option value="">Select Recipient</option>';

      // ✅ FIX: Check if backend wraps data inside `recipients`
      const list = data.recipients || data;

      if (!Array.isArray(list)) {
        console.error("Invalid recipients data:", data);
        return;
      }

      list.forEach((user) => {
        const option = document.createElement("option");
        option.value = user.id;
        option.textContent = `${user.username} (${user.role.toUpperCase()})`;
        recipientSelect.appendChild(option);
      });
    })
    .catch((err) => console.error("Error loading recipients:", err));
}

// =======================================================
// 🔹 HOURS FIELD HANDLING (HH:MM FORMAT ONLY)
// =======================================================
document.addEventListener("DOMContentLoaded", () => {
  // ✅ Create reusable function to restrict input to numbers and colon only
  function restrictHoursInput(input) {
    if (!input) return;

    // Set max length to prevent excessive input (HH:MM = 5 characters max)
    input.setAttribute("maxlength", "5");

    // Restrict input to only numbers and colon
    input.addEventListener("keypress", (e) => {
      // Allow special keys (backspace, delete, tab, arrows, etc.)
      const specialKeys = [
        "Backspace",
        "Delete",
        "Tab",
        "Escape",
        "Enter",
        "ArrowLeft",
        "ArrowRight",
        "ArrowUp",
        "ArrowDown",
        "Home",
        "End",
      ];
      if (specialKeys.includes(e.key) || e.ctrlKey || e.metaKey) {
        return; // Allow these keys
      }

      // Check if the pressed key is a number or colon
      const char = e.key || String.fromCharCode(e.which || e.keyCode);
      if (!/[0-9:]/.test(char)) {
        e.preventDefault(); // Block invalid characters
      }
    });

    // Additional safety: filter out invalid characters on paste/input
    input.addEventListener("input", (e) => {
      let value = e.target.value;
      // Remove any character that's not a number or colon
      const filtered = value.replace(/[^0-9:]/g, "");
      if (value !== filtered) {
        e.target.value = filtered;
      }
    });

    // Validate format + minimum duration on blur
    input.addEventListener("blur", () => {
      const value = input.value.trim();
      if (!value) {
        input.setCustomValidity("");
        return;
      }

      const validFormat = /^([0-9]{1,2}):([0-5][0-9])$/;

      if (!validFormat.test(value)) {
        input.setCustomValidity(
          "Please enter duration as HH:MM (e.g., 02:30)."
        );
        return;
      }

      const [hh, mm] = value.split(":").map(Number);
      const totalMinutes = hh * 60 + mm;

      if (totalMinutes < 15) {
        input.setCustomValidity("Minimum overtime duration is 15 minutes.");
        return;
      }

      input.setCustomValidity("");
    });
  }

  // Apply to create modal hours input
  const hoursInput = document.getElementById("otHours");
  restrictHoursInput(hoursInput);

  // Apply to edit/view modal hours input
  const viewHoursInput = document.getElementById("otViewHours");
  restrictHoursInput(viewHoursInput);
});

// =======================================================
// 🔹 HELPER: FORMAT HOURS (REMOVE SECONDS)
// =======================================================
function formatHoursForDisplay(hours) {
  if (!hours) return "";
  // Remove seconds if present (e.g., "02:30:00" -> "02:30")
  return hours.toString().replace(/:\d{2}$/, "");
}

// =======================================================
// 🔹 HELPER: VALIDATE HOURS FORMAT (OLD)
// =======================================================
/*
function validateHoursFormat(hours) {
  if (!hours) return false;
  const validFormat = /^([0-9]{1,2}):([0-5][0-9])$/; // HH:MM
  return validFormat.test(hours.trim());
}
*/

// =======================================================
// 🔹 HELPER: VALIDATE HOURS FORMAT + MINIMUM DURATION RULES
// =======================================================
function validateHoursFormat(hours) {
  if (!hours) return false;

  const validFormat = /^([0-9]{1,2}):([0-5][0-9])$/; // HH:MM
  if (!validFormat.test(hours.trim())) return false;

  // Split into HH and MM
  const [hh, mm] = hours.split(":").map(Number);

  // ❌ Reject 00:00
  if (hh === 0 && mm === 0) return false;

  // ❌ Reject less than 15 mins
  const totalMinutes = hh * 60 + mm;
  if (totalMinutes < 15) return false;

  return true;
}

// =======================================================
// 🔹 SUBMIT OVERTIME REQUEST
// =======================================================
document
  .getElementById("submitOvertimeRequestBtn")
  ?.addEventListener("click", () => {
    const form = document.getElementById("overtimeRequestForm");
    const alertBox = document.getElementById("otRequestAlert");

    const date = document.getElementById("otTrackerDate")?.value.trim(); // ✅ FIXED
    const hours = document.getElementById("otHours")?.value.trim();
    const reason = document.getElementById("otReason")?.value.trim();
    const recipient = document.getElementById("otRecipient")?.value;

    if (!date || !hours || !reason || !recipient) {
      alertBox.className = "alert alert-danger mt-2";
      alertBox.textContent = "Please complete all required fields.";
      alertBox.classList.remove("d-none");
      return;
    }

    // ✅ Validate hours format(OLD VERSION)
    /*
    if (!validateHoursFormat(hours)) {
      alertBox.className = "alert alert-danger mt-2";
      alertBox.textContent =
        "Please enter hours in HH:MM format (e.g., 02:30).";
      alertBox.classList.remove("d-none");
      document.getElementById("otHours").focus();
      return;
    }
*/
    // Validate hours format + minimum duration rule
    if (!validateHoursFormat(hours)) {
      alertBox.className = "alert alert-danger mt-2";

      if (hours === "00:00") {
        alertBox.textContent = "Overtime duration cannot be 00:00.";
      } else {
        // Check total minutes for more specific message
        const [hh, mm] = hours.split(":").map(Number);
        const totalMinutes = hh * 60 + mm;

        if (totalMinutes < 15) {
          alertBox.textContent = "Minimum overtime duration is 15 minutes.";
        } else {
          alertBox.textContent =
            "Please enter hours in HH:MM format (e.g., 02:30).";
        }
      }

      alertBox.classList.remove("d-none");
      document.getElementById("otHours").focus();
      return;
    }

    const formData = new FormData(form);

    fetch("../backend/submit_overtime_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          alertBox.className = "alert alert-success mt-2";
          alertBox.textContent = "Overtime request submitted successfully!";
          alertBox.classList.remove("d-none");
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("overtimeRequestModal")
            ).hide();
            /*loadOTRequests();*/
            // Refresh table to default: pending, page 1, limit 15
            loadOTRequests({
              status: "pending",
              page: 1,
              limit: 15,
            });
          }, 1000);
        } else {
          alertBox.className = "alert alert-danger mt-2";
          alertBox.textContent =
            data.message || "Failed to submit overtime request.";
          alertBox.classList.remove("d-none");
        }
      })
      .catch((err) => {
        console.error(err);
        alertBox.className = "alert alert-danger mt-2";
        alertBox.textContent =
          "An error occurred while submitting your request.";
        alertBox.classList.remove("d-none");
      });
  });

// =======================================================
// 🔹 LOAD / FILTER OT REQUESTS
// =======================================================
function loadOTRequests(filters = {}) {
  // Ensure sensible defaults
  const page = parseInt(filters.page || 1, 10) || 1;
  const limit = parseInt(filters.limit || 15, 10) || 15;
  filters.page = page;
  filters.limit = limit;

  const params = new URLSearchParams(filters);
  const url = "../backend/get_ot_requests.php?" + params.toString();

  const render = (payload) => {
    // Backend may return either the old array or a { data, pagination } object
    const tbody = document.querySelector("#ot-request-table tbody");
    tbody.innerHTML = "";

    let data = [];
    let pagination = null;
    if (Array.isArray(payload)) {
      data = payload;
    } else if (payload && Array.isArray(payload.data)) {
      data = payload.data;
      pagination = payload.pagination || null;
    }

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="8" class="text-center text-muted">No overtime requests found.</td></tr>';
      // Clear pagination if none
      renderOTPagination(null, filters);
      return;
    }

    // 🔹 Remove duplicates by OT request ID
    const uniqueData = Array.from(
      new Map(data.map((item) => [item.id, item])).values()
    );

    // 🔹 Apply client-side status filter if provided and not 'all' (default handled upstream)
    let filteredData = uniqueData;
    if (filters.status && String(filters.status).toLowerCase() !== "all") {
      const desired = String(filters.status).toLowerCase();
      filteredData = uniqueData.filter((item) => {
        return (item.status || "").toString().toLowerCase() === desired;
      });
    }

    // 🔹 Show fallback message when client-side filters return no rows
    if (!filteredData || filteredData.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="8" class="text-center text-muted">No overtime requests found.</td></tr>';
      renderOTPagination(null, filters);
      return;
    }

    filteredData.forEach((row) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${row.date_created}</td>
        <td>${row.employee_name}</td>
        <td>${row.tracker_date}</td>
        <td>${row.hours}</td>
        <td class = "text-center">
          <span class="badge bg-${getStatusColor(row.status)} text-uppercase">${
        row.status
      }</span>
        </td>
        <td>${row.checked_by || "-"}</td>
        <td>${row.remarks || "-"}</td>
        <td>
          <button class="btn btn-sm btn-success" onclick="viewOTRequest(${
            row.id
          })">View</i>
          </button>
        </td>`;
      tbody.appendChild(tr);
    });

    // Render pagination controls if backend provided metadata
    renderOTPagination(pagination, filters);
  };

  // Use global fetchWithLoader if available to show the global loader
  if (typeof fetchWithLoader === "function") {
    return fetchWithLoader(url)
      .then((data) => render(data))
      .catch((err) => console.error("Error loading OT requests:", err));
  }

  return fetch(url)
    .then((res) => res.json())
    .then((data) => render(data))
    .catch((err) => console.error("Error loading OT requests:", err));
}

// Render pagination UI for the OT requests table
function renderOTPagination(pagination, currentFilters = {}) {
  const container = document.getElementById("otRequestsPagination");
  if (!container) return;
  container.innerHTML = "";

  if (!pagination || !pagination.totalPages || pagination.totalPages <= 1)
    return;

  const totalPages = parseInt(pagination.totalPages, 10) || 1;
  const currentPage =
    parseInt(pagination.page || currentFilters.page || 1, 10) || 1;
  const maxButtons = 7;

  const createBtn = (txt, page, cls = "btn-outline-success") => {
    const b = document.createElement("button");
    b.className = `btn ${cls} mx-1`;
    b.textContent = txt;
    b.addEventListener("click", () => {
      const filters = Object.assign({}, currentFilters, { page });
      loadOTRequests(filters);
    });
    return b;
  };

  // Prev
  if (currentPage > 1) {
    container.appendChild(createBtn("Prev", currentPage - 1));
  }

  // Page window
  let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
  let endPage = startPage + maxButtons - 1;
  if (endPage > totalPages) {
    endPage = totalPages;
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  for (let p = startPage; p <= endPage; p++) {
    const cls = p === currentPage ? "btn-success" : "btn-outline-success";
    container.appendChild(createBtn(p, p, cls));
  }

  // Next
  if (currentPage < totalPages) {
    container.appendChild(createBtn("Next", currentPage + 1));
  }
}

// =======================================================
// 🔹 FILTER & RESET BUTTON NEW
// =======================================================
function filterOTRequests() {
  const start = document.getElementById("otStartDate")?.value.trim() || "";
  const end = document.getElementById("otendDate")?.value.trim() || "";

  const dept =
    document.getElementById("otSummaryDepartmentFilter")?.value || "";
  const user = document.getElementById("otUserDropdown")?.value || "";
  const status = document.getElementById("otFilterStatus")?.value || "pending";

  const filters = {
    start: start,
    end: end,
    dept: dept,
    user: user,
    status: status,
    page: 1,
    limit: 15,
  };

  console.log("APPLY FILTERS:", filters);
  loadOTRequests(filters);
}

function resetOTRequests() {
  // Reset date inputs
  document.getElementById("otStartDate").value = "";
  document.getElementById("otendDate").value = "";

  // Reset filters
  const dept = document.getElementById("otSummaryDepartmentFilter");
  if (dept) dept.value = "";

  const user = document.getElementById("otUserDropdown");
  if (user) user.value = "";

  const status = document.getElementById("otFilterStatus");
  if (status) status.value = "pending";

  // Reload OT requests with default filters (pending only)
  loadOTRequests({ status: "pending", page: 1, limit: 15 });
}

// =======================================================
// 🔹 STATUS COLOR HELPER
// =======================================================
function getStatusColor(status) {
  switch (status) {
    case "approved":
      return "success";
    case "rejected":
      return "danger";
    default:
      return "warning";
  }
}

// =======================================================
// 🔹 VIEW / APPROVE OT REQUEST LOGIC
// =======================================================

function viewOTRequest(requestId) {
  const overlay = showLoadingOverlay();
  fetch(`../backend/ot-requests/get_single_ot_request.php?id=${requestId}`)
    .then((res) => res.json())
    .then((data) => {
      hideLoadingOverlay(overlay);

      if (data.status !== "success") {
        alert("Failed to load OT request.");
        return;
      }

      const request = data.request;

      // Reset modal state first (enable all inputs and buttons)
      resetOTModalState();

      // Fill modal fields
      document.getElementById("otViewRequestId").value = request.id;
      document.getElementById("otViewEmployee").value = request.employee_name;
      document.getElementById("otViewTrackerDate").value = request.tracker_date;
      document.getElementById("otViewHours").value = formatHoursForDisplay(
        request.hours
      ); // ✅ Remove seconds
      document.getElementById("otViewReason").value = request.reason;
      document.getElementById("otViewStatus").value = request.status;
      document.getElementById("otViewRemarks").value = request.remarks || "";

      // Populate Recipient dropdown
      const recipientSelect = document.getElementById("otViewRecipient");
      recipientSelect.innerHTML = data.recipients
        .map(
          (r) =>
            `<option value="${r.id}" ${
              r.id === request.recipient_id ? "selected" : ""
            }>${r.name}</option>`
        )
        .join("");

      // Role-based buttons
      toggleOTModalButtonsBasedOnRole(
        data.user_role,
        data.request.request_owner_id,
        data.has_access // <-- use this
      );

      // ✅ Check if request is already approved or rejected - disable buttons and make read-only
      if (request.status === "approved" || request.status === "rejected") {
        makeOTModalReadOnly();
      }

      // Show modal
      const modal = new bootstrap.Modal(
        document.getElementById("viewOTRequestModal")
      );
      modal.show();
    })
    .catch((err) => {
      hideLoadingOverlay(overlay);
      console.error("Error fetching OT request:", err);
      alert("An error occurred while loading OT request.");
    });
}

// =======================================================
// 🔹 ROLE-BASED BUTTON VISIBILITY w/ HIDDEN REMARKS FOR USER (NEW) V3 TEST
// =======================================================

function toggleOTModalButtonsBasedOnRole(userRole, requestOwnerId, hasAccess) {
  const approveBtn = document.getElementById("approveOTRequestBtn");
  const rejectBtn = document.getElementById("rejectOTRequestBtn");
  const saveBtn = document.getElementById("saveOTRequestBtn");
  const remarksField = document.getElementById("otViewRemarks");

  const currentUserId = typeof USER_ID !== "undefined" ? USER_ID : null;

  let canApprove = false;

  // ------------------------------------
  // ADMIN + EXECUTIVE -> Full Access
  // ------------------------------------
  if (["admin", "executive"].includes(userRole)) {
    canApprove = true;
    // ------------------------------------
    // HR -> Can approve except their own request
    // ------------------------------------
  } else if (userRole === "hr") {
    if (currentUserId && requestOwnerId != currentUserId) canApprove = true;
    // ------------------------------------
    // SUPERVISOR RULES
    // ------------------------------------
  } else if (userRole === "supervisor") {
    // Use PHP-computed access
    if (hasAccess && requestOwnerId != currentUserId) canApprove = true;
  }
  // ------------------------------------
  // APPLY BUTTON VISIBILITY
  // ------------------------------------
  if (canApprove) {
    approveBtn.classList.remove("d-none");
    rejectBtn.classList.remove("d-none");
    remarksField.closest(".mb-3").classList.remove("d-none");
  } else {
    approveBtn.classList.add("d-none");
    rejectBtn.classList.add("d-none");
    // ❌ Non-approvers should NOT see remarks
    remarksField.closest(".mb-3").classList.add("d-none");
  }
  // Save button ALWAYS visible
  saveBtn.classList.remove("d-none");
}

// =======================================================
// 🔹 HELPER: SHOW ALERT INSIDE MODAL
// =======================================================
function showOTModalAlert(message, type = "success") {
  const alertEl = document.getElementById("viewOTAlert");
  alertEl.textContent = message;
  alertEl.className = `alert alert-${type} mt-2`;
  alertEl.classList.remove("d-none");
  setTimeout(() => {
    alertEl.classList.add("d-none");
  }, 4000);
}

// =======================================================
// 🔹 SAVE OT REQUEST CHANGES WITH CONFIRMATION & AUTO CLOSE
// =======================================================
document
  .getElementById("saveOTRequestBtn")
  .addEventListener("click", async function () {
    const confirmed = await AlertService.confirm(
      "Are you sure you want to save changes to this OT request?"
    );

 if (!confirmed) return;

    const requestId = document.getElementById("otViewRequestId").value;
    const trackerDate = document.getElementById("otViewTrackerDate").value;
    const hours = document.getElementById("otViewHours").value.trim();
    const reason = document.getElementById("otViewReason").value;
    const recipientId = document.getElementById("otViewRecipient").value;

    if (!trackerDate || !hours || !reason || !recipientId) {
      showOTModalAlert("Please fill all required fields.", "danger");
      return;
    }

    // ✅ Validate hours format + minimum duration
    if (!validateHoursFormat(hours)) {
      const [hh, mm] = hours.split(":").map(Number);
      const totalMinutes = hh * 60 + mm;

      if (hours === "00:00") {
        showOTModalAlert("Overtime duration cannot be 00:00.", "danger");
      } else if (totalMinutes < 15) {
        showOTModalAlert("Minimum overtime duration is 15 minutes.", "danger");
      } else {
        showOTModalAlert(
          "Please enter hours in HH:MM format (e.g., 02:30).",
          "danger"
        );
      }

      document.getElementById("otViewHours").focus();
      return;
    }

    const overlay = showLoadingOverlay();
    fetch("../backend/ot-requests/update_ot_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: requestId,
        tracker_date: trackerDate,
        hours: hours,
        reason: reason,
        recipient_id: recipientId,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        hideLoadingOverlay(overlay);
        if (data.status === "success") {
          showOTModalAlert("Changes saved successfully.", "success");
          refreshOTRequestsTable();

          // Make inputs read-only and disable buttons after save
          makeOTModalReadOnly();
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("viewOTRequestModal")
            ).hide();
          }, 2000);
        } else {
          showOTModalAlert(data.message || "Failed to save changes.", "danger");
        }
      })
      .catch((err) => {
        hideLoadingOverlay(overlay);
        console.error("Error saving OT request:", err);
        showOTModalAlert("An error occurred while saving changes.", "danger");
      });
  });

// =======================================================
// 🔹 APPROVE OT REQUEST WITH CONFIRMATION & AUTO CLOSE
// =======================================================

document
  .getElementById("approveOTRequestBtn")
  .addEventListener("click", function () {
    if (!confirm("Are you sure you want to approve this OT request?")) return;

    const requestId = document.getElementById("otViewRequestId").value;
    const remarks = document.getElementById("otViewRemarks").value.trim();

    const overlay = showLoadingOverlay();
    fetch("../backend/ot-requests/approve_ot_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: requestId, remarks: remarks }),
    })
      .then((res) => res.json())
      .then((data) => {
        hideLoadingOverlay(overlay);
        if (data.status === "success") {
          showOTModalAlert("Request approved successfully.", "success");
          refreshOTRequestsTable();
          makeOTModalReadOnly(); // make modal read-only
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("viewOTRequestModal")
            ).hide();
          }, 2000);
        } else {
          showOTModalAlert(
            data.message || "Failed to approve request.",
            "danger"
          );
        }
      })
      .catch((err) => {
        hideLoadingOverlay(overlay);
        console.error("Error approving OT request:", err);
        showOTModalAlert("An error occurred while approving.", "danger");
      });
  });

// =======================================================
// 🔹 REJECT OT REQUEST WITH CONFIRMATION & AUTO CLOSE
// =======================================================
document
  .getElementById("rejectOTRequestBtn")
  .addEventListener("click", function () {
    if (!confirm("Are you sure you want to reject this OT request?")) return;

    const requestId = document.getElementById("otViewRequestId").value;
    const remarks = document.getElementById("otViewRemarks").value.trim();

    const overlay = showLoadingOverlay();
    fetch("../backend/ot-requests/reject_ot_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: requestId, remarks: remarks }),
    })
      .then((res) => res.json())
      .then((data) => {
        hideLoadingOverlay(overlay);
        if (data.status === "success") {
          showOTModalAlert("Request rejected successfully.", "success");
          refreshOTRequestsTable();
          makeOTModalReadOnly(); // make modal read-only
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("viewOTRequestModal")
            ).hide();
          }, 2000);
        } else {
          showOTModalAlert(
            data.message || "Failed to reject request.",
            "danger"
          );
        }
      })
      .catch((err) => {
        hideLoadingOverlay(overlay);
        console.error("Error rejecting OT request:", err);
        showOTModalAlert("An error occurred while rejecting.", "danger");
      });
  });

// =======================================================
// 🔹 RESET MODAL STATE (Enable inputs and buttons) (NEW VERSION)
// =======================================================

function resetOTModalState() {
  // Enable all inputs & textareas (except employee and status)
  document
    .querySelectorAll(
      "#viewOTRequestForm input:not(#otViewEmployee):not(#otViewStatus), #viewOTRequestForm textarea, #viewOTRequestForm select"
    )
    .forEach((el) => {
      el.disabled = false;
    });

  const employeeField = document.getElementById("otViewEmployee");
  const statusField = document.getElementById("otViewStatus");
  if (employeeField) employeeField.disabled = true;
  if (statusField) statusField.disabled = true;

  // Show footer buttons again
  const saveBtn = document.getElementById("saveOTRequestBtn");
  const approveBtn = document.getElementById("approveOTRequestBtn");
  const rejectBtn = document.getElementById("rejectOTRequestBtn");

  if (saveBtn) saveBtn.style.display = "inline-block";
  if (approveBtn) approveBtn.style.display = "inline-block";
  if (rejectBtn) rejectBtn.style.display = "inline-block";
}

// =======================================================
// 🔹 MAKE MODAL READ-ONLY & DISABLE BUTTONS (NEW VERSION)
// =======================================================

function makeOTModalReadOnly() {
  // Disable all inputs & textareas
  document
    .querySelectorAll(
      "#viewOTRequestForm input, #viewOTRequestForm textarea, #viewOTRequestForm select"
    )
    .forEach((el) => {
      el.disabled = true;
    });

  // Footer buttons
  const saveBtn = document.getElementById("saveOTRequestBtn");
  const approveBtn = document.getElementById("approveOTRequestBtn");
  const rejectBtn = document.getElementById("rejectOTRequestBtn");

  if (saveBtn) saveBtn.style.display = "none";
  if (approveBtn) approveBtn.style.display = "none";
  if (rejectBtn) rejectBtn.style.display = "none";
}

// =======================================================
// 🔹 REFRESH OT REQUEST TABLE AFTER ACTIONS
// =======================================================
function refreshOTRequestsTable() {
  filterOTRequests();
}
