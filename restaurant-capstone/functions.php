<?php
function restaurant_capstone_assets() {
  wp_enqueue_style(
    'restaurant-style',
    get_template_directory_uri() . '/assets/css/style.css'
  );
}
add_action('wp_enqueue_scripts', 'restaurant_capstone_assets');