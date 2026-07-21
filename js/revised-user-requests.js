// ===========================
// TIME & STATUS UTILITIES
// ===========================
function formatAmendmentValue(value) {
  if (!value || value === "null" || value === "--") return "--";
  value = value.trim();

  // Date + Time → show time only (12-hour)
  if (/\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/.test(value)) {
    const [, time] = value.split(" ");
    return formatTo12Hour(time);
  }

  // Time only
  if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(value)) {
    return formatTo12Hour(value);
  }

  // Date only
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

function formatTo12Hour(value) {
  if (!value || value === "--") return "--";
  const parts = value.trim().split(":");
  const h = parseInt(parts[0] || 0, 10);
  const m = parseInt(parts[1] || 0, 10);
  const s = parseInt(parts[2] || 0, 10);
  const displayMinutes = h === 0 && m === 0 && s > 0 ? 1 : m;
  const ampm = h >= 12 ? "PM" : "AM";
  const hours12 = h % 12 || 12;
  return `${hours12}:${String(displayMinutes).padStart(2, "0")} ${ampm}`;
}

function formatTimeHHMM(timeStr) {
  if (!timeStr || timeStr === "--") return "--";
  const [hStr, mStr, sStr] = timeStr.split(":");
  const hours = parseInt(hStr || 0, 10);
  const minutes = parseInt(mStr || 0, 10);
  const seconds = parseInt(sStr || 0, 10);
  const displayMinutes =
    hours === 0 && minutes === 0 && seconds > 0 ? 1 : minutes;
  return `${String(hours).padStart(2, "0")}:${String(displayMinutes).padStart(
    2,
    "0"
  )}`;
}

function statusBadge(status) {
  if (status === "Pending")
    return '<span class="badge bg-warning text-dark">Pending</span>';
  if (status === "Approved")
    return '<span class="badge bg-success">Approved</span>';
  if (status === "Rejected")
    return '<span class="badge bg-danger">Rejected</span>';
  return status;
}

// ===========================
// LOAD RECIPIENTS
// ===========================
function loadRecipients(target = "#recipientSelect", selectedId = null) {
  $.getJSON("../backend/dtr-requests/get_recipients.php", function (data) {
    const select = $(target);
    select.empty().append('<option value="">-- Select Recipient --</option>');
    if (data.status === "success" && Array.isArray(data.recipients)) {
      data.recipients.forEach((r) => {
        select.append(
          `<option value="${r.id}" ${selectedId == r.id ? "selected" : ""}>
            ${r.username} (${r.role})
          </option>`
        );
      });
    } else {
      select.append('<option value="">No recipients available</option>');
    }
  });
}

