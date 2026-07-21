//WORKING VERSION
/*
// ============================================
// 📋 UNIFIED TASK INSERTION TABLE (Requests + History)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  const tableBody = document.getElementById("task-insertion-table");
  const paginationContainer = document.getElementById("task-pagination");
  const searchInput = document.getElementById("searchRequest");
  const statusFilter = document.getElementById("filterStatus");

  let currentPage = 1;
  const limit = 10;

  // ============================================
  // 🧭 GET CURRENT ROLE FROM HIDDEN INPUT OR GLOBAL VARIABLE
  // ============================================
  // Make sure your PHP page includes something like:
  // <script>const USER_ROLE = "<?= strtolower($_SESSION['role']); ?>";</script>
  const userRole = typeof USER_ROLE !== "undefined" ? USER_ROLE : "user";

  // Utility
  function formatTimeHHMM(timeStr) {
    if (!timeStr || timeStr === "--") return "--";
    const [h, m] = timeStr.split(":");
    return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
  }

  function getStatusClass(status) {
    switch (status.toLowerCase()) {
      case "approved":
        return "bg-success";
      case "rejected":
        return "bg-danger";
      default:
        return "bg-warning text-dark";
    }
  }

  // ============================================
  // 🔍 LOAD DATA FUNCTION
  // ============================================
  function loadTaskInsertions(page = 1, search = "", status = "All") {
    tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>`;

    fetch(
      `../backend/dtr-requests/get_task_insertion_request.php?page=${page}&limit=${limit}&search=${encodeURIComponent(
        search
      )}&status=${encodeURIComponent(status)}`
    )
      .then((res) => res.json())
      .then((data) => {
        tableBody.innerHTML = "";

        if (!data.success || !data.requests?.length) {
          tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">No task insertion records found.</td></tr>`;
          paginationContainer.innerHTML = "";
          return;
        }

        data.requests.forEach((req) => {
          const processedBy = req.processed_by_name?.trim() || "-";
          const processedAt = req.processed_at
            ? new Date(req.processed_at).toLocaleString()
            : "-";
          const start = req.start_time ? formatTimeHHMM(req.start_time) : "-";
          const end = req.end_time ? formatTimeHHMM(req.end_time) : "-";

          // ============================================
          // 🧩 CREATE TABLE ROW
          // ============================================
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td><span class="badge bg-success">${
              req.request_uid || "-"
            }</span></td>
            <td>${req.date || "-"}</td>
            <td>${req.work_mode || "-"}</td>
            <td>${req.task_desc || "-"}</td>
            <td>${start}</td>
            <td>${end}</td>
            <td><span class="badge ${getStatusClass(req.status)}">${
            req.status
          }</span></td>
            <td>${req.requester_name || "-"}</td>
            <td>${processedBy}<br><small class="text-muted">${processedAt}</small></td>
            <td class="text-center action-cell"></td>
          `;

          // ============================================
          // 👁️ CONDITIONAL VIEW BUTTON (only for non-user roles)
          // ============================================
          if (["admin", "supervisor", "hr", "executive"].includes(userRole)) {
            const btn = document.createElement("button");
            btn.className = "btn btn-success btn-sm view-btn";
            btn.innerHTML = `<i class="bi bi-eye"></i> View`;
            btn.addEventListener("click", () => {
              viewRequest(req.request_uid);
            });
            tr.querySelector(".action-cell").appendChild(btn);
          }

          tableBody.appendChild(tr);
        });

        renderPagination(data.total, data.page, data.limit);
      })
      .catch((err) => {
        console.error("Error loading task insertion records:", err);
        tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">Error loading data.</td></tr>`;
      });
  }

  // ============================================
  // 📄 PAGINATION BUTTONS
  // ============================================
  function renderPagination(total, page, limit) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(total / limit);

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = `btn btn-sm ${
        i === page ? "btn-success" : "btn-outline-success"
      } m-1`;
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
      });
      paginationContainer.appendChild(btn);
    }
  }

  // ============================================
  // 🔎 FILTER EVENTS
  // ============================================
  searchInput.addEventListener("input", () => {
    currentPage = 1;
    loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
  });

  statusFilter.addEventListener("change", () => {
    currentPage = 1;
    loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
  });

  // ============================================
  // 🚀 INITIAL LOAD
  // ============================================
  loadTaskInsertions(currentPage, "", "Pending");
});*/

//NEW WORKING VERSION
// ============================================
// 🌐 GLOBAL HELPERS (Accessible Everywhere)
// ============================================

