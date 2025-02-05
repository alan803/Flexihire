document.getElementById("subsform").addEventListener("submit", function (e) {
    e.preventDefault(); // Prevent the form from refreshing the page
  
    // Hide the form and show the feedback message
    document.getElementById("subs1").style.display = "none";
    document.getElementById("feedbackContainer").style.display = "block";
  });
  