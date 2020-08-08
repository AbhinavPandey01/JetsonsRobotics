
<?php
  require_once "pdo.php";
  session_start();


  $_SESSION['roiSubmission']=$_SESSION['roiSubmission'] ?? 0;
  $_SESSION['contactSubmission']=$_SESSION['contactSubmission'] ?? 0;

  if(!isset($_SESSION['location']) || !isset($_SESSION['userID'])){
    try{
      $ip = $_SERVER['REMOTE_ADDR'];//?? $_SERVER['HTTP_X_FORWARDED_FOR'];
    //  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json?token=d0bd640d96c0fe"));

      $_SESSION['location']=$details->city ?? 'Neverland';
      $sql="SELECT * from jetsons.user_loc WHERE ip=:ip";
      $stmnt=$pdo->prepare($sql);
      $stmnt->execute(array(':ip'=>$ip));
      $row=$stmnt->fetch(PDO::FETCH_ASSOC);
      if(empty($row)){
        $sql = "INSERT INTO jetsons.user_loc (ip,location)
                                  values (:ip,:location)";
        $stmnt = $pdo->prepare($sql);
        $stmnt->execute(array(':ip'=>$ip,':location'=>$_SESSION['location']));
        $_SESSION['userID']=$pdo->lastInsertId();
      }else{
        $_SESSION['userID']=$row['userID'];
      }
    }catch(Exception $e){
      error_log($e->getMessage());
    };
  };

  if(isset($_GET['contact'])){
    $_SESSION['contact']=1;
    header('Location:index.php');
    return;
  }
  if(isset($_POST['calc'])){
      try{
      $_SESSION['roiSubmission']+=1;
      $_SESSION['roi']=1;
      if($_SESSION['roiSubmission']>20){
        $message='Too many submissions!';
        $_SESSION['message']=$message;
        header("Location:index.php");
        return;
      };
      $nc=$_POST['nc'];  //Number of cleanings per month
      $cc=$_POST['cc'];  //cost of manual cleaning(INR)
      $mgl=$_POST['mgl'];  //Monthly generation loss(%)
      $tariff=$_POST['tariff'];
      $sunD=$_POST['sunD'];  //sunshine hours per day
      $sunY=$_POST['sunY'];  //sunshine days per year
      $size=$_POST['size']; //plant size (KW)
      $nRows=$_POST['nRows']; //number of rows
      $panelC=$_POST['panelC']; //capacity of a panel (watts)
      $_SESSION['nc']=$nc;
      $_SESSION['cc']=$cc;
      $_SESSION['mgl']=$mgl;
      $_SESSION['tariff']=$tariff;
      $_SESSION['sunD']=$sunD;
      $_SESSION['size']=$size;
      $_SESSION['nRows']=$nRows;
      $_SESSION['panelC']=$panelC;
      $_SESSION['sunY']=$sunY;

      $checkEmpty=empty($nc) || empty($cc) || empty($mgl) || empty($tariff) || empty($sunD) || empty($size)
                    ||empty($nRows) || empty($panelC)|| empty($sunY);

      if(!$checkEmpty){
        $checkNumeric=!is_numeric($nc) || !is_numeric($cc) || !is_numeric($mgl) || !is_numeric($tariff) || !is_numeric($sunD) ||
                      !is_numeric($size) || !is_numeric($nRows) || !is_numeric($panelC)|| !is_numeric($sunY);
        if(!$checkNumeric){
          $nc=(float)$nc;
          $cc=(float)$cc;
          $mgl=(float)$mgl;
          $tariff=(float)$tariff;
          $sunD=(float)$sunD;
          $sunY=(int)$sunY;
          $size=(float) $size;
          $nRows=(int) $nRows;
          $panelC=(float)$panelC;


          $costYmanual=12*$nc*$cc*(($size*1000)/$panelC); //Cost of manual cleaning (INR)
          $agl=$mgl*$size*$sunY*$sunD*$tariff/100;  // Annual generation loss (INR)
          $nRobots=ceil($size/500);
          $unitRobotCost=350000;
          $unitTrackCost=2000;
          $installCost=10000;
          $life=5;                //product life
          $opex=12000; //INR
          $rCleaningsPerMonth=30;  //Robotic cleanings per month.
          $solutionCost=$unitRobotCost*$nRobots+$nRows*$unitTrackCost+$installCost*$nRobots;  //INR
          $solutionCostPerPanel=$solutionCost/($life*($sunY*($nc/30)*(($size*1000)/$panelC))); //INR

          $paybackPeriod= 12*($solutionCost/($costYmanual+$agl-$opex)); //In months

          $paybackPeriod=(int)ceil($paybackPeriod);

          $cashFlow= $costYmanual+$agl-$opex;        //INR per year
          function npv($r,$cashFlow,$solutionCost){
            $npv=-$solutionCost+$cashFlow/(1+$r)+$cashFlow/pow((1+$r),2)+$cashFlow/pow((1+$r),3)
                  +$cashFlow/pow((1+$r),4)+$cashFlow/pow((1+$r),5);
            return $npv;
          }

          function irr($cashFlow,$solutionCost){

            $r=0.00;
            $lastNPV=npv($r,$cashFlow,$solutionCost);

            while($r<=1.00){
              $r+=0.01;
              $npv=npv($r,$cashFlow,$solutionCost);
              if($lastNPV<$npv){
                return $r-0.01;
              }else{
                $lastNPV=$npv;
              }
            }
            return $r-0.01;
          }

          $irr=round(irr($cashFlow,$solutionCost),2)*100;

          $_SESSION['irr']=$irr;
          $_SESSION['paybackPeriod']=round($paybackPeriod);
          $_SESSION['solutionCost']=round($solutionCost);
          $_SESSION['agl']=round($agl);
          $_SESSION['costYmanual']=round($costYmanual);
          $_SESSION['nRobots']=$nRobots;

          $sql="SELECT * from jetsons.roi WHERE loca=:loc";
          $stmnt=$pdo->prepare($sql);
          $stmnt->execute(array(':loc'=>$_SESSION['userID']));
          $row=$stmnt->fetch(PDO::FETCH_ASSOC);
          if(empty($row)){
            $sql = 'INSERT INTO jetsons.roi (cleanCount,cleanCost,genLoss,tariff,sunPerDay,plantSize,Nrows,panelWattage,sunPerYear,loca,paybackPeriod)
                    values (:nc,:cc,:mgl,:tariff,:sunD,:size,:nRows,:panelC,:sunY,:loc,:paybackPeriod)';
            $stmnt = $pdo->prepare($sql);

          }else{
            $sql='UPDATE jetsons.roi SET  cleanCount=:nc,cleanCost=:cc,genLoss=:mgl,tariff=:tariff,
                   sunPerDay=:sunD, plantSize=:size,Nrows=:nRows,panelWattage=:panelC,sunPerYear=:sunY,paybackPeriod=:paybackPeriod WHERE loca=:loc';
            $stmnt = $pdo->prepare($sql);

          }
          $stmnt->execute(array(':nc'=>$nc,':cc'=>$cc,':mgl'=>$mgl,':tariff'=>$tariff,
                 ':sunD'=>$sunD,':size'=>$size,':nRows'=>$nRows,':panelC'=>$panelC,':sunY'=>$sunY,':loc'=>$_SESSION['userID'],':paybackPeriod'=>$paybackPeriod));


          header("Location:index.php");
          return;
        }else{
          $message='Input must be numeric';
          $_SESSION['message']=$message;
          header("Location:index.php");
          return;
        };
      }else{
      $message= 'Please provide all the inputs!';
      $_SESSION['message']=$message;
      header("Location:index.php");
      return;
      };
    }catch(Exception $e){
      error_log($e->getMessage());
    };
  };

  if(isset($_POST['contactUs'])){
    try{
    $_SESSION['contact']=1;
    $_SESSION['contactSubmission']=$_SESSION['contactSubmission']+1;
    if($_SESSION['contactSubmission']>5){
      $_SESSION['contactMessage']="Too many submissions!";
      header("Location:index.php");
      return;
    }


    $Fname=$_POST['Fname'];
    $Lname=$_POST['Lname'];
    $email=$_POST['email'];
    $phone=$_POST['phone'];
    $message=$_POST['message'];

    $checkEmpty=empty($Fname) || empty($Lname) ||empty($email) ||empty($phone)||empty($message);
    $checkNumeric=is_numeric($phone);
    if(!$checkEmpty && !strstr($_POST['email'],'@')){
      $_SESSION['contactMessage']="Invalid email address.";
      header("Location:index.php");
      return;
    }elseif(!$checkEmpty && $checkNumeric){
      $name=$Fname.$Lname;
      $sql="INSERT INTO jetsons.emails (name,email,phone,message,loc) values (:name,:email,:phone,:message, :loc)";
      $stmnt=$pdo->prepare($sql);
      $stmnt->execute(array(':name'=>$name,':email'=>$email,':phone'=>$phone,':message'=>$message,':loc'=>$_SESSION['userID']));
      $_SESSION['contactMessage']="Message sent.";
      mail('abhinavp9873@gmail.com','testMail','This is a test');
      header("Location:index.php");
      return;

    }elseif ($checkEmpty) {
      $_SESSION['contactMessage']="All fields must be filled.";
      header("Location:index.php");
      return;
    }elseif(!$checkNumeric){
      $_SESSION['contactMessage']='Invalid phone number.';
      header("Location:index.php");
      return;
    }

  }catch(Exception $e){
    error_log($e->getMessage());
  };
  }
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
    <meta name="keywords" content="solar panel robot, solar panel cleaning robot, solar panel cleaning india, solar panel cleaning robot india, autonomous solar panel cleanin robot, cleaning robot india, solar cleaning, solar robot, solar panel cleaning, solar cleaning services, solar plant cleaning,solar, solar robot information, solar plants, solar plant cleaning robot, cleaning solar panels on roof, self cleaning solar panels, solar panel cleaning kit, solar rooftop cleaning robot, pv cleaning robot, automatic cleaning, photovolatic cleaning robot, better than ecoppia, solar rooftop yojana, solar rooftop system, solar rooftop sytem gujarat,solar panel cleaning perth, solar panel cleaning services perth, solar rooftop cleaning, solar panel cleaning brush, rooftop solar plants, cleaning robot, photovoltaikanlagen reinigung, photovoltaik reinigung, pv anlagen reinigung, cleaning equipment, jetsons robotics, fliprobotics, rooftop PV, cleaning system, cleaning solar panels, solaranlagen reinigung, limpieza automatizada de plantas fotovoltaicas, limpieza automatizada de paneles solares, swiss robot">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Jetsons Robotics: Solar Cleaning Robot</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.min.css">
    <link href='https://fonts.googleapis.com/css?family=Oxygen:400,300,700' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300|Roboto&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oxygen:400,300,700' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet">
    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/underscore-min.js"></script>
    <script src="js/script.min.js"></script>
    <script src="js/ajax-utils.js"></script>
    <script src="https://www.google.com/jsapi"></script>
  </head>
