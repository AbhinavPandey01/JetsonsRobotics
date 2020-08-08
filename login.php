<?php

session_start();

if(isset($_POST['login'])){
  if(empty($_POST['customer']) || empty($_POST['pass'])){
    $_SESSION['loginError']='<p class="text-center" style="color:#FF9900FF; font-size:14px; margin-top:5px;">Please provide all the inputs!</p>';
    header("Location:login.php");
    return;
  }else{
  $_SESSION['loginError']='<p class="text-center" style="color:#FF9900FF; font-size:14px; margin-top:5px;">Customer ID doesn\'t exist!</p>';
  header("Location:login.php");
  return;
};
};


 ?>





<!doctype html>
<html lang="en">
  <head>
    <meta property="og:title" content="Jetsons Robotics: Solar Cleaning Robot">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Jetsons Robotics: Solar Cleaning Robot">
    <meta property="og:url" content="https://www.jetsonsrobotics.com/">
    <meta property="og:site_name" content="Jetsons Robotics: Solar Cleaning Robot">
    <meta name="twitter:title" content="Jetsons Robotics: Solar Cleaning Robot">
    <link rel="canonical" href="https://www.jetsonsrobotics.com/">
    <meta name="keywords" content="solar cleaning, solar robot, solar panel cleaning, solar cleaning services, solar plant cleaning,solar, solar plants, solar plant cleaning robot, cleaning solar panels on roof, self cleaning solar panels, solar panel cleaning kit, solar rooftop cleaning robot, pv cleaning robot, automatic cleaning, photovolatic cleaning robot, better than ecoppia, solar rooftop yojana, solar rooftop system, solar rooftop sytem gujarat,solar panel cleaning perth, solar panel cleaning services perth, solar rooftop cleaning, solar panel cleaning brush, rooftop solar plants, cleaning robot, photovoltaikanlagen reinigung, photovoltaik reinigung, pv anlagen reinigung, cleaning equipment, jetsons robotics, fliprobotics, rooftop PV, cleaning system, cleaning solar panels, solaranlagen reinigung, limpieza automatizada de plantas fotovoltaicas, limpieza automatizada de paneles solares, swiss robot">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Jetsons Robotics: Solar Cleaning Robot</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.min.css">
    <link href='https://fonts.googleapis.com/css?family=Oxygen:400,300,700' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300|Roboto&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oxygen:400,300,700' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/css?family=Raleway:300&display=swap" rel="stylesheet">
    </head>
<body>


  <header>
    <nav id="header-nav" >
      <div class="container nav-bar">
      <div class="brand">
        <img src="images/logo.png"  alt="Jetsons">
        <a id="company" href="index.php">JETSONS ROBOTICS</a>
      </div>
      </div>
    </nav>
  </header>

  <div id="loginForm" class="container poda">
    <div class="container-fluid formBox">
      <div class="xButton">
        <img src="images/xWhite.png" alt="close"/>
      </div>
      <div class="xButtonH">
        <img src="images/xGrey.png" alt="close"/>
      </div>
      <h2 class="text-center"> Dashboard Log In</h2>
      <hr>
      <?php
      if(isset($_SESSION['loginError'])){

        echo($_SESSION['loginError']);
        unset($_SESSION['loginError']);
      };
       ?>
      <form method="post" class="postForm">
        <div class="field">
          <p>Customer ID</p>
          <input type="text" name="customer"/>
        </div>
        <div class="field">
          <p>Password</p>
          <input type="text" name="pass"/>
        </div>

        <button class="menu-button" type="submit" name="login">
          <span>Submit</span>
        </button>
        <p class="text-center" id="forgot"><a href="index.php?contact=us">Forgot Password or User ID?</a></p>
      </form>

    </div>
  </div>


  <script src="js/jquery-2.1.4.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/underscore-min.js"></script>
</body>
</html>
