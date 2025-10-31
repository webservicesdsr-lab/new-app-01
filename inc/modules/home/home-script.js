// Kingdom Nexus - Home Page Script (v2)

document.addEventListener("DOMContentLoaded", () => {
  const searchButton = document.getElementById("knx-search-btn");
  const input = document.getElementById("knx-address-input");

  if (searchButton && input) {
    searchButton.addEventListener("click", () => {
      const value = input.value.trim();
      if (value) {
        alert(`Searching for restaurants near: ${value}`);
      } else {
        alert("Please enter your address first.");
      }
    });
  }
});