<body>


<ul class="scrollBar" id="scrollBar"<?php if(isset($_SESSION['contact']) || isset($_SESSION['roi'])){echo "style='display:none;'"; }; ?>>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
  <li class="scrollPos scrollBg1"></li>
</ul>

<div class="faqs container-fluid">
    <div class="wrapper container">
      <div class="closeButton">
        <img src="images/xGrey.png" alt="close">
        <img src="images/xWhite.png" alt="close">

      </div>
      <h1>FAQs</h1>
      <div class="navigation">

        <div class="button">
          <span>System</span>
        </div>
        <div class="button">
          <span>Specs </span>
        </div>
        <div class="button">
          <span>Installation</span>
        </div>
        <div class="button">
          <span>AMC</span>
        </div>
        <div class="button hidden-xs">
          <span>Pilot Tests </span>
        </div>
      </div>


      <div class="wrapper-faq">

        <div class="nthFaq">
          <p>Does the robot require tracks to run on?</p>
          <p>Zero only needs side tracks to connect the different rows/strings so that it can travel accross. It doesn't need any tracks along the rows/strings.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>Will the brush damage panels?</p>
          <p>No. The brush is certified and is made of soft Dupont nylon bristles. It has been thoroughly tested for long hours on solar panel glass by rotating it continously. </p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>If Zero runs on the solar panel, will it damage it in long term?</p>
          <p>No. Zero has been tested and certified for this from a third party lab. The results have been tested negative for any damages like microcracks caused by it during its long term operation.
            This is also something that we have rigorously ensured through our design. The wheels run only on the outer aluminium frames of the solar panel and the robot weighs less than 30KGs.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>Can you elaborate more on remote operation?</p>
          <p>The systems comes with an intergreted IOT funcionality that allows Zero to be connected to the internet via an edge device. We provide our clients an access to a dashboard for
            operating Zero remotely. You can view the robot's status, monitor efficiency, schedule cleanings and raise tickets if your are facing any issues.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
      </div>
      <div class="wrapper-faq">
        <div class="nthFaq">
          <p>How much the robot weighs?</p>
          <p>30 Kgs.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>What is the maximum gap between the panels the robot can cross?</p>
          <p>20mm-30mm.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>Hou much is the run time and how it is powered?</p>
          <p>Zero can run for 2 hours per single charge. It is powered by a Lithium-ion battery onboard.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>How much it can clean in a single charge?</p>
          <p>500 KW.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>Can we use it for wet cleaning? Will the water damage it?</p>
          <p>The bot is IP65 and fully protected from dust and water. So unlike the robots available in the market it can do both wet and dry cleaning.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>What's the standard length of solar panel Zero works on? I have a solar panels of different length. Do you allow customisation?</p>
          <p>Zero has been designed to run on panels of 2m lengths. But we do provide custom versions that are made for panel length of upto 3 meters.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
      </div>
      <div class="wrapper-faq">
        <div class="nthFaq">
          <p>How much time does it take for the installation of a single unit of Zero?</p>
          <p>It only takes about 1-2 days once the system is transported to the site.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>What sort of provisions do you need on the site to setup the sytsem?</p>
          <p>We need fast clearance and easy access to the site. Apart from this we also need accessiblity to the LAN to setup the IOT and a 220V AC supply to provide a charging point for the robot.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>

      </div>
      <div class="wrapper-faq">
        <div class="nthFaq">
          <p>What's the warranty period?</p>
          <p>We provide a warranty of 5 years from the date of the first operation.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
        <div class="nthFaq">
          <p>How much is the life of a single robot and do you provide any maintenance services after installation ?</p>
          <p>Zero is made to last about  minimum 5 years easily in the outdoor condition and with proper maintanance can run for 10 years without any issues.
            As for AMC, we have hired third party vendors for servicing and maintaining the robot at a marginal cost after the warranty period is over.</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>

      </div>
      <div class="wrapper-faq">
        <div class="nthFaq">
          <p>I am interested in this technology but would like to get the system tested first at my site.</p>
          <p>You can contact us for a pilot test. We will visit your site for studying the layout and plan a pilot accordingly</p>
          <div class="downGrey">
            <img src="images/downGrey.png" alt="image">
          </div>

        </div>
      </div>

    </div>
  </div>

