<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================================
 * Kingdom Nexus - Edit Hub Template (v3.4 Production)
 * ----------------------------------------------------------
 * Shortcode [knx_edit_hub]
 * Combines Identity + City + Location + Logo + Hours + Closure
 * - Added City dropdown (active only)
 * - Fully REST integrated with update-hub-identity
 * - Preserves previous logic and structure
 * ==========================================================
 */

add_shortcode('knx_edit_hub', function() {
    global $wpdb;

    /** Validate session */
    $session = knx_get_session();
    if (
        !$session ||
        !in_array($session->role, [
            'super_admin',
            'manager',
            'hub_management',
            'menu_uploader',
            'vendor_owner'
        ])
    ) {
        return '<div class="knx-warning">⚠️ Unauthorized access.</div>';
    }

    /** Get hub ID */
    $hub_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$hub_id) {
        return '<div class="knx-warning">⚠️ Invalid or missing Hub ID.</div>';
    }

    /** Fetch hub data */
    $table = $wpdb->prefix . 'knx_hubs';
    $hub   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $hub_id));
    if (!$hub) {
        return '<div class="knx-warning">⚠️ Hub not found.</div>';
    }

/** Fetch active cities */
$table_cities = $wpdb->prefix . 'knx_cities';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_cities'") != $table_cities)
    $table_cities = 'Z7E_knx_cities';

$cities = $wpdb->get_results("
    SELECT id, name 
    FROM {$table_cities} 
    WHERE active = 1 
    ORDER BY name ASC
");


    /** Nonce and REST root */
    $nonce    = wp_create_nonce('knx_edit_hub_nonce');
    $wp_nonce = wp_create_nonce('wp_rest');
    $api_root = esc_url_raw(rest_url());

    /** Google Maps Key */
    $maps_key = get_option('knx_google_maps_key', '');
    if (!empty($maps_key)) {
        echo "<script>window.KNX_MAPS_KEY = '" . esc_js($maps_key) . "';</script>";
    }

    /** Internal action URLs (sin dependencias de tema) */
    $back_url       = esc_url( site_url('/hubs') );
    $edit_items_url = esc_url( add_query_arg('id', $hub_id, site_url('/edit-hub-items')) );
    $preview_url    = esc_url( function_exists('knx_get_hub_public_url')
                        ? knx_get_hub_public_url($hub_id)
                        : home_url('/hub/?id=' . $hub_id) );
    ?>

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-style.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-hours.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-settings.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-closure.css'); ?>">

    <!-- Page-only overrides: botones y ocultar top navbar global -->
    <style>
      /* ===== Hide global top navbar only on this page ===== */
      #knxTopNavbar,
      .knx-top-navbar,
      .knx-navbar,
      .site-header { display: none !important; }

      /* ===== Action bar (buttons) ===== */
      .knx-actionbar{
        display:flex;
        gap:10px;
        justify-content:flex-end;
        align-items:center;
        margin:8px 0 18px;
        flex-wrap:wrap;
      }
      .knx-actionbar .knx-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:10px 14px;
        border-radius:10px;
        border:1px solid #e7e7e7;
        background:#fff;
        text-decoration:none;
        font-weight:600;
        line-height:1;
        transition:transform .06s ease, box-shadow .12s ease, background .12s ease;
      }
      .knx-actionbar .knx-btn:hover{
        transform:translateY(-1px);
        box-shadow:0 4px 16px rgba(0,0,0,.06);
      }
      .knx-actionbar .knx-btn.primary{
        background:#0B793A;
        color:#fff;
        border-color:#0B793A;
      }
      .knx-actionbar .knx-btn.ghost{
        background:#fff;
        color:#111;
      }
      .knx-actionbar .knx-btn i{ font-size:14px; }

      /* Stack nicely on small screens */
      @media (max-width: 720px){
        .knx-actionbar{ justify-content:flex-start; }
      }
    </style>

    <div class="knx-edit-hub-wrapper"
         data-hub-id="<?php echo esc_attr($hub_id); ?>"
         data-nonce="<?php echo esc_attr($nonce); ?>"
         data-api-get="<?php echo esc_url(rest_url('knx/v1/get-hub')); ?>"
         data-api-identity="<?php echo esc_url(rest_url('knx/v1/update-hub-identity')); ?>"
         data-api-location="<?php echo esc_url(rest_url('knx/v1/update-hub-location')); ?>"
         data-api-logo="<?php echo esc_url(rest_url('knx/v1/upload-logo')); ?>">

        <div class="knx-edit-header">
            <i class="fas fa-warehouse" style="font-size:22px;color:#0B793A;"></i>
            <h1>Edit Hub</h1>
        </div>

        <!-- Action Bar -->
        <div class="knx-actionbar">
          <a class="knx-btn ghost" href="<?php echo $back_url; ?>">
            <i class="fas fa-arrow-left"></i> Back to Hubs
          </a>
