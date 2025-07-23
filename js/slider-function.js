// ============================================
// ===== SLIDE-TO-TAG BUTTON INTERACTION ======
// ============================================

document.addEventListener("DOMContentLoaded", () => {
  let isSliding = false;
  let startX = 0;
  let currentX = 0;

  const handle = document.getElementById("slideButtonHandle");
  const wrapper = document.getElementById("slideButtonWrapper");
  if (!handle || !wrapper) console.warn("Missing handle or wrapper for slide button.");


  handle.addEventListener("mousedown", (e) => {
    isSliding = true;
    startX = e.clientX;
    currentX = startX;
    document.body.style.userSelect = "none";
    wrapper.style.cursor = "grabbing";
  });

  document.addEventListener("mousemove", (e) => {
    if (!isSliding) return;
    currentX = e.clientX;
    const maxSlide = wrapper.clientWidth - handle.clientWidth;
    const delta = Math.min(maxSlide, Math.max(0, currentX - startX));
    handle.style.left = `${delta}px`;
  });

  document.addEventListener("mouseup", () => {
    if (!isSliding) return;
    isSliding = false;
    document.body.style.userSelect = "";
    wrapper.style.cursor = "grab";
    const maxSlide = wrapper.clientWidth - handle.clientWidth;

    if (currentX - startX >= maxSlide * 0.9) {
      handle.textContent = "Tagging...";
      const overlay = document.getElementById("globalOverlay");
      if (overlay) overlay.style.display = "flex";

      setTimeout(() => {
        startTask();
        if (overlay) overlay.style.display = "none";
        handle.style.left = "0";
        handle.textContent = "▶ Slide to Tag";
      }, 1500);
    } else {
      handle.style.left = "0";
    }
  });
});
