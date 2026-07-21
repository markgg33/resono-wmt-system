let externalCurrentUser = null;
let externalSelectedUsers = [];
let externalUsers = [];
const DEV_MODE = false; // 🔥 switch this ON/OFF

// ================= GOOGLE LOGIN =================
function handleCredentialResponse(response) {
  fetch("../backend/external/verify_google_user.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ token: response.credential }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        externalCurrentUser = data;

        document.getElementById("preLoginLayout").classList.add("hidden");

        document.getElementById("postLoginLayout").classList.remove("hidden");

        const userInfo = document.getElementById("externalUserInfo");

        const form = document.getElementById("externalCommendForm");

        document.getElementById("preLoginLayout").classList.add("hidden");

        document.getElementById("postLoginLayout").classList.remove("hidden");

        userInfo.classList.remove("hidden");
        form.classList.remove("hidden");

        userInfo.innerText = `Logged in as: ${data.name} (${data.email})`;

        document
          .getElementById("resonoValuesSection")
          .classList.remove("hidden");

        loadExternalUsers();
        loadExternalValues();
      } else {
        alert("Google verification failed");
      }
    });
}

// ================= LOAD USERS =================
/*
function loadExternalUsers() {
  fetch("../backend/get_all_users.php")
    .then((res) => res.json())
    .then((response) => {
      const users = response.users || response.data || response;

      const dropdown = document.getElementById("externalUserDropdown");
      dropdown.innerHTML = "";

      users.forEach((user) => {
        const li = document.createElement("li");

        const fullName = `${user.first_name} ${user.last_name}`;

        li.innerHTML = `
          <label class="dropdown-item d-flex align-items-center gap-2">
            <input type="checkbox" value="${user.id}">
            ${fullName}
            <!--span class="badge bg-secondary ms-auto">${user.role ?? ""}</span-->
          </label>
        `;

        li.querySelector("input").addEventListener("change", (e) => {
          toggleExternalUser(e.target, fullName);
        });

        dropdown.appendChild(li);
      });
    })
    .catch((err) => console.error(err));
}*/

function loadExternalUsers() {
  fetch("../backend/get_all_users.php")
    .then((res) => res.json())
    .then((response) => {
      externalUsers = response.users || response.data || response;

      initEmployeeSearch();
    });
}

// ================= SEARCH USERS ==================

function initEmployeeSearch() {
  const input = document.getElementById("employeeSearch");
  const suggestions = document.getElementById("employeeSuggestions");

  input.addEventListener("input", function () {
    const keyword = this.value.trim().toLowerCase();

    suggestions.innerHTML = "";

    if (!keyword) {
      suggestions.style.display = "none";
      return;
    }

    const matches = externalUsers.filter((user) => {
      const fullname = `${user.first_name} ${user.last_name}`.toLowerCase();

      const alreadySelected = externalSelectedUsers.some(
        (u) => u.id == user.id,
      );

      return fullname.includes(keyword) && !alreadySelected;
    });

    if (matches.length === 0) {
      suggestions.style.display = "none";
      return;
    }

    matches.forEach((user) => {
      const fullname = `${user.first_name} ${user.last_name}`;

      const item = document.createElement("div");

      item.className = "employee-item";

      item.innerHTML = fullname;

      item.onclick = () => {
        externalSelectedUsers.push({
          id: user.id,
          name: fullname,
        });

        renderExternalUsers();

        input.value = "";

        suggestions.innerHTML = "";

        suggestions.style.display = "none";
      };

      suggestions.appendChild(item);
    });

    suggestions.style.display = "block";
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".employee-search-wrapper")) {
      suggestions.style.display = "none";
    }
  });
}

// ================= TOGGLE USERS =================
function toggleExternalUser(cb, name) {
  const id = cb.value;

  if (cb.checked) {
    externalSelectedUsers.push({ id, name });
  } else {
    externalSelectedUsers = externalSelectedUsers.filter((u) => u.id !== id);
  }

  renderExternalUsers();
}

function renderExternalUsers() {
  const list = document.getElementById("externalSelectedUsers");
  list.innerHTML = "";

  if (externalSelectedUsers.length === 0) {
    list.innerHTML = `<li class="list-group-item text-muted">No users selected</li>`;
    return;
  }

  externalSelectedUsers.forEach((u) => {
    list.innerHTML += `
      <li class="list-group-item d-flex p-1 justify-content-between align-items-center">
        ${u.name}
        <button class="btn btn-sm btn-outline-danger px-2 py-0 gap-1"
          onclick="removeExternalUser('${u.id}')">
          ✕
        </button>
      </li>
    `;
  });
}

