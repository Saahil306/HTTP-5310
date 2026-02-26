<?php
/* Template Name: Customer Dashboard */

if (!is_user_logged_in()) {
    wp_redirect(site_url('/login'));
    exit;
}

$user = wp_get_current_user();
$today = current_time('Y-m-d');

get_header();
?>

<section class="cb-dashboard">

  <!-- SIDEBAR -->
  <aside class="cb-sidebar">
    <h3>⚓ Captain’s Boil</h3>

    <ul>
      <li><a href="#" class="active" data-tab="dashboard">Dashboard</a></li>
      <li><a href="#" data-tab="profile">My Profile</a></li>
      <li><a href="#" data-tab="menu">Menu</a></li>
      <li><a href="#" data-tab="reserve">Reserve Table</a></li>
      <li><a href="#" data-tab="reservations">My Reservations</a></li>
      <li>
        <a href="<?php echo wp_logout_url(site_url('/login')); ?>">
          Logout
        </a>
      </li>
    </ul>
  </aside>

  <!-- MAIN -->
  <div class="cb-main">

    <!-- ================= DASHBOARD ================= -->
    <div id="tab-dashboard" class="cb-tab active">
      <div class="cb-card">
        <h2>Welcome, <?php echo esc_html($user->display_name); ?> 👋</h2>
        <p>Your seafood adventure awaits.</p>	
      </div>
		
		<!-- ⭐ UPCOMING RESERVATION -->
<div class="cb-card" style="margin-top:20px;">
  <h3>Upcoming Reservation</h3>

  <?php
  $user_id = get_current_user_id();
  $today   = current_time('Y-m-d');

  $upcoming = new WP_Query([
    'post_type'      => 'reservation',
    'posts_per_page' => 1,
    'meta_key'       => '_cb_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query' => [
      [
        'key'     => '_cb_user',
        'value'   => $user_id,
        'compare' => '='
      ],
      [
        'key'     => '_cb_date',
        'value'   => $today,
        'compare' => '>=',
        'type'    => 'DATE'
      ],
      [
        'key'     => '_cb_status',
        'value'   => 'cancelled',
        'compare' => '!='
      ]
    ]
  ]);

  if ($upcoming->have_posts()):
    while ($upcoming->have_posts()): $upcoming->the_post();

      $date   = get_post_meta(get_the_ID(), '_cb_date', true);
      $time   = get_post_meta(get_the_ID(), '_cb_time', true);
      $guests = get_post_meta(get_the_ID(), '_cb_guests', true);
  ?>

    <div class="cb-upcoming-box">
      <p><strong>Date:</strong> <?php echo esc_html($date); ?></p>
      <p><strong>Time:</strong> <?php echo esc_html($time); ?></p>
      <p><strong>Guests:</strong> <?php echo esc_html($guests); ?></p>
    </div>

  <?php
    endwhile;
    wp_reset_postdata();
  else:
  ?>

    <p style="color:#aaa;">No upcoming reservations.</p>

  <?php endif; ?>
