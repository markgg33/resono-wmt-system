function showLoader() {
  document.getElementById("globalOverlay").style.display = "flex";
}

function hideLoader() {
  // add delay to prevent flashing
  setTimeout(() => {
    document.getElementById("globalOverlay").style.display = "none";
  }, 600); // adjust 500–800ms if needed
}

// ===== WRAPPER AROUND FETCH =====
function fetchWithLoader(url, options = {}) {
  showLoader();
  return fetch(url, options)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .finally(() => {
      hideLoader();
    });
}
