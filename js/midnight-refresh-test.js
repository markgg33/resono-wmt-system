// =====================================================
// MIDNIGHT REFRESH TEST VERSION
// Uses DEVICE TIME
// =====================================================

(function () {
  let alertShown = false;

  function showMidnightRefreshAlert(seconds = 10) {
    let timerInterval;

    Swal.fire({
      icon: "info",
      title: "Date Change Detected",
      html: `
        <p>
          The page will refresh to ensure accurate
          task tagging and shift tracking.
        </p>

        <p>
          Refreshing in
          <strong id="refreshCountdown">${seconds}</strong>
          seconds...
        </p>
      `,
      allowOutsideClick: false,
      allowEscapeKey: false,

      showConfirmButton: true,
      confirmButtonText: "Refresh Now",

      timer: seconds * 1000,
      timerProgressBar: true,

      didOpen: () => {
        const countdown = document.getElementById("refreshCountdown");

        timerInterval = setInterval(() => {
          const remaining = Math.ceil(Swal.getTimerLeft() / 1000);

          if (countdown) {
            countdown.textContent = remaining;
          }
        }, 100);
      },

      willClose: () => {
        clearInterval(timerInterval);
      },
    }).then(() => {
      window.location.reload();
    });
  }

  /*function startTestWatcher() {
    setInterval(() => {
      const now = new Date();

      const hours = now.getHours();
      const minutes = now.getMinutes();

      // =================================================
      // CHANGE THIS FOR TESTING
      // Example: trigger at 3:15 PM
      // =================================================

      if (!alertShown && hours === 8 && minutes === 31) {
        alertShown = true;

        console.log("Midnight Refresh Test Triggered");

        showMidnightRefreshAlert(10);
      }
    }, 1000);
  }*/

  // removed persistent alert
  function startTestWatcher() {
    setInterval(() => {
      const now = new Date();

      const hours = now.getHours();
      const minutes = now.getMinutes();

      // unique key for today's trigger
      const todayKey = now.toISOString().slice(0, 10);

      const lastTrigger = sessionStorage.getItem("midnightRefresh");

      if (
        !alertShown &&
        lastTrigger !== todayKey &&
        hours === 8 &&
        minutes === 22
      ) {
        alertShown = true;

        // Remember we've already refreshed today
        sessionStorage.setItem("midnightRefresh", todayKey);

        console.log("Midnight Refresh Triggered");

        showMidnightRefreshAlert(10);
      }
    }, 1000);
  }

  document.addEventListener("DOMContentLoaded", startTestWatcher);
})();
