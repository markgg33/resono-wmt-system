let announcementPage = 1;
let dashboardPage = 1;

const ANNOUNCEMENT_LIMIT = 10;
const DASHBOARD_LIMIT = 5;
const ANNOUNCEMENT_PUBLIC_LIMIT = 5;

document.addEventListener("DOMContentLoaded", () => {
  loadAnnouncements();
  loadDashboardAnnouncements();

  const lastPage = localStorage.getItem("lastPage");

  if (lastPage === "announcement-list") {
    loadAnnouncementList(1);
  }
});

function openAnnouncementList() {
  changePage("announcement-list");
  localStorage.setItem("lastPage", "announcement-list");
  loadAnnouncementList(1);
}

function submitAnnouncement() {
  const modalEl = document.getElementById("createAnnouncementModal");
  const editId = modalEl.dataset.editId;

  if (!confirm("Are you sure you want to post this announcement?")) return;

  const formData = new FormData();

  formData.append("title", document.getElementById("announcementTitle").value);
  formData.append(
    "description",
    document.getElementById("announcementDescription").value,
  );

  const image = document.getElementById("announcementImage").files[0];

  if (image) formData.append("image", image);

  if (editId) formData.append("id", editId);

  fetch("../backend/announcements/save_announcement.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      alert(data.message);

      bootstrap.Modal.getInstance(modalEl).hide();

      modalEl.dataset.editId = "";

      loadAnnouncements();
      loadDashboardAnnouncements();
    });
}

/*function loadDashboardAnnouncements(page = 1) {
  dashboardPage = page;

  fetch(
    `../backend/announcements/get_announcements.php?page=${page}&limit=${DASHBOARD_LIMIT}`,
  )
    .then((res) => res.json())
    .then((result) => {
      const container = document.getElementById("dashboardAnnouncements");

      container.innerHTML = "";

      if (result.data.length === 0) {
        container.innerHTML = `
        <div class="col-12 text-center mt-5">
            <h5 class="text-muted">No announcements available.</h5>
        </div>`;
        return;
      }

      result.data.forEach((a) => {
        container.innerHTML += `
<div class="col-12 d-flex justify-content-center mb-4">

<div class="card shadow-sm" style="max-width:1080px;width:100%;">

${
  a.image
    ? `<img src="../uploads/${a.image}" class="card-img-top" style="max-height:700px;object-fit:cover;">`
    : ""
}

<div class="card-body">

<h4 class="fw-bold">${a.title}</h4>

<p class="mt-3">${a.description}</p>

<small class="text-muted">
Published: ${a.created_at}
</small>

</div>
</div>
</div>`;
      });

      renderDashboardPagination(result.totalPages);
    });
}*/

function loadAnnouncementList(page = 1) {
  announcementPage = page;
  fetch(
    `../backend/announcements/get_announcements.php?page=${page}&limit=${ANNOUNCEMENT_PUBLIC_LIMIT}`,
  )
    .then((res) => res.json())
    .then((result) => {
      const container = document.getElementById("announcementListContainer");

      container.innerHTML = "";

      if (result.data.length === 0) {
        container.innerHTML = `
        <div class="text-center text-muted mt-5">
            No announcements available.
        </div>`;
        return;
      }

      result.data.forEach((a) => {
        container.innerHTML += `
<div class="col-lg-8 col-md-10 col-12 mx-auto mb-4">

${renderAnnouncementCard(a)}

</div>`;
      });

      renderAnnouncementListPagination(result.totalPages);
    });
}

function loadDashboardAnnouncements() {
  fetch(
    `../backend/announcements/get_announcements.php?page=1&limit=${DASHBOARD_LIMIT}`,
  )
    .then((res) => res.json())
    .then((result) => {
      const container = document.getElementById("dashboardAnnouncements");

      if (result.data.length === 0) {
        container.innerHTML = `
        <div class="text-center text-muted mt-4">
            No announcements available.
        </div>`;
        return;
      }

      let slides = "";

      result.data.forEach((a, index) => {
        slides += `
<div class="carousel-item ${index === 0 ? "active" : ""}">

${renderAnnouncementCard(a)}

</div>`;
      });

      container.innerHTML = `
<div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel">

<div class="carousel-inner">
${slides}
</div>

<button class="carousel-control-prev" type="button"
data-bs-target="#announcementCarousel"
data-bs-slide="prev">

<span class="carousel-control-prev-icon"></span>

</button>

<button class="carousel-control-next" type="button"
data-bs-target="#announcementCarousel"
data-bs-slide="next">

<span class="carousel-control-next-icon"></span>

</button>

</div>
`;
    });
}

function loadAnnouncements(page = 1) {
  announcementPage = page;

  fetch(
    `../backend/announcements/get_all_announcements.php?page=${page}&limit=${ANNOUNCEMENT_LIMIT}`,
  )
    .then((res) => res.json())
    .then((result) => {
      const tbody = document.querySelector("#announcementTable tbody");

      tbody.innerHTML = "";

      if (result.data.length === 0) {
        tbody.innerHTML = `
<tr>
<td colspan="5" class="text-center text-muted">
No announcements available.
</td>
</tr>`;
        return;
      }

      result.data.forEach((a) => {
        tbody.innerHTML += createAnnouncementRow(a);
      });

      renderAnnouncementPagination(result.totalPages);
    });
}

