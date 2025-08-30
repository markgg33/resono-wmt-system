document.addEventListener("DOMContentLoaded", function () {
  loadUsers();
  loadDepartments();
  loadRoles();

  function loadUsers(departmentId = "") {
    let url = "../backend/get_all_users.php";
    if (departmentId) {
      url += `?department_id=${departmentId}`;
    }

    fetch(url)
      .then((res) => res.json())
      .then((users) => {
        let tbody = document.querySelector("#usersTable tbody");
        tbody.innerHTML = "";

        if (!users || users.length === 0) {
          let noRow = `<tr>
          <td colspan="4" class="text-center text-muted">No results found</td>
        </tr>`;
          tbody.insertAdjacentHTML("beforeend", noRow);
          return;
        }

        users.forEach((user) => {
          let row = `<tr>
  <td>${user.first_name} ${user.middle_name || ""} ${user.last_name}</td>
  <td>${user.department_name || "-"}</td>
  <td>${user.role}</td>
  <td class="text-center">
    <button class="btn btn-sm btn-success editUserBtn" data-id="${user.id}">
      <i class="fa-solid fa-pen-to-square"></i>
    </button>
    <button class="btn btn-sm btn-danger deleteUserBtn" data-id="${user.id}">
      <i class="fa-solid fa-trash"></i>
    </button>
  </td>
</tr>`;
          tbody.insertAdjacentHTML("beforeend", row);
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
      })
      .catch((error) => {
        console.error("Error fetching users:", error);
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
        let filterSelect = document.getElementById("departmentFilter");
        filterSelect.innerHTML = `<option value="">All Departments</option>`;

        depts.forEach((d) => {
          modalSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
          filterSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
        });
      });
  }

  document
    .getElementById("departmentFilter")
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

      let payload = {
        id: document.getElementById("admin_edit_user_id").value,
        first_name: document.getElementById("admin_edit_first_name").value,
        middle_name: document.getElementById("admin_edit_middle_name").value,
        last_name: document.getElementById("admin_edit_last_name").value,
        employee_id: document.getElementById("admin_edit_employee_id").value,
        role: document.getElementById("admin_edit_role").value,
        department_id: document.getElementById("admin_edit_department").value,
      };

      fetch("../backend/update_user_admin.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
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
