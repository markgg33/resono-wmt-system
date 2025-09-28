function changePage(page) {
  // Hide all pages
  document.querySelectorAll(".page-content").forEach(function (pageContent) {
    pageContent.style.display = "none";
  });

  // Show target page
  document.getElementById(page + "-page").style.display = "block";

  // Save last visited page
  localStorage.setItem("lastPage", page);
}

document.addEventListener("DOMContentLoaded", function () {
  // Get saved page, fallback to my-tracker if none
  const lastPage = localStorage.getItem("lastPage") || "my-tracker";
  changePage(lastPage);

  // Highlight the correct sidebar item
  const sidebarItems = document.querySelectorAll(".sidebar-list-item");
  sidebarItems.forEach((item) => {
    item.classList.remove("active");
    if (item.getAttribute("data-page") === lastPage) {
      item.classList.add("active");
    }
  });

  // Handle clicks to change pages
  sidebarItems.forEach((item) => {
    item.addEventListener("click", function () {
      sidebarItems.forEach((item) => item.classList.remove("active"));
      this.classList.add("active");

      const page = this.getAttribute("data-page");
      changePage(page);
    });
  });
});
