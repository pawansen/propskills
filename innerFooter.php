</main>
<!--Footer sec start-->
<footer class="pb-3 site-footer bg_secondary">
  <div class="container">
    <div class="row pt-5 pb-4">
        <div class="col-lg-6 col-md-7">
            <div class="row">
                <div class="col-sm-4">
                    <h6>quick links</h6>
                    <ul class="footer_menu list-unstyled ">
                        <li><a href="AboutUs">About Propskills </a></li>
                        <li><a href="contactUs">Contact Us</a></li>
                        <li><a href="Rules&info" >Rules & Info </a></li>
                    </ul>
                </div>
                <div class="col-sm-4">
                    <h6>Support</h6>
                    <ul class="footer_menu list-unstyled">
                        <li><a href="RefundPolicy" >Refund Policy </a></li>
                        <li><a href="TermsAndConditions">Terms & Condition</a></li>
                        <li><a href="privacyPolicy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-sm-4">
                    <h6>News & Resources</h6>
                    <ul class="footer_menu list-unstyled">
                        <li><a href="javascript:;">ESPN</a></li>
                        <li><a href="javascript:;">NFL</a></li>
                        <li><a href="javascript:;">NBA</a></li>
                        <li><a href="javascript:;">MLB</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4 offset-lg-2 col-md-5">
            <h6>Stay In Touch With US</h6>
            <div class="social_menu ">
                <a href="javascript:;"><i class="fab fa-facebook-f"></i></a>
                <a href="javascript:;"><i class="fab fa-twitter"></i></a>
                <a href="javascript:;"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="btm_footer text-center">
        <span class="copyright">Copyright © {{Date | date:'y'}}  All Rights Reserved. </span>
    </div>
  </div>
</footer>
<div loading class="loader_wrapr" id="loderBG">
    <div class="pre_loader"><span></span><span></span></div>
</div>

<add-cash></add-cash>
<add-more-cash></add-more-cash>
<add-withdrawal-request></add-withdrawal-request>
<!--Footer sec end-->
<script src="assets/js/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/6.26.0/polyfill.min.js"></script>

<script src="assets/js/slick.min.js"></script>
<script src="assets/js/jquery.hover-slider.js"></script>
<script src="assets/js/wow.min.js"></script>
<!-- load angular -->
<script src="assets/js/angular-modules/angular.min.js"></script>
<!-- angular storage -->
<script src="assets/js/angular-modules/ngStorage.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular-cookies.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-sanitize/1.5.7/angular-sanitize.min.js"></script>
<!-- MAIN CONTROLLER -->
<script src="assets/js/app.js?version=<?= $VERSION ?>"></script>
<script src="assets/js/custom.js?version=<?= $VERSION ?>"></script>
<script type="text/javascript">
    var base_url = "<?php echo $base_url;?>";
    var UserGUID, UserTypeID, ParentCategoryGUID = '';
    app.constant('environment', {
        base_url: "<?php echo $base_url;?>",
        api_url: "<?php echo $api_url;?>",
        image_base_url: '<?php echo $base_url; ?>assets/img/',
        brand_name: 'fandomroyale'
    });
    app.config(function(socialProvider){
        // socialProvider.setGoogleKey("760247147566-mt9iqjkc4jbjiidd9oj48s7bku07lmt3.apps.googleusercontent.com");
        // socialProvider.setFbKey({appId: "225761894715344", apiVersion: "v2.11"});
    });