function removeExternalUser(id) {
  externalSelectedUsers = externalSelectedUsers.filter((u) => u.id !== id);

  /*document
    .querySelectorAll(`#externalUserDropdown input[value="${id}"]`)
    .forEach((cb) => (cb.checked = false));*/

  renderExternalUsers();
}

// ================= LOAD VALUES =================
function loadExternalValues() {
  fetch("../backend/commendations/get_values.php")
    .then((res) => res.json())
    .then((values) => {
      const select = document.getElementById("external_value");

      select.innerHTML = `<option value="">Select Value</option>`;

      values
        .filter((v) => v.status === "active")
        .forEach((v) => {
          select.innerHTML += `<option value="${v.id}">${v.name}</option>`;
        });
    })
    .catch((err) => {
      console.error("Values load error:", err);
    });
}

// ================= POINT BUTTONS =================
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("external-point-btn")) {
    document.getElementById("external_points").value = e.target.dataset.point;
  }
});

// ================= SUBMIT =================
document.addEventListener("DOMContentLoaded", () => {
  const userInfo = document.getElementById("externalUserInfo");
  const form = document.getElementById("externalCommendForm");

  if (DEV_MODE) {
    console.log("🚀 DEV MODE ACTIVE");

    externalCurrentUser = {
      name: "Dev Tester",
      email: "dev@test.com",
    };

    document.getElementById("preLoginLayout").classList.add("hidden");

    document.getElementById("postLoginLayout").classList.remove("hidden");

    userInfo.classList.remove("hidden");
    form.classList.remove("hidden");
    userInfo.innerText = `DEV MODE: ${externalCurrentUser.name}`;

    // SHOW VALUES TABLE
    document.getElementById("resonoValuesSection").classList.remove("hidden");

    loadExternalUsers();
    loadExternalValues();
  } else {
    console.log("🔐 Waiting for Google login...");

    // Do nothing here — wait for Google callback
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("externalCommendForm");

  let isSubmitting = false;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    if (isSubmitting) return;
    isSubmitting = true;

    if (externalSelectedUsers.length === 0) {
      alert("Please select at least one user.");
      isSubmitting = false;
      return;
    }

    const points = document.getElementById("external_points").value;
    const valueText =
      document.getElementById("external_value").selectedOptions[0]?.text;
    const reason = document.getElementById("external_reason").value;

    const userNames = externalSelectedUsers.map((u) => u.name).join(", ");

    const confirmMsg =
      `You are about to submit a commendation:\n\n` +
      `Users: ${userNames}\n` +
      `Points: ${points}\n` +
      `Value: ${valueText}\n` +
      `Reason: ${reason}\n\n` +
      `Do you want to proceed?`;

    if (!confirm(confirmMsg)) {
      isSubmitting = false;
      return;
    }

    const payload = {
      user_ids: externalSelectedUsers.map((u) => u.id),
      value_id: document.getElementById("external_value").value,
      points: document.getElementById("external_points").value,
      reason: document.getElementById("external_reason").value,
      email: externalCurrentUser.email,
      name: externalCurrentUser.name,
    };

    fetch("../backend/external/submit_external_commendation.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((resp) => {
        isSubmitting = false;

        /*if (resp.status === "success") {
          alert("✅ Commendation submitted successfully!");
          location.reload();
        }*/

        if (resp.status === "success") {
          const successData = {
            sender: externalCurrentUser.name,
            recipients: externalSelectedUsers.map((u) => u.name),
            points: payload.points,
            value: valueText,
          };

          sessionStorage.setItem(
            "commendationSuccess",
            JSON.stringify(successData),
          );

          window.location.href = "thank-you.php";
        } else {
          alert(resp.message);
        }
      });
  });
});

document.querySelectorAll(".external-point-btn").forEach((btn) => {
  btn.addEventListener("click", function () {
    const value = this.dataset.point;

    document.getElementById("external_points").value = value;

    document.querySelectorAll(".external-point-btn").forEach((b) => {
      b.classList.remove("active-point");
    });

    this.classList.add("active-point");
  });
});
