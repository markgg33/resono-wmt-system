// ============================================
// ========== GLOBAL VARIABLES ===============
// ============================================
let lastTaskRow = null;
//NEW
let lockedCallTime = null;
let openTaskIdFromLock = null;
let openTaskDateFromLock = null;

const TRACKER_CODES_WITH_CALLTIME = new Set(["W", "RH", "SH", "CB"]);

// ============================================
// ========== CALL TIME DEFAULTS PER DEPARTMENT
// ============================================

const departmentCallTimeDefaults = {
  Web: "06:00",
  "Fraud Detection": "06:00",
  Ancillary: "07:00",
  "Media and Partnerships": "07:00",
  "Nectar Brand": "05:00",
};

// ============================================
// ========== TIME HELPERS ===================
// ============================================
const CALL_TIME_OPTIONS = [
  { v: "04:30:00", label: "4:30 AM" },
  { v: "05:00:00", label: "5:00 AM" },
  { v: "05:30:00", label: "5:30 AM" },
  { v: "06:00:00", label: "6:00 AM" },
  { v: "06:15:00", label: "6:15 AM" },
  { v: "06:30:00", label: "6:30 AM" },
  { v: "06:45:00", label: "6:45 AM" },
  { v: "07:00:00", label: "7:00 AM" },
  { v: "07:30:00", label: "7:30 AM" },
  { v: "08:00:00", label: "8:00 AM" },
  { v: "08:30:00", label: "8:30 AM" },
  { v: "09:00:00", label: "9:00 AM" },
  { v: "09:30:00", label: "9:30 AM" },
  { v: "10:00:00", label: "10:00 AM" },
  { v: "10:30:00", label: "10:30 AM" },
  { v: "11:00:00", label: "11:00 AM" },
  { v: "11:30:00", label: "11:30 AM" },
  { v: "12:00:00", label: "12:00 PM" },
  { v: "12:30:00", label: "12:30 PM" },
  { v: "13:00:00", label: "1:00 PM" },
  { v: "14:00:00", label: "2:00 PM" },
  { v: "15:00:00", label: "3:00 PM" },
  { v: "16:00:00", label: "4:00 PM" },
  { v: "17:00:00", label: "5:00 PM" },
  { v: "18:00:00", label: "6:00 PM" },
  { v: "19:00:00", label: "7:00 PM" },
  { v: "20:00:00", label: "8:00 PM" },
  { v: "21:00:00", label: "9:00 PM" },
  { v: "22:00:00", label: "10:00 PM" },
  { v: "23:00:00", label: "11:00 PM" },
  { v: "00:00:00", label: "12:00 AM" },
];

function initCallTimeDropdown(
  menuId,
  buttonId,
  hiddenInputId,
  placeholderText,
) {
  const menu = document.getElementById(menuId);
  const btn = document.getElementById(buttonId);
  const hidden = document.getElementById(hiddenInputId);

  if (!menu || !btn || !hidden) return;

  // Build menu items
  menu.innerHTML = "";
  CALL_TIME_OPTIONS.forEach((opt) => {
    const li = document.createElement("li");
    li.innerHTML = `<button type="button" class="dropdown-item" data-value="${opt.v}">${opt.label}</button>`;
    menu.appendChild(li);
  });

  // Click handler (event delegation)
  menu.addEventListener("click", (e) => {
    const item = e.target.closest(".dropdown-item");
    if (!item) return;

    const value = item.getAttribute("data-value");
    const label = item.textContent.trim();

    hidden.value = value;
    btn.textContent = label;
  });

  // Initialize placeholder text
  btn.textContent = placeholderText || "-- Call Time --";
  hidden.value = hidden.value || "";
}

document.addEventListener("DOMContentLoaded", () => {
  // My Tracker dropdown
  initCallTimeDropdown(
    "callTimeMenu",
    "callTimeDropdownBtn",
    "callTimeSelect",
    "----",
  );

  // Scheduler dropdown
  initCallTimeDropdown(
    "schedulerCallTimeMenu",
    "schedulerCallTimeDropdownBtn",
    "schedulerCallTimeSelect",
    "Call Time",
  );
});

function normalizeCallTime(value) {
  if (!value) return null;

  const v = String(value).trim();
  if (!v || v === "--") return null;

  // HH:MM → HH:MM:SS
  if (/^\d{2}:\d{2}$/.test(v)) return `${v}:00`;

  // HH:MM:SS → keep
  if (/^\d{2}:\d{2}:\d{2}$/.test(v)) return v;

  return null; // invalid
}

//MIDNIGHT SHIFT TIME HELPER
function getPHDateStringOffset(days) {
  // requires serverTimeReference.timestamp to be set
  const base = serverTimeReference?.timestamp
    ? new Date(serverTimeReference.timestamp * 1000)
    : new Date();

  const shifted = new Date(base.getTime() + days * 24 * 60 * 60 * 1000);
  return formatDateInPHTimezone(shifted); // returns YYYY-MM-DD in PH timezone
}

/**
 * 🕐 Format any date/time to PH timezone (Asia/Manila)
 * This ensures consistent timezone display across all devices
 */
function formatTimeInPHTimezone(date) {
  if (!date) return "--";

  const formatter = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Manila",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  });

  return formatter.format(date);
}

/**
 * 🕐 Format date to PH timezone (Asia/Manila) as YYYY-MM-DD
 */
function formatDateInPHTimezone(date) {
  if (!date) return "";

  const formatter = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  const parts = formatter.formatToParts(date);
  const year = parts.find((p) => p.type === "year").value;
  const month = parts.find((p) => p.type === "month").value;
  const day = parts.find((p) => p.type === "day").value;

  return `${year}-${month}-${day}`;
}

/**
 * Format JS Date object to "HH:MM:SS" for DB insertion
 * 🕐 Uses server's PH time, not device time
 */
function formatTime(date) {
  // If this came from getServerTime(), use the stored PH time
  if (date && date._phTime) {
    return date._phTime; // Already in PH timezone from server
  }
  return formatTimeInPHTimezone(date); // Fallback to formatting
}

/**
 * Format JS Date object to "YYYY-MM-DD" for DB (if needed)
 * 🕐 Uses server's PH time, not device time
 */
function formatDateForDatabase(date) {
  // If this came from getServerTime(), use the stored PH date
  if (date && date._phDate) {
    return date._phDate; // Already in PH timezone from server
  }
  return formatDateInPHTimezone(date); // Fallback to formatting
}

