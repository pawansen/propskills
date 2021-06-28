<?php include('header.php'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css" type="text/css" media="all" />
<!--Main container sec start-->
<div class="{{GamesType == 'NBA'?'common_bg1':'common_bg'}} common-bg-n matchcenterDetail">
    <div ng-controller="lobbyController" ng-cloak>
        <section class="b-burger">
            <div class="container-fluid ">
                <div class="mt-2 mb-2 silder_show" ng-if="GamesType == 'NBA'">
                    <div class="wrapper">
                        <div class="slider1 lobby_page_slider" slick-custom-carousel ng-if="silder_visible" >
                            <div class="" ng-repeat="matches in MatchesList.Records" ng-if="MatchesList.Records.length > 0" >
                                <a href="javascript:void(0);" ng-click="selectMatch(matches)">
                                    <div class="slider_item {{MatchGUID == matches.MatchGUID ? 'active' : '' }}">
                                        <h4> {{matches.SeriesName}} </h4>
                                        <div class="d_flex">
                                            <figure class="mb-0">
                                                <span><img ng-src="{{(matches.TeamFlagLocal)?matches.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{matches.TeamNameShortLocal}}" class="img-fluid" width="60" /></span>
                                                <small>{{matches.TeamNameShortLocal}}</small> 
                                            </figure>
                                            <div class="timer">
                                                <div class="teamVs">Vs</div>
                                                <p id="demo" timer-text="{{matches.MatchStartDateTimeUTC}}" timer-data="{{matches.MatchStartDateTimeUTC}}" match-status="{{matches.Status}}" ng-bind-html="clock | trustAsHtml" class="ng-binding"></p>
                                            </div>
                                            <figure class="mb-0"> 
                                                <span><img ng-src="{{(matches.TeamFlagVisitor)?matches.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{matches.TeamNameShortVisitor}}" class="img-fluid" width="60"  /></span>
                                                <small>{{matches.TeamNameShortVisitor}}</small>

                                            </figure>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row ">
                    <div class="col-md-12">
                        <div class="d-flex pull-left lobbySidebar" style="width: 16%;">
                            <select ng-model="GamesType" name="GamesType" ng-change="gameTypeSelection(GamesType)" class="custom-select">
                                <option value="NFL">NFL</option>
                                <option value="NBA">NBA</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-flex-end">
                            <div class="search_form">
                                <input type="text" class="w-100" ng-model="Keyword" placeholder="Search Contest..."><button ng-click="searchContest(Keyword)" type="button" > <i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-2 pr-lg-0">
                        <div class="shadow_box_sm_dark lobbySidebar mb-2">
                            <div class="row">
                                <div class="col-md-6 col-lg-12 form-group" ng-show="GamesType == 'NBA' || GamesType == 'NFL'">
                                    <label for="">Contest Date <span ng-show="GamesType == 'NFL'">(1 day contests only)</span></label>
                                    <div class="dropdown  dropdown-start-parent1">
                                        <span  class="dateTime_field">
                                        <input type="text" id="dropdownStart1" placeholder="Contest Date" readonly role="button" data-toggle="dropdown" data-target=".dropdown-start-parent1" class="form-control" value="{{MatchStartDate | date:'MM-dd-yyyy'}}"></span>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                            <datetimepicker ng-change="getMatches(GamesType)"  data-ng-model="MatchStartDate" data-datetimepicker-config="{ dropdownSelector: '#dropdownStart1',startView:'day', minView:'day' }" ></datetimepicker>
                                        </ul>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6 col-lg-12 form-group" ng-show="GamesType == 'NFL'">
                                    <label for="">Contest Date</label>
                                    <div class="dropdown  dropdown-start-parent1">
                                        <span  class="dateTime_field">
                                        <input type="text" id="dropdownStart1" placeholder="Contest Date" readonly role="button" data-toggle="dropdown" data-target=".dropdown-start-parent1" class="form-control" value="{{contestStartDate | date:'MM-dd-yyyy'}}"></span>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                            <datetimepicker  data-ng-model="contestStartDate" data-datetimepicker-config="{ dropdownSelector: '#dropdownStart1',startView:'day', minView:'day' }" ></datetimepicker>
                                        </ul>
                                    </div>
                                </div> -->
                                <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Draft Date</label>
                                    <div class="dropdown  dropdown-start-parent">
                                        <span  class="dateTime_field">
                                        <input type="text" id="dropdownStart" placeholder="Draft Date" readonly role="button" data-toggle="dropdown" data-target=".dropdown-start-parent" class="form-control" value="{{LeagueJoinDate | date:'MM-dd-yyyy'}}"></span>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                            <datetimepicker data-ng-model="LeagueJoinDate" data-datetimepicker-config="{ dropdownSelector: '#dropdownStart',startView:'day', minView:'day' }" ></datetimepicker>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Draft Time</label>
                                    <select name="DraftTime" ng-model="LeagueJoinTime" class="custom-select ">
                                        <option value="any">Any</option>
                                        <option ng-repeat="time in TimeList" value="{{time.value}}">{{time.name}}</option>
                                        <!-- <option value="6:00 PM">3:00pm PT/6:00pm ET</option>
                                        <option value="11:00 PM">8:00pm PT/11:00pm ET</option> -->
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Participants</label>
                                    <select name="Participants" id="Participants"  ng-model="Participants" class="custom-select " ng-change="setParticipants(Participants)">
                                        <option value="Any">Any</option>
                                        <option ng-repeat="Participant in ParticipantsList" value="{{Participant.name}}">{{Participant.name}}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Contest Length</label>
                                    <select name="ContestDuration" ng-model="ContestDuration" class="custom-select ">
                                            <option value="Any">Any</option>
                                            <option value="Daily">1 Day</option>
                                            <option ng-if="GamesType == 'NFL'"  value="Weekly">1 Week</option>
                                    </select>
                                </div>
                                <!-- <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Participants</label>
                                    <select name="Participants" ng-model="Participants" class="custom-select ">
                                            <option value="Any">Any</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="6">6</option>
                                            <option value="10">10</option>
                                            <option value="16">16</option>
                                    </select>
                                </div> -->
                                <div class="col-md-6 col-lg-12 form-group">
                                    <label for="">Fee $</label>
                                    <div class="price-range-block">
                                        <div id="slider-range" class="price-filter-range" name="rangeInput"></div>
                                            <div class="rangeData">
                                                <span>    
                                                    <input readonly type="number" min=0 max="9900" oninput="validity.valid||(value='0');" id="min_price" class="price-range-field" />
                                                    MIN
                                                </span>
                                                <span>    
                                                    <input readonly type="number" min=0 max="2000" oninput="validity.valid||(value='2000');" id="max_price" class="price-range-field" />
                                                    MAX
                                                </span>
                                            </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="">
                                <div class="gray_btn_group">
                                    <a  type="submit" ng-click="filter()" class=" btn-success mr-2 justify-content-center">Save</a>  
                                    <a type="submit" ng-click="clear_filter()" class=" btn-danger justify-content-center">Reset</a>  
                                </div>
                            </div>
                        </div>  
                    </div>
                    <div class="col-lg-10">
                        <div class="table-responsive">
                            <table class="table  comman_table loby_table tableDArk table_scroll">
                            <thead>
                                <tr>
                                    <th>CONTEST</th>
                                    <th>DRAFT TYPE</th>
                                    <th>BUY IN </th>
                                    <th>PRIZE</th>
                                    <th ng-if="GamesType == 'NFL'">SCORING </th>
                                    <th ng-if="GamesType == 'NBA'">LENGTH</th>
                                    <th>PARTICIPANTS</th>
                                    <th>DRAFT TIME</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody scrolly>
                                <tr ng-if="ContestsTotalCount > 0 && GamesType == 'NFL'" ng-repeat="contest in Contests">
                                    <td class="themeClr"><img src="assets/img/rugby-balls.svg" alt="rugby-balls" width="15" class="mr-2">
                                    {{contest.ContestName}}</td>
                                    <td>{{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br /> ({{(contest.ContestSize == 3)?'8 rounds':'6 rounds'}})</td>
                                    <td ng-if="contest.IsPaid == 'Yes'">
                                        <i class="fas fa-info-circle info_icon mr-1 cursor_pointer" data-toggle="tooltip" data-html="true" title="Rosters: {{contest.RosterSize}}"></i>
                                        {{moneyFormat(contest.EntryFee)}}
                                    </td>
                                    <td ng-if="contest.IsPaid == 'No'">Free</td>
                                    <td>
                                        <div class="payoutParBox">
                                            <a href="javascript:void(0)" ng-click="showWinningPayout(contest.CustomizeWinning)"><cite class="fa fa-eye themeClr" aria-hidden="true"></cite></a>
                                        <span>{{moneyFormat(contest.WinningAmount)}}</span></td>
                                        </div>
                                    </td>
                                    <td>{{(contest.ScoringType == 'PointLeague')?'Total Points':''}}</td>
                                    <td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
                                    <td><i class="fas fa-info-circle info_icon mr-1 cursor_pointer" data-toggle="tooltip" data-html="true" title="Contest Duration : Week {{contest.WeekStart}} - {{contest.WeekEnd}}"></i> {{contest.LeagueJoinDateTime | myDateFormat}} </td>
                                    <td>
                                        <a href="javascript:void(0);" ng-if="contest.IsJoined == 'No' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="check_balance_amount(contest)">Join</a>
                                        <a href="javascript:void(0);" ng-if="contest.IsJoined == 'Yes' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="EnterDraft(contest)">Enter</a>
                                    </td>
                                </tr>	
                                <tr ng-if="ContestsTotalCount > 0 && GamesType == 'NBA'" ng-repeat="contest in Contests">
                                    <td class="themeClr"><img src="assets/img/basketball.png" alt="rugby-balls" width="20" class="mr-2">
                                    {{contest.ContestName}}</td>
                                    <td>{{(contest.GameType == 'Nba')?'Snake Draft':''}} <br /> ({{contest.RosterSize}} Rounds)</td>
                                    <td ng-if="contest.IsPaid == 'Yes'">
                                        {{moneyFormat(contest.EntryFee)}}
                                    </td>
                                    <td ng-if="contest.IsPaid == 'No'">Free</td>
                                    <td>
                                        <div class="payoutParBox">
                                            <a href="javascript:void(0)" ng-click="showWinningPayout(contest.CustomizeWinning)"><cite class="fa fa-eye themeClr" aria-hidden="true"></cite></a>
                                        <span>{{moneyFormat(contest.WinningAmount)}}</span></td>
                                        </div>
                                    </td>
                                    <td>{{(contest.ContestDuration == 'Daily')?'1 Day':'1 Week'}}  <br /> ({{MatchesDetail.MatchStartDateTime | myDateOnlyFormat}})</td>
                                    <td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
                                    <td>{{contest.LeagueJoinDateTime | myDateFormat}} </td>
                                    <td>
                                        <a href="javascript:void(0);" ng-if="contest.IsJoined == 'No' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="check_balance_amount(contest)">Join</a>
                                        <a href="javascript:void(0);" ng-if="contest.IsJoined == 'Yes' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="EnterDraft(contest)">Enter</a>
                                    </td>
                                </tr>	
                                <tr ng-if="ContestsTotalCount == 0">
                                    <td colspan="8" class="empty_table">No Contest Available.</td>
                                </tr>				    
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- confirmToEnter popup -->
        <div class="modal fade confirmToEnter site_modal modal_dark" popup-handler id="confirmToEnter" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
            <div class="modal-dialog modal-md"> 
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">×</button>
                        <h5 class="modal-title">Confirm Contest</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="details">
                                <img src="assets/img/logo-only.png" alt="">
                                <h6>{{ContestInfo.ContestName}}</h6>
                                <p>You are about to enter this Contest, and account will be deducted <span class="themeClr">{{moneyFormat(ContestInfo.EntryFee)}}</span></p>
                                <h6>Are you sure you want to enter ?</h6>
                                
                                <div class="mt-5">
                                    <button type="button" ng-click="closePopup('confirmToEnter')" class=" btn_secondary cursor_pointer mr-2">NO</button>
                                    <button type="button" ng-click="JoinContest()" class="btn_trans_dark cursor_pointer">YES</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>  
<!--Main container sec end-->
<?php include('innerFooter.php'); ?>   
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>
<script>
    $(document).ready(function(){
	$(function () {
	  $("#slider-range").slider({
		range: true,
		orientation: "horizontal",
		min: 0,
		max: 2000,
		values: [0, 2000],
		step: 100,

		slide: function (event, ui) {
		  if (ui.values[0] == ui.values[1]) {
			  return false;
		  }
		  
		  $("#min_price").val(ui.values[0]);
		  $("#max_price").val(ui.values[1]);
		}
	  });

	  $("#min_price").val($("#slider-range").slider("values", 0));
	  $("#max_price").val($("#slider-range").slider("values", 1));

	});
});
</script>