// data-visualization.js
// ✅ GLOBAL FUNCTION

let selectedDept = null;
let selectedDeptName = "";
let chartType = null;
let chartInstance = null;

/*function initDepartmentDropdown() {
  const dropdown = document.getElementById("departmentDropdown");
  if (!dropdown) return;

  dropdown.innerHTML = "<option>Loading departments...</option>";

  fetch("../backend/get_user_departments.php")
    .then((res) => {
      if (!res.ok) throw new Error("Failed to fetch");
      return res.json();
    })
    .then((data) => {
      console.log("Departments:", data); // 🔍 DEBUG

      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = "<option>No departments available</option>";
        return;
      }

      dropdown.innerHTML = `
<option value="">Select Department</option>
  <option value="all">All Departments</option>
`;

      data.forEach((dept) => {
        const opt = document.createElement("option");
        opt.value = dept.id;
        opt.textContent = dept.name;
        dropdown.appendChild(opt);
      });
    })
    .catch((err) => {
      console.error("Department load error:", err);
      dropdown.innerHTML = "<option>Error loading departments</option>";
    });
}*/

function initDepartmentDropdown() {
  const dropdown = document.getElementById("departmentDropdownMenu");
  if (!dropdown) return;

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  fetch("../backend/get_user_departments.php")
    .then((res) => {
      if (!res.ok) throw new Error("Failed to fetch departments");
      return res.json();
    })
    .then((data) => {
      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = `<li class="dropdown-item text-muted">No departments available</li>`;
        loadBillingDropdown([]);
        updateDepartmentLabel();
        return;
      }

      dropdown.innerHTML = `
        <li>
          <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
            <input type="checkbox" value="all" class="viz-dept-checkbox">
            <strong>All Departments</strong>
          </label>
        </li>
      `;

      data.forEach((dept) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
              <input type="checkbox" value="${dept.id}" class="viz-dept-checkbox" data-name="${dept.name}">
              ${dept.name}
            </label>
          </li>
        `;
      });

      loadBillingDropdown([]);
      updateDepartmentLabel();
    })
    .catch((err) => {
      console.error("Department load error:", err);
      dropdown.innerHTML = `<li class="dropdown-item text-muted">Error loading departments</li>`;
      loadBillingDropdown([]);
      updateDepartmentLabel();
    });
}

//=================================================
// LOAD AHT WORK MODES
//=================================================

/*function loadAHTWorkModes(departmentId) {
  const dropdown = document.getElementById("workModeDropdownMenu");

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  //fetch("../backend/get_work_modes.php")
  fetch(
    `../backend/get_department_work_modes.php?dept_id=${departmentId}&analytics=1`,
  )
    .then((res) => res.json())
    .then((data) => {
      /*dropdown.innerHTML = "";

      data.forEach((mode) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item">
              <input
                type="radio"
                name="workmode"
                value="${mode.id}"
                class="workmode-radio">
              ${mode.name}
            </label>
          </li>
        `;
      });

      dropdown.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = `
        <li class="dropdown-item text-muted">
            No work modes assigned
        </li>`;

        return;
      }

      data.forEach((mode) => {
        dropdown.innerHTML += `
        <li>
            <label class="dropdown-item">

                <input
                    type="radio"
                    name="workmode"
                    value="${mode.id}"
                    class="workmode-radio">

                ${mode.name}

            </label>
        </li>
    `;
      });
    });
}*/

/*function loadAHTWorkModes(departmentId = "all") {
  const dropdown = document.getElementById("workModeDropdownMenu");

  dropdown.innerHTML = `
        <li class="dropdown-item text-muted">
            Loading...
        </li>`;

  let url;

  if (departmentId === "all") {
    url = "../backend/get_department_work_modes.php?analytics=1&all=1";
  } else {
    url = `../backend/get_department_work_modes.php?analytics=1&dept_id=${departmentId}`;
  }

  fetch(url)*/

function loadAHTWorkModes(departmentId = "all") {
  const dropdown = document.getElementById("workModeDropdownMenu");

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  let url =
    departmentId === "all"
      ? "../backend/get_department_work_modes.php?analytics=1&all=1"
      : `../backend/get_department_work_modes.php?analytics=1&dept_id=${departmentId}`;

  fetch(url)
    .then((res) => res.json())

    .then((data) => {
      dropdown.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = `
                    <li class="dropdown-item text-muted">
                        No work modes found
                    </li>`;

        return;
      }

      dropdown.innerHTML += `
                <li>
                    <label class="dropdown-item">

                        <input
                            type="radio"
                            class="workmode-radio"
                            name="workmode"
                            value="all">

                        <strong>All Work Modes</strong>

                    </label>
                </li>
            `;

      data.forEach((mode) => {
        dropdown.innerHTML += `
                    <li>
                        <label class="dropdown-item">

                            <input
                                type="radio"
                                class="workmode-radio"
                                name="workmode"
                                value="${mode.id}">

                            ${mode.name}

                        </label>
                    </li>
                `;
      });
    });
}

//=================================================
// TASK LOADER
//=================================================

function loadAHTTasksByWorkMode(workModeId) {
  const dropdown = document.getElementById("taskDropdownMenu");

  dropdown.innerHTML = `<li class="dropdown-item text-muted">
Loading tasks...
</li>`;

  //fetch(`../backend/get_tasks_by_workmode.php?work_mode_id=${workModeId}`)
  let url =
    workModeId === "all"
      ? "../backend/get_tasks_by_workmode.php?all=1"
      : `../backend/get_tasks_by_workmode.php?work_mode_id=${workModeId}`;

  fetch(url)
    .then((res) => res.json())
    /*.then((data) => {
      dropdown.innerHTML = "";

      if (!data.length) {
        dropdown.innerHTML = `
        <li class="dropdown-item text-muted">
            No tasks available for this work mode
        </li>
    `;

        return;
      }

      data.forEach((task) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item">
              <input
                type="checkbox"
                value="${task.id}"
                class="task-checkbox">
              ${task.description}
            </label>
          </li>
        `;
      });
    });*/
    .then((data) => {
      dropdown.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = `
            <li class="dropdown-item text-muted">
                No tasks available for this work mode
            </li>
        `;

        return;
      }

      // All Tasks option
      dropdown.innerHTML += `
        <li>
            <label class="dropdown-item">
                <input
                    type="checkbox"
                    value="all"
                    class="task-checkbox task-all">

                <strong>All Task Descriptions</strong>
            </label>
        </li>
    `;

      // Individual tasks (WORKING VERSION)
      /*data.forEach((task) => {
        dropdown.innerHTML += `
            <li>
                <label class="dropdown-item">

                    <input
                        type="checkbox"
                        value="${task.id}"
                        class="task-checkbox">

                    ${task.description}

                </label>
            </li>
        `;
      });*/
      const excludedTasks = ["Away - Break", "End Shift"];

      data
        .filter((task) => !excludedTasks.includes(task.description))
        .forEach((task) => {
          dropdown.innerHTML += `
            <li>
                <label class="dropdown-item">

                    <input
                        type="checkbox"
                        value="${task.id}"
                        class="task-checkbox">

                    ${task.description}

                </label>
            </li>
        `;
        });
    });
}

