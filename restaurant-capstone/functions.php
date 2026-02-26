<?php
/**
 * Captain's Boil Theme Functions
 */

/* =====================================================
   SLOT CAPACITY (GUEST BASED)
===================================================== */
if (!defined('CB_MAX_GUESTS_PER_SLOT')) {
    define('CB_MAX_GUESTS_PER_SLOT', 40);
}

/* =====================================================
   HELPER: GET MAX CAPACITY
===================================================== */
function cb_get_max_capacity() {
    $cap = get_option('cb_max_capacity');
    return $cap ? intval($cap) : CB_MAX_GUESTS_PER_SLOT;
}


/* =====================================================
   OPERATING HOURS DEFAULTS
===================================================== */
add_action('init', function () {

    if (get_option('cb_open_time') === false) {
        add_option('cb_open_time', '17:00');
    }

    if (get_option('cb_close_time') === false) {
        add_option('cb_close_time', '23:00');
    }

    if (get_option('cb_slot_interval') === false) {
        add_option('cb_slot_interval', 30);
    }

});

/* =====================================================
   ENQUEUE
===================================================== */
function captainsboil_enqueue_assets() {

    wp_enqueue_style(
        'captainsboil-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_template_directory() . '/style.css')
    );

    wp_enqueue_style(
        'captainsboil-font',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
    );

    wp_enqueue_script('jquery');

    wp_enqueue_script(
        'cb-auth',
        get_template_directory_uri() . '/assets/js/auth.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/auth.js'),
        true
    );

    wp_enqueue_script(
        'cb-dashboard',
        get_template_directory_uri() . '/assets/js/dashboard.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/dashboard.js'),
        true
    );

    wp_localize_script('cb-dashboard', 'cb_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cb_auth_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'captainsboil_enqueue_assets');

/* =====================================================
   ROLES
===================================================== */
add_action('init', function () {
    add_role('customer', 'Customer', ['read'=>true]);
    add_role('restaurant_staff', 'Restaurant Staff', ['read'=>true]);
    add_role('restaurant_admin', 'Restaurant Admin', ['read'=>true]);
});

/* =====================================================
   CUSTOMER REGISTER
===================================================== */
add_action('wp_ajax_nopriv_cb_register', 'cb_register');
add_action('wp_ajax_cb_register', 'cb_register');
function cb_register() {

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_auth_nonce')) {
        wp_send_json_error('Security check failed');
    }

    $name     = sanitize_text_field($_POST['name'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        wp_send_json_error('All fields required');
    }

    if (email_exists($email)) {
        wp_send_json_error('Email already exists');
    }

    $user_id = wp_create_user($email, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error('Registration failed');
    }

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
        'role' => 'customer'
    ]);

    update_user_meta($user_id, '_cb_active', '1');

    wp_send_json_success('Registration successful. Please login.');
}

/* =====================================================
   LOGIN
===================================================== */
add_action('wp_ajax_nopriv_cb_login', 'cb_login');
add_action('wp_ajax_cb_login', 'cb_login');
function cb_login() {

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_auth_nonce')) {
        wp_send_json_error('Security check failed');
    }

    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        wp_send_json_error('Email and password required');
    }

    $creds = [
        'user_login' => $email,
        'user_password' => $password,
        'remember' => true
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        wp_send_json_error('Invalid credentials');
    }

    $role = $user->roles[0] ?? '';

    switch ($role) {
        case 'restaurant_staff':
            $redirect = site_url('/staff-dashboard');
            break;
        case 'restaurant_admin':
            $redirect = site_url('/restaurant-admin-dashboard');
            break;
        case 'administrator':
            $redirect = site_url('/system-admin-dashboard');
            break;
        default:
            $redirect = site_url('/customer-dashboard');
    }

    wp_send_json_success(['redirect' => $redirect]);
}

/* =====================================================
   PROFILE UPDATE
===================================================== */
add_action('wp_ajax_cb_update_profile', 'cb_update_profile');
function cb_update_profile() {

    if (!is_user_logged_in()) {
        wp_send_json_error('Not allowed');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_auth_nonce')) {
        wp_send_json_error('Security failed');
    }

    $user_id = get_current_user_id();
    $name = sanitize_text_field($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name
    ]);

    if (!empty($password)) {
        wp_set_password($password, $user_id);
    }

    wp_send_json_success('Profile updated successfully');
}

