<?php
/* Template Name: About Page */
get_header();
?>

<section class="section">

<div class="cb-about-wrap">

    <h2>About Captain’s Boil</h2>

    <div class="cb-page-content-area">
<?php
$post_id = get_the_ID();
$content = get_post_field('post_content', $post_id);
echo apply_filters('the_content', $content);
?>
    </div>

  </div>

</section>


<!-- ⭐ GALLERY SECTION -->
<section class="section cb-about-gallery">

  <h2 style="text-align:center;">Our Restaurant</h2>

  <div class="cb-gallery-grid">

    <?php
    $imgs = get_posts([
      'post_type'   => 'cb_gallery',
      'numberposts' => -1,
      'orderby'     => 'date',
      'order'       => 'DESC'
    ]);

    if ($imgs):
      foreach ($imgs as $img):
        $thumb = get_the_post_thumbnail_url($img->ID, 'large');
    ?>

      <div class="cb-gallery-item">
        <img src="<?php echo esc_url($thumb); ?>" alt="">
      </div>

    <?php
      endforeach;
    else:
      echo '<p style="text-align:center;">No gallery images yet.</p>';
    endif;
    ?>

  </div>

</section>

<?php get_footer(); ?>