//=================================================
// TASK LABEL UPDATE
//=================================================

function updateAHTTaskLabel() {
  const label = document.getElementById("taskDropdownLabel");

  //const checked = document.querySelectorAll(".task-checkbox:checked");

  const checked = document.querySelectorAll(
    ".task-checkbox:checked:not(.task-all)",
  );

  const allChecked = document.querySelector(".task-all:checked");

  if (allChecked) {
    label.textContent = "All Task Descriptions";
    return;
  }

  if (!checked.length) {
    label.textContent = "Select Tasks";
    return;
  }

  const names = [...checked].map((cb) => cb.parentElement.textContent.trim());

  label.textContent =
    names.length <= 2 ? names.join(", ") : `${names.length} Tasks Selected`;
}

/*function loadBillingFilter(deptId) {
  const billingDropdown = document.getElementById("billingFilter");

  if (!billingDropdown) return;

  billingDropdown.innerHTML = `<option>Loading...</option>`;

  let url = "../backend/get_billing_by_department.php";

  // ✅ Only send department_id if NOT "all"
  if (deptId && deptId !== "all") {
    url += `?department_id=${deptId}`;
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      billingDropdown.innerHTML = `<option value="">All</option>`;

      data.forEach((cat) => {
        const opt = document.createElement("option");
        opt.value = cat.id;
        opt.textContent = cat.category_name;
        billingDropdown.appendChild(opt);
      });
    })
    .catch(() => {
      billingDropdown.innerHTML = `<option>Error</option>`;
    });
}*/

function getSelectedDepartments() {
  const menu = document.getElementById("departmentDropdownMenu");
  if (!menu) return [];
  return [...menu.querySelectorAll(".viz-dept-checkbox:checked")].map(
    (cb) => cb.value,
  );
}

function getSelectedTasks() {
  return [...document.querySelectorAll(".task-checkbox:checked")].map(
    (cb) => cb.value,
  );
}

function getSelectedBilling() {
  const menu = document.getElementById("billingDropdownMenu");
  if (!menu) return [];
  return [...menu.querySelectorAll(".viz-billing-checkbox:checked")]
    .map((cb) => cb.value)
    .filter((v) => v !== "");
}

function getSelectedDepartmentLabel() {
  const menu = document.getElementById("departmentDropdownMenu");
  if (!menu) return "Department";

  const selected = menu.querySelectorAll(".viz-dept-checkbox:checked");
  if (selected.length === 0) return "Department";

  if ([...selected].some((cb) => cb.value === "all")) {
    return "All Departments";
  }

  const names = [...selected].map(
    (cb) => cb.dataset.name || cb.parentElement.textContent.trim(),
  );

  return names.length <= 2 ? names.join(", ") : `${names.length} Departments`;
}

/*function loadBillingDropdown(deptIds = []) {
  const dropdown = document.getElementById("billingDropdownMenu");
  if (!dropdown) return;

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  let url = "../backend/get_billing_by_department.php";

  if (deptIds.length > 0 && !deptIds.includes("all")) {
    url += `?department_ids=${deptIds.join(",")}`;
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      dropdown.innerHTML = "";

      dropdown.innerHTML += `
        <li>
          <label class="dropdown-item d-flex align-items-center gap-2">
            <input type="checkbox" value="" class="billing-checkbox">
            <strong>All</strong>
          </label>
        </li>
      `;

      data.forEach((cat) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item d-flex align-items-center gap-2">
              <input type="checkbox" value="${cat.id}" class="billing-checkbox">
              ${cat.category_name}
            </label>
          </li>
        `;
      });
    });
}*/

function updateDepartmentLabel() {
  const menu = document.getElementById("departmentDropdownMenu");
  const label = document.getElementById("departmentDropdownLabel");
  if (!menu || !label) return;

  const selected = menu.querySelectorAll(".viz-dept-checkbox:checked");

  if (selected.length === 0) {
    label.textContent = "Select Departments";
    return;
  }

  if ([...selected].some((cb) => cb.value === "all")) {
    label.textContent = "All Departments";
    return;
  }

  const names = [...selected].map(
    (cb) => cb.dataset.name || cb.parentElement.textContent.trim(),
  );

  label.textContent =
    names.length <= 2
      ? names.join(", ")
      : `${names.length} Departments Selected`;
}

function updateBillingLabel() {
  const menu = document.getElementById("billingDropdownMenu");
  const label = document.getElementById("billingDropdownLabel");
  if (!menu || !label) return;

  const selected = menu.querySelectorAll(".viz-billing-checkbox:checked");

  if (selected.length === 0) {
    label.textContent = "Select Billing Category";
    return;
  }

  const names = [...selected].map(
    (cb) => cb.dataset.name || cb.parentElement.textContent.trim(),
  );

  label.textContent =
    names.length <= 2
      ? names.join(", ")
      : `${names.length} Categories Selected`;
}