<a class="knx-btn" 
   href="<?php echo esc_url(site_url('/edit-hub-items?id=' . $hub->id)); ?>">
   <i class="fas fa-pen-to-square"></i> Edit Items
</a>

          <a class="knx-btn primary" href="<?php echo $preview_url; ?>" target="_blank" rel="noopener">
            <i class="fas fa-eye"></i> Preview
          </a>
        </div>

        <!-- Identity Block -->
        <div class="knx-card" id="identityBlock">
            <h2>Identity</h2>
            <div class="knx-form-group">
                <label>Hub Name</label>
                <input type="text" id="hubName" value="<?php echo esc_attr($hub->name ?? ''); ?>" disabled>
            </div>

            <div class="knx-form-group">
                <label>City</label>
                <select id="hubCity">
                    <option value="">— Select City —</option>
                    <?php if (!empty($cities)): ?>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo esc_attr($city->id); ?>" <?php selected(intval($hub->city_id), intval($city->id)); ?>>
                                <?php echo esc_html($city->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="knx-form-group">
                <label>Phone</label>
                <input type="text" id="hubPhone" value="<?php echo esc_attr($hub->phone ?? ''); ?>" placeholder="+1 708 000 0000">
            </div>
            <div class="knx-form-group">
                <label>Email</label>
                <input type="email" id="hubEmail" value="<?php echo esc_attr($hub->email ?? ''); ?>" placeholder="email@example.com">
            </div>
            <div class="knx-form-group">
                <label>Status</label>
                <select id="hubStatus">
                    <option value="active" <?php selected($hub->status, 'active'); ?>>Active</option>
                    <option value="inactive" <?php selected($hub->status, 'inactive'); ?>>Inactive</option>
                </select>
            </div>
            <button id="saveIdentity" class="knx-btn">Save Identity</button>
        </div>

        <!-- Logo Block -->
        <div class="knx-card" id="logoBlock">
            <h2>Hub Logo</h2>
            <div class="knx-logo-preview">
                <img id="hubLogoPreview"
                     src="<?php echo esc_url($hub->logo_url ?: KNX_URL . 'assets/img/default-logo.jpg'); ?>"
                     alt="Hub Logo"
                     style="max-width:150px;border-radius:8px;">
            </div>
            <div class="knx-logo-actions">
                <input type="file" id="hubLogoInput" accept="image/*">
                <button id="uploadLogoBtn" class="knx-btn">Upload</button>
            </div>
        </div>

        <?php /* ==========================================================
             SETTINGS BLOCK (v2.0)
             Timezone Autocomplete + Currency + Tax + Min Order
             ========================================================== */ ?>
        <div class="knx-card" id="settingsBlock">
          <h2>Hub Settings</h2>

          <?php
          $timezone  = $hub->timezone ?? 'America/Chicago';
          $currency  = $hub->currency ?? 'USD';
          $tax_rate  = $hub->tax_rate ?? 0;
          $min_order = $hub->min_order ?? 0;
          ?>

          <div class="knx-two-col">
            <!-- Time Zone -->
            <div class="knx-field">
              <label for="timezone">Time Zone</label>
              <select id="timezone" name="timezone" data-search="true">
                <optgroup label="Favorites Time Zones">
                  <?php
                  $favorites = [
                      'America/Chicago'      => 'Chicago (CST/CDT)',
                      'America/Fort_Worth'   => 'Fort Worth, Texas (CST/CDT)',
                      'America/Mexico_City'  => 'Mexico City (CST/CDT)',
                      'America/Cancun'       => 'Cancún (EST)',
                  ];

                  foreach ($favorites as $zone => $label) {
                      try {
                          $tz = new DateTimeZone($zone);
                          $dt = new DateTime('now', $tz);
                          $offset = $tz->getOffset($dt);
                          $hours = intdiv($offset, 3600);
                          $minutes = abs(($offset % 3600) / 60);
                          $offset_str = sprintf('UTC%+03d:%02d', $hours, $minutes);
                          echo '<option value="' . esc_attr($zone) . '" ' . selected($timezone, $zone, false) . '>' .
                               esc_html("$label — $offset_str") . '</option>';
                      } catch (Exception $e) {
                          // Skip invalid zones
                      }
                  }
                  ?>
                </optgroup>

                <optgroup label="Global Time Zones">
                  <?php
                  foreach (DateTimeZone::listIdentifiers() as $zone) {
                      try {
                          $tz = new DateTimeZone($zone);
                          $dt = new DateTime('now', $tz);
                          $offset = $tz->getOffset($dt);
                          $hours = intdiv($offset, 3600);
                          $minutes = abs(($offset % 3600) / 60);
                          $offset_str = sprintf('UTC%+03d:%02d', $hours, $minutes);
                          echo '<option value="' . esc_attr($zone) . '" ' . selected($timezone, $zone, false) . '>' .
                               esc_html("$zone — $offset_str") . '</option>';
                      } catch (Exception $e) {
                          continue;
                      }
                  }
                  ?>
                </optgroup>
              </select>
            </div>

            <!-- Currency -->
            <div class="knx-field">
              <label for="currency">Currency</label>
              <select id="currency">
                <optgroup label="North America">
                  <option value="USD" <?php selected($currency, 'USD'); ?>>US Dollar (USD)</option>
                  <option value="CAD" <?php selected($currency, 'CAD'); ?>>Canadian Dollar (CAD)</option>
                  <option value="MXN" <?php selected($currency, 'MXN'); ?>>Mexican Peso (MXN)</option>
                </optgroup>
                <optgroup label="Europe">
                  <option value="EUR" <?php selected($currency, 'EUR'); ?>>Euro (EUR)</option>
                  <option value="GBP" <?php selected($currency, 'GBP'); ?>>British Pound (GBP)</option>
                </optgroup>
              </select>
            </div>

            <!-- Taxes -->
            <div class="knx-field">
              <label for="tax_rate">Taxes & Fee (%)</label>
              <input type="number" id="tax_rate" step="0.1" min="0" max="100" value="<?php echo esc_attr($tax_rate); ?>">
            </div>

            <!-- Minimum Order -->
            <div class="knx-field">
              <label for="min_order">Minimum Order ($)</label>
              <input type="number" id="min_order" step="0.01" min="0" value="<?php echo esc_attr($min_order); ?>">
            </div>
          </div>

          <div class="knx-save-row">
            <button id="knxSaveSettingsBtn"
                    class="knx-btn"
                    data-hub-id="<?php echo esc_attr($hub_id); ?>"
                    data-nonce="<?php echo esc_attr($nonce); ?>">
              Save Settings
            </button>
          </div>
        </div>

        <!-- Location Block -->
        <div class="knx-card" id="locationBlock">
            <h2>Location & Delivery</h2>
            <div class="knx-form-group">
                <label>Address</label>
                <input type="text" id="hubAddress" value="<?php echo esc_attr($hub->address ?? ''); ?>" placeholder="Search address...">
            </div>
            <div class="knx-form-group">
                <label>Delivery Radius (miles)</label>
                <input type="number" id="deliveryRadius" step="0.1" value="<?php echo esc_attr($hub->delivery_radius ?? ''); ?>" placeholder="3">
            </div>
            <div id="map" class="knx-map"></div>
            <input type="hidden" id="hubLat" value="<?php echo esc_attr($hub->lat ?? ''); ?>">
            <input type="hidden" id="hubLng" value="<?php echo esc_attr($hub->lng ?? ''); ?>">
            <button id="saveLocation" class="knx-btn">Save Location</button>
        </div>

        <!-- Working Hours Block -->
        <div class="knx-card" id="hoursBlock">
          <h2>Working Hours</h2>

          <?php
          $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
          $json = $wpdb->get_var($wpdb->prepare("SELECT opening_hours FROM {$table} WHERE id = %d", $hub_id));
          $hours = $json ? json_decode($json, true) : [];
          ?>

          <div id="knxHoursContainer">
            <?php foreach ($days as $day):
              $intervals = $hours[$day] ?? [];
              $first = $intervals[0] ?? ['open'=>'','close'=>''];
              $second = $intervals[1] ?? ['open'=>'','close'=>''];
              $checked = !empty($intervals) ? 'checked' : '';
              $secondChecked = !empty($intervals[1]) ? 'checked' : '';
            ?>
            <div class="knx-hours-row" data-day="<?php echo esc_attr($day); ?>">

              <!-- Main Shift -->
              <div class="main-block">
                <input type="checkbox" class="day-check" <?php echo $checked; ?>>
                <label><?php echo ucfirst(substr($day, 0, 3)); ?></label>

                <input type="time" class="open1" value="<?php echo esc_attr($first['open']); ?>">
                <span class="to-label">to</span>
                <input type="time" class="close1" value="<?php echo esc_attr($first['close']); ?>">
              </div>

              <!-- Second Shift -->
              <div class="second-block">
                <input type="checkbox" class="second-check" <?php echo $secondChecked; ?>>
                <span></span>
                <input type="time" class="open2" value="<?php echo esc_attr($second['open']); ?>">
                <span class="to-label">to</span>
                <input type="time" class="close2" value="<?php echo esc_attr($second['close']); ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="text-align:right;margin-top:15px;">
            <button id="knxSaveHoursBtn"
              class="knx-btn"
              data-hub-id="<?php echo esc_attr($hub_id); ?>"
              data-nonce="<?php echo esc_attr($nonce); ?>">
              Save
            </button>
          </div>
        </div>

        <!-- Temporary Closure Block -->
        <div class="knx-card" id="closureBlock">

          <!-- Header -->
          <div class="knx-collapse-header" onclick="this.classList.toggle('active'); document.querySelector('#closureBody').classList.toggle('open');">
            <div>
              <h2>Temporarily Closure</h2>
              <p class="knx-collapse-desc">Manage this hub’s temporary or indefinite closure settings.</p>
            </div>
            <span class="toggle-arrow">▼</span>
          </div>

          <!-- Body -->
          <div id="closureBody" class="knx-collapse-body">
            <div class="knx-field">
              <label>Status</label>
              <label class="knx-switch">
                <input type="checkbox" id="closureToggle" <?php checked($hub->is_closed, 1); ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div class="knx-field">
              <label>Closure Type</label>
              <select id="closureType" <?php echo !$hub->is_closed ? 'disabled' : ''; ?>>
                <option value="">— Select —</option>
                <option value="temporary" <?php selected($hub->closure_type, 'temporary'); ?>>Temporary</option>
                <option value="indefinite" <?php selected($hub->closure_type, 'indefinite'); ?>>Indefinite</option>
              </select>
            </div>

            <div class="knx-field">
              <label>Note (optional)</label>
              <textarea id="closureReason" placeholder="Add internal note..."><?php echo esc_textarea($hub->closure_reason ?? ''); ?></textarea>
            </div>

            <div class="knx-field" id="reopenWrapper" style="<?php echo ($hub->closure_type === 'temporary') ? '' : 'display:none;'; ?>">
              <label>Reopen Date & Time</label>
              <input type="datetime-local" id="reopenDate" 
                     value="<?php echo $hub->reopen_date ? esc_attr(date('Y-m-d\TH:i', strtotime($hub->reopen_date))) : ''; ?>">
            </div>

            <div class="knx-save-row">
              <button id="saveClosureBtn"
                      class="knx-btn"
                      data-hub-id="<?php echo esc_attr($hub_id); ?>"
                      data-nonce="<?php echo esc_attr($nonce); ?>">
                Save Closure
              </button>
            </div>
          </div>
        </div>

    </div>

<script>
  const knx_api = { root: "<?php echo $api_root; ?>" };
  const knx_edit_hub = {
      hub_id: <?php echo intval($hub_id); ?>,
      nonce: "<?php echo esc_js($nonce); ?>",
      wp_nonce: "<?php echo esc_js($wp_nonce); ?>"
  };
  window.knx_session = { role: "<?php echo esc_js($session->role ?? 'guest'); ?>" };
</script>

<!-- JS Modules -->
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-identity.js'); ?>"></script>
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-logo.js'); ?>"></script>
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-settings.js'); ?>"></script>
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-location.js'); ?>"></script>
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-hours.js'); ?>"></script>
<script src="<?php echo esc_url(KNX_URL . 'inc/modules/hubs/edit-hub-closure.js'); ?>"></script>

<?php
});
