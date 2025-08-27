//=====SEARCH USER FOR ADMIN===//

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

function loadSummaryDepartments() {
  fetch("../backend/get_departments.php")
    .then((res) => res.json())
    .then((depts) => {
      let filterSelect = document.getElementById("summaryDepartmentFilter");
      if (!filterSelect) return; // safety check
      filterSelect.innerHTML = `<option value="">All Departments</option>`;
      depts.forEach((d) => {
        filterSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
      });
    })
    .catch((err) => console.error("Error loading summary departments:", err));
}

// Run this when the summary page is shown
document.addEventListener("DOMContentLoaded", function () {
  loadSummaryDepartments();
});

//=====LOAD MONTHLY SUMMARY FUNCTION===//

function loadMonthlySummary() {
  const month =
    document.getElementById("monthFilter").value ||
    new Date().toISOString().slice(0, 7);

  const tableBody = document.querySelector("#summaryTable tbody");
  const tableWrapper = document.getElementById("summaryTableWrapper");
  tableWrapper.style.display = "block";

  const overlay = showLoadingOverlay();

  // If admin selected a user, include it in the query
  let url = `../backend/get_monthly_summary.php?month=${month}`;
  if (typeof selectedUser !== "undefined" && selectedUser?.name) {
    url += `&search=${encodeURIComponent(selectedUser.name)}`;
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      tableBody.innerHTML = "";

      if (data.status !== "success") {
        alert("Failed to load summary.");
        hideLoadingOverlay(overlay);
        return;
      }

      const summary = data.summary || [];
      const mtd = data.mtd || {};

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

      // MTD Total Row
      const totalRow = tableBody.insertRow();
      totalRow.className = "table-success fw-bold";
      totalRow.insertCell(0).textContent = "MTD Total";
      totalRow.insertCell(1).textContent = "--";
      totalRow.insertCell(2).textContent = "--";
      totalRow.insertCell(3).textContent = mtd.total || "00:00";
      totalRow.insertCell(4).textContent = mtd.production || "00:00";
      totalRow.insertCell(5).textContent = mtd.offphone || "00:00";
      totalRow.insertCell(6).textContent = mtd.training || "00:00";
      totalRow.insertCell(7).textContent = mtd.resono || "00:00";
      totalRow.insertCell(8).textContent = mtd.paid_break || "00:00";
      totalRow.insertCell(9).textContent = mtd.unpaid_break || "00:00";
      totalRow.insertCell(10).textContent = mtd.personal_time || "00:00";

      hideLoadingOverlay(overlay);

      // Show Export Button after data loads
      const exportPDFBtn = document.getElementById("exportPDFBtn");
      const exportCSVBtn = document.getElementById("exportCSVBtn");
      const exportDeptCSVBtn = document.getElementById("exportDeptCSVBtn");

      if (exportPDFBtn) exportPDFBtn.style.display = "inline-block";
      if (exportCSVBtn) exportCSVBtn.style.display = "inline-block";
      if (exportDeptCSVBtn) exportDeptCSVBtn.style.display = "inline-block";
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

// === EXPORT TO CSV (Individual) ===
const exportCSVBtn = document.getElementById("exportCSVBtn");
if (exportCSVBtn) {
  exportCSVBtn.addEventListener("click", () => {
    if (!selectedUser) {
      alert("Please select a user first.");
      return;
    }
    const month =
      document.getElementById("monthFilter").value ||
      new Date().toISOString().slice(0, 7);
    fetch(
      `../backend/export_mtd_csv.php?user_id=${selectedUser.id}&month=${month}`
    )
      .then((res) => res.blob())
      .then((blob) => {
        const link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);
        link.download = `MTD_${selectedUser.name}_${month}.csv`;
        link.click();
      })
      .catch((err) => {
        console.error(err);
        alert("Error exporting CSV.");
      });
  });
}

// === EXPORT DEPARTMENT MTD (ZIP of CSVs) ===
const exportDeptCSVBtn = document.getElementById("exportDeptCSVBtn");
if (exportDeptCSVBtn) {
  exportDeptCSVBtn.addEventListener("click", () => {
    const deptId = document.getElementById("summaryDepartmentFilter").value;
    const month =
      document.getElementById("monthFilter").value ||
      new Date().toISOString().slice(0, 7);

    if (!deptId || !month) {
      alert("Please select both a department and a month before exporting.");
      return;
    }

    fetch(
      `../backend/export_department_zip.php?department=${deptId}&month=${month}`
    )
      .then((res) => {
        if (!res.ok) throw new Error("Failed to export department ZIP");
        return res.blob();
      })
      .then((blob) => {
        const link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);
        link.download = `Department_${deptId}_MTD_${month}.zip`;
        link.click();
      })
      .catch((err) => {
        console.error("Error exporting department ZIP:", err);
        alert("Error exporting Department CSVs.");
      });
  });
}

// === EXPORT TO PDF ===
document.addEventListener("DOMContentLoaded", () => {
  const exportBtn = document.getElementById("exportPDFBtn");
  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      exportMonthlySummaryToPDF();
    });
  }
});

function exportMonthlySummaryToPDF() {
  const month =
    document.getElementById("monthFilter").value ||
    new Date().toISOString().slice(0, 7);

  const userName = selectedUser?.name || loggedInUserName || "Myself";
  const summaryTable = document.getElementById("summaryTable");

  // === Create custom content wrapper for PDF ===
  const pdfContent = document.createElement("div");
  pdfContent.style.fontFamily = "Arial, sans-serif";
  pdfContent.style.padding = "20px";

  pdfContent.innerHTML = `
    <div style="margin-bottom: 20px;">
      <img src="../assets/RESONO_logo_edited.png" alt="Company Logo" style="text-align:center; height: 60px; margin-bottom: 10px;" />
      <h2 style=" text-align:center; margin: 5px 0;">Monthly Summary Report</h2>
      <p style="margin: 0;"><strong>User:</strong> ${userName}</p>
      <p style="margin: 0;"><strong>Month:</strong> ${month}</p>
    </div>
    <div>${summaryTable.outerHTML}</div>
    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: gray;">
      Generated on ${new Date().toLocaleString()}
    </div>
  `;

  // === PDF Options ===
  const opt = {
    margin: 0.5,
    filename: `Monthly_Summary_${userName}_${month}.pdf`,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "in", format: "a4", orientation: "landscape" },
  };

  html2pdf()
    .set(opt)
    .from(pdfContent)
    .toPdf()
    .get("pdf")
    .then(function (pdf) {
      const blob = pdf.output("blob");
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank"); // Open in new tab
    });
}
