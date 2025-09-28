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
    document.getElementById("department_ids") &&
    document.getElementById("role")
  ) {
    const departmentField = document.getElementById("departmentField");
    const departmentDropdown = document.getElementById("departmentDropdown");
    const hiddenInput = document.getElementById("department_ids");
    const roleSelect = document.getElementById("role");
    const dropdownBtn = departmentField.querySelector(".dropdown-toggle");

    // Helper: update hidden input + button label
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

      const selectedNames = selectedCheckboxes.map(
        (c) =>
          c.dataset.name +
          (departmentDropdown.querySelector(`.dept-primary[value="${c.value}"]`)
            .checked
            ? " ⭐"
            : "")
      );

      if (selectedNames.length === 0) {
        dropdownBtn.innerHTML = "Select Departments";
      } else {
        dropdownBtn.innerHTML = selectedNames
          .map((name) => `<span class="dept-badge">${name}</span>`)
          .join(" ");
      }
    }

    // Fetch departments for dropdown
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
        <input type="radio" name="primaryDept" value="${d.id}" class="dept-primary ms-3" title="Set Primary">
      </li>
    `
          )
          .join("");

        // Add event listeners
        departmentDropdown
          .querySelectorAll(".dept-checkbox")
          .forEach((checkbox) => {
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
            radio.addEventListener("change", () => {
              const checkbox = departmentDropdown.querySelector(
                `.dept-checkbox[value="${radio.value}"]`
              );
              if (!checkbox.checked) checkbox.checked = true;
              updateSelected();
            });
          });
      });

    // Show/hide based on role for registration page
    roleSelect.addEventListener("change", function () {
      if (["user", "client", "supervisor"].includes(this.value)) {
        departmentField.style.display = "block";
      } else {
        departmentField.style.display = "none";
        hiddenInput.value = "";
        dropdownBtn.textContent = "Select Departments";
        departmentDropdown.querySelectorAll(".dept-checkbox").forEach((c) => {
          c.checked = false;
        });
        departmentDropdown.querySelectorAll(".dept-primary").forEach((r) => {
          r.checked = false;
        });
      }
    });
  }
});