function loadBillingDropdown(deptIds = []) {
  const dropdown = document.getElementById("billingDropdownMenu");
  if (!dropdown) return;

  if (deptIds.length === 0) {
    dropdown.innerHTML = `
      <li class="dropdown-item text-muted">Select department first</li>
    `;
    updateBillingLabel();
    return;
  }

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  let url = "../backend/get_billing_by_department.php";

  if (!deptIds.includes("all")) {
    url += `?department_ids=${deptIds.join(",")}`;
  }

  fetch(url)
    .then((res) => {
      if (!res.ok) throw new Error("Failed to fetch billing categories");
      return res.json();
    })
    .then((data) => {
      if (!Array.isArray(data) || data.length === 0) {
        dropdown.innerHTML = `
          <li class="dropdown-item text-muted">No billing categories found</li>
        `;
        updateBillingLabel();
        return;
      }

      dropdown.innerHTML = `
        <li>
          <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
            <input type="checkbox" value="" class="viz-billing-checkbox" data-name="All">
            <strong>All</strong>
          </label>
        </li>
      `;

      data.forEach((cat) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
              <input type="checkbox" value="${cat.id}" class="viz-billing-checkbox" data-name="${cat.category_name}">
              ${cat.category_name}
            </label>
          </li>
        `;
      });

      updateBillingLabel();
    })
    .catch((err) => {
      console.error("Billing load error:", err);
      dropdown.innerHTML = `
        <li class="dropdown-item text-muted">Error loading billing categories</li>
      `;
      updateBillingLabel();
    });
}

function getSelectedEmployee() {
  const checked = document.querySelector(".employee-radio:checked");
  return checked ? checked.value : "";
}

function loadEmployeeDropdown(deptIds = []) {
  const dropdown = document.getElementById("employeeDropdownMenu");
  const label = document.getElementById("employeeDropdownLabel");

  if (!dropdown) return;

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  /*let url = "../backend/get_analytics_employees.php";

  if (deptIds.length > 0 && !deptIds.includes("all")) {
    url += "?department_ids=" + deptIds.join(",");
  }*/

  let url = "../backend/get_analytics_employees.php";

  if (deptIds.length > 0 && !deptIds.includes("all")) {
    url += "?department_ids=" + deptIds.join(",");
  }

  /*if (deptIds.length === 0) {
    dropdown.innerHTML = `
    <li class="dropdown-item text-muted">
        Select Department First
    </li>`;*/

  if (deptIds.length === 0) {
    dropdown.innerHTML = `
        <li class="dropdown-item text-muted">
            Loading...
        </li>`;

    label.textContent = "All Employees";

    return;
  }

  fetch(url)
    .then((r) => r.json())
    .then((users) => {
      dropdown.innerHTML = "";

      dropdown.innerHTML += `
                <li>
                    <label class="dropdown-item">
                        <input
                            type="radio"
                            name="employeeFilter"
                            class="employee-radio"
                            value="">
                        All Employees
                    </label>
                </li>
            `;

      users.forEach((user) => {
        dropdown.innerHTML += `
                    <li>
                        <label class="dropdown-item">
                            <input
                                type="radio"
                                name="employeeFilter"
                                class="employee-radio"
                                value="${user.id}">

                            ${user.first_name} ${user.last_name}
                        </label>
                    </li>
                `;
      });
    });
}

// TEST VISIBILITY HELPER
function updateEmployeeVisibility() {
  const selectedDepts = getSelectedDepartments();

  const container = document.getElementById("employeeFilterContainer");

  if (
    (chartType === "bar" || chartType === "pie") &&
    selectedDepts.length > 0
  ) {
    container.style.display = "";

    loadEmployeeDropdown(selectedDepts);
  } else if (chartType === "aht") {
    container.style.display = "";
  } else {
    container.style.display = "none";
  }
}

//=======================
// LOAD AHT DEPARTMENTS
//=======================
/*function loadAHTDepartments() {
  const dropdown = document.getElementById("departmentDropdownMenu");

  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading...</li>`;

  fetch("../backend/get_user_departments.php")
    .then((r) => r.json())
    .then((data) => {
      dropdown.innerHTML = "";

      data.forEach((dept) => {
        dropdown.innerHTML += `
                    <li>
                        <label class="dropdown-item">

                            <input
                                type="checkbox"
                                class="aht-department-checkbox"
                                value="${dept.id}"
                                data-name="${dept.name}">

                            ${dept.name}

                        </label>
                    </li>
                `;
      });
    });
}*/

// EVENT LISTENERS
document.addEventListener("DOMContentLoaded", () => {
  // Show fallback immediately on page load
  /*document.getElementById("visualizationChart").style.display = "none";
  document.getElementById("chartFallback").style.display = "block";*/
  initDepartmentDropdown();
  //loadBillingDropdown(); // initial load

  document.getElementById("fte-tab").addEventListener("click", () => {
    document.getElementById("fteContent").style.display = "block";
    document.getElementById("billingContent").style.display = "none";

    document.getElementById("fte-tab").classList.add("active");
    document.getElementById("billing-tab").classList.remove("active");
  });

  document.getElementById("billing-tab").addEventListener("click", () => {
    document.getElementById("fteContent").style.display = "none";
    document.getElementById("billingContent").style.display = "block";

    document.getElementById("billing-tab").classList.add("active");
    document.getElementById("fte-tab").classList.remove("active");
  });

  document.addEventListener("change", function (e) {
    if (!e.target.classList.contains("employee-radio")) return;

    const label = document.getElementById("employeeDropdownLabel");

    if (!label) return;

    if (e.target.value === "") {
      label.textContent = "All Employees";
    } else {
      label.textContent = e.target.parentElement.textContent.trim();
    }
  });

  // FOR DOCUMENT DROPDOWN FILTER
  document.addEventListener("change", (e) => {
    const deptMenu = document.getElementById("departmentDropdownMenu");
    if (
      deptMenu &&
      e.target.matches("#departmentDropdownMenu .viz-dept-checkbox")
    ) {
      const allCb = deptMenu.querySelector('.viz-dept-checkbox[value="all"]');

      if (e.target.value === "all" && e.target.checked) {
        deptMenu
          .querySelectorAll('.viz-dept-checkbox:not([value="all"])')
          .forEach((cb) => {
            cb.checked = false;
          });
      } else if (e.target.value !== "all" && e.target.checked && allCb) {
        allCb.checked = false;
      }

      /*const selectedDepts = getSelectedDepartments();
      updateDepartmentLabel();

      loadBillingDropdown(selectedDepts);

      // ONLY FOR PRODUCTION HOURS & TASK DISTRIBUTION
      updateEmployeeVisibility();
      return;*/

      updateDepartmentLabel();

      const selectedDepts = getSelectedDepartments();

      if (chartType === "aht") {
        document.getElementById("employeeDropdownLabel").textContent =
          "All Employees";

        // Single Department
        /*if (selectedDepts.length === 1 && selectedDepts[0] !== "all") {
          loadEmployeeDropdown(selectedDepts);

          loadAHTWorkModes(selectedDepts[0]);
        }

        // All Departments OR Multiple Departments
        else {
          loadEmployeeDropdown(["all"]);

          loadAllWorkModes();
        }*/

        if (selectedDepts.length === 1 && selectedDepts[0] !== "all") {
          loadEmployeeDropdown(selectedDepts);

          loadAHTWorkModes(selectedDepts[0]);
        } else {
          loadEmployeeDropdown(["all"]);

          loadAHTWorkModes("all");
        }

        document.getElementById("taskDropdownMenu").innerHTML = `
        <li class="dropdown-item text-muted">
            Select a work mode first
        </li>`;

        return;
      }

      loadBillingDropdown(selectedDepts);

      updateEmployeeVisibility();

      return;
    }

    if (e.target.matches("#billingDropdownMenu .viz-billing-checkbox")) {
      updateBillingLabel();
    }

    // FOR AHT
    /*
    if (
      chartType === "aht" &&
      e.target.classList.contains("aht-department-checkbox")
    ) {
      // allow only one checked
      document.querySelectorAll(".aht-department-checkbox").forEach((cb) => {
        if (cb !== e.target) cb.checked = false;
      });

      const deptLabel = document.getElementById("departmentDropdownLabel");

      const employeeLabel = document.getElementById("employeeDropdownLabel");

      // user unchecks department
      if (!e.target.checked) {
        deptLabel.textContent = "All Departments";

        employeeLabel.textContent = "All Employees";

        loadEmployeeDropdown([]);

        return;
      }

      deptLabel.textContent = e.target.parentElement.textContent.trim();

      updateEmployeeVisibility();

      employeeLabel.textContent = "All Employees";

      loadEmployeeDropdown([e.target.value]);

      return;
    }*/

    if (chartType === "aht" && e.target.classList.contains("workmode-radio")) {
      const label = document.getElementById("workModeDropdownLabel");

      label.textContent = e.target.parentElement.textContent.trim();

      loadAHTTasksByWorkMode(e.target.value);

      return;
    }

    /*if (chartType === "aht" && e.target.classList.contains("task-checkbox")) {
      updateAHTTaskLabel();
    }*/

    if (chartType === "aht" && e.target.classList.contains("task-checkbox")) {
      const menu = document.getElementById("taskDropdownMenu");

      const allTask = menu.querySelector(".task-all");

      if (e.target.classList.contains("task-all")) {
        menu
          .querySelectorAll(".task-checkbox:not(.task-all)")
          .forEach((cb) => (cb.checked = e.target.checked));
      } else {
        if (e.target.checked && allTask) {
          allTask.checked = false;
        }
      }

      updateAHTTaskLabel();

      return;
    }

    if (chartType === "aht" && e.target.classList.contains("employee-radio")) {
      const label = document.getElementById("employeeDropdownLabel");

      if (e.target.value === "") {
        label.textContent = "All Employees";
      } else {
        label.textContent = e.target.parentElement.textContent.trim();
      }

      return;
    }
  });

  const chartEl = document.getElementById("visualizationChart");
  const fallbackEl = document.getElementById("chartFallback");

  if (chartEl) chartEl.style.display = "none";
  if (fallbackEl) fallbackEl.style.display = "block";

  // Populate month selector (last 12 months)
  const monthSel = document.getElementById("monthSelector");

  if (monthSel) {
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

    monthSel.addEventListener("change", () => {
      if (chartType === "pie") loadPieChart();
    });
  }

  const chartTypeDropdown = document.getElementById("chartTypeDropdown");

  // CHART TYPE DROPDOWN LOGIC STARTS ============================================
  if (chartTypeDropdown) {
    chartTypeDropdown.addEventListener("change", (e) => {
      chartType = e.target.value;

      const deptLabel = document.getElementById("departmentFilterLabel");
      const deptButton = document.getElementById("departmentDropdownLabel");
      const billingButton = document.getElementById("billingDropdownLabel");
      const billingLabel = document.getElementById("billingFilterLabel");
      //NEW VARIABLE FOR EXPORTER
      const exportContainer = document.getElementById("ahtExportContainer");

      if (chartType === "aht") {
        //FOR AHT CHART HIDDEN
        document.getElementById("visualizationChart").style.display = "none";

        document.getElementById("ahtTableContainer").style.display = "none";
        document.getElementById("ahtTableContainer").innerHTML = "";
        document
          .getElementById("analyticsChartColumn")
          .classList.remove("col-md-7");

        document
          .getElementById("analyticsChartColumn")
          .classList.add("col-md-12");

        document.getElementById("analyticsDetailsColumn").style.display =
          "none";

        //UNCOMMENT IF NEW CODE DOESN'T WORK
        /*
        deptLabel.textContent = "Work Mode";
        billingLabel.textContent = "Task Description";

        deptButton.textContent = "Select Work Mode";
        billingButton.textContent = "Select Tasks";

        loadAHTWorkModes();
        */
        deptLabel.textContent = "Department";
        deptButton.textContent = "Select Department";

        document.getElementById("billingFilterLabel").style.display = "none";
        document
          .getElementById("billingFilterContainer")
          .closest("[class*='col-']").style.display = "none";

        // Employee filter is used by AHT
        document.getElementById("employeeFilterContainer").style.display = "";

        document.getElementById("employeeDropdownLabel").textContent =
          "All Employees";

        document.getElementById("employeeDropdownMenu").innerHTML = `
<li class="dropdown-item text-muted">
    Select Department First
</li>`;

        document.getElementById("workModeFilterContainer").style.display = "";
        document.getElementById("taskFilterContainer").style.display = "";

        document.getElementById("workModeDropdownLabel").textContent =
          "Select Work Mode";
        document.getElementById("taskDropdownLabel").textContent =
          "Select Tasks";

        document.getElementById("taskDropdownMenu").innerHTML = "";

        //loadAHTDepartments();

        loadEmployeeDropdown([]);

        //loadAHTWorkModes();
        document.getElementById("workModeDropdownMenu").innerHTML = `
<li class="dropdown-item text-muted">
    Select one department first
</li>`;

        document.getElementById("taskDropdownMenu").innerHTML = `
<li class="dropdown-item text-muted">
    Select a work mode first
</li>`;

        document.querySelector(".nav-tabs").style.display = "none";

        document
          .getElementById("barMode")
          .closest("[class*='col-']").style.display = "none";

        exportContainer.style.display = "";
      } else {
        //FOR OTHER TABLE
        /*document.getElementById("ahtTableContainer").style.display = "none";
        document.getElementById("visualizationChart").style.display = "block";*/

        const ahtTable = document.getElementById("ahtTableContainer");

        ahtTable.style.display = "none";
        ahtTable.innerHTML = "";

        document.getElementById("visualizationChart").style.display = "block";

        document
          .getElementById("analyticsChartColumn")
          .classList.remove("col-md-12");

        document
          .getElementById("analyticsChartColumn")
          .classList.add("col-md-7");

        document.getElementById("analyticsDetailsColumn").style.display = "";

        exportContainer.style.display = "none";

        // Billing Category (for task distribution)
        if (chartType === "pie") {
          document.getElementById("billingFilterContainer").style.display =
            "none";

          document.getElementById("viewFilterContainer").style.display = "none";
        } else {
          document.getElementById("billingFilterContainer").style.display = "";

          document.getElementById("viewFilterContainer").style.display = "";
        }

        // Hide AHT-only filters
        document.getElementById("employeeFilterContainer").style.display =
          "none";
        document.getElementById("workModeFilterContainer").style.display =
          "none";
        document.getElementById("taskFilterContainer").style.display = "none";

        deptLabel.textContent = "Departments";
        deptButton.textContent = "Select Departments";

        document.getElementById("departmentFilterLabel").textContent =
          "Departments";
        document.getElementById("departmentDropdownLabel").textContent =
          "Select Departments";

        document.getElementById("billingFilterLabel").textContent =
          "Billing Category";
        document.getElementById("billingDropdownLabel").textContent =
          "Select Billing Category";

        if (chartType !== "pie") {
          document.getElementById("billingFilterContainer").style.display = "";
        }

        document.getElementById("workModeFilterContainer").style.display =
          "none";
        document.getElementById("taskFilterContainer").style.display = "none";

        initDepartmentDropdown();
        updateEmployeeVisibility();

        //DISPLAY EMPLOYEE DROPDOWN
        document.getElementById("employeeDropdownLabel").textContent =
          "All Employees";

        document.getElementById("employeeDropdownMenu").innerHTML = `
<li class="dropdown-item text-muted">
    Select a department first
</li>`;

        document.querySelector(".nav-tabs").style.display = "flex";

        // View
        if (chartType === "pie") {
          document.getElementById("viewFilterContainer").style.display = "none";
        } else {
          document.getElementById("viewFilterContainer").style.display = "";
        }
      }
    });
  }

  // CHART TYPE DROPDOWN ENDS ==============================================

  function loadGraph() {
    if (!chartType) return;

    const monthFilter = document.getElementById("month-filter");

    if (chartType === "bar") {
      if (monthFilter) monthFilter.style.display = "none";
      loadBarChart();
    } else if (chartType === "pie") {
      if (monthFilter) monthFilter.style.display = "block";
      loadPieChart();
    } else if (chartType === "aht") {
      if (monthFilter) monthFilter.style.display = "none";
      loadAHTChart();
    } else if (
      chartType === "billing" &&
      typeof loadBillingChart === "function"
    ) {
      if (monthFilter) monthFilter.style.display = "none";
      loadBillingChart();
    }
  }

  // Apply date range filter
  const applyDateBtn = document.getElementById("applyDateRange");

  if (applyDateBtn) {
    applyDateBtn.addEventListener("click", () => {
      if (chartType === "bar") loadBarChart();
    });
  }

  // For bar chart (PRODUCTION HOURS VIEW)
  function loadBarChart() {
    //document.getElementById("ahtTableContainer").style.display = "none";

    const ahtTable = document.getElementById("ahtTableContainer");

    ahtTable.style.display = "none";
    ahtTable.innerHTML = "";

    showLoader();

    const start = document.getElementById("bar_Start_Date").value;
    const end = document.getElementById("bar_End_Date").value;
    const mode = document.querySelector("#barMode")?.value || "daily";
    //const billing = document.getElementById("billingFilter")?.value || "";
    const deptName = getSelectedDepartmentLabel();

    //let url = `../backend/client/fetch_bar_graph.php?dept_id=${selectedDept}&mode=${mode}`;

    const selectedDepts = getSelectedDepartments();
    const selectedBilling = getSelectedBilling();

    const employeeId = getSelectedEmployee();

    let url = `../backend/client/fetch_bar_graph.php?mode=${mode}`;

    // ✅ Departments
    if (selectedDepts.length > 0 && !selectedDepts.includes("all")) {
      url += `&department_ids=${selectedDepts.join(",")}`;
    }

    // ✅ Billing
    if (selectedBilling.length > 0) {
      url += `&billing_category_ids=${selectedBilling.join(",")}`;
    }

    if (employeeId) {
      url += `&user_id=${employeeId}`;
    }

    /*if (billing) {
      url += `&billing_category_id=${billing}`;
    }*/

    if (start && end) {
      url += `&start_date=${start}&end_date=${end}`;
    } else {
      const year = new Date().getFullYear();
      url += `&start_date=${year}-01-01&end_date=${year}-12-31`;
    }

    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        const noData =
          !data || (!data.fte && (!data.labels || data.labels.length === 0));

        if (noData) {
          if (chartEl) chartEl.style.display = "none";
          if (fallbackEl) fallbackEl.style.display = "block";
          document.getElementById("fteContent").innerHTML =
            `<p class="text-muted fst-italic text-center">No data available for the selected date range.</p>`;
          document.getElementById("billingContent").innerHTML = "";
          return;
        }

        let chartLabels = [];
        let chartValues = [];
        let chartLabel = "";

        // 📆 Monthly view (Production Hours per month)
        if (mode === "monthly" && data.fte) {
          chartLabels = data.fte.map((f) => f.month);
          chartValues = data.fte.map((f) => f.fte); // ✅ use FTE values instead of total_hours FOR CHART HOVER
          chartLabel = `Monthly FTE – ${deptName}`;

          renderChart("bar", chartLabels, chartValues, chartLabel);

          // 🧾 Task list shows FTE values
          let html = "";

          if (employeeId && data.user_name) {
            html += `
        <div class="mb-3">

            <h5 class="fw-bold mb-1">
                ${data.user_name}
            </h5>

            <small class="text-muted">
                ${data.department_name}
            </small>

        </div>
    `;
          }

          html += `
<h5 class="fw-bold">
${deptName} – Monthly FTE Report
</h5>

<ul class="list-group">
`;

          let totalHours = 0;
          data.fte.forEach((f) => {
            const hrs = Number(f.total_hours || 0);

            totalHours += hrs;

            const fte = Number(f.fte || 0).toFixed(2);

            html += `
<li class="list-group-item d-flex justify-content-between">

<span>${f.month}</span>

<span>${hrs.toFixed(2)} hrs <strong>(${fte} FTE)</strong></span>

</li>
`;
          });
          html += `

</ul>

<hr>

<div class="d-flex justify-content-between fw-bold">

    <span>Total Production Hours</span>

    <span>${totalHours.toFixed(2)} hrs</span>

</div>

`;

          document.getElementById("fteContent").innerHTML = html;
          loadBillingSummary(start, end);
        }

        // 📅 Daily or other modes (FTE view)
        else {
          chartLabels = data.labels;
          chartValues = data.fte; // ✅ DISPLAY DAILY FTE VALUES
          chartLabel = `Daily FTE – ${deptName}`; // FIXED LABEL FOR TOOLTIP & AXIS

          renderChart("bar", chartLabels, chartValues, chartLabel);

          // 🧾 Task list with FTE shown beside total hours (NEW)
          let html = "";

          if (employeeId && data.user_name) {
            html += `
        <div class="mb-3">

            <h5 class="fw-bold mb-1">
                ${data.user_name}
            </h5>

            <small class="text-muted">
                ${data.department_name}
            </small>

        </div>
    `;
          }

          html += `
    <h5 class="fw-bold">
        ${deptName} – Daily FTE Report
    </h5>

    <ul class="list-group">
`;

          let totalHours = 0;
          data.labels.forEach((label, i) => {
            // ensure numeric
            const totalHrs = Number(data.values[i]) || 0;
            totalHours += totalHrs;
            /*const fte =
              data.fte && data.fte[i]
                ? data.fte[i].toFixed(2)
                : (totalHrs / 8).toFixed(2);*/
            const fte = Number(data.fte?.[i] ?? 0).toFixed(2);
            html += `<li class="list-group-item d-flex justify-content-between">
             <span>${label}</span>
             <span>${totalHrs.toFixed(
               2,
             )} hrs <strong>(${fte} FTE)</strong></span>
           </li>`;
          });
          html += `

</ul>

<hr>

<div class="d-flex justify-content-between fw-bold">

    <span>Total Production Hours</span>

    <span>${totalHours.toFixed(2)} hrs</span>

</div>

`;
          document.getElementById("fteContent").innerHTML = html;
          loadBillingSummary(start, end);
        }

        if (chartEl) chartEl.style.display = "block";
        if (fallbackEl) fallbackEl.style.display = "none";
      })
      .catch((err) => {
        console.error("Bar chart error:", err);
        if (chartEl) chartEl.style.display = "none";
        if (fallbackEl) fallbackEl.style.display = "block";
        document.getElementById("taskList").innerHTML =
          `<p class="text-danger text-center">Error loading chart data. Please try again.</p>`;
      })

      .finally(() => hideLoader());
  }

  // Pie chart
  function loadPieChart() {
    //document.getElementById("ahtTableContainer").style.display = "none";

    const ahtTable = document.getElementById("ahtTableContainer");

    ahtTable.style.display = "none";
    ahtTable.innerHTML = "";

    const selectedDepts = getSelectedDepartments();

    if (selectedDepts.includes("all") || selectedDepts.length !== 1) {
      alert(
        "Task distribution requires exactly one department. Please select a single department.",
      );
      return;
    }

    const [year, month] = monthSel.value.split("-");
    const employeeId = getSelectedEmployee();
    showLoader();

    let url = `../backend/client/fetch_pie_graph.php?dept=${selectedDepts[0]}&year=${year}&month=${month}`;

    if (employeeId) {
      url += `&user_id=${employeeId}`;
    }

    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        /*if (!data.success || !data.labels || data.labels.length === 0) {
        document.getElementById("visualizationChart").style.display = "none";
        document.getElementById("chartFallback").style.display = "block";
        document.getElementById("taskList").innerHTML =
          "<p class='text-muted fst-italic text-center'>No task data available for this period.</p>";
        return;
      }*/

        const total = (data.values || []).reduce(
          (a, b) => a + Number(b || 0),
          0,
        );

        if (
          !data.success ||
          !data.labels ||
          data.labels.length === 0 ||
          total === 0
        ) {
          if (chartEl) chartEl.style.display = "none";
          if (fallbackEl) fallbackEl.style.display = "block";

          document.getElementById("billingContent").innerHTML =
            "<p class='text-muted fst-italic text-center'>No measurable task hours for this period.</p>";

          return;
        }

        // Chart colors
        const colors = [
          "#ff6384",
          "#36a2eb",
          "#ffce56",
          "#4bc0c0",
          "#9966ff",
          "#ff9f40",
          "#c9cbcf",
        ];

        const chartTitle = data.employeeName
          ? `${data.employeeName} — Task Distribution`
          : `${data.deptName} Department — Task Distribution`;

        renderChart("pie", data.labels, data.values, chartTitle, colors);
        renderTaskList(
          data.list,

          colors,

          data.deptName,

          data.employeeName,
        );
      })
      .catch((err) => console.error("Pie chart error:", err))
      .finally(() => hideLoader());
  }

  //FOR TESTING

  function loadAHTChart() {
    //FOR AHT CHART
    document.getElementById("visualizationChart").style.display = "none";
    document.getElementById("ahtTableContainer").style.display = "none";
    showLoader();

    const startDate = document.getElementById("bar_Start_Date").value;

    const endDate = document.getElementById("bar_End_Date").value;

    const workMode = document.querySelector(".workmode-radio:checked");

    const taskIds = getSelectedTasks();

    if (!workMode) {
      alert("Please select a work mode.");
      hideLoader();
      return;
    }

    if (taskIds.length === 0) {
      alert("Please select at least one task.");
      hideLoader();
      return;
    }

    /*let url =
      `../backend/client/fetch_aht_graph.php` +
      `?start_date=${startDate}` +
      `&end_date=${endDate}` +
      `&work_mode_id=${workMode.value}` +
      `&task_ids=${taskIds.join(",")}`;*/

    const employeeId = getSelectedEmployee();

    // CHANGE
    //const dept = document.querySelector(".aht-department-checkbox:checked");

    const selectedDepartments = getSelectedDepartments();

    /*if (selectedDepartments.length !== 1) {
      alert("Please select exactly one department.");

      hideLoader();

      return;
    }*/

    let departmentId = "";

    if (selectedDepartments.length === 1 && selectedDepartments[0] !== "all") {
      departmentId = selectedDepartments[0];
    }

    //const departmentId = selectedDepartments[0];

    let url =
      `../backend/client/fetch_aht_graph.php` +
      `?start_date=${startDate}` +
      `&end_date=${endDate}` +
      `&work_mode_id=${workMode.value}` +
      `&task_ids=${taskIds.join(",")}`;

    if (employeeId) {
      url += `&user_id=${employeeId}`;
    }

    /*if (dept) {
      url += `&department_id=${dept.value}`;
    }*/

    if (departmentId !== "") {
      url += `&department_id=${departmentId}`;
    }

    console.log({
      department: departmentId,

      employee: employeeId,

      workMode: workMode.value,

      tasks: taskIds,
    });

    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        if (!data.success || !data.data || data.data.length === 0) {
          chartEl.style.display = "none";

          document.getElementById("ahtTableContainer").style.display = "none";

          fallbackEl.style.display = "block";

          document.getElementById("fteContent").innerHTML =
            "<p class='text-muted text-center'>No AHT data found.</p>";

          return;
        }

        /*const labels = data.data.map((x) => x.task_name);

        const values = data.data.map((x) => x.aht);

        renderChart("bar", labels, values, "Average Handling Time (Minutes)");

        let html =
          "<h5 class='fw-bold'>Average Handling Time</h5>" +
          "<ul class='list-group'>";

        data.data.forEach((item) => {
          html += `
        <li class="list-group-item">
          <strong>${item.task_name}</strong><br>

          ${item.total_minutes.toFixed(2)} mins
          /
          ${item.total_volume.toFixed(2)} volume

          <br>

          <span class="text-success fw-bold">
            AHT: ${item.aht.toFixed(2)} mins
          </span>
        </li>
        `;
        });

        html += "</ul>";

        document.getElementById("fteContent").innerHTML = html;*/

        chartEl.style.display = "none";
        fallbackEl.style.display = "none";

        const tableContainer = document.getElementById("ahtTableContainer");

        tableContainer.style.display = "block";

        // FOR DEPT AND NAME DISPLAY AHT
        const deptName = document.getElementById(
          "departmentDropdownLabel",
        ).textContent;

        const employeeName = document.getElementById(
          "employeeDropdownLabel",
        ).textContent;

        const workModeName = document.getElementById(
          "workModeDropdownLabel",
        ).textContent;

        let html = `

<!--div class="card border-success mb-3">

    <div class="card-body py-2">

<div class="row">

${
  deptName !== "All Departments"
    ? `
<div class="col-md-4">
    <strong>Department</strong><br>
    ${deptName}
</div>
`
    : ""
}

${
  employeeName !== "All Employees"
    ? `
<div class="col-md-4">
    <strong>Employee</strong><br>
    ${employeeName}
</div>
`
    : ""
}

<div class="col-md-4">
    <strong>Work Mode</strong><br>
    ${workModeName}
</div>

</div>

    </div>

</div-->

<div class="table-responsive">

<h4 class="fw-bold text-center mb-3">
Average Handling Time
</h4>

<table class="table table-bordered table-striped align-middle">

<thead class="table-success">

<tr>
<th style="width:220px;">Employee</th>
<th>Task Description</th>
<th>Production Time</th>
<th>Total Minutes</th>
<th>Total Volume</th>
<th>Standard AHT</th>
<th>Actual AHT</th>
</tr>

</thead>

<tbody>
`;

        /*data.data.forEach((item) => {
          //const actualAHT = Number(item.aht || 0);

          //const standardAHT = Number(item.standard_aht || 0);

          const actualAHTValue = Number(item.actual_aht_value);

          const standardAHTValue = Number(item.standard_aht_value);

          html += `
<tr>

<td>${item.task_name}</td>

<td class="text-center">
${item.production_time}
</td>

<td class="text-end">
${Number(item.total_minutes).toFixed(2)}
</td>

<td class="text-end">
${Number(item.total_volume).toFixed(2)}
</td>

<td class="text-end">
${item.standard_aht}
</td>

<td class="text-end fw-bold">

${
  actualAHTValue > standardAHTValue
    ? `<span class="text-danger">${item.aht}</span>`
    : `<span class="text-success">${item.aht}</span>`
}

</td>

</tr>
`;
        });*/

        //==============================================
        // GROUP BY EMPLOYEE
        //==============================================

        const employeeGroups = {};

        data.data.forEach((item) => {
          if (!employeeGroups[item.employee_name]) {
            employeeGroups[item.employee_name] = [];
          }

          employeeGroups[item.employee_name].push(item);
        });

        //==============================================
        // RENDER TABLE
        //==============================================

        Object.keys(employeeGroups).forEach((employee) => {
          const rows = employeeGroups[employee];

          rows.forEach((item, index) => {
            const actualAHTValue = Number(item.actual_aht_value);
            const standardAHTValue = Number(item.standard_aht_value);

            html += `<tr>`;

            // Employee cell only once
            if (index === 0) {
              html += `
                <td rowspan="${rows.length}"
                    class="align-middle fw-bold text-center bg-light">

                    ${employee}

                </td>
            `;
            }

            html += `

            <td>${item.task_name}</td>

            <td class="text-center">
                ${item.production_time}
            </td>

            <td class="text-end">
                ${Number(item.total_minutes).toFixed(2)}
            </td>

            <td class="text-end">
                ${Number(item.total_volume).toFixed(2)}
            </td>

            <td class="text-end">
                ${item.standard_aht}
            </td>

            <td class="text-end fw-bold">

                ${
                  actualAHTValue > standardAHTValue
                    ? `<span class="text-danger">${item.aht}</span>`
                    : `<span class="text-success">${item.aht}</span>`
                }

            </td>

        </tr>`;
          });
        });

        html += `
</tbody>
</table>
</div>
`;

        tableContainer.innerHTML = html;
      })
      .catch((err) => {
        console.error(err);
      })
      .finally(() => hideLoader());
  }

  // FOR BILLING CHART

  function loadBillingSummary(start, end) {
    const selectedDepts = getSelectedDepartments();
    const selectedBilling = getSelectedBilling();

    if (selectedDepts.length === 0) {
      document.getElementById("billingContent").innerHTML =
        `<p class="text-muted text-center">Select a department to view billing breakdown.</p>`;
      return;
    }

    let url = `../backend/client/fetch_billing_graph.php?start_date=${start}&end_date=${end}`;

    if (!selectedDepts.includes("all")) {
      url += `&department_ids=${selectedDepts.join(",")}`;
    }

    if (selectedBilling.length > 0) {
      url += `&billing_category_ids=${selectedBilling.join(",")}`;
    }

    document.getElementById("billingContent").innerHTML =
      "<p class='text-muted text-center'>Loading...</p>";

    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        if (!data.success || !data.labels || data.labels.length === 0) return;

        let html = `<h5 class='fw-bold'>Billing Category Breakdown</h5>
                  <ul class='list-group'>`;

        /*data.labels.forEach((label, i) => {
          html += `
        <li class="list-group-item d-flex justify-content-between">
          <span>${label}</span>
          <span>${Number(data.values[i]).toFixed(2)} hrs</span>
        </li>`;
        });*/

        data.labels.forEach((label, i) => {
          const hrs = Number(data.values[i]).toFixed(2);
          const fte = data.fte ? Number(data.fte[i]).toFixed(2) : "0.00";

          html += `
<li class="list-group-item d-flex justify-content-between">
  <span>${label}</span>
  <span>${hrs} hrs <strong>(${fte} FTE)</strong></span>
</li>`;
        });

        html += "</ul>";

        // 🔥 IMPORTANT: append, not replace
        document.getElementById("billingContent").innerHTML = html;
      });
  }

  // Render chart
  function renderChart(type, labels, values, label, colors = []) {
    if (chartInstance) chartInstance.destroy();
    const canvas = document.getElementById("visualizationChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    // Normalize values -> numeric array for Chart.js
    const cleanValues = (values || []).map((v) => {
      if (v === null || v === undefined) return 0;
      if (typeof v === "object") {
        // expecting something like { total_hours: 27.4 } possibly — handle gracefully
        if ("total_hours" in v) return Number(v.total_hours) || 0;
        if ("value" in v) return Number(v.value) || 0;
        return Number(v.y ?? v[0] ?? 0) || 0;
      }
      return Number(v) || 0;
    });

    chartInstance = new Chart(ctx, {
      type: type,
      data: {
        labels: labels,
        datasets: [
          {
            label: label,
            data: cleanValues,
            backgroundColor: type === "pie" ? colors : "rgba(6, 143, 40, 0.6)",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: {
            display: true,
            text: label,
            font: { size: 25, weight: "bold" },
            color: "#0c4701ff",
            padding: { top: 10, bottom: 20 },
          },
          tooltip: {
            enabled: true,
            callbacks: {
              label: function (context) {
                // Normalize parsed value (Chart.js can return objects for certain dataset types)
                let rawParsed = context.parsed;
                let valueNum = 0;
                if (rawParsed === null || rawParsed === undefined) {
                  valueNum = 0;
                } else if (typeof rawParsed === "object") {
                  // e.g., {x:..., y:...} or stacked values
                  valueNum = Number(rawParsed.y ?? rawParsed) || 0;
                } else {
                  valueNum = Number(rawParsed) || 0;
                }

                const dataset = context.chart.data.datasets[0].data || [];
                const total = dataset.reduce(
                  (sum, val) => sum + Number(val || 0),
                  0,
                );

                // For pie chart: show hrs + percentage (keep existing pie behavior)
                if (type === "pie") {
                  const percentage =
                    total > 0 ? ((valueNum / total) * 100).toFixed(1) : "0.0";
                  return `${context.label}: ${valueNum.toFixed(
                    2,
                  )} hrs (${percentage}%)`;
                }

                // For bar (and other) charts: show hours only (no percentage)
                return label.includes("FTE")
                  ? `${context.label}: ${valueNum.toFixed(2)} FTE`
                  : `${context.label}: ${valueNum.toFixed(2)} hrs`;
              },
            },
          },
        },
        // show y-axis for non-pie charts
        scales:
          type !== "pie"
            ? {
                y: {
                  beginAtZero: true,
                  title: {
                    display: true,
                    text: label.includes("FTE") ? "FTE" : "Hours",
                  },
                },
              }
            : {},
      },
    });

    if (chartEl) chartEl.style.display = "block";
    if (fallbackEl) fallbackEl.style.display = "none";
  }

  // FUNCTION: RENDER TASK LIST
  function renderTaskList(
    tasks,

    colors = [],

    deptName = "",

    employeeName = "",
  ) {
    const taskListDiv = document.getElementById("billingContent");

    if (!tasks || tasks.length === 0) {
      taskListDiv.innerHTML =
        "<p class='text-muted fst-italic text-center'>No task data available for this period.</p>";
      return;
    }

    // Compute total hours
    const totalHours = tasks.reduce(
      (sum, t) => sum + parseFloat(t.total_hours || 0),
      0,
    );

    let html = "";

    html += `
<h5 class='fw-bold mb-3'>

Task Distribution

</h5>

<ul class="list-group">
`;

    //let html =
    // "<h5 class='fw-bold mb-3'>Task Distribution</h5><ul class='list-group'>";

    tasks.forEach((t, i) => {
      const color = colors[i % colors.length];
      const hours = parseFloat(t.total_hours || 0);
      const percentage =
        totalHours > 0 ? ((hours / totalHours) * 100).toFixed(1) : 0;

      html += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span>
          <span class="badge rounded-pill" 
                style="background-color:${color}; width:12px; height:12px; display:inline-block; margin-right:8px;"></span>
          ${t.task_name}
        </span>
        <span class="fw-semibold text-muted">
          ${hours} hrs <small>(${percentage}%)</small>
        </span>
      </li>`;
    });

    html += `
    <li class="list-group-item d-flex justify-content-between fw-bold">
      <span>Total</span>
      <span>${totalHours.toFixed(2)} hrs</span>
    </li>
  </ul>`;

    taskListDiv.innerHTML = html;
  }

  const applyFiltersBtn = document.getElementById("applyFilters");

  // RESET ANALYTICS FILTERS STARTS ==============================================
  function resetAnalyticsFilters() {
    // Dates
    document.getElementById("bar_Start_Date").value = "";
    document.getElementById("bar_End_Date").value = "";

    // Exporter
    document.getElementById("ahtExportContainer").style.display = "none";

    // Chart
    document.getElementById("chartTypeDropdown").value = "";

    chartType = null;

    // View
    document.getElementById("barMode").value = "daily";
    const viewContainer = document
      .getElementById("barMode")
      .closest("[class*='col-']");

    viewContainer.style.display = "";

    // Reset departments
    document
      .querySelectorAll(".viz-dept-checkbox")
      .forEach((cb) => (cb.checked = false));

    updateDepartmentLabel();

    // Reset billing

    document
      .querySelectorAll(".viz-billing-checkbox")
      .forEach((cb) => (cb.checked = false));

    updateBillingLabel();

    // Employee

    document
      .querySelectorAll(".employee-radio")
      .forEach((r) => (r.checked = false));

    document.getElementById("employeeDropdownLabel").textContent =
      "All Employees";

    document.getElementById("employeeDropdownMenu").innerHTML = `
        <li class="dropdown-item text-muted">
            Select Department First
        </li>`;

    // Work Mode

    document
      .querySelectorAll(".workmode-radio")
      .forEach((r) => (r.checked = false));

    document.getElementById("workModeDropdownLabel").textContent =
      "Select Work Mode";

    // Task

    document.getElementById("taskDropdownLabel").textContent = "Select Tasks";

    document.getElementById("taskDropdownMenu").innerHTML = `
        <li class="dropdown-item text-muted">
            Select a work mode first
        </li>`;

    // Hide AHT-only filters(resetanalyticsfilter())
    updateEmployeeVisibility();
    document.getElementById("workModeFilterContainer").style.display = "none";
    document.getElementById("taskFilterContainer").style.display = "none";

    // Restore View filter
    document
      .getElementById("barMode")
      .closest("[class*='col-']").style.display = "";

    // Restore Billing filter
    document.getElementById("billingFilterLabel").style.display = "";

    document
      .getElementById("billingFilterContainer")
      .closest("div[class*='col-']").style.display = "";

    // Restore labels
    document.getElementById("departmentFilterLabel").textContent =
      "Departments";
    document.getElementById("departmentDropdownLabel").textContent =
      "Select Departments";

    document.getElementById("billingDropdownLabel").textContent =
      "Select Billing Category";

    // Reset chart
    if (chartInstance) {
      chartInstance.destroy();

      chartInstance = null;
    }

    document.getElementById("visualizationChart").style.display = "none";

    document.getElementById("ahtTableContainer").style.display = "none";

    document.getElementById("ahtTableContainer").innerHTML = "";

    document.getElementById("chartFallback").style.display = "block";

    // Rebuild normal department filter
    initDepartmentDropdown();
    document.getElementById("employeeDropdownMenu").innerHTML = `
<li class="dropdown-item text-muted">
Select Department First
</li>`;
  }

  // RESET ANALYTICS FILTERS END ===============================================

  //RESET FILTER LAYOUT
  const resetBtn = document.getElementById("resetFilters");

  if (resetBtn) {
    resetBtn.addEventListener("click", resetAnalyticsFilters);
  }

  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener("click", () => {
      chartType =
        document.getElementById("chartTypeDropdown")?.value || chartType;

      if (!chartType) {
        alert("Please select chart type.");
        return;
      }

      const selectedDepts = getSelectedDepartments();

      /*if (chartType !== "billing" && selectedDepts.length === 0) {
        alert("Please select at least one department.");
        return;
      }*/

      if (chartType === "aht") {
        const workMode = document.querySelector(".workmode-radio:checked");

        if (!workMode) {
          alert("Please select a work mode.");
          return;
        }
      } else {
        const selectedDepts = getSelectedDepartments();

        if (selectedDepts.length === 0) {
          alert("Please select at least one department.");
          return;
        }
      }

      loadGraph();
    });
  }
});
