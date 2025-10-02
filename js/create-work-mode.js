// ========== CRUD FUNCTION FOR WORK MODE & TASK DESCRIPTIONS ==========
document.addEventListener("DOMContentLoaded", () => {
  loadWorkModes();

  const addTaskBtn = document.getElementById("addMoreTask");
  const taskInputsContainer = document.getElementById("taskInputs");
  const workModeForm = document.getElementById("addWorkModeForm");
  const taskForm = document.getElementById("addTaskDescriptionForm");
  const editSelect = document.getElementById("edit_work_mode");
  const toggleBtn = document.getElementById("toggleEditModeBtn");
  const nameInput = document.getElementById("edit_work_mode_name");
  const saveBtn = document.getElementById("saveWorkModeNameBtn");
  const nameContainer = document.getElementById("editWorkModeNameContainer");
  const descContainer = document.getElementById("editDescriptionsContainer");

  const trackerSelect = document.getElementById("workModeSelector"); // 📌 For My Tracker

  let currentEditModeId = null;
  let editorVisible = false;

  // === Tracker view: update task list when work mode changes ===
  trackerSelect?.addEventListener("change", updateTaskOptions);

  // Toggle Editor Visibility
  toggleBtn?.addEventListener("click", () => {
    editorVisible = !editorVisible;
    toggleBtn.textContent = editorVisible ? "Hide Editor" : "Show Editor";
    nameContainer.classList.toggle("d-none", !editorVisible);
    nameInput.disabled = !editorVisible;
    saveBtn.disabled = !editorVisible;
    document
      .querySelectorAll(
        "#editDescriptionsContainer input, #editDescriptionsContainer button"
      )
      .forEach((el) => {
        el.disabled = !editorVisible;
      });
  });

  // Add Dynamic Task Input
  if (addTaskBtn && taskInputsContainer) {
    addTaskBtn.addEventListener("click", () => {
      const group = document.createElement("div");
      group.className = "mb-3 task-desc-group d-flex gap-2";
      group.innerHTML = `
          <input type="text" class="form-control" name="task_description[]" required placeholder="e.g. Additional task...">
          <button type="button" class="btn btn-danger btn-sm remove-task-btn"><i class="fa fa-trash"></i></button>
        `;
      taskInputsContainer.appendChild(group);
      group
        .querySelector(".remove-task-btn")
        .addEventListener("click", () => group.remove());
    });
  }

  // Add Work Mode
  if (workModeForm) {
    workModeForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const name = document.getElementById("work_mode_name").value;
      fetch("../backend/add_work_mode.php", {
        method: "POST",
        body: new URLSearchParams({ work_mode_name: name }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.duplicate) {
            alert("Work mode already exists!");
            return;
          }
          if (data.success) {
            this.reset();
            loadWorkModes();
            showSuccess("Work Mode added successfully.");
          }
        });
    });
  }

  // Add Task Description(s)
  if (taskForm) {
    taskForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const modeId = document.getElementById("work_mode_id").value;
      const tasks = Array.from(
        document.querySelectorAll("input[name='task_description[]']")
      )
        .map((el) => el.value.trim())
        .filter((val) => val !== "");
      if (!modeId || tasks.length === 0) return;

      fetch("../backend/add_task_description.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ work_mode_id: modeId, tasks }),
      })
        .then((res) => res.json())
        .then((data) => {
          this.reset();
          taskInputsContainer.innerHTML = `<div class="mb-3 task-desc-group"><input type="text" class="form-control" name="task_description[]" required></div>`;
          if (data.duplicates?.length > 0) {
            alert(
              "Some duplicates were skipped:\n" + data.duplicates.join("\n")
            );
          } else {
            showSuccess("Task Descriptions added.");
          }
        });
    });
  }

  // Handle Edit Work Mode & Task Descriptions
  if (editSelect) {
    editSelect.addEventListener("change", function () {
      const modeId = this.value;
      currentEditModeId = modeId;
      descContainer.innerHTML = ""; // Clear previous

      if (!modeId) return;

      // --- Work Mode Name + Delete Button ---
      fetch(`../backend/get_work_modes.php?id=${modeId}`)
        .then((res) => res.json())
        .then((data) => {
          // Clear previous editor if exists
          const existing = descContainer
            .querySelector("#edit_work_mode_field")
            ?.closest(".input-group");
          if (existing) existing.remove();

          const wmGroup = document.createElement("div");
          wmGroup.className = "input-group align-items-center mb-3";
          wmGroup.innerHTML = `
      <input type="text" class="form-control" id="edit_work_mode_field" disabled>
      <button class="btn btn-outline-primary d-none" id="saveWorkModeNameBtnDynamic">Save</button>
      <button class="btn btn-outline-danger d-none" id="deleteWorkModeBtn" title="Delete Work Mode">
        <i class="fa fa-trash"></i>
      </button>
      <button class="btn btn-outline-secondary toggle-edit" title="Edit Work Mode">
        <i class="fa fa-eye"></i>
      </button>
    `;
          descContainer.prepend(wmGroup); // Insert at top

          const wmInput = wmGroup.querySelector("#edit_work_mode_field");
          const wmSave = wmGroup.querySelector("#saveWorkModeNameBtnDynamic");
          const wmToggle = wmGroup.querySelector(".toggle-edit");
          const wmDelete = wmGroup.querySelector("#deleteWorkModeBtn");

          // ✅ Set value AFTER element is created
          wmInput.value = data.name || "";

          // Toggle logic
          wmToggle.onclick = () => {
            const editing = !wmInput.disabled;
            wmInput.disabled = editing;
            wmSave.classList.toggle("d-none", editing);
            wmDelete.classList.toggle("d-none", editing); // 👈 show/hide delete
            wmToggle.innerHTML = `<i class="fa fa-eye${
              editing ? "" : "-slash"
            }"></i>`;
          };

          // Save logic
          wmSave.onclick = () => {
            const newName = wmInput.value.trim();
            if (!newName) {
              alert("Work mode name cannot be empty.");
              return;
            }

            fetch("../backend/update_work_mode.php", {
              method: "POST",
              body: new URLSearchParams({ id: modeId, name: newName }),
            })
              .then((res) => res.json())
              .then((res) => {
                if (res.duplicate) {
                  alert("Work mode name already exists.");
                } else if (res.success) {
                  showSuccess("Work Mode name updated.");
                  loadWorkModes();
                } else {
                  alert("An error occurred while updating.");
                }
              });
          };

          // 🗑️ Delete Work Mode logic
          wmDelete.onclick = () => {
            if (!confirm("Delete this work mode and all its tasks?")) return;
            fetch("../backend/delete_work_mode.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `id=${encodeURIComponent(modeId)}`, // 👈 send as POST
            })
              .then((res) => res.json())
              .then((res) => {
                if (res.success) {
                  showSuccess("Work Mode deleted with all its tasks.");
                  loadWorkModes();
                  editSelect.value = "";
                  descContainer.innerHTML = "";
                } else {
                  alert("Failed to delete work mode.");
                }
              });
          };
        });

      // --- Task Descriptions ---
      fetch(`../backend/get_task_descriptions.php?work_mode_id=${modeId}`)
        .then((res) => res.json())
        .then((tasks) => {
          if (!Array.isArray(tasks) || tasks.length === 0) {
            descContainer.innerHTML +=
              "<p class='text-muted'>No tasks found.</p>";
            return;
          }

          tasks.forEach((task) => {
            const group = document.createElement("div");
            group.className = "input-group align-items-center mb-2";

            group.innerHTML = `
              <input type="text" class="form-control" value="${task.description}" disabled>
              <button class="btn btn-outline-primary d-none">Save</button>
              <button class="btn btn-outline-danger d-none">Delete</button>
              <button class="btn btn-outline-secondary toggle-edit" title="Edit Task">
                <i class="fa fa-eye"></i>
              </button>
            `;

            const toggle = group.querySelector(".toggle-edit");
            const input = group.querySelector("input");
            const saveBtn = group.querySelector(".btn-outline-primary");
            const deleteBtn = group.querySelector(".btn-outline-danger");

            toggle.addEventListener("click", () => {
              const editing = !input.disabled;
              input.disabled = editing;
              saveBtn.classList.toggle("d-none", editing);
              deleteBtn.classList.toggle("d-none", editing);
              toggle.innerHTML = `<i class="fa fa-eye${
                editing ? "" : "-slash"
              }"></i>`;
            });

            saveBtn.addEventListener("click", () => {
              fetch("../backend/update_task_description.php", {
                method: "POST",
                body: new URLSearchParams({
                  id: task.id,
                  description: input.value,
                }),
              }).then(() => showSuccess("Task updated."));
            });

            deleteBtn.addEventListener("click", () => {
              if (!confirm("Delete this task?")) return;
              fetch(`../backend/delete_task_description.php?id=${task.id}`, {
                method: "POST",
              }).then(() => {
                group.remove();
                showSuccess("Task deleted.");
              });
            });

            descContainer.appendChild(group);
          });
        });
    });
  }

  // Save Work Mode Name (old UI)
  saveBtn?.addEventListener("click", () => {
    const newName = nameInput.value;
    fetch("../backend/update_work_mode.php", {
      method: "POST",
      body: new URLSearchParams({ id: currentEditModeId, name: newName }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.duplicate) {
          alert("Work mode name already exists.");
        } else {
          showSuccess("Work Mode name updated.");
          loadWorkModes();
        }
      });
  });
});

