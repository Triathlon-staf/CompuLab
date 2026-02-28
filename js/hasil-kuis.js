// Simulasi perhitungan poin berdasarkan difficulty
let score = 85; // contoh
let difficultyMultiplier = 1.2; // medium

let points = Math.round(score * difficultyMultiplier);
document.getElementById("points").innerText = points;

// Simpan sementara total poin (dummy localStorage)
let totalPoints = localStorage.getItem("totalPoints") || 0;
totalPoints = parseInt(totalPoints) + points;
localStorage.setItem("totalPoints", totalPoints);

function goDashboard(){
  window.location.href = "dashboard.html";
}

function goScoreboard(){
  window.location.href = "scoreboard.html";
}
