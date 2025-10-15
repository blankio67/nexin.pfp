// Get the username from the URL
const path = window.location.pathname.split("/")[1];
const username = path || "cd"; // Default to your profile if no username

fetch("bios.json")
  .then(res => res.json())
  .then(data => {
    const user = data[username];
    if (user) {
      document.getElementById("name").innerText = user.name;
      document.getElementById("bio").innerText = user.bio;
      document.getElementById("avatar").src = user.avatar;
      document.body.style.background = `linear-gradient(135deg, ${user.color}, #111)`;
    } else {
      document.body.innerHTML = `<h1>User not found 😢</h1>`;
    }
  });
