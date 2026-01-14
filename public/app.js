const dropzone = document.querySelector(".upload-dropzone");
const clipboardButton = document.querySelector(
  ".upload-dropzone .ghost-button"
);

const announce = (message) => {
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;
  document.body.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.add("toast--visible");
  });

  setTimeout(() => {
    toast.classList.remove("toast--visible");
    setTimeout(() => toast.remove(), 300);
  }, 2000);
};

if (dropzone) {
  dropzone.addEventListener("click", () => {
    announce("Hier öffnet später der Dateidialog.");
  });

  dropzone.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      announce("Hier öffnet später der Dateidialog.");
    }
  });
}

if (clipboardButton) {
  clipboardButton.addEventListener("click", () => {
    announce("Zwischenablage‑Upload ist vorbereitet.");
  });
}
