document.addEventListener("DOMContentLoaded", function () {
  loadUsers();
  loadDepartments();
  loadRoles();

  function loadUsers(departmentId = "") {
    let url = "../backend/get_all_users.php";
    if (departmentId) url += `?department_id=${departmentId}`;

    fetch(url)
      .then((res) => res.json())
      .then((users) => {
        let tbody = document.querySelector("#usersTable tbody");
        tbody.innerHTML = "";

        if (!users || users.length === 0) {
          tbody.innerHTML = `<tr>
          <td colspan="6" class="text-center text-muted">No results found</td>
        </tr>`;
          return;
        }

        users.forEach((user) => {
          let image = user.profile_image
            ? `../${user.profile_image}`
            : "../assets/default-avatar.jpg";

          let statusChecked = user.status === "active" ? "checked" : "";

          // ✅ Handle multi-department array or legacy single department
          let departmentsDisplay = "-";
          if (Array.isArray(user.departments) && user.departments.length > 0) {
            departmentsDisplay = user.departments
              .map(
                (d) =>
                  `<span class="dept-badge bg-success text-white">${d.name}</span>`
              )
              .join(" ");
          } else if (user.department_name) {
            departmentsDisplay = `<span class="dept-badge">${user.department_name}</span>`;
          }

          let row = `
          <tr>
            <td><img src="${image}" class="rounded-circle" width="50" height="50" style="object-fit:cover;"></td>
            <td>${user.first_name} ${user.middle_name || ""} ${
            user.last_name
          }</td>
            <td>${departmentsDisplay}</td>
            <td>${user.role}</td>
            <td class="text-center">
              <div class="form-check form-switch">
                <input class="form-check-input toggleStatus bg-success border-white" type="checkbox" data-id="${
                  user.id
                }" ${statusChecked}>
                <label>${user.status}</label>
              </div>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-success editUserBtn" data-id="${
                user.id
              }">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger deleteUserBtn" data-id="${
                user.id
              }">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>`;
          tbody.insertAdjacentHTML("beforeend", row);
        });

        // Re-bind events
        document.querySelectorAll(".toggleStatus").forEach((toggle) => {
          toggle.addEventListener("change", function () {
            const userId = this.dataset.id;
            const newStatus = this.checked ? "active" : "inactive";
            const confirmMsg = `Are you sure you want to set this user as ${newStatus}?`;

            if (!confirm(confirmMsg)) {
              this.checked = !this.checked;
              return;
            }

            fetch("../backend/update_user_status.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id: userId, status: newStatus }),
            })
              .then((res) => res.json())
              .then((data) => {
                if (!data.success) {
                  alert("Failed to update status");
                  this.checked = !this.checked;
                } else {
                  loadUsers(departmentId);
                }
              })
              .catch((err) => {
                console.error("Error:", err);
                this.checked = !this.checked;
              });
          });
        });

        document.querySelectorAll(".deleteUserBtn").forEach((btn) => {
          btn.addEventListener("click", function () {
            if (!confirm("Are you sure you want to delete this user?")) return;
            fetch("../backend/delete_user.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id: this.dataset.id }),
            })
              .then((res) => res.json())
              .then((data) => {
                alert(data.success || data.error);
                if (data.success) loadUsers();
              });
          });
        });

        document.querySelectorAll(".editUserBtn").forEach((btn) => {
          btn.addEventListener("click", function () {
            openEditModal(this.dataset.id);
          });
        });
      });
  }

  function loadDepartments() {
    fetch("../backend/get_departments.php")
      .then((res) => res.json())
      .then((depts) => {
        // Populate filter select
        let filterSelect = document.getElementById("adminDepartmentFilter");
        filterSelect.innerHTML = `<option value="">All Departments</option>`;

        depts.forEach((d) => {
          filterSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
        });
      });
  }

  document
    .getElementById("adminDepartmentFilter")
    .addEventListener("change", function () {
      loadUsers(this.value);
    });

  function loadRoles() {
    fetch("../backend/get_roles.php")
      .then((res) => res.json())
      .then((roles) => {
        let select = document.getElementById("admin_edit_role");
        select.innerHTML = `<option value="">-- Select Role --</option>`;
        roles.forEach((r) => {
          select.innerHTML += `<option value="${r}">${r}</option>`;
        });
      });
  }

  function openEditModal(userId) {
    fetch(`../backend/get_user_profile.php?id=${userId}`)
      .then((res) => res.json())
      .then((user) => {
        document.getElementById("admin_edit_user_id").value = userId;
        document.getElementById("admin_edit_first_name").value =
          user.first_name || "";
        document.getElementById("admin_edit_middle_name").value =
          user.middle_name || "";
        document.getElementById("admin_edit_last_name").value =
          user.last_name || "";
        document.getElementById("admin_edit_employee_id").value =
          user.employee_id || "";
        document.getElementById("admin_edit_email").value = user.email || "";
        document.getElementById("admin_edit_role").value = user.role || "";

        // ==== MULTI DEPARTMENT DROPDOWN WITH PRIMARY ====
        const departmentField = document.getElementById(
          "adminEditDepartmentField"
        );
        const departmentDropdown = document.getElementById(
          "adminEditDepartmentDropdown"
        );
        const hiddenInput = document.getElementById("admin_edit_departments");
        const dropdownBtn = departmentField.querySelector(".dropdown-toggle");

        departmentDropdown.innerHTML = "";
        hiddenInput.value = "";

        fetch("../backend/get_departments.php")
          .then((res) => res.json())
          .then((departments) => {
            departmentDropdown.innerHTML = departments
              .map(
                (d) => `
      <li class="d-flex align-items-center px-2">
        <label class="dropdown-item flex-grow-1 mb-0 px-2">
          <input type="checkbox" value="${d.id}" data-name="${d.name}" class="dept-checkbox me-2"> ${d.name}
        </label>
        <input type="radio" name="editPrimaryDept" value="${d.id}" class="dept-primary ms-4" title="Set Primary">
      </li>`
              )
              .join("");

            const userDeptIds = (user.departments || []).map((d) =>
              String(d.id)
            );
            const primaryDeptId = (user.departments || []).find(
              (d) => d.is_primary
            )?.id;

            departmentDropdown
              .querySelectorAll(".dept-checkbox")
              .forEach((checkbox) => {
                if (userDeptIds.includes(checkbox.value)) {
                  checkbox.checked = true;
                }
                checkbox.addEventListener("change", () => {
                  const radio = departmentDropdown.querySelector(
                    `.dept-primary[value="${checkbox.value}"]`
                  );
                  if (!checkbox.checked && radio.checked) {
                    radio.checked = false;
                  }
                  updateSelected();
                });
              });

            departmentDropdown
              .querySelectorAll(".dept-primary")
              .forEach((radio) => {
                if (String(radio.value) === String(primaryDeptId)) {
                  radio.checked = true;
                }
                radio.addEventListener("change", () => {
                  const checkbox = departmentDropdown.querySelector(
                    `.dept-checkbox[value="${radio.value}"]`
                  );
                  if (!checkbox.checked) checkbox.checked = true;
                  updateSelected();
                });
              });

            updateSelected();
          });

        function updateSelected() {
          const selectedCheckboxes = Array.from(
            departmentDropdown.querySelectorAll(".dept-checkbox:checked")
          );

          let selected = selectedCheckboxes.map((c) => {
            const deptId = c.value;
            const primaryRadio = departmentDropdown.querySelector(
              `.dept-primary[value="${deptId}"]`
            );
            return {
              id: deptId,
              primary: primaryRadio?.checked || false,
            };
          });

          hiddenInput.value = JSON.stringify(selected);

          const selectedNames = selectedCheckboxes.map((c) => {
            const deptId = c.value;
            const isPrimary = departmentDropdown.querySelector(
              `.dept-primary[value="${deptId}"]`
            ).checked;
            return c.dataset.name + (isPrimary ? " ⭐" : "");
          });

          if (selectedNames.length === 0) {
            dropdownBtn.innerHTML = "Select Departments";
          } else {
            dropdownBtn.innerHTML = selectedNames
              .map((n) => `<span class="dept-badge">${n}</span>`)
              .join(" ");
          }
        }

        let modal = new bootstrap.Modal(
          document.getElementById("editUserModal")
        );
        modal.show();
      });
  }

  document
    .getElementById("adminEditUserForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      // Capture old primary before update
      const oldDepartments = JSON.parse(
        document.getElementById("admin_edit_departments").value || "[]"
      );
      const oldPrimary = (oldDepartments.find((d) => d.primary) || {}).id;
      const oldPrimaryName = oldPrimary
        ? document.querySelector(`.dept-checkbox[value="${oldPrimary}"]`)
            ?.dataset.name
        : null;

      let formData = new FormData();
      formData.append(
        "id",
        document.getElementById("admin_edit_user_id").value
      );
      formData.append(
        "first_name",
        document.getElementById("admin_edit_first_name").value
      );
      formData.append(
        "middle_name",
        document.getElementById("admin_edit_middle_name").value
      );
      formData.append(
        "last_name",
        document.getElementById("admin_edit_last_name").value
      );
      formData.append(
        "employee_id",
        document.getElementById("admin_edit_employee_id").value
      );
      formData.append(
        "email",
        document.getElementById("admin_edit_email").value
      );
      formData.append("role", document.getElementById("admin_edit_role").value);

      // departments (hidden JSON input)
      formData.append(
        "departments",
        document.getElementById("admin_edit_departments").value
      );

      let fileInput = document.getElementById("admin_edit_profile_image");
      if (fileInput.files.length > 0) {
        formData.append("profile_image", fileInput.files[0]);
      }

      fetch("../backend/update_user_admin.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            // Parse new primary from hidden input after update
            const newDepartments = JSON.parse(
              document.getElementById("admin_edit_departments").value || "[]"
            );
            const newPrimary = (newDepartments.find((d) => d.primary) || {}).id;
            const newPrimaryName = newPrimary
              ? document.querySelector(`.dept-checkbox[value="${newPrimary}"]`)
                  ?.dataset.name
              : null;

            let message = data.message || "User updated successfully.";
            if (
              oldPrimaryName &&
              newPrimaryName &&
              oldPrimaryName !== newPrimaryName
            ) {
              message += ` Changed primary department from ${oldPrimaryName} to ${newPrimaryName}.`;
            }

            alert(message);
            loadUsers();
            bootstrap.Modal.getInstance(
              document.getElementById("editUserModal")
            ).hide();
          } else {
            alert("Error: " + (data.message || "Update failed."));
          }
        })
        .catch((err) => {
          console.error("Update failed:", err);
          alert("Unexpected error occurred.");
        });
    });
});

function toggleUserStatus(userId, currentStatus) {
  const newStatus = currentStatus === "active" ? "inactive" : "active";

  fetch("../backend/update_user_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: userId, status: newStatus }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        const toggle = document.querySelector(`#status-toggle-${userId}`);
        if (toggle) {
          toggle.checked = newStatus === "active";
          toggle.setAttribute("data-status", newStatus);
        }
        showToast(`Status updated: ${newStatus}`);
      } else {
        alert("Error: " + data.error);
      }
    })
    .catch((err) => console.error("Error:", err));
}
