document.addEventListener("DOMContentLoaded", () => {
  loadAmendments();
});

function openAmendmentModal(requestId) {
  fetch("../backend/dtr-requests/get_admin_amendments.php")
    .then((res) => res.json())
    .then((data) => {
      const req = data.requests.find((r) => r.id == requestId);
      if (!req) return;

      document.getElementById("amendmentModalBody").innerHTML = `
        <p><b>Requester:</b> ${req.requester_name}</p>
        <p><b>Task:</b> ${req.task_description}</p>
        <p><b>Date:</b> ${req.date}</p>
        <p><b>Requested Field:</b> ${req.field}</p>
        <p><b>Old Value:</b> ${req.old_value}</p>
        <p><b>New Value:</b> ${req.new_value}</p>
        <p><b>Reason:</b> ${req.reason}</p>
      `;

      const approveBtn = document.getElementById("approveBtn");
      const rejectBtn = document.getElementById("rejectBtn");

      // Reset first (important: avoids keeping old disabled state or handlers)
      approveBtn.disabled = false;
      rejectBtn.disabled = false;
      approveBtn.onclick = null;
      rejectBtn.onclick = null;

      // Normalize status for safety
      const status = req.status.trim().toLowerCase();

      if (status === "for approval" || status === "pending") {
        // Reset buttons for new request
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.textContent = "Approve";
        rejectBtn.textContent = "Reject";
        approveBtn.style.display = "inline-block";
        rejectBtn.style.display = "inline-block";

        approveBtn.onclick = () => handleDecision(req.id, "Approved");
        rejectBtn.onclick = () => handleDecision(req.id, "Rejected");
      } else {
        // Already processed
        approveBtn.disabled = true;
        rejectBtn.disabled = true;

        if (status === "approved") {
          approveBtn.textContent = "Already Approved";
          rejectBtn.style.display = "none";
        } else if (status === "rejected") {
          rejectBtn.textContent = "Already Rejected";
          approveBtn.style.display = "none";
        }
      }

      new bootstrap.Modal(document.getElementById("amendmentModal")).show();
    });
}

function loadAmendments() {
  fetch("../backend/dtr-requests/get_admin_amendments.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        const table = document.getElementById("admin-amendments-table");
        table.innerHTML = "";

        data.requests.forEach((req) => {
          const statusBadge =
            req.status === "Pending"
              ? `<span class="badge bg-warning text-dark">For Approval</span>`
              : req.status === "Approved"
              ? `<span class="badge bg-success">Approved</span>`
              : `<span class="badge bg-danger">Rejected</span>`;

          const row = `
            <tr>
              <td><span class="badge bg-success">${req.request_uid}</span></td>
              <td>${req.requester_name}</td>
              <td>${statusBadge}</td>
              <td>
                <button class="btn btn-sm btn-success" onclick="openAmendmentModal(${req.id})">View</button>
              </td>
            </tr>`;
          table.innerHTML += row;
        });
      }
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
