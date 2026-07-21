const data = JSON.parse(sessionStorage.getItem("commendationSuccess"));

if (!data) {
  window.location.href = "index.php";
}

document.getElementById("senderName").innerHTML =
  `Thank you, <strong>${data.sender}</strong>!`;

const recipients = document.getElementById("recipientList");

data.recipients.forEach((name) => {
  recipients.innerHTML += `

<div class="recipient">

👤 ${name}

</div>

`;
});

document.getElementById("points").innerHTML = "⭐".repeat(Number(data.points));

document.getElementById("value").textContent = data.value;

confetti({
  particleCount: 140,

  spread: 70,

  origin: { y: 0.6 },
});

//=============================================================
// CLOSE WINDOW
//==============================================================

function closePage() {
  // Clear session data
  sessionStorage.removeItem("commendationSuccess");

  // Try to close the window
  window.close();

  // If the browser blocks it, return to the landing page
  setTimeout(() => {
    window.location.href = "index.html";
  }, 300);
}
