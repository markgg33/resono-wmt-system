document.addEventListener("DOMContentLoaded", () => {
  const deptLinks = document.querySelectorAll(".dept-link");
  const deptTitle = document.getElementById("department-title");
  const monthSelect = document.getElementById("monthSelect");
  const summaryCard = document.querySelector(
    ".main-cards .card:nth-child(1) p"
  );
  const membersCard = document.querySelector(".main-cards .card:nth-child(2)");
  const breakdownDiv = document.getElementById("taskBreakdown");
  let tasksChart;
  let lineChart;

  let currentDept = 1;
  let currentMonth = new Date().toISOString().slice(0, 7);

  function populateMonthSelect() {
    monthSelect.innerHTML = "";
    const now = new Date();
    for (let i = 0; i < 6; i++) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const year = d.getFullYear();
      const month = (d.getMonth() + 1).toString().padStart(2, "0"); // local month
      const val = `${year}-${month}`;

      const opt = document.createElement("option");
      opt.value = val;
      opt.textContent = d.toLocaleString("default", {
        month: "long",
        year: "numeric",
      });
      if (val === currentMonth) opt.selected = true;
      monthSelect.appendChild(opt);
    }
  }

  // Load total production hours
  function loadSummary() {
    fetch(
      `../backend/client/get_department_summary.php?dept_id=${currentDept}&month=${currentMonth}&_=${Date.now()}`
    )
      .then((r) => r.json())
      .then((d) => {
        summaryCard.textContent = d.total_hours
          ? "Total Production Hours: " + d.total_hours
          : "Total Production Hours: 00:00:00";
      })
      .catch((err) => {
        console.error("Error loading summary:", err);
        summaryCard.textContent = "Error loading summary";
      });
  }

  // Load members
  function loadMembers() {
    fetch(
      `../backend/client/get_department_members.php?dept_id=${currentDept}&_=${Date.now()}`
    )
      .then((r) => r.json())
      .then((members) => {
        membersCard.innerHTML = `
          <div class="card-inner"><h2>Members</h2></div>
          <ul>${members
            .map((m) => `<li>${m.first_name} ${m.last_name}</li>`)
            .join("")}</ul>
        `;
      })
      .catch((err) => {
        console.error("Error loading members:", err);
        membersCard.innerHTML = "<p>Error loading members</p>";
      });
  }

  // Load tasks chart
  function loadTasks() {
    const canvas = document.getElementById("tasksChart");
    const ctx = canvas.getContext("2d");

    if (tasksChart) {
      tasksChart.destroy();
      tasksChart = null;
    }

    breakdownDiv.innerHTML = "<p>Loading...</p>";

    // Fetch active tasks first
    fetch(
      `../backend/client/get_department_tasks.php?dept_id=${currentDept}&month=${currentMonth}&_=${Date.now()}`
    )
      .then((r) => r.json())
      .then((tasks) => {
        // If active tasks exist
        if (Array.isArray(tasks) && tasks.length > 0) {
          renderTasksChart(tasks);
        } else {
          // Fetch archived tasks if no active tasks
          const [year, month] = currentMonth.split("-");

          fetch(
            `../backend/client/get_archived_department_tasks.php?dept_id=${currentDept}&year=${year}&month=${parseInt(
              month
            )}&_=${Date.now()}`
          )
            .then((r) => r.json())
            .then((archived) => {
              if (archived.status === "success" && archived.tasks.length > 0) {
                renderTasksChart(archived.tasks);
              } else {
                breakdownDiv.innerHTML =
                  "<p>No tasks found for this month.</p>";
              }
            })
            .catch((err) => {
              console.error("Error loading archived tasks:", err);
              breakdownDiv.innerHTML =
                "<p style='color:red;'>Error loading tasks</p>";
            });
        }
      })
      .catch((err) => {
        console.error("Error loading tasks:", err);
        breakdownDiv.innerHTML =
          "<p style='color:red;'>Error loading tasks</p>";
      });

    // Function to render chart and breakdown
    function renderTasksChart(tasks) {
      const labels = tasks.map((t) => t.task_name);
      const data = tasks.map((t) => t.task_count);

      const gradients = data.map((_, i) => {
        const grad = ctx.createLinearGradient(0, 0, 0, 300);
        grad.addColorStop(0, `rgba(255, ${160 - i * 10}, 0, 0.9)`);
        grad.addColorStop(1, `rgba(255, ${100 - i * 10}, 0, 0.7)`);
        return grad;
      });

      tasksChart = new Chart(ctx, {
        type: "pie",
        data: {
          labels,
          datasets: [
            {
              data,
              backgroundColor: gradients,
              borderColor: "#fff",
              borderWidth: 2,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: "bottom" } },
        },
      });

      breakdownDiv.innerHTML = `
      <ul>${tasks
        .map((t) => `<li>${t.task_name}: <strong>${t.task_count}</strong></li>`)
        .join("")}</ul>
    `;
    }
  }

  function loadTrends() {
    const [year, month] = currentMonth.split("-");

    fetch(
      `../backend/client/get_department_trends.php?dept_id=${currentDept}&year=${parseInt(
        year
      )}&month=${parseInt(month)}&_=${Date.now()}`
    )
      .then((r) => r.json())
      .then((data) => {
        const canvas = document.getElementById("lineChart");
        const ctx = canvas.getContext("2d");

        if (lineChart) {
          lineChart.destroy();
          lineChart = null;
        }

        if (data.status === "success" && Array.isArray(data.daily)) {
          // labels = every day of the selected month (YYYY-MM-DD)
          const labels = data.daily.map((d) => d.task_date);
          const counts = data.daily.map((d) => d.task_count);

          // Optional: quick sanity log (source will be 'live' for current month)
          console.log(
            `[Trends] month=${currentMonth} source=${
              data.source
            }, total=${counts.reduce((a, b) => a + b, 0)}`
          );

          lineChart = new Chart(ctx, {
            type: "line",
            data: {
              labels,
              datasets: [
                {
                  label: "Tasks per Day",
                  data: counts,
                  borderColor: "#ff9800",
                  backgroundColor: "rgba(255, 152, 0, 0.2)",
                  fill: true,
                  tension: 0.3,
                  pointRadius: 3,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false, // keeps size stable inside your fixed-height card
              plugins: { legend: { position: "top" } },
              scales: {
                x: {
                  title: { display: true, text: "Date" },
                  ticks: { autoSkip: true, maxTicksLimit: 12 },
                },
                y: {
                  title: { display: true, text: "Tasks" },
                  beginAtZero: true,
                  precision: 0,
                },
              },
            },
          });
        } else {
          // No data for month: show flat 0 line across all days
          const [y, m] = currentMonth.split("-");
          const daysInMonth = new Date(parseInt(y), parseInt(m), 0).getDate();
          const labels = Array.from(
            { length: daysInMonth },
            (_, i) => `${y}-${m}-${String(i + 1).padStart(2, "0")}`
          );
          const counts = Array(daysInMonth).fill(0);

          lineChart = new Chart(ctx, {
            type: "line",
            data: {
              labels,
              datasets: [
                {
                  label: "Tasks per Day",
                  data: counts,
                  borderColor: "#ff9800",
                  backgroundColor: "rgba(255, 152, 0, 0.2)",
                  fill: true,
                  tension: 0.3,
                  pointRadius: 3,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { position: "top" } },
              scales: {
                x: { title: { display: true, text: "Date" } },
                y: {
                  title: { display: true, text: "Tasks" },
                  beginAtZero: true,
                },
              },
            },
          });
        }
      })
      .catch((err) => console.error("Error loading trends:", err));
  }

  // Reload dashboard
  function reloadDashboard() {
    loadSummary();
    loadMembers();
    loadTasks();
    loadTrends();
  }

  // Department click
  deptLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      currentDept = parseInt(e.target.getAttribute("data-dept-id"));
      deptTitle.textContent = e.target.textContent + " Department";

      // Sync currentMonth with select value
      currentMonth = monthSelect.value;

      reloadDashboard();
    });
  });

  // Month change
  monthSelect.addEventListener("change", (e) => {
    currentMonth = e.target.value;
    reloadDashboard();
  });

  populateMonthSelect();
  reloadDashboard();
});
