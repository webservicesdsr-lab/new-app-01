/**
 * ==========================================================
 * Kingdom Nexus - Edit Hub Identity Script (v4.3 Production)
 * ----------------------------------------------------------
 * Updates hub identity information via REST:
 * ✅ Supports City ID, Email, Phone, and Status
 * ✅ Secure nonce + unified knxToast()
 * ✅ Fully compatible with api-get-hub.php & api-edit-hub-identity.php
 * ==========================================================
 */

document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.querySelector(".knx-edit-hub-wrapper");
  if (!wrapper) return;

  const getApi = wrapper.dataset.apiGet;
  const apiUrl = wrapper.dataset.apiIdentity;
  const hubId = wrapper.dataset.hubId;
  const nonce = wrapper.dataset.nonce;

  const nameInput = document.getElementById("hubName");
  const phoneInput = document.getElementById("hubPhone");
  const emailInput = document.getElementById("hubEmail");
  const statusSelect = document.getElementById("hubStatus");
  const citySelect = document.getElementById("hubCity");
  const saveBtn = document.getElementById("saveIdentity");

  /**
   * ----------------------------------------------------------
   * Load hub data
   * ----------------------------------------------------------
   */
  async function loadHubData() {
    try {
      const res = await fetch(`${getApi}?id=${hubId}`);
      const data = await res.json();

      if (data.success && data.hub) {
        const hub = data.hub;
        nameInput.value = hub.name || "";
        phoneInput.value = hub.phone || "";
        emailInput.value = hub.email || "";
        statusSelect.value = hub.status || "active";

        if (citySelect && hub.city_id) {
          citySelect.value = hub.city_id;
        }
      } else {
        knxToast("Unable to load hub data", "error");
      }
    } catch {
      knxToast("Network error while loading hub data", "error");
    }
  }

  /**
   * ----------------------------------------------------------
   * Save hub identity
   * ----------------------------------------------------------
   */
  saveBtn.addEventListener("click", async () => {
    const payload = {
      hub_id: parseInt(hubId),
      city_id: citySelect ? parseInt(citySelect.value) || 0 : 0,
      email: emailInput.value.trim(),
      phone: phoneInput.value.trim(),
      status: statusSelect.value,
      knx_nonce: nonce,
    };

    if (!payload.email) {
      knxToast("Email is required", "error");
      return;
    }

    try {
      const res = await fetch(apiUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (data.success) {
        knxToast(data.message || "Hub identity updated successfully", "success");
        loadHubData();
      } else {
        const msg =
          data.error === "invalid_city"
            ? "Invalid or inactive city selected"
            : data.error === "unauthorized"
            ? "Access denied"
            : "Update failed";
        knxToast(msg, "error");
      }
    } catch {
      knxToast("Connection error saving identity", "error");
    }
  });

  loadHubData();
});
