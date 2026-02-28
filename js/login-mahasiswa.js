function loginMahasiswa(event){
  event.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const errorMessage = document.getElementById("errorMessage");

  // Ambil data dari localStorage (sementara)
  const user = JSON.parse(localStorage.getItem("registeredMahasiswa"));

  if(!user){
    errorMessage.innerText = "Akun tidak ditemukan. Silakan daftar terlebih dahulu.";
    return;
  }

  if(email !== user.email){
    errorMessage.innerText = "Email tidak terdaftar.";
    return;
  }

  if(password.length < 6){
    errorMessage.innerText = "Password salah.";
    return;
  }

  // Simpan status login
  localStorage.setItem("role", "mahasiswa");
  localStorage.setItem("isLoggedIn", "true");

  window.location.href = "../mahasiswa/dashboard.html";
}