<div id="page1" class="pages">
  	<header>
  		<nav id="header-nav" >
        <div class="container nav-bar">
        <div class="brand">
          <img src="images/logo.png"  alt="Jetsons">
          <a id="company" href="index.php">JETSONS ROBOTICS</a>
        </div>
        <div class="menu-bar">
          <div class="menu-button hidden-xs" onclick="location.href='login.php';">
            <span> Log in</span>
          </div>
          <div class="menu-button hidden-xs" onclick="location.href='services.html';">
            <span> Services</span>
          </div>
          <div id="menu" class="container">
            <input  id="pseudoB" type="checkbox"  />
            <span class="sr-only">Toggle navigation</span>
            <div class="bar1"></div>
            <div class="bar2"></div>
            <div class="bar3"></div>

            <ul id="slide-nav">
              <li class="visible-xs"> <a href="login.php"> Log in </a></li>
              <li class="visible-xs"> <a href="services.html"> Services </a></li>
              <li><a href="#"> FAQs </a></li>
              <li><a href="media.html" target="_blank"> Media </a></li>
              <li><a href="docs/product_spec_annexure2.pdf" target="_blank"> Product Datasheet </a></li>
              <li><a id="sideNavContact"> Contact Us </a></li>
            </ul>
          </div>
        </div>
        </div>
  		</nav>
  	</header>


  <div id="main" class="container">
    <div id="video" class="container">

      <video autoplay="" muted="" loop="" playsinline=""><source src="videos/1.mp4" type="video/mp4">
        Your browser does not support the video.
      </video>

      <div class="overlay"></div>
      <div class="intro">
        <p> Introducing Zero</p>
        <p> Truly Autonomous </p>
        <p>Solar Panel Cleaning Robot</p>
        <p> 1 Robot Cleans Multiple Rows</p>
        <p> Remote Operation Supported </p>
        <p> No Water Needed </p>
        <p> ROI in less than 18 months </p>
      </div>

      <div class="wrap-button container">
        <div class="menu-button">
          <span> Calculate ROI </span>
        </div>
        <div class="menu-button">
          <span>Contact Us </span>
        </div>
      </div>
    </div>

    <div id="cForm" class="container" <?php if(isset($_SESSION['contact'])){echo "style='visibility: visible; opacity:1;'"; unset($_SESSION['contact']); }; ?>>

      <div class="container-fluid formBox">
        <div class="xButton">
          <img src="images/xWhite.png" alt="close"/>
        </div>
        <div class="xButtonH">
          <img src="images/xGrey.png" alt="close"/>
        </div>
        <h2 class="text-center"> Contact Us </h2>
        <?php if(isset($_SESSION['contactMessage'])){
          echo('<p class="text-center" style="color:#595959; font-size:14px;">'.$_SESSION['contactMessage'].'</p>');
          unset($_SESSION['contactMessage']);
        };?>
        <form method="post" class="postForm">
          <div class="flexWrapper">
            <div id="Fname">
              <p>First Name:</p>
              <input type="text" name="Fname"/>
            </div>
            <div id="Lname">
              <p>Last Name:</p>
              <input type="text" name="Lname"/>
            </div>
          </div>
          <div class="field">
            <p>Email:</p>
            <input type="text" name="email"/>
          </div>
          <div class="field">
            <p>Phone No:</p>
            <input type="text" name="phone"/>
          </div>
          <div class="field">
            <p>Message:</p>
            <textarea name="message" ></textarea>
          </div>

          <button class="menu-button" type="submit" name="contactUs">
            <span>Submit</span>
          </button>
        </form>

      </div>
    </div>


  </div>


  <div id="roi" class="container" <?php if(isset($_SESSION['roi'])){echo "style='transform:none'"; unset($_SESSION['roi']); }; ?> >
    <form method="post">
      <div class="input container-fluid">
        <div class="mainWrapper container">

          <div class=" wrapper  col-md-6">
            <p class="attribute">Average number of cleanings per month</p>
            <input class="userInput" type="text" value=<?=($_SESSION['nc'] ?? '3');?> name="nc"/>
          </div>
          <div class="wrapper col-md-6 ">
            <p class="attribute">Cost of cleaning(INR per module)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['cc'] ?? '4');?> name="cc"/>
          </div>
          <div class="wrapper col-md-6">
            <p class="attribute">Average monthly generation loss(%)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['mgl'] ?? '4');?> name="mgl"/>
          </div>
          <div class="wrapper col-md-6 ">
            <p class="attribute">tariff(INR/KWp)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['tariff'] ?? '3');?> name="tariff"/>
          </div>
          <div class="wrapper col-md-6">
            <p class="attribute">Plant Size(Killo-Watts)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['size'] ?? '500');?> name="size"/>
          </div>
          <div class="wrapper col-md-6">
            <p class="attribute">No. of independent rows</p>
            <input class="userInput" type="text" value=<?=($_SESSION['nRows'] ?? '24');?> name="nRows"/>
          </div>
          <div class="wrapper col-md-6 ">
            <p class="attribute">Solar Panel Capacity(Watts)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['panelC'] ?? '325');?> name="panelC"/>
          </div>
          <div class="wrapper col-md-6">
            <p class="attribute">Sunshine hours in a day(hours)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['sunD'] ?? '7');?> name="sunD"/>
          </div>
          <div class="wrapper col-md-6 ">
            <p class="attribute">Days of sunshine in an year(days)</p>
            <input class="userInput" type="text" value=<?=($_SESSION['sunY'] ?? '300');?> name="sunY"/>
          </div>
          <div class="imgWrapper">
            <img src="images/back1.svg" alt="back-button"/>
          </div>
          <div class="imgWrapperH">
            <img src="images/back1hover.png" alt="back-button"/>
          </div>
        </div>
      </div>

      <div class="output container-fluid">
        <div class="mainWrapper container">
          <div class="wrapper  col-md-6">
            <p class="attribute">Annual cost of manual cleanin(INR)*</p>
            <p class="userOutput"><?php if(isset($_SESSION['costYmanual'])){echo($_SESSION['costYmanual']);unset($_SESSION['costYmanual']);};?></p>
          </div>
          <div class="wrapper  col-md-6">
            <p class="attribute">Number of robots required*</p>
            <p class="userOutput"><?php if(isset($_SESSION['nRobots'])){echo($_SESSION['nRobots']);unset($_SESSION['nRobots']);};?></p>
          </div>
          <div class="wrapper  col-md-6">
            <p class="attribute">Annual generation loss(INR)*</p>
            <p class="userOutput"><?php if(isset($_SESSION['agl'])){echo($_SESSION['agl']); unset($_SESSION['agl']);};?></p>
          </div>
          <div class="wrapper  col-md-6">
            <p class="attribute">Robotic Solution cost(For 5 Years)*</p>
            <p class="userOutput"><?php if(isset($_SESSION['solutionCost'])){echo($_SESSION['solutionCost']);unset($_SESSION['solutionCost']);};?></p>
          </div>
          <div class="wrapper  col-md-6">
            <p class="attribute">Payback Period(months)*</p>
            <p class="userOutput"><?php if(isset($_SESSION['paybackPeriod'])){echo($_SESSION['paybackPeriod']); unset($_SESSION['paybackPeriod']);};?></p>
          </div>
          <div class="wrapper  col-md-6">
            <p class="attribute">IRR for 5 Years(%)*</p>
            <p class="userOutput"><?php if(isset($_SESSION['irr'])){echo($_SESSION['irr']);unset($_SESSION['irr']);};?></p>
          </div>
          <div id="roiMessage" class="wrapper messages  col-md-12" >
            <p class="attribute"><?php
            if(isset($_SESSION['message'])){
              echo($_SESSION['message']);
              unset($_SESSION['message']);
            };?></p>
              <p class="attribute">*These are ballpark values.
                To get a more accurate estimation please <span>contact us</span>.</p>
          </div>
        </div>
      </div>



      <button id="roiCalc" class="menu-button" type="submit" name="calc">
        <span>Calculate</span>
      </button>
    </form>

  </div>

