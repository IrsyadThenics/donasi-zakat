    const btnDonatur = document.getElementById("btnDonatur");
    const btnPenerima = document.getElementById("btnPenerima");
    const form = document.getElementById("registerForm");
    const roleText = document.querySelector(".role-indicator");
    let role = "";

    btnDonatur.addEventListener("click", () => {
      role = "donatur";
      form.classList.remove("hidden");
      roleText.textContent = "Mendaftar sebagai Donatur";
      btnDonatur.classList.add("active");
      btnPenerima.classList.remove("active");
    });

    btnPenerima.addEventListener("click", () => {
      role = "penerima";
      form.classList.remove("hidden");
      roleText.textContent = "Mendaftar sebagai Penerima";
      btnPenerima.classList.add("active");
      btnDonatur.classList.remove("active");
    });