/* =====================================================
   REGISTER POST TYPES
===================================================== */
add_action('init', function () {

    register_post_type('reservation', [
        'labels' => ['name'=>'Reservations'],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title']
    ]);

});

/* =====================================================
   MAKE RESERVATION (SMART CAPACITY)
===================================================== */
add_action('wp_ajax_cb_make_reservation', 'cb_make_reservation');
function cb_make_reservation() {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_auth_nonce')) {
        wp_send_json_error('Security failed');
    }

    $user_id = get_current_user_id();
    $date    = sanitize_text_field($_POST['date'] ?? '');
    $time    = sanitize_text_field($_POST['time'] ?? '');
	
	// ===== validate slot
$valid_slots = cb_generate_time_slots();
if (!in_array($time, $valid_slots)) {
    wp_send_json_error('Invalid time slot selected.');
}

    $guests  = intval($_POST['guests'] ?? 0);

    if (!$date || !$time || !$guests) {
        wp_send_json_error('All fields required');
    }

// ⭐ MASTER RULE VALIDATION
$rule_check = cb_validate_reservation_rules($date, $time, $guests);
if ($rule_check !== true) {
    wp_send_json_error($rule_check);
}

$today = current_time('Y-m-d');
$current_time = current_time('H:i');

if (strtotime($date) < strtotime($today)) {
    wp_send_json_error('Past date not allowed');
}

// 🚨 SAME DAY TIME CHECK
if ($date === $today && $time <= $current_time) {
    wp_send_json_error('Selected time slot already passed.');
}

    // ===== capacity check
    $existing = new WP_Query([
        'post_type'=>'reservation',
        'posts_per_page'=>-1,
        'meta_query'=>[
            ['key'=>'_cb_date','value'=>$date],
            ['key'=>'_cb_time','value'=>$time],
            [
                'key'=>'_cb_status',
                'value'=>'cancelled',
                'compare'=>'!='
            ]
        ]
    ]);

    $total_guests = 0;

    if ($existing->have_posts()) {
        while ($existing->have_posts()) {
            $existing->the_post();
            $total_guests += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
        }
        wp_reset_postdata();
    }

    if (($total_guests + $guests) > cb_get_max_capacity()) {
        wp_send_json_error('Selected time slot does not have enough seats.');
    }

    $post_id = wp_insert_post([
        'post_type'=>'reservation',
        'post_status'=>'publish',
        'post_title'=>"Reservation - User {$user_id} - {$date} {$time}"
    ]);

    update_post_meta($post_id, '_cb_user', $user_id);
    update_post_meta($post_id, '_cb_date', $date);
    update_post_meta($post_id, '_cb_time', $time);
    update_post_meta($post_id, '_cb_guests', $guests);
    update_post_meta($post_id, '_cb_status', 'pending');

    wp_send_json_success('Table reserved successfully!');
}

/* =====================================================
   ⭐ STAFF STATUS UPDATE (MISSING FIX)
===================================================== */
add_action('wp_ajax_cb_update_reservation_status', 'cb_update_reservation_status');
function cb_update_reservation_status() {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_staff', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id     = intval($_POST['id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');

    if (!$id || !$status) {
        wp_send_json_error('Invalid data');
    }

    update_post_meta($id, '_cb_status', $status);

    wp_send_json_success('Status updated');
}

/* =====================================================
   UPDATE CAPACITY
===================================================== */
add_action('wp_ajax_cb_update_capacity', function () {

    $user = wp_get_current_user();
  /*  if (!in_array('restaurant_staff', $user->roles)) {
        wp_send_json_error('Not allowed');
    }*/

    $cap = intval($_POST['capacity'] ?? 0);
    if ($cap < 1) wp_send_json_error('Invalid capacity');

    update_option('cb_max_capacity', $cap);

    wp_send_json_success('Capacity updated');
});

/* =====================================================
   SECURITY HARDENING
===================================================== */
add_action('admin_init', function () {
    if (!current_user_can('administrator') && !wp_doing_ajax()) {
        wp_redirect(site_url());
        exit;
    }
});

add_filter('authenticate', function ($user) {

    if (is_wp_error($user) || !$user) return $user;

    $active = get_user_meta($user->ID, '_cb_active', true);
    if ($active === '0') {
        return new WP_Error('inactive', 'Account disabled');
    }

    return $user;

}, 30);

