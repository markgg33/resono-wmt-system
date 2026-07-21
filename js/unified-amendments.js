//WORKING VERSION?
/*let currentPage = 1;
console.log("💡 Using renderRequests from unified-amendments.js");

document.addEventListener("DOMContentLoaded", () => {
  loadRequestors();
  fetchDtrRequests();

  document.getElementById("filterBtn").addEventListener("click", () => {
    currentPage = 1;
    fetchDtrRequests();
  });
});

function loadRequestors() {
  fetch("../backend/dtr-requests/get_requestors.php", {
    credentials: "include",
  })"current_user_id" => $userId,
    .then((res) => res.json())
    .then((data) => {
      const select = document.getElementById("filterRequestor");
      select.innerHTML = `<option value="">All Requestors</option>`;
      if (data.status === "success") {
        data.recipients.forEach((r) => {
          select.innerHTML += `<option value="${r.id}">${r.username}</option>`;
        });
      }
    });
}

function fetchDtrRequests(page = 1) {
  const requestor = document.getElementById("filterRequestor").value;
  const status = document.getElementById("amendmentFilterStatus").value;
  const params = new URLSearchParams({ page, requestor, status });

  fetch(`../backend/dtr-requests/get_combined_requests.php?${params}`, {
    credentials: "include",
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("🔍 Raw fetch result:", data); // ADD THIS LINE
      if (data.status === "success") {
        console.log("✅ Parsed requests:", data.requests); // ADD THIS LINE
        renderRequests(data.requests);
        renderPagination(data.pagination);
      } else {
        document.getElementById(
          "dtr-requests-table"
        ).innerHTML = `<tr><td colspan="11" class="text-muted">No requests found</td></tr>`;
      }
    })
    .catch(() => {
      document.getElementById(
        "dtr-requests-table"
      ).innerHTML = `<tr><td colspan="11" class="text-danger text-center">Failed to load requests.</td></tr>`;
    });
}

function renderRequests(requests) {
  const tbody = document.getElementById("dtr-requests-table");
  tbody.innerHTML = "";

  const currentUserId = parseInt(sessionStorage.getItem("user_id"));
  const currentRole = (sessionStorage.getItem("userRole") || "").toLowerCase();

  requests.forEach((r) => {
    const badge =
      r.status === "Pending"
        ? '<span class="badge bg-warning text-dark">Pending</span>'
        : r.status === "Approved"
        ? '<span class="badge bg-success">Approved</span>'
        : '<span class="badge bg-danger">Rejected</span>';

    let actions = "";

    if (r.status === "Pending") {
      // ✅ If this user is the requestor
      if (parseInt(r.requester_id) === currentUserId) {
        actions = `<button class="btn btn-sm btn-primary" onclick="openEditModal(${r.id})">Edit</button>`;
      }
      // ✅ If user is admin/hr/executive or assigned recipient
      else if (
        ["admin", "hr", "executive"].includes(currentRole) ||
        parseInt(r.recipient_id) === currentUserId
      ) {
        actions = `
          <button class="btn btn-sm btn-success" onclick="handleDecision(${r.id}, 'Approved')">Approve</button>
          <button class="btn btn-sm btn-danger" onclick="handleDecision(${r.id}, 'Rejected')">Reject</button>`;
      } else {
        actions = `<span class="text-muted">No Action</span>`;
      }
    } else {
      actions = `<button class="btn btn-sm btn-secondary" disabled>${r.status}</button>`;
    }

    tbody.innerHTML += `
      <tr>
        <td><span class="badge bg-success">${r.request_uid || "-"}</span></td>
        <td>${r.requester_name || "-"}</td>
        <td>${r.task_description || "-"}</td>
        <td>${r.field || "-"}</td>
        <td>${formatTo24Hour(r.old_value) || "-"}</td>
        <td>${formatTo24Hour(r.new_value) || "-"}</td>
        <td>${r.reason || "-"}</td>
        <td>${badge}</td>
        <td>${r.processed_by_name || r.processed_by || "-"}</td>
        <td>${r.requested_at || "-"}</td>
        <td>${actions}</td>
      </tr>`;
  });

  if (!requests.length) {
    tbody.innerHTML = `<tr><td colspan="11" class="text-muted text-center">No requests found</td></tr>`;
  }
}

function renderPagination(pagination) {
  const container = document.getElementById("dtr-pagination");
  container.innerHTML = "";
  if (!pagination || pagination.totalPages <= 1) return;
  for (let i = 1; i <= pagination.totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.classList.add(
      "btn",
      "btn-sm",
      "mx-1",
      i === pagination.currentPage ? "btn-success" : "btn-outline-success"
    );
    btn.addEventListener("click", () => {
      currentPage = i;
      fetchDtrRequests(i);
    });
    container.appendChild(btn);
  }
}

function formatTo24Hour(value) {
  if (!value || value === "--") return value;
  const parts = value.split(":");
  if (parts.length < 2) return value;
  return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}`;
}

function handleDecision(id, decision) {
  if (!confirm(`Are you sure to ${decision.toLowerCase()} this request?`))
    return;

  fetch("../backend/dtr-requests/process_amendment.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ request_id: id, decision }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert(`Request ${decision.toLowerCase()} successfully`);
        fetchDtrRequests(currentPage);
      } else alert("Error: " + data.message);
    })
    .catch(() => alert("Something went wrong!"));
}*/

