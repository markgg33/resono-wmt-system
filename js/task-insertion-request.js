// WORKING VERSION

// ===========================
// UTILITY FUNCTIONS
// ===========================
/*function formatDateTime(datetime) {
  if (!datetime) return "N/A";
  const date = new Date(datetime);
  return date.toLocaleString("en-PH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
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

// Format "HH:MM:SS" → "HH:MM", and round up sub-minute seconds to 1 minute if needed
function formatTimeHHMM(timeStr) {
  if (!timeStr || timeStr === "--") return "--";

  const parts = timeStr.trim().split(":");
  const h = parseInt(parts[0] || 0, 10);
  const m = parseInt(parts[1] || 0, 10);
  const s = parseInt(parts[2] || 0, 10);

  // If less than a minute, display as 00:01
  const displayMinutes = h === 0 && m === 0 && s > 0 ? 1 : m;

  return `${String(h).padStart(2, "0")}:${String(displayMinutes).padStart(
    2,
    "0"
  )}`;
}

// ===========================
// LOAD ALL TASK INSERTION REQUESTS
// ===========================
document.addEventListener("DOMContentLoaded", loadTaskInsertionRequests);

function loadTaskInsertionRequests() {
  fetch("../backend/dtr-requests/get_task_insertion_request.php")
    .then((res) => res.json())
    .then((data) => {
      const tableBody = document.getElementById(
        "task-insertion-table"
      );
      tableBody.innerHTML = "";

      if (!data.length) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No requests found.</td></tr>`;
        return;
      }

      data.forEach((req) => {
        const row = document.createElement("tr");

        row.innerHTML = `
          <td><span class="badge bg-success">${req.request_uid}</span></td>
          <td>${req.requestor_name}</td>
          <td><span class="badge ${getStatusClass(req.status)}">${
          req.status
        }</span></td>
          <td><button class="btn btn-success btn-sm view-btn"> 
              <i class="bi bi-eye"></i> View
          </button></td>
        `;

        // Add event listener to view button
        row.querySelector(".view-btn").addEventListener("click", () => {
          viewRequest(req.request_uid);
        });

        tableBody.appendChild(row);
      });
    })
    .catch((err) => console.error("Error loading requests:", err));
}

// ===========================
// VIEW REQUEST MODAL
// ===========================
function viewRequest(request_uid) {
  fetch(
    `../backend/dtr-requests/get_task_insertion_details.php?request_uid=${encodeURIComponent(
      request_uid
    )}`
  )
    .then((res) => res.json())
    .then((req) => {
      // Populate modal fields
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

      // Handle approve/reject buttons
      const actionButtons = document.getElementById("actionButtons");
      actionButtons.innerHTML = "";

      if (req.status === "Pending") {
        const approveBtn = document.createElement("button");
        approveBtn.className = "btn btn-success";
        approveBtn.textContent = "Approve";
        approveBtn.addEventListener("click", () =>
          updateRequestStatus(req.request_uid, "Approved")
        );

        const rejectBtn = document.createElement("button");
        rejectBtn.className = "btn btn-danger ms-2";
        rejectBtn.textContent = "Reject";
        rejectBtn.addEventListener("click", () =>
          updateRequestStatus(req.request_uid, "Rejected")
        );

        actionButtons.appendChild(approveBtn);
        actionButtons.appendChild(rejectBtn);
      } else {
        actionButtons.innerHTML = `<span class="text-muted">No actions available</span>`;
      }

      // Show modal
      const modal = new bootstrap.Modal(
        document.getElementById("viewTaskInsertionModal")
      );
      modal.show();
    })
    .catch((err) => console.error("Error fetching request details:", err));
}

// ===========================
// APPROVE/REJECT REQUEST (Bootstrap/native confirm)
// ===========================
function updateRequestStatus(request_uid, newStatus) {
  const confirmed = window.confirm(
    `Are you sure you want to mark this request as ${newStatus}?`
  );
  if (!confirmed) return;

  // Get alert container inside modal
  const alertContainer = document.getElementById("modalAlertContainer");
  alertContainer.innerHTML = ""; // clear previous alerts

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
      alertContainer.appendChild(alertDiv);

      if (data.success) {
        // Reload requests table after a short delay
        setTimeout(() => {
          const modalEl = document.getElementById("viewTaskInsertionModal");
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();

          loadTaskInsertionRequests();
        }, 1000); // 1s so user sees the alert
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
      alertContainer.appendChild(alertDiv);
    });
}

let currentPageReq = 1;
const limitReq = 10;

document.addEventListener("DOMContentLoaded", () => {
  loadTaskInsertionRequests();

  // Search and filter triggers
  document.getElementById("searchRequest").addEventListener("input", () => {
    currentPageReq = 1;
    loadTaskInsertionRequests();
  });

  document.getElementById("filterStatus").addEventListener("change", () => {
    currentPageReq = 1;
    loadTaskInsertionRequests();
  });
});

function loadTaskInsertionRequests() {
  const search = document.getElementById("searchRequest").value.trim();
  const status = document.getElementById("filterStatus").value;

  fetch(
    `../backend/dtr-requests/get_task_insertion_request.php?page=${currentPageReq}&limit=${limitReq}&search=${encodeURIComponent(
      search
    )}&status=${encodeURIComponent(status)}`
  )
    .then((res) => res.json())
    .then((data) => {
      const tableBody = document.getElementById(
        "task-insertion-table"
      );
      const pagination = document.getElementById("requests-pagination");
      tableBody.innerHTML = "";

      if (!data.success || !data.requests.length) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No requests found.</td></tr>`;
        pagination.innerHTML = "";
        return;
      }

      data.requests.forEach((req) => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td><span class="badge bg-success">${req.request_uid}</span></td>
          <td>${req.requestor_name}</td>
          <td><span class="badge ${getStatusClass(req.status)}">${
          req.status
        }</span></td>
          <td><button class="btn btn-success btn-sm view-btn"><i class="bi bi-eye"></i> View</button></td>
        `;
        row
          .querySelector(".view-btn")
          .addEventListener("click", () => viewRequest(req.request_uid));
        tableBody.appendChild(row);
      });

      renderPagination(data.total, data.page, data.limit, "requests");
    })
    .catch((err) => console.error("Error loading requests:", err));
}

// Pagination renderer
function renderPagination(total, page, limit, type) {
  const container = document.getElementById(`${type}-pagination`);
  container.innerHTML = "";
  const totalPages = Math.ceil(total / limit);

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.className = `btn btn-sm ${
      i === page ? "btn-success" : "btn-outline-success"
    } mx-1`;
    btn.textContent = i;
    btn.onclick = () => {
      if (type === "requests") {
        currentPageReq = i;
        loadTaskInsertionRequests();
      } else {
        currentPageHist = i;
        loadTaskInsertionHistory();
      }
    };
    container.appendChild(btn);
  }
}*/