/* =====================================================
   TIME SLOT GENERATOR (30 MIN)
===================================================== */
function cb_generate_time_slots() {

    $open  = get_option('cb_open_time', '17:00');
    $close = get_option('cb_close_time', '23:00');
    $step  = intval(get_option('cb_slot_interval', 30));

    $start = strtotime($open);
    $end   = strtotime($close);

    $slots = [];

    if (!$start || !$end || $step < 5) {
        return $slots;
    }

    while ($start < $end) {
        $slots[] = date('H:i', $start);
        $start = strtotime("+{$step} minutes", $start);
    }

    return $slots;
}

/* =====================================================
   CUSTOMER CANCEL RESERVATION
===================================================== */
add_action('wp_ajax_cb_customer_cancel_reservation', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user_id = get_current_user_id();
    $id      = intval($_POST['id'] ?? 0);

    if (!$id) {
        wp_send_json_error('Invalid reservation');
    }

    // verify ownership
    $owner = get_post_meta($id, '_cb_user', true);
    if ($owner != $user_id) {
        wp_send_json_error('Not allowed');
    }

    update_post_meta($id, '_cb_status', 'cancelled');

    wp_send_json_success('Reservation cancelled');
});

/* =====================================================
   HELPER: NEXT CUSTOMER RESERVATION (FIXED)
===================================================== */
function cb_get_next_customer_reservation($user_id) {

    $today = current_time('Y-m-d');
    $now   = current_time('H:i');

    $query = new WP_Query([
        'post_type'      => 'reservation',
        'posts_per_page' => 1,
        'meta_query' => [
            'relation' => 'AND',

            // user filter
            [
                'key'   => '_cb_user',
                'value' => $user_id
            ],

            // not cancelled
            [
                'key'     => '_cb_status',
                'value'   => 'cancelled',
                'compare' => '!='
            ],

            // future date
            [
                'key'     => '_cb_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE'
            ],
        ],
        'meta_key' => '_cb_date',
        'orderby'  => 'meta_value',
        'order'    => 'ASC'
    ]);

    return $query;
}



/* =====================================================
   QUICK REBOOK (CUSTOMER)
===================================================== */
add_action('wp_ajax_cb_quick_rebook', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user_id = get_current_user_id();
    $old_id  = intval($_POST['id'] ?? 0);

    if (!$old_id) {
        wp_send_json_error('Invalid reservation');
    }

    // verify ownership
    $owner = get_post_meta($old_id, '_cb_user', true);
    if ($owner != $user_id) {
        wp_send_json_error('Not allowed');
    }

    // get old data
    $time   = get_post_meta($old_id, '_cb_time', true);
    $guests = intval(get_post_meta($old_id, '_cb_guests', true));

    // next day booking
	$old_date = get_post_meta($old_id, '_cb_date', true);
    $date = date('Y-m-d', strtotime('+1 day', strtotime($old_date)));

    // ===== capacity check (reuse logic)
    $existing = new WP_Query([
        'post_type' => 'reservation',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key'=>'_cb_date','value'=>$date],
            ['key'=>'_cb_time','value'=>$time],
            [
                'key'=>'_cb_status',
                'value'=>'cancelled',
                'compare'=>'!='
            ]
        ]
    ]);

    $total_guests = 0;

    if ($existing->have_posts()) {
        while ($existing->have_posts()) {
            $existing->the_post();
            $total_guests += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
        }
        wp_reset_postdata();
    }

    if (($total_guests + $guests) > cb_get_max_capacity()) {
        wp_send_json_error('Next slot is full.');
    }

    // create new reservation
    $post_id = wp_insert_post([
        'post_type' => 'reservation',
        'post_status' => 'publish',
        'post_title' => "Reservation - User {$user_id} - {$date} {$time}"
    ]);

    update_post_meta($post_id, '_cb_user', $user_id);
    update_post_meta($post_id, '_cb_date', $date);
    update_post_meta($post_id, '_cb_time', $time);
    update_post_meta($post_id, '_cb_guests', $guests);
    update_post_meta($post_id, '_cb_status', 'pending');

    wp_send_json_success('Rebooked successfully!');
});

/* =====================================================
   CATEGORY MANAGEMENT (ADMIN)
===================================================== */

