let allUsers = [];

async function loadUserStatuses() {
  try {
    const filter = document.getElementById("departmentFilter")?.value || "";
    const url = filter
      ? `../backend/get_user_statuses.php?department_id=${filter}`
      : "../backend/get_user_statuses.php";

    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      console.error(data.message || "Failed to fetch statuses");
      return;
    }

    allUsers = data.users;
    renderTable();
    renderOnlineWidget();
  } catch (err) {
    console.error("Error loading statuses:", err);
  }
}

function renderTable() {
  const tbody = document.getElementById("statusTable");
  if (!tbody) return;

  tbody.innerHTML = "";
  allUsers.forEach((user) => {
    let colorClass = "text-secondary";
    if (user.status === "active") colorClass = "text-success";
    if (user.status === "away") colorClass = "text-warning";

    const timeTagged = user.time_tagged
      ? new Date("1970-01-01T" + user.time_tagged).toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        })
      : "--";

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="fw-semibold d-flex align-items-center">
        <img src="${user.profile_image}" alt="${user.full_name}"
             class="rounded-circle me-2" width="32" height="32"
             onerror="this.onerror=null;this.src='assets/default-avatar.jpg';">
        ${user.full_name}
      </td>
      <td><span class="badge bg-info text-dark">${user.department}</span></td>
      <td>
        <span class="${colorClass} fw-bold">●</span>
        ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
      </td>
      <td>${user.latest_task}</td>
      <td>${timeTagged}</td>
    `;
    tbody.appendChild(tr);
  });
}

function renderOnlineWidget() {
  const list = document.getElementById("onlineUsersList");
  if (!list) return;

  list.innerHTML = "";
  allUsers.forEach((user) => {
    let colorClass = "text-secondary";
    if (user.status === "active") colorClass = "text-success";
    if (user.status === "away") colorClass = "text-warning";

    const li = document.createElement("li");
    li.className = "list-group-item d-flex align-items-center border-0 px-0";
    li.innerHTML = `
      <img src="${user.profile_image}" alt="${user.full_name}"
           class="rounded-circle me-2" width="28" height="28"
           onerror="this.onerror=null;this.src='assets/default-avatar.jpg';">
      <span class="${colorClass} me-2">●</span>
      <span>${user.full_name} <small class="text-muted">(${user.department})</small></span>
    `;
    list.appendChild(li);
  });
}

// Toggle popup
document.getElementById("onlineToggle").addEventListener("click", () => {
  const popup = document.getElementById("onlineUsersPopup");
  popup.style.display = popup.style.display === "none" ? "block" : "none";
});

// Close popup if clicked outside
document.addEventListener("click", (e) => {
  const widget = document.getElementById("onlineWidget");
  if (!widget.contains(e.target)) {
    document.getElementById("onlineUsersPopup").style.display = "none";
  }
});

// 🔹 Add event listener for filter change
document.getElementById("departmentFilter")?.addEventListener("change", () => {
  loadUserStatuses();
});

// Auto refresh every 5s
setInterval(loadUserStatuses, 5000);
loadUserStatuses();
