<?php
/* Template Name: Staff Dashboard */

if (!is_user_logged_in()) {
    wp_redirect(site_url('/login'));
    exit;
}

$user = wp_get_current_user();
if (!in_array('restaurant_staff', $user->roles)) {
    wp_redirect(site_url('/'));
    exit;
}

get_header();
?>

<section class="cb-dashboard">

  <!-- SIDEBAR -->
  <aside class="cb-sidebar">
    <h3>🍽 Staff Panel</h3>

    <ul>
      <li><a href="#" class="active" data-tab="overview">Overview</a></li>
      <li><a href="#" data-tab="manage">Manage Reservations</a></li>
      <li><a href="#" data-tab="tables">Table Availability</a></li>
      <li>
        <a href="<?php echo wp_logout_url(site_url('/login')); ?>">
          Logout
        </a>
      </li>
    </ul>
  </aside>

  <!-- MAIN -->
  <div class="cb-main">

    <!-- ================= OVERVIEW ================= -->
    <div id="tab-overview" class="cb-tab active">

      <div class="cb-card">
        <h3>Today's Overview</h3>

        <?php
        $today = current_time('Y-m-d');

        $today_res = new WP_Query([
          'post_type' => 'reservation',
          'posts_per_page' => -1,
          'meta_query' => [
            ['key' => '_cb_date', 'value' => $today],
            ['key' => '_cb_status','value'=>'cancelled','compare'=>'!=']
          ]
        ]);

        $total_reservations = $today_res->found_posts;

        $total_guests = 0;
        if ($today_res->have_posts()) {
          while ($today_res->have_posts()) {
            $today_res->the_post();
            $total_guests += intval(get_post_meta(get_the_ID(), '_cb_guests', true));
          }
          wp_reset_postdata();
        }
        ?>

        <div class="cb-stats">
          <div class="cb-stat-box">
            <h4><?php echo esc_html($total_reservations); ?></h4>
            <p>Today's Bookings</p>
          </div>

          <div class="cb-stat-box">
            <h4><?php echo esc_html($total_guests); ?></h4>
            <p>Total Guests Today</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ================= MANAGE ================= -->
    <div id="tab-manage" class="cb-tab">

      <div class="cb-card">
        <h3>Today's Reservations</h3>

        <?php
        $res = new WP_Query([
          'post_type' => 'reservation',
          'posts_per_page' => -1,
          'meta_query' => [
            ['key' => '_cb_date', 'value' => $today]
          ],
          'orderby' => 'meta_value',
          'meta_key' => '_cb_time',
          'order' => 'ASC'
        ]);

        if ($res->have_posts()):
        ?>

        <table class="cb-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Time</th>
              <th>Guests</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>

          <?php while ($res->have_posts()): $res->the_post();

            $id      = get_the_ID();
            $user_id = get_post_meta($id, '_cb_user', true);
            $time    = get_post_meta($id, '_cb_time', true);
            $date    = get_post_meta($id, '_cb_date', true);
            $guests  = get_post_meta($id, '_cb_guests', true);
            $status  = get_post_meta($id, '_cb_status', true);

            $customer = get_userdata($user_id);
          ?>

          <!-- ===== VIEW ROW ===== -->
          <tr class="cb-row">
            <td><?php echo esc_html($customer->display_name ?? 'User'); ?></td>
			<td><?php echo esc_html($date); ?></td>
            <td><?php echo esc_html($time); ?></td>
            <td><?php echo esc_html($guests); ?></td>

            <td>
              <span class="cb-status cb-status-<?php echo esc_attr($status); ?>">
                <?php echo esc_html(ucfirst($status)); ?>
              </span>
            </td>

            <td>
              <button class="cb-edit-res btn-primary"
                      data-id="<?php echo $id; ?>">
                Edit
              </button>
            </td>
          </tr>

          <!-- ⭐ INLINE EDIT ROW -->
          <tr id="edit-res-<?php echo $id; ?>" class="cb-edit-row" style="display:none;">
            <td colspan="5">

              <div class="cb-edit-box">

                <input type="date"
                       class="cb-edit-date"
                       value="<?php echo esc_attr($date); ?>">

                <select class="cb-edit-time">
                  <?php foreach (cb_generate_time_slots() as $slot): ?>
                    <option value="<?php echo esc_attr($slot); ?>"
                      <?php selected($time,$slot); ?>>
                      <?php echo esc_html($slot); ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <input type="number"
                       class="cb-edit-guests"
                       value="<?php echo esc_attr($guests); ?>"
                       min="1">

                <select class="cb-edit-status">
                  <option value="pending" <?php selected($status,'pending'); ?>>Pending</option>
                  <option value="confirmed" <?php selected($status,'confirmed'); ?>>Confirmed</option>
                  <option value="completed" <?php selected($status,'completed'); ?>>Completed</option>
                  <option value="cancelled" <?php selected($status,'cancelled'); ?>>Cancelled</option>
                </select>

                <button class="cb-save-res btn-primary"
                        data-id="<?php echo $id; ?>">
                  Save
                </button>

                <button class="cb-cancel-res btn-secondary">
                  Cancel
                </button>

              </div>

            </td>
          </tr>

          <?php endwhile; wp_reset_postdata(); ?>

          </tbody>
        </table>

        <?php else: ?>
          <p>No reservations today.</p>
        <?php endif; ?>

      </div>

    </div>

    <!-- ================= CAPACITY ================= -->
    <div id="tab-tables" class="cb-tab">

      <div class="cb-card">
        <h3>Table Availability</h3>

        <?php $current_cap = cb_get_max_capacity(); ?>

        <form id="capacityForm">
          <label>Max Guests Per Time Slot</label><br>
          <input type="number" name="capacity"
                 value="<?php echo esc_attr($current_cap); ?>" min="1"><br><br>

          <button type="submit" class="btn-primary">
            Update Capacity
          </button>

          <p id="capacityMsg"></p>
        </form>
      </div>

    </div>

  </div>
</section>

<?php get_footer(); ?>