/**
 * Format JS Date object to "MMM DD, YYYY" for display (e.g., Jul 9, 2025)
 */
function formatDateForDisplay(date) {
  // If date is a string (from server pre-formatted), parse and format it
  if (typeof date === "string") {
    const [year, month, day] = date.split("-");
    const dateObj = new Date(year, parseInt(month) - 1, day);
    return dateObj.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }
  // If date is a Date object with _phDate property, use that
  if (date._phDate) {
    return formatDateForDisplay(date._phDate);
  }
  // Fallback: format Date object in PH timezone
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function formatToHHMM(value) {
  if (!value) return "--";

  const v = String(value).trim(); // ✅ important
  if (!v || v === "--") return "--";

  // treat 00:00 / 00:00:00 as empty for UI display
  if (v === "00:00" || v === "00:00:00") return "--";

  const parts = v.split(":");
  const h = parts[0] ?? "00";
  const m = parts[1] ?? "00";
  return `${h}:${m}`;
}

function calculateTimeSpent(start, end) {
  // Remove seconds (Excel strips seconds)
  start = start.substring(0, 5) + ":00";
  end = end.substring(0, 5) + ":00";

  const startParts = start.split(":").map(Number);
  const endParts = end.split(":").map(Number);

  let startSeconds = startParts[0] * 3600 + startParts[1] * 60;
  let endSeconds = endParts[0] * 3600 + endParts[1] * 60;

  // ⭐ Excel BEHAVIOR: If end < start → crossed midnight → add 24 hours
  if (endSeconds < startSeconds) {
    endSeconds += 24 * 3600;
  }

  const diff = endSeconds - startSeconds;

  const hours = Math.floor(diff / 3600);
  const minutes = Math.floor((diff % 3600) / 60);

  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(
    2,
    "0",
  )}:00`;
}

// Get PH timezone YYYY-MM-DD (not local timezone)
// 🕐 TIMEZONE FIX: Always returns PH date regardless of device timezone
function getLocalDateString() {
  // If we have server time reference, use it
  if (serverTimeReference) {
    return serverTimeReference.date; // Use PH date from server
  }

  // Fallback: format current date in PH timezone
  const now = new Date();

  const formatter = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  const parts = formatter.formatToParts(now);
  const yyyy = parts.find((p) => p.type === "year").value;
  const mm = parts.find((p) => p.type === "month").value;
  const dd = parts.find((p) => p.type === "day").value;

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
    "0",
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

//ENSURE CALL TIME OPTIONS EXIST HELPER
function ensureCallTimeOptionExists(select, value) {
  if (!value) return;

  const exists = [...select.options].some((opt) => opt.value === value);
  if (!exists) {
    const opt = document.createElement("option");
    opt.value = value;
    opt.textContent = value.slice(0, 5);
    select.appendChild(opt);
  }
}

/*function ensureCallTimeOptionExistsDropdown(menuId, value) {
    if (!value) return;

    const menu = document.getElementById(menuId);
    if (!menu) return;

    const exists = [...menu.querySelectorAll(".dropdown-item")].some(
      (btn) => btn.getAttribute("data-value") === value,
    );

    if (!exists) {
      const li = document.createElement("li");
      li.innerHTML = `<button type="button" class="dropdown-item" data-value="${value}">${value.slice(0, 5)}</button>`;
      menu.appendChild(li);
    }
  }*/

//NEW HELPER FOR CALL TIME LOCK

async function checkCallTimeLock() {
  const callTimeSelect = document.getElementById("callTimeSelect");
  const slideHandle = document.getElementById("slideButtonHandle");

  // ✅ Always ensure we have a server reference first (PH date correctness)
  if (!serverTimeReference) {
    await getServerTime();
  }

  const today = getLocalDateString();
  const yesterday = getPHDateStringOffset(-1);

  try {
    // ✅ Look at yesterday + today so midnight-crossing tasks are included
    const res = await fetch(
      `../backend/get_user_task_logs_combined.php?start_date=${yesterday}&end_date=${today}&overlap=1`,
    );
    const data = await res.json();

    if (data.status !== "success") {
      console.error("Call time lock check failed:", data.message);
      return;
    }

    const logs = Array.isArray(data.logs) ? data.logs : [];

    // ✅ Find open tasks, but ignore a carry-over OPEN "End Shift" when it's already a NEW calendar day.
    const reversed = [...logs].reverse();
    //let openLog = reversed.find((l) => !l.end_time);
    let openLog = reversed.find((l) => {
      if (l.end_time) return false;
      const desc = String(l.task_description || "").toLowerCase();
      return !desc.includes("end shift"); // never treat end shift as open driver
    });

    if (openLog) {
      const openWorkDate = openLog.work_date || openLog.date || today;
      const desc = String(openLog.task_description || "").toLowerCase();
      const isEndShiftOpen = desc.includes("end shift");

      // ✅ If it's a NEW day and the only open task is "End Shift",
      // treat it as NOT open (so the new day can start normally).
      if (isEndShiftOpen && openWorkDate !== today) {
        openLog = null;
      }
    }

    if (openLog) {
      // 🔒 If there is an open task, we LOCK call time using that log
      lockedCallTime = openLog.call_time || lockedCallTime || "";
      // For checking open tasks for midnight crossing
      openTaskIdFromLock = openLog.id;
      openTaskDateFromLock = openLog.work_date || openLog.date || today;

      if (lockedCallTime) {
        ensureCallTimeOptionExists(callTimeSelect, lockedCallTime);
        callTimeSelect.value = lockedCallTime;
      }

      callTimeSelect.disabled = true;
      slideHandle.classList.remove("disabled");

      // ✅ IMPORTANT: show the date where the open task lives (often yesterday)
      const openDate = openLog.work_date || openLog.date || today;
      await loadExistingLogs(openDate, openDate);

      return;
    }

    openTaskIdFromLock = null;
    openTaskDateFromLock = null;

    // 🆕 New day + no open task + no today logs → unlock (normal behavior // WORKING BEHAVIOR)
    //lockedCallTime = null;
    //callTimeSelect.disabled = false;
    //callTimeSelect.value = "";
    //slideHandle.classList.add("disabled");

    lockedCallTime = null;
    callTimeSelect.value = "";
    callTimeSelect.disabled = true; // ✅ default disabled
    slideHandle.classList.remove("disabled");

    await loadExistingLogs(today, today);
  } catch (err) {
    console.error("Failed to check call time lock:", err);
  }
}

// DEFAULT CALL TIME FUNCTION
function applyDefaultCallTimeIfAllowed() {
  const callTimeSelect = document.getElementById("callTimeSelect");

  // 🚫 Never override locked or restored value
  if (callTimeSelect.disabled || lockedCallTime || callTimeSelect.value) return;

  const userDept = sessionStorage.getItem("primary_department");
  if (!userDept) return;

  const defaultTime = departmentCallTimeDefaults[userDept];

  if (defaultTime) {
    ensureCallTimeOptionExists(callTimeSelect, defaultTime);
    callTimeSelect.value = defaultTime;
    slideHandle.classList.remove("disabled");
  }

  //CODE FOR CALL TIME CONFIRMATION (SCHEDULER)
  /*
    if (defaultTime) {
      ensureCallTimeOptionExists(callTimeSelect, defaultTime);
      callTimeSelect.value = defaultTime;

      // ✅ Keep slider disabled until user confirms
      slideHandle.classList.add("disabled");
    }*/
}

// ============================================
// NEW HELPERS
// ============================================

async function fetchSchedulerCallTime(workDate) {
  const res = await fetch(
    `../backend/scheduler/get_my_call_time_today.php?date=${encodeURIComponent(
      workDate,
    )}`,
  );
  return await res.json();
}

async function syncTaskLogsCallTime(workDate, callTime) {
  try {
    const res = await fetch(
      "../backend/scheduler/sync_call_time_for_work_date.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ work_date: workDate, call_time: callTime }),
      },
    );
    return await res.json();
  } catch (e) {
    console.error("syncTaskLogsCallTime error:", e);
    return { success: false, message: "Network error" };
  }
}

/**
 * ✅ Always check scheduler and if changed, reflect on UI + update backend logs.
 * - workDate = the shift "work_date" (NOT always today's calendar date) refreshCallTimeFromSchedulerAndSync ORIGINAL
 */
/*
async function refreshCallTimeFromSchedulerAndSync(workDate) {
  const callTimeSelect = document.getElementById("callTimeSelect");
  if (!callTimeSelect || !workDate) return;

  const data = await fetchSchedulerCallTime(workDate);

  if (!data.success || !data.found) return;

  const code = String(data.schedule_code || "").toUpperCase();
  const ct = normalizeCallTime(data.call_time);

  // Only W/PH/CB are call-time driven in tracker
  if (!TRACKER_CODES_WITH_CALLTIME.has(code)) return;
  if (!ct) return;

  const current = normalizeCallTime(lockedCallTime || callTimeSelect.value);

  // ✅ If scheduler time differs, update UI + lockedCallTime + backend logs
  if (!current || current !== ct) {
    ensureCallTimeOptionExists(callTimeSelect, ct);
    callTimeSelect.value = ct;
    lockedCallTime = ct;

    // keep it locked in UI (scheduler-driven)
    callTimeSelect.disabled = true;
    slideHandle.classList.remove("disabled");

    // ✅ Sync DB
    const syncResp = await syncTaskLogsCallTime(workDate, ct);
    if (!syncResp.success) {
      console.warn("Call time backend sync failed:", syncResp.message);
    } else {
      console.log("✅ Call time synced to logs:", syncResp.updated_rows);
    }
  }
}*/

async function refreshCallTimeFromSchedulerAndSync(workDate) {
  const callTimeSelect = document.getElementById("callTimeSelect");
  if (!callTimeSelect || !workDate) return;

  const data = await fetchSchedulerCallTime(workDate);

  // ✅ If no schedule exists, reset
  if (!data.success || !data.found || !data.call_time) {
    lockedCallTime = null;
    callTimeSelect.value = "";
    callTimeSelect.disabled = true;
    slideHandle.classList.remove("disabled");
    return;
  }

  const code = String(data.schedule_code || "").toUpperCase();
  const ct = normalizeCallTime(data.call_time);

  if (TRACKER_CODES_WITH_CALLTIME.has(code) && ct) {
    ensureCallTimeOptionExists(callTimeSelect, ct);
    callTimeSelect.value = ct;
    lockedCallTime = ct;
    callTimeSelect.disabled = true;
    slideHandle.classList.remove("disabled");

    // Sync backend
    await syncTaskLogsCallTime(workDate, ct);
  } else {
    lockedCallTime = null;
    callTimeSelect.value = "";
    callTimeSelect.disabled = true;
    slideHandle.classList.remove("disabled");
  }
}

// ============================================
// ========== LOAD WORK MODES PER DEPARTMENT == COMMENTED OUT UNTIL FURTHER NOTICE
// ============================================
async function loadUserWorkModes() {
  try {
    const res = await fetch("../backend/get_user_work_modes.php");
    const data = await res.json();

    const selector = document.getElementById("workModeSelector");
    selector.innerHTML = `<option value="">-- Select Work Mode --</option>`;

    if (!data.work_modes || data.work_modes.length === 0) {
      selector.innerHTML += `<option disabled>No work modes assigned</option>`;
      return;
    }

    data.work_modes.forEach((mode) => {
      selector.innerHTML += `<option value="${mode.id}">${mode.name}</option>`;
    });
  } catch (err) {
    console.error("Failed to load work modes:", err);
    const selector = document.getElementById("workModeSelector");
    selector.innerHTML = `<option disabled>Error loading work modes</option>`;
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
            <button class="btn btn-success" id="${btnId}"><i class="fa-solid fa-floppy-disk"></i></button>
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
          AlertService.error("Failed to save remarks.");
        } else {
          AlertService.success("Remarks saved.");
        }
      })
      .catch(() => AlertService.error("Error saving remarks."));
  });
}

// ============================================
// ========== SAVE VOLUME HELPER ===============
// ============================================

async function saveVolume(logId) {
  const input = document.querySelector(`#volume_${logId}`);
  if (!input) return;

  let raw = input.value.trim();

  const payload = {
    id: logId,
    volume_remark: raw === "" ? null : raw, // allow empty
  };

  try {
    const res = await fetch("../backend/update_volume.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (data.status === "success") {
      let formatted = "";
      if (data.volume_remark !== null && data.volume_remark !== "") {
        formatted = parseFloat(data.volume_remark)
          .toFixed(2)
          .replace(/\.00$/, "");
      }
      input.value = formatted;

      // ✅ alert confirmation
      AlertService.success("Volume saved.");

      // highlight field briefly
      input.classList.add("border-success");
      setTimeout(() => input.classList.remove("border-success"), 1200);
    } else {
      AlertService.error(
        "Failed to save volume: " + (data.message || "Unknown error"),
      );
    }
  } catch (err) {
    console.error("Save failed:", err);
    AlertService.error("Network error while saving volume");
  }
}

