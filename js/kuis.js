let currentQuestion = 1;
let totalQuestions = 3;

function nextQuestion(){
  if(currentQuestion < totalQuestions){
    currentQuestion++;
    document.getElementById("current").innerText = currentQuestion;
  } else {
    alert("Kuis selesai!");
    window.location.href = "hasil-kuis.html";
  }
}

/* TIMER */
let time = 900; // 15 menit

let timerInterval = setInterval(() => {
  let minutes = Math.floor(time / 60);
  let seconds = time % 60;

  document.getElementById("timer").innerText =
    `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

  time--;

  if(time < 0){
    clearInterval(timerInterval);
    alert("Waktu habis!");
    window.location.href = "hasil-kuis.html";
  }

},1000);
