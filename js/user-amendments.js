$(document).ready(function () {
  /* Load user logs
  function loadUserLogs() {
    $.get(
      "../backend/get_user_task_logs.php",
      function (data) {
        if (data.status === "success") {
          let rows = "";
          data.logs.forEach((log) => {
            rows += `
                        <tr>
                            <td>${log.date}</td>
                            <td>${log.task_description}</td>
                            <td>${log.start_time || "--"}</td>
                            <td>${log.end_time || "--"}</td>
                            <td>${log.computed_duration}</td>
                            <td>${log.remarks || "--"}</td>
                            <td>
                                <button class="btn btn-warning btn-sm request-amendment-btn"
                                    data-id="${log.id}"
                                    data-date="${log.date}"
                                    data-old-start="${log.start_time || ""}"
                                    data-old-end="${log.end_time || ""}"
                                    data-old-remarks="${log.remarks || ""}">
                                    Request Amendment
                                </button>
                            </td>
                        </tr>`;
          });
          $("#user-logs-table").html(rows);
        }
      },
      "json"
    );
  }

  loadUserLogs();*/

  // Open modal
  $(document).on("click", ".request-amendment-btn", function () {
    $("#logId").val($(this).data("id"));
    $("#amendDate").val($(this).data("date"));

    // Default field = start_time
    $("#field").val("start_time").trigger("change");
    $("#oldValue").val($(this).data("old-start"));

    $("#userAmendmentModal").modal("show");
  });

  // When field changes, update oldValue
  $("#field").on("change", function () {
    let field = $(this).val();
    let btn = $(".request-amendment-btn[data-id='" + $("#logId").val() + "']");
    if (field === "start_time") $("#oldValue").val(btn.data("old-start"));
    else if (field === "end_time") $("#oldValue").val(btn.data("old-end"));
    else if (field === "remarks") $("#oldValue").val(btn.data("old-remarks"));
  });

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
