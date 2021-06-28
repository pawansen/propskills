<?php include('header.php'); ?>
<div ng-controller="draftRoomController" ng-init="matchDetails();getContest();getSnakeDraftUsers();getUserPlayRound();getBannerList();" ng-cloak >
    <div class="draftTeamPage draftTeamPage1 pt-0 common_bg1" style="margin-top: 60px;">
        <!-- Contest info -->
        <div class="topBar">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="list-unstyled status_bar mb-0">
                            <li><i class="fa fa-bars" style="color: #b17500;font-size: 24px;vertical-align: middle;"></i> {{ContestInfo.ContestName}}</li>
                            <li><img src="assets/img/rugby-balls.svg" alt="icon" class="img-fluid">{{ContestInfo.TotalJoined}} - Man {{(ContestInfo.ScoringType == 'PointLeague')?'Total Points Contest':'Round Robin'}}</li>
                            <li><img src="assets/img/yellow-dollar.svg" alt="icon" class="img-fluid">{{moneyFormat(ContestInfo.EntryFee)}} Entry Fee</li>
                            <li><img src="assets/img/yellow-trophy.svg" alt="icon" class="img-fluid">   {{moneyFormat(ContestInfo.WinningAmount)}} Prizes</li>
                            <li><img src="assets/img/people.svg" alt="icon" class="img-fluid">{{ContestInfo.TotalJoined}}/{{ContestInfo.ContestSize}} Participants</li>
                        </ul>
                    </div>
                    <div class="matchCenterHeader draftRoom col-md-12 mt-3">
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
                </div>
            </div>
        </div>

        <div class="draft_topHeader">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 col-lg-8">
                        <ul class="nav site_line_tabs site_line_tabs1" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'teams')?'active':''}}" id="teams-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('teams')" role="tab" aria-controls="teams" aria-selected="true">Draft Room</a>
                            </li>
                            <!-- <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'standing')?'active':''}}" id="standing-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('standing');getUserTeam();" role="tab" aria-controls="standing" aria-selected="false">Standing</a>
                            </li> -->
                            <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'playerStats')?'active':''}}" id="playerStats-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('playerStats');getPlayersStats(true);" role="tab" aria-controls="playerStats" aria-selected="false">Player Stats</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'schedule')?'active':''}}" id="schedule-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('schedule');getSchedule();" role="tab" aria-controls="schedule" aria-selected="false">Schedule</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'PlayerInjuries')?'active':''}}" id="PlayerInjuries-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('PlayerInjuries');" role="tab" aria-controls="PlayerInjuries" aria-selected="false">Player Injuries</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{(Tabs == 'PlayerProjections')?'active':''}}" id="PlayerProjections-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('PlayerProjections');" role="tab" aria-controls="PlayerProjections" aria-selected="false">Player Projections</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <ul class="nav category_players">
                            <li ng-repeat="pos in ContestInfo.SelectionCriteria">{{pos.name}} <span class="{{(pos.isCompleted)?'completed':''}}">{{pos.value}}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade {{(Tabs == 'teams')?'show active':''}}" id="teams" role="tabpanel" aria-labelledby="teams-tab">
                <!-- Slider section -->
                <div class="draft_header d-flex">
                    <!-- Timer & Assistant on/off section -->
                    <div class="draft_timer_wrapr ">
                        <p><strong class="mr-2">Status : </strong> <span ng-class="{Pending:'badge badge-secondary','Cancelled':'badge badge-danger', Completed:'badge badge-success',Running:'badge badge-success'}[ContestInfo.AuctionStatus]">{{ContestInfo.AuctionStatus}}</span></p>
                        <p ng-if="ContestInfo.AuctionStatus == 'Pending'"><strong class="mr-2">Starting Time : </strong><span class="live_counter" ><span ng-if="ContestInfo.AuctionStatus == 'Pending'" class="timer" timer-text="{{ContestInfo.LeagueJoinDateTime}}" timer-data="{{ContestInfo.LeagueJoinDateTime}}" ng-bind-html="clock | trustAsHtml"  match-status="{{ContestInfo.AuctionStatus}}"></span></span> </p>
                        <div class="d-flex align-items-center ">
                            <strong class="pr-2 border-right mr-2 d-inline-block">Round {{DraftLiveRound}}</strong>   
                            <div class="draft_timer" style="background-color:{{(counter <= 10 && LiveSnakeUserInfo.UserGUID == user_details.UserGUID)?'red':''}}"><i class="far fa-clock"></i> <span>{{counter| secondsToDateTime | date:'mm:ss'}}</span> Sec</div>
                        </div>
                        <audio id="remainder_music" loop>
                            <source src="assets/music/reminder_clock.mp3" type="audio/mp3">
                        </audio>
                    </div>

                    <!-- Round section -->
                    <div class="round_slider_wrapr">
                        <div class="round_team " ng-if="draft_silder_visible" snake-slick-custom-carousel >
                            <ul class="list-unstyled draft_team_list mb-0 " ng-repeat="user in userPlayRounds">
                                <li class="{{($index == 0)?'round_started':''}} {{(round.UserGUID == LiveSnakeUserInfo.UserGUID && SliderRound == (user.Round - 1)) ? 'current_snake_user' : ''}}" ng-repeat="round in user.Users">
                                    <bdi ng-if="$index == 0">ROUND {{user.Round}}</bdi>
                                    <div class="team_img {{(round.AuctionUserStatus == 'Online')?'user_online':'user_offline'}}"><img ng-src="{{round.ProfilePic}}" on-error-src="assets/img/default.jpg" alt=""></div>
                                    {{round.UserTeamCode}}
                                </li>
                            </ul>
                        </div>
                    </div>  
                    <div class="clearfix"></div>  
                </div>

                <!-- Main section -->
                <div class="pt_15 b-burger">
                    <div class="container-fluid">
                        <div class="row flex-lg-nowrap">
                            <!-- Team list section -->
                            <div class="col-lg-3 col-md-12 draft_sidebar pr-md-0">
                                <!-- All team list section -->
                                <div class="shadow_box_sm">
                                    <h6>PICK HISTORY</h6>
                                    <div ng-repeat="(key,value) in OtherUserSquads">
                                        <h6 class="round">{{key}}</h6>
                                        <table class="table ">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Player</th>
                                                    <th>Team</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr ng-repeat="team in value">
                                                    <td>{{$index +1}}</td>
                                                    <td ng-if="team.hasOwnProperty('PlayerName')">{{(team.PlayerSelectTypeRole)?team.PlayerSelectTypeRole:''}} - {{team.PlayerName}}</td>
                                                    <td ng-if="!team.hasOwnProperty('PlayerName')">-</td>
                                                    <td>{{team.UserTeamCode}}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- Draft team section -->
                            <div class="col-lg-7 col-md-12">
                                <div class="shadow_box_sm middleSec">
                                    <div class="team_header" ng-if="livePlayerInfo.hasOwnProperty('PlayerName')">
                                        <div class="row align-items-center">
                                            <div class="col-12 text-right mb-3">
                                                <button type="button" ng-disabled="DraftTeam" ng-show="ContestInfo.AuctionStatus == 'Running' && LiveSnakeUserInfo.UserGUID == user_details.UserGUID" class="btn_sm_primary bg-success" ng-click="confirmDraftTeam(livePlayerInfo)">Draft Player</button>
                                                <button type="button" ng-click="addPlayerToQueue(livePlayerInfo)" ng-if="!livePlayerInfo.IsAdded" class="btn_sm_white theme bg-warning text-white">ADD TO QUEUE</button>
                                                <button type="button" ng-click="removePlayerToQueue(livePlayerInfo)" ng-if="livePlayerInfo.IsAdded"  class="btn_sm_white theme bg-danger text-white">Remove TO QUEUE</button>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="draft_team_meta">
                                                    <img ng-src="{{livePlayerInfo.PlayerPic}}" on-error-src="assets/img/default.jpg" alt="" class="img-fluid mr-2">
                                                    <div class="">
                                                        <h6>{{livePlayerInfo.PlayerName}}</h6>
                                                        <!-- <p>RANK</p>
                                                        <h6>{{livePlayerInfo.Game.rank}}</h6> -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <p>POSITION</p>
                                                <h6>{{livePlayerInfo.PlayerRoleShort}}</h6>
                                            </div>
                                            <div class="col-lg-3">
                                                <p>TEAM</p>
                                                <h6>{{livePlayerInfo.TeamName}}</h6>
                                            </div>
                                            <div class="col-lg-3">
                                                <p>Season Stats</p>
                                                <h6> {{livePlayerInfo.Game.points_per_game}} PPG, {{livePlayerInfo.Game.assists_per_game}} APG ,{{livePlayerInfo.Game.rebounds_per_game}} RPG</h6>
                                            </div>
                                        </div>
                                    </div>  
                                    <div class="team_middle">
                                        <div class="row align-items-center">
                                            <div class="col-xl-8 col-md-auto">
                                                <ul class="shortList">
                                                    <li ng-repeat="(key,value) in ContestInfo.DraftPlayerSelectionCriteria"><a class="{{ActiveTab == key?'active':''}}" ng-click="chnageTab(key)" href="javascript:void(0)">{{key}}</a></li>
                                                </ul>
                                            </div>
                                            <!-- <div class="col-xl-3 col-md-auto px-0 d-flex justify-content-center align-items-center my-2 flex-lg-column">
                                                <span class="themeClr font-weight-500" ng-if="ContestInfo.AuctionStatus != 'Completed' && DraftLiveRound == 1">Auto Draft <br>(1st round only) </span>
                                                <div class="d-flex align-items-center justify-content-end auto_pick" ng-if="ContestInfo.AuctionStatus != 'Completed' && DraftLiveRound == 1">
                                                    On
                                                    <label class="switch d-block mx-2 mb-0" >
                                                        <input type="checkbox" class="custom-control-input" ng-model="AssistantStatus" ng-change="changeAssistantStatus(AssistantStatus)" id="customCheck1">
                                                        <span class="slider round"></span>
                                                    </label>
                                                    Off
                                                </div>
                                            </div> -->
                                            <div class="col-xl-4 col-md mt-md-2 mt-xl-0">
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <!-- <a class="download" href="javascript:void(0)"><i class="fa fa-download"></i> Download Player List</a> -->
                                                    <div class="searchBox ml-2">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" ng-model="searchPlayer" ng-model-options="{allowInvalid: true, debounce: 200}"  placeholder="Search Player">
                                                            <i class="fa fa-search form-control-feedback"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="playerStatus">
                                        <table class="table table_scroll dark_table">
                                            <thead>
                                                <tr>
                                                    <th>Pos</th>
                                                    <th>Player</th>
                                                    <th>Team</th>
                                                    <!-- <th>Opp</th> -->
                                                    <th>Status</th>
                                                    <th>PPG</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody ng-if="ActiveTab != ''">
                                                <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="(ActiveTab == player.PlayerRoleShort && ActiveTab != 'FLEX') || (ActiveTab == 'PF/C')">
                                                    <td><p>{{player.PlayerRoleShort}}</p></td>
                                                    <td>{{player.PlayerName}}</td>
                                                    <td>{{player.TeamNameShort}}</td>
                                                    <!-- <td></td> -->
                                                    <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{player.IsInjuries}}</td>
                                                    <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                                    <td><a href="javascript:void(0)" ng-click="selectPlayer(player)">+</a></td>
                                                </tr>
                                                <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="player.PlayerRoleShort != 'QB' && ActiveTab == 'FLEX'">
                                                    <td><p>{{player.PlayerRoleShort}}</p></td>
                                                    <td>{{player.PlayerName}}</td>
                                                    <td >{{player.TeamNameShort}}</td>
                                                    <td ></td>
                                                    <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}" >{{player.IsInjuries}}</td>
                                                    <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                                    <td><a href="javascript:void(0)" ng-click="selectPlayer(player)">+</a></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="top_banner_slider" slick-banner-slider ng-if="IsBannerAvailable">
                                    <div ng-repeat="banner in BannerList">
                                        <img ng-src="{{banner.MediaURL}}" alt="{{banner.MediaCaption}}">
                                    </div>
                                </div>

                                <div class="shadow_box_sm text-danger mt-2">
                                    Flex position can't be drafted until 5 main positions are drafted first
                                </div>
                            </div>
                            <!-- Sqaud section -->
                            <div class="col-lg-2 col-md-12 pl-lg-0 draft_sidebar playerQueue">
                                <!-- Logged in user sqaud -->
                                <div class="shadow_box_sm team-roster">
                                    <div class="topHead">
                                        <div class="heading">
                                            <h6>TEAM ROSTER</h6>
                                            <p>{{MySquadPlayerCount}}/{{ContestInfo.RosterSize}}</p>
                                        </div>
                                        <div class="d-flex">
                                            <img ng-src="{{user_details.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="" class="img-fluid">
                                            <div class="ml-2">
                                                <h6>{{user_details.FirstName}}</h6>
                                                <p>Your Team</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="details">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>PLAYER</th>
                                                    <th>POS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr ng-repeat="player in MySquadPlayers">
                                                    <td>{{(player.PlayerName)?player.PlayerName:'-'}}</td>
                                                    <td>{{player.PlayerSelectTypeRole}}</td>
                                                </tr>
                                                <tr ng-if="MySquadPlayers.length == 0">
                                                    <td colspan="2">No Player Draft.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="shadow_box_sm">
                                    <h6>ASSISTANT QUEUE</h6>
                                    <div class="clearfix"></div>
                                    <p class="ml-2 mb-0 mutedClr">Drag and Drop to set Player priority.</p>
                                    <ul class="custom_scroll connectedSortable" id="sortable">
                                        <li id="{{player.PlayerGUID}}" ng-repeat='player in PreAssistantPlayers track by $index'>
                                            <a href="javascript:void(0)" ng-click="removePlayerToQueue(player)">
                                                <span>{{player.PlayerRoleShort}} - {{player.PlayerName}}, {{player.TeamNameShort}}</span>
                                                <span>-</span>
                                            </a>
                                        </li>
                                        <li ng-if="PreAssistantPlayers.length == 0" class="ml-2"> No player in your assistant queue.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
            <div class="tab-pane fade {{(Tabs == 'standing')?'show active':''}}" id="standing" role="tabpanel" aria-labelledby="standing-tab">
                <div class="container-fluid mt-4">
                    <div class="shadow_box px-4 pt-4">
                        <div class=" mb-4">
                            <h4>Standing</h4>	
                        </div>
                        <table class="table commn_table text-center table-borderless ">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Rank</th>
                                    <th class="text-left">User</th>
                                    <th>Total Points</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="user in JoinedContestUserStanding">
                                    <td>{{user.UserRank | RankFormat}}</td>
                                    <td>
                                        <a class="player_profile leaderboard">
                                            <img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg">
                                            <div>
                                                <span class="player_name">{{user.UserTeamCode}}</span>		
                                            </div>
                                        </a>
                                    </td>
                                    <td>{{(user.TotalPointsSeason)?user.TotalPointsSeason:0}}</td>
                                    <td><a href="Scoreboard?ContestGUID={{ContestGUID}}&SeriesGUID={{SeriesGUID}}&UserGUID={{user.UserGUID}}" target="_blank" class="themeClr">View Scoreboard</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- {{(Tabs == 'playerStats')?'show active':''}} -->
            <div class="tab-pane fade " id="playerStats" role="tabpanel" aria-labelledby="playerStats-tab">
                <div class="container-fluid mt-4">
                    <div class="shadow_box px-4 pt-4">
                        <div class=" mb-4">
                            <h4>Player Stats</h4>	
                        </div>
                        <div class="row align-items-center ">
                            <div class="col-md-auto">
                                <ul class="p-3 nav site_sm_tabs site_line_tabs1" id="myTab" role="tablist">
                                    <li class="nav-item " role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == ''?'active':''}}" id="all-tab" data-toggle="tab" ng-click="chnageTabStats('')" href="javascript:void(0)" role="tab" aria-controls="all" aria-selected="true">all</a>
                                    </li>
                                    <li class="nav-item" role="presentation" ng-repeat="(key,value) in ContestInfo.DraftPlayerSelectionCriteria">
                                        <a class="nav-link {{ActiveRoleTab == key?'active':''}}" id="{{key}}-tab" data-toggle="tab" ng-click="chnageTabStats(key)" href="javascript:void(0)"role="tab" aria-controls="{{key}}" aria-selected="false">{{key}}</a>
                                    </li>
                                </ul>
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
                                </div>
                                <div class="playerTableWrapr custom_scroll mb-4" scrolly>
                                    <table class="table table-hovered table-bordered table-sm bg-white ">
                                        <thead>
                                            <tr>
                                                <th>POS</th>
                                                <th>PLAYER</th>
                                                <!-- <th>OPP</th> -->
                                                <th>PPG</th>
                                                <th>AST</th>
                                                <th>REB</th>
                                                <th>BLK</th>
                                                <th>STL</th>
                                                <th>TO</th>
                                            </tr>
                                        </thead>
                                        <tbody ng-show="ActiveRoleTab == '' || ActiveRoleTab == 'FLEX'" >
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer} | orderBy:propertyName1:reverse1" ng-if="player.hasOwnProperty('PlayerName')">
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">{{player.PlayerName}}, {{player.TeamName}}</td>    
                                                <!-- <td>{{player.PlayerBattingStats.Opp}}</td> -->
                                                <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            </tr>
                                        </tbody>
                                        <tbody ng-show="ActiveRoleTab != '' && ActiveRoleTab != 'FLEX'" > 
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer} | orderBy:propertyName1:reverse1" ng-if="(ActiveRoleTab == player.PlayerRoleShort && ActiveRoleTab != 'FLEX')" >
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">
                                                    {{player.PlayerName}}, {{player.TeamName}}
                                                </td>    
                                                <!-- <td>{{player.PlayerBattingStats.Opp}}</td> -->
                                                <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            </tr>
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer} | orderBy:propertyName1:reverse1" ng-if="ActiveRoleTab == 'PF/C' && (player.PlayerRoleShort == 'PF' || player.PlayerRoleShort == 'C')" >
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">
                                                    {{player.PlayerName}}, {{player.TeamName}}
                                                </td>    
                                                <!-- <td>{{player.PlayerBattingStats.Opp}}</td> -->
                                                <td>{{player.PlayerBattingStats.points_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.assists_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.rebounds_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.blocks_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.steals_per_game}}</td>
                                                <td>{{player.PlayerBattingStats.turnovers_per_game  }}</td>
                                            </tr>
                                        </tbody>
                                    </table>  
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--  {{(Tabs == 'schedule')?'show active':''}} -->
            <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                <div class="container-fluid mt-4">
                    <div class="shadow_box px-4 pt-4">
                        <div class="mb-2">
                            <h4>Schedule</h4>	
                        </div>
                        <div class="">
                            <div class="table-responsive">
                                <table class="table  comman_table loby_table tableDArk table_scroll">
                                    <thead>
                                        <tr>
                                            <th>CONTEST</th>
                                            <th>DRAFT TYPE</th>
                                            <th>BUY IN </th>
                                            <th>PRIZE</th>
                                            <th>LENGTH</th>
                                            <th>PARTICIPANTS</th>
                                            <th>DRAFT TIME</th>
                                            <th>ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr ng-if="ContestsTotalCount > 0" ng-repeat="contest in Contests">
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
                                            <td>{{(contest.ContestDuration == 'Daily')?'1 Day':'1 Week'}} <br /> ({{MatchDetails.MatchStartDateTime | myDateOnlyFormat}})</td>
                                            <td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
                                            <td>{{contest.LeagueJoinDateTime | myDateFormat}} </td>
                                            <td>
                                                <a href="javascript:void(0);" ng-if="contest.IsJoined == 'No' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="check_balance_amount(contest)">Join</a>
                                                <a href="javascript:void(0);" ng-if="contest.IsJoined == 'Yes' && contest.Status == 'Pending'" class="btn_sm_primary" ng-click="EnterDraft(contest)">Enter</a>
                                            </td>
                                        </tr>	
                                        <tr ng-if="ContestsTotalCount == 0">
                                            <td colspan="8" class="empty_table">No Contests Available.</td>
                                        </tr>				    
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                            <h6>{{ContestDetails.ContestName}}</h6>
                            <p>You are about to enter this Contest, and account will be deducted <span class="themeClr">{{moneyFormat(ContestDetails.EntryFee)}}</span></p>
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
<?php include('innerFooter.php'); ?>    