// =====================================
// Helper to format combined amendment values (table + modal)
// =====================================
function formatAmendmentValue(value) {
  if (!value || value === "null" || value.trim() === "") return "--";
  value = value.trim();

  // 🕒 Convert 24-hour to 12-hour time (e.g., "20:40:00" → "8:40 PM")
  const formatTo12Hour = (timeStr) => {
    if (!timeStr) return "";
    const [h, m] = timeStr.split(":").map(Number);
    if (isNaN(h) || isNaN(m)) return timeStr;
    const period = h >= 12 ? "PM" : "AM";
    const hour12 = h % 12 || 12;
    return `${hour12}:${m.toString().padStart(2, "0")} ${period}`;
  };

  // ✅ Case 1: time-only values
  if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(value)) {
    const timeOnly = value.split(":").slice(0, 2).join(":");
    return formatTo12Hour(timeOnly);
  }

  // ✅ Case 2: date-only values
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const d = new Date(value);
    if (!isNaN(d)) {
      return d.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    }
  }

  // ✅ Case 3: date + time (e.g., "2025-11-02 20:40:00")
  if (/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/.test(value)) {
    const [date, time] = value.split(" ");
    const d = new Date(date);
    const dateFormatted = !isNaN(d)
      ? d.toLocaleDateString("en-US", {
          year: "numeric",
          month: "long",
          day: "numeric",
        })
      : date;
    const timeFormatted = formatTo12Hour(time);
    return `${dateFormatted} — ${timeFormatted}`;
  }

  // ✅ Case 4: combined format (e.g., "2025-11-02|20:40:00|21:50:00")
  if (value.includes("|")) {
    const [date, start, end] = value.split("|");
    let formatted = "";

    if (date && date.trim() !== "" && date !== "null") {
      const d = new Date(date);
      if (!isNaN(d)) {
        formatted += d.toLocaleDateString("en-US", {
          year: "numeric",
          month: "long",
          day: "numeric",
        });
      }
    }

    if (start && start.trim() !== "") {
      const s = start.trim().split(":").slice(0, 2).join(":");
      formatted += (formatted ? " — " : "") + formatTo12Hour(s);
    }

    if (end && end.trim() !== "") {
      const e = end.trim().split(":").slice(0, 2).join(":");
      formatted += " → " + formatTo12Hour(e);
    }

    return formatted || value;
  }

  return value;
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

