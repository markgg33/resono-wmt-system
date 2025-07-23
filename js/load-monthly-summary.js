let selectedUser = null;

function searchUsers() {
  const query = document.getElementById("searchUser").value.trim();
  const searchResults = document.getElementById("searchResults");
  const tableWrapper = document.getElementById("summaryTableWrapper");
  tableWrapper.style.display = "none";
  selectedUser = null;

  if (!query) {
    searchResults.innerHTML = `<div class="text-danger">Please enter a name.</div>`;
    return;
  }

  searchResults.innerHTML = `<div class="text-muted">Searching...</div>`;

  fetch(`../backend/search_users.php?query=${encodeURIComponent(query)}`)
    .then((res) => res.json())
    .then((users) => {
      if (users.length === 0) {
        searchResults.innerHTML = `<div class="text-warning">No matching users found.</div>`;
        return;
      }

      const listHTML = users
        .map(
          (user) => `
            <div class="card my-1 user-result shadow-sm" style="cursor: pointer;" 
                 onclick="selectUser(${user.id}, '${user.name.replace(
            /'/g,
            "\\'"
          )}')">
              <div class="card-body p-2">
                <strong>${user.name}</strong>
              </div>
            </div>`
        )
        .join("");

      searchResults.innerHTML =
        `<div class="mb-2 text-muted">Select a user to view summary:</div>` +
        listHTML;
    })
    .catch(() => {
      searchResults.innerHTML = `<div class="text-danger">Error loading users.</div>`;
    });
}

function selectUser(userId, userName) {
  selectedUser = { id: userId, name: userName };
  loadMonthlySummary();
}

function loadMonthlySummary() {
  if (!selectedUser) return;

  const month =
    document.getElementById("monthFilter").value ||
    new Date().toISOString().slice(0, 7);
  const tableBody = document.querySelector("#summaryTable tbody");
  const tableWrapper = document.getElementById("summaryTableWrapper");
  tableWrapper.style.display = "block";

  const overlay = showLoadingOverlay();

  fetch(
    `../backend/get_monthly_summary.php?month=${month}&search=${encodeURIComponent(
      selectedUser.name
    )}`
  )
    .then((res) => res.json())
    .then((data) => {
      tableBody.innerHTML = "";

      if (data.status !== "success") {
        alert("Failed to load summary.");
        hideLoadingOverlay(overlay);
        return;
      }

      const summary = data.summary || [];
      const mtd = data.mtd || "00:00";

      if (summary.length === 0) {
        const row = tableBody.insertRow();
        const cell = row.insertCell(0);
        cell.colSpan = 11;
        cell.className = "text-center text-muted";
        cell.textContent = "No data available for selected filters.";
        hideLoadingOverlay(overlay);
        return;
      }

      summary.forEach((entry) => {
        const row = tableBody.insertRow();
        row.insertCell(0).textContent = formatDateForDisplay(
          new Date(entry.date)
        );
        row.insertCell(1).textContent = formatWithSeconds(entry.login);
        row.insertCell(2).textContent = formatWithSeconds(entry.logout);
        row.insertCell(3).textContent = entry.total || "00:00";
        row.insertCell(4).textContent = entry.production || "00:00";
        row.insertCell(5).textContent = entry.offphone || "00:00";
        row.insertCell(6).textContent = entry.training || "00:00";
        row.insertCell(7).textContent = entry.resono || "00:00";
        row.insertCell(8).textContent = entry.paid_break || "00:00";
        row.insertCell(9).textContent = entry.unpaid_break || "00:00";
        row.insertCell(10).textContent = entry.personal_time || "00:00";
      });

      const totalRow = tableBody.insertRow();
      totalRow.className = "table-success fw-bold";
      totalRow.insertCell(0).textContent = "MTD Total";
      totalRow.insertCell(1).textContent = "--";
      totalRow.insertCell(2).textContent = "--";
      totalRow.insertCell(3).textContent = mtd;

      for (let i = 4; i <= 10; i++) {
        totalRow.insertCell(i).textContent = "--";
      }

      hideLoadingOverlay(overlay);
    })
    .catch((err) => {
      console.error("Error loading monthly summary:", err);
      alert("An error occurred while fetching the summary.");
      hideLoadingOverlay(overlay);
    });
}

// Utility
function formatWithSeconds(timeStr) {
  if (!timeStr || timeStr === "00:00") return "00:00:00";
  const parts = timeStr.split(":");
  return parts.length === 2 ? `${parts[0]}:${parts[1]}:00` : timeStr;
}

function formatDateForDisplay(date) {
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

// Overlay
function showLoadingOverlay() {
  const overlay = document.createElement("div");
  overlay.className = "loading-overlay";
  overlay.innerHTML = `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>`;
  overlay.style.position = "fixed";
  overlay.style.top = "0";
  overlay.style.left = "0";
  overlay.style.width = "100%";
  overlay.style.height = "100%";
  overlay.style.backgroundColor = "rgba(255, 255, 255, 0.7)";
  overlay.style.display = "flex";
  overlay.style.alignItems = "center";
  overlay.style.justifyContent = "center";
  overlay.style.zIndex = "9999";
  document.body.appendChild(overlay);
  return overlay;
}

function hideLoadingOverlay(overlay) {
  setTimeout(() => {
    if (overlay && overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }
  }, 500); // slightly longer delay for smoother UX
}
