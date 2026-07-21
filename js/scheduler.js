let scheduler = {
  users: [],
  year: null,
  month: null,
  selected: new Set(),
  pendingCode: null,
  dirty: {},
  saved: {}, // scheduler_days
  leaves: {}, // 🔥 leave overlay
  toDelete: new Set(),
};

let anchorKey = null;
let isDragging = false;
let dragStartKey = null;
let dragMode = "add"; // "add" or "remove"

//const CODES_WITH_CALLTIME = new Set(["W", "PH", "CB"]);
const CODES_WITH_CALLTIME = new Set(["W", "RH", "SH", "CB"]);

// TIME FORMAT HELPER FOR CALL TIME DROPDOWN =============================

function formatCallTimeLabel(value) {
  const ct = normalizeCallTime(value);
  if (!ct) return "";

  const [hh, mm] = ct.split(":").map((x) => parseInt(x, 10));
  let h = hh % 12;
  if (h === 0) h = 12;

  const ampm = hh >= 12 ? "PM" : "AM";
  return `${h}:${String(mm).padStart(2, "0")} ${ampm}`;
}

// END TIME FORMAT HELPER ==============================================

function parseKey(key) {
  const [userId, dateStr] = key.split("|");
  return { userId: parseInt(userId, 10), dateStr };
}

function getCellByKey(key) {
  return document.querySelector(
    `td.scheduler-cell[data-key="${CSS.escape(key)}"]`,
  );
}

// builds a rectangle selection between 2 cells (same table)
function getKeysInRectangle(startKey, endKey) {
  const allCells = [
    ...document.querySelectorAll("#schedulerMatrixBody td.scheduler-cell"),
  ];
  const indexMap = new Map(); // key -> {r,c}

  // build row/col positions by DOM order
  let r = 0;
  let c = 0;
  let lastRow = null;

  allCells.forEach((td) => {
    const tr = td.parentElement;
    if (tr !== lastRow) {
      r++;
      c = 0;
      lastRow = tr;
    }
    c++;
    indexMap.set(td.dataset.key, { r, c });
  });

  const a = indexMap.get(startKey);
  const b = indexMap.get(endKey);
  if (!a || !b) return [];

  const r1 = Math.min(a.r, b.r);
  const r2 = Math.max(a.r, b.r);
  const c1 = Math.min(a.c, b.c);
  const c2 = Math.max(a.c, b.c);

  const keys = [];
  indexMap.forEach((pos, key) => {
    if (pos.r >= r1 && pos.r <= r2 && pos.c >= c1 && pos.c <= c2) {
      keys.push(key);
    }
  });

  return keys;
}

//HELPERS===============================

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

function ymd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

function dayName(date) {
  return date.toLocaleString("en-US", { weekday: "short" }); // Mon Tue ...
}

function dateLabel(date) {
  const d = date.getDate();
  const mon = date.toLocaleString("en-US", { month: "short" });
  return `${d}-${mon}`; // 1-Jan style
}

function getMonthDates(year, month1to12) {
  const start = new Date(year, month1to12 - 1, 1);
  const end = new Date(year, month1to12, 0);
  const out = [];
  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    out.push(new Date(d));
  }
  return out;
}

//WORKING VERSION OF SETSCHEDULERCALLTIMEUI
/*function setSchedulerCallTimeUI({ value = "", disabled = false } = {}) {
  const hidden = document.getElementById("schedulerCallTimeSelect");
  const btn = document.getElementById("schedulerCallTimeDropdownBtn"); // from your dropdown init
  if (!hidden || !btn) return;

  if (value) hidden.value = value;

  // update button label (match your options formatting)
  const label = value ? value.slice(0, 5) : "Call Time (for W/PH/CB)";
  btn.textContent = label;

  btn.disabled = !!disabled;
  btn.classList.toggle("disabled", !!disabled);
}*/

// WORKING VERSION OF setSchedulerCallTimeUI
/*function setSchedulerCallTimeUI({
  value,
  disabled = false,
  keepLabel = false,
} = {}) {
  const hidden = document.getElementById("schedulerCallTimeSelect");
  const btn = document.getElementById("schedulerCallTimeDropdownBtn");
  if (!hidden || !btn) return;

  // ✅ If caller passed a value (including ""), update hidden
  if (value !== undefined) hidden.value = value;

  // ✅ Decide what label to show
  if (!keepLabel) {
    const current = hidden.value || "";
    btn.textContent = current ? current.slice(0, 5) : "Call Time (for W/PH/CB)";
  }

  btn.disabled = !!disabled;
  btn.classList.toggle("disabled", !!disabled);
}*/

