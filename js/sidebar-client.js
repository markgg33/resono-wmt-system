function changePage(page) {
  // Hide all pages
  document.querySelectorAll(".page-content").forEach(function (pageContent) {
    pageContent.style.display = "none";
  });

  // Show target page
  document.getElementById(page + "-page").style.display = "block";
}

document.addEventListener("DOMContentLoaded", function () {
  // Default page for client dashboard
  changePage("data-visualization");

  // Highlight the Data Visualization item by default
  const sidebarItems = document.querySelectorAll(".sidebar-list-item");
  sidebarItems.forEach((item) => {
    item.classList.remove("active");
  });

  const defaultItem = document.querySelector(
    '.sidebar-list-item[data-page="data-visualization"]'
  );
  if (defaultItem) {
    defaultItem.classList.add("active");
  }

  // Handle clicks to change pages
  sidebarItems.forEach((item) => {
    item.addEventListener("click", function () {
      // Remove the "active" class from all sidebar items
      sidebarItems.forEach((item) => {
        item.classList.remove("active");
      });

      // Add the "active" class to the clicked sidebar item
      this.classList.add("active");
    });
  });
});