// ============================================
// ========== ADD VOLUME CELL ===============
// ============================================

function addVolumeCell(row, taskId, initialVolume = "") {
  const cell = row.insertCell(7); // after remarks
  cell.className = "volume-cell";
  const inputId = `volume_${taskId}`;
  const btnId = `saveVolumeBtn_${taskId}`;

  cell.innerHTML = `
          <div class="d-flex gap-1 align-items-center">
            <input type="number" id="${inputId}" value="${initialVolume}" 
                  class="form-control form-control-sm" 
                  data-task-id="${taskId}" min="0" step="any"/>
            <button class="btn btn-success" id="${btnId}">
              <i class="fa-solid fa-floppy-disk"></i>
            </button>
          </div>
        `;

  // 🔹 Use your helper here
  document.getElementById(btnId).addEventListener("click", () => {
    saveVolume(taskId);
  });
}

// ============================================
// ========== ACTION BUTTONS CELL (FINAL WITH EDIT BUTTON ADDED)
// ============================================

function addActionButtonsCell(row, log) {
  const cell = row.insertCell(-1);
  cell.classList.add("text-center");

  // === EDIT WORK MODE BUTTON ===
  const editBtn = document.createElement("button");
  editBtn.className = "btn btn-warning me-1";
  editBtn.innerHTML = `<i class="fa-solid fa-pen-to-square"></i>`;
  editBtn.addEventListener("click", () => {
    openEditTaskLogModal(log.id, log.task_description);
  });

  // === AMENDMENT BUTTON ===
  const amendBtn = document.createElement("button");
  amendBtn.className = "btn btn-success";
  amendBtn.innerHTML = `<i class="fa-solid fa-clock-rotate-left"></i>`;
  amendBtn.addEventListener("click", () => {
    // Fill modal with log info
    document.getElementById("logId").value = log.id;

    // Old values
    document.getElementById("oldDate").value = log.date || "";
    document.getElementById("oldStartTime").value = formatToHHMM(
      log.start_time,
    );
    document.getElementById("oldEndTime").value = formatToHHMM(log.end_time);

    // Hidden fields
    document.getElementById("oldDateHidden").value = log.date || "";
    document.getElementById("oldStartTimeHidden").value = log.start_time || "";
    document.getElementById("oldEndTimeHidden").value = log.end_time || "";

    // Reset new values
    document.getElementById("newDate").value = "";
    document.getElementById("newStartTime").value = "";
    document.getElementById("reason").value = "";
    document.getElementById("recipientSelect").value = "";

    const modal = new bootstrap.Modal(
      document.getElementById("userAmendmentModal"),
    );
    modal.show();
  });

  // Append both buttons
  cell.appendChild(editBtn);
  cell.appendChild(amendBtn);
}

