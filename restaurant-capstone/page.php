<?php get_header(); ?>

<main>
  <?php
  if (have_posts()) :
    while (have_posts()) : the_post();
  ?>
      <h2><?php the_title(); ?></h2>
      <div>
        <?php the_content(); ?>
      </div>
  <?php
    endwhile;
  else :
    echo "<p>No content found</p>";
  endif;
  ?>
</main>

<?php get_footer(); ?>