</div>

<div id="page2" class="pages">
  <div class="solar-robot container">
    <div class="details">
      <p>Zero is a truly  autonomous  machine  built for commercial and
        industrial solar rooftops installation. It’s a robot designed to
        travel from one row to another. This type of system is the first of
        its kind where one robot can clean solar plants upto capacity of 500  KW.
      </p>
      <div class="images">
        <div class="img ">
          <img src="images/patents.svg" alt="patents">
          <p>5 Patents</p>
        </div>
        <div class="img">
          <img src="images/cert.svg" alt="certificates">
          <p>Fully Certified</p>
        </div>
        <div class="img">
          <img src="images/awards.svg" alt="awards">
          <p>5 Awards</p>
        </div>
      </div>
      <div class="efficiencyWrapper">
        <div class="menu-button"><span>Start Cleaning</span></div>
        <div class="efficiency"><span>Relative efficiency</span><div class="effBar"><span>50%</span></div></div>
      </div>

    </div>
    <div class="system">
      <div class="hub">
        <img id=hub src="images/hub0.png" alt="hub"/>
        <img id=hub src="images/hub.png" alt="hub"/>
        <img id=hub1 src="images/hub1.png" alt="hub"/>
        <img id=hub2 src="images/hub2.png" alt="hub"/>
      </div>
      <div class="bot">
        <canvas id="canvas"></canvas>
      </div>

    </div>
  </div>
  <div class="down">
    <img src="images/down.svg" alt="down">
    <img src="images/downGrey.png" alt="down">
  </div>
