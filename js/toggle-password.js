//FOR TOGGLE PASSWORD
function togglePassword(fieldId) {
    let field = document.getElementById(fieldId);
    let icon = field.parentElement.querySelector(".toggle-password i"); // Select icon inside the span
  
    if (field.type === "password") {
      field.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
      field.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
    }
  }