function setSchedulerCallTimeUI({
  value,
  disabled = false,
  keepLabel = false,
} = {}) {
  const hidden = document.getElementById("schedulerCallTimeSelect");
  const btn = document.getElementById("schedulerCallTimeDropdownBtn");
  if (!hidden || !btn) return;

  // If caller passed a value (including ""), update hidden
  if (value !== undefined) hidden.value = value;

  // Decide what label to show
  if (!keepLabel) {
    const current = hidden.value || "";
    const label = current ? formatCallTimeLabel(current) : "";
    btn.textContent = label || "Call Time (for W/RH/SH/CB)";
  }

  btn.disabled = !!disabled;
  btn.classList.toggle("disabled", !!disabled);
}

/**
 * If user selects ONE cell:
 * - if it’s W and has call_time -> show call_time + LOCK dropdown
 * - else -> unlock dropdown (only if pendingCode=W), or set placeholder
 */

//WORKING VERSION OF syncSchedulerCallTimeFromSelection
/*function syncSchedulerCallTimeFromSelection() {
  const key = [...scheduler.selected][0];
  if (!key) {
    // no selection
    setSchedulerCallTimeUI({ value: "", disabled: false });
    return;
  }

  const isDeleted = scheduler.toDelete?.has(key);

  // effective saved/draft data for that cell
  const saved = scheduler.saved?.[key];
  const dirty = scheduler.dirty?.[key];
  const effective = isDeleted ? null : dirty || saved || null;

  const code = effective?.schedule_code || "";
  const ct = effective?.call_time || null;

  // lock only when it is already scheduled as W with a call_time
  //if (String(code).toUpperCase() === "W" && ct) {
  //  setSchedulerCallTimeUI({ value: ct, disabled: true });
  //  return;
  //}

  const codeUpper = String(code).toUpperCase();
  if (CODES_WITH_CALLTIME.has(codeUpper) && ct) {
    setSchedulerCallTimeUI({ value: ct, disabled: true });
    return;
  }

  // otherwise: if pending code is W, allow editing; else disable (optional)
  if (scheduler.pendingCode === "W") {
    setSchedulerCallTimeUI({ value: "", disabled: false });
  } else {
    // keep it enabled/disabled based on your preference
    setSchedulerCallTimeUI({ value: "", disabled: false });
  }
}*/

function syncSchedulerCallTimeFromSelection() {
  const key = [...scheduler.selected][0];

  // If nothing selected: don't wipe the user's chosen call time label
  if (!key) {
    setSchedulerCallTimeUI({ disabled: false, keepLabel: true });
    return;
  }

  const isDeleted = scheduler.toDelete?.has(key);

  const saved = scheduler.saved?.[key];
  const dirty = scheduler.dirty?.[key];
  const effective = isDeleted ? null : dirty || saved || null;

  const codeUpper = String(effective?.schedule_code || "").toUpperCase();
  const ct = effective?.call_time || null;

  // ✅ If selected cell is W/PH/CB and has call_time -> show + lock (WORKING VERSION)
  /*if (CODES_WITH_CALLTIME.has(codeUpper) && ct) {
    setSchedulerCallTimeUI({ value: ct, disabled: true, keepLabel: false });
    return;
  }*/

  // ✅ If selected cell is W/PH/CB and has call_time -> show it but KEEP editable
  if (CODES_WITH_CALLTIME.has(codeUpper) && ct) {
    setSchedulerCallTimeUI({ value: ct, disabled: false, keepLabel: false });
    return;
  }

  // ✅ Otherwise:
  // - If no pending code chosen yet: DO NOT disable (keep current state)
  // - If pending code is W/PH/CB: enable
  // - If pending code is OFF/others: disable

  const pendingUpper = String(scheduler.pendingCode || "").toUpperCase();

  if (!pendingUpper) {
    // no code selected yet → don't mess with enabled/disabled
    setSchedulerCallTimeUI({ keepLabel: true });
    return;
  }

  const shouldEnable = CODES_WITH_CALLTIME.has(pendingUpper);

  setSchedulerCallTimeUI({
    disabled: !shouldEnable,
    keepLabel: true,
  });
}

