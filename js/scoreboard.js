let totalPoints = localStorage.getItem("totalPoints") || 0;

document.getElementById("userPoints").innerText = totalPoints + " pts";
document.getElementById("userPointsList").innerText = totalPoints;

function goDashboard(){
  window.location.href = "dashboard.html";
}
