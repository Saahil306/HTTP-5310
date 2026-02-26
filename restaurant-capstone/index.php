<?php get_header(); ?>

<!-- HERO -->
<!-- HERO SLIDER -->
<section class="hero-slider">

  <div class="hero-slide active">
    <div class="hero-overlay"></div>
    <img src="https://images.unsplash.com/photo-1559847844-5315695dadae?q=80&w=2070" alt="">
    <div class="hero-content">
      <h1>Captain’s Boil</h1>
      <p>Choose Your Catch • Choose Your Flavour • Choose Your Heat</p>
      <a href="<?php echo site_url('/login'); ?>" class="btn-primary">Reserve a Table</a>
    </div>
  </div>

  <div class="hero-slide">
    <div class="hero-overlay"></div>
    <img src="https://images.unsplash.com/photo-1604908176997-4316d8d7c9c4?q=80&w=2070" alt="">
    <div class="hero-content">
      <h1>Premium Seafood Experience</h1>
      <p>Fresh • Bold • Unforgettable</p>
	        <a href="<?php echo site_url('/login'); ?>" class="btn-primary">Reserve a Table</a>
    </div>
  </div>

  <div class="hero-slide">
    <div class="hero-overlay"></div>
    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=2070" alt="">
    <div class="hero-content">
      <h1>Turn Up The Heat</h1>
      <p>From Mild to Fire 🔥</p>
	        <a href="<?php echo site_url('/login'); ?>" class="btn-primary">Reserve a Table</a>
    </div>
  </div>

</section>

<!-- FEATURE IMAGES STRIP -->
<section class="feature-strip">
  <div class="feature-grid">

    <div class="feature-card">
      <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=1200" alt="Seafood Boil">
      <h3>Fresh Seafood</h3>
    </div>

    <div class="feature-card">
      <img src="https://assets.rbl.ms/21534920/origin.jpg" alt="Signature Crab ">
      <h3>Signature Crab</h3>
    </div>

    <div class="feature-card">
      <img src="https://images.unsplash.com/photo-1550547660-d9450f859349?q=80&w=1200" alt="Spicy Food">
      <h3>Custom Spice</h3>
    </div>

  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <div class="cb-page-content-area">
<?php
$post_id = get_the_ID();
$content = get_post_field('post_content', $post_id);
echo apply_filters('the_content', $content);
?>
    </div>
</section>

<?php get_footer(); ?>