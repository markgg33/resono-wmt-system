document.addEventListener("DOMContentLoaded", function () {
  const msgDiv = document.getElementById("profileMessage");
  const previewImg = document.getElementById("profilePreview");
  const fileInput = document.getElementById("edit_profile_image");
  const submitBtn = document.getElementById("profileSubmitBtn");

  // Elements for multi-department dropdown
  const departmentField = document.createElement("div");
  const deptDropdownBtn = document.createElement("button");
  const deptDropdown = document.createElement("ul");
  const hiddenInput = document.createElement("input");

  // Setup dropdown structure
  departmentField.className = "dropdown mb-3";
  deptDropdownBtn.className = "btn btn-secondary dropdown-toggle w-100";
  deptDropdownBtn.setAttribute("type", "button");
  deptDropdownBtn.setAttribute("data-bs-toggle", "dropdown");
  deptDropdownBtn.textContent = "Select Departments";

  deptDropdown.className = "dropdown-menu w-100 p-2";
  deptDropdown.style.maxHeight = "200px";
  deptDropdown.style.overflowY = "auto";

  hiddenInput.type = "hidden";
  hiddenInput.id = "edit_departments_hidden";

  departmentField.appendChild(deptDropdownBtn);
  departmentField.appendChild(deptDropdown);
  departmentField.appendChild(hiddenInput);

  // Replace old single select
  const oldSelect = document.getElementById("edit_department_select");
  oldSelect.parentNode.replaceChild(departmentField, oldSelect);

  // Load profile
  fetch("../backend/get_user_profile.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        msgDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
      }

      document.getElementById("edit_employee_id").value =
        data.employee_id || "";
      document.getElementById("edit_first_name").value = data.first_name || "";
      document.getElementById("edit_middle_name").value =
        data.middle_name || "";
      document.getElementById("edit_last_name").value = data.last_name || "";
      document.getElementById("edit_email").value = data.email || "";
      document.getElementById("edit_role").value = data.role || "";

      // Show current profile image
      const imgPath = data.profile_image
        ? `../${data.profile_image}`
        : "../assets/default-avatar.jpg";
      previewImg.src = imgPath;

      // Lock inputs if role is "user"
      const isUser = (data.role || "").toLowerCase() === "user";
      [
        "edit_employee_id",
        "edit_first_name",
        "edit_middle_name",
        "edit_last_name",
      ].forEach((id) => (document.getElementById(id).disabled = isUser));

      submitBtn.textContent = isUser ? "Update Photo" : "Update Profile";

      // ===== Load departments for multi-select dropdown =====
      fetch("../backend/get_departments.php")
        .then((res) => res.json())
        .then((departments) => {
          deptDropdown.innerHTML = "";

          departments.forEach((dept) => {
            const li = document.createElement("li");
            li.className =
              "d-flex align-items-center justify-content-between px-2";

            const label = document.createElement("label");
            label.className = "dropdown-item flex-grow-1 mb-0";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "dept-checkbox me-2";
            checkbox.value = dept.id;
            checkbox.dataset.name = dept.name;

            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(dept.name));

            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "primaryDept";
            radio.className = "dept-primary ms-2";
            radio.value = dept.id;
            radio.title = "Set Primary";

            li.appendChild(label);
            li.appendChild(radio);
            deptDropdown.appendChild(li);
          });

          // Pre-check assigned departments
          const assignedIds = (data.departments || []).map((d) => String(d.id));
          const primaryId = (data.departments || []).find(
            (d) => d.is_primary
          )?.id;

          deptDropdown.querySelectorAll(".dept-checkbox").forEach((chk) => {
            if (assignedIds.includes(chk.value)) chk.checked = true;
          });
          deptDropdown.querySelectorAll(".dept-primary").forEach((r) => {
            if (String(r.value) === String(primaryId)) r.checked = true;
          });

          updateDropdown(); // initial render of badges

          if (isUser) {
            // Disable interaction but still show assigned departments
            deptDropdownBtn.disabled = true;
            deptDropdown
              .querySelectorAll("input")
              .forEach((input) => (input.disabled = true));

            const selectedNames = (data.departments || []).map((d) => {
              return `<span class="dept-badge">${d.name}${
                d.is_primary ? " ⭐" : ""
              }</span>`;
            });

            deptDropdownBtn.innerHTML =
              selectedNames.length > 0
                ? selectedNames.join(" ")
                : "No Departments Assigned";
          } else {
            // Add event listeners for checkboxes and radios
            deptDropdown.querySelectorAll(".dept-checkbox").forEach((chk) => {
              chk.addEventListener("change", updateDropdown);
            });
            deptDropdown.querySelectorAll(".dept-primary").forEach((r) => {
              r.addEventListener("change", updateDropdown);
            });
          }
        });

      function updateDropdown() {
        const selected = [];
        const names = [];

        deptDropdown
          .querySelectorAll(".dept-checkbox:checked")
          .forEach((chk) => {
            const radio = deptDropdown.querySelector(
              `.dept-primary[value="${chk.value}"]`
            );
            const isPrimary = radio.checked;
            selected.push({ id: chk.value, primary: isPrimary });
            names.push(`${chk.dataset.name}${isPrimary ? " ⭐" : ""}`);
          });

        hiddenInput.value = JSON.stringify(selected);
        deptDropdownBtn.innerHTML =
          names.length > 0
            ? names
                .map((n) => `<span class="badge bg-success me-1">${n}</span>`)
                .join("")
            : "Select Departments";
      }
    })
    .catch((err) => {
      console.error("Error loading profile:", err);
      msgDiv.innerHTML = `<div class="alert alert-danger">Error loading profile.</div>`;
    });

  // Preview new image before upload
  fileInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      previewImg.src = URL.createObjectURL(this.files[0]);
    }
  });

  // ===== Submit form =====
  document
    .getElementById("updateProfileForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      if (!confirm("Are you sure you want to update your profile?")) return;

      const formData = new FormData();
      formData.append(
        "first_name",
        document.getElementById("edit_first_name").value
      );
      formData.append(
        "middle_name",
        document.getElementById("edit_middle_name").value
      );
      formData.append(
        "last_name",
        document.getElementById("edit_last_name").value
      );
      formData.append(
        "employee_id",
        document.getElementById("edit_employee_id").value || ""
      );

      // Send multi-department JSON
      formData.append("departments", hiddenInput.value);

      if (fileInput.files.length > 0) {
        formData.append("profile_image", fileInput.files[0]);
      } else {
        formData.append(
          "keep_existing_image",
          previewImg.src.replace("../", "")
        );
      }

      fetch("../backend/update_profile.php", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            msgDiv.innerHTML = `<div class="alert alert-success">${data.success}</div>`;
            previewImg.src =
              "../" + data.profile_image + "?t=" + new Date().getTime();
          } else {
            msgDiv.innerHTML = `<div class="alert alert-danger">${
              data.error || "Update failed"
            }</div>`;
          }
        })
        .catch((err) => console.error("Error updating profile:", err));
    });

  // ===== PATCH FIX: Change Password Handler =====
  const changeForm = document.getElementById("changePasswordForm");
  if (changeForm) {
    changeForm.addEventListener("submit", function (e) {
      e.preventDefault(); // prevent page refresh
      if (!confirm("Update Password?")) return;

      const payload = {
        current_password: document.getElementById("current_password").value,
        new_password: document.getElementById("new_password").value,
        confirm_password: document.getElementById("confirm_password").value,
      };

      fetch("../backend/change_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      })
        .then((res) => res.json())
        .then((data) => {
          const msgDiv = document.getElementById("passwordMessage");
          if (data.success) {
            msgDiv.innerHTML = `<div class="alert alert-success">${data.success}</div>`;
            changeForm.reset();
          } else {
            msgDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
          }
        })
        .catch((err) => {
          console.error("Error changing password:", err);
          document.getElementById(
            "passwordMessage"
          ).innerHTML = `<div class="alert alert-danger">Error changing password.</div>`;
        });
    });
  }
});
