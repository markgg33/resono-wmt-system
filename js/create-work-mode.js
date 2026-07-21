let billingCategoriesCache = [];

// ========== CRUD FUNCTION FOR WORK MODE & TASK DESCRIPTIONS ==========
document.addEventListener("DOMContentLoaded", () => {
  loadWorkModes();
  loadBillingCategories();

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

  // === Toggle Editor Visibility ===
  toggleBtn?.addEventListener("click", () => {
    editorVisible = !editorVisible;
    toggleBtn.textContent = editorVisible ? "Hide Editor" : "Show Editor";
    nameContainer.classList.toggle("d-none", !editorVisible);
    nameInput.disabled = !editorVisible;
    saveBtn.disabled = !editorVisible;
    document
      .querySelectorAll(
        "#editDescriptionsContainer input, #editDescriptionsContainer button",
      )
      .forEach((el) => (el.disabled = !editorVisible));
  });

  // BUTTON TASK DESCRIPTION REMOVAL AND BILLING CATEGORY
  document.addEventListener("click", function (e) {
    if (e.target.closest(".remove-task-btn")) {
      e.target.closest(".task-desc-group").remove();
    }
  });

  document.addEventListener("click", function (e) {
    if (e.target.closest(".remove-billing-input")) {
      e.target.closest(".billing-input-group").remove();
    }
  });

  // === Add Billing Category NEW FUNCTION ===
  const addBillingBtn = document.getElementById("addBillingCategoryBtn");

  addBillingBtn?.addEventListener("click", () => {
    const inputs = document.querySelectorAll(".billing-category-new");

    let categories = [];

    inputs.forEach((input) => {
      const name = input.value.trim();

      if (name !== "") {
        categories.push(name);
      }
    });

    if (categories.length === 0) {
      alert("Enter at least one billing category.");
      return;
    }

    if (!confirm("Add these billing categories?")) {
      return;
    }

    fetch("../backend/add_billing_category.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ categories }),
    })
      .then((res) => res.json())
      .then((data) => {
        loadBillingCategories();
        //populateBillingDropdown(group.querySelector(".billing-category"));

        document.getElementById("billingInputs").innerHTML = `
  <div class="input-group mb-2 billing-input-group">

    <input type="text" class="form-control billing-category-new" placeholder="Billing Category">

    <button class="btn btn-danger remove-billing-input">
      <i class="fa fa-trash"></i>
    </button>

  </div>
  `;

        if (data.duplicates?.length > 0) {
          alert("Duplicates skipped:\n" + data.duplicates.join("\n"));
        }

        showSuccess("Billing categories added.");
      });
  });

  const addBillingInputBtn = document.getElementById("addBillingInput");
  const billingInputs = document.getElementById("billingInputs");

  addBillingInputBtn?.addEventListener("click", () => {
    const row = document.createElement("div");

    row.className = "input-group mb-2 billing-input-group";

    row.innerHTML = `
    <input type="text" class="form-control billing-category-new" placeholder="Billing Category">

    <button class="btn btn-danger remove-billing-input">
      <i class="fa fa-trash"></i>
    </button>
  `;

    billingInputs.appendChild(row);
  });

  // === Add Dynamic Task Input ===
  if (addTaskBtn && taskInputsContainer) {
    addTaskBtn.addEventListener("click", () => {
      const group = document.createElement("div");
      group.className = "mb-3 task-desc-group d-flex gap-2";
      /*group.innerHTML = `
<select class="form-select billing-category" name="billing_category_id[]"></select>
<input type="text" class="form-control" name="task_description[]" required placeholder="Additional task...">
<button type="button" class="btn btn-danger btn-sm remove-task-btn">
<i class="fa fa-trash"></i>
</button>
`;*/

      group.innerHTML = `
<select class="form-select billing-category"
name="billing_category_id[]">
</select>

<input type="text"
class="form-control"
name="task_description[]"
placeholder="Additional Task">

<input type="number"
class="form-control"
name="standard_aht[]"
step="0.01"
min="0"
placeholder="Standard AHT">

<button type="button"
class="btn btn-danger btn-sm remove-task-btn">
<i class="fa fa-trash"></i>
</button>
`;

      loadBillingCategories();
      taskInputsContainer.appendChild(group);
      group
        .querySelector(".remove-task-btn")
        .addEventListener("click", () => group.remove());
    });
  }

  // For loading the billing categories

  function loadBillingCategories() {
    fetch("../backend/get_billing_categories.php")
      .then((res) => res.json())
      .then((categories) => {
        billingCategoriesCache = categories; // ✅ CACHE IT
        /* ---------- Populate Dropdowns ---------- */
        document.querySelectorAll(".billing-category").forEach((select) => {
          select.innerHTML = `<option value="">Billing Category</option>`;
          categories.forEach((cat) => {
            if (cat.is_active != 1) return;

            const opt = document.createElement("option");

            opt.value = cat.id;
            opt.textContent = cat.category_name;

            select.appendChild(opt);
          });
        });

        /* ---------- Populate Category List ---------- */
        const tableBody = document.querySelector("#billingCategoryTable tbody");

        if (!tableBody) return;

        tableBody.innerHTML = "";

        categories.forEach((cat) => {
          const row = document.createElement("tr");

          row.innerHTML = `

<td>
<input type="text" class="form-control billing-edit"
value="${cat.category_name}"
data-id="${cat.id}"
disabled>
</td>

<td class="align-middle">

<div class="form-check form-switch m-0">

<input class="form-check-input toggle-billing-status"
type="checkbox"
data-id="${cat.id}"
${cat.is_active == 1 ? "checked" : ""}>

<label class="form-check-label">
${cat.is_active == 1 ? "Active" : "Inactive"}
</label>

</div>

</td>

<td>

<div class="d-flex gap-2 align-items-center">

<button class="btn btn-outline-secondary toggle-billing">
<i class="fa fa-eye"></i>
</button>

<button class="btn btn-outline-primary save-billing d-none">
Save
</button>

<button class="btn btn-outline-danger delete-billing d-none">
Delete
</button>

</div>

</td>

`;

          tableBody.appendChild(row);
        });
      });
  }

  document.addEventListener("click", function (e) {
    if (e.target.closest(".toggle-billing")) {
      const row = e.target.closest("tr");

      const input = row.querySelector(".billing-edit");
      const save = row.querySelector(".save-billing");
      const del = row.querySelector(".delete-billing");

      const editing = !input.disabled;

      input.disabled = editing;

      save.classList.toggle("d-none", editing);
      del.classList.toggle("d-none", editing);
    }
  });

  // === Save Billing Category ===
  document.addEventListener("click", function (e) {
    if (e.target.closest(".save-billing")) {
      const row = e.target.closest("tr");

      const input = row.querySelector(".billing-edit");

      const id = input.dataset.id;
      const name = input.value.trim();

      if (!confirm("Save billing category change?")) {
        return;
      }

      fetch("../backend/update_billing_category.php", {
        method: "POST",

        body: new URLSearchParams({
          id: id,
          name: name,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showSuccess("Billing category updated.");
            loadBillingCategories();
          }
        });
    }
  });

  // === Delete Billing Category ===
  document.addEventListener("click", function (e) {
    if (e.target.closest(".delete-billing")) {
      const row = e.target.closest("tr");
      const id = row.querySelector(".billing-edit").dataset.id;

      if (!confirm("Delete this billing category?")) {
      }

      fetch("../backend/delete_billing_category.php?id=" + id, {
        method: "POST",
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showSuccess("Billing category deleted.");
            loadBillingCategories();
          }
        });
    }
  });

  // === Toggle Billing Category Status ===
  document.addEventListener("change", function (e) {
    if (e.target.classList.contains("toggle-billing-status")) {
      const checkbox = e.target;
      const id = checkbox.dataset.id;
      const newState = checkbox.checked ? 1 : 0;

      const label = checkbox
        .closest(".form-check")
        .querySelector(".form-check-label");

      fetch("../backend/toggle_billing_category_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          id: id,
          is_active: newState,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            alert("Failed to update status.");
            checkbox.checked = !checkbox.checked;
            return;
          }

          label.textContent = newState ? "Active" : "Inactive";

          showSuccess(
            `Billing category ${newState ? "activated" : "deactivated"}.`,
          );

          loadBillingCategories();
        });
    }
  });

  // === Add Work Mode ===
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
          if (data.duplicate) return alert("Work mode already exists!");
          if (data.success) {
            this.reset();
            loadWorkModes();
            showSuccess("Work Mode added successfully.");
          }
        });
    });
  }

  // === Add Task Description(s) ===
  if (taskForm) {
    taskForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const modeId = document.getElementById("work_mode_id").value;
      /*const tasks = Array.from(
        document.querySelectorAll("input[name='task_description[]']"),
      )
        .map((el) => el.value.trim())
        .filter((val) => val !== "");*/
      const descriptions = document.querySelectorAll(
        "input[name='task_description[]']",
      );
      const categories = document.querySelectorAll(
        "select[name='billing_category_id[]']",
      );
      //FOR AHT
      const standardAhts = document.querySelectorAll(
        "input[name='standard_aht[]']",
      );

      let tasks = [];

      descriptions.forEach((desc, index) => {
        const text = desc.value.trim();

        if (text !== "") {
          tasks.push({
            description: text,
            billing_category_id: categories[index].value || null,

            standard_aht: parseFloat(standardAhts[index].value || 0),
          });
        }
      });
      if (!modeId || tasks.length === 0) return;

      fetch("../backend/add_task_description.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ work_mode_id: modeId, tasks }),
      })
        .then((res) => res.json())
        .then((data) => {
          this.reset();
          //taskInputsContainer.innerHTML = `<div class="mb-3 task-desc-group"><input type="text" class="form-control" name="task_description[]" required></div>`;
          taskInputsContainer.innerHTML = `
<div class="d-flex gap-2 task-desc-group">

<select class="form-select billing-category" name="billing_category_id[]">
<option value="">Billing Category</option>
</select>

<input type="text" class="form-control" name="task_description[]" required placeholder="Task Description">

<button type="button" class="btn btn-danger btn-sm remove-task-btn">
<i class="fa fa-trash"></i>
</button>

</div>
`;
          if (data.duplicates?.length > 0) {
            alert(
              "Some duplicates were skipped:\n" + data.duplicates.join("\n"),
            );
          } else {
            showSuccess("Task Descriptions added.");
          }
        });
    });
  }

  // === Handle Edit Work Mode & Task Descriptions ===
  if (editSelect) {
    /*editSelect.addEventListener("change", function () {
      const modeId = this.value;
      currentEditModeId = modeId;
      descContainer.innerHTML = ""; // Clear previous
      if (!modeId) return;*/

    editSelect.addEventListener("change", function () {
      const modeId = this.value;

      const saveOrderBtn = document.getElementById("saveOrderBtn");

      // ✅ Enable if selected, disable if not
      if (modeId) {
        saveOrderBtn.disabled = false;
      } else {
        saveOrderBtn.disabled = true;
      }

      currentEditModeId = modeId;
      descContainer.innerHTML = "";

      if (!modeId) return;

      // --- Work Mode Name + Delete Button + Status Switch --- test version
      fetch(`../backend/get_work_modes.php?id=${modeId}`)
        .then((res) => res.json())
        .then((data) => {
          const wmGroup = document.createElement("div");
          //wmGroup.className = "input-group align-items-center mb-3";
          wmGroup.className = "d-flex align-items-center gap-1 mb-3";
          /*wmGroup.innerHTML = `
      <input type="text" class="form-control" id="edit_work_mode_field" disabled>
      <button class="btn btn-outline-primary d-none" id="saveWorkModeNameBtnDynamic">Save</button>
      <button class="btn btn-outline-danger d-none" id="deleteWorkModeBtn"><i class="fa fa-trash"></i></button>
      <button class="btn btn-outline-secondary toggle-edit" title="Edit Work Mode"><i class="fa fa-eye"></i></button>
      <div class="form-check form-switch ms-3">
        <input class="form-check-input bg-success border-success" type="checkbox" id="workModeStatusToggle" ${
          data.is_active == 1 ? "checked" : ""
        }>
        <label class="form-check-label" for="workModeStatusToggle">${
          data.is_active == 1 ? "Active" : "Inactive"
        }</label>
      </div>
    `;*/
          wmGroup.innerHTML = `
      <input type="text" class="form-control flex-grow-1" id="edit_work_mode_field" disabled>
      <button class="btn btn-outline-primary d-none" id="saveWorkModeNameBtnDynamic">Save</button>
      <button class="btn btn-outline-danger d-none" id="deleteWorkModeBtn">Delete</button>
      <button class="btn btn-outline-secondary toggle-edit" title="Edit Work Mode"><i class="fa fa-eye"></i></button>
      <div class="form-check form-switch ms-3">
        <input class="form-check-input bg-success border-success" type="checkbox" id="workModeStatusToggle" ${
          data.is_active == 1 ? "checked" : ""
        }>
        <label class="form-check-label" for="workModeStatusToggle">${
          data.is_active == 1 ? "Active" : "Inactive"
        }</label>
      </div>
    `;
          descContainer.prepend(wmGroup);

          const wmInput = wmGroup.querySelector("#edit_work_mode_field");
          const wmSave = wmGroup.querySelector("#saveWorkModeNameBtnDynamic");
          const wmToggle = wmGroup.querySelector(".toggle-edit");
          const wmDelete = wmGroup.querySelector("#deleteWorkModeBtn");
          const statusToggle = wmGroup.querySelector("#workModeStatusToggle");
          const statusLabel = wmGroup.querySelector(".form-check-label");

          wmInput.value = data.name || "";

          // === Status Toggle ===
          statusToggle.addEventListener("change", async () => {
            const newState = statusToggle.checked ? 1 : 0;
            try {
              const res = await fetch(
                "../backend/toggle_work_mode_status.php",
                {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ id: modeId, is_active: newState }),
                },
              );
              const json = await res.json();
              if (!json.success) throw new Error();
              statusLabel.textContent = newState ? "Active" : "Inactive";
              showSuccess(
                `Work Mode marked as ${newState ? "Active" : "Inactive"}.`,
              );
              loadWorkModes();
            } catch {
              alert("Failed to update status.");
              statusToggle.checked = !statusToggle.checked;
            }
          });

          // === Edit / Save / Delete Logic ===
          wmToggle.onclick = () => {
            const editing = !wmInput.disabled;
            wmInput.disabled = editing;
            wmSave.classList.toggle("d-none", editing);
            wmDelete.classList.toggle("d-none", editing);
            wmToggle.innerHTML = `<i class="fa fa-eye${
              editing ? "" : "-slash"
            }"></i>`;
          };

          wmSave.onclick = () => {
            const newName = wmInput.value.trim();
            if (!newName) return alert("Work mode name cannot be empty.");
            fetch("../backend/update_work_mode.php", {
              method: "POST",
              body: new URLSearchParams({ id: modeId, name: newName }),
            })
              .then((res) => res.json())
              .then((res) => {
                if (res.duplicate) alert("Work mode name already exists.");
                else if (res.success) {
                  showSuccess("Work Mode name updated.");
                  loadWorkModes();
                } else alert("Error updating work mode.");
              });
          };

          wmDelete.onclick = () => {
            if (!confirm("Delete this work mode and all its tasks?")) return;
            fetch("../backend/delete_work_mode.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `id=${encodeURIComponent(modeId)}`,
            })
              .then((res) => res.json())
              .then((res) => {
                if (res.success) {
                  showSuccess("Work Mode deleted with all its tasks.");
                  loadWorkModes();
                  editSelect.value = "";
                  descContainer.innerHTML = "";
                } else alert("Failed to delete work mode.");
              });
          };
        });

      // --- Task Descriptions + Status Toggles ---
      fetch(`../backend/get_task_descriptions.php?work_mode_id=${modeId}&all=1`)
        .then((res) => res.json())
        .then((tasks) => {
          if (!Array.isArray(tasks) || tasks.length === 0)
            return (descContainer.innerHTML +=
              "<p class='text-muted'>No tasks found.</p>");

          tasks.forEach((task) => {
            const group = document.createElement("div");
            //group.className = "input-group align-items-center mb-2";
            group.className = "d-flex align-items-center gap-2 mb-2";
            /*group.innerHTML = `
        <select class="form-select billing-category-edit" data-task="${task.id}" disabled></select>
        <input type="text" class="form-control" value="${task.description}" disabled>
        <button class="btn btn-outline-primary d-none">Save</button>
        <button class="btn btn-outline-danger d-none">Delete</button>
        <button class="btn btn-outline-secondary toggle-edit" title="Edit Task">
          <i class="fa fa-eye"></i>
        </button>
        <div class="form-check form-switch ms-3">
          <input class="form-check-input bg-success border-success toggle-task-status" 
            type="checkbox" data-id="${task.id}" ${
              task.is_active == 1 ? "checked" : ""
            }>
          <label class="form-check-label">${
            task.is_active == 1 ? "Active" : "Inactive"
          }</label>
        </div>
      `;*/

            //NEW VERSION WITH DELETE BUTTON REMOVED AND BILLING CATEGORY ADDED
            /*group.innerHTML = `

<input type="text"
class="form-control flex-grow-1"
value="${task.description}"
disabled>

<select class="form-select billing-category-edit"
data-task="${task.id}" disabled
style="max-width:220px"></select>

<button class="btn btn-outline-primary d-none">Save</button>

<button class="btn btn-outline-danger d-none">
Delete
</button>

<button class="btn btn-outline-secondary toggle-edit">
<i class="fa fa-eye"></i>
</button>

<div class="form-check form-switch ms-2">

<input class="form-check-input bg-success toggle-task-status"
type="checkbox"
data-id="${task.id}"
${task.is_active == 1 ? "checked" : ""}>

<label class="form-check-label">
${task.is_active == 1 ? "Active" : "Inactive"}
</label>

</div>

`;*/

            group.innerHTML = `
<div class="drag-handle" style="cursor:grab;">☰</div>

<input type="text" class="form-control flex-grow-1" value="${task.description}" disabled>

<select class="form-select billing-category-edit"
data-task="${task.id}" disabled style="max-width:220px"></select>

<input type="number"
class="form-control standard-aht-edit"
value="${task.standard_aht ?? ""}"
step="0.01"
disabled
style="max-width:120px">

<button class="btn btn-outline-primary d-none">Save</button>

<button class="btn btn-outline-secondary toggle-edit">
<i class="fa fa-eye"></i>
</button>

<div class="form-check form-switch ms-2">
<input class="form-check-input bg-success toggle-task-status"
type="checkbox"
data-id="${task.id}"
${task.is_active == 1 ? "checked" : ""}>
<label class="form-check-label">
${task.is_active == 1 ? "Active" : "Inactive"}
</label>
</div>
`;

            populateBillingEditDropdown(group, task.billing_category_id);

            const toggle = group.querySelector(".toggle-edit");
            const input = group.querySelector("input.form-control");
            const saveBtn = group.querySelector(".btn-outline-primary");
            //const deleteBtn = group.querySelector(".btn-outline-danger");
            const statusSwitch = group.querySelector(".toggle-task-status");
            const statusLabel = group.querySelector(".form-check-label");
            //New variable
            const billingSelect = group.querySelector(".billing-category-edit");
            const standardAhtInput = group.querySelector(".standard-aht-edit");

            // === Task Status Toggle ===
            statusSwitch.addEventListener("change", async () => {
              const newState = statusSwitch.checked ? 1 : 0;
              try {
                const res = await fetch(
                  "../backend/toggle_task_description_status.php",
                  {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                      id: statusSwitch.dataset.id,
                      is_active: newState,
                    }),
                  },
                );
                const json = await res.json();
                if (!json.success) throw new Error();
                statusLabel.textContent = newState ? "Active" : "Inactive";
                showSuccess(
                  `Task marked as ${newState ? "Active" : "Inactive"}.`,
                );
              } catch {
                alert("Failed to update task status.");
                statusSwitch.checked = !statusSwitch.checked;
              }
            });

            // === Edit / Save / Delete Logic ===
            toggle.addEventListener("click", () => {
              const editing = !input.disabled;

              input.disabled = editing;
              billingSelect.disabled = editing;
              standardAhtInput.disabled = editing;

              saveBtn.classList.toggle("d-none", editing);
              deleteBtn.classList.toggle("d-none", editing);

              toggle.innerHTML = `<i class="fa fa-eye${editing ? "" : "-slash"}"></i>`;
            });

            saveBtn.addEventListener("click", async () => {
              const newDesc = input.value.trim();
              if (!newDesc) return alert("Task description cannot be empty.");
              const res = await fetch(
                "../backend/update_task_description.php",
                {
                  method: "POST",
                  /*body: new URLSearchParams({
                    id: task.id,
                    description: newDesc,
                  }),*/

                  body: new URLSearchParams({
                    id: task.id,
                    description: newDesc,
                    billing_category_id: billingSelect.value,
                    standard_aht: standardAhtInput.value,
                  }),
                },
              );
              const json = await res.json();
              if (json.success) showSuccess("Task updated.");
              else alert("Failed to update task.");
            });

            // Working delete button
            /*deleteBtn.addEventListener("click", async () => {
              if (!confirm("Delete this task?")) return;
              const res = await fetch(
                `../backend/delete_task_description.php?id=${task.id}`,
                {
                  method: "POST",
                },
              );
              const json = await res.json();
              if (json.success) {
                group.remove();
                showSuccess("Task deleted.");
              } else alert("Failed to delete task.");
            });*/

            descContainer.appendChild(group);
          });

          makeTasksSortable();
        });
    });
  }

  function saveTaskOrder() {
    const rows = document.querySelectorAll("#editDescriptionsContainer > div");

    let orderData = [];

    rows.forEach((row, index) => {
      const taskId = row.querySelector(".billing-category-edit")?.dataset.task;
      if (taskId) {
        orderData.push({
          id: taskId,
          order: index + 1,
        });
      }
    });

    fetch("../backend/update_task_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ tasks: orderData }),
    });
  }

  function makeTasksSortable() {
    const container = document.getElementById("editDescriptionsContainer");

    new Sortable(container, {
      animation: 150,
      handle: ".drag-handle", // optional
      onEnd: function () {
        console.log("Order changed. Click Save to apply.");
      },
    });
  }

  document.getElementById("saveOrderBtn")?.addEventListener("click", () => {
    if (!confirm("Save new task order?")) return;

    saveTaskOrder();
    showSuccess("Task order updated.");
  });

  // === Save Work Mode Name (legacy form) ===
  saveBtn?.addEventListener("click", () => {
    const newName = nameInput.value;
    fetch("../backend/update_work_mode.php", {
      method: "POST",
      body: new URLSearchParams({ id: currentEditModeId, name: newName }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.duplicate) alert("Work mode name already exists.");
        else {
          showSuccess("Work Mode name updated.");
          loadWorkModes();
        }
      });
  });
});

