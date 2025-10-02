let selectedDept = null;
let chartType = null;
let chartInstance = null;

// Show fallback immediately on page load
document.getElementById("visualizationChart").style.display = "none";
document.getElementById("chartFallback").style.display = "block";

// Load departments dynamically based on user role
fetch("../backend/get_user_departments.php") // <-- new backend that respects role
  .then((res) => res.json())
  .then((data) => {
    const container = document.getElementById("department-buttons");

    if (!data || data.length === 0) {
      container.innerHTML =
        "<p class='text-muted fst-italic'>No departments available.</p>";
      return;
    }

    data.forEach((dept, index) => {
      const btn = document.createElement("button");
      btn.className = "btn btn-outline-success";
      btn.textContent = dept.name;

      btn.onclick = () => {
        selectedDept = dept.id;

        // Reset active state
        document
          .querySelectorAll("#department-buttons button")
          .forEach((b) => b.classList.remove("active"));

        // Activate current
        btn.classList.add("active");

        loadGraph();
      };

      container.appendChild(btn);

      // Automatically select the first department
      if (index === 0) {
        btn.click();
      }
    });
  });

// Populate month selector (last 12 months)
const monthSel = document.getElementById("monthSelector");
const today = new Date();

for (let i = 0; i < 12; i++) {
  const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
  const val = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
  const opt = document.createElement("option");
  opt.value = val;
  opt.textContent = d.toLocaleString("default", {
    month: "long",
    year: "numeric",
  });
  monthSel.appendChild(opt);
}

// Handle chart toggle
document.querySelectorAll(".chart-toggle").forEach((btn) => {
  btn.addEventListener("click", () => {
    chartType = btn.dataset.type;
    loadGraph();
  });
});

// Month filter change (only affects pie chart)
monthSel.addEventListener("change", () => {
  if (chartType === "pie") loadPieChart();
});

// Show date range only for bar chart
function loadGraph() {
  if (!selectedDept || !chartType) return;

  if (chartType === "bar") {
    document.getElementById("month-filter").style.display = "none";
    document.getElementById("date-range-filter").style.display = "block";
    loadBarChart();
  } else {
    document.getElementById("month-filter").style.display = "block";
    document.getElementById("date-range-filter").style.display = "none";
    loadPieChart();
  }
}

// Apply date range filter
document.getElementById("applyDateRange").addEventListener("click", () => {
  if (chartType === "bar") loadBarChart();
});

function loadBarChart() {
  showLoader();

  const start = document.getElementById("bar_Start_Date").value;
  const end = document.getElementById("bar_End_Date").value;

  // default daily mode
  let mode = document.querySelector("#barMode")?.value || "daily";

  let url = `../backend/client/fetch_bar_graph.php?dept_id=${selectedDept}&mode=${mode}`;

  if (start && end) {
    url += `&start_date=${start}&end_date=${end}`;
  } else {
    const year = new Date().getFullYear();
    url += `&start_date=${year}-01-01&end_date=${year}-12-31`;
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      renderChart("bar", data.labels, data.values, "Production Hours");

      if (mode === "monthly" && data.fte) {
        let html =
          "<h5 class='fw-bold'>FTE per Month</h5><ul class='list-group'>";
        data.fte.forEach((f) => {
          html += `<li class="list-group-item d-flex justify-content-between">
                    <span>${f.month}</span>
                    <span>${f.fte} FTE</span>
                   </li>`;
        });
        html += "</ul>";
        document.getElementById("taskList").innerHTML = html;
      } else {
        document.getElementById("taskList").innerHTML = "";
      }
    })
    .catch((err) => console.error("Bar chart error:", err))
    .finally(() => hideLoader());
}

// Pie chart
function loadPieChart() {
  const [year, month] = monthSel.value.split("-");
  showLoader();
  fetch(
    `../backend/client/fetch_pie_graph.php?dept=${selectedDept}&year=${year}&month=${month}`
  )
    .then((res) => res.json())
    .then((data) => {
      renderChart("pie", data.labels, data.values, "Task Distribution");
      renderTaskList(data.list);
    })
    .catch((err) => console.error("Pie chart error:", err))
    .finally(() => hideLoader());
}

// Render chart
function renderChart(type, labels, values, label) {
  if (chartInstance) chartInstance.destroy();
  const ctx = document.getElementById("visualizationChart").getContext("2d");

  if (
    !labels ||
    labels.length === 0 ||
    !values ||
    values.every((v) => v === 0)
  ) {
    // Fallback message
    document.getElementById("visualizationChart").style.display = "none";
    document.getElementById("chartFallback").style.display = "block";
    return;
  } else {
    document.getElementById("visualizationChart").style.display = "block";
    document.getElementById("chartFallback").style.display = "none";
  }

  chartInstance = new Chart(ctx, {
    type: type,
    data: {
      labels: labels,
      datasets: [
        {
          label: label,
          data: values,
          backgroundColor:
            type === "pie"
              ? [
                  "#ff6384",
                  "#36a2eb",
                  "#ffce56",
                  "#4bc0c0",
                  "#9966ff",
                  "#ff9f40",
                  "#c9cbcf",
                ]
              : "rgba(6, 143, 40, 0.6)",
        },
      ],
    },
    options: { responsive: true, maintainAspectRatio: false },
  });
}

// Render task list (for pie chart)
function renderTaskList(tasks) {
  if (!tasks || tasks.length === 0) {
    document.getElementById("taskList").innerHTML =
      "<p class='text-muted fst-italic'>No task data available for this period.</p>";
    return;
  }

  let html = "<h5 class='fw-bold'>Task Breakdown</h5><ul class='list-group'>";
  tasks.forEach((t) => {
    html += `<li class="list-group-item d-flex justify-content-between">
              <span>${t.task_name}</span>
              <span>${t.task_count} tags / ${t.total_hours} hrs</span>
            </li>`;
  });
  html += "</ul>";
  document.getElementById("taskList").innerHTML = html;
}