// ============================================
// ========== AMENDMENT MODAL LOGIC ===========
// ============================================

document.addEventListener("DOMContentLoaded", () => {
  const fieldSelect = document.getElementById("field");
  const newStart = document.getElementById("newStartTime");
  const newEnd = document.getElementById("newEndTime");
  const newDate = document.getElementById("newDate");
  const newDateWrapper = document.getElementById("newDateWrapper");
  const oldStart = document.getElementById("oldStartTime");
  const oldEnd = document.getElementById("oldEndTime");

  // Toggle visibility & enable/disable based on field selection
  fieldSelect.addEventListener("change", () => {
    const selected = fieldSelect.value;

    if (selected === "start_time") {
      newDateWrapper.classList.add("d-none");
      newDate.value = "";
      newStart.disabled = false;
      newEnd.disabled = true;
      newEnd.value = "";
    } else if (selected === "end_time") {
      newDateWrapper.classList.add("d-none");
      newDate.value = "";
      newStart.disabled = true;
      newStart.value = "";
      newEnd.disabled = false;
    } else if (selected === "date") {
      // Enable all three
      newDateWrapper.classList.remove("d-none");
      newStart.disabled = false;
      newEnd.disabled = false;
    }
  });

  // Optional: Recalculate total duration live if Date is being amended
  [newStart, newEnd].forEach((input) => {
    input.addEventListener("change", () => {
      if (fieldSelect.value === "date" && newStart.value && newEnd.value) {
        const total = computeTimeDiff(
          `${newStart.value}:00`,
          `${newEnd.value}:00`,
        );
        console.log("⏱ New total duration:", total);
        // You could display this somewhere if needed
      }
    });
  });
});

// ============================================
// ========== START TASK LOGIC (TESTING VERSION)===============
// ============================================

// 🕐 Store server time reference for all time operations
let serverTimeReference = null;

async function getServerTime() {
  try {
    const res = await fetch("../backend/get_server_time.php");
    const data = await res.json();

    // 🕐 CRITICAL: Store the server's formatted time (already in PH timezone)
    // We use this as the SOURCE OF TRUTH, not device time
    serverTimeReference = {
      timestamp: data.server_timestamp,
      date: data.time_parts.date, // "2026-01-19" (PH date)
      time: data.time_parts.time, // "14:30:45" (PH time)
      timezone: data.timezone,
    };

    // Return a marker object that stores PH time parts
    const result = new Date(data.server_timestamp * 1000);
    result._phDate = data.time_parts.date;
    result._phTime = data.time_parts.time;
    result._phTimezone = data.timezone;
    result._phTimestamp = data.server_timestamp;

    // Debug: Log what we received
    console.log("🕐 Server Time Received:", {
      phDate: data.time_parts.date,
      phTime: data.time_parts.time,
      timezone: data.timezone,
      deviceTimezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    });

    return result;
  } catch (err) {
    console.error("Failed to fetch server time:", err);
    // NEVER fallback to device time - that causes Brisbane timezone issue!
    alert(
      "CRITICAL: Cannot fetch PH time from server. Cannot tag task with wrong timezone!",
    );
    throw err; // Prevent task from being tagged with wrong timezone
  }
}

let prevTaskId = null;

