// ============================================
// 📜 TASK INSERTION HISTORY (with Pagination + Search + Filter)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  const tableBody = document.getElementById("task-insertion-history-table");
  const paginationContainer = document.getElementById("history-pagination"); // ✅ FIXED ID
  const searchInput = document.getElementById("searchRequest"); // ✅ FIXED ID (matches HTML)
  const statusFilter = document.getElementById("filterStatus"); // ✅ FIXED ID (matches HTML)

  let currentPage = 1;
  const limit = 10;

  // ============================================
  // 🔍 LOAD DATA FUNCTION
  // ============================================
  function loadHistory(page = 1, search = "", status = "All") {
    tableBody.innerHTML = `
      <tr>
        <td colspan="9" class="text-center text-muted">Loading...</td>
      </tr>`;

    fetch(
      `../backend/dtr-requests/get_task_insertion_history.php?page=${page}&limit=${limit}&search=${encodeURIComponent(
        search
      )}&status=${encodeURIComponent(status)}`
    )
      .then((res) => res.json())
      .then((data) => {
        tableBody.innerHTML = "";

        if (!data.success) {
          tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${
            data.message || "Error loading data"
          }</td></tr>`;
          return;
        }

        const requests = data.requests || [];
        if (!requests.length) {
          tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-muted">No task insertion history found.</td></tr>`;
          paginationContainer.innerHTML = "";
          return;
        }

        // ============================================
        // 🧾 RENDER TABLE ROWS
        // ============================================
        requests.forEach((req) => {
          const processedBy =
            req.processed_by_name && req.processed_by_name.trim()
              ? req.processed_by_name
              : "-";
          const processedAt = req.processed_at
            ? new Date(req.processed_at).toLocaleString()
            : "-";

          const start = req.start_time ? req.start_time.slice(0, 5) : "-";
          const end = req.end_time ? req.end_time.slice(0, 5) : "-";

          const statusClass =
            req.status === "Approved"
              ? "bg-success"
              : req.status === "Rejected"
              ? "bg-danger"
              : "bg-warning text-dark";

          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td><span class="badge bg-success">${
              req.request_uid || "-"
            }</span></td>
            <td>${req.date || "-"}</td>
            <td>${req.work_mode || "-"}</td>
            <td>${req.task_desc || "-"}</td>
            <td>${start}</td>
            <td>${end}</td>
            <td><span class="badge ${statusClass}">${req.status}</span></td>
            <td>${processedBy}<br><small class="text-muted">${processedAt}</small></td>
            <td>${req.remarks || "-"}</td>
          `;
          tableBody.appendChild(tr);
        });

        // ============================================
        // 📄 PAGINATION
        // ============================================
        const totalPages = data.total_pages || 1;
        renderPagination(totalPages, page);
      })
      .catch((err) => {
        console.error("Error loading task insertion history:", err);
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Error loading data.</td></tr>`;
      });
  }

  // ============================================
  // 📄 PAGINATION BUTTONS
  // ============================================
  function renderPagination(totalPages, currentPage) {
    paginationContainer.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = `btn btn-sm ${
        i === currentPage ? "btn-success" : "btn-outline-success"
      } m-1`;
      btn.textContent = i;
      btn.addEventListener("click", () => {
        loadHistory(i, searchInput.value, statusFilter.value);
      });
      paginationContainer.appendChild(btn);
    }
  }

  // ============================================
  // 🔄 FILTER & SEARCH EVENTS
  // ============================================
  searchInput?.addEventListener("input", (e) => {
    currentPage = 1;
    loadHistory(currentPage, e.target.value, statusFilter.value);
  });

  statusFilter?.addEventListener("change", (e) => {
    currentPage = 1;
    loadHistory(currentPage, searchInput.value, e.target.value);
  });

  // ============================================
  // 🚀 INITIAL LOAD
  // ============================================
  loadHistory(currentPage);
});