</script>
<!-- common service -->
<script src="assets/js/services/database.fac.js?version=<?= $VERSION ?>"></script>
<!-- common directive -->
<script src="assets/js/directive/design-directive.lib.js?version=<?= $VERSION ?>"></script>
<!-- helper -->
<script src="assets/js/helper/helper.js?version=<?= $VERSION ?>"></script>
<!-- social ligin library -->
<script src="assets/js/angularjs-social-login/angularjs-social-login.js"></script>
<!-- file upload -->
<script src="assets/js/jquery.form.js"></script>
<!-- Angular animate js -->
<script src="assets/js/angular-animate.min.js?version=<?= $VERSION ?>"></script>
<!-- header controller -->
<script src="assets/js/controllers/header.js?version=<?= $VERSION ?>"></script>
<?php if($PathName == '' || $PathName == 'index'){ ?>
<!-- home controller -->
<script src="assets/js/controllers/home.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'lobby'){ ?>
<!-- lobby controller -->
<script src="assets/js/controllers/lobby.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'myAccount' || $PathName == 'verifyAccount'){ ?>
<!-- my account controller -->
<script src="assets/js/controllers/account.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'profile' || $PathName == 'changePassword'){ ?>
<!-- profile controller -->
<script src="assets/js/controllers/profile.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'draftTeam'){ ?>
<!-- Create snake Team Controller -->
<script src="assets/js/controllers/draftTeam.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'myContest' || $PathName == 'myPrivateLeague'){ ?>
<!-- My league Controller -->
<script src="assets/js/controllers/myLeague.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'pointsLeaderboard'){ ?>
<!-- Point Leaderboard Controller -->
<script src="assets/js/controllers/pointLeaderboard.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'contestInfo'){ ?>
<!-- Lineup Controller -->
<script src="assets/js/controllers/lineup.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'Scoreboard'){ ?>
<!-- Scoreboard Controller -->
<script src="assets/js/controllers/scoreboard.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'createLeague'){ ?>
<!-- create league controller -->
<script src="assets/js/controllers/createLeague.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'pointSystem'){ ?>
<!-- pointSystem controller -->
<script src="assets/js/controllers/pointSystem.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'inviteFriends'){ ?>
<!-- invite friend controller -->
<script src="assets/js/controllers/inviteFriend.js?version=<?= $VERSION ?>"></script>
<?php }else if($PathName == 'MyJoinedMatches' || $PathName == 'showContest'){ ?>
<!-- Joined Matches controller -->
<script src="assets/js/controllers/MyJoinedMatches.js?version=<?= $VERSION ?>"></script>
<?php } if($PathName == 'draftRoom'){ ?>
<!-- NBA draft Room controller -->
<script src="assets/js/controllers/draftRoom.js?version=<?= $VERSION ?>"></script>
<?php } if($PathName == 'gameInfo'){ ?>
<!-- NBA Game Info controller -->
<script src="assets/js/controllers/gameInfo.js?version=<?= $VERSION ?>"></script>
<?php } if($PathName == 'liveScoreboard'){ ?>
<!-- NBA Game Info controller -->
<script src="assets/js/controllers/liveScoreboard.js?version=<?= $VERSION ?>"></script>
<?php } if($PathName == 'contestStanding'){ ?>
<!-- NBA contest standing controller -->
<script src="assets/js/controllers/contestStanding.js?version=<?= $VERSION ?>"></script>
<?php } ?>

<script src="assets/js/angular-modules/ng-file-upload-master/dist/ng-file-upload.min.js"></script>
<script src="assets/js/angular-modules/ng-file-upload-master/dist/ng-file-upload-shim.min.js"></script>
<link rel="stylesheet" href="assets/bootstrap-datetime-picker/bootstrap-datetimepicker.min.css">
<script src="assets/bootstrap-datetime-picker/bootstrap-datetimepicker.min.js" charset="UTF-8"></script>
<script src="assets/js/sweetalert.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.js"></script>
<script type="text/javascript" src="draft/node_modules/angularjs-bootstrap-datetimepicker/src/js/datetimepicker.js"></script>
<script type="text/javascript" src="draft/node_modules/angularjs-bootstrap-datetimepicker/src/js/datetimepicker.templates.js"></script>
<!-- Load the required checkout.js script -->
<script src="https://www.paypalobjects.com/api/checkout.js" data-version-4></script>

<!-- Load the required Braintree components. -->
<script src="https://js.braintreegateway.com/web/3.39.0/js/client.min.js"></script>
<script src="https://js.braintreegateway.com/web/3.39.0/js/paypal-checkout.min.js"></script>