async function startTask() {
  const workModeSelect = document.getElementById("workModeSelector");
  const taskSelect = document.getElementById("taskSelector");
  const remarksInput = document.getElementById("remarksInput");

  const workMode = workModeSelect.value;
  const taskDescriptionId = taskSelect.value;
  const remarks = remarksInput?.value || "";

  if (!workMode || !taskDescriptionId) {
    AlertService.warning("Please select both Work Mode and Task.");
    return;
  }

  // VALIDATION FOR CALL TIME (only required for first task of the day)
  const callTimeSelect = document.getElementById("callTimeSelect");

  // Get call_time value
  const rawCallTime = callTimeSelect.disabled
    ? lockedCallTime
    : callTimeSelect.value;

  const callTime = normalizeCallTime(rawCallTime); // ✅ normalize

  // Only validate call_time if the select is NOT disabled (i.e., this is the first task)
  // If the select is disabled, call time is already locked for the day
  if (!callTimeSelect.disabled && !callTime) {
    AlertService.error("Please select Call Time before tagging.");
    return;
  }

  // 🔐 CRITICAL: Fetch server time instead of using client-side time
  const now = await getServerTime();

  // 🕐 Use the server's PH-formatted time, not device time
  const startTime = now._phTime.slice(0, 5) + ":00"; // minute-locked
  const displayStart = formatToHHMM(startTime);
  const dbDate = now._phDate; // "YYYY-MM-DD" in PH timezone
  const displayDate = formatDateForDisplay(now._phDate);
  const userId = sessionStorage.getItem("user_id");
  const tableBody = document.querySelector("#wmtLogTable tbody");

  const selectedTaskText = taskSelect.options[taskSelect.selectedIndex].text
    .trim()
    .toLowerCase();

  const isEndShiftSelected = selectedTaskText.includes("end shift");

  // New
  let prevOpenWorkDate = null;

  // Step 1: Close previous task if still open
  if (
    lastTaskRow &&
    (!lastTaskRow.cells[4].textContent ||
      lastTaskRow.cells[4].textContent === "--")
  ) {
    //const lastTaskDateText = lastTaskRow.cells[0].textContent;
    //const todayDisplayDate = formatDateForDisplay(new Date());

    // ✅ add this FIRST
    prevOpenWorkDate = lastTaskRow?.dataset?.workDate || null;

    const prevTaskName = lastTaskRow.cells[2].textContent.trim();
    const currentTaskName =
      taskSelect.options[taskSelect.selectedIndex].text.trim();

    if (prevTaskName === currentTaskName) {
      AlertService.error(
        "You already tagged this same task — it’s still ongoing.",
      );
      return;
    }

    // ALWAYS close previous open task regardless of calendar date
    const prevStart = lastTaskRow.cells[3].textContent + ":00";
    const prevEnd = startTime;
    const duration = calculateTimeSpent(prevStart, prevEnd);

    lastTaskRow.cells[4].textContent = formatToHHMM(prevEnd);
    lastTaskRow.cells[5].textContent = "--";
    lastTaskRow.classList.remove("active-task");

    //const prevTaskId = lastTaskRow.dataset.taskId;

    prevTaskId = lastTaskRow.dataset.taskId;

    // ✅ add these
    const prevWorkDate =
      prevOpenWorkDate || lastTaskRow.dataset.workDate || dbDate;
    const endDateForPrev =
      prevWorkDate && prevWorkDate !== dbDate ? dbDate : prevWorkDate;

    // REMOVE COMMENT (WORKING VERSION)
    /*try {
      const updateRes = await fetch("../backend/update_task_log.php", {
        method: "POST",
        credentials: "same-origin", 
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id: prevTaskId,
          end_time: prevEnd,
          end_date: endDateForPrev,
          //duration: duration,
        }),
      });

      const updateResp = await updateRes.json();
      if (updateResp.status !== "success") {
        console.error("Error updating previous task:", updateResp.message);
      }
    } catch (err) {
      console.error("Error updating previous task:", err);
    }*/
  }

  // Step 2: Insert new task row with start time only
  const newRow = document.createElement("tr");
  newRow.classList.add("active-task");
  newRow.innerHTML = `
        <td>${displayDate}</td>
        <td>${workModeSelect.options[workModeSelect.selectedIndex].text}</td>
        <td>${taskSelect.options[taskSelect.selectedIndex].text}</td>
        <td>${displayStart}</td>
        <td>--</td>
        <td>--</td>
      `;
  const firstCell = tableBody.querySelector("td.text-muted");
  if (firstCell && firstCell.textContent.includes("No logs found for today")) {
    tableBody.innerHTML = "";
  }
  tableBody.appendChild(newRow);

  let newRowId = null;

  // ✅ Determine the active shift work_date (if we are continuing a midnight-crossing shift)
  const activeShiftWorkDate =
    prevOpenWorkDate || openTaskDateFromLock || dbDate;

  // ✅ If scheduler was corrected, reflect before inserting
  await refreshCallTimeFromSchedulerAndSync(activeShiftWorkDate);

  // ✅ Recompute callTime after refresh (important!)
  const rawCallTime2 = callTimeSelect.disabled
    ? lockedCallTime
    : callTimeSelect.value;
  const callTime2 = normalizeCallTime(rawCallTime2);

  // ✅ If we are on a new calendar day but there is an open shift from yesterday,
  // keep saving tasks under the old shift work_date until End Shift is tagged.
  const hasCarryOverShift =
    (openTaskDateFromLock && openTaskDateFromLock !== dbDate) ||
    (prevOpenWorkDate && prevOpenWorkDate !== dbDate);

  // ✅ Decide work_date for this insert
  const workDateForInsert = isEndShiftSelected
    ? activeShiftWorkDate // End Shift closes the shift it belongs to
    : hasCarryOverShift
      ? activeShiftWorkDate
      : dbDate; // normal tasks follow open shift if any

  //REMOVE COMMENT (WORKING VERSION)
  /*const payload = {
    user_id: userId,
    work_mode_id: workMode,
    task_description_id: taskDescriptionId,
    date: dbDate, // calendar date of tagging
    work_date: workDateForInsert, // ✅ shift date
    start_time: startTime,
    call_time: callTime2, //changed from callTime
    remarks: remarks,
  };*/

  const payload = {
    previous_task_id: prevTaskId || null,

    user_id: userId,
    work_mode_id: workMode,
    task_description_id: taskDescriptionId,

    date: dbDate,
    work_date: workDateForInsert,

    start_time: startTime,
    call_time: callTime2,

    remarks: remarks,
  };

  // ✅ If End Shift, close it immediately (same timestamp)
  if (isEndShiftSelected) {
    payload.end_time = startTime;
    payload.end_date = dbDate; // calendar end date
    payload.total_duration = "00:00:00"; // optional
  }

  try {
    //const res = await fetch("../backend/insert_task_logs.php", {
    const res = await fetch("../backend/switch_task.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`);
    }

    const insertResp = await res.json();

    if (insertResp.status !== "success") {
      AlertService.error(
        "❌ Error inserting task: " + (insertResp.message || "Unknown error"),
      );
      return;
    }

    newRowId = insertResp.inserted_id;

    // 🔒 LOCK CALL TIME FOR THE DAY AFTER FIRST TAG (WORKING)
    /*lockedCallTime = callTime;
    callTimeSelect.disabled = true;*/

    lockedCallTime = callTime2 || callTime; // ✅ refreshed value wins
    callTimeSelect.disabled = true;

    if (lockedCallTime) {
      ensureCallTimeOptionExists(callTimeSelect, lockedCallTime);
      callTimeSelect.value = lockedCallTime;
    }

    // ✅ If End Shift was tagged, it means the shift is CLOSED. So unlock call time for the next shift/day.
    /*
      if (isEndShiftSelected) {
        lockedCallTime = null;
        openTaskIdFromLock = null;
        openTaskDateFromLock = null;

        callTimeSelect.disabled = false;
        callTimeSelect.value = "";
        lastConfirmedCallTime = "";
        slideHandle.classList.add("disabled");
      }*/

    if (isEndShiftSelected) {
      // shift closed, but for now keep call time locked/disabled on UI
      openTaskIdFromLock = null;
      openTaskDateFromLock = null;

      // keep disabled (do not allow manual choosing)
      callTimeSelect.disabled = true;

      // optional: keep showing the last call time label/value
      callTimeSelect.value = lockedCallTime || callTime || "";

      //slideHandle.classList.add("disabled");

      // ✅ enable slider again after End Shift
      slideHandle.classList.remove("disabled");
    }
  } catch (err) {
    console.error("Error inserting task:", err);
    AlertService.error("Error inserting task.");
    return;
  }

  // Step 3: Assign task_description_id separatelystartTask(
  /*try {
    await fetch("../backend/assign_task.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: newRowId,
        task_description_id: taskDescriptionId,
      }),
    });
  } catch (err) {
    console.error("Error assigning task:", err);
  }*/

  /*const reloadStart =
        prevOpenWorkDate && prevOpenWorkDate < dbDate ? prevOpenWorkDate : dbDate;
      const reloadEnd = dbDate;*/

  const baseWorkDate = prevOpenWorkDate || openTaskDateFromLock || dbDate;

  const reloadStart =
    isEndShiftSelected && baseWorkDate < dbDate ? baseWorkDate : dbDate;

  const reloadEnd = dbDate;

  loadExistingLogs(reloadStart, reloadEnd).then(() => {
    if (!isEndShiftSelected) callTimeSelect.disabled = true;
    AlertService.success("✅ Task successfully tagged and logs refreshed!");
  });

  taskSelect.value = "";
}