function formatTo24Hour(value) {
  if (!value || value === "--") return value;
  const parts = value.split(":");
  if (parts.length < 2) return value;
  return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}`;
}

// ================================
// Main Initialization
// ================================
/*
let currentPage = 1;

document.addEventListener("DOMContentLoaded", () => {
  loadRequestors();
  fetchDtrRequests();

  // Filter button
  document.getElementById("filterBtn").addEventListener("click", () => {
    currentPage = 1;
    fetchDtrRequests();
  });

  // ✅ Attach Approve/Reject handlers only once
  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");

  if (approveBtn) {
    approveBtn.addEventListener("click", () => {
      const id = approveBtn.dataset.requestId;
      if (id) handleDecision(id, "Approved");
    });
  }

  if (rejectBtn) {
    rejectBtn.addEventListener("click", () => {
      const id = rejectBtn.dataset.requestId;
      if (id) handleDecision(id, "Rejected");
    });
  }
});*/

// ================================
// Main Initialization
// ================================
let currentPage = 1;

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchRequestor");
  const statusFilter = document.getElementById("amendmentFilterStatus");
  const filterBtn = document.getElementById("filterBtn");

  // 🚀 Set default value explicitly for safety
  statusFilter.value = "Pending";

  // 🚀 INITIAL LOAD — show Pending by default
  fetchDtrRequests(currentPage, "", "Pending");

  // ✅ Fetch only when filter button is clicked
  if (filterBtn) {
    filterBtn.addEventListener("click", () => {
      currentPage = 1;
      const search = searchInput ? searchInput.value.trim() : "";
      const status = statusFilter ? statusFilter.value : "";
      fetchDtrRequests(currentPage, search, status);
    });
  }

  // ✅ Attach Approve/Reject handlers once
  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");

  if (approveBtn) {
    approveBtn.addEventListener("click", () => {
      const id = approveBtn.dataset.requestId;
      if (id) handleDecision(id, "Approved");
    });
  }

  if (rejectBtn) {
    rejectBtn.addEventListener("click", () => {
      const id = rejectBtn.dataset.requestId;
      if (id) handleDecision(id, "Rejected");
    });
  }
});

// ================================
// Load Requestors for Filter
// ================================
/*
function loadRequestors() {
  fetch("../backend/dtr-requests/get_requestors.php", {
    credentials: "include",
  })
    .then((res) => res.json())
    .then((data) => {
      const select = document.getElementById("filterRequestor");
      select.innerHTML = `<option value="">All Requestors</option>`;
      if (data.status === "success") {
        data.recipients.forEach((r) => {
          select.innerHTML += `<option value="${r.id}">${r.username}</option>`;
        });
      }
    })
    .catch((err) => console.error("Failed to load requestors:", err));
}*/

// ================================
// Fetch Requests (with Pagination + Filter)
// ================================

/*function fetchDtrRequests(page = 1, search = "", status = "") {
  const params = new URLSearchParams({ page, requestor: search, status });

  showLoading();

  fetch(`../backend/dtr-requests/get_combined_requests.php?${params}`, {
    credentials: "include",
  })
    .then((res) => res.json())
    .then((data) => {
      hideLoading();
      if (data.status === "success") {
        renderRequests(
          data.requests,
          data.current_user_id,
          data.current_user_role
        );
        renderPagination(data.pagination);
      } else {
        document.getElementById("dtr-requests-table").innerHTML = `
          <tr><td colspan="11" class="text-muted text-center">No requests found</td></tr>`;
      }
    })
    .catch((err) => {
      hideLoading();
      console.error("Error fetching requests:", err);
      document.getElementById("dtr-requests-table").innerHTML = `
        <tr><td colspan="11" class="text-danger text-center">Failed to load requests.</td></tr>`;
    });
}*/

function fetchDtrRequests(page = 1, search = "", status = "") {
  const params = new URLSearchParams({
    page,
    search, // ✅ match the PHP $_GET['search']
    status,
  });

  showLoading();

  fetch(
    `../backend/dtr-requests/get_combined_requests.php?${params.toString()}`,
    {
      credentials: "include",
    }
  )
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then((data) => {
      hideLoading();

      if (data.status === "success" && Array.isArray(data.requests)) {
        renderRequests(
          data.requests,
          data.current_user_id,
          data.current_user_role
        );
        renderPagination(data.pagination, search, status);
      } else {
        document.getElementById("dtr-requests-table").innerHTML = `
          <tr><td colspan="11" class="text-muted text-center">No requests found</td></tr>`;
      }
    })
    .catch((err) => {
      hideLoading();
      console.error("Error fetching requests:", err);
      document.getElementById("").innerHTML = `
        <tr><td colspan="11" class="text-danger text-center">Failed to load requests.</td></tr>`;
    });
}

/* ================================
// Render Requests Table //WORKING VERSION
// ================================
function renderRequests(requests, currentUserId, currentRole) {
  const tbody = document.getElementById("dtr-requests-table");
  tbody.innerHTML = "";

  currentUserId = parseInt(currentUserId);
  currentRole = (currentRole || "").toLowerCase();

  requests.forEach((r) => {
    const badge =
      r.status === "Pending"
        ? '<span class="badge bg-warning text-dark">Pending</span>'
        : r.status === "Approved"
        ? '<span class="badge bg-success">Approved</span>'
        : '<span class="badge bg-danger">Rejected</span>';

    // ✅ Only show "Edit" if current user owns the request and it's pending
    let actions = `
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-success" onclick="openAmendmentModal(${
          r.id
        })">View</button>
        ${
          r.status === "Pending" && parseInt(r.requester_id) === currentUserId
            ? `<button class="btn btn-sm btn-primary" onclick="openAdminEditModal(${r.id})">Edit</button>`
            : ""
        }
      </div>
    `;

    tbody.innerHTML += `
      <tr>
        <td><span class="badge bg-success">${r.request_uid || "-"}</span></td>
        <td>${r.requester_name || "-"}</td>
        <td>${r.task_description || "-"}</td>
        <td>${r.field || "-"}</td>
        <td>${formatTo24Hour(r.old_value) || r.old_value || "-"}</td>
        <td>${formatTo24Hour(r.new_value) || r.new_value || "-"}</td>
        <td>${r.reason || "-"}</td>
        <td>${badge}</td>
        <td>${r.processed_by_name || r.processed_by || "-"}</td>
        <td>${r.requested_at || "-"}</td>
        <td>${actions}</td>
      </tr>`;
  });

  if (!requests.length) {
    tbody.innerHTML = `<tr><td colspan="11" class="text-muted text-center">No requests found</td></tr>`;
  }
}*/

// ================================
// Render Requests Table (final version with correct value formatting)
// ================================
function renderRequests(requests, currentUserId, currentRole) {
  const tbody = document.getElementById("dtr-requests-table");
  tbody.innerHTML = "";

  currentUserId = parseInt(currentUserId);
  currentRole = (currentRole || "").toLowerCase();

  // 🕒 Helper: convert to 12-hour format with AM/PM
  function formatTo12Hour(timeStr) {
    if (!timeStr) return "-";
    const [hour, minute] = timeStr.split(":").map(Number);
    if (isNaN(hour) || isNaN(minute)) return timeStr;
    const period = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minute.toString().padStart(2, "0")} ${period}`;
  }

  // ✅ Helper: format based on field type
  function formatAmendmentValue(value, field) {
    if (!value || value === "null") return "-";
    value = value.trim();

    // Case 1: If contains both date and time (e.g. "2025-11-02 20:44:00")
    if (/\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/.test(value)) {
      const [date, time] = value.split(" ");
      if (field === "start_time" || field === "end_time") {
        return formatTo12Hour(time);
      } else {
        return date;
      }
    }

    // Case 2: Only time
    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(value)) {
      const timeOnly = value.split(":").slice(0, 2).join(":");
      return formatTo12Hour(timeOnly);
    }

    // Case 3: Only date
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
      const d = new Date(value);
      if (!isNaN(d)) {
        return d.toLocaleDateString("en-US", {
          year: "numeric",
          month: "long",
          day: "numeric",
        });
      }
    }

    return value;
  }

  requests.forEach((r) => {
    const badge =
      r.status === "Pending"
        ? '<span class="badge bg-warning text-dark">Pending</span>'
        : r.status === "Approved"
        ? '<span class="badge bg-success">Approved</span>'
        : '<span class="badge bg-danger">Rejected</span>';

    // ✅ Only show "Edit" if current user owns the request and it's pending
    const actions = `
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-success" onclick="openAmendmentModal(${
          r.id
        })">View</button>
        ${
          r.status === "Pending" && parseInt(r.requester_id) === currentUserId
            ? `<button class="btn btn-sm btn-primary admin-edit-btn" data-id="${r.id}">Edit</button>`
            : ""
        }
      </div>
    `;

    // ✅ Format displayed values properly
    const oldVal = formatAmendmentValue(r.old_value, r.field);
    const newVal = formatAmendmentValue(r.new_value, r.field);

    tbody.innerHTML += `
      <tr>
        <td><span class="badge bg-success">${r.request_uid || "-"}</span></td>
        <td>${r.requester_name || "-"}</td>
        <td>${r.task_description || "-"}</td>
        <td>${oldVal}</td>
        <td>${newVal}</td>
        <td>${r.reason || "-"}</td>
        <td>${badge}</td>
        <td>${r.processed_by_name || r.processed_by || "-"}</td>
        <td>${r.requested_at || "-"}</td>
        <td>${actions}</td>
      </tr>`;
  });

  if (!requests.length) {
    tbody.innerHTML = `<tr><td colspan="11" class="text-muted text-center">No requests found</td></tr>`;
  }
}

