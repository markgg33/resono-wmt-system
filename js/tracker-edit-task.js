// Open modal with current values
function openEditTaskLogModal(logId, currentWorkModeId, currentDescriptionId) {
  document.getElementById("editLogId").value = logId;

  // Load work modes
  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((workModes) => {
      const workModeSelect = document.getElementById("editWorkMode");
      workModeSelect.innerHTML = '<option value="">Select Work Mode</option>';

      workModes.forEach((wm) => {
        let opt = document.createElement("option");
        opt.value = wm.id;
        opt.textContent = wm.name;
        if (wm.id == currentWorkModeId) opt.selected = true;
        workModeSelect.appendChild(opt);
      });

      // Load tasks for current work mode
      loadTasksForWorkMode(currentWorkModeId, currentDescriptionId);
    });

  let modal = new bootstrap.Modal(document.getElementById("editTaskLogModal"));
  modal.show();
}

// Load tasks dynamically when work mode changes
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("editWorkMode")
    .addEventListener("change", function () {
      const workModeId = this.value;
      loadTasksForWorkMode(workModeId);
    });
});

function loadTasksForWorkMode(workModeId, selectedTaskId = null) {
  if (!workModeId) {
    document.getElementById("editDescription").innerHTML =
      '<option value="">Select Task</option>';
    return;
  }

  fetch(`../backend/get_tasks_by_workmode.php?work_mode_id=${workModeId}`)
    .then((res) => res.json())
    .then((tasks) => {
      const taskSelect = document.getElementById("editDescription");
      taskSelect.innerHTML = '<option value="">Select Task</option>';

      tasks.forEach((task) => {
        let opt = document.createElement("option");
        opt.value = task.id;
        opt.textContent = task.description;
        if (selectedTaskId && task.id == selectedTaskId) opt.selected = true;
        taskSelect.appendChild(opt);
      });
    });
}

// Save changes
document
  .getElementById("saveTaskLogBtn")
  .addEventListener("click", function () {
    const logId = document.getElementById("editLogId").value;
    const workModeId = document.getElementById("editWorkMode").value;
    const newDescriptionId = document.getElementById("editDescription").value;

    if (!workModeId || !newDescriptionId) {
      alert("Please select both Work Mode and Task Description!");
      return;
    }

    fetch("../backend/update_task_log_description.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        log_id: logId,
        work_mode_id: workModeId,
        task_description_id: newDescriptionId,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          alert("Task log updated!");
          location.reload(); // refresh to reflect changes
        } else {
          alert(data.message || "Failed to update.");
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        alert("Something went wrong.");
      });
  });
