// ============================================
// ========== GLOBAL VARIABLES ===============
// ============================================
let lastTaskRow = null;

// ============================================
// ========== TIME HELPERS ===================
// ============================================

/**
 * Format JS Date object to "HH:MM:SS" for DB insertion
 */
function formatTime(date) {
  return date.toTimeString().split(" ")[0]; // "HH:MM:SS"
}

/**
 * Format JS Date object to "YYYY-MM-DD" for DB (if needed)
 */
function formatDateForDatabase(date) {
  return date.toISOString().split("T")[0]; // "YYYY-MM-DD"
}

/**
 * Format JS Date object to "MMM DD, YYYY" for display (e.g., Jul 9, 2025)
 */
function formatDateForDisplay(date) {
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

/**
 * Format "HH:MM:SS" string into "HH:MM" (ignoring seconds), or "--" if empty/invalid
 */
function formatToHHMM(value) {
  if (!value || value === "00:00:00   ") return "--";
  const [h, m] = value.split(":");
  return `${h}:${m}`;
}

/**
 * Calculate duration from start to end time (both in "HH:MM:SS") as "HH:MM:SS"
 */
function calculateTimeSpent(start, end) {
  const [sh, sm, ss] = start.split(":").map(Number);
  const [eh, em, es] = end.split(":").map(Number);

  const startSeconds = sh * 3600 + sm * 60 + ss;
  const endSeconds = eh * 3600 + em * 60 + es;
  const diff = endSeconds - startSeconds;

  if (diff <= 0) return "00:00:00"; // handle invalid or zero durations

  const hours = Math.floor(diff / 3600);
  const minutes = Math.floor((diff % 3600) / 60);
  const seconds = diff % 60;

  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(
    2,
    "0"
  )}:${String(seconds).padStart(2, "0")}`;
}

// Get local YYYY-MM-DD (based on your timezone)
function getLocalDateString() {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, "0");
  const dd = String(now.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

//COMPUTE TIME DIFFERENCE
function computeTimeDiff(start, end) {
  const startDate = new Date(`1970-01-01T${start}`);
  const endDate = new Date(`1970-01-01T${end}`);

  let diffMs = endDate - startDate;
  if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000; // handle overnight spans

  const hours = Math.floor(diffMs / (1000 * 60 * 60));
  const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(
    2,
    "0"
  )}`;
}