// ================================
// Pagination Renderer
// ================================
function renderPagination(pagination, search, status) {
  const container = document.getElementById("dtr-pagination");
  container.innerHTML = "";

  if (!pagination || pagination.totalPages <= 1) return;

  const maxButtons = 10; // ✅ Limit pagination buttons
  let startPage = Math.max(
    1,
    pagination.currentPage - Math.floor(maxButtons / 2)
  );
  let endPage = startPage + maxButtons - 1;

  if (endPage > pagination.totalPages) {
    endPage = pagination.totalPages;
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.classList.add(
      "btn",
      "btn-sm",
      "mx-1",
      i === pagination.currentPage ? "btn-success" : "btn-outline-success"
    );
    btn.addEventListener("click", () => {
      currentPage = i;
      const search = document.getElementById("searchRequestor").value;
      const status = document.getElementById("amendmentFilterStatus").value;
      fetchDtrRequests(currentPage, search, status);
    });
    container.appendChild(btn);
  }
}

// ================================
// View / Approve / Reject Modal Logic
// ================================
/*
function openAmendmentModal(requestId) {
  showLoading();

  document.getElementById(
    "viewRequestId"
  ).innerHTML = `<span class="text-muted">Loading...</span>`;
  document.getElementById("viewStatus").innerHTML = "";
  document.getElementById(
    "amendmentModalBody"
  ).innerHTML = `<p class="text-muted">Loading request details...</p>`;

  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");
  approveBtn.dataset.requestId = "";
  rejectBtn.dataset.requestId = "";
  approveBtn.style.display = "none";
  rejectBtn.style.display = "none";

  fetch(`../backend/dtr-requests/get_combined_requests.php?id=${requestId}`)
    .then((res) => res.json())
    .then((data) => {
      hideLoading();

      const req = data.request || data.requests?.[0];
      if (!req) {
        alert("Request not found.");
        return;
      }

      document.getElementById(
        "viewRequestId"
      ).innerHTML = `<span class="badge bg-success">${req.request_uid}</span>`;

      const statusBadge =
        req.status === "Pending"
          ? '<span class="badge bg-warning text-dark">Pending</span>'
          : req.status === "Approved"
          ? '<span class="badge bg-success">Approved</span>'
          : '<span class="badge bg-danger">Rejected</span>';
      document.getElementById("viewStatus").innerHTML = statusBadge;

      /*document.getElementById("amendmentModalBody").innerHTML = `
        <p><b>Requester:</b> ${req.requester_name}</p>
        <p><b>Task:</b> ${req.task_description}</p>
        <p><b>Date Requested:</b> ${req.requested_at || "--"}</p>
        <p><b>Requested Field:</b> ${req.field}</p>
        <p><b>Old Value:</b> ${
          formatTo24Hour(req.old_value) || req.old_value
        }</p>
        <p><b>New Value:</b> ${
          formatTo24Hour(req.new_value) || req.new_value
        }</p>
        <p><b>Reason:</b> ${req.reason}</p>
      `;

      // =====================================
      // ✅ PATCH #2 — Use smart value formatter
      // =====================================
      document.getElementById("amendmentModalBody").innerHTML = `
  <p><b>Requester:</b> ${req.requester_name}</p>
  <p><b>Task:</b> ${req.task_description}</p>
  <p><b>Date Requested:</b> ${req.requested_at || "--"}</p>
  <p><b>Requested Field:</b> ${req.field}</p>
  <p><b>Old Value:</b> ${formatAmendmentValue(req.old_value)}</p>
  <p><b>New Value:</b> ${formatAmendmentValue(req.new_value)}</p>
  <p><b>Reason:</b> ${req.reason || "--"}</p>
`;

      approveBtn.dataset.requestId = requestId;
      rejectBtn.dataset.requestId = requestId;

      //WORKING VERSION
      const userRole = (sessionStorage.getItem("userRole") || "").toLowerCase();
      if (
        req.status.toLowerCase() === "pending" &&
        ["admin", "hr", "executive", "supervisor"].includes(userRole)
      ) {
        approveBtn.style.display = "inline-block";
        rejectBtn.style.display = "inline-block";
      }

      new bootstrap.Modal(document.getElementById("amendmentModal")).show();
    })
    .catch((err) => {
      hideLoading();
      console.error("Error loading request:", err);
      alert("Failed to load amendment details.");
    });
}*/

