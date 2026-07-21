// =======================================================
// ========== TEAM MANAGEMENT – FRONTEND LOGIC ===========
// =======================================================

// =======================================================
// SECTION 1: GLOBAL STATE
// =======================================================
let teamMembers = [];
let editTeamMembers = [];
let commendUsers = [];
let commendationMode = "commend"; // 'commend' | 'deduct'

// =======================================================
// SECTION 2: INITIAL LOADERS
// =======================================================

document.addEventListener("DOMContentLoaded", () => {
  if (document.getElementById("teamsAccordion")) {
    loadTeams();
    loadUsersForTeam();
  }

  if (document.getElementById("valuesTable")) {
    loadValues();
  }
});

// =======================================================
// SECTION 3: LOAD USERS FOR TEAM MODAL
// =======================================================
function loadUsersForTeam() {
  const dropdown = document.getElementById("teamUserDropdown");
  if (!dropdown) return;

  fetch("../backend/commendations/get_available_team_users.php")
    .then((res) => {
      if (!res.ok) throw new Error("Unauthorized");
      return res.json();
    })
    .then((users) => {
      dropdown.innerHTML = "";

      users.forEach((user) => {
        const li = document.createElement("li");
        li.innerHTML = `
          <label class="dropdown-item d-flex align-items-center gap-2">
            <input type="checkbox"
                   value="${user.id}"
                   onchange="toggleTeamMember(this, '${user.first_name} ${user.last_name}')">
            ${user.first_name} ${user.last_name}
            <span class="badge bg-secondary ms-auto">${user.role}</span>
          </label>
        `;
        dropdown.appendChild(li);
      });
    })
    .catch(() => {});
}

// =======================================================
// SECTION 4: TEAM MEMBER SELECTION LOGIC
// =======================================================
function toggleTeamMember(checkbox, name) {
  const userId = checkbox.value;

  if (checkbox.checked) {
    if (!teamMembers.some((m) => m.id === userId)) {
      teamMembers.push({ id: userId, name });
    }
  } else {
    teamMembers = teamMembers.filter((m) => m.id !== userId);
  }

  renderSelectedMembers();
}

function renderSelectedMembers() {
  const list = document.getElementById("selectedTeamMembers");
  if (!list) return;

  list.innerHTML = "";

  if (teamMembers.length === 0) {
    list.innerHTML = `<li class="list-group-item text-muted">No members selected</li>`;
    return;
  }

  teamMembers.forEach((member) => {
    const li = document.createElement("li");
    li.className =
      "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `
      ${member.name}
      <button class="btn btn-sm btn-outline-danger"
              onclick="removeTeamMember('${member.id}')">
        <i class="fa fa-times"></i>
      </button>
    `;
    list.appendChild(li);
  });
}

function removeTeamMember(userId) {
  teamMembers = teamMembers.filter((m) => m.id !== userId);

  document
    .querySelectorAll(`#teamUserDropdown input[value="${userId}"]`)
    .forEach((cb) => (cb.checked = false));

  renderSelectedMembers();
}

// =======================================================
// SECTION 5: CREATE TEAM (CONFIRMATION + LOADING)
// =======================================================
const createTeamForm = document.getElementById("createTeamForm");
if (createTeamForm) {
  createTeamForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const teamName = document.getElementById("team_name").value.trim();

    if (teamName === "") {
      alert("Team name is required.");
      return;
    }

    if (teamMembers.length === 0) {
      alert("Please select at least one team member.");
      return;
    }

    // 🔹 Confirmation before proceeding
    if (!confirm("Are you sure you want to create this team?")) {
      return;
    }

    const payload = {
      team_name: teamName,
      members: teamMembers.map((m) => m.id),
    };

    // 🔹 Show loading overlay
    showLoading();

    fetch("../backend/commendations/create_team.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((response) => {
        hideLoading();

        if (response.status === "success") {
          alert("Team created successfully!");

          // Reset modal state
          document.getElementById("createTeamForm").reset();
          teamMembers = [];
          renderSelectedMembers();

          // Uncheck all checkboxes
          document
            .querySelectorAll("#teamUserDropdown input[type='checkbox']")
            .forEach((cb) => (cb.checked = false));

          // Close modal
          bootstrap.Modal.getInstance(
            document.getElementById("createTeamModal"),
          ).hide();

          // Reload teams table
          loadTeams();
          loadUsersForTeam(); // 🔥 THIS WAS MISSING
        } else {
          alert(response.message || "Failed to create team.");
        }
      })
      .catch(() => {
        hideLoading();
        alert("Server error while creating team.");
      });
  });
}
// =======================================================
// SECTION 6: LOAD TEAMS TABLE (WITH MEMBER NAMES)
// =======================================================

