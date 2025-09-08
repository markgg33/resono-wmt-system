document.addEventListener("DOMContentLoaded", () => {
  if (!document.getElementById("archive-page")) return;

  // Load month/year options from backend then load the most recent archived month
  fetch("../backend/list_archive_months.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success")
        throw new Error(data.message || "Failed to load months");

      const years = new Set(data.months.map((m) => m.year));
      const yearSelect = document.getElementById("archiveYear");
      const monthSelect = document.getElementById("archiveMonth");

      yearSelect.innerHTML = "";
      [...years]
        .sort((a, b) => b - a)
        .forEach((y) => {
          const opt = document.createElement("option");
          opt.value = y;
          opt.textContent = y;
          yearSelect.appendChild(opt);
        });

      // Helper for month names
      const monthNames = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ];

      function populateMonthsForYear(y) {
        monthSelect.innerHTML = "";
        const monthsForYear = data.months
          .filter((m) => m.year === Number(y))
          .map((m) => m.month)
          .sort((a, b) => b - a);

        monthsForYear.forEach((m) => {
          const opt = document.createElement("option");
          opt.value = m;
          opt.textContent = monthNames[m - 1];
          monthSelect.appendChild(opt);
        });
      }

      // default to most recent year & its most recent month
      const defaultYear = [...years].sort((a, b) => b - a)[0];
      yearSelect.value = defaultYear;
      populateMonthsForYear(defaultYear);

      // if months exist for the default year, select the first (most recent)
      // otherwise leave empty
      // load logs
      loadArchive();

      yearSelect.addEventListener("change", () => {
        populateMonthsForYear(yearSelect.value);
      });

      document
        .getElementById("archiveFilterBtn")
        .addEventListener("click", loadArchive);

      function loadArchive() {
        const y = document.getElementById("archiveYear").value;
        const m = document.getElementById("archiveMonth").value;
        if (!y || !m) {
          renderArchiveRows([]);
          return;
        }
        fetch(`../backend/get_archived_logs.php?year=${y}&month=${m}`)
          .then((res) => res.json())
          .then((data) => {
            if (data.status !== "success") {
              renderArchiveRows([]);
              return;
            }
            renderArchiveRows(data.logs);
          })
          .catch(() => renderArchiveRows([]));
      }

      function renderArchiveRows(rows) {
        const tbody = document.querySelector("#archiveLogTable tbody");
        tbody.innerHTML = "";
        if (!rows || rows.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-muted">No archived records</td></tr>`;
          return;
        }
        rows.forEach((log) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
      <td>${formatDateDisplay(log.date)}</td>
      <td>${log.work_mode}</td>
      <td>${log.task_description}</td>
      <td>${ensureHHMMSS(log.start_time)}</td>
      <td>${log.end_time ? ensureHHMMSS(log.end_time) : "--"}</td>
      <td>${log.computed_duration || "--"}</td>
      <td>${log.remarks ? escapeHtml(log.remarks) : ""}</td>
      <td>${log.volume_remark ? escapeHtml(log.volume_remark) : ""}</td>
    `;
          tbody.appendChild(tr);
        });
      }

      // Helpers (mirror your tracker helpers)
      function ensureHHMMSS(val) {
        if (!val) return "--";
        const parts = val.split(":");
        if (parts.length === 2) return `${parts[0]}:${parts[1]}:00`;
        return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}:${(
          parts[2] || "00"
        ).padStart(2, "0")}`;
      }
      function formatDateDisplay(d) {
        if (!d) return "--";
        const dt = new Date(d);
        if (isNaN(dt.getTime())) return d; // fallback
        return dt.toLocaleDateString(undefined, {
          year: "numeric",
          month: "short",
          day: "2-digit",
        });
      }
      function escapeHtml(str) {
        return str.replace(
          /[&<>"']/g,
          (s) =>
            ({
              "&": "&amp;",
              "<": "&lt;",
              ">": "&gt;",
              '"': "&quot;",
              "'": "&#39;",
            }[s])
        );
      }
    })
    .catch((err) => {
      console.error("Archive init error:", err);
      const tbody = document.querySelector("#archiveLogTable tbody");
      if (tbody)
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger">Failed to load archive</td></tr>`;
    });
});
