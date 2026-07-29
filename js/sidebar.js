// =====================================================
// PAGE LIFECYCLE MANAGER
// =====================================================

window.PageManager = {
  modules: {},

  register(page, module) {
    this.modules[page] = {
      initialized: false,
      ...module,
    };
  },

  async activate(page) {
    const module = this.modules[page];

    if (!module) return;

    // Run only once
    if (!module.initialized) {
      module.initialized = true;

      if (typeof module.init === "function") {
        await module.init();
      }
    }

    // Run every time page opens
    if (typeof module.refresh === "function") {
      await module.refresh();
    }
  },
};

// =====================================================
// PAGE NAVIGATION
// =====================================================

async function changePage(page) {
  // Hide all pages
  document.querySelectorAll(".page-content").forEach((pageContent) => {
    pageContent.style.display = "none";
  });

  const target = document.getElementById(page + "-page");

  if (!target) {
    console.warn(`Page '${page}' not found.`);
    return;
  }

  target.style.display = "block";

  // NEW
  await PageManager.activate(page);

  // Save page
  localStorage.setItem("lastPage", page);
}

// =====================================================
// INITIALIZATION
// =====================================================

document.addEventListener("DOMContentLoaded", () => {
  const sidebarItems = document.querySelectorAll(".sidebar-list-item");

  sidebarItems.forEach((item) => {
    item.addEventListener("click", () => {
      sidebarItems.forEach((i) => i.classList.remove("active"));

      item.classList.add("active");

      changePage(item.dataset.page);
    });
  });

  const lastPage = localStorage.getItem("lastPage") || "my-tracker";

  const active = document.querySelector(
    `.sidebar-list-item[data-page="${lastPage}"]`,
  );

  if (active) {
    active.classList.add("active");
  }

  changePage(lastPage);
});