function loadTeams() {
  const container = document.getElementById("teamsAccordion");
  if (!container) return;

  fetch("../backend/commendations/get_teams.php")
    .then((res) => {
      if (!res.ok) return null; // 🔒 stop here
      return res.json();
    })
    .then((teams) => {
      if (!teams) return; // user not allowed

      container.innerHTML = "";
      teams.forEach((team, index) => {
        const membersHtml = team.members.length
          ? team.members
              .map(
                (m) => `
              <tr>
                <td>${m.name}</td>
                <td>${m.role}</td>
                <td>
                  <span class="badge ${
                    m.status === "active" ? "bg-success" : "bg-secondary"
                  }">
                    ${m.status}
                  </span>
                </td>
              </tr>
            `,
              )
              .join("")
          : `
              <tr>
                <td colspan="3" class="text-muted text-center">
                  No members assigned
                </td>
              </tr>
            `;

        container.innerHTML += `
  <div class="accordion-item mb-2">
    <h2 class="accordion-header">
      <div class="accordion-button collapsed d-flex align-items-center"
           data-bs-toggle="collapse"
           data-bs-target="#team-${team.id}"
           style="cursor:pointer;">

        <!-- Centered Team Name -->
        <div class="flex-grow-1 text-center fw-bold">
          ${team.name}
        </div>

      </div>
    </h2>

            <div id="team-${team.id}" class="accordion-collapse collapse">
              <div class="accordion-body">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Name</th>
                      <th>User Type</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${membersHtml}
                  </tbody>
                </table>

                <div class="d-flex justify-content-end mb-2 gap-2">
                  <button class="btn btn-sm btn-success" onclick="viewTeam(${team.id})"><i class="fa-solid fa-eye"></i></button>
                  <button class="btn btn-sm btn-warning" onclick="editTeam(${team.id})"><i class="fa-solid fa-pen"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="confirmDeleteTeam(${team.id})"><i class="fa-solid fa-trash"></i></button>
                </div>

              </div>
            </div>
        `;
      });
    });
}

// =======================================================
// SECTION 7: VIEW TEAM (READ-ONLY MODAL)
// =======================================================
function viewTeam(teamId) {
  const modalEl = document.getElementById("viewTeamModal");
  if (!modalEl) return;
  showLoading();

  fetch(`../backend/commendations/get_team_details.php?team_id=${teamId}`)
    .then((res) => res.json())
    .then((response) => {
      hideLoading();

      if (response.status !== "success") {
        alert(response.message || "Failed to load team details.");
        return;
      }

      const team = response.team;
      const members = response.members;

      document.getElementById("viewTeamName").textContent = team.name;
      document.getElementById("viewTeamCreatedBy").textContent =
        team.created_by;
      document.getElementById("viewTeamCreatedAt").textContent = new Date(
        team.created_at,
      ).toLocaleString();

      const list = document.getElementById("viewTeamMembers");
      list.innerHTML = "";

      if (members.length === 0) {
        list.innerHTML = `
          <li class="list-group-item text-muted">
            No members in this team
          </li>
        `;
      } else {
        members.forEach((member) => {
          list.innerHTML += `
            <li class="list-group-item d-flex justify-content-between">
              <span>${member.name}</span>
              <span class="badge bg-secondary">${member.role}</span>
            </li>
          `;
        });
      }

      new bootstrap.Modal(document.getElementById("viewTeamModal")).show();
    })
    .catch(() => {
      hideLoading();
      alert("Server error while loading team.");
    });
}

// =======================================================
// SECTION 8: UPDATE TEAM
// =======================================================

