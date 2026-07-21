//==================================================
// AHT EXPORT
//==================================================

document.addEventListener("DOMContentLoaded", () => {
  const exportBtn = document.getElementById("exportAHTBtn");

  if (!exportBtn) return;

  exportBtn.addEventListener("click", exportAHT);
});

function exportAHT() {
  //----------------------------------------
  // Dates
  //----------------------------------------

  const startDate = document.getElementById("bar_Start_Date").value;

  const endDate = document.getElementById("bar_End_Date").value;

  //----------------------------------------
  // Employee
  //----------------------------------------

  let employeeId = "";

  const employee = document.querySelector(".employee-radio:checked");

  if (employee) {
    employeeId = employee.value;
  }

  //----------------------------------------
  // Department
  //----------------------------------------

  let departmentId = "";

  const selectedDepartments = [
    ...document.querySelectorAll(".viz-dept-checkbox:checked"),
  ].map((cb) => cb.value);

  if (selectedDepartments.length === 1 && selectedDepartments[0] !== "all") {
    departmentId = selectedDepartments[0];
  }

  //----------------------------------------
  // Work Mode
  //----------------------------------------

  const workMode = document.querySelector(".workmode-radio:checked");

  if (!workMode) {
    alert("Please select a work mode.");

    return;
  }

  //----------------------------------------
  // Tasks
  //----------------------------------------

  const taskIds = [...document.querySelectorAll(".task-checkbox:checked")].map(
    (cb) => cb.value,
  );

  if (taskIds.length === 0) {
    alert("Please select at least one task.");

    return;
  }

  //----------------------------------------
  // Build URL
  //----------------------------------------

  const params = new URLSearchParams();

  params.append("start_date", startDate);

  params.append("end_date", endDate);

  params.append("work_mode_id", workMode.value);

  params.append("task_ids", taskIds.join(","));

  if (employeeId !== "") {
    params.append("user_id", employeeId);
  }

  if (departmentId !== "") {
    params.append("department_id", departmentId);
  }

  //----------------------------------------
  // Download
  //----------------------------------------

  window.location =
    "../backend/client/export_aht_excel.php?" + params.toString();
}
