const userAmendment = (() => {
  // Format HH:MM:SS to 12-hour
  function formatTo12Hour(value) {
    if (!value || value === "--") return value;
    const parts = value.split(":");
    if (parts.length < 2) return value;
    let hours = parseInt(parts[0], 10);
    const minutes = parts[1];
    const seconds = parts[2] ?? null;
    const ampm = hours >= 12 ? "PM" : "AM";
    hours = hours % 12 || 12;
    return `${hours}:${minutes}${seconds ? ":" + seconds : ""} ${ampm}`;
  }

  // Status badge
  function statusBadge(status) {
    if (status === "Pending")
      return '<span class="badge bg-warning text-dark">Pending</span>';
    if (status === "Approved")
      return '<span class="badge bg-success">Approved</span>';
    if (status === "Rejected")
      return '<span class="badge bg-danger">Rejected</span>';
    return status;
  }

  // Load user amendments 
  function loadUserAmendments() {
    $.getJSON(
      "../backend/dtr-requests/get_user_amendments.php",
      function (data) {
        const tbody = $("#user-amendments-table");
        tbody.empty();
        if (!data.requests || data.requests.length === 0) {
          tbody.append(
            '<tr><td colspan="13" class="text-center">No requests yet</td></tr>'
          );
          return;
        }

        data.requests.forEach((req) => {
          const oldVal = formatTo12Hour(req.old_value);
          const newVal = formatTo12Hour(req.new_value);
          const actionBtn =
            req.status === "Pending"
              ? `<button class="btn btn-sm btn-success user-edit-btn" data-id="${req.id}">Edit</button>`
              : "-";

          tbody.append(`
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
                ? req.processed_by_name +
                  " (" +
                  (req.processed_by_role || "") +
                  ")"
                : "-"
            }</td>
            <td>${req.requested_at || "-"}</td>
            <td>${actionBtn}</td>
          </tr>
        `);
        });
      }
    );
  }

  // Open edit modal
  $(document).on("click", ".user-edit-btn", function () {
    const id = $(this).data("id");
    $.getJSON(
      "../backend/dtr-requests/get_user_amendments.php",
      function (data) {
        const req = data.requests.find((r) => r.id == id);
        if (!req) return;

        $("#userEditRequestId").val(req.id);
        $("#userEditRequester").val(req.requester_name);
        $("#userEditDate").val(req.date || "--");
        $("#userEditField").val(req.field);
        $("#userEditOldValue").val(req.old_value || "--"); // always old_value
        $("#userEditNewValue").val(req.new_value);
        $("#userEditReason").val(req.reason || "");
        // Populate recipients then preselect current one
        $.getJSON("../backend/dtr-requests/get_recipients.php", function (res) {
          const select = $("#userEditRecipientId");
          select
            .empty()
            .append('<option value="">-- Select Recipient --</option>');
          if (res.status === "success" && Array.isArray(res.recipients)) {
            res.recipients.forEach((r) => {
              select.append(
                `<option value="${r.id}" ${
                  r.id == req.recipient_id ? "selected" : ""
                }>
          ${r.username} (${r.role})
        </option>`
              );
            });
          }
        });

        // Change type for time fields
        $("#userEditField")
          .off("change")
          .on("change", function () {
            const field = $(this).val();
            $("#userEditOldValue").val(req.old_value || "--"); // always old_value
            $("#userEditNewValue").attr(
              "type",
              field === "start_time" || field === "end_time" ? "time" : "text"
            );
          });
        $("#userEditField").trigger("change");

        const modal = new bootstrap.Modal(
          document.getElementById("userEditAmendmentModal")
        );
        modal.show();
      }
    );
  });

  // Submit form with confirmation
  $("#userEditAmendmentForm").on("submit", function (e) {
    e.preventDefault();
    if (!confirm("Are you sure you want to update this request?")) return;

    const formData = $(this).serialize();
    $.post(
      "../backend/dtr-requests/update_user_amendment.php",
      formData,
      function (res) {
        if (res.status === "success") {
          alert("Request updated successfully");
          loadUserAmendments();
          const modalEl = document.getElementById("userEditAmendmentModal");
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        } else {
          alert(res.message);
        }
      },
      "json"
    );
  });

  // Public API
  return {
    init: function () {
      loadUserAmendments();
    },
  };
})();

// Initialize user amendments
$(document).ready(function () {
  userAmendment.init();
});