function editTeam(teamId) {
  showLoading();

  fetch(`../backend/commendations/get_team_details.php?team_id=${teamId}`)
    .then((res) => res.json())
    .then((data) => {
      hideLoading();

      if (data.status !== "success") {
        alert("Failed to load team.");
        return;
      }

      document.getElementById("edit_team_id").value = teamId;
      document.getElementById("edit_team_name").value = data.team.name;

      // Set current members
      editTeamMembers = data.members.map((m) => ({
        id: m.id,
        name: m.name,
      }));

      renderEditSelectedMembers();
      loadEditAvailableUsers();

      new bootstrap.Modal(document.getElementById("editTeamModal")).show();
    })
    .catch(() => {
      hideLoading();
      alert("Server error.");
    });
}

function loadEditAvailableUsers() {
  fetch("../backend/commendations/get_available_team_users.php")
    .then((res) => res.json())
    .then((users) => {
      const dropdown = document.getElementById("editTeamUserDropdown");
      dropdown.innerHTML = "";

      const available = users.filter(
        (u) => !editTeamMembers.some((m) => String(m.id) === String(u.id)),
      );

      if (available.length === 0) {
        dropdown.innerHTML = `<li class="dropdown-item text-muted">No available users</li>`;
        return;
      }

      available.forEach((user) => {
        dropdown.innerHTML += `
          <li>
            <label class="dropdown-item d-flex align-items-center gap-2">
              <input type="checkbox"
                     value="${user.id}"
                     onchange="toggleEditTeamMember(this, '${user.first_name} ${user.last_name}')">
              ${user.first_name} ${user.last_name}
              <span class="badge bg-secondary ms-auto">${user.role}</span>
            </label>
          </li>
        `;
      });
    });
}

function toggleEditTeamMember(checkbox, name) {
  const userId = checkbox.value;

  if (checkbox.checked) {
    if (!editTeamMembers.some((m) => String(m.id) === String(userId))) {
      editTeamMembers.push({ id: userId, name });
    }
  } else {
    editTeamMembers = editTeamMembers.filter(
      (m) => String(m.id) !== String(userId),
    );
  }

  renderEditSelectedMembers();
}

function renderEditSelectedMembers() {
  const list = document.getElementById("editSelectedTeamMembers");
  list.innerHTML = "";

  if (editTeamMembers.length === 0) {
    list.innerHTML = `<li class="list-group-item text-muted">No members</li>`;
    return;
  }

  editTeamMembers.forEach((member) => {
    list.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        ${member.name}
        <button class="btn btn-sm btn-outline-danger"
                onclick="removeEditTeamMember('${member.id}')">
          <i class="fa fa-times"></i>
        </button>
      </li>
    `;
  });
}

function removeEditTeamMember(userId) {
  // Remove from selected
  editTeamMembers = editTeamMembers.filter(
    (m) => String(m.id) !== String(userId),
  );

  // Refresh UI
  renderEditSelectedMembers();
  loadEditAvailableUsers(); // so user reappears in dropdown
}

const editTeamForm = document.getElementById("editTeamForm");
if (editTeamForm) {
  editTeamForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const teamId = document.getElementById("edit_team_id").value;
    const teamName = document.getElementById("edit_team_name").value.trim();

    if (!teamName) {
      alert("Team name is required.");
      return;
    }

    if (!confirm("Save changes to this team?")) return;

    showLoading();

    fetch("../backend/commendations/update_team.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        team_id: teamId,
        team_name: teamName,
        members: editTeamMembers.map((m) => m.id),
      }),
    })
      .then((res) => res.json())
      .then((resp) => {
        hideLoading();

        if (resp.status === "success") {
          alert("Team updated successfully.");

          bootstrap.Modal.getInstance(
            document.getElementById("editTeamModal"),
          ).hide();

          loadTeams();
          loadUsersForTeam(); // 🔥 refresh create dropdown too
        } else {
          alert(resp.message || "Update failed.");
        }
      })
      .catch(() => {
        hideLoading();
        alert("Server error.");
      });
  });
}

// =======================================================
// SECTION 9: DELETE TEAM
// =======================================================

function confirmDeleteTeam(teamId) {
  if (!confirm("Are you sure you want to delete this team?")) return;

  showLoading();

  fetch("../backend/commendations/delete_team.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `team_id=${teamId}`,
  })
    .then((res) => res.json())
    .then((resp) => {
      hideLoading();
      if (resp.status === "success") {
        alert("Team deleted successfully.");
        loadTeams();
        loadUsersForTeam(); // 🔥 REQUIRED
      } else {
        alert(resp.message || "Delete failed.");
      }
    })
    .catch(() => {
      hideLoading();
      alert("Server error while deleting team.");
    });
}

// =======================================================
// SECTION 10: LOADING VALUES
// =======================================================
function loadValues() {
  fetch("../backend/commendations/get_values.php")
    .then((res) => res.json())
    .then((values) => {
      const tbody = document.querySelector("#valuesTable tbody");
      tbody.innerHTML = "";

      values.forEach((v) => {
        tbody.innerHTML += `
          <tr>
            <td>${v.name}</td>
            <td>${v.description || "-"}</td>
            <td class="text-center">
              <div class="form-check form-switch">
                <input class="form-check-input toggleValueStatus bg-success border-white" 
                       type="checkbox" 
                       data-id="${v.id}"
                       ${v.status === "active" ? "checked" : ""}>
                <label class="form-check-label">${v.status === "active" ? "Active" : "Inactive"}</label>
              </div>
            </td>
            <td>
              <button class="btn btn-sm btn-success" onclick="editValue(${
                v.id
              })">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteValue(${
                v.id
              })">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      });

      // Re-bind toggle switch events
      document.querySelectorAll(".toggleValueStatus").forEach((toggle) => {
        toggle.addEventListener("change", function () {
          const valueId = this.dataset.id;
          const newStatus = this.checked ? "active" : "inactive";
          const confirmMsg = `Are you sure you want to set this value as ${newStatus}?`;

          if (!confirm(confirmMsg)) {
            this.checked = !this.checked;
            return;
          }

          toggleValueStatus(valueId);
        });
      });
    });
}

