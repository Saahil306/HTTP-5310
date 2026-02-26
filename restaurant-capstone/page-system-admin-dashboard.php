<?php
/* Template Name: System Admin Dashboard */

if (!is_user_logged_in()) {
    wp_redirect(site_url('/login'));
    exit;
}

$user = wp_get_current_user();
if (!in_array('administrator', $user->roles)) {
    wp_redirect(site_url('/'));
    exit;
}

get_header();
?>

<section class="cb-dashboard">

  <!-- SIDEBAR -->
  <aside class="cb-sidebar">
    <h3>⚙ System Admin</h3>

    <ul>
      <li><a href="#" class="active" data-tab="users">Users</a></li>
      <li><a href="#" data-tab="add-user">Create User</a></li>
      <li><a href="#" data-tab="export">Export</a></li>
	  <li><a href="#" data-tab="hours">Operating Hours</a></li>
	  <li><a href="#" data-tab="rules">Reservation Rules</a></li>

      <li>
        <a href="<?php echo wp_logout_url(site_url('/login')); ?>">
          Logout
        </a>
      </li>
    </ul>
  </aside>

  <!-- MAIN -->
  <div class="cb-main">

    <!-- ================= USERS LIST ================= -->
    <div id="tab-users" class="cb-tab active">
      <div class="cb-card">
        <h3>All Users</h3>

        <?php
        $users = get_users([ 'role__not_in' => ['system_admin']]);
        ?>

        <table class="cb-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>

              <th>Status</th>
			  			  <th>Password Reset</th>
            </tr>
          </thead>
          <tbody>

          <?php foreach ($users as $u):
		  
		  // 🚫 hide system admin
if (in_array('system_admin', (array) $u->roles)) {
    continue;
}
            $active = get_user_meta($u->ID, '_cb_active', true);
            if ($active === '') $active = '1';

            $role = $u->roles[0] ?? '';
          ?>

            <tr>

              <!-- NAME -->
              <td>
                <input type="text"
                       class="cb-user-name"
                       data-id="<?php echo $u->ID; ?>"
                       value="<?php echo esc_attr($u->display_name); ?>">
              </td>

              <!-- EMAIL -->
              <<td>
  <input type="email"
         class="cb-user-email"
         data-id="<?php echo $u->ID; ?>"
         value="<?php echo esc_attr($u->user_email); ?>">
</td>

              <!-- ROLE (editable) -->
              <td>
                <select class="cb-user-role"
                        data-id="<?php echo $u->ID; ?>">

                  <option value="customer" <?php selected($role,'customer'); ?>>
                    Customer
                  </option>

                  <option value="restaurant_staff" <?php selected($role,'restaurant_staff'); ?>>
                    Restaurant Staff
                  </option>

                  <option value="restaurant_admin" <?php selected($role,'restaurant_admin'); ?>>
                    Restaurant Admin
                  </option>

                </select>
              </td>
			 



              <!-- STATUS -->
              <td>
                <label class="cb-switch">
                  <input type="checkbox"
                         class="cb-user-toggle"
                         data-id="<?php echo $u->ID; ?>"
                         <?php checked($active, '1'); ?>>
                  <span>Active</span>
                </label>
              </td>
			  
			  			  <td>
  <input type="password"
         class="cb-user-pass"
         data-id="<?php echo $u->ID; ?>"
         placeholder="New password">

  <button class="cb-save-user btn-primary"
          data-id="<?php echo $u->ID; ?>">
    Save
  </button>
</td>

            </tr>

          <?php endforeach; ?>

          </tbody>
        </table>

        <p style="margin-top:10px;color:#888;">
          Changes save automatically.
        </p>

      </div>
    </div>

    <!-- ================= ADD USER ================= -->
    <div id="tab-add-user" class="cb-tab">
      <div class="cb-card">
        <h3>Create User</h3>

        <form id="createUserForm">

          <label>Name</label><br>
          <input type="text" name="name" required><br><br>

          <label>Email</label><br>
          <input type="email" name="email" required><br><br>

          <label>Password</label><br>
          <input type="password" name="password" required><br><br>

          <label>Role</label><br>
          <select name="role" required>
            <option value="restaurant_staff">Restaurant Staff</option>
            <option value="restaurant_admin">Restaurant Admin</option>
            <option value="customer">Customer</option>
          </select><br><br>

          <button type="submit" class="btn-primary">
            Create User
          </button>

          <p id="userMsg"></p>

        </form>
      </div>
    </div>

    <!-- ================= EXPORT ================= -->
    <div id="tab-export" class="cb-tab">
      <div class="cb-card">
        <h3>Export Reservations</h3>

        <button id="exportReservations" class="btn-primary">
          Download CSV
        </button>

        <p>Exports all reservation data.</p>
      </div>
    </div>

    <!-- ================= OPERATING HOURS ================= -->
    <div id="tab-hours" class="cb-tab">
      <div class="cb-card">
        <h3>Operating Hours</h3>

        <?php
        $open  = get_option('cb_open_time', '17:00');
        $close = get_option('cb_close_time', '23:00');
        $step  = get_option('cb_slot_interval', 30);
        ?>

        <form id="hoursForm">

          <label>Opening Time</label><br>
          <input type="time" name="open"
                 value="<?php echo esc_attr($open); ?>"><br><br>

          <label>Closing Time</label><br>
          <input type="time" name="close"
                 value="<?php echo esc_attr($close); ?>"><br><br>

          <label>Slot Interval (minutes)</label><br>
          <input type="number" name="interval"
                 value="<?php echo esc_attr($step); ?>"
                 min="5"><br><br>

          <button type="submit" class="btn-primary">
            Save Hours
          </button>

          <p id="hoursMsg"></p>

        </form>
      </div>
    </div>
	
	<!-- ================= RULES ================= -->
<div id="tab-rules" class="cb-tab">
  <div class="cb-card">
    <h3>Reservation Rules</h3>

    <?php
    $maxGuests   = get_option('cb_max_guests_per_booking', 10);
    $advanceDays = get_option('cb_max_advance_days', 30);
    $cutoff      = get_option('cb_same_day_cutoff', '15:00');
    ?>

    <form id="rulesForm">

      <label>Max Guests per Booking</label><br>
      <input type="number" name="max_guests" value="<?php echo esc_attr($maxGuests); ?>" required><br><br>

      <label>Max Advance Booking Days</label><br>
      <input type="number" name="advance_days" value="<?php echo esc_attr($advanceDays); ?>" required><br><br>

      <label>Same Day Cutoff Time</label><br>
      <input type="time" name="cutoff" value="<?php echo esc_attr($cutoff); ?>" required><br><br>

      <button type="submit" class="btn-primary">
        Save Rules
      </button>

      <p id="rulesMsg"></p>

    </form>
  </div>
</div>


	
	
  </div>

</section>

<?php get_footer(); ?>