</div>

    </div>

    <!-- ================= PROFILE ================= -->
    <div id="tab-profile" class="cb-tab">
      <div class="cb-card">
        <h3>My Profile</h3>

        <form id="profileForm">
          <label>Full Name</label><br>
          <input type="text" name="name"
            value="<?php echo esc_attr($user->display_name); ?>" required><br><br>

          <label>Email (readonly)</label><br>
          <input type="email"
            value="<?php echo esc_attr($user->user_email); ?>" readonly><br><br>

          <label>New Password</label><br>
          <input type="password" name="password"
            placeholder="Leave blank to keep same"><br><br>

          <button type="submit" class="btn-primary">
            Update Profile
          </button>

          <p id="profileMsg"></p>
        </form>
      </div>
    </div>

    <!-- ================= MENU (UNCHANGED SAFE) ================= -->
    <div id="tab-menu" class="cb-tab">

      <div class="cb-card">
        <h3>Our Menu</h3>

        <div class="cb-menu-filters">
          <button class="cb-filter-btn active" data-cat="all">All</button>

          <?php
          $cats = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => true
          ]);

          if ($cats && !is_wp_error($cats)):
            foreach ($cats as $cat):
          ?>
              <button class="cb-filter-btn"
                data-cat="<?php echo esc_attr($cat->slug); ?>">
                <?php echo esc_html($cat->name); ?>
              </button>
          <?php endforeach; endif; ?>
        </div>

        <div id="cb-menu-grid" class="cb-menu-grid">

          <?php
          $items = new WP_Query([
            'post_type' => 'menu_item',
            'posts_per_page' => -1
          ]);

          if ($items->have_posts()):
            while ($items->have_posts()): $items->the_post();

              $price = get_post_meta(get_the_ID(), '_cb_price', true);
              $spice = get_post_meta(get_the_ID(), '_cb_spice', true);

              $spice_icon = '';
              if ($spice === 'mild') $spice_icon = '🌶';
              if ($spice === 'medium') $spice_icon = '🌶🌶';
              if ($spice === 'hot') $spice_icon = '🌶🌶🌶';

              $terms = get_the_terms(get_the_ID(), 'menu_category');
              $slug  = ($terms && !is_wp_error($terms)) ? $terms[0]->slug : 'uncategorized';
              $cat_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
          ?>

            <div class="cb-menu-card" data-cat="<?php echo esc_attr($slug); ?>">

              <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>

              <h4><?php the_title(); ?></h4>

              <div class="cb-menu-meta">
                <?php if ($price): ?>
                  <span class="cb-price">$<?php echo esc_html($price); ?></span>
                <?php endif; ?>

                <?php if ($spice_icon): ?>
                  <span class="cb-spice"><?php echo $spice_icon; ?></span>
                <?php endif; ?>
              </div>

              <p><?php the_excerpt(); ?></p>

              <?php if ($cat_name): ?>
                <div class="cb-category"><?php echo esc_html($cat_name); ?></div>
              <?php endif; ?>

            </div>

          <?php endwhile; wp_reset_postdata(); endif; ?>

        </div>
      </div>
    </div>

    <!-- ================= RESERVE TABLE ================= -->
    <div id="tab-reserve" class="cb-tab">

      <div class="cb-card">
        <h3>Reserve a Table</h3>

        <form id="reservationForm">

          <label>Date</label><br>
          <input type="date" name="date" required><br><br>

          <label>Select Time</label>
          <select name="time" required>
            <option value="">Select Time</option>
            <?php foreach (cb_generate_time_slots() as $slot): ?>
              <option value="<?php echo esc_attr($slot); ?>"><?php echo $slot; ?></option>
            <?php endforeach; ?>
          </select>

          <label>Number of Guests</label><br>
          <select name="guests" required>
            <option value="">Select guests</option>
			<?php $max_guests = cb_get_max_guests_per_booking(); ?>
            <?php for ($i=1; $i <= $max_guests; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> Guests</option>
            <?php endfor; ?>
          </select><br><br>

          <button type="submit" class="btn-primary">
            Book Table
          </button>

          <p id="reservationMsg"></p>
        </form>
      </div>

    </div>

    <!-- ================= MY RESERVATIONS (UPCOMING + PAST) ================= -->
    <div id="tab-reservations" class="cb-tab">

      <div class="cb-card">
        <h3>Upcoming Reservations</h3>
		<h5>Rebook will book the table for next day with same time slot, if available*</h5>

        <?php
        $upcoming = new WP_Query([
          'post_type' => 'reservation',
          'posts_per_page' => -1,
          'meta_query' => [
            ['key'=>'_cb_user','value'=>get_current_user_id()],
            ['key'=>'_cb_date','value'=>$today,'compare'=>'>=']
          ],
          'orderby' => 'meta_value',
          'meta_key' => '_cb_date',
          'order' => 'ASC'
        ]);
        ?>

        <?php if ($upcoming->have_posts()): ?>
          <table class="cb-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Guests</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>

            <?php while ($upcoming->have_posts()): $upcoming->the_post();
              $date   = get_post_meta(get_the_ID(), '_cb_date', true);
              $time   = get_post_meta(get_the_ID(), '_cb_time', true);
              $guests = get_post_meta(get_the_ID(), '_cb_guests', true);
              $status = get_post_meta(get_the_ID(), '_cb_status', true);
            ?>

              <tr class="cb-res-row">
                <td><?php echo esc_html($date); ?></td>
                <td><?php echo esc_html($time); ?></td>
                <td><?php echo esc_html($guests); ?></td>
                <td>
                  <span class="cb-status cb-status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                  </span>
                </td>
                <td>
                  <?php if ($status !== 'cancelled'): ?>

<button class="cb-quick-rebook btn-primary"
            data-id="<?php echo get_the_ID(); ?>">
      Rebook
    </button>
                    <button class="cb-cancel-booking btn-danger"
                            data-id="<?php echo get_the_ID(); ?>">
                      Cancel
                    </button>
                  <?php endif; ?>
                </td>
              </tr>

            <?php endwhile; wp_reset_postdata(); ?>

            </tbody>
          </table>
        <?php else: ?>
          <p>No upcoming reservations.</p>
        <?php endif; ?>
      </div>
	  
	      <p style="margin-top:10px;color:#888;">
          For any Reservations changes, contact Restaurant.  
        </p>

    </div>

  </div>

</section>

<?php get_footer(); ?>