document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector("#valuesTable tbody")) {
    loadValues();
  }
});

function toggleValueStatus(id) {
  fetch("../backend/commendations/toggle_value_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.status === "success") {
        loadValues();
      } else {
        alert(resp.message || "Failed to update status.");
      }
    });
}

function deleteValue(id) {
  if (!confirm("Delete this value?")) return;

  fetch("../backend/commendations/delete_value.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.status === "success") {
        alert("Value deleted successfully!");
        loadValues();
      } else {
        alert(resp.message || "Delete failed.");
      }
    });
}

// =======================================================
// SECTION 11: CREATE VALUE MODAL JS
// =======================================================

const createValueForm = document.getElementById("createValueForm");
if (createValueForm) {
  createValueForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const name = value_name.value.trim();
    const description = value_description.value.trim();

    if (!name) {
      alert("Value name is required.");
      return;
    }

    // 🔹 Confirmation before creating
    if (!confirm("Are you sure you want to create this value?")) {
      return;
    }

    fetch("../backend/commendations/create_value.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, description }),
    })
      .then((res) => res.json())
      .then((resp) => {
        if (resp.status === "success") {
          alert("Value created successfully!");
          bootstrap.Modal.getInstance(
            document.getElementById("createValueModal"),
          ).hide();
          loadValues();

          // Reset form
          document.getElementById("createValueForm").reset();
        } else {
          alert(resp.message || "Failed to create value.");
        }
      });
  });
}

// =======================================================
// SECTION 12: EDIT VALUE MODAL JS
// =======================================================