// ======================= SHARED FUNCTIONS FOR MY TRACKER ==========================
function loadWorkModes() {
  fetch("../backend/get_work_modes.php?all=1")
    .then((r) => r.json())
    .then((modes) => {
      const selects = [
        document.getElementById("work_mode_id"),
        document.getElementById("edit_work_mode"),
      ];
      selects.forEach((sel) => {
        if (!sel) return;
        sel.innerHTML = `<option value="">-- Choose Work Mode --</option>`;
        modes.forEach((m) => {
          const opt = document.createElement("option");
          opt.value = m.id;
          opt.textContent = m.is_active == 1 ? m.name : `${m.name} (inactive)`;
          sel.appendChild(opt);
        });
      });
    });
}

function updateTaskOptions() {
  const modeId = document.getElementById("workModeSelector")?.value;
  const taskSelect = document.getElementById("taskSelector");
  if (!taskSelect) return;
  if (!modeId)
    return (taskSelect.innerHTML = `<option value="">-- Select Task --</option>`);
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

// === Success Modal ===
function showSuccess(message) {
  const modalMsg = document.getElementById("successMessage");
  if (modalMsg) modalMsg.textContent = message;
  new bootstrap.Modal(document.getElementById("successModal")).show();
}

//HELPER FOR DISPLAYING BILLING CATEGORY IN EDIT SECTION

/*function populateBillingEditDropdown(container, selectedId) {
  fetch("../backend/get_billing_categories.php")
    .then((res) => res.json())

    .then((categories) => {
      const select = container.querySelector(".billing-category-edit");

      select.innerHTML = `<option value="">Billing Category</option>`;

      categories.forEach((cat) => {
        // Hide inactive unless it is currently selected
        if (cat.is_active != 1 && cat.id != selectedId) return;

        const opt = document.createElement("option");

        opt.value = cat.id;

        opt.textContent =
          cat.category_name + (cat.is_active != 1 ? " (inactive)" : "");

        if (cat.id == selectedId) {
          opt.selected = true;
        }

        select.appendChild(opt);
      });
    });
}*/

function populateBillingEditDropdown(container, selectedId) {
  const select = container.querySelector(".billing-category-edit");

  select.innerHTML = `<option value="">Billing Category</option>`;

  billingCategoriesCache.forEach((cat) => {
    // Hide inactive unless selected
    if (cat.is_active != 1 && cat.id != selectedId) return;

    const opt = document.createElement("option");
    opt.value = cat.id;

    opt.textContent =
      cat.category_name + (cat.is_active != 1 ? " (inactive)" : "");

    if (cat.id == selectedId) {
      opt.selected = true;
    }

    select.appendChild(opt);
  });
}

function populateBillingDropdown(select) {
  select.innerHTML = `<option value="">Billing Category</option>`;

  billingCategoriesCache.forEach((cat) => {
    if (cat.is_active != 1) return;

    const opt = document.createElement("option");
    opt.value = cat.id;
    opt.textContent = cat.category_name;

    select.appendChild(opt);
  });
}