// ===========================
// UTILITY FUNCTIONS
// ===========================
function formatDateTime(datetime) {
  if (!datetime) return "N/A";
  const date = new Date(datetime);
  return date.toLocaleString("en-PH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function getStatusClass(status) {
  switch (status?.toLowerCase()) {
    case "approved":
      return "bg-success";
    case "rejected":
      return "bg-danger";
    default:
      return "bg-warning text-dark";
  }
}

function formatTimeHHMM(timeStr) {
  if (!timeStr || timeStr === "--") return "--";
  const [h = 0, m = 0, s = 0] = timeStr.trim().split(":").map(Number);
  const displayMinutes = h === 0 && m === 0 && s > 0 ? 1 : m;
  return `${String(h).padStart(2, "0")}:${String(displayMinutes).padStart(
    2,
    "0"
  )}`;
}

// ===========================
// LOCAL SCOPED VARIABLES
// ===========================
let taskPage = 1;
const taskLimit = 10;

// ===========================
// MAIN LOADER (SAFE GUARD)
// ===========================
function initTaskInsertionRequests() {
  const table = document.getElementById("task-insertion-table");
  if (!table) return; // stop if page doesn’t have task insertion section

  loadTaskInsertionRequests();

  const searchInput = document.getElementById("searchRequest");
  const statusSelect = document.getElementById("filterStatus");

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      taskPage = 1;
      loadTaskInsertionRequests();
    });
  }

  if (statusSelect) {
    statusSelect.addEventListener("change", () => {
      taskPage = 1;
      loadTaskInsertionRequests();
    });
  }
}

