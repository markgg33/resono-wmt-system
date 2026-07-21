//WORKING VERSION
/*
document.addEventListener("DOMContentLoaded", function () {
  const workModeSelect = document.getElementById("insertWorkMode");
  const taskDescSelect = document.getElementById("insertTaskDescription");
  const recipientSelect = document.getElementById("RecipientSelectInsertion");
  const alertBox = document.getElementById("insertTaskAlert");
  const submitBtn = document.getElementById("submitTaskInsertionBtn");
  const form = document.getElementById("taskInsertionForm");

  // 🔹 Load Work Modes
  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((data) => {
      workModeSelect.innerHTML = `<option value="">Select Work Mode</option>`;
      data.forEach((mode) => {
        const opt = document.createElement("option");
        opt.value = mode.id;
        opt.textContent = mode.name;
        workModeSelect.appendChild(opt);
      });
    });

    //FOR END TIME DISABLED WHEN END SHIFT IS SELECTED
  document.addEventListener("DOMContentLoaded", function () {
    const taskSelect = document.getElementById("task_description_id");
    const endTimeInput = document.getElementById("end_time");

    if (taskSelect && endTimeInput) {
      taskSelect.addEventListener("change", function () {
        const selectedText =
          taskSelect.options[taskSelect.selectedIndex].text.toLowerCase();

        if (selectedText.includes("end shift")) {
          endTimeInput.value = "";
          endTimeInput.disabled = true;
          endTimeInput.removeAttribute("required");
        } else {
          endTimeInput.disabled = false;
          endTimeInput.setAttribute("required", "required");
        }
      });
    }
  });

  // 🔹 Load Task Descriptions dynamically
  workModeSelect.addEventListener("change", function () {
    const workModeId = this.value;
    taskDescSelect.innerHTML = `<option value="">Select Task</option>`;
    if (!workModeId) return;

    fetch(`../backend/get_task_descriptions.php?work_mode_id=${workModeId}`)
      .then((res) => res.json())
      .then((data) => {
        data.forEach((task) => {
          const opt = document.createElement("option");
          opt.value = task.id;
          opt.textContent = task.description;
          taskDescSelect.appendChild(opt);
        });
      });
  });

  // 🔹 Load Recipients (Admins or Supervisors)
  fetch("../backend/dtr-requests/get_task_insertion_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      recipientSelect.innerHTML = `<option value="">Select Recipient</option>`;
      data.forEach((user) => {
        const opt = document.createElement("option");
        opt.value = user.id;
        opt.textContent = `${user.full_name} (${user.role})`;
        recipientSelect.appendChild(opt);
      });
    })
    .catch(() => {
      recipientSelect.innerHTML = `<option value="">No recipients available</option>`;
    });

  // 🔹 Submit Task Insertion Request
  submitBtn.addEventListener("click", function () {
    alertBox.classList.add("d-none");
    submitBtn.disabled = true;

    const formData = new FormData(form);

    // Simple front-end validation
    const requiredFields = [
      "date",
      "work_mode_id",
      "task_description_id",
      "start_time",
      "reason",
      "recipient_id",
    ];
    for (const field of requiredFields) {
      if (!formData.get(field)) {
        showAlert("⚠️ Please fill in all required fields.", false);
        submitBtn.disabled = false;
        return;
      }
    }

    fetch("../backend/dtr-requests/insert_task_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        showAlert(data.message, data.success);
        if (data.success) {
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("taskInsertionModal")
            );
            modal.hide();
            form.reset();
            document
              .querySelector("#task-insertion-requests-table")
              ?.dispatchEvent(new Event("refreshTable"));
          }, 1200);
        }
      })
      .catch(() => {
        showAlert("❌ An unexpected error occurred. Please try again.", false);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  // 🔹 Helper for showing alerts
  function showAlert(message, success) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none", "alert-success", "alert-danger");
    alertBox.classList.add(success ? "alert-success" : "alert-danger");
  }
});

// 🔹 Global function to open the modal
function openTaskInsertionModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("taskInsertionModal")
  );
  modal.show();
}
*/

