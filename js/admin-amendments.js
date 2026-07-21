function showLoading() {
  let loader = document.createElement("div");
  loader.id = "loading-overlay";
  loader.style.position = "fixed";
  loader.style.top = "0";
  loader.style.left = "0";
  loader.style.width = "100%";
  loader.style.height = "100%";
  loader.style.backgroundColor = "rgba(0, 0, 0, 0.4)";
  loader.style.display = "flex";
  loader.style.justifyContent = "center";
  loader.style.alignItems = "center";
  loader.style.zIndex = "9999";
  loader.innerHTML = `<div class="spinner-border text-light" role="status"></div>`;
  document.body.appendChild(loader);
}

// ================================
// Format HH:MM:SS to 24-hour HH:MM NEW FORMAT FOR SECONDS REMOVAL
// ================================
function formatTo24Hour(value) {
  if (!value || value === "--") return value;

  const parts = value.split(":");
  if (parts.length < 2) return value;

  const hours = parts[0].padStart(2, "0");
  const minutes = parts[1].padStart(2, "0");

  return `${hours}:${minutes}`;
}

function hideLoading() {
  let loader = document.getElementById("loading-overlay");
  if (loader) loader.remove();
}

document.addEventListener("DOMContentLoaded", () => {
  loadAmendments();
});

function openAmendmentModal(requestId) {
  const currentRole = (sessionStorage.getItem("userRole") || "").toLowerCase();

  // ✅ FIX #1: Use a broader endpoint for admin/hr/executive
  const endpoint = ["admin", "hr", "executive"].includes(currentRole)
    ? "../backend/dtr-requests/get_dtr_requests.php" // fetch all requests
    : "../backend/dtr-requests/get_admin_amendments.php"; // recipient-specific

  fetch(endpoint)
    .then((res) => res.json())
    .then((data) => {
      const req = data.requests.find((r) => r.id == requestId);
      if (!req) {
        alert("Request not found or access restricted.");
        return;
      }

      // 🔹 Set Request UID
      document.getElementById(
        "viewRequestId"
      ).innerHTML = `<span class="badge bg-success">${req.request_uid}</span>`;

      // 🔹 Determine and Set Status Badge
      let statusBadge = "";
      const status = req.status.trim().toLowerCase();
      if (status === "pending" || status === "for approval") {
        statusBadge = `<span class="badge bg-warning text-dark">For Approval</span>`;
      } else if (status === "approved") {
        statusBadge = `<span class="badge bg-success">Approved</span>`;
      } else if (status === "rejected") {
        statusBadge = `<span class="badge bg-danger">Rejected</span>`;
      } else {
        statusBadge = `<span class="badge bg-secondary">${req.status}</span>`;
      }
      document.getElementById("viewStatus").innerHTML = statusBadge;

      // 🔹 Populate modal body details
      document.getElementById("amendmentModalBody").innerHTML = `
        <p><b>Requester:</b> ${req.requester_name}</p>
        <p><b>Task:</b> ${req.task_description}</p>
        <p><b>Date:</b> ${req.date}</p>
        <p><b>Requested Field:</b> ${req.field}</p>
        <p><b>Old Value:</b> ${
          req.field.includes("time")
            ? formatTo24Hour(req.old_value)
            : req.old_value
        }</p>
        <p><b>New Value:</b> ${
          req.field.includes("time")
            ? formatTo24Hour(req.new_value)
            : req.new_value
        }</p>
        <p><b>Reason:</b> ${req.reason}</p>
      `;

      // 🔹 Button control logic
      const approveBtn = document.getElementById("approveBtn");
      const rejectBtn = document.getElementById("rejectBtn");

      approveBtn.disabled = false;
      rejectBtn.disabled = false;
      approveBtn.onclick = null;
      rejectBtn.onclick = null;

      if (status === "for approval" || status === "pending") {
        approveBtn.style.display = "inline-block";
        rejectBtn.style.display = "inline-block";
        approveBtn.textContent = "Approve";
        rejectBtn.textContent = "Reject";
        approveBtn.onclick = () => handleDecision(req.id, "Approved");
        rejectBtn.onclick = () => handleDecision(req.id, "Rejected");
      } else {
        if (status === "approved") {
          approveBtn.textContent = "Already Approved";
          approveBtn.disabled = true;
          rejectBtn.style.display = "none";
        } else if (status === "rejected") {
          rejectBtn.textContent = "Already Rejected";
          rejectBtn.disabled = true;
          approveBtn.style.display = "none";
        }
      }

      // 🔹 Show modal
      new bootstrap.Modal(document.getElementById("amendmentModal")).show();
    })
    .catch((err) => {
      console.error("Error loading request:", err);
      alert("Failed to load amendment details.");
    });
}

