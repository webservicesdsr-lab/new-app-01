<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Home Shortcode (v2)
 *
 * Shortcode: [knx_home]
 * Public-facing landing page for customers and visitors.
 * Accessible without login, but dynamically aware of session state.
 */

add_shortcode('knx_home', function () {
    wp_enqueue_style('knx-home-style', KNX_URL . 'inc/modules/home/home-style.css', [], KNX_VERSION);
    wp_enqueue_script('knx-home-script', KNX_URL . 'inc/modules/home/home-script.js', [], KNX_VERSION, true);

    $session = knx_get_session();
    $username = $session ? esc_html($session->username) : null;

    ob_start(); ?>

    <section class="knx-hero">
        <?php if ($username): ?>
            <h2>Welcome back, <?php echo $username; ?>!</h2>
            <p>Your local delivery dashboard is ready when you are.</p>
        <?php else: ?>
            <h2>Delivering local flavors, supporting local businesses,<br>and giving back with every delivery.</h2>
            <p>Sign in or explore restaurants near you.</p>
        <?php endif; ?>

        <div class="knx-searchbox">
            <input type="text" id="knx-address-input" placeholder="Enter your street or address">
            <button id="knx-search-btn">Find Restaurants</button>
        </div>

        <div class="knx-cities">
            <div class="knx-city-card">
                <img src="https://via.placeholder.com/400x200?text=Kankakee+County" alt="Kankakee County">
                <span>Kankakee County</span>
            </div>
            <div class="knx-city-card">
                <img src="https://via.placeholder.com/400x200?text=Collin+County,+TX" alt="Collin County, TX">
                <span>Collin County, TX</span>
            </div>
        </div>
    </section>

    <?php
    return ob_get_clean();
});