// CLEAN + FINAL VERSION
/*
document.addEventListener("DOMContentLoaded", function () {
  const workModeSelect = document.getElementById("insertWorkMode");
  const taskDescSelect = document.getElementById("insertTaskDescription");
  const recipientSelect = document.getElementById("RecipientSelectInsertion");
  const alertBox = document.getElementById("insertTaskAlert");
  const submitBtn = document.getElementById("submitTaskInsertionBtn");
  const form = document.getElementById("taskInsertionForm");
  const endTimeInput = document.getElementById("insertEndTime");

  // ======================================================
  // 🔹 Load Work Modes
  // ======================================================
  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((data) => {
      workModeSelect.innerHTML = `<option value="">Select Work Mode</option>`;
      data.forEach((mode) => {
        const opt = document.createElement("option");
        opt.value = mode.id;
        opt.textContent = mode.name;
        workModeSelect.appendChild(opt);
      });
    });

  // ======================================================
  // 🔹 Load Task Descriptions dynamically
  // ======================================================
  workModeSelect.addEventListener("change", function () {
    const workModeId = this.value;
    taskDescSelect.innerHTML = `<option value="">Select Task</option>`;
    if (!workModeId) return;

    fetch(`../backend/get_task_descriptions.php?work_mode_id=${workModeId}`)
      .then((res) => res.json())
      .then((data) => {
        data.forEach((task) => {
          const opt = document.createElement("option");
          opt.value = task.id;
          opt.textContent = task.description;
          taskDescSelect.appendChild(opt);
        });
      });
  });

  // ======================================================
  // 🔹 Disable or hide End Time when "End Shift" is selected
  // ======================================================
  taskDescSelect.addEventListener("change", function () {
    const selectedText = taskDescSelect.options[
      taskDescSelect.selectedIndex
    ].text
      .toLowerCase()
      .trim();

    if (selectedText.includes("end shift")) {
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder = "Not required for End Shift";
    } else {
      endTimeInput.disabled = false;
      endTimeInput.placeholder = "Select end time (optional)";
    }
  });

  // ======================================================
  // 🔹 Load Recipients (Admins or Supervisors)
  // ======================================================
  fetch("../backend/dtr-requests/get_task_insertion_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      recipientSelect.innerHTML = `<option value="">Select Recipient</option>`;
      data.forEach((user) => {
        const opt = document.createElement("option");
        opt.value = user.id;
        opt.textContent = `${user.full_name} (${user.role})`;
        recipientSelect.appendChild(opt);
      });
    })
    .catch(() => {
      recipientSelect.innerHTML = `<option value="">No recipients available</option>`;
    });

  // ======================================================
  // 🔹 Submit Task Insertion Request
  // ======================================================
  submitBtn.addEventListener("click", function () {
    alertBox.classList.add("d-none");
    submitBtn.disabled = true;

    const formData = new FormData(form);

    // ⛔ Simplified validation (end_time optional now)
    const requiredFields = [
      "date",
      "work_mode_id",
      "task_description_id",
      "start_time",
      "reason",
      "recipient_id",
    ];

    for (const field of requiredFields) {
      if (!formData.get(field)) {
        showAlert("⚠️ Please fill in all required fields.", false);
        submitBtn.disabled = false;
        return;
      }
    }

    fetch("../backend/dtr-requests/insert_task_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        showAlert(data.message, data.success);
        if (data.success) {
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("taskInsertionModal")
            );
            modal.hide();
            form.reset();
            document
              .querySelector("#task-insertion-requests-table")
              ?.dispatchEvent(new Event("refreshTable"));
          }, 1200);
        }
      })
      .catch(() => {
        showAlert("❌ An unexpected error occurred. Please try again.", false);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  // ======================================================
  // 🔹 Helper for showing alerts
  // ======================================================
  function showAlert(message, success) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none", "alert-success", "alert-danger");
    alertBox.classList.add(success ? "alert-success" : "alert-danger");
  }
});

// ======================================================
// 🔹 Global function to open the modal
// ======================================================
function openTaskInsertionModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("taskInsertionModal")
  );
  modal.show();
}*/

