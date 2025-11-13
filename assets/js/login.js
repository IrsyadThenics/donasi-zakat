    const btnDonatur = document.getElementById("btnDonatur");
    const btnPenerima = document.getElementById("btnPenerima");
    const form = document.getElementById("loginForm");
    const roleText = document.querySelector(".role-indicator");

    let role = "";

    btnDonatur.addEventListener("click", () => {
      role = "donatur";
      form.classList.remove("hidden");
      roleText.textContent = "Anda sedang login sebagai Donatur";
      btnDonatur.classList.add("active");
      btnPenerima.classList.remove("active");
    });

    btnPenerima.addEventListener("click", () => {
      role = "penerima";
      form.classList.remove("hidden");
      roleText.textContent = "Anda sedang login sebagai Penerima";
      btnPenerima.classList.add("active");
      btnDonatur.classList.remove("active");
    });

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (role === "donatur") {
        window.location.href = "dashboard-donatur.html";
      } else if (role === "penerima") {
        window.location.href = "dashboard-penerima.html";
      } else {
        alert("Silakan pilih peran terlebih dahulu!");
      }
    });