// Legacy function (still works for login and old forms)
function togglePassword(fieldId) {
  let field = document.getElementById(fieldId);
  let icon = field.parentElement.querySelector(".toggle-password i"); // Select icon inside span

  if (field.type === "password") {
    field.type = "text";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  } else {
    field.type = "password";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  }
}

// New auto-bind version (for cleaner modals without inline onclick)
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".toggle-password[data-target]").forEach((el) => {
    el.addEventListener("click", function () {
      let targetId = this.getAttribute("data-target");
      let field = document.getElementById(targetId);
      let icon = this.querySelector("i");

      if (field.type === "password") {
        field.type = "text";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      } else {
        field.type = "password";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      }
    });
  });
});
