document.addEventListener("DOMContentLoaded", function () {
  // Handle success modal
  const params = new URLSearchParams(window.location.search);
  if (params.get("user_added") === "1") {
    const userAddedModal = new bootstrap.Modal(
      document.getElementById("userAddedModal")
    );
    userAddedModal.show();
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