//UPDATED WITH FALLBACK
function loadAmendments() {
  fetch("../backend/dtr-requests/get_dtr_requests.php")
    .then((res) => res.json())
    .then((data) => {
      const table = document.getElementById("admin-amendments-table");
      table.innerHTML = "";

      if (
        data.status === "success" &&
        Array.isArray(data.requests) &&
        data.requests.length > 0
      ) {
        data.requests.forEach((req) => {
          const statusBadge =
            req.status === "Pending"
              ? `<span class="badge bg-warning text-dark">For Approval</span>`
              : req.status === "Approved"
              ? `<span class="badge bg-success">Approved</span>`
              : `<span class="badge bg-danger">Rejected</span>`;

          // 🔹 Check current user role and recipient restriction
          const currentRole = (
            sessionStorage.getItem("userRole") || ""
          ).toLowerCase(); // assuming you set this on login
          const currentUserId = parseInt(sessionStorage.getItem("user_id"));

          let viewButton = "";

          // Admin, HR, Executive can always view
          if (["admin", "hr", "executive"].includes(currentRole)) {
            viewButton = `<button class="btn btn-sm btn-success" onclick="openAmendmentModal(${req.id})">View</button>`;
          }
          // Supervisor can only view if they are the recipient
          else if (
            currentRole === "supervisor" &&
            parseInt(req.recipient_id) === currentUserId
          ) {
            viewButton = `<button class="btn btn-sm btn-success" onclick="openAmendmentModal(${req.id})">View</button>`;
          } else {
            // Hide or disable the button (depending on your preference)
            viewButton = `<button class="btn btn-sm btn-secondary" disabled>Not Authorized</button>`;
          }

          const row = `
      <tr>
        <td><span class="badge bg-success">${req.request_uid}</span></td>
        <td>${req.requester_name}</td>
        <td>${statusBadge}</td>
        <td>${viewButton}</td>
      </tr>`;

          table.innerHTML += row;
        });
      } else {
        // 🔹 Fallback message row
        table.innerHTML = `
            <tr>
              <td colspan="4" class="text-center text-muted py-3">
                No new requests at the moment
              </td>
            </tr>`;
      }
    })
    .catch((err) => {
      console.error("Error loading amendments:", err);
      const table = document.getElementById("admin-amendments-table");
      table.innerHTML = `
          <tr>
            <td colspan="4" class="text-center text-danger py-3">
              Failed to load requests. Please try again later.
            </td>
          </tr>`;
    });
}

function handleDecision(id, decision) {
  if (
    !confirm(`Are you sure you want to ${decision.toLowerCase()} this request?`)
  )
    return;

  // Disable modal buttons immediately to prevent double click
  document
    .querySelectorAll("#amendmentModal .decision-btn")
    .forEach((btn) => (btn.disabled = true));

  fetch("../backend/dtr-requests/process_amendment.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: JSON.stringify({
      request_id: id,
      decision: decision,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        // Refresh table
        loadAmendments();

        // Close modal smoothly
        const modalEl = document.getElementById("amendmentModal");
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
          setTimeout(() => {
            modal.hide();
          }, 300); // slight delay for UX
        }

        alert(`Request has been ${decision.toLowerCase()} successfully.`);
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((err) => {
      hideLoading();
      alert("Something went wrong: " + err);
    })
    .finally(() => {
      // Re-enable modal buttons (just in case)
      document
        .querySelectorAll("#amendmentModal .decision-btn")
        .forEach((btn) => (btn.disabled = false));
    });
}
