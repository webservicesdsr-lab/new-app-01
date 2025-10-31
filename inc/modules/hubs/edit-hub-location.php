<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Edit Hub Location Block (v2.0)
 * ------------------------------------------------
 * Updates hub address, lat/lng, and delivery radius.
 * Integrates Google Maps API key stored in settings.
 */

global $hub;
$maps_key = get_option('knx_google_maps_key', '');
?>

<div class="knx-card">
  <h2>Location & Delivery</h2>

  <?php if (empty($maps_key)): ?>
      <p class="knx-warning">⚠️ Google Maps API key not configured. Go to Nexus Settings to add one.</p>
  <?php else: ?>
      <div class="knx-form-group">
          <label>Address</label>
          <input type="text" id="hubAddress" value="<?php echo esc_attr($hub->address); ?>" placeholder="Search address...">
      </div>

      <div id="map" class="knx-map"></div>

      <div class="knx-form-group">
          <label>Delivery Radius (miles)</label>
          <input type="number" id="deliveryRadius" value="<?php echo esc_attr($hub->delivery_radius); ?>" step="0.1">
      </div>

      <input type="hidden" id="hubLat" value="<?php echo esc_attr($hub->lat); ?>">
      <input type="hidden" id="hubLng" value="<?php echo esc_attr($hub->lng); ?>">

      <button id="saveLocation" class="knx-btn">Save Location</button>
  <?php endif; ?>
</div>
