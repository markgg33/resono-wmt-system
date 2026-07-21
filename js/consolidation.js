/*document.addEventListener("DOMContentLoaded", () => {
  loadValueFilters();
  loadDepartments();
  loadEmployees();

  showTablePlaceholder("teamConTable");
  showTablePlaceholder("individualConTable");

  const indDept = document.getElementById("indiConSummaryDepartmentFilter");

  indDept?.addEventListener("change", function () {
    loadEmployees(this.value);
  });
});*/

document.addEventListener("DOMContentLoaded", () => {
  showTablePlaceholder("individualConTable");
  showTablePlaceholder("individualTotalTable");
  showTablePlaceholder("teamConTable");
  showTablePlaceholder("teamTotalTable");

  loadValueFilters();
});

// LOAD VALUES
function loadValueFilters() {
  fetch("../backend/commendations/get_values.php")
    .then((r) => r.json())
    .then((values) => {
      const teamSel = document.getElementById("teamValueFilter");
      const indSel = document.getElementById("indValueFilter");

      values.forEach((v) => {
        if (v.status !== "active") return;

        const opt = document.createElement("option");
        opt.value = v.id;
        opt.textContent = v.name;

        teamSel?.appendChild(opt.cloneNode(true));
        indSel?.appendChild(opt.cloneNode(true));
      });
    });
}

//CALL FUNCTIONS FOR CONSOLIDATIONS

function loadIndividualValue() {
  const params = new URLSearchParams({
    start: document.getElementById("indStartDate")?.value || "",
    end: document.getElementById("indEndDate")?.value || "",
    level: document.getElementById("indValueLevelFilter")?.value || "",
    value: document.getElementById("indValueFilter")?.value || "",
  });


  fetch("../backend/consolidation/get_individual_value_con.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const header = document.getElementById("individualConHeader");
      const tbody = document.querySelector("#individualConTable tbody");

      header.innerHTML = "<th>Employee</th>";
      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
      <tr>
      <td colspan="${(resp.values?.length || 0) + 2}" class="text-center text-muted py-2">
      No results found.
      </td>
      </tr>`;
        return;
      }

      resp.values.forEach((v) => {
        header.innerHTML += `<th>${v.name}</th>`;
      });

      header.innerHTML += "<th>Total Points</th>";

      resp.data.forEach((row) => {
        let tr = `<tr><td>${row.employee}</td>`;

        resp.values.forEach((v) => {
          const pts = row.values[v.id] || 0;
          tr += `<td>${pts}</td>`;
        });

        tr += `<td class="fw-bold">${row.total}</td></tr>`;

        tbody.innerHTML += tr;
      });
    });
}

function loadIndividualTotal() {
  const params = new URLSearchParams({
    start: document.getElementById("indTotalStart")?.value || "",
    end: document.getElementById("indTotalEnd")?.value || "",
    level: document.getElementById("indLevelFilter")?.value || "employee",
  });

  fetch("../backend/consolidation/get_individual_total_con.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const tbody = document.querySelector("#individualTotalTable tbody");

      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
      <tr>
      <td colspan="2" class="text-center text-muted py-2">
      No results found.
      </td>
      </tr>`;
        return;
      }

      resp.data.forEach((row) => {
        tbody.innerHTML += `
      <tr>
      <td>${row.employee}</td>
      <td class="fw-bold">${row.total_points}</td>
      </tr>
      `;
      });
    });
}

function loadTeamValue() {
  const params = new URLSearchParams({
    start: document.getElementById("teamStartDate")?.value || "",
    end: document.getElementById("teamEndDate")?.value || "",
  });

  fetch("../backend/consolidation/get_team_value_con.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const header = document.getElementById("teamConHeader");
      const tbody = document.querySelector("#teamConTable tbody");

      header.innerHTML = "<th>Team</th>";
      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
      <tr>
      <td colspan="${(resp.values?.length || 0) + 2}" class="text-center text-muted py-2">
      No results found.
      </td>
      </tr>`;
        return;
      }

      resp.values.forEach((v) => {
        header.innerHTML += `<th>${v.name}</th>`;
      });

      header.innerHTML += "<th>Total Points</th>";

      resp.data.forEach((row) => {
        let tr = `<tr><td>${row.team}</td>`;

        resp.values.forEach((v) => {
          const pts = row.values[v.id] || 0;
          tr += `<td>${pts}</td>`;
        });

        tr += `<td class="fw-bold">${row.total}</td></tr>`;

        tbody.innerHTML += tr;
      });
    });
}

function loadTeamTotal() {
  const params = new URLSearchParams({
    start: document.getElementById("teamTotalStart")?.value || "",
    end: document.getElementById("teamTotalEnd")?.value || "",
  });

  fetch("../backend/consolidation/get_team_total_con.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const tbody = document.querySelector("#teamTotalTable tbody");

      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
      <tr>
      <td colspan="2" class="text-center text-muted py-2">
      No results found.
      </td>
      </tr>`;
        return;
      }

      resp.data.forEach((row) => {
        tbody.innerHTML += `
      <tr>
      <td>${row.team}</td>
      <td class="fw-bold">${row.total_points}</td>
      </tr>`;
      });
    });
}