</div>

<div id="page3" class="pages">
  <div class="wrapper container">
    <h2>Features</h2>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/water.svg" alt="">
      <p>Water Free Operation</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/truly.svg" alt="">
      <p>Truly Autonomous</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/smart.svg" alt="">
      <p>Smart Scheduling</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/self.png" alt="">
      <p>Self Powered</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/roi.svg" alt="">
      <p>ROI in 18 Months</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-4 col-xs-6">
      <img src="images/warranty.svg" alt="">
      <p>Warranty</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/one.svg" alt="">
      <p>One Day on-site Assembly</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/waterproof.svg" alt="">
      <p>Waterproof</p>
    </div>

  </div>
  <div class="down">
    <img src="images/down.svg" alt="down">
    <img src="images/downW.png" alt="down">
  </div>
</div>

<div id="page4" class="pages">
  <div class="wrapper container">
    <h2 >Seamless intergration with  solar plants</h2>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/climbs.png" alt="">
      <p>Climbs slopes upto 15 °</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/handles.png" alt="">
      <p>Handles y-axis offsets </p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/spans.png" alt="">
      <p>Spans gaps and heights</p>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-xs-6">
      <img src="images/modular.png" alt="">
      <p>Modular track system</p>
    </div>
  </div>
  <div class="wrapper2 container">
    <h3 class="text-center">Remote operation via dashboard</h3>
    <div class="innerWrapper">
      <div class="iconWrapper visible-md visible-lg">
        <img src="images/laptopCloud.svg" alt="">
      </div>
      <div class="iconWrapper visible-md visible-lg">
        <img src="images/system2.svg" alt="">
      </div>
      <div class="iconWrapper hidden-md hidden-lg">
        <img src="images/laptop.svg" alt="">
      </div>
    </div>
    <div class="down">
      <img src="images/downW.png" alt="down">
      <img src="images/downGrey.png" alt="down">
    </div>

  </div>