document.addEventListener("DOMContentLoaded", initTaskInsertionRequests);

// ===========================
// LOAD TASK INSERTION REQUESTS
// ===========================
function loadTaskInsertionRequests() {
  const tableBody = document.getElementById("task-insertion-table");
  const pagination = document.getElementById("requests-pagination");
  if (!tableBody) return;

  const search = document.getElementById("searchRequest")?.value?.trim() || "";
  const status = document.getElementById("filterStatus")?.value || "";

  fetch(
    `../backend/dtr-requests/get_task_insertion_request.php?page=${taskPage}&limit=${taskLimit}&search=${encodeURIComponent(
      search
    )}&status=${encodeURIComponent(status)}`
  )
    .then((res) => res.json())
    .then((data) => {
      tableBody.innerHTML = "";

      if (!data.success || !data.requests?.length) {
        tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">No requests found.</td></tr>`;
        if (pagination) pagination.innerHTML = "";
        return;
      }

      data.requests.forEach((req) => {
        const row = document.createElement("tr");
        row.innerHTML = `
    <td><span class="badge bg-success">${req.request_uid}</span></td>
    <td>${req.date || "N/A"}</td>
    <td>${req.work_mode || "N/A"}</td>
    <td>${req.task_desc || "N/A"}</td>
    <td>${req.start_time || "N/A"}</td>
    <td>${req.end_time || "N/A"}</td>
    <td><span class="badge ${getStatusClass(req.status)}">${
          req.status
        }</span></td>
    <td>${req.requester_name || "N/A"}</td>
    <td>${req.processed_by_name || "Pending"}</td>
    <td>
      <button class="btn btn-success btn-sm view-btn">
        <i class="bi bi-eye"></i> View
      </button>
    </td>
  `;

        row
          .querySelector(".view-btn")
          .addEventListener("click", () => viewRequest(req.request_uid));

        tableBody.appendChild(row);
      });

      if (pagination) renderTaskPagination(data.total, data.page, data.limit);
    })
    .catch((err) =>
      console.error("Error loading Task Insertion Requests:", err)
    );
}

// ===========================
// PAGINATION (ISOLATED)
// ===========================
function renderTaskPagination(total, page, limit) {
  const container = document.getElementById("requests-pagination");
  if (!container) return;

  container.innerHTML = "";
  const totalPages = Math.ceil(total / limit);

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.className = `btn btn-sm ${
      i === page ? "btn-success" : "btn-outline-success"
    } mx-1`;
    btn.textContent = i;
    btn.onclick = () => {
      taskPage = i;
      loadTaskInsertionRequests();
    };
    container.appendChild(btn);
  }
}

// ===========================
// VIEW REQUEST MODAL
// ===========================
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

      if (req.status === "Pending") {
        const approveBtn = document.createElement("button");
        approveBtn.className = "btn btn-success";
        approveBtn.textContent = "Approve";
        approveBtn.addEventListener("click", () =>
          updateRequestStatus(req.request_uid, "Approved")
        );

        const rejectBtn = document.createElement("button");
        rejectBtn.className = "btn btn-danger ms-2";
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

// ===========================
// UPDATE STATUS
// ===========================
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
          loadTaskInsertionRequests();
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
