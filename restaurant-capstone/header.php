<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php bloginfo('name'); ?></title>
  <?php wp_head(); ?>
</head>
<body>

<header>
  <h1>Captain's Boil</h1>
  <nav>
    <a href="<?php echo home_url(); ?>">Home</a> |
    <a href="<?php echo home_url('/menu'); ?>">Menu</a> |
    <a href="#">Reservations</a> |
    <a href="#">Contact</a>
  </nav>
</header>