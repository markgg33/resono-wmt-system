document.addEventListener("DOMContentLoaded", function () {
  fetch("../backend/get_user_profile.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        document.getElementById(
          "profileMessage"
        ).innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
      }
      document.getElementById("edit_first_name").value = data.first_name || "";
      document.getElementById("edit_middle_name").value =
        data.middle_name || "";
      document.getElementById("edit_last_name").value = data.last_name || "";
      document.getElementById("edit_email").value = data.email || "";
      document.getElementById("edit_role").value = data.role || "";
    })
    .catch((err) => {
      console.error("Error loading profile:", err);
      document.getElementById(
        "profileMessage"
      ).innerHTML = `<div class="alert alert-danger">Error loading profile.</div>`;
    });
});

// Update Profile
document
  .getElementById("updateProfileForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    // Confirmation before update
    if (!confirm("Are you sure you want to update your profile?")) {
      return; // Stop execution if cancelled
    }

    let payload = {
      first_name: document.getElementById("edit_first_name").value,
      middle_name: document.getElementById("edit_middle_name").value,
      last_name: document.getElementById("edit_last_name").value,
      employee_id: document.getElementById("employee_id").value || null, // optional

      // No email — it's readonly and shouldn't be sent
    };

    fetch("../backend/update_profile.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((data) => {
        let msgDiv = document.getElementById("profileMessage");
        msgDiv.innerHTML = data.success
          ? `<div class="alert alert-success">${data.success}</div>`
          : `<div class="alert alert-danger">${data.error}</div>`;
      });
  });

// Change Password
document
  .getElementById("changePasswordForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    // Confirmation before update
    if (!confirm("Update Password?")) {
        return; // Stop execution if cancelled
    }

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
