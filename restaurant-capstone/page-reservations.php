<?php
/* Template Name: Reservations */

get_header();
?>

<div class="container">
    <h2>Reserve a Table</h2>

<?php
if (isset($_POST['submit_reservation'])) {

    $guest_name       = sanitize_text_field($_POST['guest_name']);
    $guest_email      = sanitize_email($_POST['guest_email']);
    $guest_phone      = sanitize_text_field($_POST['guest_phone']);
    $reservation_date = sanitize_text_field($_POST['reservation_date']);
    $reservation_time = sanitize_text_field($_POST['reservation_time']);
    $guest_count      = intval($_POST['guest_count']);

    $max_guests = get_option('restaurant_max_guests', 10);

    if (!restaurant_validate_reservation_time($reservation_time)) {

        echo '<div style="color:red;font-weight:bold;margin-bottom:15px;">
        Reservation not allowed outside business hours.
        </div>';

    }
    elseif ($guest_count > $max_guests) {

        echo '<div style="color:red;font-weight:bold;margin-bottom:15px;">
        Guest limit exceeded. Maximum allowed: ' . $max_guests . '
        </div>';

    }
    else {

        $reservation_id = wp_insert_post(array(
            'post_type'   => 'reservation',
            'post_title'  => 'Reservation - ' . $guest_name,
            'post_status' => 'publish'
        ));

        update_post_meta($reservation_id, 'guest_name', $guest_name);
        update_post_meta($reservation_id, 'guest_email', $guest_email);
        update_post_meta($reservation_id, 'guest_phone', $guest_phone);
        update_post_meta($reservation_id, 'reservation_date', $reservation_date);
        update_post_meta($reservation_id, 'reservation_time', $reservation_time);
        update_post_meta($reservation_id, 'guest_count', $guest_count);
        update_post_meta($reservation_id, 'reservation_status', 'Pending');

        echo '<div style="color:green;font-weight:bold;margin-bottom:15px;">
        Reservation submitted successfully! Status: Pending
        </div>';
    }
}
?>

<form method="post">

    <p>
        <label>Full Name:</label><br>
        <input type="text" name="guest_name" required>
    </p>

    <p>
        <label>Email:</label><br>
        <input type="email" name="guest_email" required>
    </p>

    <p>
        <label>Phone:</label><br>
        <input type="text" name="guest_phone" required>
    </p>

    <p>
        <label>Date:</label><br>
        <input type="date" name="reservation_date" required>
    </p>

    <p>
        <label>Time:</label><br>
        <input type="time" name="reservation_time" required>
    </p>

    <p>
        <label>Number of Guests:</label><br>
        <input type="number" name="guest_count" min="1" required>
    </p>

    <p>
        <button type="submit" name="submit_reservation">
            Book Now
        </button>
    </p>

</form>

</div>

<?php
get_footer();
?>