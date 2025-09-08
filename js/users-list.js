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

          let row = `
          <tr>
            <td><img src="${image}" class="rounded-circle" width="50" height="50" style="object-fit:cover;"></td>
            <td>${user.first_name} ${user.middle_name || ""} ${
            user.last_name
          }</td>
            <td>${user.department_name || "-"}</td>
            <td>${user.role}</td>
            <td class="text-center">
              <div class="form-check form-switch">
                <input class="form-check-input toggleStatus" type="checkbox" data-id="${
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

        // Attach events
        document.querySelectorAll(".toggleStatus").forEach((toggle) => {
          toggle.addEventListener("change", function () {
            fetch("../backend/update_user_status.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                id: this.dataset.id,
                status: this.checked ? "active" : "inactive",
              }),
            })
              .then((res) => res.json())
              .then((data) => {
                if (!data.success) alert("Failed to update status");
                loadUsers(departmentId);
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
        // Populate modal select
        let modalSelect = document.getElementById("admin_edit_department");
        modalSelect.innerHTML = `<option value="">-- Select Department --</option>`;

        // Populate filter select
        let filterSelect = document.getElementById("adminDepartmentFilter");
        filterSelect.innerHTML = `<option value="">All Departments</option>`;

        depts.forEach((d) => {
          modalSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
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
        document.getElementById("admin_edit_department").value =
          user.department_id || "";

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
      formData.append("role", document.getElementById("admin_edit_role").value);
      formData.append(
        "department_id",
        document.getElementById("admin_edit_department").value
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
          alert(data.success || data.error);
          if (data.success) {
            loadUsers();
            bootstrap.Modal.getInstance(
              document.getElementById("editUserModal")
            ).hide();
          }
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
        // Update the toggle visually
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
