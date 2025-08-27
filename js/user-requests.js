//TIME FORMATTER FOR AMENDMENT PAGE
function formatTo12Hour(value) {
  if (!value || value === "--") return value;

  // Match HH:MM or HH:MM:SS
  const timeParts = value.split(":");
  if (timeParts.length < 2) return value; // not a time, just return raw

  let hours = parseInt(timeParts[0], 10);
  let minutes = timeParts[1];
  let seconds = timeParts.length === 3 ? timeParts[2] : null;

  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12 || 12;

  return `${hours}:${minutes}${seconds ? ":" + seconds : ""} ${ampm}`;
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

        const actionBtn =
          req.status === "Pending"
            ? `<button class="btn btn-sm btn-success edit-request-btn" 
                  data-id="${req.id}" 
                  data-field="${req.field}" 
                  data-newvalue="${req.new_value}" 
                  data-reason="${req.reason}">
            Edit
         </button>`
            : "-";

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
            <td>${actionBtn}</td>
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