// CREATE CATEGORY
add_action('wp_ajax_cb_add_category', function () {

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $name = sanitize_text_field($_POST['name'] ?? '');

    if (!$name) {
        wp_send_json_error('Category name required');
    }

    $term = wp_insert_term($name, 'menu_category');

    if (is_wp_error($term)) {
        wp_send_json_error($term->get_error_message());
    }

    wp_send_json_success('Category created');
});


// DELETE CATEGORY
add_action('wp_ajax_cb_delete_category', function () {

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        wp_send_json_error('Invalid category');
    }

    wp_delete_term($id, 'menu_category');

    wp_send_json_success('Category deleted');
});

register_taxonomy('menu_category', 'menu_item', [
    'hierarchical' => true,
    'public' => true,
    'show_in_rest' => true
]);

/* =====================================================
   ENSURE MENU TAXONOMY EXISTS (SAFE FIX)
===================================================== */
add_action('init', function () {

    if (!taxonomy_exists('menu_category')) {

        register_taxonomy('menu_category', 'menu_item', [
            'labels' => [
                'name' => 'Menu Categories',
                'singular_name' => 'Menu Category'
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true
        ]);

    }

}, 5); // priority 5 = load EARLY

/* =====================================================
   ADMIN UPDATE MENU ITEM (INLINE EDIT)
===================================================== */
add_action('wp_ajax_cb_update_menu_item', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id    = intval($_POST['id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $desc  = sanitize_textarea_field($_POST['description'] ?? '');
    $price = sanitize_text_field($_POST['price'] ?? '');
    $spice = sanitize_text_field($_POST['spice'] ?? '');
    $cat   = intval($_POST['category'] ?? 0);

    if (!$id || !$title || !$price) {
        wp_send_json_error('Required fields missing');
    }

    // update post
    wp_update_post([
        'ID'           => $id,
        'post_title'   => $title,
        'post_content' => $desc
    ]);

    // update meta
    update_post_meta($id, '_cb_price', $price);
    update_post_meta($id, '_cb_spice', $spice);

    if ($cat) {
        wp_set_post_terms($id, [$cat], 'menu_category');
    }

    wp_send_json_success('Menu item updated');
});


/* =====================================================
   ⭐ STAFF EDIT RESERVATION (PREMIUM)
===================================================== 
add_action('wp_ajax_cb_staff_edit_reservation', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();

    $allowed = array_intersect(
        ['restaurant_staff','restaurant_admin','administrator'],
        $user->roles
    );

    if (empty($allowed)) {
        wp_send_json_error('Not allowed');
    }

    $id     = intval($_POST['id'] ?? 0);
    $date   = sanitize_text_field($_POST['date'] ?? '');
    $time   = sanitize_text_field($_POST['time'] ?? '');
    $guests = intval($_POST['guests'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');

    if (!$id || !$date || !$time || !$guests) {
        wp_send_json_error('Invalid data');
    }

    // ✅ validate slot
    if (!in_array($time, cb_generate_time_slots())) {
        wp_send_json_error('Invalid time slot');
    }

    // ✅ capacity check (exclude current booking)
    $existing = new WP_Query([
        'post_type' => 'reservation',
        'posts_per_page' => -1,
        'post__not_in' => [$id],
        'meta_query' => [
            ['key' => '_cb_date', 'value' => $date],
            ['key' => '_cb_time', 'value' => $time],
            [
                'key' => '_cb_status',
                'value' => 'cancelled',
                'compare' => '!='
            ]
        ]
    ]);

    $total = 0;

    if ($existing->have_posts()) {
        while ($existing->have_posts()) {
            $existing->the_post();
            $total += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
        }
        wp_reset_postdata();
    }

    if (($total + $guests) > cb_get_max_capacity()) {
        wp_send_json_error('Capacity exceeded for this slot');
    }

    // ✅ update
    update_post_meta($id, '_cb_date', $date);
    update_post_meta($id, '_cb_time', $time);
    update_post_meta($id, '_cb_guests', $guests);

    if ($status) {
        update_post_meta($id, '_cb_status', $status);
    }

    wp_send_json_success('Reservation updated');
});


/* =====================================================
   STAFF FULL RESERVATION EDIT
===================================================== 
add_action('wp_ajax_cb_staff_edit_reservation', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_staff', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id     = intval($_POST['id']);
    $date   = sanitize_text_field($_POST['date']);
    $time   = sanitize_text_field($_POST['time']);
    $guests = intval($_POST['guests']);
    $status = sanitize_text_field($_POST['status']);

    // ✅ capacity safety check
    $existing = new WP_Query([
        'post_type' => 'reservation',
        'posts_per_page' => -1,
        'post__not_in' => [$id],
        'meta_query' => [
            ['key'=>'_cb_date','value'=>$date],
            ['key'=>'_cb_time','value'=>$time],
            ['key'=>'_cb_status','value'=>'cancelled','compare'=>'!=']
        ]
    ]);

    $total = 0;
    while ($existing->have_posts()) {
        $existing->the_post();
        $total += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
    }
    wp_reset_postdata();

    if (($total + $guests) > cb_get_max_capacity()) {
        wp_send_json_error('Slot capacity exceeded');
    }

    update_post_meta($id,'_cb_date',$date);
    update_post_meta($id,'_cb_time',$time);
    update_post_meta($id,'_cb_guests',$guests);
    update_post_meta($id,'_cb_status',$status);

    wp_send_json_success('Reservation updated');
});  


*/
/* =====================================================
   STAFF SAFE EDIT RESERVATION (CAPACITY PROTECTED)
===================================================== */

add_action('wp_ajax_cb_staff_update_reservation', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();

    $allowed = array_intersect(
        ['restaurant_staff','restaurant_admin','administrator'],
        $user->roles
    );

    if (empty($allowed)) {
        wp_send_json_error('Not allowed');
    }

    $id     = intval($_POST['id'] ?? 0);
    $date   = sanitize_text_field($_POST['date'] ?? '');
    $time   = sanitize_text_field($_POST['time'] ?? '');
    $guests = intval($_POST['guests'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');

    if (!$id || !$date || !$time || !$guests) {
        wp_send_json_error('Invalid data');
    }

    // ===== CURRENT VALUES (⭐ VERY IMPORTANT)
    $current_status = get_post_meta($id, '_cb_status', true);
    $current_date   = get_post_meta($id, '_cb_date', true);
    $current_time   = get_post_meta($id, '_cb_time', true);
    $current_guests = intval(get_post_meta($id, '_cb_guests', true));

    // 🚫 prevent completed → pending
    if ($current_status === 'completed' && $status === 'pending') {
        wp_send_json_error('Completed reservation cannot be reopened.');
    }

    // 🔒 LOCK completed/cancelled reservation
    if (in_array($current_status, ['completed','cancelled'])) {

        // sirf status change allow
        if ($date != $current_date ||
            $time != $current_time ||
            $guests != $current_guests) {

            wp_send_json_error('Completed/Cancelled reservation cannot be modified.');
        }

        // ✅ status update allowed
        update_post_meta($id, '_cb_status', $status);
        wp_send_json_success('Status updated');
    }

    // ===== CHECK IF ONLY STATUS CHANGE
    $only_status_change =
        ($date === $current_date) &&
        ($time === $current_time) &&
        ($guests === $current_guests);

    if ($only_status_change) {
        update_post_meta($id, '_cb_status', $status);
        wp_send_json_success('Status updated');
    }

    // 🚫 block past edit
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        wp_send_json_error('Cannot modify past reservations');
    }

    // ✅ validate slot
    if (!in_array($time, cb_generate_time_slots())) {
        wp_send_json_error('Invalid time slot');
    }

    // ⭐ MASTER RULE VALIDATION (ab correct jagah)
    $rule_check = cb_validate_reservation_rules($date, $time, $guests);
    if ($rule_check !== true) {
        wp_send_json_error($rule_check);
    }

    // ================= CAPACITY CHECK (EXCLUDE SELF)
    $existing = new WP_Query([
        'post_type'      => 'reservation',
        'posts_per_page' => -1,
        'post__not_in'   => [$id],
        'meta_query' => [
            ['key'=>'_cb_date','value'=>$date],
            ['key'=>'_cb_time','value'=>$time],
            [
                'key'=>'_cb_status',
                'value'=>'cancelled',
                'compare'=>'!='
            ]
        ]
    ]);

    $total_guests = 0;

    if ($existing->have_posts()) {
        while ($existing->have_posts()) {
            $existing->the_post();
            $total_guests += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
        }
        wp_reset_postdata();
    }

    if (($total_guests + $guests) > cb_get_max_capacity()) {
        wp_send_json_error('Capacity exceeded for this time slot');
    }

    // ✅ SAFE UPDATE
    update_post_meta($id, '_cb_date', $date);
    update_post_meta($id, '_cb_time', $time);
    update_post_meta($id, '_cb_guests', $guests);
    update_post_meta($id, '_cb_status', $status);

    wp_send_json_success('Reservation updated safely');
});


/* =====================================================
   ADMIN ADD MENU ITEM
===================================================== */
add_action('wp_ajax_cb_add_menu_item', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $title = sanitize_text_field($_POST['title'] ?? '');
    $desc  = sanitize_textarea_field($_POST['description'] ?? '');
    $price = sanitize_text_field($_POST['price'] ?? '');
    $spice = sanitize_text_field($_POST['spice'] ?? '');
    $cat   = intval($_POST['category'] ?? 0);

    if (!$title || !$price) {
        wp_send_json_error('Title and price required');
    }

    // create menu item post
    $post_id = wp_insert_post([
        'post_type'   => 'menu_item',
        'post_status' => 'publish',
        'post_title'  => $title,
        'post_content'=> $desc
    ]);

    if (!$post_id) {
        wp_send_json_error('Failed to create item');
    }

    // save meta
    update_post_meta($post_id, '_cb_price', $price);
    update_post_meta($post_id, '_cb_spice', $spice);

    // assign category
    if ($cat) {
        wp_set_post_terms($post_id, [$cat], 'menu_category');
    }

    wp_send_json_success('Menu item added');
});

/* =====================================================
   ADMIN DELETE MENU ITEM
===================================================== */
add_action('wp_ajax_cb_delete_menu_item', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        wp_send_json_error('Invalid item');
    }

    // check post exists
    $post = get_post($id);
    if (!$post || $post->post_type !== 'menu_item') {
        wp_send_json_error('Menu item not found');
    }

    wp_delete_post($id, true);

    wp_send_json_success('Menu item deleted');
});

