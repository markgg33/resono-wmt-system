$(document).ready(function () {
  // Open modal
  $(document).on("click", ".request-amendment-btn", function () {
    $("#logId").val($(this).data("id"));
    $("#amendDate").val($(this).data("date"));

    // Default field = start_time
    $("#field").val("start_time").trigger("change");
    $("#oldValue").val($(this).data("old-start"));

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