// ✅ UNIFIED + WORKING VERSION (with filters, remarks, actions, active task highlight)
async function loadExistingLogs(startDate = null, endDate = null) {
  try {
    showLoader();

    let url = "../backend/get_user_task_logs_combined.php";

    // Default: show today's logs if no range selected
    if (!startDate && !endDate) {
      const today = getLocalDateString();
      startDate = today;
      endDate = today;
    }

    url += `?start_date=${startDate}&end_date=${endDate}`;

    const res = await fetch(url);
    const data = await res.json();

    const tbody = document.querySelector("#wmtLogTable tbody");
    tbody.innerHTML = "";

    // 🏷️ Update Date Label
    const dateLabel = document.getElementById("currentDayLabel");
    if (dateLabel) {
      const displayStart = formatDateForDisplay(startDate);
      const displayEnd = formatDateForDisplay(endDate);
      dateLabel.textContent =
        startDate === endDate
          ? `Logs for ${displayStart}`
          : `Logs from ${displayStart} to ${displayEnd}`;
    }

    // ❌ Handle error
    if (data.status !== "success") {
      console.error("Error loading logs:", data.message);
      const row = tbody.insertRow();
      const cell = row.insertCell(0);
      cell.colSpan = 9;
      cell.classList.add("text-center", "text-muted");
      cell.textContent = "Error loading logs.";
      return;
    }

    // ❌ Handle no logs
    if (data.logs.length === 0) {
      const row = tbody.insertRow();
      const cell = row.insertCell(0);
      cell.colSpan = 9;
      cell.classList.add("text-center", "text-muted");
      cell.textContent = "No logs found for selected date range.";
      return;
    }

    // 🗂️ Group logs by date
    const grouped = data.logs.reduce((acc, log) => {
      const key = log.work_date || log.date;
      acc[key] = acc[key] || [];
      acc[key].push(log);
      return acc;
    }, {});

    const today = getLocalDateString();
    // used to detect active task

    // 🧭 Sort by date ascending
    Object.keys(grouped)
      .sort()
      .forEach((dateKey) => {
        const logs = grouped[dateKey];

        /*logs.sort((a, b) => {
            const aEndShift = String(a.task_description || "")
              .toLowerCase()
              .includes("end shift");
            const bEndShift = String(b.task_description || "")
              .toLowerCase()
              .includes("end shift");
            if (aEndShift !== bEndShift) return aEndShift ? 1 : -1; // End Shift last

            const base = dateKey; // work_date group key (YYYY-MM-DD)

            // ✅ Use the best “calendar marker” available
            // Priority: end_date (most accurate for midnight-crossing) -> date -> work_date
            const aMarker = a.end_date || a.date || a.work_date || base;
            const bMarker = b.end_date || b.date || b.work_date || base;

            const aOffset = aMarker > base ? 86400 : 0;
            const bOffset = bMarker > base ? 86400 : 0;

            const aSec = timeToSeconds(a.start_time || "00:00:00") + aOffset;
            const bSec = timeToSeconds(b.start_time || "00:00:00") + bOffset;

            return aSec - bSec;
          });*/

        logs.sort((a, b) => {
          const aDesc = String(a.task_description || "").toLowerCase();
          const bDesc = String(b.task_description || "").toLowerCase();

          const aEndShift = aDesc.includes("end shift");
          const bEndShift = bDesc.includes("end shift");

          // End Shift always last
          if (aEndShift !== bEndShift) return aEndShift ? 1 : -1;

          const base = String(dateKey); // work_date group key (YYYY-MM-DD)

          // ✅ For ordering:
          // - normal tasks: use calendar DATE (so after-midnight becomes "next day" and gets offset)
          // - end shift: use end_date (close day)
          const aMarker = String(
            aEndShift
              ? a.end_date || a.date || a.work_date || base
              : a.date || a.work_date || base,
          );
          const bMarker = String(
            bEndShift
              ? b.end_date || b.date || b.work_date || base
              : b.date || b.work_date || base,
          );

          const aOffset = aMarker > base ? 86400 : 0;
          const bOffset = bMarker > base ? 86400 : 0;

          const aSec = timeToSeconds(a.start_time || "00:00:00") + aOffset;
          const bSec = timeToSeconds(b.start_time || "00:00:00") + bOffset;

          if (aSec !== bSec) return aSec - bSec;

          // tie breaker for identical timestamps
          return Number(a.id || 0) - Number(b.id || 0);
        });

        logs.forEach((log, index) => {
          const row = tbody.insertRow();
          row.dataset.taskId = log.id;
          const logDay = log.work_date || log.date;
          row.dataset.workDate = logDay;

          // 📅 Date
          //const displayDate = formatDateForDisplay(log.work_date || log.date);
          //row.insertCell(0).textContent = displayDate;

          // 📅 Date (SHIFT-AWARE DISPLAY FIX)
          const workDate = log.work_date || log.date; // shift date
          const calendarDate = log.date || log.work_date; // calendar tag date

          const desc = String(log.task_description || "").toLowerCase();
          const isEndShift = desc.includes("end shift");

          // ✅ If a row was tagged on the next calendar day but belongs to same work_date,
          // show the calendar day (Feb 12) while keeping it grouped under work_date (Feb 11).
          const isNextCalendarDay =
            workDate && calendarDate && String(calendarDate) > String(workDate);

          const uiDate = isEndShift
            ? log.end_date || calendarDate // End Shift shows close date
            : isNextCalendarDay
              ? calendarDate
              : workDate; // other rows show real calendar day if crossed midnight

          row.insertCell(0).textContent = formatDateForDisplay(uiDate);

          // 💼 Mode / Description
          row.insertCell(1).textContent = log.work_mode;
          row.insertCell(2).textContent = log.task_description;

          // 🕓 Start Time
          row.insertCell(3).textContent = log.start_time
            ? formatToHHMM(log.start_time)
            : "--";

          // 🕔 End Time & Duration
          const endTimeCell = row.insertCell(4);
          const durationCell = row.insertCell(5);

          //const isToday = log.date === today;
          const isToday = logDay === today;
          const isLast = index === logs.length - 1;
          const isIncomplete = !log.end_time;

          // ✅ If this row is the exact open task found by checkCallTimeLock, treat it as active
          const isLockedOpenTask =
            openTaskIdFromLock && String(log.id) === String(openTaskIdFromLock);

          // ✅ Old behavior still works for “today’s last open row”
          const isDefaultActiveToday = isToday && isLast && isIncomplete;

          if ((isLockedOpenTask && isIncomplete) || isDefaultActiveToday) {
            row.classList.add("active-task");
            lastTaskRow = row;
            endTimeCell.textContent = "--";
            durationCell.textContent = "--";
          } else {
            endTimeCell.textContent = log.end_time
              ? formatToHHMM(log.end_time)
              : "--";
            if (log.total_duration) {
              durationCell.textContent = formatToHHMM(log.total_duration);
            } else if (log.start_time && log.end_time) {
              // fallback compute
              const rawDuration = calculateTimeSpent(
                ensureHHMMSS(log.start_time),
                ensureHHMMSS(log.end_time),
              );
              durationCell.textContent = formatToHHMM(rawDuration);
            } else {
              durationCell.textContent = "--";
            }
          }

          // 🗒️ Remarks + Volume + Actions
          addRemarksCell(row, log.id, log.remarks || "");
          addVolumeCell(
            row,
            log.id,
            log.volume_remark
              ? parseFloat(log.volume_remark).toFixed(2).replace(/\.00$/, "")
              : "",
          );
          addActionButtonsCell(row, log);
        });
      });
  } catch (err) {
    console.error("Error loading logs:", err);
  } finally {
    hideLoader();
  }
}