</div>

<div id="page5" class="pages">
  <div class="wrapper container row">
    <div class="title">
      <h2>Team</h2>
      <div class="vBar"></div>
      <span>"If everyone is moving forward together, then success takes care of itself." — Henry Ford</span>
    </div>
    <div class="iconWrapper col-md-3 col-lg-3 col-sm-6 col-xs-6">
      <img src="images/jatin.png" alt="">
      <img class='jatin' src="images/potraitHover.png" alt="hover">
      <p>Jatin Sharma, System Architecure, Biz Dev (CEO)</p>
      <a href="https://www.linkedin.com/in/jatin-sharma-b017692a/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="iconWrapper col-md-3 col-lg-3 col-sm-6 col-xs-6">
      <img src="images/abhinav.png" alt="">
      <img class='abhinav' src="images/potraitHover.png" alt="hover">
      <p> Abhinav Pandey, Mechanical  Design  Lead (CTO)</p>
      <a href="https://www.linkedin.com/in/abhinav-pandey-ap/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="clearfix hidden-md hidden-lg"> </div>
    <div class="iconWrapper col-md-3 col-lg-3 col-sm-6 col-xs-6">
      <img src="images/manik.png" alt="">
      <img class='manik' src="images/potraitHover.png" alt="hover">
      <p>Manik Sharma,<br/> Embedded Programming Lead</p>
      <a href="https://www.linkedin.com/in/manik1991/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="iconWrapper col-md-3 col-sm-6 col-lg-3 col-xs-6">
      <img src="images/garvit.png" alt="">
      <img class='garvit' src="images/potraitHover.png" alt="hover">
      <p>Garvit Dahiya,<br/> Supply Chain, Operations Lead</p>
      <a href="https://www.linkedin.com/in/garvit-dahiya-811b2844/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="iconWrapper col-md-3 col-lg-3 col-sm-6 col-xs-6">
      <img src="images/harshit.png" alt="">
      <img class='harshit' src="images/potraitHover.png" alt="hover">
      <p>Harshit Madan,<br/> PCB Design, Electronics Lead</p>
      <a href="https://www.linkedin.com/in/harshitmadaan/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
  </div>
  <div class="wrapper2 container">
    <p class="team">Our team is a combination of experienced mechanical designers,
      embedded engineers and programmers. Primarily, we are maker of things and our raison d'être is to find solutions that will automate away
       the routine and repetitive tasks so that that humans can focus more on building beautiful artifacts and exploring the secrets of the physical universe.
    </p>
    <p class="jatin">He obtained his B.tech degree in Mechanical engineering from
      IP university in 2013. He has 6 years of experience working in the robotics industry. He held the
      position of the senior design engineer in Greyorange India. In the past, he has worked on
      multiple projects for DRDO as a designer. He believes that humans shouldn’t engage in a
      repetitive task, where they have no opportunity to grow and such jobs should be left to robots.
    </p>
    <p class="abhinav">He completed his graduation from BITS Pilani
      as a mechanical engineer in 2016 and worked at GreyOrange India as a design engineer.
      He is well-equipped with a wide array of technical skills in both the hardware and software fields and
     is passionate about building things that make mankind's
      journey into the future more sustainable.
    </p>
    <p class="garvit">He obtained his B.tech degree in Mechanical engineering from IP
      university in 2016. He has taken part in more than 12 competitions in college and lead a strong
      team of 20 engineers to ABU Robocon. After graduation, he has worked in the solar
      industry--mostly on performance bench-marking and data gathering.
    </p>
    <p class="manik">He obtained his B.tech degree in Electronics and
      Communication Engineering from IP university in 2014. He has experience working on
      embedded systems and microcontrollers and has a strong background in physics. This mixture
      of skills makes him ideal for a programming system that involves complex dynamics.
    </p>
    <p class="harshit">He obtained his B.tech degree in Electrical
      and Electronics engineering from IP university in 2018. He has worked on a multitude of
      projects like neuro-feedback systems and smart-book reader out of his own sheer curiosity. He
      was a senior author of a technical magazine in his college and has published 2 papers.
    </p>

  </div>
  <div class="down">
    <img src="images/down.svg" alt="down">
    <img src="images/downGrey.png" alt="down">
  </div>

