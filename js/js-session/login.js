// FOR LOGIN JAVASCRIPT

document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch("backend/login.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        const errDiv = document.getElementById("loginError");
        errDiv.style.display = "block";
        errDiv.textContent = data.message;
      }
    })
    .catch((err) => console.error("Login error:", err));
});