/* =====================================================
   SYSTEM ADMIN: TOGGLE USER ACTIVE STATUS
===================================================== */
add_action('wp_ajax_cb_toggle_user', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();

    // only system admin (administrator)
    if (!in_array('administrator', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id  = intval($_POST['id'] ?? 0);
    $val = ($_POST['val'] ?? '0') === '1' ? '1' : '0';

    if (!$id) {
        wp_send_json_error('Invalid user');
    }

    // ⭐ SAVE ACTIVE FLAG
    update_user_meta($id, '_cb_active', $val);

    wp_send_json_success('User status updated');
});


/* =====================================================
   SYSTEM ADMIN — UPDATE USER PROFILE + ROLE
===================================================== */
add_action('wp_ajax_cb_update_user_account', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $current = wp_get_current_user();

    // ✅ only system admin / administrator
    if (!in_array('administrator', $current->roles)) {
        wp_send_json_error('Not allowed');
    }

    $id    = intval($_POST['id'] ?? 0);
    $name  = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $role  = sanitize_text_field($_POST['role'] ?? '');
    $active = sanitize_text_field($_POST['active'] ?? '1');

    if (!$id || !$email) {
        wp_send_json_error('Invalid data');
    }

    // ✅ update basic info
    wp_update_user([
        'ID'           => $id,
        'display_name' => $name,
        'user_email'   => $email,
        'role'         => $role
    ]);

    // ✅ active / deactivate
    update_user_meta($id, '_cb_active', $active);

    wp_send_json_success('User updated successfully');

});

