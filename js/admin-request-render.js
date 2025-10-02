// ================================
// Format HH:MM:SS to 12-hour
// ================================
function formatTo12Hour(value) {
  if (!value || value === "--") return value;

  const parts = value.split(":");
  if (parts.length < 2) return value; // not a time

  let hours = parseInt(parts[0], 10);
  const minutes = parts[1];
  const seconds = parts[2] ?? null;

  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12 || 12;

  return `${hours}:${minutes}${seconds ? ":" + seconds : ""} ${ampm}`;
}

// ================================
// Load recipients into select dropdown
// ================================
function loadAdminRecipients(selectId, selectedId = null) {
  $.getJSON("../backend/dtr-requests/get_recipients.php", function (data) {
    const select = $(`#${selectId}`);
    select.empty().append('<option value="">-- Select Recipient --</option>');

    if (data.status === "success" && Array.isArray(data.recipients)) {
      data.recipients.forEach((r) => {
        select.append(
          `<option value="${r.id}">${r.username} (${r.role})</option>`
        );
      });

      if (selectedId) {
        // Ensure value is set after options exist
        setTimeout(() => select.val(selectedId), 0);
      }
    } else {
      select.append('<option value="">No recipients available</option>');
    }
  });
}

// ================================
// Convert status to badge HTML
// ================================
function statusBadge(status) {
  if (status === "Pending")
    return '<span class="badge bg-warning text-dark">Pending</span>';
  if (status === "Approved")
    return '<span class="badge bg-success">Approved</span>';
  if (status === "Rejected")
    return '<span class="badge bg-danger">Rejected</span>';
  return status;
}

// ================================
// Admin Requests with Pagination
// ================================
let currentAdminPage = 1;

function fetchAdminRequests(page = 1) {
  fetch(`../backend/dtr-requests/get_admin_requests.php?page=${page}`)
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then((data) => {
      if (data.status === "success") {
        renderAdminRequestTable(data.requests);
        renderAdminPagination(data.pagination);
      } else {
        console.error("Error:", data.message);
      }
    })
    .catch((err) => console.error("Error fetching admin requests:", err));
}

function renderAdminRequestTable(requests) {
  const tbody = document.getElementById("admin-request-table");
  tbody.innerHTML = "";

  if (!requests || requests.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="13" class="text-center text-muted">No requests yet</td>
      </tr>`;
    return;
  }

  requests.forEach((req) => {
    const oldVal = formatTo12Hour(req.old_value);
    const newVal = formatTo12Hour(req.new_value);

    const actionBtn =
      req.status === "Pending"
        ? `<button class="btn btn-sm btn-success admin-edit-btn" data-id="${req.id}">Edit</button>`
        : "-";

    const row = `
      <tr>
        <td><span class="badge bg-success">${req.request_uid}</span></td>
        <td>${req.date || "-"}</td>
        <td>${req.task_description || "-"}</td>
        <td>${req.field}</td>
        <td>${oldVal}</td>
        <td>${newVal}</td>
        <td>${req.reason}</td>
        <td>${req.recipient_name || "-"} (${req.recipient_role || ""})</td>
        <td>${statusBadge(req.status)}</td>
        <td>${
          req.processed_by_name
            ? req.processed_by_name + " (" + (req.processed_by_role || "") + ")"
            : "-"
        }</td>
        <td>${req.requested_at || "-"}</td>
        <td>${actionBtn}</td>
      </tr>`;
    tbody.insertAdjacentHTML("beforeend", row);
  });
}

function renderAdminPagination(pagination) {
  const container = document.getElementById("admin-requests-pagination");
  container.innerHTML = "";

  if (!pagination || pagination.totalPages <= 1) return;

  for (let i = 1; i <= pagination.totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.classList.add(
      "btn",
      "btn-md",
      "mx-1",
      i === pagination.currentPage ? "btn-success" : "btn-outline-success"
    );
    btn.addEventListener("click", () => {
      currentAdminPage = i;
      fetchAdminRequests(i);
    });
    container.appendChild(btn);
  }
}

// ================================
// Open admin edit modal
// ================================
$(document).on("click", ".admin-edit-btn", function () {
  const id = $(this).data("id");

  $.getJSON(
    "../backend/dtr-requests/get_admin_amendments.php",
    function (data) {
      const req = data.requests.find((r) => r.id == id);
      if (!req) return;

      // Populate modal fields
      $("#adminEditRequestId").val(req.id);
      $("#adminEditDate").val(req.date || "--");
      $("#adminEditField").val(req.field);
      $("#adminEditReason").val(req.reason || "");
      $("#adminEditNewValue").val(req.new_value || "");

      // Load recipients and select the current one
      loadAdminRecipients("adminEditRecipientSelect", req.recipient_id);

      // Set old value based on field type
      const updateOldValue = () => {
        const field = $("#adminEditField").val();
        if (field === "start_time") {
          $("#adminEditOldValue").val(req.start_time || "--");
          $("#adminEditNewValue").attr("type", "time");
        } else if (field === "end_time") {
          $("#adminEditOldValue").val(req.end_time || "--");
          $("#adminEditNewValue").attr("type", "time");
        } else {
          $("#adminEditOldValue").val(req.old_value || "--");
          $("#adminEditNewValue").attr("type", "text");
        }
      };

      $("#adminEditField").off("change").on("change", updateOldValue);
      updateOldValue();

      // Show modal
      new bootstrap.Modal(
        document.getElementById("adminEditAmendmentModal")
      ).show();
    }
  );
});

// ================================
// Submit admin edit form
// ================================
$("#adminEditAmendmentForm").on("submit", function (e) {
  e.preventDefault();
  const formData = $(this).serialize();

  $.post(
    "../backend/dtr-requests/update_admin_request.php",
    formData,
    function (res) {
      if (res.status === "success") {
        alert("Request updated successfully");
        fetchAdminRequests(currentAdminPage); // reload current page
        $("#adminEditAmendmentModal").modal("hide");
      } else {
        alert(res.message);
      }
    },
    "json"
  );
});

// ================================
// Initial load
// ================================
$(document).ready(function () {
  fetchAdminRequests(currentAdminPage);
});