/*function loadTeamConsolidation() {
  const params = new URLSearchParams({
    value: document.getElementById("teamValueFilter")?.value || "",
    department:
      document.getElementById("teamConSummaryDepartmentFilter")?.value || "",
    start: document.getElementById("teamStartDate")?.value || "",
    end: document.getElementById("teamEndDate")?.value || "",
  });

  fetch("../backend/consolidation/get_team_consolidation.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const header = document.getElementById("teamConHeader");
      const tbody = document.querySelector("#teamConTable tbody");

      header.innerHTML = "<th>Team</th>";
      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
    <tr>
      <td colspan="${(resp.values?.length || 0) + 2}" class="text-center text-muted py-2">
        No results found for the selected filters.
      </td>
    </tr>
  `;
        return;
      }

      resp.values.forEach((v) => {
        header.innerHTML += `<th>${v.name}</th>`;
      });

      header.innerHTML += "<th>Points Awarded</th>";

      resp.data.forEach((row) => {
        let tr = `<tr><td>${row.team}</td>`;

        resp.values.forEach((v) => {
          const pts = row.values[v.id] || 0;
          tr += `<td>${pts}</td>`;
        });

        tr += `<td class="fw-bold">${row.total}</td></tr>`;

        tbody.innerHTML += tr;
      });
    });
}*/

/*function loadIndividualCon(page = 1) {
  const params = new URLSearchParams({
    value: document.getElementById("indValueFilter")?.value || "",
    user: document.getElementById("indUserFilter")?.value || "",
    department:
      document.getElementById("indiConSummaryDepartmentFilter")?.value || "",
    start: document.getElementById("indStartDate")?.value || "",
    end: document.getElementById("indEndDate")?.value || "",
    page,
  });

  fetch("../backend/consolidation/get_individual_consolidation.php?" + params)
    .then((r) => r.json())
    .then((resp) => {
      const header = document.getElementById("individualConHeader");
      const tbody = document.querySelector("#individualConTable tbody");

      header.innerHTML = "<th>Employee</th>";
      tbody.innerHTML = "";

      if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = `
    <tr>
      <td colspan="${(resp.values?.length || 0) + 2}" class="text-center text-muted py-2">
        No results found for the selected filters.
      </td>
    </tr>
  `;
        return;
      }

      resp.values.forEach((v) => {
        header.innerHTML += `<th>${v.name}</th>`;
      });

      header.innerHTML += "<th>Points Awarded</th>";

      resp.data.forEach((row) => {
        let tr = `<tr><td>${row.employee}</td>`;

        resp.values.forEach((v) => {
          const pts = row.values[v.id] || 0;
          tr += `<td>${pts}</td>`;
        });

        tr += `<td class="fw-bold">${row.total}</td></tr>`;

        tbody.innerHTML += tr;
      });
    });
}*/

/*function loadDepartments() {
  fetch("../backend/get_departments.php")
    .then((r) => r.json())
    .then((departments) => {
      const teamDept = document.getElementById(
        "teamConSummaryDepartmentFilter",
      );
      const indDept = document.getElementById("indiConSummaryDepartmentFilter");

      departments.forEach((d) => {
        const opt = document.createElement("option");
        opt.value = d.id;
        opt.textContent = d.name;

        teamDept?.appendChild(opt.cloneNode(true));
        indDept?.appendChild(opt.cloneNode(true));
      });
    });
}*/

function loadEmployees(departmentId = "") {
  let url = "../backend/get_all_users.php";

  if (departmentId) {
    url += "?department_id=" + departmentId;
  }

  fetch(url)
    .then((r) => r.json())
    .then((users) => {
      const select = document.getElementById("indUserFilter");
      if (!select) return;

      select.innerHTML = `<option value="">All Employees</option>`;

      users.forEach((u) => {
        const name = `${u.first_name} ${u.last_name}`;

        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = name;

        select.appendChild(opt);
      });
    });
}

function resetTeamConsolidation() {
  document.getElementById("teamValueFilter").value = "";
  //document.getElementById("teamConSummaryDepartmentFilter").value = "";
  document.getElementById("teamStartDate").value = "";
  document.getElementById("teamEndDate").value = "";

  showTablePlaceholder("teamConTable");
}

function resetIndividualCon() {
  document.getElementById("indValueFilter").value = "";
  document.getElementById("indiConSummaryDepartmentFilter").value = "";
  document.getElementById("indStartDate").value = "";
  document.getElementById("indEndDate").value = "";
  document.getElementById("indUserFilter").value = "";

  loadEmployees();
  showTablePlaceholder("individualConTable");
}

function showTablePlaceholder(
  tableId,
  message = "Apply filters and click Search to view results.",
) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const colCount = table.querySelectorAll("thead th").length || 1;

  tbody.innerHTML = `
    <tr>
      <td colspan="${colCount}" class="text-center text-muted py-2">
        ${message}
      </td>
    </tr>
  `;
}
