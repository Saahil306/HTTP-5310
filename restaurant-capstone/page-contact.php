
<?php
/* Template Name: Contact Page */
get_header();
?>

<section class="section">
<h2>Contact Us</h2>

    <div class="cb-page-content-area">
<?php
$post_id = get_the_ID();
$content = get_post_field('post_content', $post_id);
echo apply_filters('the_content', $content);
?>
    </div>

<div style="margin-top:30px;">
<iframe
src="https://maps.google.com/maps?q=toronto&t=&z=13&ie=UTF8&iwloc=&output=embed"
width="100%"
height="400"
style="border:0;border-radius:12px;">
</iframe>
</div>
</section>

<?php get_footer(); ?>