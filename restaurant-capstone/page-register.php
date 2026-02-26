<?php
/* Template Name: Register Page */
get_header();
?>

<section class="section">
  <h2>Customer Registration</h2>

  <form id="registerForm">
    <input type="text" name="name" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit" class="btn-primary">Register</button>
    <p id="registerMsg"></p>
  </form>
</section>

<?php get_footer(); ?>