//WORKING VERSION OF TIME FORMAT
/*
function formatTimeHHMM(timeStr) {
  if (!timeStr || timeStr === "--") return "--";
  const [h, m] = timeStr.split(":");
  return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
}*/

function formatTimeHHMM(timeStr) {
  if (
    !timeStr ||
    timeStr === "--" ||
    timeStr === "0000-00-00 00:00" ||
    timeStr === "0000-00-00 00:00:00"
  )
    return "--";

  // If datetime string (e.g. 2025-11-06 13:30:00)
  if (timeStr.includes(" ")) {
    const [, timePart] = timeStr.split(" ");
    const [h, m] = timePart.split(":");
    return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
  }

  // If plain HH:MM string
  const [h, m] = timeStr.split(":");
  return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
}

function getStatusClass(status) {
  switch (status.toLowerCase()) {
    case "approved":
      return "bg-success";
    case "rejected":
      return "bg-danger";
    default:
      return "bg-warning text-dark";
  }
}

function formatDateTime(datetime) {
  if (!datetime) return "N/A";
  const date = new Date(datetime);
  return date.toLocaleString("en-PH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

// ============================================
// 📋 UNIFIED TASK INSERTION TABLE (Requests + History)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  const tableBody = document.getElementById("task-insertion-table");
  const paginationContainer = document.getElementById("task-pagination");
  const searchInput = document.getElementById("searchRequest");
  const statusFilter = document.getElementById("filterStatus");

  let currentPage = 1;
  const limit = 10;

  const userRole = typeof USER_ROLE !== "undefined" ? USER_ROLE : "user";

  // ============================================
  // 🔍 LOAD DATA FUNCTION
  // ============================================
  function loadTaskInsertions(page = 1, search = "", status = "All") {
    tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>`;

    fetch(
      `../backend/dtr-requests/get_task_insertion_request.php?page=${page}&limit=${limit}&search=${encodeURIComponent(
        search
      )}&status=${encodeURIComponent(status)}`
    )
      .then((res) => res.json())
      .then((data) => {
        tableBody.innerHTML = "";

        if (!data.success || !data.requests?.length) {
          tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">No task insertion records found.</td></tr>`;
          paginationContainer.innerHTML = "";
          return;
        }

        data.requests.forEach((req) => {
          const processedBy = req.processed_by_name?.trim() || "-";
          const processedAt = req.processed_at
            ? new Date(req.processed_at).toLocaleString()
            : "-";
          const start = req.start_time ? formatTimeHHMM(req.start_time) : "-";
          const end = req.end_time ? formatTimeHHMM(req.end_time) : "-";

          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td><span class="badge bg-success">${
              req.request_uid || "-"
            }</span></td>
            <td>${req.date || "-"}</td>
            <td>${req.work_mode || "-"}</td>
            <td>${req.task_desc || "-"}</td>
            <td>${start}</td>
            <td>${end}</td>
            <td><span class="badge ${getStatusClass(req.status)}">${
            req.status
          }</span></td>
            <td>${req.requester_name || "-"}</td>
            <td>${processedBy}<br><small class="text-muted">${processedAt}</small></td>
            <td class="text-center action-cell"></td>
          `;

          // 👁️ Add "View" button for all roles
          const btn = document.createElement("button");
          btn.className = "btn btn-success btn-sm view-btn";
          btn.innerHTML = `<i class="bi bi-eye"></i> View`;
          btn.addEventListener("click", () => {
            viewRequest(req.request_uid);
          });
          tr.querySelector(".action-cell").appendChild(btn);

          tableBody.appendChild(tr);
        });

        renderPagination(data.total, data.page, data.limit);
      })
      .catch((err) => {
        console.error("Error loading task insertion records:", err);
        tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">Error loading data.</td></tr>`;
      });
  }

  // ============================================
  // 📄 PAGINATION BUTTONS
  // ============================================
  function renderPagination(total, page, limit) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(total / limit);

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = `btn btn-sm ${
        i === page ? "btn-success" : "btn-outline-success"
      } m-1`;
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
      });
      paginationContainer.appendChild(btn);
    }
  }

  // ============================================
  // 🔎 FILTER EVENTS
  // ============================================
  searchInput.addEventListener("input", () => {
    currentPage = 1;
    loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
  });

  statusFilter.addEventListener("change", () => {
    currentPage = 1;
    loadTaskInsertions(currentPage, searchInput.value, statusFilter.value);
  });

  // 🚀 INITIAL LOAD
  loadTaskInsertions(currentPage, "", "Pending");
});

