document.addEventListener("DOMContentLoaded", function () {
  // ====== EDIT PROFILE LOGIC ======
  if (document.getElementById("edit_department")) {
    fetch("../backend/get_departments.php")
      .then((res) => res.json())
      .then((departments) => {
        const deptSelect = document.getElementById("edit_department");
        deptSelect.innerHTML = departments
          .map((dept) => `<option value="${dept.id}">${dept.name}</option>`)
          .join("");

        // Load user profile
        fetch("../backend/get_user_profile.php")
          .then((response) => response.json())
          .then((data) => {
            if (data.error) {
              document.getElementById(
                "profileMessage"
              ).innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
              return;
            }
            document.getElementById("edit_first_name").value =
              data.first_name || "";
            document.getElementById("edit_middle_name").value =
              data.middle_name || "";
            document.getElementById("edit_last_name").value =
              data.last_name || "";
            document.getElementById("edit_email").value = data.email || "";
            document.getElementById("edit_role").value = data.role || "";
            document.getElementById("edit_department").value =
              data.department_name || "";
          });
      });
  }

  // ====== ADD USERS LOGIC ======
  if (
    document.getElementById("department_id") &&
    document.getElementById("role")
  ) {
    const departmentField = document.getElementById("departmentField");
    const departmentSelect = document.getElementById("department_id");
    const roleSelect = document.getElementById("role");

    // Fetch departments for dropdown
    fetch("../backend/get_departments.php")
      .then((res) => res.json())
      .then((departments) => {
        departmentSelect.innerHTML = `
                    <option value="">-- Select Department --</option>
                    ${departments
                      .map((d) => `<option value="${d.id}">${d.name}</option>`)
                      .join("")}
                `;
      });

    // Show/hide based on role
    roleSelect.addEventListener("change", function () {
      if (this.value === "user") {
        departmentField.style.display = "block";
      } else {
        departmentField.style.display = "none";
        departmentSelect.value = "";
      }
    });
  }
});
