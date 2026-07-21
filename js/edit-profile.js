document.addEventListener("DOMContentLoaded", function () {
  const msgDiv = document.getElementById("profileMessage");
  const previewImg = document.getElementById("profilePreview");
  const fileInput = document.getElementById("edit_profile_image");
  const submitBtn = document.getElementById("profileSubmitBtn");

  // ===============================
  // 🔹 PATCH 1: Detect if department select exists
  // ===============================
  const oldSelect = document.getElementById("edit_department_select");
  const hasDepartment = !!oldSelect;
  let deptDropdown, deptDropdownBtn, hiddenInput;

  if (hasDepartment) {
    const departmentField = document.createElement("div");
    deptDropdownBtn = document.createElement("button");
    deptDropdown = document.createElement("ul");
    hiddenInput = document.createElement("input");

    departmentField.className = "dropdown mb-3";
    deptDropdownBtn.className = "btn btn-secondary dropdown-toggle w-100";
    deptDropdownBtn.type = "button";
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

    oldSelect.parentNode.replaceChild(departmentField, oldSelect);
  }

  // ===============================
  // 🔹 PATCH 2: Load Profile (works for all roles)
  // ===============================
  fetch("../backend/get_user_profile.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        msgDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
      }

      // ✅ Fill info
      const fill = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val || "";
      };

      fill("edit_employee_id", data.employee_id);
      fill("edit_first_name", data.first_name);
      fill("edit_middle_name", data.middle_name);
      fill("edit_last_name", data.last_name);
      fill("edit_email", data.email);
      fill("edit_role", data.role);

      // ✅ Image
      previewImg.src = data.profile_image
        ? `../${data.profile_image}`
        : "../assets/default-avatar.jpg";

      // ✅ Determine role
      const role = (data.role || "").toLowerCase();
      const isUserOrClient = role === "user" || role === "client";

      ["edit_first_name", "edit_middle_name", "edit_last_name"].forEach(
        (id) => {
          const input = document.getElementById(id);
          if (input) input.disabled = isUserOrClient;
        }
      );

      submitBtn.textContent = isUserOrClient
        ? "Update Photo"
        : "Update Profile";

      // ===============================
      // 🔹 PATCH 3: Always load departments if exists
      // ===============================
      if (!hasDepartment) return; // if there's no department field, skip

      fetch("../backend/get_departments.php")
        .then((r) => r.json())
        .then((departments) => {
          deptDropdown.innerHTML = "";

          departments.forEach((dept) => {
            const li = document.createElement("li");
            li.className =
              "d-flex align-items-center justify-content-between px-2";

            const label = document.createElement("label");
            label.className = "dropdown-item flex-grow-1 mb-0";

            const chk = document.createElement("input");
            chk.type = "checkbox";
            chk.className = "dept-checkbox me-2";
            chk.value = dept.id;
            chk.dataset.name = dept.name;

            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "primaryDept";
            radio.className = "dept-primary ms-2";
            radio.value = dept.id;
            radio.title = "Set Primary";

            label.appendChild(chk);
            label.appendChild(document.createTextNode(dept.name));
            li.appendChild(label);
            li.appendChild(radio);
            deptDropdown.appendChild(li);
          });

          // ✅ Pre-check assigned departments
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

          updateDropdown();

          /* ✅ PATCHED: if user or client, disable all dept interactions but show names
          if (isUserOrClient) {
            deptDropdownBtn.disabled = true;
            deptDropdown
              .querySelectorAll("input")
              .forEach((input) => (input.disabled = true));

            const selectedNames = (data.departments || []).map((d) => {
              return `<span class="badge bg-success me-1">${d.name}${
                d.is_primary ? " ⭐" : ""
              }</span>`;
            });

            deptDropdownBtn.innerHTML =
              selectedNames.length > 0
                ? selectedNames.join(" ")
                : "No Departments Assigned";
            return; // stop here (no event listeners needed)
          }*/

          // ✅ PATCHED: Disable department editing for user, client, and supervisor
          const isRestrictedRole =
            role === "user" || role === "client" || role === "supervisor";

          if (isRestrictedRole) {
            deptDropdownBtn.disabled = true;
            deptDropdown
              .querySelectorAll("input")
              .forEach((input) => (input.disabled = true));

            const selectedNames = (data.departments || []).map((d) => {
              return `<span class="badge bg-success me-1">${d.name}${
                d.is_primary ? " ⭐" : ""
              }</span>`;
            });

            deptDropdownBtn.innerHTML =
              selectedNames.length > 0
                ? selectedNames.join(" ")
                : "No Departments Assigned";
            return; // stop here (no event listeners needed)
          }

          // ✅ For admin/higher roles: enable department editing
          deptDropdown.querySelectorAll(".dept-checkbox").forEach((chk) => {
            chk.addEventListener("change", updateDropdown);
          });
          deptDropdown.querySelectorAll(".dept-primary").forEach((r) => {
            r.addEventListener("change", updateDropdown);
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
                    .map(
                      (n) => `<span class="badge bg-success me-1">${n}</span>`
                    )
                    .join("")
                : "Select Departments";
          }
        });
    })
    .catch((err) => {
      console.error("Error loading profile:", err);
      msgDiv.innerHTML = `<div class="alert alert-danger">Error loading profile.</div>`;
    });

  // ===============================
  // 🔹 PATCH 4: Image Preview
  // ===============================
  fileInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      previewImg.src = URL.createObjectURL(this.files[0]);
    }
  });

  // ===============================
  // 🔹 PATCH 5: Profile Form Submit
  // ===============================
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
        document.getElementById("edit_employee_id")
          ? document.getElementById("edit_employee_id").value
          : ""
      );

      if (hasDepartment && hiddenInput) {
        formData.append("departments", hiddenInput.value);
      }

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

  // ===============================
  // 🔹 PATCH 6: Change Password Form
  // ===============================
  const changeForm = document.getElementById("changePasswordForm");
  if (changeForm) {
    changeForm.addEventListener("submit", function (e) {
      e.preventDefault();
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
          const msg = document.getElementById("passwordMessage");
          if (data.success) {
            msg.innerHTML = `<div class="alert alert-success">${data.success}</div>`;
            changeForm.reset();
          } else {
            msg.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
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