// ============================================
// 📄 VIEW TASK INSERTION REQUEST MODAL
// ============================================
function viewRequest(request_uid) {
  fetch(
    `../backend/dtr-requests/get_task_insertion_details.php?request_uid=${encodeURIComponent(
      request_uid
    )}`
  )
    .then((res) => res.json())
    .then((req) => {
      document.getElementById(
        "viewTaskRequestId"
      ).innerHTML = `<span class="badge bg-success">${req.request_uid}</span>`;
      document.getElementById("viewRequestor").textContent =
        req.requestor_name || "N/A";
      document.getElementById("viewTaskDate").textContent =
        req.task_date || "N/A";
      document.getElementById("viewDateRequested").textContent = formatDateTime(
        req.request_created_at
      );
      document.getElementById("viewWorkMode").textContent =
        req.work_mode || "N/A";
      document.getElementById("viewTaskDescription").textContent =
        req.task_description || "N/A";
      document.getElementById("viewStartTime").textContent =
        formatTimeHHMM(req.start_time) || "--";
      document.getElementById("viewEndTime").textContent =
        formatTimeHHMM(req.end_time) || "--";
      document.getElementById("viewTaskReason").textContent =
        req.reason || "No reason provided";
      document.getElementById(
        "viewTaskStatus"
      ).innerHTML = `<span class="badge ${getStatusClass(req.status)}">${
        req.status
      }</span>`;

      const actionButtons = document.getElementById("actionButtons");
      actionButtons.innerHTML = "";

      //COMMENT OUT CODE (WORKING VERSION)
      /*
      if (
        req.status === "Pending" &&
        ["admin", "hr", "executive", "supervisor"].includes(USER_ROLE)
      ) {
        */
      if (req.status === "Pending" && req.can_approve === true) {
        const approveBtn = document.createElement("button");
        approveBtn.className = "btn btn-success rounded-pill px-4";
        approveBtn.textContent = "Approve";
        approveBtn.addEventListener("click", () =>
          updateRequestStatus(req.request_uid, "Approved")
        );

        const rejectBtn = document.createElement("button");
        rejectBtn.className = "btn btn-danger rounded-pill px-4 ms-2";
        rejectBtn.textContent = "Reject";
        rejectBtn.addEventListener("click", () =>
          updateRequestStatus(req.request_uid, "Rejected")
        );

        actionButtons.appendChild(approveBtn);
        actionButtons.appendChild(rejectBtn);
      } else {
        actionButtons.innerHTML = `<span class="text-muted">No actions available</span>`;
      }

      const modal = new bootstrap.Modal(
        document.getElementById("viewTaskInsertionModal")
      );
      modal.show();
    })
    .catch((err) => console.error("Error fetching request details:", err));
}

// ============================================
// ✅ APPROVE / REJECT UPDATE HANDLER
// ============================================
function updateRequestStatus(request_uid, newStatus) {
  if (!confirm(`Are you sure you want to mark this request as ${newStatus}?`))
    return;

  const alertContainer = document.getElementById("modalAlertContainer");
  if (alertContainer) alertContainer.innerHTML = "";

  fetch("../backend/dtr-requests/update_task_insertion_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `request_uid=${encodeURIComponent(
      request_uid
    )}&status=${encodeURIComponent(newStatus)}`,
  })
    .then((res) => res.json())
    .then((data) => {
      const alertDiv = document.createElement("div");
      alertDiv.className = `alert alert-${
        data.success ? "success" : "danger"
      } alert-dismissible fade show mt-2`;
      alertDiv.role = "alert";
      alertDiv.innerHTML = `
        ${data.message || "Something went wrong."}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      alertContainer?.appendChild(alertDiv);

      if (data.success) {
        setTimeout(() => {
          const modalEl = document.getElementById("viewTaskInsertionModal");
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          modalInstance?.hide();

          // reload table
          document.dispatchEvent(new CustomEvent("reloadTaskInsertions"));
        }, 1000);
      }
    })
    .catch(() => {
      const alertDiv = document.createElement("div");
      alertDiv.className =
        "alert alert-danger alert-dismissible fade show mt-2";
      alertDiv.role = "alert";
      alertDiv.innerHTML = `
        Unable to connect to the server.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      alertContainer?.appendChild(alertDiv);
    });
}