function openAmendmentModal(requestId) {
  showLoading();

  // Reset modal
  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");
  approveBtn.dataset.requestId = "";
  rejectBtn.dataset.requestId = "";
  approveBtn.style.display = "none";
  rejectBtn.style.display = "none";

  document.getElementById(
    "viewRequestId"
  ).innerHTML = `<span class="text-muted">Loading...</span>`;
  document.getElementById("viewStatus").innerHTML = "";
  document.getElementById(
    "amendmentModalBody"
  ).innerHTML = `<p class="text-muted">Loading request details...</p>`;

  fetch(`../backend/dtr-requests/get_combined_requests.php?id=${requestId}`)
    .then((res) => res.json())
    .then((data) => {
      hideLoading();

      const req = data.request || data.requests?.[0];
      if (!req) return alert("Request not found.");

      // ==============================
      // Role-based approval logic (check BEFORE showing modal)
      // ==============================
      const userRole = (sessionStorage.getItem("userRole") || "").toLowerCase();
      const userId = parseInt(sessionStorage.getItem("user_id")) || 0;
      const userDept = parseInt(sessionStorage.getItem("department_id")) || 0; // Supervisor dept
      const requesterId = parseInt(req.requester_id || req.user_id) || 0;
      let canProcess = false;

      console.log("🔍 Request data:", {
        reqId: req.id,
        requesterId: requesterId,
        reqUser_id: req.user_id,
        reqRequester_id: req.requester_id,
        currentUserId: userId,
        userRole: userRole,
        status: req.status
      });

      // Set request info in modal
      document.getElementById(
        "viewRequestId"
      ).innerHTML = `<span class="badge bg-success">${req.request_uid}</span>`;
      const statusBadge =
        req.status === "Pending"
          ? '<span class="badge bg-warning text-dark">Pending</span>'
          : req.status === "Approved"
          ? '<span class="badge bg-success">Approved</span>'
          : '<span class="badge bg-danger">Rejected</span>';
      document.getElementById("viewStatus").innerHTML = statusBadge;

      document.getElementById("amendmentModalBody").innerHTML = `
                <p><b>Requester:</b> ${req.requester_name}</p>
                <p><b>Task:</b> ${req.task_description}</p>
                <p><b>Date Requested:</b> ${req.requested_at || "--"}</p>
                <p><b>Requested Field:</b> ${req.field}</p>
                <p><b>Old Value:</b> ${formatAmendmentValue(req.old_value)}</p>
                <p><b>New Value:</b> ${formatAmendmentValue(req.new_value)}</p>
                <p><b>Reason:</b> ${req.reason || "--"}</p>
            `;

      if (req.status.toLowerCase() === "pending") {
        // Admin / HR / Executive can process any pending request
        if (["admin", "hr", "executive"].includes(userRole)) {
          canProcess = true;
        }
        // Supervisor can process if NOT their own request
        else if (userRole === "supervisor") {
          // ✅ CRITICAL: Supervisors cannot approve/reject their own requests
          if (requesterId !== userId) {
            // Allow if department matches OR department is missing in request
            if (!req.department_id || parseInt(req.department_id) === userDept) {
              canProcess = true;
            }
          }
        }
      }

      // ✅ Hide/Show buttons based on permission
      if (canProcess) {
        approveBtn.style.display = "inline-block";
        rejectBtn.style.display = "inline-block";
        approveBtn.classList.remove("d-none");
        rejectBtn.classList.remove("d-none");
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.dataset.requestId = requestId;
        rejectBtn.dataset.requestId = requestId;
      } else {
        // ✅ Completely hide and disable buttons for supervisors viewing own requests
        approveBtn.style.display = "none";
        rejectBtn.style.display = "none";
        approveBtn.classList.add("d-none");
        rejectBtn.classList.add("d-none");
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        approveBtn.dataset.requestId = "";
        rejectBtn.dataset.requestId = "";
      }

      console.log("Supervisor check:", {
        reqId: req.id,
        requesterId: requesterId,
        currentUserId: userId,
        userRole: userRole,
        departmentId: req.department_id,
        canProcess,
        isOwnRequest: requesterId === userId,
      });

      // Show modal
      new bootstrap.Modal(document.getElementById("amendmentModal")).show();
    })
    .catch((err) => {
      hideLoading();
      console.error("Error loading request:", err);
      alert("Failed to load amendment details.");
    });
}