//END OF HELPERS===============================

/*async function loadSchedulerDepartments() {
  try {
    const res = await fetch(
      "../backend/scheduler/get_scheduler_departments.php",
    );
    const data = await res.json();

    const select = document.getElementById("schedulerDepartmentFilter");
    if (!select) return;

    select.innerHTML = `<option value="">All Departments</option>`;

    if (!data.success) {
      console.error("Failed to load departments");
      return;
    }

    data.departments.forEach((dept) => {
      const opt = document.createElement("option");
      opt.value = dept.id;
      opt.textContent = dept.name;
      select.appendChild(opt);
    });
  } catch (err) {
    console.error("Department load error:", err);
  }
}*/

async function loadSchedulerDepartments() {
  const res = await fetch("../backend/scheduler/get_scheduler_departments.php");
  const data = await res.json();

  if (!data.success) return;

  const select = document.getElementById("schedulerDepartmentFilter");
  select.innerHTML = '<option value="">All Departments</option>';

  data.departments.forEach((d) => {
    select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
  });
}

async function loadSchedulerMatrix() {
  const deptId = document.getElementById("schedulerDepartmentFilter").value;
  const monthVal = document.getElementById("schedulerMonth").value;
  if (!monthVal) {
    alert("Select month.");
    return;
  }

  scheduler.selected.clear();
  scheduler.dirty = {};
  scheduler.pendingCode = null;
  scheduler.toDelete = new Set();

  let url = `../backend/scheduler/get_scheduler_matrix.php?month=${monthVal}`;
  if (deptId) url += `&department_id=${encodeURIComponent(deptId)}`;

  const res = await fetch(url);
  const data = await res.json();

  if (!data.success) {
    alert(data.message || "Failed to load scheduler.");
    return;
  }

  scheduler.users = data.users || [];
  scheduler.saved = data.scheduler || {};

  const [y, m] = monthVal.split("-").map((n) => parseInt(n, 10));
  scheduler.year = y;
  scheduler.month = m;

  // 🔥 NOW FETCH LEAVES
  const leaveRes = await fetch(
    `../backend/scheduler/get_approved_leave_days.php?start=${data.start}&end=${data.end}${deptId ? `&department_id=${deptId}` : ""}`,
  );

  const leaveData = await leaveRes.json();

  scheduler.leaves = leaveData.success ? leaveData.leaves : {};

  renderMatrix();
}

function confirmDeleteSelected() {
  if (scheduler.selected.size === 0) {
    alert("Select at least one cell.");
    return;
  }

  if (
    !confirm(
      `Delete ${scheduler.selected.size} cell(s)? This will remove saved schedule from DB after you click Save Changes.`,
    )
  )
    return;

  scheduler.selected.forEach((key) => {
    // remove any staged update for this key
    delete scheduler.dirty[key];

    // mark for deletion
    scheduler.toDelete.add(key);
  });

  renderMatrix();
}

