async function fetchServerTime() {
  try {
    const response = await fetch("../backend/get_server_time.php");
    const data = await response.json();
    return new Date(data.server_time); // server sends ISO string
  } catch (error) {
    console.error("Failed to fetch server time", error);
    return new Date(); // fallback
  }
}

let serverNow;

// Sync with server first
fetchServerTime().then((date) => {
  serverNow = date;
  updateDateTime();
  setInterval(() => {
    serverNow.setSeconds(serverNow.getSeconds() + 1); // tick server time
    updateDateTime();
  }, 1000);
});

function updateDateTime() {
  if (!serverNow) return;

  // Format date
  const dateOptions = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  document.getElementById("live-date").textContent =
    serverNow.toLocaleDateString(undefined, dateOptions);

  // Format time
  const timeOptions = {
    hour: "numeric",
    minute: "numeric",
    second: "numeric",
    hour12: true,
  };
  document.getElementById("live-time").textContent =
    serverNow.toLocaleTimeString(undefined, timeOptions);
}