function editValue(id) {
  const modal = document.getElementById("editValueModal");
  if (!modal) return;
  fetch(`../backend/commendations/get_value.php?id=${id}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success") {
        alert(data.message || "Failed to fetch value.");
        return;
      }

      edit_value_id.value = data.value.id;
      edit_value_name.value = data.value.name;
      edit_value_desc.value = data.value.description;

      new bootstrap.Modal(document.getElementById("editValueModal")).show();
    });
}

function saveValueEdit() {
  const id = edit_value_id.value.trim();
  const name = edit_value_name.value.trim();
  const description = edit_value_desc.value.trim();

  if (!name) {
    alert("Value name is required.");
    return;
  }

  // 🔹 Confirmation before updating
  if (!confirm("Are you sure you want to update this value?")) {
    return;
  }

  fetch("../backend/commendations/update_value.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, name, description }),
  })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.status === "success") {
        alert("Value updated successfully!");
        bootstrap.Modal.getInstance(
          document.getElementById("editValueModal"),
        ).hide();
        loadValues();
      } else {
        alert(resp.message || "Update failed.");
      }
    });
}

// =======================================================
// SECTION 13: LOAD USERS FOR COMMEND
// =======================================================

function loadUsersForCommend() {
  const modalEl = document.getElementById("commendModal");
  if (!modalEl) return;

  // ✅ IMPORTANT: scope to the modal to avoid duplicate-ID issues
  const dropdown = modalEl.querySelector("#commendUserDropdown");
  if (!dropdown) return;

  // show something immediately so you know it's targeting the right element
  dropdown.innerHTML = `<li class="dropdown-item text-muted">Loading users...</li>`;

  fetch("../backend/get_all_users.php")
    .then((res) => res.json())
    .then((response) => {
      const users = response.users || response.data || response;

      if (!Array.isArray(users) || users.length === 0) {
        dropdown.innerHTML = `<li class="dropdown-item text-muted">No users available</li>`;
        return;
      }

      dropdown.innerHTML = "";

      users.forEach((user) => {
        const li = document.createElement("li");

        const label = document.createElement("label");
        label.className = "dropdown-item d-flex align-items-center gap-2";

        const cb = document.createElement("input");
        cb.type = "checkbox";
        cb.value = user.id;

        const fullName = `${user.first_name} ${user.last_name}`;

        // ✅ avoids breaking when names have apostrophes, quotes, etc.
        cb.addEventListener("change", () => toggleCommendUser(cb, fullName));

        const nameNode = document.createTextNode(fullName);

        const badge = document.createElement("span");
        badge.className = "badge bg-secondary ms-auto";
        badge.textContent = user.role ?? "";

        label.appendChild(cb);
        label.appendChild(nameNode);
        label.appendChild(badge);

        li.appendChild(label);
        dropdown.appendChild(li);
      });

      // quick sanity check
      console.log("✅ Dropdown items:", dropdown.children.length);
    })
    .catch((err) => {
      console.error("Failed loading users:", err);
      dropdown.innerHTML = `<li class="dropdown-item text-danger">Failed to load users</li>`;
    });
}

//COMMENDATION DEDUCTION HELPERS
function isDeductMode() {
  return String(commendationMode || "").toLowerCase() === "deduct";
}

function getCommendCopy() {
  const deduct = isDeductMode();

  return {
    // Modal header + button
    modalTitle: deduct ? "Deduct Employee" : "Commend Employee",
    submitText: deduct ? "Submit Deduction" : "Submit Commendation",
    submitBtnClass: deduct ? "btn btn-danger" : "btn btn-success",

    // Confirmation prompt
    actionWord: deduct ? "deduction" : "commendation",
    actionWordTitleCase: deduct ? "Deduction" : "Commendation",

    // After submit
    successAlert: deduct
      ? "Deduction submitted for approval."
      : "Commendation submitted for approval.",

    // Optional: if you want form label changes later
    // pointsLabel: deduct ? "Deduction Points" : "Commendation Points"
  };
}

//COMMEND / DEDUCT MODAL IN ONE FUNCTION
function openCommendationModal(type = "commend") {
  commendationMode = type;

  commendUsers = [];
  renderSelectedCommendUsers();

  const form = document.getElementById("commendForm");
  if (form) form.reset();

  document.querySelectorAll(".point-btn").forEach((b) => {
    b.classList.remove("btn-outline-success", "btn-outline-danger");
  })

  const title = document.querySelector("#commendModal .modal-title");
  const submitBtn = document.querySelector("#commendForm button[type=submit]");

  if (title && submitBtn) {
    const copy = getCommendCopy();
    title.textContent = copy.modalTitle;
    submitBtn.className = copy.submitBtnClass;
    submitBtn.textContent = copy.submitText;
  }

  const modalEl = document.getElementById("commendModal");
  const modal = new bootstrap.Modal(modalEl);

  // ✅ load after shown to avoid timing issues
  modalEl.addEventListener(
    "shown.bs.modal",
    () => {
      loadUsersForCommend();
      loadActiveValues();
    },
    { once: true },
  );

  modal.show();
}

// Point Buttons
const pointButtons = document.querySelectorAll(".point-btn");
if (pointButtons.length) {
  pointButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      const pointsInput = document.getElementById("commend_points");
      if (!pointsInput) return;

      pointsInput.value = this.dataset.point;

      const outlineClass = isDeductMode()
        ? "btn-outline-danger"
        : "btn-outline-success";

      // Remove both possible outline classes, then add the right one
      document.querySelectorAll(".point-btn").forEach((b) => {
        b.classList.remove("btn-outline-success", "btn-outline-danger");
      });

      this.classList.add(outlineClass);
    });
  });
}

function toggleCommendUser(cb, name) {
  const id = cb.value;

  if (cb.checked) {
    if (!commendUsers.some((u) => u.id === id)) {
      commendUsers.push({ id, name });
    }
  } else {
    commendUsers = commendUsers.filter((u) => u.id !== id);
  }

  renderSelectedCommendUsers();
}

function renderSelectedCommendUsers() {
  const list = document.getElementById("selectedCommendUsers");
  if (!list) return;
  list.innerHTML = "";

  if (commendUsers.length === 0) {
    list.innerHTML = `<li class="list-group-item text-muted">No users selected</li>`;
    return;
  }

  commendUsers.forEach((u) => {
    list.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        ${u.name}
        <button class="btn btn-sm btn-outline-danger"
                onclick="removeCommendUser('${u.id}')">
          <i class="fa fa-times"></i>
        </button>
      </li>
    `;
  });
}