</div>

<div id="page6" class="pages">
  <div class="wrapper container row">
    <div class="title">
      <h2>Mentors</h2>
      <div class="vBar"></div>
      <span>"If I have seen further it is by standing on the shoulders of giants." — Isaac Newton</span>
    </div>
    <div class="iconWrapper col-md-4 col-lg-4 col-sm-6 col-xs-6">
      <img src="images/avinash.png" alt="">
      <img class='avinash' src="images/potraitHover.png" alt="hover">
      <p>Avinash Pitale, PlasmaBerry</p>
      <a href="https://www.linkedin.com/in/avinashpitale/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="iconWrapper col-md-4 col-lg-4 col-sm-6 col-xs-6">
      <img src="images/tanvir.png" alt="">
      <img class='tanvir' src="images/potraitHover.png" alt="hover">
      <p> Tanvir Singh, Amplus &nbsp; Solar </p>
      <a href="https://www.linkedin.com/in/tanvirsinghs/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>
    <div class="iconWrapper col-md-4 col-lg-4 col-sm-12 col-xs-12">
      <img src="images/aditya.png" alt="">
      <img class='aditya' src="images/potraitHover.png" alt="hover">
      <p>Dr. Aditya Dev Sood, Startup Tunnel</p>
      <a href="https://www.linkedin.com/in/adityadevsood/" target="_blank"><img src="images/linkedin.png" alt=""></a>
    </div>

  </div>
  <div class="wrapper2 container">
    <p class="team text-center">We are being guided by mentors who have years of experience in the solar industry and have a streak of mentoring successful startups.
    </p>
    <p class="avinash">He has 20 years experience in building hardware and software platforms.
      He has developed various solutions on renewable energy, especially in Solar PV and has
       designed and launched special rooftop product series under the name Progeny
       ( residential ), Dynamo ( Commercial houses ) and turbina ( utility level ) for
       PlasmaBerry Solar.
    </p>
    <p class="tanvir">He is focused on business development,
      project operations, strategic planning, and team building.
       Have proven capabilities and practical knowledge of contract negotiations
       and new product development. Over 8 years of experience in solar and battery
       energy storage industry developing and managing 350MW Portfolio across 200 projects.
    </p>
    <p class="aditya">He is a serial social entrepreneur with a background in
       Design and the SocialSciences. He is a former Fulbright Scholar with two doctorates
        from the University of Chicago. Sood has built several different kinds of
        organizations, all of which are co-located at the Vihara Innovation Campus
        in New Delhi. The Center for Knowledge Societies (www.cks.in) is an innovation
        consulting firm, which focuses on user research, user experience design, design
        strategy and systems innovation.
    </p>

  </div>
  <div class="down">
    <img src="images/down.svg" alt="down">
    <img src="images/downGrey.png" alt="down">
  </div>

