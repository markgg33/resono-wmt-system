//====NEW DROPDOWN FOR SEARCHING USERS===//
//WORKING VERSION
let selectedUser = null;

//NEW TIME HELPERS

/*
function ensureHHMMSS(timeStr) {
  if (!timeStr) return "00:00:00";
  const parts = timeStr.split(":");
  return parts.length === 2 ? `${parts[0]}:${parts[1]}:00` : timeStr;
}

function calculateTimeSpent(start, end) {
  const startDate = new Date(`1970-01-01T${start}`);
  const endDate = new Date(`1970-01-01T${end}`);
  const diff = (endDate - startDate) / 1000;
  return diff > 0 ? secondsToHHMM(diff) : "00:00";
}

function secondsToHHMM(seconds) {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}
  */

function formatDateTimeForSummary(dateStr, timeStr) {
  if (!timeStr || timeStr === "--") return "--";
  const d = dateStr ? new Date(dateStr + "T00:00:00") : null;
  const dateLabel = d ? formatDateForDisplaySummary(d) : "--";
  return `${dateLabel} ${formatToHHMM(timeStr)}`;
}

// ============================
// ✅ MIDNIGHT-CROSSING HELPERS
// ============================
function ensureHHMMSS(timeStr) {
  if (!timeStr || timeStr === "--") return "00:00:00";
  const t = String(timeStr).trim();
  const parts = t.split(":");
  if (parts.length === 2)
    return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}:00`;
  if (parts.length === 3) return parts.map((p) => p.padStart(2, "0")).join(":");
  return "00:00:00";
}

/*
function timeToSeconds(hhmmss) {
  const [h, m, s] = ensureHHMMSS(hhmmss).split(":").map(Number);
  return h * 3600 + m * 60 + (s || 0);
}
  */

function timeToSeconds(hhmmss) {
  const [h, m] = ensureHHMMSS(hhmmss).split(":");
  return Number(h) * 3600 + Number(m) * 60; // ignore seconds
}

function secondsToHHMM(seconds) {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}

/**
 * ✅ Calculates duration safely:
 * - If end < start => add 24h (midnight crossing)
 * - If end_date exists and differs from date => treat as next day(s)
 *
 * start/end: "HH:MM:SS" (or "HH:MM")
 * startDate/endDate: "YYYY-MM-DD" (optional, but best if provided)
 */
function calculateTimeSpent(start, end, startDate = null, endDate = null) {
  const sSec = timeToSeconds(start);
  const eSec = timeToSeconds(end);

  // If no end time -> no duration
  if (!end || end === "--") return "00:00";

  // ✅ If backend provides end_date, use it
  // (best for End Shift rows and any explicit “next day” closure)
  let dayOffsetSeconds = 0;
  if (startDate && endDate && startDate !== endDate) {
    const d1 = new Date(`${startDate}T00:00:00`);
    const d2 = new Date(`${endDate}T00:00:00`);
    const dayDiff = Math.round((d2 - d1) / (24 * 3600 * 1000)); // can be 1+
    if (dayDiff > 0) dayOffsetSeconds = dayDiff * 24 * 3600;
  }

  let diff = eSec + dayOffsetSeconds - sSec;

  // ✅ Fallback: if still negative, assume crossed midnight once
  if (diff < 0) diff += 24 * 3600;

  if (diff < 0) diff = 0;
  return secondsToHHMM(diff);
}

// PRE-CHECK PENDING FUNCTION

async function precheckPending(
  start,
  end,
  { userId = "", department = "" } = {},
) {
  const params = new URLSearchParams({ start, end });
  if (userId) params.set("user_id", userId);
  if (department) params.set("department", department);

  const res = await fetch(
    `../backend/check_pending_requests_for_export.php?${params.toString()}`,
  );
  const data = await res.json();
  return data;
}

function loadUsersByDepartment(deptId) {
  const userDropdown = document.getElementById("userDropdown");
  if (!userDropdown) return;

  userDropdown.innerHTML = `<option value="">-- Select User --</option>`;
  selectedUser = null;

  // ✅ Choose backend file dynamically
  const url = deptId
    ? `../backend/get_users_by_department.php?department_id=${encodeURIComponent(
        deptId,
      )}`
    : `../backend/get_all_users.php`;

  fetch(url)
    .then((res) => res.json())
    .then((users) => {
      if (!Array.isArray(users) || users.length === 0) {
        userDropdown.innerHTML = `<option value="">No users found</option>`;
        return;
      }

      users.forEach((user) => {
        // For get_all_users.php we have first_name/last_name
        const name = user.name
          ? user.name
          : `${user.first_name} ${user.last_name}`.trim();

        userDropdown.insertAdjacentHTML(
          "beforeend",
          `<option value="${user.id}">${name}</option>`,
        );
      });
    })
    .catch((err) => {
      console.error("Error loading users:", err);
      userDropdown.innerHTML = `<option value="">Error loading users</option>`;
    });
}

// NEW USER SELECTION
function handleUserSelection() {
  const dropdown = document.getElementById("userDropdown");
  const userId = dropdown.value;
  const userName = dropdown.options[dropdown.selectedIndex]?.text;
  if (userId) {
    selectedUser = { id: userId, name: userName };
  } else {
    selectedUser = null;
  }
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

// NEW DOM FOR THE DROPDOWN WORKING VERSION
/*
document.addEventListener("DOMContentLoaded", function () {
  loadSummaryDepartments();

  const deptFilter = document.getElementById("summaryDepartmentFilter");
  if (deptFilter) {
    deptFilter.addEventListener("change", function () {
      loadUsersByDepartment(this.value);
    });
  }

  const userDropdown = document.getElementById("userDropdown");
  if (userDropdown) {
    userDropdown.addEventListener("change", handleUserSelection);
  }
});*/

// === NEW DOM FOR THE DROPDOWN WITH AUTO-LOAD FIX ===
document.addEventListener("DOMContentLoaded", function () {
  loadSummaryDepartments();

  const deptFilter = document.getElementById("summaryDepartmentFilter");
  const userDropdown = document.getElementById("userDropdown");
  const detailedTabBtn = document.querySelector(
    '[data-bs-target="#detailedTabContent"]',
  );

  // ✅ Event listener for department changes
  if (deptFilter) {
    deptFilter.addEventListener("change", function () {
      loadUsersByDepartment(this.value);
    });

    // ✅ Auto-load users if "All Departments" is selected by default
    // This ensures that on first page load, users are displayed immediately.
    setTimeout(() => {
      const currentValue = deptFilter.value;
      if (!currentValue || currentValue === "" || currentValue === "0") {
        // Automatically fetch all users on initial load
        loadUsersByDepartment("");
      }
    }, 300); // slight delay to ensure departments are loaded first
  }

  // ✅ Event listener for user selection
  if (userDropdown) {
    userDropdown.addEventListener("change", handleUserSelection);
  }

  // 🔹 When switching to detailed tab → reuse SAME filters
  if (detailedTabBtn) {
    detailedTabBtn.addEventListener("shown.bs.tab", () => {
      loadDetailedTaskLogs({ validate: false });
    });
  }
});

function getUserRole() {
  return (
    window.userRole ||
    sessionStorage.getItem("userRole") ||
    (typeof USER_ROLE !== "undefined" ? USER_ROLE : "")
  );
}

//NEW HELPER FOR EMPTY STATE
function showSummaryEmptyState(tableBody, message) {
  tableBody.innerHTML = "";
  const row = tableBody.insertRow();
  const cell = row.insertCell(0);
  cell.colSpan = 18;
  cell.className = "text-center text-muted";
  cell.textContent = message;
}

//=====LOAD MONTHLY SUMMARY FUNCTION===//

function loadMonthlySummary() {
  const tableBody = document.querySelector("#summaryTable tbody");

  // 🔥 Remove default empty row if present
  const defaultRow = document.getElementById("summaryEmptyRow");
  if (defaultRow) defaultRow.remove();

  tableBody.innerHTML = "";

  const tableWrapper = document.getElementById("summaryTableWrapper");
  tableWrapper.style.display = "block";
  const overlay = showLoadingOverlay();

  let url = "../backend/get_monthly_summary.php";
  let params = [];

  if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
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

  // NEW SELECT USER FUNCTION
  if (selectedUser?.id) {
    params.push(`user_id=${encodeURIComponent(selectedUser.id)}`);
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

      /* OLD CODE FOR EMPTY STATE
      if (summary.length === 0) {
        const row = tableBody.insertRow();
        const cell = row.insertCell(0);
        cell.colSpan = 17;
        cell.className = "text-center text-muted";
        cell.textContent = "No data available for selected filters.";
        hideLoadingOverlay(overlay);
        return;
      }*/

      if (!Array.isArray(summary) || summary.length === 0) {
        showSummaryEmptyState(
          tableBody,
          "No summary data available for the selected period.",
        );
        hideLoadingOverlay(overlay);
        return;
      }

      // Helper function to convert HH:MM to seconds
      function HHMMToSeconds(timeStr) {
        if (!timeStr || timeStr === "--") return 0;
        const parts = timeStr.split(":");
        if (parts.length < 2) return 0;
        return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60;
      }

      // Helper function to convert seconds to HH:MM
      function secondsToHHMM(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
      }

      // populate table
      summary.forEach((entry) => {
        const row = tableBody.insertRow();
        row.insertCell(0).textContent = formatDateForDisplaySummary(
          new Date(entry.date + "T00:00:00"),
        );
        //row.insertCell(1).textContent = formatToHHMM(entry.login);
        row.insertCell(1).textContent = formatDateTimeForSummary(
          entry.login_date || entry.date,
          entry.login,
        );
        row.insertCell(2).textContent = formatToHHMM(entry.call_time || "--");
        //row.insertCell(3).textContent = formatToHHMM(entry.logout);
        row.insertCell(3).textContent = formatDateTimeForSummary(
          entry.logout_date || entry.date,
          entry.logout,
        );
        row.insertCell(4).textContent = formatToHHMM(entry.total);
        row.insertCell(5).textContent = formatToHHMM(entry.production);
        row.insertCell(6).textContent = formatToHHMM(entry.offphone);
        row.insertCell(7).textContent = formatToHHMM(entry.training);
        row.insertCell(8).textContent = formatToHHMM(entry.resono);
        row.insertCell(9).textContent = formatToHHMM(entry.paid_break);
        row.insertCell(10).textContent = formatToHHMM(entry.unpaid_break);
        row.insertCell(11).textContent = formatToHHMM(entry.personal_time);
        row.insertCell(12).textContent = formatToHHMM(entry.system_down);

        // === LEAVE HOURS ===
        row.insertCell(13).textContent = entry.leave_hours || "--";

        // Calculate Paid Hours: production + offphone + training + resono + paid_break + system_down
        /*const paidHoursSeconds =
          HHMMToSeconds(entry.production) +
          HHMMToSeconds(entry.offphone) +
          HHMMToSeconds(entry.training) +
          HHMMToSeconds(entry.resono) +
          HHMMToSeconds(entry.paid_break) +
          HHMMToSeconds(entry.system_down);
        row.insertCell(13).textContent = secondsToHHMM(paidHoursSeconds);

        row.insertCell(14).textContent = entry.approved_ot || "--";
        row.insertCell(15).textContent = entry.remarks || "--";*/

        // === THEORETICAL PAID HOURS ===
        const theoreticalPaidSeconds =
          HHMMToSeconds(entry.production) +
          HHMMToSeconds(entry.offphone) +
          HHMMToSeconds(entry.training) +
          HHMMToSeconds(entry.resono) +
          HHMMToSeconds(entry.paid_break) +
          HHMMToSeconds(entry.system_down) +
          HHMMToSeconds(entry.leave_hours);

        row.insertCell(14).textContent = secondsToHHMM(theoreticalPaidSeconds);

        // === ACTUAL PAID HOURS (CAPPED AT 8:00) ===
        /*
        const actualPaidSeconds =
          theoreticalPaidSeconds >= 8 * 3600
            ? 8 * 3600
            : theoreticalPaidSeconds;*/

        const regularPaidSeconds = Math.min(theoreticalPaidSeconds, 8 * 3600);
        const approvedOTSeconds = HHMMToSeconds(entry.approved_ot);
        const actualPaidSeconds = regularPaidSeconds + approvedOTSeconds;

        // === APPROVED OT ===
        row.insertCell(15).textContent = entry.approved_ot || "--";

        row.insertCell(16).textContent = secondsToHHMM(actualPaidSeconds);

        // === REMARKS ===
        row.insertCell(17).textContent = entry.remarks || "--";

        // ✅ PATCH HERE: add carry-over row right after the main row (REMOVE COMMENT IF NEEDED AGAIN)
        /*if (entry.logout_date && entry.date && entry.logout_date > entry.date) {
          const carry = tableBody.insertRow();
          carry.className = "text-muted"; // optional styling

          carry.insertCell(0).textContent = formatDateForDisplaySummary(
            new Date(entry.logout_date + "T00:00:00"),
          );

          // show the carry-over time (logout time) in "Login" column
          carry.insertCell(1).textContent = formatToHHMM(entry.logout);

          // rest blank/--
          carry.insertCell(2).textContent = "--";
          carry.insertCell(3).textContent = "--";
          for (let i = 4; i <= 16; i++) carry.insertCell(i).textContent = "--";
        }*/
      });

      // MTD Totals
      const totalRow = tableBody.insertRow();
      totalRow.className = "table-success fw-bold";
      totalRow.insertCell(0).textContent = "MTD Total";
      totalRow.insertCell(1).textContent = "--";
      totalRow.insertCell(2).textContent = "--";
      totalRow.insertCell(3).textContent = "--";
      totalRow.insertCell(4).textContent = mtd.total || "00:00";
      totalRow.insertCell(5).textContent = mtd.production || "00:00";
      totalRow.insertCell(6).textContent = mtd.offphone || "00:00";
      totalRow.insertCell(7).textContent = mtd.training || "00:00";
      totalRow.insertCell(8).textContent = mtd.resono || "00:00";
      totalRow.insertCell(9).textContent = mtd.paid_break || "00:00";
      totalRow.insertCell(10).textContent = mtd.unpaid_break || "00:00";
      totalRow.insertCell(11).textContent = mtd.personal_time || "00:00";
      totalRow.insertCell(12).textContent = mtd.system_down || "00:00";
      totalRow.insertCell(13).textContent = mtd.leave_hours || "00:00"; // Leave Hours

      // Calculate MTD Paid Hours
      /*
      const mtdPaidHoursSeconds =
        HHMMToSeconds(mtd.production) +
        HHMMToSeconds(mtd.offphone) +
        HHMMToSeconds(mtd.training) +
        HHMMToSeconds(mtd.resono) +
        HHMMToSeconds(mtd.paid_break) +
        HHMMToSeconds(mtd.system_down);
      totalRow.insertCell(13).textContent = secondsToHHMM(mtdPaidHoursSeconds);*/

      // === MTD THEORETICAL PAID HOURS ===
      const mtdTheoreticalPaidSeconds =
        HHMMToSeconds(mtd.production) +
        HHMMToSeconds(mtd.offphone) +
        HHMMToSeconds(mtd.training) +
        HHMMToSeconds(mtd.resono) +
        HHMMToSeconds(mtd.paid_break) +
        HHMMToSeconds(mtd.system_down) +
        HHMMToSeconds(mtd.leave_hours);

      totalRow.insertCell(14).textContent = secondsToHHMM(
        mtdTheoreticalPaidSeconds,
      );

      totalRow.insertCell(15).textContent = "--";

      // === MTD ACTUAL PAID HOURS (CAPPED DAILY * DAYS WORKED LOGIC LATER) ===
      // For now, just cap per total (safe interim)
      const mtdActualPaidSeconds = mtdTheoreticalPaidSeconds; // OR leave blank
      totalRow.insertCell(16).textContent = secondsToHHMM(mtdActualPaidSeconds);
      totalRow.insertCell(17).textContent = "--";

      //totalRow.insertCell(14).textContent = mtd.approved_ot || "--";
      //totalRow.insertCell(15).textContent = mtd.remarks || "--";
      hideLoadingOverlay(overlay);

      // ✅ Show appropriate export buttons based on user role
      const isAdminLike = ["admin", "hr", "executive", "supervisor"].includes(
        userRole,
      );

      if (isAdminLike) {
        // Show export button group for admin/HR/executive/supervisor
        const exportButtonGroup = document.getElementById("exportButtonGroup");
        if (exportButtonGroup) exportButtonGroup.style.display = "flex";
      } else {
        // Show PDF export button for regular users
        const exportPDFBtn = document.getElementById("exportPDFBtn");
        if (exportPDFBtn) exportPDFBtn.style.display = "inline-block";
      }
    })
    .catch((err) => {
      console.error("Error loading summary:", err);
      alert("An error occurred while fetching the summary.");
      hideLoadingOverlay(overlay);
    });

  // 🔁 If User Task Logs tab is active, reload it automatically
  const detailedTabBtn = document.querySelector(
    '[data-bs-target="#detailedTabContent"]',
  );

  if (detailedTabBtn && detailedTabBtn.classList.contains("active")) {
    loadDetailedTaskLogs();
  }
  hideLoadingOverlay(overlay);
}

// UTILITY HELPERS

function formatToHHMM(timeStr) {
  if (!timeStr) return "00:00";
  const parts = timeStr.split(":");
  if (parts.length >= 2) {
    return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}`;
  }
  return timeStr;
}