// 🧭 FILTER CONTROLS
function filterTaskLogs() {
  const startDate = document.getElementById("startDateFilter").value;
  const endDate = document.getElementById("endDateFilter").value;

  if (!startDate || !endDate) {
    AlertService.warning("Please select both start and end dates.");
    return;
  }

  loadExistingLogs(startDate, endDate);
}

// ============================================
// ========== TIMEZONE DISPLAY ================
// ============================================

let serverTimeOffset = 0; // Cache the offset to avoid multiple API calls

//DISABLE SLIDE UNTIL CALL TIME IS SELECTED

const callTimeSelect = document.getElementById("callTimeSelect");
const slideHandle = document.getElementById("slideButtonHandle");

// lock by default
slideHandle.classList.remove("disabled");

let lastConfirmedCallTime = "";

callTimeSelect.addEventListener("change", () => {
  const selected = callTimeSelect.value;

  if (!selected) {
    slideHandle.classList.remove("disabled");
    return;
  }

  const confirmed = confirm("Is the selected call time correct?");
  if (!confirmed) {
    callTimeSelect.value = lastConfirmedCallTime || "";
    slideHandle.classList.toggle("disabled", !callTimeSelect.value);
    return;
  }

  // ✅ Persist confirmed call time
  lastConfirmedCallTime = selected;

  slideHandle.classList.remove("disabled");
});

function resetTaskLogs() {
  document.getElementById("startDateFilter").value = "";
  document.getElementById("endDateFilter").value = "";
  loadExistingLogs();
}

