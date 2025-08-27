// Handle edit button click
$(document).on("click", ".edit-request-btn", function () {
  const id = $(this).data("id");
  const newValue = $(this).data("newvalue");
  const reason = $(this).data("reason");

  $("#edit-request-id").val(id);
  $("#edit-new-value").val(newValue);
  $("#edit-reason").val(reason);

  $("#editRequestModal").modal("show");
});

// Handle form submission
$("#edit-request-form").submit(function (e) {
  e.preventDefault();
  const requestId = $("#edit-request-id").val();
  const newValue = $("#edit-new-value").val();
  const reason = $("#edit-reason").val();

  $.ajax({
    url: "../backend/dtr-requests/update_user_amendment.php",
    method: "POST",
    data: { id: requestId, new_value: newValue, reason: reason },
    success: function (res) {
      let data;
      try {
        data = JSON.parse(res);
      } catch (e) {
        alert("Invalid response from server");
        return;
      }
      if (data.status === "success") {
        $("#editRequestModal").modal("hide");
        loadUserAmendments();
      } else {
        alert(data.message || "Failed to update");
      }
    },
  });
});
