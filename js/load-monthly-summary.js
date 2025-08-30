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
  const tableBody = document.querySelector("#summaryTable tbody");
  const tableWrapper = document.getElementById("summaryTableWrapper");
  tableWrapper.style.display = "block";
  const overlay = showLoadingOverlay();

  let url = "../backend/get_monthly_summary.php";
  let params = [];

  if (["admin", "hr", "executive"].includes(userRole)) {
    // 🔹 Admins use date range
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;

    if (!startDate || !endDate) {
      alert("Please select both start and end dates.");
      hideLoadingOverlay(overlay);
      return;
    }

    params.push(`start=${startDate}`);
    params.push(`end=${endDate}`);
  } else {
    // 🔹 Regular users still use month filter
    const month =
      document.getElementById("monthFilter").value ||
      new Date().toISOString().slice(0, 7);
    params.push(`month=${month}`);
  }

  if (selectedUser?.name) {
    params.push(`search=${encodeURIComponent(selectedUser.name)}`);
  }

  url += "?" + params.join("&");

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

      // populate table
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

      // MTD Totals
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

      // ✅ Restore Export Buttons
      const exportPDFBtn = document.getElementById("exportPDFBtn");
      const exportCSVBtn = document.getElementById("exportCSVBtn");
      const exportDeptCSVBtn = document.getElementById("exportDeptCSVBtn");

      if (exportPDFBtn) exportPDFBtn.style.display = "inline-block";
      if (exportCSVBtn) exportCSVBtn.style.display = "inline-block";
      if (exportDeptCSVBtn) exportDeptCSVBtn.style.display = "inline-block";
    })
    .catch((err) => {
      console.error("Error loading summary:", err);
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

// helper: sanitize filename (replace spaces and unsafe chars)
function safeFilename(str) {
  if (!str) return "export";
  return String(str)
    .replace(/\s+/g, "_")
    .replace(/[^\w\-\.]/g, "_");
}

// helper: download blob
function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1500);
}

/* === EXPORT TO CSV (Individual) === */
const exportCSVBtn = document.getElementById("exportCSVBtn");
if (exportCSVBtn) {
  exportCSVBtn.addEventListener("click", async () => {
    if (!selectedUser || !selectedUser.id) {
      alert("Please select a user first.");
      return;
    }

    const overlay = showLoadingOverlay();
    try {
      let url = `../backend/export_mtd_csv.php?user_id=${encodeURIComponent(
        selectedUser.id
      )}`;
      let filename;

      if (["admin", "hr", "executive"].includes(userRole)) {
        // admin/HR/executive use date range
        const start = document.getElementById("startDate").value;
        const end = document.getElementById("endDate").value;
        if (!start || !end) {
          alert("Please select both start and end dates.");
          hideLoadingOverlay(overlay);
          return;
        }
        url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
          end
        )}`;
        filename = `Summary_${safeFilename(
          selectedUser.name
        )}_${start}_to_${end}.csv`;
      } else {
        // regular users use month MTD
        const month =
          document.getElementById("monthFilter").value ||
          new Date().toISOString().slice(0, 7);
        url += `&month=${encodeURIComponent(month)}`;
        filename = `MTD_${safeFilename(selectedUser.name)}_${month}.csv`;
      }

      const res = await fetch(url);
      if (!res.ok) throw new Error(`Export failed: ${res.status}`);
      const blob = await res.blob();
      downloadBlob(blob, filename);
    } catch (err) {
      console.error("Error exporting CSV:", err);
      alert("Error exporting CSV. Check server logs for details.");
    } finally {
      hideLoadingOverlay(overlay);
    }
  });
}

/* === EXPORT DEPARTMENT (ZIP of CSVs) === */
const exportDeptCSVBtn = document.getElementById("exportDeptCSVBtn");
if (exportDeptCSVBtn) {
  exportDeptCSVBtn.addEventListener("click", async () => {
    const deptId = document.getElementById("summaryDepartmentFilter")?.value;
    if (!deptId) {
      alert("Please select a department first.");
      return;
    }

    const overlay = showLoadingOverlay();
    try {
      let url = `../backend/export_department_zip.php?department=${encodeURIComponent(
        deptId
      )}`;
      let filename;

      if (["admin", "hr", "executive"].includes(userRole)) {
        const start = document.getElementById("startDate").value;
        const end = document.getElementById("endDate").value;
        if (!start || !end) {
          alert("Please select both start and end dates.");
          hideLoadingOverlay(overlay);
          return;
        }
        url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
          end
        )}`;
        filename = `Department_${safeFilename(deptId)}_${start}_to_${end}.zip`;
      } else {
        const month =
          document.getElementById("monthFilter").value ||
          new Date().toISOString().slice(0, 7);
        url += `&month=${encodeURIComponent(month)}`;
        filename = `Department_${safeFilename(deptId)}_MTD_${month}.zip`;
      }

      const res = await fetch(url);
      if (!res.ok) throw new Error(`Export failed: ${res.status}`);
      const blob = await res.blob();
      downloadBlob(blob, filename);
    } catch (err) {
      console.error("Error exporting department ZIP:", err);
      alert("Error exporting Department ZIP. Check server logs for details.");
    } finally {
      hideLoadingOverlay(overlay);
    }
  });
}

