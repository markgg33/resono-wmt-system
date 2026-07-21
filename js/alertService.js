// GLOBAL ALERT SERVICE

window.AlertService = {
  success(message, title = "Success") {
    return Swal.fire({
      icon: "success",
      title,
      text: message,
      confirmButtonColor: "#28a745",
    });
  },

  error(message, title = "Error") {
    return Swal.fire({
      icon: "error",
      title,
      text: message,
      confirmButtonColor: "#dc3545",
    });
  },

  warning(message, title = "Warning") {
    return Swal.fire({
      icon: "warning",
      title,
      text: message,
      confirmButtonColor: "#ffc107",
    });
  },

  info(message, title = "Info") {
    return Swal.fire({
      icon: "info",
      title,
      text: message,
    });
  },

  confirm(message, title = "Are you sure?") {
    return Swal.fire({
      icon: "question",
      title,
      text: message,
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes",
    }).then((result) => result.isConfirmed);
  },
};