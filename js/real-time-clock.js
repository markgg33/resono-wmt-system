function updateDateTime() {
  const now = new Date();

  // Format date: Monday, April 15, 2025
  const dateOptions = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  document.getElementById("live-date").textContent = now.toLocaleDateString(
    undefined,
    dateOptions
  );

  // Format time: 10:45:30 AM
  const timeOptions = {
    hour: "numeric",
    minute: "numeric",
    second: "numeric",
    hour12: true,
  };
  document.getElementById("live-time").textContent = now.toLocaleTimeString(
    undefined,
    timeOptions
  );
}

setInterval(updateDateTime, 1000); // Update every second
updateDateTime(); // Run on load