/* === EXPORT TO PDF (client-side) === */
const exportPDFBtn = document.getElementById("exportPDFBtn");
if (exportPDFBtn) {
  exportPDFBtn.addEventListener("click", () => exportMonthlySummaryToPDF());
}

function exportMonthlySummaryToPDF() {
  let periodLabel = "";
  let filename = "";

  const isAdminLike = ["admin", "hr", "executive"].includes(userRole);
  if (isAdminLike) {
    const start = document.getElementById("startDate").value;
    const end = document.getElementById("endDate").value;
    if (!start || !end) {
      alert("Please select both start and end dates.");
      return;
    }
    periodLabel = `${start} to ${end}`;
    filename = `Summary_${safeFilename(
      selectedUser?.name ||
        (typeof loggedInUserName !== "undefined" ? loggedInUserName : "Myself")
    )}_${start}_to_${end}.pdf`;
  } else {
    const month =
      document.getElementById("monthFilter").value ||
      new Date().toISOString().slice(0, 7);
    periodLabel = month;
    filename = `Monthly_Summary_${safeFilename(
      selectedUser?.name ||
        (typeof loggedInUserName !== "undefined" ? loggedInUserName : "Myself")
    )}_${month}.pdf`;
  }

  const userName =
    selectedUser?.name ||
    (typeof loggedInUserName !== "undefined" ? loggedInUserName : "Myself");
  const summaryTable = document.getElementById("summaryTable");

  const pdfContent = document.createElement("div");
  pdfContent.style.fontFamily = "Arial, sans-serif";
  pdfContent.style.padding = "18px";
  pdfContent.innerHTML = `
    <div style="text-align:center; margin-bottom:12px;">
      <img src="../assets/RESONO_logo_edited.png" alt="Logo" style="height:56px; display:block; margin:0 auto 8px;" />
      <h2 style="margin:0 0 6px;">Summary Report</h2>
      <div><strong>User:</strong> ${userName}</div>
      <div><strong>Period:</strong> ${periodLabel}</div>
    </div>
    <div>${summaryTable ? summaryTable.outerHTML : "<p>No table data</p>"}</div>
    <div style="margin-top:14px; text-align:center; font-size:11px; color:gray;">Generated on ${new Date().toLocaleString()}</div>
  `;

  const opt = {
    margin: 0.4,
    filename,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "in", format: "a4", orientation: "landscape" },
  };

  html2pdf()
    .set(opt)
    .from(pdfContent)
    .toPdf()
    .get("pdf")
    .then((pdf) => {
      const blob = pdf.output("blob");
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");
    });
}
