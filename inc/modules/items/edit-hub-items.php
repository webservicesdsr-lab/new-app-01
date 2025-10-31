<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================================
 * Kingdom Nexus - Edit Hub Items (v2.7 Production)
 * ----------------------------------------------------------
 * ✅ Compatible con Sidebar y Toast global
 * ✅ Estructura limpia y centrada (.knx-with-sidebar)
 * ✅ REST Real (get/add/delete/reorder)
 * ✅ Categorías dinámicas (Z7E_items_categories)
 * ✅ Modales Add / Delete
 * ✅ Sin duplicar sidebar ni JS redundante
 * ==========================================================
 */

add_shortcode('knx_edit_hub_items', function() {

    $session = knx_get_session();
    if (!$session || !in_array($session->role, ['manager','super_admin','hub_management','menu_uploader'])) {
        wp_safe_redirect(site_url('/login'));
        exit;
    }

    $hub_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$hub_id) {
        echo '<div class="knx-warning">Invalid or missing Hub ID.</div>';
        return;
    }

    $nonce = wp_create_nonce('knx_edit_hub_nonce');

    $back_hubs_url  = esc_url(site_url('/hubs'));
    $manage_cats_url = esc_url(add_query_arg(['id' => $hub_id], site_url('/edit-item-categories')));

    ob_start(); ?>

<link rel="stylesheet" href="<?php echo esc_url(KNX_URL . 'inc/modules/items/edit-hub-items.css?v=' . KNX_VERSION); ?>">

<!-- Content -->
<div class="knx-content knx-with-sidebar">

  <div class="knx-items-wrapper"
      data-api-get="<?php echo esc_url(rest_url('knx/v1/get-hub-items')); ?>"
      data-api-add="<?php echo esc_url(rest_url('knx/v1/add-hub-item')); ?>"
      data-api-delete="<?php echo esc_url(rest_url('knx/v1/delete-hub-item')); ?>"
      data-api-reorder="<?php echo esc_url(rest_url('knx/v1/reorder-item')); ?>"
      data-api-cats="<?php echo esc_url(rest_url('knx/v1/get-item-categories')); ?>"
      data-hub-id="<?php echo esc_attr($hub_id); ?>"
      data-nonce="<?php echo esc_attr($nonce); ?>">

      <div class="knx-hubs-header">
        <h2><i class="fas fa-utensils"></i> Hub Menu Items</h2>

        <div class="knx-hubs-controls">
          <form class="knx-search-form" id="knxSearchForm">
            <input type="hidden" name="id" value="<?php echo esc_attr($hub_id); ?>">
            <input type="text" id="knxSearchInput" name="search" placeholder="Search items...">
            <button type="submit"><i class="fas fa-search"></i></button>
          </form>

          <a class="knx-btn-secondary" href="<?php echo $back_hubs_url; ?>">
            <i class="fas fa-arrow-left"></i> Back to Hubs
          </a>

          <a class="knx-btn-yellow" href="<?php echo $manage_cats_url; ?>">
            <i class="fas fa-layer-group"></i> Manage Categories
          </a>

          <button id="knxAddItemBtn" class="knx-add-btn">
            <i class="fas fa-plus"></i> Add Item
          </button>
        </div>
      </div>

      <!-- Categories + Items -->
      <div id="knxCategoriesContainer" class="knx-categories-container"></div>

      <!-- Pagination -->
      <div class="knx-pagination"></div>
  </div>
</div>

<!-- Modal: Add Item -->
<div id="knxAddItemModal" class="knx-modal" role="dialog" aria-modal="true" aria-labelledby="knxAddItemTitle">
  <div class="knx-modal-content">
    <h3 id="knxAddItemTitle">Add New Item</h3>
    <form id="knxAddItemForm" enctype="multipart/form-data">
      <div class="knx-form-group">
        <label for="knxItemCategorySelect">Category</label>
        <select name="category_id" id="knxItemCategorySelect" required></select>
      </div>
      <div class="knx-form-group">
        <label for="knxItemName">Name</label>
        <input type="text" id="knxItemName" name="name" placeholder="Item name" required>
      </div>
      <div class="knx-form-group">
        <label for="knxItemDescription">Description</label>
        <textarea id="knxItemDescription" name="description" placeholder="Optional description"></textarea>
      </div>
      <div class="knx-form-group">
        <label for="knxItemPrice">Price (USD)</label>
        <input type="number" id="knxItemPrice" step="0.01" name="price" placeholder="0.00" required>
      </div>
      <div class="knx-form-group">
        <label for="knxItemImageInput">Image</label>
        <input type="file" id="knxItemImageInput" name="item_image" accept="image/*" required>
      </div>

      <div class="knx-modal-actions">
        <button type="submit" class="knx-btn">Save</button>
        <button type="button" id="knxCloseModal" class="knx-btn-secondary">Cancel</button>
        <a class="knx-btn-link" id="knxGoManageCats" href="<?php echo $manage_cats_url; ?>">
          Manage categories
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Delete Item -->
<div id="knxDeleteItemModal" class="knx-modal" role="dialog" aria-modal="true" aria-labelledby="knxDeleteItemTitle">
  <div class="knx-modal-content">
    <h3 id="knxDeleteItemTitle">Confirm delete</h3>
    <p>This action cannot be undone.</p>
    <div class="knx-modal-actions">
      <button type="button" class="knx-btn" id="knxConfirmDeleteItemBtn">Delete</button>
      <button type="button" class="knx-btn-secondary" id="knxCancelDeleteItemBtn">Cancel</button>
    </div>
    <input type="hidden" id="knxDeleteItemId" value="">
  </div>
</div>

<noscript>
  <p style="text-align:center;color:#b00020;margin-top:10px;">
    JavaScript is required for this page to function properly.
  </p>
</noscript>

<script src="<?php echo esc_url(KNX_URL . 'inc/modules/items/edit-hub-items.js?v=' . KNX_VERSION); ?>"></script>

<style>
/* Sidebar-aware layout */
.knx-with-sidebar {
  margin-left: 230px;
  min-height: 100vh;
  padding-bottom: 40px;
}
@media (max-width: 900px) {
  .knx-with-sidebar { margin-left: 70px; }
}
</style>

<?php
    return ob_get_clean();
});