/* =====================================================
   SYSTEM ADMIN: UPDATE OPERATING HOURS
===================================================== */
add_action('wp_ajax_cb_update_hours', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('administrator', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $open  = sanitize_text_field($_POST['open'] ?? '');
    $close = sanitize_text_field($_POST['close'] ?? '');
    $step  = intval($_POST['interval'] ?? 30);

    if (!$open || !$close || $step < 5) {
        wp_send_json_error('Invalid values');
    }

    update_option('cb_open_time', $open);
    update_option('cb_close_time', $close);
    update_option('cb_slot_interval', $step);

    wp_send_json_success('Operating hours updated');
});


/* =====================================================
   RESERVATION RULES DEFAULTS
===================================================== */
function cb_get_reservation_rules() {

    return [
        'max_guests_per_booking' => intval(get_option('cb_max_guests_per_booking', 5)),
        'max_advance_days'       => intval(get_option('cb_max_advance_days', 30)),
        'same_day_cutoff'        => get_option('cb_same_day_cutoff', '15:00'),
    ];
}


/* =====================================================
   MASTER RESERVATION VALIDATION (CUSTOMER + STAFF)
===================================================== */
function cb_validate_reservation_rules($date, $time, $guests) {

    $rules = cb_get_reservation_rules();

    $today        = current_time('Y-m-d');
    $current_time = current_time('H:i');

    /* ========= PAST DATE BLOCK ========= */
    if (strtotime($date) < strtotime($today)) {
        return 'Past date not allowed';
    }

    /* ========= SAME DAY TIME CHECK ========= */
    if ($date === $today && $time <= $current_time) {
        return 'Selected time slot already passed';
    }

    /* ========= MAX GUEST PER BOOKING ========= */
    if ($guests > $rules['max_guests_per_booking']) {
        return 'Guest limit exceeded per reservation';
    }

    /* ========= ADVANCE BOOKING LIMIT ========= */
    $last_allowed = date('Y-m-d', strtotime("+{$rules['max_advance_days']} days"));

    if ($date > $last_allowed) {
        return 'Booking too far in advance';
    }

    return true; // ✅ all good
}