// ======================= SHARED FUNCTIONS ==========================

// Populate all dropdowns
function loadWorkModes() {
  fetch("../backend/get_work_modes.php")
    .then((r) => r.json())
    .then((modes) => {
      const selects = [
        document.getElementById("work_mode_id"),
        document.getElementById("edit_work_mode"),
        document.getElementById("workModeSelector"),
      ];
      selects.forEach((sel) => {
        if (!sel) return;
        sel.innerHTML = `<option value="">-- Choose Work Mode --</option>`;
        modes.forEach((m) => {
          const opt = document.createElement("option");
          opt.value = m.id;
          opt.textContent = m.name;
          sel.appendChild(opt);
        });
      });
    });
}

// For My Tracker: update task selector based on selected work mode
function updateTaskOptions() {
  const modeId = document.getElementById("workModeSelector")?.value;
  const taskSelect = document.getElementById("taskSelector");
  if (!taskSelect) return;

  if (!modeId) {
    taskSelect.innerHTML = `<option value="">-- Select Task --</option>`;
    return;
  }

  fetch(`../backend/get_task_descriptions.php?work_mode_id=${modeId}`)
    .then((res) => res.json())
    .then((tasks) => {
      taskSelect.innerHTML = `<option value="">-- Select Task --</option>`;
      tasks.forEach((task) => {
        const opt = document.createElement("option");
        opt.value = task.id;
        opt.textContent = task.description;
        taskSelect.appendChild(opt);
      });
    });
}

// Show success modal (Bootstrap)
function showSuccess(message) {
  const modalMsg = document.getElementById("successMessage");
  if (modalMsg) modalMsg.textContent = message;
  new bootstrap.Modal(document.getElementById("successModal")).show();
}