function renderMatrix() {
  const head = document.getElementById("schedulerMatrixHead");
  const body = document.getElementById("schedulerMatrixBody");
  head.innerHTML = "";
  body.innerHTML = "";

  // HEADER
  let h = `<tr>
    <th class="scheduler-sticky-col">Day</th>
    <th class="scheduler-sticky-col-2">Date</th>
    ${scheduler.users
      .map((u) => {
        const name = `${u.first_name}`.trim(); // or first name only like sample
        return `<th>${escapeHtml(name)}</th>`;
      })
      .join("")}
  </tr>`;
  head.innerHTML = h;

  // ROWS (each day)
  const dates = getMonthDates(scheduler.year, scheduler.month);

  const rowsHtml = dates
    .map((d) => {
      const dYmd = ymd(d);

      const cells = scheduler.users
        .map((u) => {
          const key = `${u.id}|${dYmd}`;

          // display value priority:
          // dirty override -> planned data -> computed overlay (later)
          const saved = scheduler.saved?.[key];
          const dirty = scheduler.dirty?.[key];
          const isDeleted = scheduler.toDelete?.has(key);
          const leave = scheduler.leaves?.[key];

          let v = "";

          /*if (leave) {
            v = leave;
          } else if (isDeleted) {
            v = ""; // ✅ cleared
          } else {
            v = dirty?.schedule_code || saved?.schedule_code || "";
          }*/

          // New Logic added RHL to display if RH and VL exist in one cell
          const scheduleCode = (
            dirty?.schedule_code ||
            saved?.schedule_code ||
            ""
          ).toUpperCase();

          if (leave) {
            // 🔥 NEW LOGIC
            if (scheduleCode === "RH") {
              v = "RHL"; // Regular Holiday Leave
            } else {
              v = leave;
            }
          } else if (isDeleted) {
            v = "";
          } else {
            v = scheduleCode;
          }

          // ✅ Get effective call_time (dirty overrides saved)
          const effective = isDeleted ? null : dirty || saved || null;
          const cellCallTime = effective?.call_time || null;

          // 🔥 Remove "W" from display, keep call time
          /*
          const displayCode = v === "W" ? "" : v || "";
          const displayTime =
            v === "W" && cellCallTime ? cellCallTime.slice(0, 5) : "";
            */
          // ✅ normalize for comparisons
          /*const codeUpper = String(v || "").toUpperCase();
          const displayCode = codeUpper === "W" ? "" : v || "";
          const displayTime =
            codeUpper === "W" && cellCallTime ? cellCallTime.slice(0, 5) : "";*/

          const codeUpper = String(v || "").toUpperCase();

          // W is hidden; PH/CB shown
          const displayCode = codeUpper === "W" ? "" : v || "";

          // show time for W/PH/CB (if missing, show "--")
          //const ctLabel = cellCallTime ? cellCallTime.slice(0, 5) : "--";
          const ctLabel = cellCallTime
            ? formatCallTimeLabel(cellCallTime)
            : "--";
          const displayTime = CODES_WITH_CALLTIME.has(codeUpper) ? ctLabel : "";

          // empty for now
          let colorClass = "";

          switch (codeUpper) {
            case "W":
              colorClass = "scheduler-w";
              break;
            case "OFF":
              colorClass = "scheduler-off";
              break;
            /*case "PH":
              colorClass = "scheduler-ph";
              break;*/
            case "RH":
              colorClass = "scheduler-rh";
              break;
            case "SH":
              colorClass = "scheduler-sh";
              break;
            case "LEAVE":
              colorClass = "scheduler-leave";
              break;
            case "TOIL":
              colorClass = "scheduler-toil";
              break;
            case "RHL":
              colorClass = "scheduler-rhl";
              break;
            case "SICK-PL":
              colorClass = "scheduler-sick";
              break;
            case "UNPAID":
              colorClass = "scheduler-unpaid";
              break;
            case "SPND":
              colorClass = "scheduler-spnd";
              break;
            case "ABSENT":
              colorClass = "scheduler-absent";
              break;
            case "CB":
              colorClass = "scheduler-cb";
              break;
            case "SBL":
              colorClass = "scheduler-sbl";
              break;
            case "LWOP":
              colorClass = "scheduler-lwop";
              break;
            case "ABSENT":
              colorClass = "scheduler-absent";
              break;
          }

          const cls = `scheduler-cell ${colorClass} ${
            scheduler.selected.has(key) ? "selected" : ""
          }`;

          return `
  <td class="${cls}" data-key="${key}" data-cell="1">
    <div class="scheduler-val">${escapeHtml(displayCode)}</div>
    ${displayTime ? `<div class="scheduler-time">${escapeHtml(displayTime)}</div>` : ""}
  </td>
`;
        })
        .join("");

      return `
      <tr>
        <td class="scheduler-sticky-col">${dayName(d)}</td>
        <td class="scheduler-sticky-col-2">${dateLabel(d)}</td>
        ${cells}
      </tr>
    `;
    })
    .join("");

  body.innerHTML = rowsHtml;
  attachSchedulerSelectionHandlers();
}

function toggleCell(e, key) {
  // Ctrl multi-select
  if (!e.ctrlKey && !e.metaKey) {
    scheduler.selected.clear();
  }
  if (scheduler.selected.has(key)) scheduler.selected.delete(key);
  else scheduler.selected.add(key);

  // re-render only selection style (simple: rerender all)
  renderMatrix();
}