/* =====================================================
   SAVE RESERVATION RULES (SYSTEM ADMIN)
===================================================== */
add_action('wp_ajax_cb_save_reservation_rules', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('administrator', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $max_guests   = intval($_POST['max_guests'] ?? 0);
    $advance_days = intval($_POST['advance_days'] ?? 0);
    $cutoff       = sanitize_text_field($_POST['cutoff'] ?? '');

    if ($max_guests < 1 || $advance_days < 1 || !$cutoff) {
        wp_send_json_error('Invalid values');
    }

    update_option('cb_max_guests_per_booking', $max_guests);
    update_option('cb_max_advance_days', $advance_days);
    update_option('cb_same_day_cutoff', $cutoff);

    wp_send_json_success('Rules saved successfully');
});

/* =====================================================
   EXPORT RESERVATIONS CSV (SYSTEM ADMIN)
===================================================== */
add_action('wp_ajax_cb_export_reservations', function () {

    if (!is_user_logged_in()) {
        wp_die('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('administrator', $user->roles)) {
        wp_die('Not allowed');
    }

    // filename
    $filename = 'reservations-' . date('Y-m-d-H-i') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // CSV header row
    fputcsv($output, [
        'Reservation ID',
        'Customer Name',
        'Customer Email',
        'Date',
        'Time',
        'Guests',
        'Status',
        'Created At'
    ]);

    // get reservations
    $query = new WP_Query([
        'post_type' => 'reservation',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $id     = get_the_ID();
            $userId = get_post_meta($id, '_cb_user', true);
            $date   = get_post_meta($id, '_cb_date', true);
            $time   = get_post_meta($id, '_cb_time', true);
            $guests = get_post_meta($id, '_cb_guests', true);
            $status = get_post_meta($id, '_cb_status', true);

            $customer = get_userdata($userId);

            fputcsv($output, [
                $id,
                $customer->display_name ?? '',
                $customer->user_email ?? '',
                $date,
                $time,
                $guests,
                $status,
                get_the_date('Y-m-d H:i:s')
            ]);
        }
        wp_reset_postdata();
    }

    fclose($output);
    exit;
});

/* =====================================================
   SYSTEM ADMIN — UPDATE USER ROLE
===================================================== */
add_action('wp_ajax_cb_update_user_role', function () {

    if (!current_user_can('administrator')) {
        wp_send_json_error('Not allowed');
    }

    $user_id = intval($_POST['user_id'] ?? 0);
    $role    = sanitize_text_field($_POST['role'] ?? '');

    if (!$user_id || !$role) {
        wp_send_json_error('Invalid data');
    }

    $user = new WP_User($user_id);
    $user->set_role($role);

    wp_send_json_success('Role updated');
});


/* =====================================================
   SYSTEM ADMIN — TOGGLE USER STATUS
===================================================== */
add_action('wp_ajax_cb_toggle_user_status', function () {

    if (!current_user_can('administrator')) {
        wp_send_json_error('Not allowed');
    }

    $user_id = intval($_POST['user_id'] ?? 0);
    $active  = sanitize_text_field($_POST['active'] ?? '1');

    update_user_meta($user_id, '_cb_active', $active);

    wp_send_json_success('Status updated');
});


/* =====================================================
   SYSTEM ADMIN UPDATE USER (EMAIL + PASSWORD SAFE)
===================================================== */
add_action('wp_ajax_cb_update_user_account', function () {

    if (!current_user_can('administrator')) {
        wp_send_json_error('Not allowed');
    }

    $id       = intval($_POST['id'] ?? 0);
    $name     = sanitize_text_field($_POST['name'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $role     = sanitize_text_field($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $active   = ($_POST['active'] ?? '1') === '1' ? '1' : '0';

    if (!$id || !$email) {
        wp_send_json_error('Invalid data');
    }

    // ✅ update name + email
    wp_update_user([
        'ID'           => $id,
        'display_name' => $name,
        'user_email'   => $email
    ]);

    // ✅ update role
    if ($role) {
        $user = new WP_User($id);
        $user->set_role($role);
    }

    // ✅ password reset (ONLY if filled)
    if (!empty($password)) {
        wp_set_password($password, $id);
    }

    // ✅ active toggle
    update_user_meta($id, '_cb_active', $active);

    wp_send_json_success('User updated successfully');
});


/* =====================================================
   RESTAURANT GALLERY
===================================================== */
add_action('init', function () {

    register_post_type('cb_gallery', [
        'labels' => [
            'name' => 'Restaurant Gallery',
            'singular_name' => 'Gallery Image'
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title','thumbnail'],
        'menu_icon' => 'dashicons-format-image'
    ]);

});

/* =====================================================
   ADMIN UPLOAD RESTAURANT IMAGE
===================================================== */
add_action('wp_ajax_cb_upload_gallery_image', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    $user = wp_get_current_user();
    if (!in_array('restaurant_admin', $user->roles)) {
        wp_send_json_error('Not allowed');
    }

    if (empty($_FILES['image']['name'])) {
        wp_send_json_error('No image selected');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_handle_upload('image', 0);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Upload failed');
    }

    $post_id = wp_insert_post([
        'post_type' => 'cb_gallery',
        'post_status' => 'publish',
        'post_title' => 'Gallery Image'
    ]);

    set_post_thumbnail($post_id, $attachment_id);

    wp_send_json_success('Image uploaded');
});


/* =====================================================
   DELETE GALLERY IMAGE
===================================================== */
add_action('wp_ajax_cb_delete_gallery_image', function () {

    if (!current_user_can('restaurant_admin')) {
        wp_send_json_error('Not allowed');
    }

    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        wp_send_json_error('Invalid image');
    }

    wp_delete_post($id, true);

    wp_send_json_success('Image deleted');
});



// ✅ give restaurant admin page edit power
add_action('init', function () {

    $role = get_role('restaurant_admin');

    if ($role) {
        $role->add_cap('edit_pages');
        $role->add_cap('edit_others_pages');
        $role->add_cap('publish_pages');
        $role->add_cap('read');
    }

});

add_action('wp_ajax_cb_save_page_content', 'cb_save_page_content');

function cb_save_page_content() {

    if (!current_user_can('edit_pages')) {
        wp_send_json_error('No permission');
    }

    $page_id = intval($_POST['page_id'] ?? 0);
    $content = wp_kses_post($_POST['content'] ?? '');

    if (!$page_id) {
        wp_send_json_error('Invalid page');
    }

    wp_update_post([
        'ID' => $page_id,
        'post_content' => $content
    ]);

    wp_send_json_success('Saved');
}

// 🔒 Hide admin bar on frontend (except admin)
add_filter('show_admin_bar', function ($show) {
    if (!current_user_can('administrator')) {
        return false;
    }
    return $show;
});

function cb_get_max_guests_per_booking() {
    return intval(get_option('cb_max_guests_per_booking', 10));
}


// ===============================
// CREATE USER (SYSTEM ADMIN)
// ===============================
add_action('wp_ajax_cb_create_user', 'cb_create_user');

function cb_create_user() {

    // only system admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Not allowed');
    }

    $name  = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = sanitize_text_field($_POST['role'] ?? 'customer');

    if (!$name || !$email || !$pass) {
        wp_send_json_error('Missing fields');
    }

    if (email_exists($email)) {
        wp_send_json_error('Email already exists');
    }

    $user_id = wp_insert_user([
        'user_login'   => $email,
        'user_email'   => $email,
        'user_pass'    => $pass,
        'display_name' => $name,
        'role'         => $role
    ]);

    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }

    wp_send_json_success('User created successfully');
}