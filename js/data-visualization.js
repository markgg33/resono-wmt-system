let selectedDept = null;
let chartType = null;
let chartInstance = null;

// Load departments (filter only 4)
fetch("../backend/get_departments.php")
  .then((res) => res.json())
  .then((data) => {
    const target = ["Web", "Ancillary", "Nectar Brand", "Fraud Detection"];
    const container = document.getElementById("department-buttons");

    data.filter(d => target.includes(d.name))
        .forEach((dept, index) => {
      const btn = document.createElement("button");
      btn.className = "btn btn-outline-success";
      btn.textContent = dept.name;

      btn.onclick = () => {
        selectedDept = dept.id;

        // Reset active state
        document.querySelectorAll("#department-buttons button")
                .forEach(b => b.classList.remove("active"));

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
  opt.textContent = d.toLocaleString("default", { month: "long", year: "numeric" });
  monthSel.appendChild(opt);
}

// Handle chart toggle
document.querySelectorAll(".chart-toggle").forEach(btn => {
  btn.addEventListener("click", () => {
    chartType = btn.dataset.type;
    loadGraph();
  });
});

// Month filter change (only affects pie chart)
monthSel.addEventListener("change", () => {
  if (chartType === "pie") loadPieChart();
});

// Load chart
function loadGraph() {
  if (!selectedDept || !chartType) return;

  if (chartType === "bar") {
    document.getElementById("month-filter").style.display = "none";
    loadBarChart();
  } else {
    document.getElementById("month-filter").style.display = "block";
    loadPieChart();
  }
}

// Bar chart
function loadBarChart() {
  fetch(`../backend/client/fetch_bar_graph.php?dept=${selectedDept}`)
    .then(res => res.json())
    .then(data => {
      renderChart("bar", data.labels, data.values, "Monthly Production Hours");
      document.getElementById("taskList").innerHTML = ""; // clear task list
    });
}

// Pie chart
function loadPieChart() {
  const [year, month] = monthSel.value.split("-");
  fetch(`../backend/client/fetch_pie_graph.php?dept=${selectedDept}&year=${year}&month=${month}`)
    .then(res => res.json())
    .then(data => {
      renderChart("pie", data.labels, data.values, "Task Distribution");
      renderTaskList(data.list);
    });
}

// Render chart
function renderChart(type, labels, values, label) {
  if (chartInstance) chartInstance.destroy();
  const ctx = document.getElementById("visualizationChart").getContext("2d");

  chartInstance = new Chart(ctx, {
    type: type,
    data: {
      labels: labels,
      datasets: [{
        label: label,
        data: values,
        backgroundColor: type === "pie"
          ? ["#ff6384","#36a2eb","#ffce56","#4bc0c0","#9966ff","#ff9f40","#c9cbcf"]
          : "rgba(6, 143, 40, 0.6)"
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
}

// Render task list (for pie chart)
function renderTaskList(tasks) {
  let html = "<h5 class='fw-bold'>Task Breakdown</h5><ul class='list-group'>";
  tasks.forEach(t => {
    html += `<li class="list-group-item d-flex justify-content-between">
              <span>${t.task_name}</span>
              <span>${t.task_count} tags / ${t.total_hours} hrs</span>
            </li>`;
  });
  html += "</ul>";
  document.getElementById("taskList").innerHTML = html;
}
