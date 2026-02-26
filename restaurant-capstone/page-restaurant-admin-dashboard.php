<?php
/* Template Name: Restaurant Admin Dashboard */

if (!is_user_logged_in()) {
    wp_redirect(site_url('/login'));
    exit;
}

$user = wp_get_current_user();
if (!in_array('restaurant_admin', $user->roles)) {
    wp_redirect(site_url('/'));
    exit;
}

get_header();
?>

<section class="cb-dashboard">

  <!-- SIDEBAR -->
  <aside class="cb-sidebar">
    <h3>🧑‍🍳 Restaurant Admin</h3>

    <ul>
      <li><a href="#" class="active" data-tab="menu-list">Menu Items</a></li>
      <li><a href="#" data-tab="add-menu">Add New Item</a></li>
      <li><a href="#" data-tab="categories">Categories</a></li>
	  <li><a href="#" data-tab="manage">Manage Reservations</a></li>
      <li><a href="#" data-tab="tables">Table Availability</a></li>
	  	  <li><a href="#" data-tab="gallery">Gallery</a></li>
		  
      <!-- ✅ NEW -->
      <li><a href="#" data-tab="pages">Page Content</a></li>
		  
      <li>
        <a href="<?php echo wp_logout_url(site_url('/login')); ?>">
          Logout
        </a>
      </li>
    </ul>
  </aside>

  <!-- MAIN -->
  <div class="cb-main">

    <!-- ================= MENU LIST ================= -->
    <div id="tab-menu-list" class="cb-tab active">

      <div class="cb-card">
        <h3>All Menu Items</h3>

        <?php
        $items = new WP_Query([
          'post_type' => 'menu_item',
          'posts_per_page' => -1,
          'orderby' => 'date',
          'order' => 'DESC'
        ]);

        if ($items->have_posts()):
        ?>

        <table class="cb-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Price</th>
              <th>Spice</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>

          <?php while ($items->have_posts()): $items->the_post();

            $price = get_post_meta(get_the_ID(), '_cb_price', true);
            $spice = get_post_meta(get_the_ID(), '_cb_spice', true);
          ?>

            <!-- ===== MAIN ROW ===== -->
            <tr class="cb-row">
              <td><?php the_title(); ?></td>
              <td>$<?php echo esc_html($price); ?></td>
              <td><?php echo esc_html($spice); ?></td>
              <td>

                <!-- ⭐ NEW EDIT BUTTON -->
                <button class="cb-edit-menu btn-primary"
                        data-id="<?php echo get_the_ID(); ?>">
                  Edit
                </button>

                <!-- OLD DELETE (UNCHANGED) -->
                <button class="cb-delete-menu btn-danger"
                        data-id="<?php echo get_the_ID(); ?>">
                  Delete
                </button>

              </td>
            </tr>

            <!-- ⭐ INLINE EDIT ROW (NEW PREMIUM FEATURE) -->
            <tr class="cb-edit-row" id="edit-<?php echo get_the_ID(); ?>" style="display:none;">
              <td colspan="4">

                <div class="cb-edit-box">

                  <input type="text"
                         class="cb-edit-title"
                         value="<?php echo esc_attr(get_the_title()); ?>"
                         placeholder="Item name">

                  <textarea class="cb-edit-desc"
                            placeholder="Description"><?php echo esc_textarea(get_the_content()); ?></textarea>

                  <input type="number"
                         class="cb-edit-price"
                         value="<?php echo esc_attr($price); ?>"
                         placeholder="Price">

                  <select class="cb-edit-spice">
                    <option value="">Select spice</option>
                    <option value="mild" <?php selected($spice,'mild'); ?>>Mild</option>
                    <option value="medium" <?php selected($spice,'medium'); ?>>Medium</option>
                    <option value="hot" <?php selected($spice,'hot'); ?>>Hot</option>
                  </select>

                  <select class="cb-edit-category">
                    <?php
                    $cats = get_terms([
                      'taxonomy' => 'menu_category',
                      'hide_empty' => false
                    ]);

                    if ($cats && !is_wp_error($cats)):
                      foreach ($cats as $c):
                    ?>
                      <option value="<?php echo $c->term_id; ?>">
                        <?php echo esc_html($c->name); ?>
                      </option>
                    <?php endforeach; endif; ?>
                  </select>

                  <button class="cb-save-menu btn-primary"
                          data-id="<?php echo get_the_ID(); ?>">
                    Save
                  </button>

                  <button class="cb-cancel-edit btn-secondary">
                    Cancel
                  </button>

                </div>

              </td>
            </tr>

          <?php endwhile; wp_reset_postdata(); ?>

          </tbody>
        </table>

        <?php else: ?>
          <p>No menu items found.</p>
        <?php endif; ?>

      </div>
    </div>

    <!-- ================= ADD MENU ================= -->
    <div id="tab-add-menu" class="cb-tab">

      <div class="cb-card">
        <h3>Add Menu Item</h3>

        <form id="addMenuForm">

          <label>Item Name</label><br>
          <input type="text" name="title" required><br><br>

          <label>Description</label><br>
          <textarea name="description"></textarea><br><br>

          <label>Price</label><br>
          <input type="number" name="price" required><br><br>

          <select name="spice">
            <option value="">Select (optional)</option>
            <option value="mild">Mild</option>
            <option value="medium">Medium</option>
            <option value="hot">Hot</option>
          </select><br><br>

          <label>Category</label><br>
          <select name="category" required>
            <option value="">Select Category</option>

            <?php
            $cats = get_terms([
              'taxonomy'   => 'menu_category',
              'hide_empty' => false,
            ]);

            if (!empty($cats) && !is_wp_error($cats)):
              foreach ($cats as $cat):
            ?>
              <option value="<?php echo esc_attr($cat->term_id); ?>">
                <?php echo esc_html($cat->name); ?>
              </option>
            <?php endforeach; endif; ?>
          </select>

          <button type="submit" class="btn-primary">
            Add Item
          </button>

          <p id="menuMsg"></p>

        </form>
      </div>
    </div>

    <!-- ================= CATEGORIES ================= -->
    <div id="tab-categories" class="cb-tab">

      <div class="cb-card">
        <h3>Manage Categories</h3>

        <form id="addCategoryForm">
          <input type="text" name="name" placeholder="New category name" required>
          <button type="submit" class="btn-primary">Add Category</button>
          <p id="catMsg"></p>
        </form>

        <hr style="margin:20px 0;">

        <table class="cb-table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>

          <?php
          $cats = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => false
          ]);

          if ($cats && !is_wp_error($cats)):
            foreach ($cats as $cat):
          ?>

            <tr>
              <td><?php echo esc_html($cat->name); ?></td>
              <td>
                <button class="btn-danger cb-delete-cat"
                        data-id="<?php echo $cat->term_id; ?>">
                  Delete
                </button>
              </td>
            </tr>

          <?php endforeach; endif; ?>

          </tbody>
        </table>

      </div>
    </div>
	
	 <!-- ================= MANAGE ================= -->
    <div id="tab-manage" class="cb-tab">

      <div class="cb-card">
        <h3>Reservations</h3>

        <?php
		
		$today = current_time('Y-m-d');
        $res = new WP_Query([
          'post_type' => 'reservation',
          'posts_per_page' => -1,
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
			  <th>Date</th>
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
          <p>No reservations.</p>
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
	
	
		<!-- ================= Gallery ================= -->
<div id="tab-gallery" class="cb-tab">
  <div class="cb-card">
    <h3>Restaurant Gallery</h3>

    <form id="galleryForm" enctype="multipart/form-data">
      <input type="file" name="image" required>
      <button class="btn-primary">Upload</button>
      <p id="galleryMsg"></p>
    </form>

    <hr>

    <div class="cb-gallery-grid">
      <?php
      $imgs = get_posts([
        'post_type' => 'cb_gallery',
        'numberposts' => -1
      ]);

      foreach ($imgs as $img):
        $thumb = get_the_post_thumbnail_url($img->ID,'medium');
      ?>
        <div class="cb-gallery-item">
          <img src="<?php echo esc_url($thumb); ?>">
          <button class="cb-delete-gallery"
                  data-id="<?php echo $img->ID; ?>">
            Delete
          </button>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>


    <!-- ================= PAGE CONTENT (NEW) ================= -->
    <div id="tab-pages" class="cb-tab">
      <div class="cb-card">
        <h3>Edit Website Pages</h3>

        <?php
        $pages = get_pages([
          'title_li' => '',
          'include'  => [
            get_page_by_path('home')->ID ?? 0,
            get_page_by_path('about')->ID ?? 0,
            get_page_by_path('contact')->ID ?? 0,
          ]
        ]);

        foreach ($pages as $page):
        ?>
          <div style="margin-bottom:30px;">
            <h4><?php echo esc_html($page->post_title); ?></h4>

            <textarea class="cb-page-content"
                      data-id="<?php echo $page->ID; ?>"
                      style="width:100%;height:120px;"><?php
                echo esc_textarea($page->post_content);
            ?></textarea>

            <button class="cb-save-page btn-primary"
                    data-id="<?php echo $page->ID; ?>">
              Save Page
            </button>
          </div>
        <?php endforeach; ?>

      </div>
    </div>
	
	
  </div>

</section>

<?php get_footer(); ?>