function removeCommendUser(id) {
  commendUsers = commendUsers.filter((u) => u.id !== id);

  const dropdown = document.getElementById("commendUserDropdown");
  if (dropdown) {
    dropdown
      .querySelectorAll(`input[value="${id}"]`)
      .forEach((cb) => (cb.checked = false));
  }

  renderSelectedCommendUsers();
}

// =======================================================
// SECTION 14: LOAD ACTIVE VALUES FOR DROPDOWN
// =======================================================

function loadActiveValues() {
  const select = document.getElementById("commend_value");
  if (!select) return;

  fetch("../backend/commendations/get_values.php")
    .then((res) => res.json())
    .then((values) => {
      const select = document.getElementById("commend_value");
      select.innerHTML = `<option value="">-- Select Value --</option>`;

      values
        .filter((v) => v.status === "active")
        .forEach((v) => {
          select.innerHTML += `
            <option value="${v.id}">${v.name}</option>
          `;
        });
    });
}

// =======================================================
// SECTION 15: SUBMIT COMMENDATION
// =======================================================

const commendForm = document.getElementById("commendForm");
if (commendForm) {
  commendForm.addEventListener("submit", (e) => {
    e.preventDefault();

    if (commendUsers.length === 0) {
      alert("Please select at least one user.");
      return;
    }

    // Create a summary of selected users, points, and value for confirmation
    const userNames = commendUsers.map((u) => u.name).join(", ");
    const points = commend_points.value || "0";
    const valueText = commend_value.selectedOptions[0]?.text || "(No Value)";
    const reasonText = commend_reason.value.trim();

    const copy = getCommendCopy();

    const confirmMsg =
      `You are about to submit a ${copy.actionWord} with the following details:\n\n` +
      `Users: ${userNames}\n` +
      `Points: ${points}\n` +
      `Value: ${valueText}\n` +
      `Reason: ${reasonText}\n\n` +
      `Do you want to proceed?`;

    if (!confirm(confirmMsg)) {
      // User canceled
      return;
    }

    const payload = new FormData();
    payload.append("points", points);
    payload.append("reason", reasonText);
    payload.append("value_id", commend_value.value);
    //payload.append("type", "commend");
    payload.append("type", commendationMode);

    commendUsers.forEach((u) => {
      payload.append("user_ids[]", u.id);
    });

    if (commend_attachment.files[0]) {
      payload.append("attachment", commend_attachment.files[0]);
    }

    fetch("../backend/commendations/create_commendation.php", {
      method: "POST",
      body: payload,
    })
      .then((res) => res.json())
      .then((resp) => {
        if (resp.status === "success") {
          alert(getCommendCopy().successAlert);
          bootstrap.Modal.getInstance(
            document.getElementById("commendModal"),
          ).hide();
          commendForm.reset();
          commendUsers = [];
        } else {
          alert(resp.message || "Failed.");
        }
      });
  });
}
