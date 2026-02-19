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

// ===============================
// OPERATING HOURS SETTINGS
// ===============================

// Add Admin Menu
function restaurant_add_settings_menu() {
    add_menu_page(
        'Operating Hours',
        'Operating Hours',
        'manage_options',
        'restaurant-operating-hours',
        'restaurant_operating_hours_page',
        'dashicons-clock',
        25
    );
}
add_action('admin_menu', 'restaurant_add_settings_menu');


// Settings Page Content
function restaurant_operating_hours_page() {

    // Save Data
    if (isset($_POST['restaurant_save_settings'])) {

        update_option('restaurant_opening_time', sanitize_text_field($_POST['opening_time']));
        update_option('restaurant_closing_time', sanitize_text_field($_POST['closing_time']));
        update_option('restaurant_max_guests', intval($_POST['max_guests']));

        echo '<div class="updated"><p>Settings Saved Successfully!</p></div>';
    }

    // Get Saved Values
    $opening_time = get_option('restaurant_opening_time', '10:00');
    $closing_time = get_option('restaurant_closing_time', '22:00');
    $max_guests = get_option('restaurant_max_guests', 10);
    ?>

    <div class="wrap">
        <h1>Restaurant Operating Hours</h1>

        <form method="post">

            <table class="form-table">

                <tr>
                    <th>Opening Time</th>
                    <td>
                        <input type="time" name="opening_time" value="<?php echo esc_attr($opening_time); ?>" required>
                    </td>
                </tr>

                <tr>
                    <th>Closing Time</th>
                    <td>
                        <input type="time" name="closing_time" value="<?php echo esc_attr($closing_time); ?>" required>
                    </td>
                </tr>

                <tr>
                    <th>Maximum Guests Per Reservation</th>
                    <td>
                        <input type="number" name="max_guests" value="<?php echo esc_attr($max_guests); ?>" min="1" required>
                    </td>
                </tr>

            </table>

            <p>
                <input type="submit" name="restaurant_save_settings" class="button button-primary" value="Save Settings">
            </p>

        </form>
    </div>

<?php
}

// ===============================
// RESERVATION TIME VALIDATION FUNCTION
// ===============================

function restaurant_validate_reservation_time($selected_time) {

    $opening_time = get_option('restaurant_opening_time', '10:00');
    $closing_time = get_option('restaurant_closing_time', '22:00');

    if ($selected_time < $opening_time || $selected_time > $closing_time) {
        return false; // Not allowed
    }

    return true; // Allowed
}


// ===============================
// CUSTOM POST TYPE: RESERVATIONS
// ===============================

function create_reservation_post_type() {

    register_post_type('reservation',
        array(
            'labels' => array(
                'name' => 'Reservations',
                'singular_name' => 'Reservation'
            ),
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
        )
    );

}
add_action('init', 'create_reservation_post_type');

// ===============================
// ADD CUSTOM COLUMNS TO RESERVATIONS
// ===============================

// ===============================
// ADD CUSTOM COLUMNS TO RESERVATIONS
// ===============================

// Add columns
// ===============================
// ADD CUSTOM COLUMNS TO RESERVATIONS
// ===============================

function add_reservation_columns($columns) {

    unset($columns['date']);

    $columns['guest_name'] = 'Guest Name';
    $columns['reservation_date'] = 'Reservation Date';
    $columns['reservation_time'] = 'Reservation Time';
    $columns['guest_count'] = 'Guests';
    $columns['reservation_status'] = 'Status';
    $columns['date'] = 'Created';

    return $columns;
}
add_filter('manage_reservation_posts_columns', 'add_reservation_columns');


function show_reservation_columns($column, $post_id) {

    if ($column == 'guest_name') {
        echo get_post_meta($post_id, 'guest_name', true);
    }

    if ($column == 'reservation_date') {
        echo get_post_meta($post_id, 'reservation_date', true);
    }

    if ($column == 'reservation_time') {
        echo get_post_meta($post_id, 'reservation_time', true);
    }

    if ($column == 'guest_count') {
        echo get_post_meta($post_id, 'guest_count', true);
    }

    if ($column == 'reservation_status') {

        $status = get_post_meta($post_id, 'reservation_status', true);

        if ($status == 'Confirmed') {
            echo '<span style="color:green;font-weight:bold;">Confirmed</span>';
        } elseif ($status == 'Cancelled') {
            echo '<span style="color:red;font-weight:bold;">Cancelled</span>';
        } else {
            echo '<span style="color:orange;font-weight:bold;">Pending</span>';
        }
    }
}
add_action('manage_reservation_posts_custom_column', 'show_reservation_columns', 10, 2);

// ===============================
// ADD STATUS META BOX
// ===============================

// Add Meta Box
function add_reservation_status_metabox() {
    add_meta_box(
        'reservation_status_box',
        'Reservation Status',
        'reservation_status_metabox_callback',
        'reservation',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_reservation_status_metabox');


// Meta Box Content
function reservation_status_metabox_callback($post) {

    $current_status = get_post_meta($post->ID, 'reservation_status', true);

    if (!$current_status) {
        $current_status = 'Pending';
    }
    ?>

    <label for="reservation_status">Select Status:</label>
    <select name="reservation_status" id="reservation_status" style="width:100%; margin-top:8px;">
        <option value="Pending" <?php selected($current_status, 'Pending'); ?>>Pending</option>
        <option value="Confirmed" <?php selected($current_status, 'Confirmed'); ?>>Confirmed</option>
        <option value="Cancelled" <?php selected($current_status, 'Cancelled'); ?>>Cancelled</option>
    </select>

    <?php
}


// Save Status
function save_reservation_status($post_id) {

    if (isset($_POST['reservation_status'])) {
        update_post_meta(
            $post_id,
            'reservation_status',
            sanitize_text_field($_POST['reservation_status'])
        );
    }
}
add_action('save_post_reservation', 'save_reservation_status');

// ===============================
// ADD GUEST DETAILS META BOX
// ===============================

function add_guest_details_metabox() {
    add_meta_box(
        'guest_details_box',
        'Guest Details',
        'guest_details_metabox_callback',
        'reservation',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_guest_details_metabox');


function guest_details_metabox_callback($post) {

    $name  = get_post_meta($post->ID, 'guest_name', true);
    $email = get_post_meta($post->ID, 'guest_email', true);
    $phone = get_post_meta($post->ID, 'guest_phone', true);
    ?>

    <p><strong>Name:</strong><br>
    <input type="text" name="guest_name" value="<?php echo esc_attr($name); ?>" style="width:100%;"></p>

    <p><strong>Email:</strong><br>
    <input type="email" name="guest_email" value="<?php echo esc_attr($email); ?>" style="width:100%;"></p>

    <p><strong>Phone:</strong><br>
    <input type="text" name="guest_phone" value="<?php echo esc_attr($phone); ?>" style="width:100%;"></p>

    <?php
}


function save_guest_details($post_id) {

    if (isset($_POST['guest_name'])) {
        update_post_meta($post_id, 'guest_name', sanitize_text_field($_POST['guest_name']));
    }

    if (isset($_POST['guest_email'])) {
        update_post_meta($post_id, 'guest_email', sanitize_email($_POST['guest_email']));
    }

    if (isset($_POST['guest_phone'])) {
        update_post_meta($post_id, 'guest_phone', sanitize_text_field($_POST['guest_phone']));
    }
}
add_action('save_post_reservation', 'save_guest_details');