/* ================================
// Admin Edit Modal Logic
// ================================
function openAdminEditModal(id) {
  $.getJSON(
    "../backend/dtr-requests/get_admin_amendments.php",
    function (data) {
      const req = data.requests.find((r) => r.id == id);
      if (!req) return alert("Request not found.");

      $("#adminEditRequestId").val(req.id);
      $("#adminEditDate").val(req.date || "--");
      $("#adminEditField").val(req.field);
      $("#adminEditReason").val(req.reason || "");
      loadAdminRecipients("adminEditRecipientSelect", req.recipient_id);

      $("#adminEditOldStartTime").val(
        formatTo24Hour(req.old_start_time) || "--"
      );
      $("#adminEditOldEndTime").val(formatTo24Hour(req.old_end_time) || "--");
      $("#adminEditOldDate").val(req.old_date || "--");
      $("#adminEditNewDate").val(req.new_date || "");
      $("#adminEditNewStartTime").val(req.new_start_time || "");
      $("#adminEditNewEndTime").val(req.new_end_time || "");

      new bootstrap.Modal(
        document.getElementById("adminEditAmendmentModal")
      ).show();
    }
  );
}*/

// ================================
// Admin Edit Modal Logic (Simplified like User Modal)
// ================================
/*
$(document).on("click", ".admin-edit-btn", function () {
  const id = $(this).data("id");

  $.getJSON(
    "../backend/dtr-requests/get_admin_amendments.php",
    function (data) {
      const req = data.requests.find((r) => r.id == id);
      if (!req) return alert("Request not found.");

      // Populate fields
      $("#adminEditRequestId").val(req.id);
      $("#adminEditOldDate").val(req.old_date || "--");
      $("#adminEditOldStartTime").val(
        formatTo24Hour(req.old_start_time) || "--"
      );
      $("#adminEditOldEndTime").val(formatTo24Hour(req.old_end_time) || "--");

      $("#adminEditOldDateHidden").val(req.old_date || "");
      $("#adminEditOldStartTimeHidden").val(req.old_start_time || "");
      $("#adminEditOldEndTimeHidden").val(req.old_end_time || "");

      $("#adminEditNewDate").val(req.new_date || "");
      $("#adminEditNewStartTime").val(req.new_start_time || "");
      $("#adminEditReason").val(req.reason || "");

      loadAdminRecipients("adminEditRecipientSelect", req.recipient_id);

      new bootstrap.Modal(
        document.getElementById("adminEditAmendmentModal")
      ).show();
    }
  );
});*/