</div>

<div id="pageEnd" class="pages">
  <div class="clients wrapper container">
    <h2 >Our Clients</h2>
    <div class="Scrollwrapper"><ul class="auto-scroll">
        <li class="fourth image">
          <img src="images/fourth.png" alt="">
        </li>
        <li class="freyr image">
          <img src="images/freyr.png" alt="">
        </li>
        <li class="8minutes image">
          <img src="images/8minutes.png" alt="">
        </li>
        <li class="amplus image">
          <img src="images/amplus.png" alt="">
        </li>
        <li class="autonk image">
          <img src="images/autonk.png" alt="">
        </li>
        <li class="atlanta image">
          <img src="images/atlanta.png" alt="">
        </li>
      </ul></div>
  </div>
  <div class="partners wrapper container">
    <h2 >Our Partners</h2>
    <div class="Scrollwrapper"><ul class="auto-scroll">
        <li class="invest image">
          <img src="images/invest.png" alt="">
        </li>
        <li class="jetro image">
          <img src="images/jetro.png" alt="">
        </li>
        <li class="nise image">
          <img src="images/nise.png" alt="">
        </li>
        <li class="yesscale image">
          <img src="images/yesscale.png" alt="">
        </li>
        <li class="plasma image">
          <img src="images/plasma.png" alt="">
        </li>
        <li class="nasscom image">
          <img src="images/nasscom.png" alt="">
        </li>
        <li class="make image">
          <img src="images/make.png" alt="">
        </li>
        <li class="isa image">
          <img src="images/isa.png" alt="">
        </li>
      </ul></div>
  </div>
  <div id="footer" class="footer container-fluid">
    <div class="footWrapper container">
      <div class="about col-xs-6 col-sm-6 col-md-6 col-lg-3">
        <h5>About us</h5>
        <p>We at Jetsons Robotics believe that a man should not do a machine's job.
          That's why we have started our journey by creating a lineup of world's most advanced solar panel cleaning robots named Zero.
          <br>Zero is a unique amalgamation of cutting edge hardware and sophisticated software which will enable solar industry to improve
          power generation efficiency and reduce the cleaning cost by 40 percent thereby ensuring solar plants work at their peak
          generation capacity.</p>
      </div>
      <div class="contact col-xs-6 col-sm-6 col-md-6 col-lg-3">
        <div class="icons">
          <img src="images/location.png" alt="">
          <img src="images/phone.png" alt="">
          <img src="images/email.png" alt="">
        </div>
        <div class="v-bar"></div>
        <div class="info">
          <p>A-85, Sector-83, Noida<br>Uttar Pradesh - 201305</p>
          <p>+91 9821-480-880<br>+91 9011-479-569</p>
          <p>info@jetsonsrobotics.com</p>
        </div>
      </div>
      <div class="clearfix hidden-lg"></div>
      <div class="map col-xs-6 col-sm-6 col-md-6 col-lg-3">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3505.4266190955345!2d77.39559131468634!3d28.526893982459484!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce9f6bed910d5%3A0xfd23b22200046568!2sJetsons%20Robotics!5e0!3m2!1sen!2sin!4v1582896460717!5m2!1sen!2sin" frameborder="0" style="border:0;" allowfullscreen=""></iframe>

      </div>
      <div class="media col-xs-6 col-sm-6 col-md-6 col-lg-3">
        <div class="brand">
          <a href="index.php"><img src="images/jetsons.png" alt="jetsons"></a>
          <a href="index.php"><h4>Jetsons Robotics</h4></a>
        </div>
        <div class="social">
          <a href="https://www.linkedin.com/company/jetsonsrobotics/" target="_blank"><img src="images/linkedin.png" alt=""></a>
          <a href="https://www.instagram.com/jetsonsrobotics/?hl=en" target="_blank"><img src="images/insta.png" alt=""></a>
          <a href="https://twitter.com/RoboticsJetsons" target="_blank"><img src="images/twitter.png" alt=""></a>
        </div>
        <div class="sub-menu">
          <a href="media.html" target="_blank">Media</a>
          <div class="v-bar"></div>
          <a href="docs/product_spec_annexure2.pdf" target="_blank">Product Datasheet</a>
          <div class="v-bar"></div>
          <a href="#" id="contactScrollTop">Contact Us</a>
        </div>
      </div>

    </div>
    <div class="copyright">
      <p>Copyright &copy 2019 - All Rights Reserved - Jetsons Robotics Pvt Ltd</p>
    </div>
  </div>
</div>

</body>
</html>
