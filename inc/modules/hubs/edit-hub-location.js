/**
 * Kingdom Nexus - Edit Hub Location Script (v2.3)
 * ------------------------------------------------
 * Loads Google Maps dynamically and syncs address/radius
 * Uses global knxToast() for notifications
 * Handles Brave/uBlock false blocking errors safely.
 */

document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.querySelector(".knx-edit-hub-wrapper");
  if (!wrapper) return;

  const apiUrl = wrapper.dataset.apiLocation;
  const getApi = wrapper.dataset.apiGet;
  const hubId = wrapper.dataset.hubId;
  const nonce = wrapper.dataset.nonce;

  const addressInput = document.getElementById("hubAddress");
  const radiusInput = document.getElementById("deliveryRadius");
  const latInput = document.getElementById("hubLat");
  const lngInput = document.getElementById("hubLng");
  const saveBtn = document.getElementById("saveLocation");
  const mapDiv = document.getElementById("map");

  const mapsKey = window.KNX_MAPS_KEY || null;

  // Suppress Google Maps blocked ping errors (Brave, uBlock, etc)
  window.addEventListener("error", function (e) {
    if (e.message && e.message.includes("mapsjs/gen_204")) {
      e.stopImmediatePropagation();
      return true;
    }
  });

  // --- Load hub data first ---
  async function loadHubData() {
    try {
      const res = await fetch(`${getApi}?id=${hubId}`);
      const data = await res.json();

      if (data.success && data.hub) {
        addressInput.value = data.hub.address || "";
        radiusInput.value = data.hub.delivery_radius || 3;
        latInput.value = data.hub.lat || 41.12;
        lngInput.value = data.hub.lng || -87.86;
        initMap();
      } else {
        knxToast("⚠️ Unable to load hub data", "error");
      }
    } catch (e) {
      knxToast("⚠️ Error loading hub data", "error");
    }
  }

  function initMap() {
    if (!mapsKey) {
      mapDiv.innerHTML =
        "<div class='knx-warning'>⚠️ Google Maps API key not configured.</div>";
      return;
    }

    const script = document.createElement("script");
    script.src = `https://maps.googleapis.com/maps/api/js?key=${mapsKey}&libraries=places`;
    script.async = true;
    document.head.appendChild(script);

    script.onload = () => {
      const lat = parseFloat(latInput.value) || 41.1200;
      const lng = parseFloat(lngInput.value) || -87.8611;
      const radius = parseFloat(radiusInput.value) || 3;

      const map = new google.maps.Map(mapDiv, {
        center: { lat, lng },
        zoom: 13,
      });

      const marker = new google.maps.Marker({
        position: { lat, lng },
        map,
        draggable: true,
      });

      const circle = new google.maps.Circle({
        map,
        center: { lat, lng },
        radius: milesToMeters(radius),
        fillColor: "#0b793a",
        fillOpacity: 0.25,
        strokeColor: "#0b793a",
        strokeWeight: 2,
        editable: true,
      });

      // --- Marker drag updates ---
      marker.addListener("dragend", (e) => {
        latInput.value = e.latLng.lat().toFixed(8);
        lngInput.value = e.latLng.lng().toFixed(8);
        circle.setCenter(e.latLng);
      });

      // --- Address autocomplete ---
      const autocomplete = new google.maps.places.Autocomplete(addressInput);
      autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        const pos = place.geometry.location;
        map.setCenter(pos);
        marker.setPosition(pos);
        circle.setCenter(pos);
        latInput.value = pos.lat().toFixed(8);
        lngInput.value = pos.lng().toFixed(8);
      });

      // --- Radius change events ---
      google.maps.event.addListener(circle, "radius_changed", () => {
        const miles = metersToMiles(circle.getRadius());
        radiusInput.value = miles.toFixed(2);
      });

      radiusInput.addEventListener("input", () => {
        const miles = parseFloat(radiusInput.value) || 0;
        circle.setRadius(milesToMeters(miles));
      });

      // --- Save handler ---
      saveBtn.addEventListener("click", async () => {
        const payload = {
          hub_id: hubId,
          address: addressInput.value.trim(),
          lat: parseFloat(latInput.value),
          lng: parseFloat(lngInput.value),
          delivery_radius: parseFloat(radiusInput.value),
          knx_nonce: nonce,
        };

        try {
          const res = await fetch(apiUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          });

          const data = await res.json();

          if (data.success) {
            knxToast("✅ Location updated successfully", "success");
          } else {
            knxToast("⚠️ " + (data.error || "Failed to update"), "error");
          }
        } catch (e) {
          knxToast("⚠️ Network error", "error");
        }
      });
    };
  }

  function milesToMeters(miles) {
    return miles * 1609.34;
  }

  function metersToMiles(meters) {
    return meters / 1609.34;
  }

  loadHubData();
});
