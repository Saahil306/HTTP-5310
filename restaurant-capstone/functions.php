<?php
function restaurant_capstone_assets() {
  wp_enqueue_style(
    'restaurant-style',
    get_template_directory_uri() . '/assets/css/style.css'
  );
}
add_action('wp_enqueue_scripts', 'restaurant_capstone_assets');

// Custom Post Type: Menu Items
function create_menu_post_type() {
  register_post_type('menu_item',
    array(
      'labels' => array(
        'name' => 'Menu Items',
        'singular_name' => 'Menu Item'
      ),
      'public' => true,
      'has_archive' => true,
      'menu_icon' => 'dashicons-carrot',
      'supports' => array('title', 'editor', 'thumbnail')
    )
  );
}
add_action('init', 'create_menu_post_type');

// Taxonomy: Menu Categories
function create_menu_taxonomy() {
  register_taxonomy(
    'menu_category',
    'menu_item',
    array(
      'label' => 'Menu Categories',
      'hierarchical' => true,
      'public' => true,
      'show_admin_column' => true,
    )
  );
}
add_action('init', 'create_menu_taxonomy');