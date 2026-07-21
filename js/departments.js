document.addEventListener("DOMContentLoaded", function () {
  loadDepartmentsTable();

  // Add Department
  document
    .getElementById("addDeptForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const name = document.getElementById("deptName").value.trim();
      if (!name) return;

      fetch("../backend/add_department.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name }),
      })
        .then((res) => res.json())
        .then((data) => {
          alert(data.success || data.error);
          if (data.success) {
            document.getElementById("deptName").value = "";
            loadDepartmentsTable();
          }
        });
    });

  // Load Departments
  function loadDepartmentsTable() {
    fetch("../backend/get_departments.php")
      .then((res) => res.json())
      .then((depts) => {
        const tbody = document.querySelector("#departmentsTable tbody");
        tbody.innerHTML = "";
        if (!depts || depts.length === 0) {
          tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">No departments found</td></tr>`;
          return;
        }
        depts.forEach((d) => {
          let row = `
            <tr>
              <td>${d.name}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-success me-2 editDeptBtn" data-id="${d.id}" data-name="${d.name}">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn btn-sm btn-primary me-2 assignWorkModesBtn" data-id="${d.id}" data-name="${d.name}">
                   <i class="fa-solid fa-gear"></i>
                  </button>
                <button class="btn btn-sm btn-danger deleteDeptBtn" data-id="${d.id}">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          `;
          tbody.insertAdjacentHTML("beforeend", row);
        });

        // Bind edit buttons
        document.querySelectorAll(".editDeptBtn").forEach((btn) => {
          btn.addEventListener("click", function () {
            document.getElementById("editDeptId").value = this.dataset.id;
            document.getElementById("editDeptName").value = this.dataset.name;
            new bootstrap.Modal(
              document.getElementById("editDeptModal")
            ).show();
          });
        });

        // Bind delete buttons
        document.querySelectorAll(".deleteDeptBtn").forEach((btn) => {
          btn.addEventListener("click", function () {
            if (!confirm("Are you sure you want to delete this department?"))
              return;
            fetch("../backend/delete_department.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id: this.dataset.id }),
            })
              .then((res) => res.json())
              .then((data) => {
                alert(data.success || data.error);
                if (data.success) loadDepartmentsTable();
              });
          });
        });
      });
  }

  // Open Assign Work Modes Modal
  document.addEventListener("click", async (e) => {
    if (e.target.closest(".assignWorkModesBtn")) {
      const btn = e.target.closest(".assignWorkModesBtn");
      const deptId = btn.dataset.id;
      const deptName = btn.dataset.name;

      document.getElementById(
        "assignDeptName"
      ).textContent = `Department: ${deptName}`;
      document
        .getElementById("saveWorkModesBtn")
        .setAttribute("data-id", deptId);

      // Fetch all and assigned work modes
      const res = await fetch(
        `../backend/get_department_work_modes.php?dept_id=${deptId}`
      );
      const data = await res.json();

      const container = document.getElementById("workModeList");
      container.innerHTML = "";

      data.allModes.forEach((mode) => {
        const isChecked = data.assigned.map(Number).includes(Number(mode.id));
        const col = document.createElement("div");
        col.className = "col-md-4 mb-2";
        col.innerHTML = `
    <div class="form-check">
      <input class="form-check-input" type="checkbox" value="${
        mode.id
      }" id="mode-${mode.id}" ${isChecked ? "checked" : ""}>
      <label class="form-check-label" for="mode-${mode.id}">
        ${mode.name}
      </label>
    </div>`;
        container.appendChild(col);
      });

      new bootstrap.Modal(
        document.getElementById("assignWorkModesModal")
      ).show();
    }
  });

  // Save Work Modes
  document
    .getElementById("saveWorkModesBtn")
    .addEventListener("click", async () => {
      const deptId = document
        .getElementById("saveWorkModesBtn")
        .getAttribute("data-id");
      const selectedModes = Array.from(
        document.querySelectorAll("#workModeList input:checked")
      ).map((chk) => parseInt(chk.value));

      const res = await fetch("../backend/save_department_work_modes.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          department_id: deptId,
          work_modes: selectedModes,
        }),
      });

      const data = await res.json();
      if (data.success) {
        alert("✅ Work modes updated successfully!");
        bootstrap.Modal.getInstance(
          document.getElementById("assignWorkModesModal")
        ).hide();
      } else {
        alert("❌ Failed to update work modes.");
      }
    });

  // Edit Department
  document
    .getElementById("editDeptForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const id = document.getElementById("editDeptId").value;
      const name = document.getElementById("editDeptName").value.trim();

      fetch("../backend/update_department.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id, name }),
      })
        .then((res) => res.json())
        .then((data) => {
          alert(data.success || data.error);
          if (data.success) {
            bootstrap.Modal.getInstance(
              document.getElementById("editDeptModal")
            ).hide();
            loadDepartmentsTable();
          }
        });
    });
});
