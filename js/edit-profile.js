document.addEventListener("DOMContentLoaded", function () {
  const msgDiv = document.getElementById("profileMessage");
  const previewImg = document.getElementById("profilePreview");
  const fileInput = document.getElementById("edit_profile_image");
  const submitBtn = document.getElementById("profileSubmitBtn");

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

      // Load departments dropdown
      fetch("../backend/get_departments.php")
        .then((res) => res.json())
        .then((departments) => {
          const deptSelect = document.getElementById("edit_department_select");
          deptSelect.innerHTML = "";

          const defaultOption = document.createElement("option");
          defaultOption.value = "";
          defaultOption.textContent = "Select Department";
          deptSelect.appendChild(defaultOption);

          departments.forEach((dept) => {
            const option = document.createElement("option");
            option.value = dept.id;
            option.textContent = dept.name;
            deptSelect.appendChild(option);
          });

          deptSelect.value = data.department_id || "";
        });

      // Show current profile image
      const imgPath = data.profile_image
        ? `../${data.profile_image}`
        : "../assets/default-avatar.jpg";
      previewImg.src = imgPath;

      // Lock inputs if role is "user"
      const isUser = (data.role || "").toLowerCase() === "user";
      const lockIds = [
        "edit_employee_id",
        "edit_first_name",
        "edit_middle_name",
        "edit_last_name",
      ];
      lockIds.forEach((id) => (document.getElementById(id).disabled = isUser));
      document.getElementById("edit_department_select").disabled = isUser;

      submitBtn.textContent = isUser ? "Update Photo" : "Update Profile";
    })
    .catch((err) => {
      console.error("Error loading profile:", err);
      msgDiv.innerHTML = `<div class="alert alert-danger">Error loading profile.</div>`;
    });

  // Preview new image before upload
  fileInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      const url = URL.createObjectURL(this.files[0]);
      previewImg.src = url;
    }
  });
});

// Update Profile
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
    formData.append(
      "department_id",
      document.getElementById("edit_department_select").value || ""
    );

    const fileInput = document.getElementById("edit_profile_image");
    if (fileInput.files.length > 0) {
      formData.append("profile_image", fileInput.files[0]);
    }

    fetch("../backend/update_profile.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        const msgDiv = document.getElementById("profileMessage");

        if (data.success) {
          msgDiv.innerHTML = `<div class="alert alert-success">${data.success}</div>`;

          // Update sidebar name
          const nameElement = document.querySelector("p.text-center strong");
          if (nameElement && data.name) {
            nameElement.textContent = data.name;
          }

          // Update sidebar image with cache-busting
          if (data.profile_image) {
            const sidebarImg = document.querySelector(
              ".profile-container img.rounded-circle"
            );
            if (sidebarImg)
              sidebarImg.src =
                "../" + data.profile_image + "?t=" + new Date().getTime();

            // Update preview
            if (previewImg)
              previewImg.src =
                "../" + data.profile_image + "?t=" + new Date().getTime();
          }

          // Update Users list row if editing self
          const userRows = document.querySelectorAll("#usersTable tbody tr");
          userRows.forEach((row) => {
            const img = row.querySelector("td img");
            const nameTd = row.querySelector("td:nth-child(2)");
            if (img && nameTd && data.id == row.dataset.userId) {
              img.src =
                "../" + data.profile_image + "?t=" + new Date().getTime();
              nameTd.textContent = data.name;
            }
          });
        } else {
          msgDiv.innerHTML = `<div class="alert alert-danger">${
            data.error || "Update failed"
          }</div>`;
        }
      })
      .catch((err) => console.error("Error updating profile:", err));
  });

// Change Password
document
  .getElementById("changePasswordForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    if (!confirm("Update Password?")) return;

    let payload = {
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
        let msgDiv = document.getElementById("passwordMessage");
        msgDiv.innerHTML = data.success
          ? `<div class="alert alert-success">${data.success}</div>`
          : `<div class="alert alert-danger">${data.error}</div>`;
        if (data.success) {
          document.getElementById("changePasswordForm").reset();
        }
      });
  });
