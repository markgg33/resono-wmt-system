function openArchiveAmendmentModal(requestId) {
  fetch(`../backend/get_admin_amendments_archive.php?page=1`) // fetch archive data
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
  <p><b>Status:</b> ${req.status}</p>
  <p><b>Processed At:</b> ${req.processed_at ?? "-"}</p>
  <p><b>Processed By:</b> ${
    req.processed_by_name
      ? req.processed_by_name + " (" + (req.processed_by_role || "") + ")"
      : "-"
  }</p>
`;

      // Hide decision buttons (archive = read only)
      document.getElementById("approveBtn").style.display = "none";
      document.getElementById("rejectBtn").style.display = "none";

      new bootstrap.Modal(document.getElementById("amendmentModal")).show();
    });
}

document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;

  function fetchArchive(page = 1) {
    fetch(`../backend/get_admin_amendments_archive.php?page=${page}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.status === "success") {
          renderTable(data.requests);
          renderPagination(data.pagination);
        } else {
          console.error("Error:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error fetching archive:", error);
      });
  }

  function renderTable(requests) {
    const tableBody = document.getElementById("admin-amendments-archive-table");
    tableBody.innerHTML = "";

    if (!requests || requests.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted">No archived requests found</td>
        </tr>`;
      return;
    }

    requests.forEach((req) => {
      const row = `
  <tr>
    <td><span class="badge bg-success">${req.request_uid}</span></td>
    <td>${req.requester_name}</td>
    <td>
      <span class="badge ${
        req.status.toLowerCase() === "approved" ? "bg-success" : "bg-danger"
      }">
  ${req.status.charAt(0).toUpperCase() + req.status.slice(1).toLowerCase()}
</span>
    </td>
    <td>${req.processed_at ? req.processed_at : "-"}</td>
    <td>${
      req.processed_by_name
        ? req.processed_by_name + " (" + (req.processed_by_role || "") + ")"
        : "-"
    }</td>
    <td>
      <button class="btn btn-sm btn-success" onclick="openArchiveAmendmentModal(${
        req.id
      })">View</button>
    </td>
  </tr>
`;

      tableBody.insertAdjacentHTML("beforeend", row);
    });
  }

  function renderPagination(pagination) {
    const paginationContainer = document.getElementById("archive-pagination");
    paginationContainer.innerHTML = "";

    if (pagination.totalPages <= 1) return;

    for (let i = 1; i <= pagination.totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.classList.add(
        "btn",
        "btn-sm",
        "mx-1",
        i === pagination.currentPage ? "btn-primary" : "btn-outline-secondary"
      );
      btn.addEventListener("click", () => {
        currentPage = i;
        fetchArchive(i);
      });
      paginationContainer.appendChild(btn);
    }
  }

  // Initial load
  fetchArchive(currentPage);
});
