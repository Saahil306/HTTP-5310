<?php
/* Template Name: Menu Page */
get_header();
?>

<section class="menu-hero">
  <div class="container">
    <h1>Our Menu</h1>
    <p>Explore our delicious seafood selection</p>
  </div>
</section>

<section class="menu-section">
  <div class="container">

<?php
$cats = get_terms([
  'taxonomy' => 'menu_category',
  'hide_empty' => true
]);

foreach ($cats as $cat):
?>

  <div class="menu-category-block">

    <div class="menu-category-header">
      <h2><?php echo esc_html($cat->name); ?></h2>
      <div class="menu-line"></div>
    </div>

    <div class="menu-grid">

<?php
$items = new WP_Query([
  'post_type' => 'menu_item',
  'posts_per_page' => -1,
  'tax_query' => [[
    'taxonomy' => 'menu_category',
    'field' => 'term_id',
    'terms' => $cat->term_id
  ]]
]);

while ($items->have_posts()): $items->the_post();

$price = get_post_meta(get_the_ID(), '_cb_price', true);
$spice = get_post_meta(get_the_ID(), '_cb_spice', true);
?>

  <div class="menu-card">
    <div class="menu-card-inner">

      <div class="menu-top">
        <h3><?php the_title(); ?></h3>
        <span class="price">$<?php echo esc_html($price); ?></span>
      </div>

      <p class="menu-desc"><?php echo get_the_excerpt(); ?></p>

      <?php if ($spice): ?>
        <span class="spice-badge spice-<?php echo esc_attr(strtolower($spice)); ?>">
          🌶 <?php echo esc_html($spice); ?>
        </span>
      <?php endif; ?>

    </div>
  </div>

<?php endwhile; wp_reset_postdata(); ?>

    </div>
  </div>

<?php endforeach; ?>

  </div>
</section>

<?php get_footer(); ?>