<?php
/* Template Name: Login Page */
get_header();
?>

<section class="section">
  <h2>Login</h2>

  <form id="loginForm">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit" class="btn-primary">Login</button>
    <p id="loginMsg"></p>
  </form>
</section>

<?php get_footer(); ?>