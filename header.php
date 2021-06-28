<!DOCTYPE html>
<html data-ng-app="FandomRoyale" ng-cloak >
<head>
    <?php require('MetaData.php');?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" >
    <meta name="description" content="Unique Pro and College fantasy football leagues drafting entire teams/schools to your fantasy squad. FREE and paid/prize contests available!">
    <meta name="keywords" content="College Fantasy Football, College Fantasy Football League, Pro Fantasy Football League, College Football Contest, Pro Football Contests, College Fantasy Sports">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16x16.png">
    <link href="assets/css/slick.css" rel="stylesheet">
    <link href="assets/css/slick-theme.css" rel="stylesheet">
    <link href="assets/css/custom.css?version=<?= $VERSION ?>" rel="stylesheet">
    <link href="assets/css/responsive.css?version=<?= $VERSION ?>" rel="stylesheet">
    <?php if($PathName != 'createLeague'){ ?>
    <link rel="stylesheet" href="draft/node_modules/angularjs-bootstrap-datetimepicker/src/css/datetimepicker.css"/>
    <?php }else{ ?>
    <link rel="stylesheet" href="assets/bootstrap-datetime-picker/bootstrap-datetimepicker.min.css">
    <?php } ?>
    <script src="draft/node_modules/socket.io-client/dist/socket.io.js"></script>
</head>
<body ng-controller="MainController" ng-cloak >
    <nav class="navbar navbar-expand-md  navbar-light site_header fixed-top {{!isLoggedIn ? 'withoutLogin' : '' }}" id="header" ng-controller="headerController" ng-cloak >
        <div class="container-fluid d-block">
            <div class="row align-items-center resposnive-menu-f">
                <div class="col-4 col-md-2 order-1">
                    <a class="navbar-brand" href="{{(isLoggedIn)?'lobby':'index'}}"><img src="assets/img/logo.png" class="img-fluid" alt="logo"></a>
                </div>
                <div class="col-md-7 col-md order-3 order-md-2">
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav  primary_menu" ng-if="isLoggedIn">
                             <li class="nav-item">
                                <a class="nav-link {{(headerActiveMenu == 'index' && page =='') ? 'active' : '' }} " href="index" >Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{headerActiveMenu == 'lobby' ? 'active' : '' }}" href="lobby"> Lobby  </a>
                            </li>
                            <li class="nav-item" ng-if="GamesType == 'NFL'">
                                <a class="nav-link {{headerActiveMenu == 'myContest' ? 'active' : '' }} " href="myContest"  >My Contests</a>
                            </li>
                            <li class="nav-item" ng-if="GamesType == 'NBA'">
                                <a class="nav-link {{headerActiveMenu == 'MyJoinedMatches' ? 'active' : '' }} " href="MyJoinedMatches"  >My Contests</a>
                            </li>
                            <li class="nav-item" ng-if="GamesType == 'NBA' || GamesType == 'NFL'">
                                <a class="nav-link {{(headerActiveMenu == 'contestStanding')?'active' : '' }}" href="contestStanding">Contest Standings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(headerActiveMenu == 'myPrivateLeague')?'active' : '' }}"  href="myPrivateLeague">Private Leagues</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(headerActiveMenu == 'pointSystem')?'active' : '' }}" href="pointSystem">Scoring</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(headerActiveMenu == 'Rules&info')?'active' : '' }}" href="Rules&info">Rules & Info</a>
                            </li>
                            <!-- <li class="nav-item ">
                                <select ng-model="GamesType" name="GamesType" ng-change="gameTypeSelection(GamesType)" class="custom-select">
                                    <option value="NFL">NFL</option>
                                    <option value="NBA">NBA</option>
                                </select>
                            </li> -->
                        </ul>
                        <ul class="navbar-nav  ng-scope" ng-if="!isLoggedIn">
                            <li class="nav-item"><a class="nav-link" href="index">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="AboutUs">About Us</a></li>
                            <li class="nav-item"><a class="nav-link" href="contactUs">Contact Us</a></li>
                            <li class="nav-item"><a class="nav-link" href="pointSystem">Scoring Rules</a></li>
                            <li class="nav-item"><a class="nav-link" href="Rules&info">Rules & Info</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-8 col-md-3 order-2 order-md-3 d-flex align-items-center justify-content-end pl-0" >
                    <ul class="navbar-nav header_login_menu ng-scope" ng-if="!isLoggedIn">
                        <li class="nav-item"><a class="nav-link" href="login">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="signup">Sign Up</a></li>
                    </ul>
                    <ul class="navbar-nav header_profile_menu" ng-if="isLoggedIn">
                        <li class="nav-item dropdown  notification" ng-init="getNotifications()">
                            <a href="#" id="menuDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="count">{{notificationCount}}</span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="menuDropdown">
                                <h6>Notifications Center</h6>
                                <ul class="list-unstyled custom_scroll">
                                    <li ng-repeat="notifications in notificationList">
                                        <div>
                                            <h6>{{notifications.NotificationText}} <a href="javascript:void(0)" ng-click="deleteNotification(notifications.NotificationID)" class="float-right themeClr"><i class="fas fa-trash"></i></a></h6>
                                            <p>{{notifications.NotificationMessage}}</p>
                                        </div>
                                        <time>{{notifications.EntryDate| myDateFormat}}</time>
                                    </li>
                                    <li ng-if="notificationList.length == 0"> No unread notification. </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item dropdown header_menu_dropdown_wrapr">
                            <a href="#" id="menuDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="">
                                <img class="user_profile" ng-src="{{profileDetails.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="">
                                <span class="user_name font-weight-500" style="text-transform: capitalize;">{{profileDetails.FullName}}</span>
                           </a>
                           <div class="dropdown-menu" aria-labelledby="menuDropdown">
                                <div class="dropdown_btns border-bottom">
                                    <a href="javascript:void(0)"class="border-right"  ng-click="openPopup('add_money');"> <i class="fa fa-money" aria-hidden="true"></i> Add Cash </a>
                                    <a href="javascript:void(0)" ng-click="openPopup('withdrawPopup')"><i class="fa fa-credit-card" aria-hidden="true"></i> Withdraw </a>
                                </div>
                                <ul class="list-unstyled" >
                                    <li><a href="profile"> <i class="fa fa-fw fa-user"></i> Profile</a></li>
                                    <li><a href="myAccount"><i class="fa fa-user-circle"></i> My Account</a></li>
                                    <li><a href="verifyAccount"><i class="fa fa-fw fa-gear"></i> Verify Accounts</a></li>
                                    <li><a href="changePassword"><i class="fa fa-user-circle"></i> Change Password</a></li>
                                    <li><a href="javascript:void(0)" ng-click="logout()"><i class="fa fa-fw fa-power-off"></i> Log Out</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar top"></span>
                        <span class="icon-bar mid"></span>
                        <span class="icon-bar btm"></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
<main class="main-content">