/*function createAnnouncementRow(a) {
  return `

<tr>

<td>${a.created_at}</td>
<td>${a.title}</td>
<td>${a.description}</td>

<td>
<span class="badge ${a.status == "active" ? "bg-success" : "bg-secondary"}">
${a.status}
</span>
</td>

<td>

<button class="btn btn-sm btn-primary"
onclick="editAnnouncement(${a.id})">
Edit
</button>

<button class="btn btn-sm btn-warning"
onclick="toggleAnnouncement(${a.id})">
Toggle
</button>

<button class="btn btn-sm btn-danger"
onclick="deleteAnnouncement(${a.id})">
Delete
</button>

</td>

</tr>

`;
}*/

function createAnnouncementRow(a) {
  return `

<tr>

<td>${a.created_at}</td>

<td>${a.title}</td>

<td>${a.description}</td>

<td>

<div class="form-check form-switch">

<input class="form-check-input"
type="checkbox"
${a.status === "active" ? "checked" : ""}
onchange="toggleAnnouncement(${a.id}, this)">

<span class="ms-2 text-success fw-bold">
${a.status}
</span>

</div>

</td>

<td>

<button class="btn btn-sm btn-primary"
onclick="editAnnouncement(${a.id})">

<i class="fa-solid fa-pen"></i>

</button>

<button class="btn btn-sm btn-danger"
onclick="deleteAnnouncement(${a.id})">

<i class="fa-solid fa-trash"></i>

</button>

</td>

</tr>

`;
}

function createAnnouncements() {
  document.getElementById("announcementTitle").value = "";
  document.getElementById("announcementDescription").value = "";
  document.getElementById("announcementImage").value = "";

  const modal = new bootstrap.Modal(
    document.getElementById("createAnnouncementModal"),
  );

  modal.show();
}

function filterAnnouncements(page = 1) {
  const start = document.getElementById("announcementStartDate").value;
  const end = document.getElementById("announcementendDate").value;

  fetch(
    `../backend/announcements/get_all_announcements.php?page=${page}&limit=${ANNOUNCEMENT_LIMIT}&start=${start}&end=${end}`,
  )
    .then((res) => res.json())
    .then((result) => {
      const tbody = document.querySelector("#announcementTable tbody");

      tbody.innerHTML = "";

      if (result.data.length === 0) {
        tbody.innerHTML = `
<tr>
<td colspan="5" class="text-center text-muted">
No announcements created within date range.
</td>
</tr>`;
        return;
      }

      result.data.forEach((a) => {
        tbody.innerHTML += createAnnouncementRow(a);
      });

      renderAnnouncementPagination(result.totalPages);
    });
}

function toggleAnnouncement(id, checkbox) {
  const confirmed = confirm("Change announcement status?");

  if (!confirmed) {
    // revert the checkbox state if cancelled
    checkbox.checked = !checkbox.checked;
    return;
  }

  fetch("../backend/announcements/toggle_announcement.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((res) => res.json())
    .then(() => {
      loadAnnouncements();
    })
    .catch(() => {
      // revert if request fails
      checkbox.checked = !checkbox.checked;
    });
}

function deleteAnnouncement(id) {
  if (!confirm("Delete this announcement permanently?")) return;

  fetch("../backend/announcements/delete_announcement.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((res) => res.json())
    .then((data) => {
      alert("Announcement deleted successfully.");

      loadAnnouncements();
      loadDashboardAnnouncements();
    });
}

function editAnnouncement(id) {
  fetch(`../backend/announcements/get_single_announcement.php?id=${id}`)
    .then((res) => res.json())
    .then((data) => {
      document.getElementById("announcementTitle").value = data.title;
      document.getElementById("announcementDescription").value =
        data.description;

      document.getElementById("createAnnouncementModal").dataset.editId = id;

      const modal = new bootstrap.Modal(
        document.getElementById("createAnnouncementModal"),
      );
      modal.show();
    });
}

function renderDashboardPagination(totalPages) {
  const container = document.getElementById("dashboardAnnouncements");

  let pagination = `<div class="col-12 text-center mt-3">`;

  for (let i = 1; i <= totalPages; i++) {
    pagination += `
    <button class="btn btn-sm ${
      i === dashboardPage ? "btn-primary" : "btn-outline-primary"
    } me-1"
    onclick="loadDashboardAnnouncements(${i})">
    ${i}
    </button>`;
  }

  pagination += `</div>`;

  container.innerHTML += pagination;
}

function renderAnnouncementPagination(totalPages) {
  let container = document.getElementById("announcementPagination");

  if (!container) {
    container = document.createElement("div");
    container.id = "announcementPagination";
    container.className = "text-center mt-3";
    document.getElementById("announcementTable").after(container);
  }

  container.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    container.innerHTML += `
<button class="btn btn-sm ${
      i === announcementPage ? "btn-primary" : "btn-outline-primary"
    } me-1"
onclick="loadAnnouncements(${i})">
${i}
</button>`;
  }
}

function renderAnnouncementListPagination(totalPages) {
  const container = document.getElementById("announcementListPagination");

  container.innerHTML = "";

  if (totalPages <= 1) return;

  for (let i = 1; i <= totalPages; i++) {
    container.innerHTML += `
      <button class="btn btn-sm ${
        i === announcementPage ? "btn-success" : "btn-outline-success"
      } me-1"
      onclick="loadAnnouncementList(${i})">
      ${i}
      </button>
    `;
  }
}

function renderAnnouncementCard(a) {
  return `
<div class="card shadow-sm mx-auto announcement-carousel-card">

  ${
    a.image
      ? `<img src="../uploads/${a.image}" 
              class="card-img-top announcement-img">`
      : ""
  }

  <div class="card-body">

    <h4 class="fw-bold">${a.title}</h4>

    <p class="mt-3">${a.description}</p>

    <small class="text-muted">
      Published: ${a.created_at}
    </small>

  </div>

</div>
`;
}