$(document).on("click", ".admin-edit-btn", function () {
  const id = $(this).data("id");

  $.getJSON(
    "../backend/dtr-requests/get_admin_amendments.php",
    function (data) {
      const req = data.requests.find((r) => r.id == id);
      if (!req) return alert("Request not found.");

      // ============================
      // ✅ Required hidden values
      // ============================
      $("#adminEditRequestId").val(req.id);
      $("#adminEditField").val(req.field || ""); // some backends require this field

      // ============================
      // ✅ Old values (both hidden + readonly display)
      // ============================
      $("#adminEditOldDateHidden").val(req.old_date || "");
      $("#adminEditOldStartTimeHidden").val(req.old_start_time || "");
      $("#adminEditOldEndTimeHidden").val(req.old_end_time || "");

      $("#adminEditOldDate").val(req.old_date || "--");
      $("#adminEditOldStartTime").val(
        formatTo24Hour(req.old_start_time) || "--"
      );
      $("#adminEditOldEndTime").val(formatTo24Hour(req.old_end_time) || "--");

      // ============================
      // ✅ New values
      // ============================
      let newDate = "";
      let newStartTime = "";
      let newEndTime = "";

      if (req.new_value) {
        // Expected format: "YYYY-MM-DD HH:MM|HH:MM" or variations
        const parts = req.new_value.split(" ");
        if (parts.length === 2) {
          newDate = parts[0];
          newStartTime = parts[1];
        } else if (parts.length === 3) {
          newDate = parts[0];
          newStartTime = parts[1];
          newEndTime = parts[2];
        } else if (req.new_start_time) {
          // fallback for split fields
          newDate = req.new_date || "";
          newStartTime = req.new_start_time || "";
          newEndTime = req.new_end_time || "";
        }
      }

      $("#adminEditNewDate").val(newDate);
      $("#adminEditNewStartTime").val(newStartTime);
      $("#adminEditNewEndTime").val(newEndTime);

      // ============================
      // ✅ Reason + Recipient
      // ============================
      $("#adminEditReason").val(req.reason || "");
      loadAdminRecipients("adminEditRecipientSelect", req.recipient_id);

      // ============================
      // ✅ Show modal
      // ============================
      new bootstrap.Modal(
        document.getElementById("adminEditAmendmentModal")
      ).show();
    }
  );
});