function clearSelection() {
  scheduler.selected.clear();
  renderMatrix();
}

/*
function setSelectedCode(code, btn) {
  scheduler.pendingCode = code;

  // remove active style from all buttons
  document.querySelectorAll(".btn-group button").forEach((b) => {
    b.classList.remove("active");
  });

  // activate selected
  btn.classList.add("active");
}*/

/*function setSelectedCode(code, btn) {
  scheduler.pendingCode = code;

  document.querySelectorAll(".btn-group button").forEach((b) => {
    b.classList.remove("active");
  });
  btn.classList.add("active");

  // If not W, we don't need call_time
  //if (code !== "W") {
  //  setSchedulerCallTimeUI({ value: "", disabled: true });
  //} else {
    // W: allow call time unless selection already has locked one
  //  setSchedulerCallTimeUI({ value: "", disabled: false });
  //  syncSchedulerCallTimeFromSelection();
 // }
  const codeUpper = String(code).toUpperCase();

  if (!CODES_WITH_CALLTIME.has(codeUpper)) {
    setSchedulerCallTimeUI({ value: "", disabled: true });
  } else {
    setSchedulerCallTimeUI({ value: "", disabled: false });
    syncSchedulerCallTimeFromSelection();
  }
}*/

function setSelectedCode(code, btn) {
  scheduler.pendingCode = code;

  document
    .querySelectorAll(".btn-group button")
    .forEach((b) => b.classList.remove("active"));
  btn.classList.add("active");

  const codeUpper = String(code).toUpperCase();

  if (!CODES_WITH_CALLTIME.has(codeUpper)) {
    // code doesn't use call time -> disable dropdown but KEEP label (optional)
    setSchedulerCallTimeUI({ disabled: true, keepLabel: true });
  } else {
    // code uses call time -> enable dropdown and KEEP whatever user already chose
    setSchedulerCallTimeUI({ disabled: false, keepLabel: true });
    syncSchedulerCallTimeFromSelection();
  }
}

/*
function confirmApply() {
  if (!scheduler.pendingCode)
    return alert("Choose schedule code first (W/OFF/PH).");
  if (scheduler.selected.size === 0) return alert("Select at least one cell.");

  const rawCt = document.getElementById("schedulerCallTimeSelect").value;
  const ct = normalizeCallTime(rawCt); // ✅ normalize

  const msg =
    `Apply ${scheduler.pendingCode}` +
    (scheduler.pendingCode === "W" && ct
      ? ` with call time ${ct.slice(0, 5)}`
      : "") +
    ` to ${scheduler.selected.size} cell(s)?`;

  document.getElementById("schedulerConfirmText").textContent = msg;
  new bootstrap.Modal(document.getElementById("schedulerConfirmModal")).show();
}

function applyToSelected() {
  const code = scheduler.pendingCode;

  const rawCt = document.getElementById("schedulerCallTimeSelect").value;
  const callTime = code === "W" ? normalizeCallTime(rawCt) : null; // ✅ normalize

  // ✅ if W but call time missing, block (so user sees time immediately too)
  if (code === "W" && !callTime) {
    alert("Please select a valid call time (HH:MM) before applying W.");
    return;
  }

  scheduler.selected.forEach((key) => {
    scheduler.toDelete.delete(key);
    scheduler.dirty[key] = { schedule_code: code, call_time: callTime };
  });

  bootstrap.Modal.getInstance(
    document.getElementById("schedulerConfirmModal"),
  ).hide();
  renderMatrix(); // ✅ now it will display because call_time is correct
}*/

function confirmApply() {
  if (!scheduler.pendingCode) return alert("Choose schedule code first.");
  if (scheduler.selected.size === 0) return alert("Select at least one cell.");

  const rawCt = document.getElementById("schedulerCallTimeSelect").value;
  const ct = normalizeCallTime(rawCt);
  const codeUpper = String(scheduler.pendingCode).toUpperCase();

  const msg =
    `Apply ${scheduler.pendingCode}` +
    (CODES_WITH_CALLTIME.has(codeUpper) && ct
      ? ` with call time ${ct.slice(0, 5)}`
      : "") +
    ` to ${scheduler.selected.size} cell(s)?`;

  document.getElementById("schedulerConfirmText").textContent = msg;
  new bootstrap.Modal(document.getElementById("schedulerConfirmModal")).show();
}

