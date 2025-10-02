// 🔹 Stores all users fetched from backend
let allUsers = [];
let currentPage = 1; // 🔹 Pagination: current page
const rowsPerPage = 10; // 🔹 Pagination: rows per page

// 🔹 Message to show if no users available
function getFallbackMessage() {
  if (userRole === "supervisor") {
    return "No users online in your department";
  }
  return "No users online";
}

// 🔹 Load all departments into the dashboard filter dropdown
async function loadDepartments() {
  try {
    const res = await fetch("../backend/get_user_departments.php"); // <-- FIXED
    const data = await res.json();

    const select = document.getElementById("dashDepartmentFilter");
    if (!select) return;

    // Show "All Departments" only for roles that can see all
    select.innerHTML = "";
    if (["admin", "executive", "hr"].includes(userRole)) {
      select.innerHTML = `<option value="">All Departments</option>`;
    }

    data.forEach((dept) => {
      if (dept.name.toLowerCase() !== "unassigned") {
        const option = document.createElement("option");
        option.value = dept.id;
        option.textContent = dept.name;
        select.appendChild(option);
      }
    });
  } catch (err) {
    console.error("Error loading departments:", err);
  }
}

// 🔹 Fetch user statuses (dashboard + widget separately)
async function loadUserStatuses() {
  try {
    let dashUrl = "../backend/get_user_statuses.php?mode=dashboard";
    const filter = document.getElementById("dashDepartmentFilter")?.value || "";

    if (
      userRole === "supervisor" &&
      Array.isArray(supervisorDepartments) &&
      supervisorDepartments.length > 0 &&
      !filter
    ) {
      dashUrl += `&department_ids=${encodeURIComponent(
        supervisorDepartments.join(",")
      )}`;
    } else if (filter) {
      dashUrl += `&department_id=${filter}`;
    }

    const dashRes = await fetch(dashUrl);
    const dashData = await dashRes.json();
    if (dashData.success) {
      allUsers = dashData.users || [];
      currentPage = 1; // reset page when new data arrives
      renderTable();
      renderPaginationControls();
    }

    const widgetRes = await fetch(
      "../backend/get_user_statuses.php?mode=widget"
    );
    const widgetData = await widgetRes.json();
    if (widgetData.success) renderOnlineWidget(widgetData.users || []);
  } catch (err) {
    console.error("Error loading statuses:", err);
  }
}

// 🔹 Render the main status dashboard table (paginated)
function renderTable() {
  const tbody = document.getElementById("statusTable");
  if (!tbody) return;
  tbody.innerHTML = "";

  if (allUsers.length === 0) {
    const tr = document.createElement("tr");
    tr.innerHTML = `<td colspan="5" class="text-center text-muted py-3">${getFallbackMessage()}</td>`;
    tbody.appendChild(tr);
    return;
  }

  // 🔹 Pagination slice
  const start = (currentPage - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const usersToShow = allUsers.slice(start, end);

  usersToShow.forEach((user) => {
    let colorClass = "text-secondary";
    if (user.status === "active") colorClass = "text-success";
    if (user.status === "away") colorClass = "text-warning";

    const timeTagged = user.time_tagged
      ? new Date("1970-01-01T" + user.time_tagged).toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        })
      : "--";

    // 🔹 Display multiple departments as badges
    const deptBadges = (user.departments || [user.department])
      .map(
        (dept) =>
          `<span class="badge bg-success text-white me-1">${dept}</span>`
      )
      .join(" ");

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="fw-semibold d-flex align-items-center">
        <img src="${user.profile_image}" alt="${
      user.full_name
    }" class="rounded-circle me-2" width="32" height="32" onerror="this.onerror=null;this.src='../assets/default-avatar.jpg';">
        ${user.full_name}
      </td>
      <td>${deptBadges}</td>
      <td>
        <span class="${colorClass} fw-bold">●</span> ${
      user.status.charAt(0).toUpperCase() + user.status.slice(1)
    }
      </td>
      <td>${user.latest_task}</td>
      <td>${timeTagged}</td>
    `;
    tbody.appendChild(tr);
  });
}

// 🔹 Pagination controls
function renderPaginationControls() {
  const container = document.getElementById("paginationControls");
  if (!container) return;
  container.innerHTML = "";

  const totalPages = Math.ceil(allUsers.length / rowsPerPage);
  if (totalPages <= 1) return;

  // Previous button
  const prevBtn = document.createElement("button");
  prevBtn.className = "btn btn-sm btn-outline-success me-1";
  prevBtn.textContent = "Previous";
  prevBtn.disabled = currentPage === 1;
  prevBtn.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderTable();
      renderPaginationControls();
    }
  });
  container.appendChild(prevBtn);

  // Page number buttons
  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.className = `btn btn-sm me-1 ${
      i === currentPage ? "btn-success" : "btn-outline-success"
    }`;
    btn.textContent = i;
    btn.addEventListener("click", () => {
      currentPage = i;
      renderTable();
      renderPaginationControls();
    });
    container.appendChild(btn);
  }

  // Next button
  const nextBtn = document.createElement("button");
  nextBtn.className = "btn btn-sm btn-outline-success";
  nextBtn.textContent = "Next";
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.addEventListener("click", () => {
    if (currentPage < totalPages) {
      currentPage++;
      renderTable();
      renderPaginationControls();
    }
  });
  container.appendChild(nextBtn);
}

// 🔹 Render the floating popup widget
function renderOnlineWidget(users) {
  const list = document.getElementById("onlineUsersList");
  if (!list) return;
  list.innerHTML = "";

  if (users.length === 0) {
    const li = document.createElement("li");
    li.className = "list-group-item text-center text-muted border-0";
    li.textContent = getFallbackMessage();
    list.appendChild(li);
    return;
  }

  users.forEach((user) => {
    let colorClass = "text-secondary";
    if (user.status === "active") colorClass = "text-success";
    if (user.status === "away") colorClass = "text-warning";

    const li = document.createElement("li");
    li.className =
      "list-group-item d-flex align-items-center border-0 px-1 py-2";
    li.innerHTML = `
      <img src="${user.profile_image}" alt="${user.full_name}" class="rounded-circle me-2 flex-shrink-0" width="40" height="40" onerror="this.onerror=null;this.src='../assets/default-avatar.jpg';">
      <span class="${colorClass} fw-bold me-2" style="font-size:1.2rem;">●</span>
      <div class="d-flex flex-column">
        <span>${user.full_name}</span>
        <strong><small class="text-muted">${user.department}</small></strong>
      </div>
    `;
    list.appendChild(li);
  });
}

// 🔹 Toggle floating popup
document.getElementById("onlineToggle").addEventListener("click", () => {
  const popup = document.getElementById("onlineUsersPopup");
  popup.style.display = popup.style.display === "none" ? "block" : "none";
});

// 🔹 Close popup if clicked outside
document.addEventListener("click", (e) => {
  const widget = document.getElementById("onlineWidget");
  if (!widget.contains(e.target)) {
    document.getElementById("onlineUsersPopup").style.display = "none";
  }
});

// 🔹 Department filter event
document
  .getElementById("dashDepartmentFilter")
  ?.addEventListener("change", loadUserStatuses);

// 🔹 Initialize
loadDepartments().then(() => {
  loadUserStatuses();
  setInterval(loadUserStatuses, 5000);
});
