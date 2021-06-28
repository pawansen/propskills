<?php include('header.php'); ?>
<section class="burger common_bg createLeaguePage" ng-controller="createLeagueController" ng-init="getSeriesList();getPoints();getCurrentWeek();" ng-cloak>
    <div class="container">
        <div class="shadow_box px-3 px-lg-0">
            <div class="top_bar">
                <h6 class="crtlg">Create League</h6>
                <a href="javascript:void(0)" class="themeClr" data-toggle="modal" ng-click="getRoaterInfo()" data-target="#rosterPopup"><strong>Roster</strong></a>
            </div>
            <div class="createLeague-Details">
                <div class="alert alert-danger" ><strong class="h6">Note :</strong> Private leagues are currently only available for <strong class="themeClr h5">NFL</strong>.</div>
                <div class="alert alert-success" ><strong class="h6">Note :</strong> To create private league, 1 Week league fee will be <strong class="themeClr h5">{{moneyFormat(profileDetails.PrivateContestFeeWeek)}}</strong>, Season Long fee will be <strong class="themeClr h5">{{moneyFormat(profileDetails.PrivateContestFeeSeasonLong)}}</strong> (plus {{profileDetails.PrivateContestFeePercentage}}% of Total Prize).</div>
                <div class="basicInfo-scoring col-md-4 offset-md-4">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{tab == 'info'?'active':''}}" id="info-tab" data-toggle="tab" href="javascript:void(0)" ng-click="activeTab('info')" role="tab" aria-controls="info" aria-selected="true">1</a>Basic Info
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{tab == 'scoring'?'active':''}}" id="scoring-tab" data-toggle="tab" href="javascript:void(0)" ng-click="activeTab('scoring')" role="tab" aria-controls="scoring" aria-selected="false">2</a>Scoring
                        </li>
                    </ul>
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade {{tab == 'info'?'show active':''}}" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <form name="createContestForm" ng-submit="CheckCreateContestForm(createContestForm)" novalidate="" autocomplete="off">
                            <div class="guter basic-spacing">
                                <div class="row mt-5">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Duration <small class="themeClr">(SeasonLong goes until conf.championship)</small></label>
                                            <select name="Duration" ng-model="Duration" class="custom-select" ng-required="true">
                                                <option value="Weekly">1 Week</option>
                                                <option value="SeasonLong">Season Long</option>
                                            </select>
                                            <div  ng-show="submitted && createContestForm.Duration.$error.required" class="text-danger form-error">
                                                *Duration type is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Sport</label>
                                            <select name="SeriesGUID" ng-model="SeriesGUID" class="custom-select" ng-required="true">
                                                <option ng-repeat="series in SeriesList" value="{{series.SeriesGUID}}">{{series.SeriesName}}</option>
                                            </select>
                                            <div  ng-show="submitted && createContestForm.SeriesGUID.$error.required" class="text-danger form-error">
                                                *Sport is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Scoring Type</label>
                                            <select name="ScoringType" ng-model="ScoringType" ng-required="true" class="custom-select">
                                                <option value="PointLeague">Total Points</option>
                                          <!--       <option value="H2H" ng-show="Duration == 'SeasonLong'">Head 2 Head</option> -->
                                            </select>
                                            <div  ng-show="submitted && createContestForm.ScoringType.$error.required" class="text-danger form-error">
                                                *Scoring Type is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Week <small class="themeClr">(for SeasonLong this is the starting week)</small></label>
                                            <select name="Week" ng-model="Week" ng-required="true" class="custom-select" >
                                                <option value="">Select Week</option>
                                                <option ng-repeat="week in WeekList" ng-if="week >= currentWeek" value="{{week}}">Week {{week}}</option>
                                            </select>
                                            <div  ng-show="submitted && (createContestForm.Week.$error.required || !createContestForm.Week.$valid)" class="text-danger form-error">
                                                *Week is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Visibility</label>
                                            <select name="Privacy" ng-model="Privacy" ng-required="true" class="custom-select" ng-change="WeekEnd = ''">
                                                <option value="Yes">Private</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Draft Type</label>
                                            <select name="LeagueType" ng-model="LeagueType" ng-required="true" class="custom-select">
                                                <option value="Draft">Standard Snake Draft</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group leagueName icon">
                                            <div class="icons"><i class="fa fa-bars"></i></div>
                                            <label for="">League Name</label>
                                            <input type="text" name="ContestName"  placeholder="League Name" ng-model="ContestName" ng-required="true" class="form-control">
                                            <div  ng-show="submitted && (createContestForm.ContestName.$error.required || !createContestForm.ContestName.$valid)" class="text-danger form-error">
                                                *League name is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group icon">
                                            <div class="icons"><i class="fa fa-lock"></i></div>
                                            <label for="">Invite Permission</label>
                                            <select name="InvitePermission" ng-model="InvitePermission" ng-required="true" class="custom-select">
                                                <option value="ByCreator">By Creator</option>
                                                <option value="ByAnyone">By Anyone who joins</option>
                                            </select>
                                            <div ng-show="submitted && createContestForm.InvitePermission.$error.required" class="text-danger form-error">
                                                *Invite permission is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Draft Date & Time (EST)</label>
                                            <!-- <div class="row">
                                                <div class="col"> -->
                                                    <!-- <div class="date_input"> -->
                                                            <input type="text" name="LeagueJoinDateTime"  id="draftDateTime" ng-model="LeagueJoinDateTime" readonly class=" form-control" ng-required="true">
                                                        <!-- <div class="dropdown  dropdown-start-parent">
                                                            <span  class="dateTime_field">
                                                            <input type="text" name="LeagueJoinDateTime" id="dropdownStart"  ng-required="true" placeholder="Draft Date & Time" role="button" data-toggle="dropdown" data-target=".dropdown-start-parent" class="form-control" value="{{LeagueJoinDateTime | date:'yyyy-MM-dd h:mm a'}}" readonly></span>
                                                            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                                                <datetimepicker data-ng-model="LeagueJoinDateTime" data-datetimepicker-config="{ dropdownSelector: '#dropdownStart', renderOn: 'end-date-changed' }" data-on-set-time="startDateOnSetTime()" data-before-render="startDateBeforeRender($dates)"></datetimepicker>
                                                            </ul>
                                                        </div> -->
                                                         <div  ng-show="submitted && (LeagueJoinDateTime== '' || LeagueJoinDateTime == undefined)" class="text-danger form-error">
                                                            *Draft Date Time is required.
                                                        </div>
                                                    <!-- </div> -->
                                                <!-- </div>
                                            </div> -->
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group icon">
                                            <div class="icons"><i class="fa fa-dollar-sign"></i></div>
                                            <label for="">Entry Fee <small class="themeClr">(a fee above 0 will require league to fill)</small></label>
                                            <input type="text" name="EntryFee" placeholder="Entry Fee" ng-model="EntryFee" ng-required="true" numbers-only class="form-control">
                                            <div  ng-show="submitted && (createContestForm.EntryFee.$error.required || !createContestForm.EntryFee.$valid)" class="text-danger form-error">
                                                *Entry Fee is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Roster Size</label>
                                            <select name="RosterSize" ng-model="RosterSize" ng-required="true" class="custom-select">
                                                <option value="6">6</option>
                                                <option value="8">8</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Maximum Teams</label>
                                            <select name="ContestSize" ng-model="ContestSize" ng-required="true" class="custom-select">
                                                <option ng-repeat="size in ContestSizeList" value="{{size}}">{{size}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="">Minimum Teams Required <small class="themeClr">(only if entry fee is 0)</small></label>
                                            <input type="text" name="MinimumUserJoined" placeholder="Minimum Teams Required" ng-model="MinimumUserJoined" readonly class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="prize">
                                            <div class="positon">
                                                <p>Total Prize</p>
                                                <p><span ng-show="EntryFee > 0">{{moneyFormat(WinningAmount)}}</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group ">
                                            <div class="customCheckbox">
                                                <input id="styled-checkbox3" type="checkbox" class="styled-checkbox" ng-model="IsAutoDraft">
                                                <label for="styled-checkbox3">Enable Auto Drafting
                                            </div>   
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mt-4" ng-show="EntryFee > 0">
                                        <div class="form-group ">
                                            <div class="customCheckbox">
                                                <input id="styled-checkbox1" type="checkbox" class="styled-checkbox" ng-model="winnings">
                                                <label for="styled-checkbox1">Winnings Breakups
                                            </div>   
                                        </div>
                                    </div>
                                    <div class="col-12" ng-show="winnings">
                                        <div class="custom_scroll1">
                                            <div class="distribution" ng-repeat="winner in choices | orderBy:'NoOfWinners'" >
                                                <input type="radio" class="mt-1" name="select_winnings" ng-model="$parent.SelectedWinners" ng-value="winner.NoOfWinners">
                                                <div class="positon ml-2" ng-repeat="win in winner.Winners">
                                                    <p>{{win.Rank | RankFormat}}</p>
                                                    <p><span>{{moneyFormat(win.WinningAmount)}}</span></p>
                                                    <p>{{win.Percent}}%</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="totalPrize">
                                    <p>Total Prize <span ng-show="EntryFee > 0">{{moneyFormat(WinningAmount)}}</span></p>
                                </div>
                                <div class="">
                                    <div class="form-group">
                                        <input class="styled-checkbox" ng-required="true" name="agree" ng-model="agree" id="styled-checkbox" type="checkbox">
                                        <label for="styled-checkbox"> I agree to the Fandom Royale <a href="TermsAndConditions" target="_blank" class="themeClr ml-2">Terms of Use</a></label>
                                        <div class="form-error text-danger" ng-show="submitted && createContestForm.agree.$error.required">*You must need to agree with condition.</div>
                                    </div>
                                    <div class="text-right">
                                        <button type="button" ng-click="cancelButton()" class="btn_secondary cursor_pointer">CANCEL</button>
                                        <button type="submit" class="btn_trans_dark cursor_pointer">CONTINUE</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade {{tab == 'scoring'?'show active':''}}" id="scoring" role="tabpanel" aria-labelledby="scoring-tab">
                        <div class="guter">
                            <div class="row mt-5">
                                <div class="col-sm-12">
                                    <div class="playerscroll-y custom_scroll">
                                        <h6>OFFENSIVE</h6>
                                        <div class="form-group" ng-repeat="point in Points" ng-if="point.PointsTypeShortDescription == 'OFFENSIVE'">
                                            <div class="boxGrplayer">
                                                <div><p>{{point.PointsTypeDescprition}}</p><input type="number" class="form-control" style="width: 10%;" name={{point.PointsTypeGUID}} ng-model="point.Points"><b>Points</b></div>
                                            </div>
                                        </div>
                                        <h6>KICKING</h6>
                                        <div class="form-group" ng-repeat="point in Points" ng-if="point.PointsTypeShortDescription == 'KICKING'">
                                            <div class="boxGrplayer">
                                                <div><p>{{point.PointsTypeDescprition}}</p><input type="number" class="form-control" style="width: 10%;" name={{point.PointsTypeGUID}} ng-model="point.Points"><b>Points</b></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-3">
                                <button type="button" ng-click="cancelButton()" class="btn_secondary cursor_pointer">CANCEL</button>
                                <button type="button" ng-click="CreateContest()" class="btn_trans_dark cursor_pointer">Create</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade rosterPopup site_modal modal_dark" popup-handler id="rosterPopup" tabindex="-1" role="dialog" >
        <div class="modal-dialog custom_popup modal-lg"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h6 class="modal-title w-100">ROSTER</h6>
                </div>
                <div class="modal-body clearfix comon_body ammount_popup">
                    <div class="top-head">
                        <p>ROSTER</p>
                    </div>
                    <div class="form-group mb-0">
                        <ul>
                            <li ng-repeat="ros in rosterInfo">
                                <span>{{ros.FullName}} <span class="themeClr">
                                ({{ros.ShortName}})</span></span>
                                <span>{{ros.Player}}</span>
                            </li>
                        </ul>
                    </div>                        
                </div>
            </div>
        </div>
    </div> 
    <div class="modal fade  site_sm_modal" data-backdrop="static"  popup-handler id="joinLeaguePopup" tabindex="-1" role="dialog" >
        <div class="modal-dialog "> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4 class="modal-title w-100">Join League</h4>
                </div>
                <div class="modal-body clearfix comon_body ammount_popup">
                    <div class="form-group mb-0">
                        <ul class="p-0 mb-0">
                            <li class="clearfix">
                                <div class="float-left"><h6>Total Wallet Amount </h6></div>
                                <div class="float-right"><p class="ng-binding mb-0"> {{moneyFormat(profileDetails.TotalCash)}}</p></div>
                            </li>
                        </ul>
                    </div>
                    <hr>    
                    <div class="form-group mb-0">
                        <ul class="p-0 mb-0">
                            <li class="clearfix">
                                <div class="float-left"><h6>Joining Amount </h6></div>
                                <div class="float-right"><p class="ng-binding mb-0"> {{moneyFormat(ContestInfo.EntryFee)}}</p></div>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 text-center">
                            <button type="button" class="btn btn-submit btn_sm_border mt-3 btn-green text-white" style="border-radius: 5px;" ng-click="JoinContest()" >Join</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</section>
<?php include('innerFooter.php'); ?>    
<style>
.switch{
    display: table-cell !important;
}
</style>