function applyToSelected() {
  const code = scheduler.pendingCode;

  const rawCt = document.getElementById("schedulerCallTimeSelect").value;
  /*const callTime = code === "W" ? normalizeCallTime(rawCt) : null;

  if (code === "W" && !callTime) {
    alert("Please select a valid call time (HH:MM) before applying W.");
    return;
  }

  scheduler.selected.forEach((key) => {
    scheduler.toDelete.delete(key);
    scheduler.dirty[key] = { schedule_code: code, call_time: callTime };
  });*/

  const codeUpper = String(code).toUpperCase();
  const callTime = CODES_WITH_CALLTIME.has(codeUpper)
    ? normalizeCallTime(rawCt)
    : null;

  if (CODES_WITH_CALLTIME.has(codeUpper) && !callTime) {
    alert(
      "Please select a valid call time (HH:MM) before applying " +
        codeUpper +
        ".",
    );
    return;
  }

  scheduler.selected.forEach((key) => {
    scheduler.toDelete.delete(key);
    scheduler.dirty[key] = { schedule_code: codeUpper, call_time: callTime };
  });

  bootstrap.Modal.getInstance(
    document.getElementById("schedulerConfirmModal"),
  )?.hide();
  renderMatrix();
  syncSchedulerCallTimeFromSelection();
}

function escapeHtml(str) {
  return String(str).replace(
    /[&<>"']/g,
    (s) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      })[s],
  );
}

async function saveSchedulerChanges() {
  const dirtyKeys = Object.keys(scheduler.dirty);
  const deleteKeys = [...(scheduler.toDelete || [])];

  if (dirtyKeys.length === 0 && deleteKeys.length === 0) {
    alert("No changes to save.");
    return;
  }

  const changes = dirtyKeys.map((key) => {
    const [userId, date] = key.split("|");
    const v = scheduler.dirty[key];

    return {
      user_id: parseInt(userId, 10),
      work_date: date,
      schedule_code: v.schedule_code,
      /*call_time:
        v.schedule_code === "W" ? normalizeCallTime(v.call_time) || null : null,*/
      call_time: CODES_WITH_CALLTIME.has(String(v.schedule_code).toUpperCase())
        ? normalizeCallTime(v.call_time) || null
        : null,
    };
  });

  const deletes = deleteKeys.map((key) => {
    const [userId, date] = key.split("|");
    return { user_id: parseInt(userId, 10), work_date: date };
  });

  if (
    !confirm(
      `Save ${changes.length} update(s) and ${deletes.length} delete(s)?`,
    )
  )
    return;

  const res = await fetch("../backend/scheduler/save_scheduler_batch.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ changes, deletes }),
  });

  const data = await res.json();

  if (!data.success) {
    alert(data.message || "Save failed.");
    return;
  }

  alert("Scheduler saved successfully.");
  scheduler.dirty = {};
  scheduler.toDelete = new Set();
  loadSchedulerMatrix();
}

function attachSchedulerSelectionHandlers() {
  const body = document.getElementById("schedulerMatrixBody");
  if (!body) return;

  if (body.dataset.bound === "1") return;
  body.dataset.bound = "1";

  body.addEventListener("click", (e) => {
    const cell = e.target.closest('td[data-cell="1"]');
    if (!cell) return;

    const key = cell.dataset.key;

    // ✅ SHIFT + click = range select from anchorKey to this key
    if (e.shiftKey && anchorKey) {
      const rectKeys = getKeysInRectangle(anchorKey, key);

      scheduler.selected.clear();
      rectKeys.forEach((k) => scheduler.selected.add(k));

      renderMatrix();
      syncSchedulerCallTimeFromSelection(); // ✅ add this
      return;
    }

    // ✅ Normal click = set anchor + select only this cell
    anchorKey = key;
    scheduler.selected.clear();
    scheduler.selected.add(key);

    renderMatrix();
    syncSchedulerCallTimeFromSelection();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  loadSchedulerDepartments();
});