// WORKING DOM VERSION
/*document.addEventListener("DOMContentLoaded", async () => {
  loadUserWorkModes();

  // ✅ This will also load logs correctly (yesterday if open task exists)
  await checkCallTimeLock();

  // ✅ apply scheduled call time first
  await applyScheduledCallTimeForTodayIfAllowed();

  const callTimeSelect = document.getElementById("callTimeSelect");

  // Apply default ONLY if unlocked and empty
  if (!lockedCallTime && !callTimeSelect.disabled && !callTimeSelect.value) {
    applyDefaultCallTimeIfAllowed();
  }
});*/

document.addEventListener("DOMContentLoaded", async () => {
  loadUserWorkModes();

  // ensures server time exists and checks open tasks
  await checkCallTimeLock();

  // ✅ Determine ACTIVE shift date (work_date)
  const today = getLocalDateString();
  const yesterday = getPHDateStringOffset(-1);

  // ✅ always attempt yesterday too (covers midnight-crossing shifts)
  await refreshCallTimeFromSchedulerAndSync(yesterday);

  const activeWorkDate = openTaskDateFromLock || today;
  // ✅ Pull scheduler truth ALWAYS and sync backend if changed
  await refreshCallTimeFromSchedulerAndSync(activeWorkDate);

  // Apply default ONLY if unlocked and empty (rare now, but keep safe)
  const callTimeSelect = document.getElementById("callTimeSelect");
  if (!lockedCallTime && !callTimeSelect.disabled && !callTimeSelect.value) {
    applyDefaultCallTimeIfAllowed();
  }

  // reload logs (optional, but helps reflect corrected call_time display logic)
  await loadExistingLogs(activeWorkDate, today);
});

/*async function applyScheduledCallTimeForTodayIfAllowed() {
    const callTimeSelect = document.getElementById("callTimeSelect");

    if (callTimeSelect.disabled || lockedCallTime) return;

    const today = getLocalDateString();

    try {
      const res = await fetch(
        `../backend/scheduler/get_my_call_time_today.php?date=${today}`,
      );
      const data = await res.json();

      if (!data.success || !data.found) return;

      const ct = normalizeCallTime(data.call_time); // ✅ normalize

      if (data.schedule_code === "W" && ct) {
        ensureCallTimeOptionExists(callTimeSelect, ct);
        callTimeSelect.value = ct;
        //NEW
        callTimeSelect.disabled = true;
        lockedCallTime = ct;
        slideHandle.classList.remove("disabled");
        return;
      }

      //CALL TIME CONFIRMATION (SCHEDULER)
      /*
      if (data.schedule_code === "W" && ct) {
        ensureCallTimeOptionExists(callTimeSelect, ct);
        callTimeSelect.value = ct;

        // ✅ Keep slider disabled until user confirms the call time
        slideHandle.classList.add("disabled");
        return;
      }

      callTimeSelect.value = "";
      lastConfirmedCallTime = "";
      slideHandle.classList.add("disabled");
    } catch (e) {
      console.error("Failed to load scheduled call time:", e);
    }
  }*/

/*async function applyScheduledCallTimeForTodayIfAllowed() {
  const callTimeSelect = document.getElementById("callTimeSelect");
  if (!callTimeSelect) return;

  // If an open task already locked call time, don't override
  if (lockedCallTime) return;

  const today = getLocalDateString();

  try {
    const res = await fetch(
      `../backend/scheduler/get_my_call_time_today.php?date=${today}`,
    );
    const data = await res.json();

    // WORKING VERSION
    //if (!data.success || !data.found) return;

    if (!data.success || !data.found) {
      lockedCallTime = null;
      callTimeSelect.value = "";
      callTimeSelect.disabled = true;
      slideHandle.classList.remove("disabled");
      return;
    }

    const code = String(data.schedule_code || "").toUpperCase();
    const ct = normalizeCallTime(data.call_time);

    // ✅ OFF: disable dropdown + keep slider disabled
    if (code === "OFF" || code === "ABSENT") {
      lockedCallTime = null;
      callTimeSelect.value = "";
      callTimeSelect.disabled = true;
      slideHandle.classList.remove("disabled");
      return;
    }

    // ✅ W/PH/CB: show call time + lock dropdown
    if (TRACKER_CODES_WITH_CALLTIME.has(code)) {
      if (ct) {
        ensureCallTimeOptionExists(callTimeSelect, ct);
        callTimeSelect.value = ct;
        lockedCallTime = ct;
        callTimeSelect.disabled = true;
        slideHandle.classList.remove("disabled");
      } else {
        // No call time saved (shouldn't happen if backend requires it)
        callTimeSelect.value = "";
        lockedCallTime = null;
        callTimeSelect.disabled = true;
        slideHandle.classList.remove("disabled");
      }
      return;
    }

    // Other codes: normal behavior (enable selection)
    callTimeSelect.disabled = false;
  } catch (e) {
    console.error("Failed to load scheduled call time:", e);
  }
}*/

async function applyScheduledCallTimeForTodayIfAllowed() {
  const callTimeSelect = document.getElementById("callTimeSelect");
  if (!callTimeSelect) return;

  // If an open task already locked call time, don't override
  if (lockedCallTime) return;

  const today = getLocalDateString();

  try {
    const res = await fetch(
      `../backend/scheduler/get_my_call_time_today.php?date=${today}`,
    );
    const data = await res.json();

    // ✅ If no schedule exists for today OR code is OFF/ABSENT → reset
    if (
      !data.success ||
      !data.found ||
      ["OFF", "ABSENT"].includes(data.schedule_code)
    ) {
      lockedCallTime = null;
      callTimeSelect.value = "";
      callTimeSelect.disabled = true; // disable until first tag or default
      slideHandle.classList.remove("disabled");
      return;
    }

    const code = String(data.schedule_code || "").toUpperCase();
    const ct = normalizeCallTime(data.call_time);

    // ✅ Only W/PH/CB should set a call time
    if (TRACKER_CODES_WITH_CALLTIME.has(code) && ct) {
      lockedCallTime = ct;
      callTimeSelect.value = ct;
      callTimeSelect.disabled = true; // keep locked
      slideHandle.classList.remove("disabled");
    } else {
      // Other codes → reset
      lockedCallTime = null;
      callTimeSelect.value = "";
      callTimeSelect.disabled = true;
      slideHandle.classList.remove("disabled");
    }
  } catch (e) {
    console.error("Failed to load scheduled call time:", e);
    lockedCallTime = null;
    callTimeSelect.value = "";
    callTimeSelect.disabled = true;
    slideHandle.classList.remove("disabled");
  }
}
