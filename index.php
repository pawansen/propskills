<?php
include('header.php');
?>
<!--Main container sec start-->
<div class="mainContainer" ng-controller="homeController" ng-cloak >
    <div class="home_banner d-inline-flex w-100 py-5 align-items-center position-relative">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 offset-lg-5">
                    <div class="banner__text text-right text-white">
                        <h1 class="wow fadeInUp font-italic"> Daily Live Drafts</h1>
                        <h2>1 in 10 chance to win</h2>
                        <p class="wow fadeInUp"  data-wow-delay=".3s"> WIN REAL PRIZE MONEY</p>
                        <a href="javascript:Void(0);" ng-click="redirectToLobby()" class="btn_primary wow fadeInUp" data-wow-delay=".6s">PLAY NOW</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="pick_sports text-white">
        <div class="container-fluid pl-md-0">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-5 d-none d-md-block">
                    <img src="assets/img/pick-sports.png" alt="pick-sports" class="img-fluid">
                </div>
                    
                <div class="col-lg-7 col-md-7 ml-lg-4 pl-lg-5">
                    <p class="mb-3 wow fadeInUp" data-wow-delay=".8s" >are you and your friends ready for</p>
                    <p class="wow fadeInUp" data-wow-delay=".8s"><strong> Season -long leagues that have weekly live </strong></p>
                    <p><strong> drafts and that lasts for weeks longer ? </strong></p>
                    <span class="underline"></span>
                    <a href="javascript:;" ng-click="redirectToCreateLeague()" class="btn_primary">Create a private league</a>
                </div>
            </div>
        </div>
    </section>
    <section class="steps_section text-white">
        <div class="container text-center">
            <div class="comman_heading">
                <h2 class="">How to Play</h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="step_item">
                        <img src="assets/img/play-step1.png" alt="step" class="img-fluid">
                        <h5>STEP 1<aside class="font-weight-normal">Create an account</aside></h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step_item">
                        <img src="assets/img/play-step2.png" alt="step" class="img-fluid">
                        <h5>STEP 2<aside class="font-weight-normal">Choose a contest</aside></h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step_item">
                        <img src="assets/img/play-step2.png" alt="step" class="img-fluid">
                        <h5>STEP 3<aside class="font-weight-normal">Draft Teams</aside></h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step_item">
                        <img src="assets/img/play-step4.png" alt="step" class="img-fluid">
                        <h5>STEP 4<aside class="font-weight-normal"> Play for Prizes (in Paid Leagues)</aside></h5>
                    </div>
                </div>
            </div>
            <div class="text-center mt-md-4 mb-5">
                <a href="javascript:volid(0);" ng-click="redirectToLobby()" class="btn_primary btn_lg">PLAY NOW </a>
            </div>
        </div>
    </section>
    <section class="features d-inline-block w-100" style="background-image: url('');">
        <div class="container-fluid">
            <div class="comman_heading text-center">
                <h2 class="themeClr">FEATURES</h2>
            </div>
            <div class="features-list mt-5">
                <div class="row">
                    <div class="col-md-7 offset-md-5">
                        <div class="features-box d-inline-flex w-100 position-relative">
                            <div class="features-icon d-inline-flex align-items-center justify-content-center mr-md-4">
                                <img src="assets/img/throphy.png" class="" alt="">
                            </div>
                            <div class="features-text position-relative">
                                <h4 class="theme-text">Lorem Ipsum is simply</h4>
                                <p>orem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="features-box left-side d-inline-flex w-100 position-relative">
                            <div class="features-text position-relative">
                                <h4 class="theme-text">Lorem Ipsum is simply</h4>
                                <p>orem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, </p>
                            </div>
                            <div class="features-icon d-inline-flex align-items-center justify-content-center ml-md-4">
                                <img src="assets/img/ball.png" class="" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 offset-md-5">
                        <div class="features-box d-inline-flex w-100 position-relative">
                            <div class="features-icon d-inline-flex align-items-center justify-content-center mr-md-4">
                                <img src="assets/img/throphy.png" class="" alt="">
                            </div>
                            <div class="features-text position-relative">
                                <h4 class="theme-text">Lorem Ipsum is simply</h4>
                                <p>orem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="bg_dark">

    </section>
</div>
<?php
include('footerHome.php');
?>

