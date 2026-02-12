<?php get_header(); ?>

<main>
  <h2>Our Menu</h2>

  <?php
  $categories = get_terms(array(
    'taxonomy' => 'menu_category',
    'hide_empty' => false,
  ));

  foreach ($categories as $category) :
  ?>
    <h3 style="margin-top:30px;"><?php echo $category->name; ?></h3>

    <?php
    $args = array(
      'post_type' => 'menu_item',
      'posts_per_page' => -1,
      'tax_query' => array(
        array(
          'taxonomy' => 'menu_category',
          'field' => 'slug',
          'terms' => $category->slug,
        ),
      ),
    );

    $menu_query = new WP_Query($args);

    if ($menu_query->have_posts()) :
      while ($menu_query->have_posts()) : $menu_query->the_post();
    ?>
        <div style="background:#fff; padding:20px; margin:15px 0; border:1px solid #ddd;">
          <h4><?php the_title(); ?></h4>
          <p><?php the_content(); ?></p>
        </div>
    <?php
      endwhile;
      wp_reset_postdata();
    else :
      echo "<p>No items in this category.</p>";
    endif;
    ?>

  <?php endforeach; ?>
</main>

<?php get_footer(); ?>