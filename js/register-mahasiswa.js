function registerMahasiswa(){

  const nama = document.getElementById("nama").value;
  const npm = document.getElementById("npm").value;
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const prodi = document.getElementById("prodi").value;
  const angkatan = document.getElementById("angkatan").value;

  if(!nama || !npm || !email || !password || !prodi || !angkatan){
    alert("Semua field wajib diisi!");
    return;
  }

  if(password.length < 6){
    alert("Password minimal 6 karakter!");
    return;
  }

  // Simulasi simpan data
  localStorage.setItem("registeredMahasiswa", JSON.stringify({
    nama, npm, email, prodi, angkatan
  }));

  alert("Registrasi berhasil! Silakan login.");
  window.location.href = "../login/mahasiswa.html";
}
