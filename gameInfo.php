<?php include('header.php'); ?>
<section class=" myTeam common_bg1 fr_contest" ng-controller="GameInfoController" ng-init="getContest();matchDetails();" ng-cloak>
    <div class="container-fluid mb-3">
		<div class="creatTeamTop">
			<div class="row">
				<div class="col-lg-2 col-md-2">
					
				</div>
				<div class="col-lg-8 col-md-10 coman_bg_overlay p-4">
					<div class="row justify-content-center align-items-center">
						<div class="col-sm-6 border-right">
							<div class="matchCenterbox ">
								<div class="matchCenterHeader">
									<ul class="d-flex justify-content-center">
										<li>
											<div class="teamLogo">
												<img ng-src="{{(MatchDetails.TeamFlagLocal)?MatchDetails.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{MatchDetails.TeamNameShortLocal}}">
											</div>
										</li>
										<li>{{MatchDetails.TeamNameShortLocal}}
											<span>Vs</span> {{MatchDetails.TeamNameShortVisitor}}</li>
										<li>
											<div class="teamLogo">
												<img ng-src="{{(MatchDetails.TeamFlagVisitor)?MatchDetails.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{MatchDetails.TeamNameShortVisitor}}">
											</div>
										</li>
									</ul>
								</div>
								<div class="matchCenterBody">
									<p>{{MatchDetails.SeriesName}}
										<br> {{MatchDetails.MatchType}} | {{MatchDetails.MatchNo}}</p>
									<!-- <div class="location">
										<i class="fa fa-map-marker"></i> {{MatchDetails.MatchLocation}}
									</div> -->
								</div>
							</div>
						</div>
						<div class="col-sm-6 ">
							<div class="matchCenterbox" ng-if="MatchDetails.Status!='Pending'">
								<div class=" matchFeed">
									<h5 class="mb-2">Match Feed</h5>
									<div class="matchFeedList pl-3">
										<ul class="row">
											<li class="col-7 pr-0 text-left">Match Status</li>
											<li class="col-5 pl-0"><span ng-class="{Pending:'text-danger', Live:'text-success',Cancelled:'text-danger',Completed:'text-success'}[MatchDetails.Status]">{{MatchDetails.Status}}</span></li>
										</ul>
										<ul class="row">
											<li class="col-7 pr-0 d-flex">{{MatchDetails.TeamNameLocal}}</li>
											<li class="col-5 pl-0 numeric " ng-if="MatchDetails.MatchScoreDetails.localteam.total_score">{{MatchDetails.MatchScoreDetails.localteam.total_score}} </li>
										</ul>
										<ul class="row">
											<li class="col-7 pr-0 d-flex">{{MatchDetails.TeamNameVisitor}}</li>
											<li class="col-5 pl-0 numeric" ng-if=" MatchDetails.MatchScoreDetails.visitorteam.total_score">{{MatchDetails.MatchScoreDetails.visitorteam.total_score}} </li>
										</ul>
									</div>
								</div>
							</div>
							<div class="matchCenterbox" ng-if="MatchDetails.Status =='Pending'">
								<div class="coman_bg_overlay">
									<h4>Match Start in</h4>
									<div class="timer" ng-if="MatchDetails.MatchStartDateTimeUTC">
										<p id="demo1" timer-text="{{MatchDetails.MatchStartDateTimeUTC}}" timer-data="{{MatchDetails.MatchStartDateTimeUTC}}" match-status="{{MatchDetails.Status}}" ng-bind-html="clock | trustAsHtml"></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
    
    <div class="container">
        <div class="shadow_box bg_light p-0">
            <ul class="nav site_line_tabs site_line_tabs1 nav-tabs" id="myTab" role="tablist">
                <li class="nav-item " role="presentation">
                    <a class="nav-link {{(activeTab == 'contest')?'active':''}}" id="contest-tab" data-toggle="tab" href="javascript:void(0)" ng-click="selectTab('contest')" role="tab" aria-controls="contest" aria-selected="true">Main</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{(activeTab == 'myteam')?'active':''}}" id="myteam-tab" data-toggle="tab" href="javascript:void(0)" ng-click="selectTab('myteam')" role="tab" aria-controls="myteam" aria-selected="false">My team</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{(activeTab == 'players')?'active':''}}" id="players-tab" data-toggle="tab" href="javascript:void(0)" ng-click="selectTab('players')" role="tab" aria-controls="players" aria-selected="false">All Players</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{(activeTab == 'draft')?'active':''}}" id="draft-tab" data-toggle="tab" href="javascript:void(0)" ng-click="selectTab('draft')" role="tab" aria-controls="draft" aria-selected="false">Draft Results</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{(activeTab == 'trade')?'active':''}}" id="trade-tab" data-toggle="tab" href="javascript:void(0)" ng-click="selectTab('trade')" role="tab" aria-controls="trade" aria-selected="false">Transactions</a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane {{(activeTab == 'contest')?'active':''}}" id="contest" role="tabpanel" aria-labelledby="contest-tab">
                    <div class="tinyBanner">
                        <div class="profileCircleBig">
                            <img ng-src="{{user_details.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="rorjaguar">
                            <div>
                                <h3 class="mb-0">{{Contest.ContestName}}</h3>
                                <a ng-if="Contest.ScoringType == 'PointLeague'" href="liveScoreboard?ContestGUID={{Contest.ContestGUID}}&SeriesGUID={{Contest.SeriesGUID}}&MatchGUID={{MatchGUID}}" class="btn_sm_primary text-white"> View Leaderboard </a> -->
                            </div>
                        </div>
                    </div> 
                    <div class="p-4">
                        <div class="row mt-5">
                            <div class="col-6 col-md">
                                <label for="">PRIZE</label>
                                <h6>{{moneyFormat(Contest.WinningAmount)}} </h6>
                            </div>
                            <div class="col-6 col-md">
                                <label for="">ENTRY FEE</label>
                                <h6 >{{(Contest.IsPaid == 'Yes')?moneyFormat(Contest.EntryFee):'Free'}}</h6>
                            </div>
                            <div class="col-6 col-md">
                                <label for="">ENTRIES</label>
                                <h6>{{Contest.TotalJoined}}/{{Contest.ContestSize}}</h6>
                            </div>
                            <div class="col-6 col-md">
                                <label for="">LENGTH</label>
                                <h6>{{Contest.ContestDuration == 'Daily'?'1 Day':'1 Week'}}</h6>
                            </div>
                            <div class="col-6 col-md">
                                <label for="">CONTEST DATE</label>
                                <h6>{{MatchDetails.MatchStartDateTimeUTC | myDateFormat}}</h6>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="col-md-12">
                                <ul class="list-unstyled prizes_list d-flex mt-4">
                                    <li class="flex-fill" ng-repeat="winner in Contest.CustomizeWinning">
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
                </div>
                <div class="tab-pane {{(activeTab == 'myteam')?'active':''}}" id="myteam" role="tabpanel" aria-labelledby="myteam-tab">
                    <div class="tinyBanner">
                        <div class="profileCircleBig">
                            <img ng-src="{{user_details.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="">
                            <div>
                                <h3 class="mb-0">{{user_details.Username}}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 mt-5">
                         <div class="table-responsive">
                         <table class="table table-hovered table-bordered ">
                            <thead>
                                <tr>
                                    <th>POS</th>
                                    <th>PLAYER</th>
                                    <th>STATUS</th>
                                    <th>OPP</th>
                                    <th>PPG</th>
                                    <th>AST</th>
                                    <th>REB</th>
                                    <th>BLK</th>
                                    <th>STL</th>
                                    <th>TO</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="player in MySquadTeams" ng-if="player.hasOwnProperty('PlayerName')">
                                    <td>
                                        <span class="positionBox">{{player.PlayerSelectTypeRole}}</span>
                                    </td>
                                    <td class="font-weight-500">{{player.PlayerName}}, {{player.TeamNameShort}}</td>    
                                    <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{(player.IsInjuries)?player.IsInjuries:'-'}}</td> 
                                    <td>{{player.PlayerBattingStats.opp}}</td>
                                    <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                    <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                    <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                    <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                    <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                    <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                    <td><span class="removePlayer" ng-click="removePlayer(player)"><i class="fas fa-minus"></i></span></td>
                                </tr>
                                <tr class="addPlayerRow" ng-repeat="player in MySquadTeams" ng-if="!player.hasOwnProperty('PlayerName')">
                                    <td><span class="positionBox DEF">{{player.PlayerSelectTypeRole}}</span></td>
                                    <td colspan="9" class="text-right"> Add Player</td>
                                    <td><span class="addPlayer" ng-click="addPlayer(player)"><i class="fas fa-plus"></i></span></td>
                                </tr>
                            </tbody>
                        </table>  
                         </div> 
                    </div>
                </div>
                <div class="tab-pane {{(activeTab == 'players')?'active':''}}" id="players" role="tabpanel" aria-labelledby="players-tab">
                    <div class="row align-items-center ">
                        <div class="col-md-auto">
                        <div class="alert alert-success mt-3 ml-3" ><strong class="h6">Note :</strong> Players that were dropped by other contestants are not available to be added.</div>
                        
                            <ul class="p-3 nav site_sm_tabs site_line_tabs1" id="myTab" role="tablist">
                                <li class="nav-item " role="presentation">
                                    <a class="nav-link {{ActiveRoleTab == ''?'active':''}}" id="all-tab" data-toggle="tab" ng-click="chnageTab('')" href="javascript:void(0)" role="tab" aria-controls="all" aria-selected="true">all</a>
                                </li>
                                <li class="nav-item" role="presentation" ng-repeat="(key,value) in Contest.DraftPlayerSelectionCriteria">
                                    <a class="nav-link {{ActiveRoleTab == key?'active':''}}" id="{{key}}-tab" data-toggle="tab" ng-click="chnageTab(key)" href="javascript:void(0)"role="tab" aria-controls="{{key}}" aria-selected="false">{{key}}</a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md text-right">
                            <!-- <span class="font-weight-500 pr-3"><img src="assets/img/check-mark.png" alt="show" width="30px" class="img-fluid mr-2"> Show My Team</span> -->
                        </div>
                    </div>
                    <div class="tab-content px-3">
                        <div class="tab-pane active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            <div class="row mb-3 playerHeader align-items-center">
                                <div class="col-md-4">
                                    <div class="search_form w-100">
                                        <input type="text" ng-model="searchPlayer" ng-model-options="{allowInvalid: true, debounce: 200}" placeholder="Search Player" class="form-control">  
                                        <button ng-click="searchContest(Keyword)" type="button" class="text-dark"> <i class="fa fa-search"></i></button>      
                                    </div>
                                </div>
                                <div class="col-md text-md-right pr-md-4 pt-xs-3">
                                    <span class="mr-3"><span class="addPlayer"><i class="fas fa-plus"></i></span> Add Player</span>
                                    <span><span class="removePlayer "><i class="fas fa-minus"></i></span> Drop Player</span>
                                </div>
                            </div>
                            <div class="playerTableWrapr custom_scroll mb-4">
                                <table class="table table-hovered table-bordered table-sm bg-white ">
                                    <thead>
                                        <tr>
                                            <th>POS</th>
                                            <th>PLAYER</th>
                                            <th>STATUS</th>
                                            <th>OPP</th>
                                            <th>PPG</th>
                                            <th>AST</th>
                                            <th>REB</th>
                                            <th>BLK</th>
                                            <th>STL</th>
                                            <th>TO</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody ng-if="ActiveRoleTab == '' || ActiveRoleTab == 'FLEX'">
                                        <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="player.hasOwnProperty('PlayerName')">
                                            <td>
                                                <span class="positionBox">{{(player.PlayerSelectTypeRole)?player.PlayerSelectTypeRole:player.PlayerRoleShort}}</span>
                                            </td>
                                            <td class="font-weight-500">{{player.PlayerName}}, {{player.TeamNameShort}} 
                                                <!-- <span class="text-muted">Oct 20, 10:00 pm v NYG</span> -->
                                            </td>
                                            <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{(player.IsInjuries)?player.IsInjuries:'-'}}</td> 
                                            <td>{{player.PlayerBattingStats.opp}}</td>
                                            <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            <td>
                                                <span  ng-if="!player.isSelected" class="addPlayer" ng-click="addPlayer(player)"><i class="fas fa-plus"></i></span>
                                                <span  ng-if="player.isSelected" class="removePlayer" ng-click="removePlayer(player)"><i class="fas fa-minus"></i></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody ng-if="ActiveRoleTab != '' && ActiveRoleTab != 'FLEX'"> 
                                        <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="ActiveRoleTab == player.PlayerRoleShort && ActiveRoleTab != 'FLEX'" >
                                            <td>
                                                <span class="positionBox">{{(player.PlayerSelectTypeRole)?player.PlayerSelectTypeRole:player.PlayerRoleShort}}</span>
                                            </td>
                                            <td class="font-weight-500">
                                                {{player.PlayerName}}, {{player.TeamNameShort}}
                                            </td>   
                                            <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{(player.IsInjuries)?player.IsInjuries:'-'}}</td> 
                                            <td>{{player.PlayerBattingStats.opp}}</td>
                                            <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            <td>
                                                <span  ng-if="!player.isSelected" class="addPlayer" ng-click="addPlayer(player)"><i class="fas fa-plus"></i></span>
                                                <span  ng-if="player.isSelected" class="removePlayer" ng-click="removePlayer(player)"><i class="fas fa-minus"></i></span>
                                            </td>
                                        </tr>
                                        <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="ActiveRoleTab == 'PF/C' && (player.PlayerRoleShort == 'PF' || player.PlayerRoleShort == 'C')" >
                                            <td>
                                                <span class="positionBox">{{(player.PlayerSelectTypeRole)?player.PlayerSelectTypeRole:player.PlayerRoleShort}}</span>
                                            </td>
                                            <td class="font-weight-500">
                                                {{player.PlayerName}}, {{player.TeamNameShort}}
                                            </td>   
                                            <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{(player.IsInjuries)?player.IsInjuries:'-'}}</td> 
                                            <td>{{player.PlayerBattingStats.opp}}</td>
                                            <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                            <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            <td>
                                                <span  ng-if="!player.isSelected" class="addPlayer" ng-click="addPlayer(player)"><i class="fas fa-plus"></i></span>
                                                <span  ng-if="player.isSelected" class="removePlayer" ng-click="removePlayer(player)"><i class="fas fa-minus"></i></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>  
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane {{(activeTab == 'draft')?'active':''}}" id="draft" role="tabpanel" aria-labelledby="draft-tab">
                    <div class="tinyBanner d-flex align-items-center justify-content-center">
                        <h3 class="text-white">Drafting History </h3>
                    </div>
                    <ul class="draft_list list-unstyled">
                        <li ng-repeat="user in UsersList"><div class="draftImg"><img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="team"></div><span>{{user.UserTeamCode}}</span></li>
                    </ul>
                    <div class="p-4 ">
                       <div class="table-responsive">
                       <table class=" table draftTable table-bordered bg-white text-center">
                            <thead>
                                <tr>
                                    <th>R#</th>
                                    <th colspan="6">
                                        <ul class="nav colorDots">
                                            <li> <span class="bg-success"></span>PG</li>
                                            <li> <span class="bg-orange"></span>SG</li>
                                            <li> <span class="bg-danger"></span>SF</li>
                                            <li> <span class="bg-info "></span>PF</li>
                                            <li> <span class="bg-purple"></span> C </li>
                                            <li> <span class="bg-gray"></span>FLEX</li>
                                        </ul>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="(key,value) in OtherUserSquads">
                                    <td>{{key}} <i class="text-danger fa fa-arrow-right"></i> </td>
                                    <td ng-repeat="val in value">
                                        <div ng-If="val.PlayerName" class="{{(val.PlayerSelectTypeRole == 'PG')?'text-success':''}} {{(val.PlayerSelectTypeRole == 'SG')?'text-orange':''}} {{(val.PlayerSelectTypeRole == 'SF')?'text-danger':''}} {{(val.PlayerSelectTypeRole == 'PF')?'text-info':''}} {{(val.PlayerSelectTypeRole == 'C')?'text-purple':''}} {{(val.PlayerSelectTypeRole == 'FLEX')?'text-gray':''}} drafTableImg">
                                            <img ng-src="{{val.PlayerPic}}" alt="" on-error-src="assets/img/default.jpg">
                                        </div>
                                        <span >{{(val.PlayerName)?val.PlayerName:''}} - {{val.PlayerSelectTypeRole}}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                       </div>
                    </div>
                </div>
                <div class="tab-pane {{(activeTab == 'trade')?'active':''}}" id="trade" role="tabpanel" aria-labelledby="trade-tab">
                    <div class="p-3 h-100 all_transaction_history">
                        <h6 class="mb-3">All Transaction History </h6>
                        <table class="table comman_table mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>User</th>
                                    <th>Player</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="data in AllTransactionList | orderBy:'-DateTime'">
                                    <td><span class="d-block {{(data.Type == 'DROP')?'text-danger':'text-success'}}" >{{(data.Type == "DROP")?'-':'+'}} {{data.Type}}</span></td>
                                    <td><span class="d-block ">{{data.UserTeamCode}}<span></td>
                                    <td><span class="d-block">{{data.PlayerName}} - {{data.PlayerSelectTypeRole}}</span></td>
                                    <td><span>{{data.DateTime | myDateFormat}}</span></td>
                                </tr>
                                <tr ng-if="AllTransactionList.length == 0">
                                    <td colspan="4">No Transaction yet!</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- removePlayer popup -->
    <div class="modal fade confirmToEnter site_modal modal_dark" popup-handler id="removePlayer" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
        <div class="modal-dialog modal-md"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h5 class="modal-title">Confirm Drop Player</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="details">
                            <img ng-src="{{PlayerInfo.PlayerPic}}" alt="" class="drafTableImg" on-error-src="assets/img/default.jpg">
                            <h6>{{PlayerInfo.PlayerName}} - {{PlayerInfo.PlayerSelectTypeRole}}</h6>
                            <!-- <p>You are about to enter this Contest, and account will be deducted <span class="themeClr">{{moneyFormat(ContestInfo.EntryFee)}}</span></p> -->
                            <h6>Are you sure you want to drop this player ?</h6>
                            
                            <div class="mt-5">
                                <button type="button" ng-click="closePopup('removePlayer')" class=" btn_secondary cursor_pointer mr-2">NO</button>
                                <button type="button" ng-click="confirmRemovePlayer()" class="btn_trans_dark cursor_pointer">YES</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- add player popup -->
     <div class="modal fade confirmToEnter site_modal modal_dark" popup-handler id="addPlayer" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
        <div class="modal-dialog modal-md"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h5 class="modal-title">Add Player</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="details">
                            <img src="assets/img/logo-only.png" alt="">
                            <h6>Please Select a {{playerRole}} position player.</h6>
                            <div class="form-group">
                                <select name="selectAddPlayer" ng-model="selectAddPlayer" class="custom-select ">
                                    <option value="">Select Player</option>
                                    <option ng-repeat="player in playerList" ng-if="!player.isSelected && player.PlayerRoleShort == playerRole && playerRole != 'FLEX'" value="{{player.PlayerGUID}}">{{player.PlayerName}} - {{player.PlayerRoleShort}}</option>
                                    <option ng-repeat="player in playerList" ng-if="!player.isSelected && player.PlayerRoleShort != 'QB' && playerRole == 'FLEX'" value="{{player.PlayerGUID}}">{{player.PlayerName}} - {{player.PlayerRoleShort}}</option>
                                 </select>
                            </div>
                            <div class="mt-5">
                                <button type="button" ng-click="closePopup('addPlayer')" class=" btn_secondary cursor_pointer mr-2">NO</button>
                                <button type="button" ng-click="confirmAddPlayer(selectAddPlayer)" class="btn_trans_dark cursor_pointer">YES</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade confirmToEnter site_modal modal_dark" popup-handler id="addPlayerConfirm" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
        <div class="modal-dialog modal-md"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h5 class="modal-title">Confirm Player</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="details">
                        <img ng-src="{{PlayerInfo.PlayerPic}}" alt="" class="drafTableImg" on-error-src="assets/img/default.jpg">
                            <h6>{{PlayerInfo.PlayerName}} - {{(ActiveRoleTab == 'FLEX')?'FLEX':PlayerInfo.PlayerRoleShort}}</h6>
                            <!-- <p>You are about to enter this Contest, and account will be deducted <span class="themeClr">{{moneyFormat(ContestInfo.EntryFee)}}</span></p> -->
                            <h6>Are you sure you want to add this player ?</h6>
                            
                            <div class="mt-5">
                                <button type="button" ng-click="closePopup('addPlayerConfirm')" class=" btn_secondary cursor_pointer mr-2">NO</button>
                                <button type="button" ng-click="confirmAddPlayer(PlayerInfo.PlayerGUID)" class="btn_trans_dark cursor_pointer">YES</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include('innerFooter.php'); ?>
