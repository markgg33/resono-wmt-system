$(document).ready(function () {
  //TIME HELPER NEW FOR SECONDS REMOVAL
  function formatToHHMM(timeStr) {
    if (!timeStr) return "--";
    timeStr = timeStr.toString().trim();

    // Convert AM/PM to 24-hour if necessary
    const ampmMatch = timeStr.match(
      /^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)$/i
    );
    if (ampmMatch) {
      let [_, h, m, , ampm] = ampmMatch;
      h = parseInt(h, 10);
      if (ampm.toUpperCase() === "PM" && h < 12) h += 12;
      if (ampm.toUpperCase() === "AM" && h === 12) h = 0;
      return `${String(h).padStart(2, "0")}:${m}`;
    }

    // Handle HH:MM:SS or HH:MM
    const parts = timeStr.split(":");
    if (parts.length >= 2) {
      return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}`;
    }

    return timeStr;
  }

  // Open modal
  $(document).on("click", ".request-amendment-btn", function () {
    $("#logId").val($(this).data("id"));
    $("#amendDate").val($(this).data("date"));

    // Format old start/end times to 24-hour no seconds
    // Read directly from the DOM attributes instead of jQuery's cached data
    const oldStartRaw = $(this).attr("data-old-start");
    const oldEndRaw = $(this).attr("data-old-end");

    const oldStart = formatToHHMM(oldStartRaw);
    const oldEnd = formatToHHMM(oldEndRaw);

    $("#oldStartTime").val(oldStart);
    $("#oldEndTime").val(oldEnd);

    // Also set hidden fields for backend (unchanged raw values)
    $("#oldStartTimeHidden").val($(this).data("old-start"));
    $("#oldEndTimeHidden").val($(this).data("old-end"));

    // Default field = start_time
    $("#field").val("start_time").trigger("change");

    $("#userAmendmentModal").modal("show");
  });

  // When field changes, update oldValue (WORKING VERSION)
  /*$("#field").on("change", function () {
    let field = $(this).val();
    let btn = $(".request-amendment-btn[data-id='" + $("#logId").val() + "']");
    if (field === "start_time") $("#oldValue").val(btn.data("old-start"));
    else if (field === "end_time") $("#oldValue").val(btn.data("old-end"));
    else if (field === "remarks") $("#oldValue").val(btn.data("old-remarks"));
  });*/

  // When field changes, update oldValue and input type
  $("#field").on("change", function () {
    let field = $(this).val();
    let btn = $(".request-amendment-btn[data-id='" + $("#logId").val() + "']");

    if (field === "start_time") {
      $("#oldValue").val(btn.data("old-start"));
      setNewValueInput("time"); // time picker
    } else if (field === "end_time") {
      $("#oldValue").val(btn.data("old-end"));
      setNewValueInput("time");
    } else if (field === "date") {
      $("#oldValue").val($("#amendDate").val()); // current log date
      setNewValueInput("date"); // date picker
    } else if (field === "remarks") {
      $("#oldValue").val(btn.data("old-remarks"));
      setNewValueInput("text");
    }
  });

  // Helper to swap input type dynamically
  function setNewValueInput(type) {
    let wrapper = $("#newValueWrapper");
    wrapper.empty();

    if (type === "time") {
      wrapper.append(
        '<input type="time" id="newValue" name="new_value" class="form-control" required>'
      );
    } else if (type === "date") {
      wrapper.append(
        '<input type="date" id="newValue" name="new_value" class="form-control" required>'
      );
    } else {
      wrapper.append(
        '<input type="text" id="newValue" name="new_value" class="form-control" required>'
      );
    }
  }

  // Submit amendment
  $("#amendmentForm").on("submit", function (e) {
    e.preventDefault();
    $.post(
      "../backend/dtr-requests/submit_amendment.php",
      $(this).serialize(),
      function (response) {
        alert(response.message);
        if (response.status === "success") {
          $("#userAmendmentModal").modal("hide");
          //sloadUserLogs();
        }
      },
      "json"
    );
  });
});