/*function formatWithSeconds(timeStr) {
  if (!timeStr || timeStr === "00:00") return "00:00:00";
  const parts = timeStr.split(":");
  return parts.length === 2 ? `${parts[0]}:${parts[1]}:00` : timeStr;
}*/

function formatDateForDisplaySummary(date) {
  if (!date) return "--";
  if (typeof date === "string") date = new Date(date);
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
  overlay.style.backgroundColor = "rgba(70, 70, 70, 0.7)";
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

/* === UNIFIED EXPORT HANDLER === */
const exportBtn = document.getElementById("exportBtn");
if (exportBtn) {
  exportBtn.addEventListener("click", async () => {
    const exportType = document.getElementById("exportTypeSelect")?.value;
    if (!exportType) {
      alert("Please select an export type.");
      return;
    }

    const overlay = showLoadingOverlay();
    try {
      let url = "";
      let filename = "";

      // Get date range
      let start, end, month;

      // FOR EXPORT FLAGGING
      const isAdminLike = ["admin", "hr", "executive", "supervisor"].includes(
        userRole,
      );

      if (isAdminLike) {
        start = document.getElementById("startDate")?.value;
        end = document.getElementById("endDate")?.value;

        if (!start || !end) {
          alert("Please select both start and end dates.");
          hideLoadingOverlay(overlay);
          return;
        }
      } else {
        month =
          document.getElementById("monthFilter")?.value ||
          new Date().toISOString().slice(0, 7);
      }

      // ✅ Build a period window for precheck (always start/end)
      let periodStart, periodEnd;

      if (isAdminLike) {
        periodStart = start;
        periodEnd = end;
      } else {
        const [yy, mm] = month.split("-").map(Number);
        const lastDay = new Date(yy, mm, 0);
        const pad = (n) => String(n).padStart(2, "0");
        periodStart = `${yy}-${pad(mm)}-01`;
        periodEnd = `${yy}-${pad(mm)}-${pad(lastDay.getDate())}`;
      }

      // ✅ Decide what to check: per-user or per-department
      let precheckUserId = "";
      let precheckDept = "";

      if (
        exportType === "tracker_summary" ||
        exportType === "monthly_summary"
      ) {
        precheckUserId = selectedUser?.id || "";
      }

      if (exportType === "department" || exportType === "department_tracker") {
        const deptId =
          document.getElementById("summaryDepartmentFilter")?.value ?? "";
        precheckDept = deptId === "" ? "all" : deptId;
      }

      // ✅ Run precheck before export
      const check = await precheckPending(periodStart, periodEnd, {
        userId: precheckUserId,
        department: precheckDept,
      });

      // ✅ IMPORTANT: handle backend errors so you see them
      if (!check || check.status !== "success") {
        console.warn("Precheck failed:", check);
      } else if (check.has_pending) {
        const s = check.summary || {};
        const preview = (check.details || [])
          .slice(0, 10)
          .map(
            (d) =>
              `- ${d.name}: OT ${d.pending_ot}, DTR ${d.pending_dtr}, Leave ${d.pending_leave}`,
          )
          .join("\n");

        const msg = `⚠ Pending requests found in ${periodStart} to ${periodEnd}

Totals:
• OT Pending: ${s.pending_ot || 0}
• DTR Amendments Pending: ${s.pending_dtr || 0}
• Leave Pending: ${s.pending_leave || 0}
• Employees affected: ${s.affected_employees || 0}

Users affected:
${preview || "(none)"}

Continue export?`;

        if (!confirm(msg)) {
          hideLoadingOverlay(overlay);
          return;
        }
      }

      if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
        start = document.getElementById("startDate")?.value;
        end = document.getElementById("endDate")?.value;
        if (!start || !end) {
          alert("Please select both start and end dates.");
          hideLoadingOverlay(overlay);
          return;
        }
      } else {
        month =
          document.getElementById("monthFilter")?.value ||
          new Date().toISOString().slice(0, 7);
      }

      if (exportType === "tracker_summary") {
        // Tracker Summary Export
        if (!selectedUser || !selectedUser.id) {
          alert("Please select a user first.");
          hideLoadingOverlay(overlay);
          return;
        }

        url = `../backend/export_tracker_summary_xlsx.php?user_id=${encodeURIComponent(
          selectedUser.id,
        )}`;

        if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
          url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
            end,
          )}`;
          filename = `Tracker_Summary_${safeFilename(
            selectedUser.name,
          )}_${start}_to_${end}.xlsx`;
        } else {
          url += `&month=${encodeURIComponent(month)}`;
          filename = `Tracker_Summary_${safeFilename(
            selectedUser.name,
          )}_${month}.xlsx`;
        }
      } else if (exportType === "monthly_summary") {
        // Monthly Summary Export (detailed task list)
        if (!selectedUser || !selectedUser.id) {
          alert("Please select a user first.");
          hideLoadingOverlay(overlay);
          return;
        }

        url = `../backend/export_mtd_csv.php?user_id=${encodeURIComponent(
          selectedUser.id,
        )}`;

        if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
          url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
            end,
          )}`;
          filename = `Monthly_Summary_${safeFilename(
            selectedUser.name,
          )}_${start}_to_${end}.xlsx`;
        } else {
          url += `&month=${encodeURIComponent(month)}`;
          filename = `Monthly_Summary_${safeFilename(
            selectedUser.name,
          )}_${month}.xlsx`;
        }
      } else if (exportType === "department") {
        // Department Export
        const deptId =
          document.getElementById("summaryDepartmentFilter")?.value ?? "";
        // Allow exporting for "All Departments" (empty value). Use explicit 'all' marker.
        const deptParam = deptId === "" ? "all" : deptId;
        url = `../backend/export_department_csv.php?department=${encodeURIComponent(
          deptParam,
        )}`;

        if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
          url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
            end,
          )}`;
          filename = `Department_Export_${safeFilename(
            deptId || "All_Departments",
          )}_${start}_to_${end}.xlsx`;
        } else {
          url += `&month=${encodeURIComponent(month)}`;
          filename = `Department_Export_${safeFilename(
            deptId || "All_Departments",
          )}_MTD_${month}.xlsx`;
        }
      } else if (exportType === "department_tracker") {
        // Department Tracker XLSX Export (new endpoint)
        const deptId2 =
          document.getElementById("summaryDepartmentFilter")?.value ?? "";
        const deptParam2 = deptId2 === "" ? "all" : deptId2;

        url = `../backend/export_department_tracker_summary_xlsx.php?department=${encodeURIComponent(
          deptParam2,
        )}`;

        if (["admin", "hr", "executive", "supervisor"].includes(userRole)) {
          url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(
            end,
          )}`;
          filename = `Department_Tracker_Summary_${safeFilename(
            deptId2 || "All_Departments",
          )}_${start}_to_${end}.xlsx`;
        } else {
          url += `&month=${encodeURIComponent(month)}`;
          filename = `Department_Tracker_Summary_${safeFilename(
            deptId2 || "All_Departments",
          )}_MTD_${month}.xlsx`;
        }
      }

      const res = await fetch(url);
      if (!res.ok) throw new Error(`Export failed: ${res.status}`);
      const blob = await res.blob();
      downloadBlob(blob, filename);
    } catch (err) {
      console.error("Error exporting:", err);
      alert("Error exporting. Check server logs for details.");
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

  const isAdminLike = ["admin", "hr", "executive", "supervisor"].includes(
    userRole,
  );
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
        (typeof loggedInUserName !== "undefined" ? loggedInUserName : "Myself"),
    )}_${start}_to_${end}.pdf`;
  } else {
    const month =
      document.getElementById("monthFilter").value ||
      new Date().toISOString().slice(0, 7);
    periodLabel = month;
    filename = `Monthly_Summary_${safeFilename(
      selectedUser?.name ||
        (typeof loggedInUserName !== "undefined" ? loggedInUserName : "Myself"),
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

// FOR NEW TABLE IN TRACKER SUMMARY
async function loadDetailedTaskLogs({ validate = true } = {}) {
  const start = document.getElementById("startDate")?.value;
  const end = document.getElementById("endDate")?.value;

  if (validate) {
    if (!start || !end) {
      alert("Please select both start and end dates.");
      return;
    }

    if (!selectedUser?.id) {
      alert("Please select a user.");
      return;
    }
  }

  // 🚫 If no validation and no filters yet → just clear table
  if (!start || !end || !selectedUser?.id) {
    const tbody = document.querySelector("#adminTaskLogTable tbody");
    if (tbody) {
      tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-muted text-center">
          Please select filters and click Search to view task logs.
        </td>
      </tr>`;
    }
    return;
  }

  const tbody = document.querySelector("#adminTaskLogTable tbody");
  if (!tbody) return;
  tbody.innerHTML = "";

  let url = `../backend/get_user_task_logs_combined.php?target_user_id=${encodeURIComponent(selectedUser.id)}&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status !== "success" || !data.logs.length) {
      const row = tbody.insertRow();
      const cell = row.insertCell(0);
      cell.colSpan = 8;
      cell.className = "text-muted text-center";
      cell.textContent = "No task logs found for selected filters.";
      return;
    }

    /*data.logs.forEach((log) => {
      const row = tbody.insertRow();

      row.insertCell(0).textContent = formatDateForDisplaySummary(
        new Date(log.date + "T00:00:00"),
      );
      row.insertCell(1).textContent = log.work_mode;
      row.insertCell(2).textContent = log.task_description;
      row.insertCell(3).textContent = log.start_time
        ? formatToHHMM(log.start_time)
        : "--";
      row.insertCell(4).textContent = log.end_time
        ? formatToHHMM(log.end_time)
        : "--";

      if (log.start_time && log.end_time) {
        const duration = calculateTimeSpent(
          ensureHHMMSS(log.start_time),
          ensureHHMMSS(log.end_time),
        );
        row.insertCell(5).textContent = duration;
      } else {
        row.insertCell(5).textContent = "--";
      }

      row.insertCell(6).textContent = log.remarks || "";
      row.insertCell(7).textContent = log.volume_remark ?? "";
    });*/
    // ✅ GROUP BY WORK_DATE (same as My Tracker)
    const grouped = (data.logs || []).reduce((acc, log) => {
      const key = log.work_date || log.date; // shift key
      acc[key] = acc[key] || [];
      acc[key].push(log);
      return acc;
    }, {});

    // ✅ render by work_date ascending
    Object.keys(grouped)
      .sort()
      .forEach((dateKey) => {
        const logs = grouped[dateKey];

        // ✅ Sort inside group (same idea as My Tracker)
        logs.sort((a, b) => {
          const aDesc = String(a.task_description || "").toLowerCase();
          const bDesc = String(b.task_description || "").toLowerCase();

          const aEndShift = aDesc.includes("end shift");
          const bEndShift = bDesc.includes("end shift");

          // End Shift always last
          if (aEndShift !== bEndShift) return aEndShift ? 1 : -1;

          const base = String(dateKey);

          // for ordering: normal rows use calendar date (log.date), End Shift uses end_date
          const aMarker = String(
            aEndShift
              ? a.end_date || a.date || a.work_date || base
              : a.date || a.work_date || base,
          );
          const bMarker = String(
            bEndShift
              ? b.end_date || b.date || b.work_date || base
              : b.date || b.work_date || base,
          );

          const aOffset = aMarker > base ? 86400 : 0;
          const bOffset = bMarker > base ? 86400 : 0;

          const aSec = timeToSeconds(a.start_time || "00:00:00") + aOffset;
          const bSec = timeToSeconds(b.start_time || "00:00:00") + bOffset;

          if (aSec !== bSec) return aSec - bSec;

          // tie breaker
          return Number(a.id || 0) - Number(b.id || 0);
        });

        // ✅ Render rows
        logs.forEach((log) => {
          const row = tbody.insertRow();

          const workDate = log.work_date || log.date; // shift date
          const calendarDate = log.date || log.work_date; // calendar tag date

          const desc = String(log.task_description || "").toLowerCase();
          const isEndShift = desc.includes("end shift");

          const isNextCalendarDay =
            workDate && calendarDate && String(calendarDate) > String(workDate);

          // ✅ match My Tracker UI date display
          const uiDate = isEndShift
            ? log.end_date || calendarDate
            : isNextCalendarDay
              ? calendarDate
              : workDate;

          row.insertCell(0).textContent = formatDateForDisplaySummary(
            new Date(uiDate + "T00:00:00"),
          );

          row.insertCell(1).textContent = log.work_mode;
          row.insertCell(2).textContent = log.task_description;

          row.insertCell(3).textContent = log.start_time
            ? formatToHHMM(log.start_time)
            : "--";

          row.insertCell(4).textContent = log.end_time
            ? formatToHHMM(log.end_time)
            : "--";

          // ✅ duration (use end_date when available)
          if (log.start_time && log.end_time) {
            const startD = log.date || log.work_date || workDate;
            const endD = log.end_date || startD;

            const duration = calculateTimeSpent(
              ensureHHMMSS(log.start_time),
              ensureHHMMSS(log.end_time),
              startD,
              endD,
            );

            row.insertCell(5).textContent = duration;
          } else {
            row.insertCell(5).textContent = "--";
          }

          row.insertCell(6).textContent = log.remarks || "";
          row.insertCell(7).textContent = log.volume_remark ?? "";
        });
      });
  } catch (err) {
    console.error("Failed to load detailed task logs:", err);
  }
}