// CLEAN + FINAL VERSION TESTING WITH NEW FORGOT MISSED TASK LOGIC
// WORKING VERSION
/*
document.addEventListener("DOMContentLoaded", function () {
  const workModeSelect = document.getElementById("insertWorkMode");
  const taskDescSelect = document.getElementById("insertTaskDescription");
  const recipientSelect = document.getElementById("RecipientSelectInsertion");
  const alertBox = document.getElementById("insertTaskAlert");
  const submitBtn = document.getElementById("submitTaskInsertionBtn");
  const form = document.getElementById("taskInsertionForm");
  const endTimeInput = document.getElementById("insertEndTime");
  const startTimeInput = document.getElementById("insertStartTime");
  const dateInput = document.getElementById("insertDate");

  let earliestStartTime = null; // 🕓 will store earliest existing start_time from backend

  // ======================================================
  // 🔹 Fetch earliest start time for selected date
  // ======================================================
  async function fetchEarliestStartTime() {
    const date = dateInput.value;
    if (!date) return;

    try {
      const res = await fetch(
        `../backend/dtr-requests/get_earliest_start_time.php?date=${date}`
      );
      const data = await res.json();
      earliestStartTime = data.earliest || null;
      console.log("Earliest start time for", date, ":", earliestStartTime);
    } catch (err) {
      console.error("Failed to fetch earliest start time:", err);
      earliestStartTime = null;
    }
  }

  // ======================================================
  // 🔹 Fetch earliest start time immediately if date already filled
  // ======================================================
  if (dateInput.value) fetchEarliestStartTime();

  // Trigger fetch again when date changes
  dateInput.addEventListener("change", fetchEarliestStartTime);

  // ======================================================
  // 🔹 Detect "Forgot First Task" (start < earliest) → disable end time (COMMENT OUT)
  // ======================================================
  /*
  startTimeInput.addEventListener("change", function () {
    if (!earliestStartTime) return;

    const selectedStart = startTimeInput.value;
    if (!selectedStart) return;

    // Compare HH:MM format times
    if (selectedStart < earliestStartTime.slice(11, 16)) {
      // earlier than first task
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder = "Disabled for 'Forgot First Task'";
    } else {
      // revert normal state
      if (
        !taskDescSelect.options[taskDescSelect.selectedIndex]?.text
          .toLowerCase()
          .includes("end shift")
      ) {
        endTimeInput.disabled = false;
        endTimeInput.placeholder = "Select end time (optional)";
      }
    }
  });

  startTimeInput.addEventListener("change", function () {
    if (!earliestStartTime) return;
    const selectedStart = startTimeInput.value; // "HH:MM"
    if (!selectedStart) return;

    // Parse earliest start time from backend
    const earliestTime = earliestStartTime.slice(11, 16); // "HH:MM"

    // Convert to minutes since midnight for proper comparison
    const [selH, selM] = selectedStart.split(":").map(Number);
    const selectedMinutes = selH * 60 + selM;

    const [earH, earM] = earliestTime.split(":").map(Number);
    const earliestMinutes = earH * 60 + earM;

    const taskText = taskDescSelect.options[taskDescSelect.selectedIndex]?.text
      .toLowerCase()
      .trim();

    if (selectedMinutes < earliestMinutes || taskText.includes("end shift")) {
      // Disable end time for "Forgot First Task" or "End Shift"
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder =
        selectedMinutes < earliestMinutes
          ? "Disabled for 'Forgot First Task'"
          : "Not required for End Shift";
    } else {
      // Enable end time normally
      endTimeInput.disabled = false;
      endTimeInput.placeholder = "Select end time (optional)";
    }
  });

  // ======================================================
  // 🔹 Load Work Modes
  // ======================================================
  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((data) => {
      workModeSelect.innerHTML = `<option value="">Select Work Mode</option>`;
      data.forEach((mode) => {
        const opt = document.createElement("option");
        opt.value = mode.id;
        opt.textContent = mode.name;
        workModeSelect.appendChild(opt);
      });
    });

  // ======================================================
  // 🔹 Load Task Descriptions dynamically
  // ======================================================
  workModeSelect.addEventListener("change", function () {
    const workModeId = this.value;
    taskDescSelect.innerHTML = `<option value="">Select Task</option>`;
    if (!workModeId) return;

    fetch(`../backend/get_task_descriptions.php?work_mode_id=${workModeId}`)
      .then((res) => res.json())
      .then((data) => {
        data.forEach((task) => {
          const opt = document.createElement("option");
          opt.value = task.id;
          opt.textContent = task.description;
          taskDescSelect.appendChild(opt);
        });
      });
  });

  // ======================================================
  // 🔹 Disable End Time when "End Shift" is selected
  // ======================================================
  taskDescSelect.addEventListener("change", function () {
    const selectedText = taskDescSelect.options[
      taskDescSelect.selectedIndex
    ]?.text
      .toLowerCase()
      .trim();

    if (selectedText.includes("end shift")) {
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder = "Not required for End Shift";
    } else {
      endTimeInput.disabled = false;
      endTimeInput.placeholder = "Select end time (optional)";
    }
  });

  // ======================================================
  // 🔹 Load Recipients
  // ======================================================
  fetch("../backend/dtr-requests/get_task_insertion_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      recipientSelect.innerHTML = `<option value="">Select Recipient</option>`;
      data.forEach((user) => {
        const opt = document.createElement("option");
        opt.value = user.id;
        opt.textContent = `${user.full_name} (${user.role})`;
        recipientSelect.appendChild(opt);
      });
    })
    .catch(() => {
      recipientSelect.innerHTML = `<option value="">No recipients available</option>`;
    });

  // ======================================================
  // 🔹 Submit Task Insertion Request
  // ======================================================
  submitBtn.addEventListener("click", function () {
    alertBox.classList.add("d-none");
    submitBtn.disabled = true;

    const formData = new FormData(form);

    const requiredFields = [
      "date",
      "work_mode_id",
      "task_description_id",
      "start_time",
      "reason",
      "recipient_id",
    ];

    for (const field of requiredFields) {
      if (!formData.get(field)) {
        showAlert("⚠️ Please fill in all required fields.", false);
        submitBtn.disabled = false;
        return;
      }
    }

    fetch("../backend/dtr-requests/insert_task_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        showAlert(data.message, data.success);
        if (data.success) {
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("taskInsertionModal")
            );
            modal.hide();
            form.reset();
            document
              .querySelector("#task-insertion-requests-table")
              ?.dispatchEvent(new Event("refreshTable"));
          }, 1200);
        }
      })
      .catch(() => {
        showAlert("❌ An unexpected error occurred. Please try again.", false);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  // ======================================================
  // 🔹 Helper for showing alerts
  // ======================================================
  function showAlert(message, success) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none", "alert-success", "alert-danger");
    alertBox.classList.add(success ? "alert-success" : "alert-danger");
  }
});

// ======================================================
// 🔹 Global function to open the modal
// ======================================================
function openTaskInsertionModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("taskInsertionModal")
  );
  modal.show();
}*/