<!-- Enter draft popup -->
<div class="modal fade site_modal modal_dark" id="EnterDraftModal" popup-handler role="dialog" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <div class="modal-header">
                <div class="row w-100">
                    <div class="col-md-8 ">
                        <p class="text-capicalize">{{Info.ContestName}} <strong class="themeClr">({{Info.Privacy == 'Yes'?'Private League':'Public Contest'}})</strong></p>
                        <div class="row">
                            <div class="col">
                                <div>PRIZE</div>
                                <span><strong>{{moneyFormat(Info.WinningAmount)}}</strong> <span>Top {{Info.NoOfWinners}}</span></span>
                            </div>
                            <div class="col">
                                <div>ENTRY FEE</div>
                                <span><strong>{{moneyFormat(Info.EntryFee)}}</strong> </span>
                            </div>
                            <div class="col">
                                <div>ENTRIES</div>
                                <span><strong>{{Info.TotalJoined}}/{{Info.ContestSize}}</strong> </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-left ">
                        <div class="media">
                            <img src="assets/img/stopwatch.svg" alt="timer" class="mr-2" width="30px">
                            <div class="media-body">
                            <span>Draft Start In</span> 
                                <p class="timer" timer-text="{{Info.LeagueJoinDateTime}}" timer-data="{{Info.LeagueJoinDateTime}}" match-status="{{Info.Status}}" ng-bind-html="clock | trustAsHtml" ></p>          
                                <small>{{Info.LeagueJoinDateTime | myDateFormat}}</small>
                            </div>   
                        </div>
                    </div>
                </div>
            </div>
            <ul class="nav nav-pills border_tabs dark_tab mt-5 bg_primary" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="{{activeDraftTab == 'pills-1'?'active':''}}" id="pills-1-tab" data-toggle="pill" href="javascript:void(0)" ng-click="draftTab('pills-1')" role="tab" aria-controls="pills-1" aria-selected="true">Rules</a>
                </li>
                <li class="nav-item">
                    <a class="{{activeDraftTab == 'pills-3'?'active':''}}" id="pills-3-tab" data-toggle="pill" href="javascript:void(0)" ng-click="draftTab('pills-3')" role="tab" aria-controls="pills-3" aria-selected="false" class="">ENTRIES</a>
                </li>
                <li class="nav-item">
                    <a class="{{activeDraftTab == 'pills-2'?'active':''}}" id="pills-2-tab" data-toggle="pill" href="javascript:void(0)" ng-click="draftTab('pills-2')" role="tab" aria-controls="pills-2" aria-selected="false" class="">PRIZES</a>
                </li>
                <li class="nav-item">
                    <a class="{{activeDraftTab == 'pills-4'?'active':''}}" id="pills-4-tab" data-toggle="pill" href="javascript:void(0)" ng-click="draftTab('pills-4')" role="tab" aria-controls="pills-4" aria-selected="false" class="">SCORING</a>
                </li>
            </ul>
            <div class="modal-body">
                <div class="tab-content px-md-4 text-white" id="pills-tabContent">
                    <div class="tab-pane fade {{activeDraftTab == 'pills-1'?'active show':''}}" id="pills-1" role="tabpanel" aria-labelledby="pills-1-tab">
                        <table class="table dark_table table_scroll">
                            <thead>
                                <tr>
                                    <th>League Type</th>
                                    <th>Total Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Draft Type</td>
                                    <td>SNAKE DRAFT</td>
                                </tr>
                                <tr>
                                    <td>Draft Date</td>
                                    <td>{{Info.newDate | date :'MMM dd, y'}}</td>
                                </tr>
                                <tr>
                                    <td>Draft Time</td>
                                    <td>{{Info.newDate  | date :'h:mm a'}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade {{activeDraftTab == 'pills-2'?'active show':''}}" id="pills-2" role="tabpanel" aria-labelledby="pills-2-tab">
                        <div class="row">
                            <div class="col-md-10 offset-md-1">
                                <ul class="list-unstyled prizes_list d-flex mt-4">
                                    <li class="flex-fill" ng-repeat="winner in Info.CustomizeWinning">
                                        <span class="bg-danger" ng-if="winner.From == 1">
                                            <img src="assets/img/yellow-white.svg" alt="prize">
                                        </span>
                                        <span class="bg-success" ng-if="winner.From == 2">
                                            <img src="assets/img/yellow-white.svg" alt="prize">
                                        </span>
                                        <span class="bg-light" ng-if="winner.From > 2 ">
                                            <img src="assets/img/yellow-dark.svg" alt="prize">
                                        </span>
                                        <small ng-if="winner.From == winner.To">{{winner.From | RankFormat}}</small>
                                        <small ng-if="winner.From != winner.To">{{winner.From | RankFormat}} - {{winner.To | RankFormat}}</small>
                                        {{moneyFormat(winner.WinningAmount)}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{activeDraftTab == 'pills-3'?'active show':''}}" id="pills-3" role="tabpanel" aria-labelledby="pills-3-tab">
                        <div class="row">
                            <div class="col-md-10 offset-md-1">
                                <ul class="entries_list list-unstyled">
                                    <li ng-repeat="user in ContestUserList">
                                        <img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="img">
                                        {{user.UserTeamCode}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{activeDraftTab == 'pills-4'?'active show':''}}" id="pills-4" role="tabpanel" aria-labelledby="pills-4-tab">
                        <table class="table dark_table enterDraftScoringTable table_scroll">
                            <thead>
                                <tr>
                                    <th>Point Type</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="point in Points">
                                    <td>{{point.PointsTypeDescprition}}</td>
                                    <td>{{point.Points}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="button" ng-click='enterDraft(Info)' class="btn_primary"> ENTER</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Show Payouts break ups -->
<div class="modal fade  site_sm_modal " popup-handler id="PayoutBreakUp" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
    <div class="modal-dialog "> 
        <!-- Modal content-->
        <div class="modal-content ">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h5 class="modal-title">Prize(s) Breakdown</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class=" text-center " style="width: 100%">
                        <table class="table comman_table">
                            <thead>
                                <tr>
                                    <th>Rank </th>
                                    <th>Winning Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="winnings in CustomizeWinning" >
                                    <td ng-if="winnings.From == winnings.To">{{winnings.From | RankFormat}}</td>
                                    <td ng-if="winnings.From != winnings.To">{{winnings.From | RankFormat}} - {{winnings.To | RankFormat}}</td>
                                    <td>{{moneyFormat(winnings.WinningAmount)}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>

</html>