function ensureHHMMSS(timeStr) {
  if (!timeStr || timeStr === "--") return "--";
  const parts = timeStr.trim().split(":");
  if (parts.length === 2) {
    // Only HH:MM provided, add :00 seconds
    return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}:00`;
  } else if (parts.length === 3) {
    // Already HH:MM:SS
    return parts.map((p) => p.padStart(2, "0")).join(":");
  } else {
    return timeStr; // fallback
  }
}

// ============================================
// ========== ADD REMARKS CELL ===============
// ============================================

function addRemarksCell(row, taskId, initialRemarks = "") {
  const cell = row.insertCell(6);
  cell.className = "remarks-cell";
  const inputId = `remarks_${taskId}`;
  const btnId = `saveRemarksBtn_${taskId}`;

  cell.innerHTML = `
      <div class="d-flex gap-1 align-items-center">
        <input type="text" id="${inputId}" value="${initialRemarks}" class="form-control form-control-sm" data-task-id="${taskId}" />
        <button class="btn btn-sm btn-success" id="${btnId}"><i class="fa-solid fa-floppy-disk"></i></button>
      </div>
    `;

  document.getElementById(btnId).addEventListener("click", () => {
    const input = document.getElementById(inputId);
    const value = input.value.trim();

    fetch("../backend/update_remarks.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: taskId, remarks: value }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status !== "success") {
          alert("Failed to save remarks.");
        } else {
          alert("Remarks saved.");
        }
      })
      .catch(() => alert("Error saving remarks."));
  });
}

// ============================================
// ========== ACTION BUTTONS CELL ===============
// ============================================

function addActionButtonsCell(row, log) {
  const cell = row.insertCell(-1); // put at the end as "Actions"
  cell.classList.add("text-center");

  // Edit button
  const editBtn = document.createElement("button");
  editBtn.className = "btn btn-sm btn-warning me-1"; // spacing
  editBtn.innerHTML = `<i class="fa-solid fa-pen-to-square"></i>`;
  editBtn.addEventListener("click", () => {
    openEditTaskLogModal(log.id, log.task_description);
  });

  // Amendment button
  const amendBtn = document.createElement("button");
  amendBtn.className = "btn btn-sm btn-success";
  amendBtn.innerHTML = `<i class="fa-solid fa-clock-rotate-left"></i>`; // icon for amendment
  amendBtn.addEventListener("click", () => {
    // Fill modal fields with log details
    document.getElementById("logId").value = log.id;
    document.getElementById("amendDate").value = log.date
      ? formatDateForDisplay(new Date(log.date))
      : "--";

    // Default: show current start_time in Old Value
    document.getElementById("oldValue").value = ensureHHMMSS(log.start_time);

    // Reset new value + reason
    document.getElementById("newValue").value = "";
    document.getElementById("reason").value = "";

    // Handle "Field to Amend" dropdown changes
    const fieldSelect = document.getElementById("field");
    fieldSelect.onchange = () => {
      switch (fieldSelect.value) {
        case "start_time":
          document.getElementById("oldValue").value = ensureHHMMSS(
            log.start_time
          );
          document.getElementById("newValue").type = "time";
          break;
        case "end_time":
          document.getElementById("oldValue").value = log.end_time
            ? ensureHHMMSS(log.end_time)
            : "--";
          document.getElementById("newValue").type = "time";
          break;
        case "remarks":
          document.getElementById("oldValue").value = log.remarks || "--";
          document.getElementById("newValue").type = "text";
          break;
      }
    };

    // Default dropdown → set "start_time"
    fieldSelect.value = "start_time";
    document.getElementById("newValue").type = "time";

    // Show modal
    const modal = new bootstrap.Modal(
      document.getElementById("userAmendmentModal")
    );
    modal.show();
  });

  // Append both buttons
  cell.appendChild(editBtn);
  cell.appendChild(amendBtn);
}
// ============================================
// ========== START TASK LOGIC ===============
// ============================================

function startTask() {
  const workModeSelect = document.getElementById("workModeSelector");
  const taskSelect = document.getElementById("taskSelector");
  const remarksInput = document.getElementById("remarksInput");

  const workMode = workModeSelect.value;
  const taskDescriptionId = taskSelect.value;
  const remarks = remarksInput?.value || "";

  if (!workMode || !taskDescriptionId) {
    alert("Please select both Work Mode and Task.");
    return;
  }

  const now = new Date();
  const startTime = now.toTimeString().split(" ")[0]; // "HH:MM:SS"

  const dbDate = getLocalDateString();

  const displayDate = formatDateForDisplay(now);

  const userId = sessionStorage.getItem("user_id");

  const tableBody = document.querySelector("#wmtLogTable tbody");

  // Close previous task if still active
  // Close previous task only if it's from the same date and still open
  if (
    lastTaskRow &&
    (!lastTaskRow.cells[4].textContent ||
      lastTaskRow.cells[4].textContent === "--")
  ) {
    const lastTaskDateText = lastTaskRow.cells[0].textContent;
    const todayDisplayDate = formatDateForDisplay(new Date());

    if (lastTaskDateText === todayDisplayDate) {
      const prevStart = lastTaskRow.cells[3].textContent + ":00"; // add seconds back
      const prevEnd = startTime;
      const duration = calculateTimeSpent(prevStart, prevEnd);

      lastTaskRow.cells[4].textContent = prevEnd;
      lastTaskRow.cells[5].textContent = duration;
      lastTaskRow.classList.remove("active-task");

      const prevTaskId = lastTaskRow.dataset.taskId;

      // Optionally update backend for old task
      fetch("../backend/update_task_log.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id: prevTaskId,
          end_time: prevEnd,
          duration: duration,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          console.log("Update Task Response:", data);
        });
    }
  }

  // Create new task row
  const newRow = document.createElement("tr");
  newRow.classList.add("active-task");
  newRow.innerHTML = `
    <td>${displayDate}</td>
    <td>${workModeSelect.options[workModeSelect.selectedIndex].text}</td>
    <td>${taskSelect.options[taskSelect.selectedIndex].text}</td>
    <td>${startTime}</td>
    <td>--</td>
    <td>--</td>
`;
  tableBody.appendChild(newRow);

  fetch("../backend/insert_task_logs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      user_id: userId,
      work_mode_id: workMode,
      task_description_id: taskDescriptionId,
      date: dbDate,
      start_time: startTime,
      remarks: remarks,
    }),
  })
    .then(async (res) => {
      const text = await res.text();
      return JSON.parse(text);
    })
    .then((data) => {
      if (data.status === "success") {
        newRow.dataset.taskId = data.inserted_id;
        addRemarksCell(newRow, data.inserted_id, remarks);
        addActionButtonsCell(newRow, {
          id: data.inserted_id,
          date: dbDate,
          start_time: startTime,
          end_time: null,
          remarks: remarks,
          task_description: taskSelect.options[taskSelect.selectedIndex].text,
        });
        lastTaskRow = newRow;
      } else {
        alert("Error: " + (data?.message || "Unknown error"));
      }
    })
    .catch((err) => {
      console.error("Tagging error:", err);
      alert("Error tagging task.");
    });

  taskSelect.value = "";
}

//LOAD EXISTING TASK LOGS
function loadExistingLogs() {
  fetch("../backend/get_user_task_logs.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.status !== "success") {
        return console.error("Error loading logs:", data.message);
      }

      const tbody = document.querySelector("#wmtLogTable tbody");
      tbody.innerHTML = ""; // Clear existing

      data.logs.forEach((log, index) => {
        const row = tbody.insertRow();
        row.dataset.taskId = log.id;

        const displayDate = log.date
          ? formatDateForDisplay(new Date(log.date))
          : "--";
        row.insertCell(0).textContent = displayDate;
        row.insertCell(1).textContent = log.work_mode;
        row.insertCell(2).textContent = log.task_description;
        row.insertCell(3).textContent = ensureHHMMSS(log.start_time);

        const endTimeCell = row.insertCell(4);
        const durationCell = row.insertCell(5);

        const isLast = index === data.logs.length - 1;
        const isIncomplete = !log.end_time;

        if (isLast && isIncomplete) {
          row.classList.add("active-task");
          lastTaskRow = row;
          endTimeCell.textContent = "--";
          durationCell.textContent = "--";
        } else {
          endTimeCell.textContent = log.end_time
            ? ensureHHMMSS(log.end_time)
            : "--";
          if (log.start_time && log.end_time) {
            durationCell.textContent = calculateTimeSpent(
              ensureHHMMSS(log.start_time),
              ensureHHMMSS(log.end_time)
            );
          } else {
            durationCell.textContent = "--";
          }
        }

        addRemarksCell(row, log.id, log.remarks || "");
        //ONE CONTAINER FOR BOTH BUTTONS AMEND AND EDIT
        addActionButtonsCell(row, log);
      });
    })
    .catch((err) => {
      console.error("Failed to load logs:", err);
    });
}

document.addEventListener("DOMContentLoaded", () => {
  loadExistingLogs();
});