//TIME FORMATTER FOR AMENDMENT PAGE
function formatTo12Hour(value) {
  if (!value || value === "--") return "--";

  // Keep only HH:MM:SS
  const parts = value.trim().split(":");
  const h = parseInt(parts[0] || 0, 10);
  const m = parseInt(parts[1] || 0, 10);
  const s = parseInt(parts[2] || 0, 10);

  const displayMinutes = h === 0 && m === 0 && s > 0 ? 1 : m;

  const ampm = h >= 12 ? "PM" : "AM";
  const hours12 = h % 12 || 12;

  return `${hours12}:${String(displayMinutes).padStart(2, "0")} ${ampm}`;
}

// Load recipients into dropdown
function loadRecipients() {
  $.getJSON("../backend/dtr-requests/get_recipients.php", function (data) {
    let select = $("#recipientSelect");
    select.empty().append('<option value="">-- Select Recipient --</option>');

    if (data.status === "success" && Array.isArray(data.recipients)) {
      data.recipients.forEach((r) => {
        select.append(
          `<option value="${r.id}">${r.username} (${r.role})</option>`
        );
      });
    } else {
      select.append('<option value="">No recipients available</option>');
    }
  });
}

// Load user’s amendment requests
function loadUserAmendments() {
  $.getJSON("../backend/dtr-requests/get_user_amendments.php", function (data) {
    let table = $("#user-amendments-table");
    table.empty();
    if (!data.requests || data.requests.length === 0) {
      table.append(
        '<tr><td colspan="10" class="text-center">No requests yet</td></tr>'
      );
    } else {
      data.requests.forEach((req) => {
        const oldValFormatted = formatTo12Hour(req.old_value);
        const newValFormatted = formatTo12Hour(req.new_value);

        table.append(`
          <tr>
            <td><span class="badge bg-success">${req.request_uid}</span></td>
            <td>${req.date || "-"}</td>
            <td>${req.task_description || "-"}</td>
            <td>${req.field}</td>
            <td>${oldValFormatted}</td>
            <td>${newValFormatted}</td>
            <td>${req.reason}</td>
            <td>${req.recipient_name || "-"} (${req.recipient_role || ""})</td>
            <td><span class="badge bg-${
              req.status === "Pending"
                ? "warning"
                : req.status === "Approved"
                ? "success"
                : "danger"
            }">${req.status}</span></td>
            <td>${
              req.processed_by_name
                ? req.processed_by_name +
                  " (" +
                  (req.processed_by_role || "") +
                  ")"
                : "-"
            }</td>
            <td>${req.requested_at || "-"}</td>
          </tr>
        `);
      });
    }
  });
}

// Call these on page load
$(document).ready(function () {
  loadRecipients();
  loadUserAmendments();
});
