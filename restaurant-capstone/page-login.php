<?php
/*
Template Name: Login Page
*/
get_header();
?>

<main class="login-page">
  <h2>Login</h2>

  <form method="post" action="<?php echo wp_login_url(); ?>">
    <label>Username</label>
    <input type="text" name="log" required>

    <label>Password</label>
    <input type="password" name="pwd" required>

    <button type="submit">Login</button>
  </form>
</main>

<?php get_footer(); ?>