// ===========================
// MAIN MODULE
// ===========================
const userAmendment = (() => {
  const rowsPerPage = 5;
  let allRequests = [];

  // Load and render requests with pagination
  function loadUserAmendments(page = 1) {
    $.getJSON(
      "../backend/dtr-requests/get_user_amendments.php",
      function (data) {
        const tbody = $("#user-amendments-table");
        const pagination = $("#user-amendments-pagination");
        tbody.empty();
        pagination.empty();

        if (!data.requests || data.requests.length === 0) {
          tbody.append(
            '<tr><td colspan="13" class="text-center">No requests yet</td></tr>'
          );
          return;
        }

        allRequests = data.requests;
        const totalPages = Math.ceil(allRequests.length / rowsPerPage);
        const startIndex = (page - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const pageRequests = allRequests.slice(startIndex, endIndex);

        pageRequests.forEach((req) => {
          /*
          const oldVal = formatTo12Hour(req.old_value);
          const newVal = formatTo12Hour(req.new_value);
          */
          // Parse old_value into date + time(OLD VERSION - please remove if not working)
          /*
          let oldTime = req.old_value
            ? req.old_value.split(" ")[1] || req.old_value
            : "--";
          // Parse new_value into date + time
          let newTime = req.new_value
            ? req.new_value.split(" ")[1] || req.new_value
            : "--";
*/

          const oldVal = formatAmendmentValue(req.old_value);
          const newVal = formatAmendmentValue(req.new_value);

          const actionBtn =
            req.status === "Pending"
              ? `<button class="btn btn-sm btn-success user-edit-btn" data-id="${req.id}">Edit</button>`
              : "-";

          //<td>${req.field}</td> <- removed inside tbody
          tbody.append(`
          <tr>
            <td><span class="badge bg-success">${req.request_uid}</span></td>
            <td>${req.date || "-"}</td>
            <td>${req.task_description || "-"}</td>
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

        // Pagination UI
        if (totalPages > 1) {
          let paginationHTML = `
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-3">
        `;
          for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `
            <li class="page-item ${i === page ? "active" : ""}">
              <button class="page-link" data-page="${i}">${i}</button>
            </li>`;
          }
          paginationHTML += `</ul></nav>`;
          pagination.html(paginationHTML);

          $(".page-link").on("click", function () {
            const newPage = parseInt($(this).data("page"));
            loadUserAmendments(newPage);
          });
        }
      }
    );
  }

  // ===========================
  // EDIT MODAL
  // ===========================
  $(document).on("click", ".user-edit-btn", function () {
    const id = $(this).data("id");
    const req = allRequests.find((r) => r.id == id);
    if (!req) return;

    // 🔧 Fix: normalize old/new fields so modal can use them
    /*
    req.old_start_time =
      req.field === "start_time" ? req.old_value : req.start_time;
    req.old_end_time = req.field === "end_time" ? req.old_value : req.end_time;
    req.new_start_time = req.field === "start_time" ? req.new_value : null;
    req.new_end_time = req.field === "end_time" ? req.new_value : null;
    req.old_date = req.field === "date" ? req.old_value : req.date;
    req.new_date = req.field === "date" ? req.new_value : null;

    // 🔽 Existing field population below
    $("#userEditRequestId").val(req.id);
    $("#userEditDate").val(req.date || "--");
    $("#userEditField").val(req.field);
    $("#userEditOldStartTime").val(
      req.old_start_time ? formatTimeHHMM(req.old_start_time) : "--"
    );
    $("#userEditOldEndTime").val(
      req.old_end_time ? formatTimeHHMM(req.old_end_time) : "--"
    );
    $("#userEditNewStartTime").val(
      req.new_start_time ? formatTimeHHMM(req.new_start_time) : ""
    );
    $("#userEditNewEndTime").val(
      req.new_end_time ? formatTimeHHMM(req.new_end_time) : ""
    );
    $("#userEditOldDate").val(req.old_date || req.date || "--");
    $("#userEditNewDate").val(req.new_date || "");
    $("#userEditReason").val(req.reason || "");

    loadRecipients("#userEditRecipientSelect", req.recipient_id);

    function toggleFields(field) {
      $("#userEditDateWrapper").addClass("d-none");
      $("#userEditNewStartTime").prop("readonly", false);
      $("#userEditNewEndTime").prop("readonly", false);

      if (field === "start_time") {
        $("#userEditNewEndTime").prop("readonly", true);
      } else if (field === "end_time") {
        $("#userEditNewStartTime").prop("readonly", true);
      } else if (field === "date") {
        $("#userEditDateWrapper").removeClass("d-none");
        $("#userEditNewStartTime, #userEditNewEndTime").prop("readonly", true);
      }
    }

    $("#userEditField")
      .off("change")
      .on("change", function () {
        toggleFields($(this).val());
      });
    toggleFields(req.field);

    new bootstrap.Modal(
      document.getElementById("userEditAmendmentModal")
    ).show();
  });*/

    // 🔧 Normalize old/new values (start time + optional date)
    let oldDate = "";
    let oldStart = "";
    let newDate = "";
    let newStart = "";

    // OLD value
    if (req.old_value && req.old_value.includes(" ")) {
      const parts = req.old_value.split(" ");
      oldDate = parts[0];
      oldStart = parts[1];
    } else {
      oldStart = req.old_value || "";
    }

    // NEW value
    if (req.new_value && req.new_value.includes(" ")) {
      const parts = req.new_value.split(" ");
      newDate = parts[0];
      newStart = parts[1];
    } else {
      newStart = req.new_value || "";
    }

    // Populate modal
    $("#userEditRequestId").val(req.id);
    $("#userEditOldDate").val(oldDate || req.date || "--");
    $("#userEditOldStartTime").val(oldStart ? formatTimeHHMM(oldStart) : "--");
    $("#userEditOldEndTime").val(
      req.end_time ? formatTimeHHMM(req.end_time) : "--"
    );
    $("#userEditNewDate").val(newDate || ""); // optional
    $("#userEditNewStartTime").val(newStart ? formatTimeHHMM(newStart) : "");
    $("#userEditReason").val(req.reason || "");
    loadRecipients("#userEditRecipientSelect", req.recipient_id);

    // Show modal
    new bootstrap.Modal(
      document.getElementById("userEditAmendmentModal")
    ).show();
  });

  // ===========================
  // FORM SUBMISSION
  // ===========================
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

  // ===========================
  // PUBLIC API
  // ===========================
  return {
    init: function () {
      loadRecipients();
      loadUserAmendments();
    },
  };
})();

// ===========================
// INITIALIZE ON LOAD
// ===========================
$(document).ready(function () {
  userAmendment.init();
});
