<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Navbar Renderer (v4 Formal)
 * Solid, minimal, responsive. Hidden on internal CRUD pages.
 */

add_action('wp_body_open', function () {
    global $post;
    $slug = is_object($post) ? $post->post_name : '';

    $private_slugs = [
        'dashboard','basic-dashboard','advanced-dashboard',
        'hubs','edit-hub','edit-hub-items','edit-item-categories',
        'drivers','customers','cities','settings','menus','hub-categories'
    ];
    if (in_array($slug, $private_slugs, true)) return;

    $session = knx_get_session();
    $is_logged = $session ? true : false;
    $role = $session ? $session->role : 'guest';

    echo '<link rel="stylesheet" href="' . esc_url(KNX_URL . 'inc/modules/navbar/navbar-style.css?v=' . KNX_VERSION) . '">';
    echo '<script src="' . esc_url(KNX_URL . 'inc/modules/navbar/navbar-script.js?v=' . KNX_VERSION) . '" defer></script>';
    ?>
    <nav class="knx-nav">
      <div class="knx-nav__inner">
        <a href="<?php echo esc_url(site_url('/')); ?>" class="knx-nav__brand">Kingdom Nexus</a>

        <button class="knx-nav__toggle" id="knxNavToggle" aria-label="Toggle menu">
          <span></span><span></span><span></span>
        </button>

        <div class="knx-nav__menu" id="knxNavMenu">
          <a href="<?php echo esc_url(site_url('/')); ?>">Home</a>
          <a href="<?php echo esc_url(site_url('/about')); ?>">About</a>
          <a href="<?php echo esc_url(site_url('/blog')); ?>">Blog</a>
          <a href="<?php echo esc_url(site_url('/contact')); ?>">Contact</a>

          <?php if ($is_logged): ?>
            <?php if (in_array($role, ['manager','hub_management','menu_uploader','super_admin'], true)): ?>
              <a href="<?php echo esc_url(site_url('/hubs')); ?>">Hubs</a>
            <?php endif; ?>
            <?php if (in_array($role, ['manager','super_admin'], true)): ?>
              <a href="<?php echo esc_url(site_url('/cities')); ?>">Cities</a>
            <?php endif; ?>
            <form method="post" class="knx-nav__logout">
              <?php wp_nonce_field('knx_logout_action','knx_logout_nonce'); ?>
              <button type="submit" name="knx_logout">Logout</button>
            </form>
          <?php else: ?>
            <a class="knx-nav__cta" href="<?php echo esc_url(site_url('/login')); ?>">Login</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
    <?php
});