// ================================
// Submit Updated Admin Amendment (with auto-close + refresh)
// ================================
$("#adminEditAmendmentForm").on("submit", function (e) {
  e.preventDefault();

  const formData = $(this).serialize();

  $.post(
    "../backend/dtr-requests/update_admin_request.php",
    formData,
    function (res) {
      if (res.status === "success") {
        // Success alert (short delay)
        alert("Request updated successfully!");

        // Close modal
        const modalEl = document.getElementById("adminEditAmendmentModal");
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        // Refresh the page after small delay (so modal hides first)
        setTimeout(() => {
          location.reload();
        }, 600);
      } else {
        alert(res.message || "Failed to update request.");
      }
    },
    "json"
  ).fail(() => {
    alert("Error: Unable to connect to the server.");
  });
});

function loadAdminRecipients(selectId, selectedId = null) {
  $.getJSON("../backend/dtr-requests/get_recipients.php", function (data) {
    const select = $(`#${selectId}`);
    select.empty().append('<option value="">-- Select Recipient --</option>');
    if (data.status === "success" && Array.isArray(data.recipients)) {
      data.recipients.forEach((r) =>
        select.append(
          `<option value="${r.id}">${r.username} (${r.role})</option>`
        )
      );
      if (selectedId) select.val(selectedId);
    } else {
      select.append('<option value="">No recipients available</option>');
    }
  });
}

// ================================
// Approve / Reject Handler
// ================================
function handleDecision(id, decision) {
  if (!confirm(`Are you sure to ${decision.toLowerCase()} this request?`))
    return;

  showLoading();

  fetch("../backend/dtr-requests/process_amendment.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ request_id: id, decision }),
  })
    .then((res) => res.json())
    .then((data) => {
      hideLoading();

      if (data.status === "success") {
        alert(`Request ${decision.toLowerCase()} successfully.`);

        const modalEl = document.getElementById("amendmentModal");
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        // ✅ Instead of always reloading "Pending", reload the current filter
        const currentStatus = document.getElementById(
          "amendmentFilterStatus"
        ).value;
        const search = document.getElementById("searchRequestor").value;
        fetchDtrRequests(currentPage, search, currentStatus);
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((err) => {
      hideLoading();
      console.error(err);
      alert("Something went wrong!");
    });
}