// CLEAN + PATCHED FINAL VERSION WITH FORGOT FIRST TASK LOGIC (WORKING)
/*
document.addEventListener("DOMContentLoaded", function () {
  const workModeSelect = document.getElementById("insertWorkMode");
  const taskDescSelect = document.getElementById("insertTaskDescription");
  const recipientSelect = document.getElementById("RecipientSelectInsertion");
  const alertBox = document.getElementById("insertTaskAlert");
  const submitBtn = document.getElementById("submitTaskInsertionBtn");
  const form = document.getElementById("taskInsertionForm");
  const endTimeInput = document.getElementById("insertEndTime");
  const startTimeInput = document.getElementById("insertStartTime");
  const dateInput = document.getElementById("insertDate");

  let earliestStartTime = null; // 🕓 store earliest start_time from backend

  // ======================================================
  // 🔹 Fetch earliest start time
  // ======================================================
  async function fetchEarliestStartTime() {
    const date = dateInput.value;
    if (!date) return;

    try {
      const res = await fetch(
        `../backend/dtr-requests/get_earliest_start_time.php?date=${date}`
      );
      const data = await res.json();
      earliestStartTime = data.earliest || null;
      console.log("Earliest start time for", date, ":", earliestStartTime);

      // Ensure end-time logic runs if start time already set
      handleEndTimeState();
    } catch (err) {
      console.error("Failed to fetch earliest start time:", err);
      earliestStartTime = null;
    }
  }

  // Initial fetch if date is pre-filled
  if (dateInput.value) fetchEarliestStartTime();

  // Fetch when date changes
  dateInput.addEventListener("change", fetchEarliestStartTime);

  // ======================================================
  // 🔹 Unified function to handle end-time disable logic
  // ======================================================
  function handleEndTimeState() {
    const selectedStart = startTimeInput.value;
    const selectedTaskText = taskDescSelect.options[
      taskDescSelect.selectedIndex
    ]?.text
      .toLowerCase()
      .trim();

    let disableEnd = false;
    let placeholderText = "Select end time (optional)";

    if (selectedStart) {
      // Parse selected start as Date
      const [selH, selM] = selectedStart.split(":").map(Number);
      const selectedDate = new Date(0, 0, 0, selH, selM);

      if (earliestStartTime) {
        // Attempt to parse backend datetime safely
        let earliestTimeStr = null;

        if (typeof earliestStartTime === "string") {
          // Accept "YYYY-MM-DD HH:MM:SS" or "HH:MM:SS"
          earliestTimeStr = earliestStartTime.includes(" ")
            ? earliestStartTime.split(" ")[1] // get "HH:MM:SS"
            : earliestStartTime;
        }

        if (earliestTimeStr) {
          const [earH, earM] = earliestTimeStr.split(":").map(Number);
          const earliestDate = new Date(0, 0, 0, earH, earM);

          if (selectedDate < earliestDate) {
            disableEnd = true;
            placeholderText = "Disabled for 'Forgot First Task'";
          }
        }
      }
    }

    if (selectedTaskText?.includes("end shift")) {
      disableEnd = true;
      placeholderText = "Not required for End Shift";
    }

    // Apply to end time input
    if (disableEnd) {
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder = placeholderText;
    } else {
      endTimeInput.disabled = false;
      endTimeInput.placeholder = "Select end time (optional)";
    }
  }

  // ======================================================
  // 🔹 Event listeners
  // ======================================================
  startTimeInput.addEventListener("change", handleEndTimeState);
  taskDescSelect.addEventListener("change", handleEndTimeState);

  // ======================================================
  // 🔹 Load Work Modes
  // ======================================================
  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((data) => {
      workModeSelect.innerHTML = `<option value="">Select Work Mode</option>`;
      data.forEach((mode) => {
        const opt = document.createElement("option");
        opt.value = mode.id;
        opt.textContent = mode.name;
        workModeSelect.appendChild(opt);
      });
    });

  // ======================================================
  // 🔹 Load Task Descriptions dynamically
  // ======================================================
  workModeSelect.addEventListener("change", function () {
    const workModeId = this.value;
    taskDescSelect.innerHTML = `<option value="">Select Task</option>`;
    if (!workModeId) return;

    fetch(`../backend/get_task_descriptions.php?work_mode_id=${workModeId}`)
      .then((res) => res.json())
      .then((data) => {
        data.forEach((task) => {
          const opt = document.createElement("option");
          opt.value = task.id;
          opt.textContent = task.description;
          taskDescSelect.appendChild(opt);
        });
      });
  });

  // ======================================================
  // 🔹 Load Recipients
  // ======================================================
  fetch("../backend/dtr-requests/get_task_insertion_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      recipientSelect.innerHTML = `<option value="">Select Recipient</option>`;
      data.forEach((user) => {
        const opt = document.createElement("option");
        opt.value = user.id;
        opt.textContent = `${user.full_name} (${user.role})`;
        recipientSelect.appendChild(opt);
      });
    })
    .catch(() => {
      recipientSelect.innerHTML = `<option value="">No recipients available</option>`;
    });

  // ======================================================
  // 🔹 Submit Task Insertion Request
  // ======================================================
  submitBtn.addEventListener("click", function () {
    alertBox.classList.add("d-none");
    submitBtn.disabled = true;

    const formData = new FormData(form);

    const requiredFields = [
      "date",
      "work_mode_id",
      "task_description_id",
      "start_time",
      "reason",
      "recipient_id",
    ];

    for (const field of requiredFields) {
      if (!formData.get(field)) {
        showAlert("⚠️ Please fill in all required fields.", false);
        submitBtn.disabled = false;
        return;
      }
    }

    fetch("../backend/dtr-requests/insert_task_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        showAlert(data.message, data.success);
        if (data.success) {
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("taskInsertionModal")
            );
            modal.hide();
            form.reset();
            document
              .querySelector("#task-insertion-requests-table")
              ?.dispatchEvent(new Event("refreshTable"));
          }, 1200);
        }
      })
      .catch(() => {
        showAlert("❌ An unexpected error occurred. Please try again.", false);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  // ======================================================
  // 🔹 Helper for showing alerts
  // ======================================================
  function showAlert(message, success) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none", "alert-success", "alert-danger");
    alertBox.classList.add(success ? "alert-success" : "alert-danger");
  }
});

// ======================================================
// 🔹 Global function to open the modal
// ======================================================
function openTaskInsertionModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("taskInsertionModal")
  );
  modal.show();
}*/

// CLEAN + PATCHED FINAL VERSION WITH FORGOT FIRST TASK LOGIC + OPTIONAL END TIME
document.addEventListener("DOMContentLoaded", function () {
  const workModeSelect = document.getElementById("insertWorkMode");
  const taskDescSelect = document.getElementById("insertTaskDescription");
  const recipientSelect = document.getElementById("RecipientSelectInsertion");
  const alertBox = document.getElementById("insertTaskAlert");
  const submitBtn = document.getElementById("submitTaskInsertionBtn");
  const form = document.getElementById("taskInsertionForm");
  const endTimeInput = document.getElementById("insertEndTime");
  const startTimeInput = document.getElementById("insertStartTime");
  const dateInput = document.getElementById("insertDate");

  // ✅ Make sure end time is optional by default
  endTimeInput.removeAttribute("required");
  endTimeInput.placeholder = "Select end time (optional)";

  let earliestStartTime = null;

  async function fetchEarliestStartTime() {
    const date = dateInput.value;
    if (!date) return;

    try {
      const res = await fetch(
        `../backend/dtr-requests/get_earliest_start_time.php?date=${date}`,
      );
      const data = await res.json();
      earliestStartTime = data.earliest || null;
      handleEndTimeState();
    } catch (err) {
      console.error("Failed to fetch earliest start time:", err);
      earliestStartTime = null;
    }
  }

  if (dateInput.value) fetchEarliestStartTime();
  dateInput.addEventListener("change", fetchEarliestStartTime);

  function handleEndTimeState() {
    const selectedStart = startTimeInput.value;
    const selectedTaskText = taskDescSelect.options[
      taskDescSelect.selectedIndex
    ]?.text
      .toLowerCase()
      .trim();

    let disableEnd = false;
    let placeholderText = "Select end time (optional)";

    if (selectedStart) {
      const [selH, selM] = selectedStart.split(":").map(Number);
      const selectedDate = new Date(0, 0, 0, selH, selM);

      if (earliestStartTime) {
        let earliestTimeStr = null;

        if (typeof earliestStartTime === "string") {
          earliestTimeStr = earliestStartTime.includes(" ")
            ? earliestStartTime.split(" ")[1]
            : earliestStartTime;
        }

        if (earliestTimeStr) {
          const [earH, earM] = earliestTimeStr.split(":").map(Number);
          const earliestDate = new Date(0, 0, 0, earH, earM);

          /*if (selectedDate < earliestDate) {
            disableEnd = true;
            placeholderText = "Disabled for 'Forgot First Task'";
          }*/
          // Forgot-first-task is now allowed with optional OR manual end time
          if (selectedDate < earliestDate) {
            disableEnd = false;
            placeholderText =
              "Optional: leave blank to auto-infer next task start";
          }
        }
      }
    }

    if (selectedTaskText?.includes("end shift")) {
      disableEnd = true;
      placeholderText = "Not required for End Shift";
    }

    if (disableEnd) {
      endTimeInput.value = "";
      endTimeInput.disabled = true;
      endTimeInput.removeAttribute("required");
      endTimeInput.placeholder = placeholderText;
    } else {
      endTimeInput.disabled = false;
      endTimeInput.removeAttribute("required"); // ✅ always optional now
      endTimeInput.placeholder = "Select end time (optional)";
    }
  }

  startTimeInput.addEventListener("change", handleEndTimeState);
  taskDescSelect.addEventListener("change", handleEndTimeState);

  fetch("../backend/get_work_modes.php")
    .then((res) => res.json())
    .then((data) => {
      workModeSelect.innerHTML = `<option value="">Select Work Mode</option>`;
      data.forEach((mode) => {
        const opt = document.createElement("option");
        opt.value = mode.id;
        opt.textContent = mode.name;
        workModeSelect.appendChild(opt);
      });
    });

  workModeSelect.addEventListener("change", function () {
    const workModeId = this.value;
    taskDescSelect.innerHTML = `<option value="">Select Task</option>`;
    if (!workModeId) return;

    fetch(`../backend/get_task_descriptions.php?work_mode_id=${workModeId}`)
      .then((res) => res.json())
      .then((data) => {
        data.forEach((task) => {
          const opt = document.createElement("option");
          opt.value = task.id;
          opt.textContent = task.description;
          taskDescSelect.appendChild(opt);
        });
      });
  });

  fetch("../backend/dtr-requests/get_task_insertion_recipients.php")
    .then((res) => res.json())
    .then((data) => {
      recipientSelect.innerHTML = `<option value="">Select Recipient</option>`;
      data.forEach((user) => {
        const opt = document.createElement("option");
        opt.value = user.id;
        opt.textContent = `${user.full_name} (${user.role})`;
        recipientSelect.appendChild(opt);
      });
    })
    .catch(() => {
      recipientSelect.innerHTML = `<option value="">No recipients available</option>`;
    });

  submitBtn.addEventListener("click", function () {
    alertBox.classList.add("d-none");
    submitBtn.disabled = true;

    const formData = new FormData(form);

    // ✅ end_time removed from required fields
    const requiredFields = [
      "date",
      "work_mode_id",
      "task_description_id",
      "start_time",
      "reason",
      "recipient_id",
    ];

    for (const field of requiredFields) {
      if (!formData.get(field)) {
        showAlert("⚠️ Please fill in all required fields.", false);
        submitBtn.disabled = false;
        return;
      }
    }

    fetch("../backend/dtr-requests/insert_task_request.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        showAlert(data.message, data.success);
        if (data.success) {
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("taskInsertionModal"),
            );
            modal.hide();
            form.reset();
            endTimeInput.removeAttribute("required");
            endTimeInput.disabled = false;
            endTimeInput.placeholder = "Select end time (optional)";
            document
              .querySelector("#task-insertion-requests-table")
              ?.dispatchEvent(new Event("refreshTable"));
          }, 1200);
        }
      })
      .catch(() => {
        showAlert("❌ An unexpected error occurred. Please try again.", false);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  function showAlert(message, success) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none", "alert-success", "alert-danger");
    alertBox.classList.add(success ? "alert-success" : "alert-danger");
  }
});

function openTaskInsertionModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("taskInsertionModal"),
  );
  modal.show();
}
