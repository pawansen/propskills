<?php include('header.php'); ?>
<div ng-controller="createSnakeDraftTeamController" ng-init="getContest();getSnakeDraftUsers();getUserPlayRound();getBannerList();" ng-cloak >
    <div class="draftTeamPage draftTeamPage1 pt-0 common_bg" style="margin-top: 60px;">
        <!-- Contest info -->
        <div class="topBar">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="list-unstyled status_bar mb-0">
                            <li><i class="fa fa-bars" style="color: #b17500;font-size: 24px;vertical-align: middle;"></i> {{ContestInfo.ContestName}}</li>
                            <li><img src="assets/img/rugby-balls.svg" alt="icon" class="img-fluid">{{ContestInfo.TotalJoined}} - Man {{(ContestInfo.ScoringType == 'PointLeague')?'Point League':'Round Robin'}}</li>
                            <li><img src="assets/img/yellow-dollar.svg" alt="icon" class="img-fluid">{{moneyFormat(ContestInfo.EntryFee)}} Entry Fee</li>
                            <li><img src="assets/img/yellow-trophy.svg" alt="icon" class="img-fluid">   {{moneyFormat(ContestInfo.WinningAmount)}} Prizes</li>
                            <li><img src="assets/img/people.svg" alt="icon" class="img-fluid">{{ContestInfo.TotalJoined}}/{{ContestInfo.ContestSize}} Participants</li>
                        </ul>
                    </div>
                    <div style="margin:0 auto;">
                        <h4 class="themeClr align-items-center" style="margin: 0 auto;padding-top: 10px;">{{ContestInfo.ContestDuration}} <span ng-if="ContestInfo.ContestDuration == 'SeasonLong'">- {{ContestInfo.WeekStart}}</span></h4>
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
                                <a class="nav-link {{(Tabs == 'teams')?'active':''}}" id="teams-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('teams')" role="tab" aria-controls="teams" aria-selected="true">Teams</a>
                            </li>
                            <li class="nav-item" ng-if="ContestInfo.ContestDuration == 'SeasonLong'">
                                <a class="nav-link {{(Tabs == 'standing')?'active':''}}" id="standing-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('standing');getUserTeam();" role="tab" aria-controls="standing" aria-selected="false">Standing</a>
                            </li>
                            <li class="nav-item" ng-if="ContestInfo.ContestDuration == 'SeasonLong'">
                                <a class="nav-link {{(Tabs == 'weeklyResults')?'active':''}}" id="weeklyResults-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('weeklyResults');getSeasonLongUserList(week);" role="tab" aria-controls="weeklyResults" aria-selected="false">Weekly Results</a>
                            </li>
                            <li class="nav-item" ng-if="ContestInfo.ContestDuration == 'SeasonLong'">
                                <a class="nav-link {{(Tabs == 'playerStats')?'active':''}}" id="playerStats-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('playerStats');getPlayersStats(true);" role="tab" aria-controls="playerStats" aria-selected="false">Player Stats</a>
                            </li>
                            <!-- <li class="nav-item" ng-if="ContestInfo.ContestDuration == 'SeasonLong'">
                                <a class="nav-link {{(Tabs == 'schedule')?'active':''}}" id="schedule-tab" data-toggle="tab" href="javascript:void(0);" ng-click="changeTab('schedule')" role="tab" aria-controls="schedule" aria-selected="false">Schedule</a>
                            </li> -->
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
                        <!-- <audio id="remainder_music" loop>
                            <source src="assets/music/reminder_clock.mp3" type="audio/mp3">
                        </audio> -->
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
                                                <button type="button" ng-disabled="DraftTeam" ng-show="ContestInfo.AuctionStatus == 'Running' && LiveSnakeUserInfo.UserGUID == user_details.UserGUID" class="btn_sm_primary" ng-click="confirmDraftTeam(livePlayerInfo)">Draft Player</button>
                                                <!-- <button type="button" class="btn_sm_white">ADD TO QUEUE</button> -->
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="draft_team_meta">
                                                    <img ng-src="{{livePlayerInfo.PlayerPic}}" on-error-src="assets/img/default.jpg" alt="" class="img-fluid mr-2">
                                                    <div class="">
                                                        <h6>{{livePlayerInfo.PlayerName}}</h6>
                                                        <!-- <p>PRE - DRAFT RANK</p>
                                                        <h6>{{livePlayerInfo.PlayerBattingStats.rank}}</h6> -->
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
                                                <p>2019 SEASON</p>
                                                <h6>{{livePlayerInfo.Yards}} YDS, {{livePlayerInfo.TotalTouchdowns | number:0}} TDS<!-- {{livePlayerInfo.PlayerBattingStats.total_points}} PTS --></h6>
                                            </div>
                                        </div>
                                    </div>  
                                    <div class="team_middle">
                                        <div class="row align-items-center">
                                            <div class="col-xl-5 col-md-auto">
                                                <ul class="shortList">
                                                    <!-- <li><a class="{{ActiveTab == ''?'active':''}}" ng-click="chnageTab('')" href="javascript:void(0)">ALL</a></li> -->
                                                    <li><a class="{{ActiveTab == 'QB'?'active':''}}" ng-click="chnageTab('QB')" href="javascript:void(0)">QB</a></li>
                                                    <li><a class="{{ActiveTab == 'RB'?'active':''}}" ng-click="chnageTab('RB')" href="javascript:void(0)">RB</a></li>
                                                    <li><a class="{{ActiveTab == 'WR'?'active':''}}" ng-click="chnageTab('WR')" href="javascript:void(0)">WR</a></li>
                                                    <li><a class="{{ActiveTab == 'TE'?'active':''}}" ng-click="chnageTab('TE')" href="javascript:void(0)">TE</a></li>
                                                    <li><a class="{{ActiveTab == 'FLEX'?'active':''}}"ng-click="chnageTab('FLEX')"  href="javascript:void(0)">FLEX</a></li>
                                                </ul>
                                            </div>
                                            <div class="col-xl-3 col-md-auto px-0 d-flex justify-content-center align-items-center my-2 flex-lg-column">
                                                <span class="themeClr font-weight-500" ng-if='ContestInfo.Privacy == "Yes"'>Auto Draft  </span>
                                                <div ng-if='ContestInfo.Privacy == "Yes"' class="d-flex align-items-center justify-content-end auto_pick">
                                                    On
                                                    <label class="switch d-block mx-2 mb-0" >
                                                        <input type="checkbox" class="custom-control-input" ng-model="AssistantStatus" ng-change="changeAssistantStatus(AssistantStatus)" id="customCheck1">
                                                        <span class="slider round"></span>
                                                    </label>
                                                    Off
                                                </div>
                                            </div>
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
                                                   <!--  <th>Rank</th> -->
                                                    <th>Player</th>
                                                    <th>Team</th>
                                                    <th>Status</th>
                                                    <th>YPG</th>
                                                    <!-- <th>Date & Time</th>
                                                    <th>Game</th> -->
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody ng-if="ActiveTab != ''">
                                                <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="ActiveTab == player.PlayerRoleShort && ActiveTab != 'FLEX'">
                                                    <td><p>{{player.PlayerRoleShort}}</p></td>
                                             <!--        <td>{{player.PlayerBattingStats.rank}}</td> -->
                                                    <td>{{player.PlayerName}}</td>
                                                    <td>{{player.TeamName}}</td>
                                                    <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}">{{player.IsInjuries}}</td>
                                                    <td>{{player.YardsPerGame}}</td>
                                                    <!-- <td>30 Jul, 01:56 PM</td>
                                                    <td>MT @ <span class="themeClr">CHI</span></td> -->
                                                    <td><a href="javascript:void(0)" ng-click="selectPlayer(player)">+</a></td>
                                                </tr>
                                                <tr ng-repeat="player in playerList | filter:{PlayerName: searchPlayer} | orderBy:propertyName:reverse" ng-if="player.PlayerRoleShort != 'QB' && ActiveTab == 'FLEX'">
                                                    <td><p>{{player.PlayerRoleShort}}</p></td>
                                           <!--          <td>{{player.PlayerBattingStats.rank}}</td> -->
                                                    <td>{{player.PlayerName}}</td>
                                                    <td >{{player.TeamName}}</td>
                                                    <td class="text-{{(player.IsInjuries == 'Active')?'success':'danger'}}" >{{player.IsInjuries}}</td>
                                                    <td>{{player.YardsPerGame}}</td>
                                                    <!-- <td>30 Jul, 01:56 PM</td>
                                                    <td>MT @ <span class="themeClr">CHI</span></td> -->
                                                    <td><a href="javascript:void(0)" ng-click="selectPlayer(player)">+</a></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- <div class="playerStatusBtm">
                                    <div class="row align-items-center">
                                        <div class="col-sm-8">
                                            <div class="inactivePlayer">
                                                <div class="mr-3">
                                                    <a href="javascript:void(0)">+</a>
                                                    <span>Inactive Player</span>
                                                </div>
                                                <div class="">
                                                    <a href="javascript:void(0)">I</a>
                                                    <span>Inactive Reserve</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 text-right">
                                            <div class="form-group">
                                                <input class="styled-checkbox" name="checkbox" id="styled-checkbox" type="checkbox">
                                                <label for="styled-checkbox">show Drafted</label>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->
                                <div class="top_banner_slider" slick-banner-slider ng-if="IsBannerAvailable">
                                    <div ng-repeat="banner in BannerList">
                                        <img ng-src="{{banner.MediaURL}}" alt="{{banner.MediaCaption}}">
                                    </div>
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
                                                    <th>POS</th>
                                                    <th>PLAYER</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr ng-repeat="player in MySquadPlayers">
                                                    <td>{{player.PlayerSelectTypeRole}}</td>
                                                    <td>{{(player.PlayerName)?player.PlayerName:'-'}}</td>
                                                </tr>
                                                <tr ng-if="MySquadPlayers.length == 0">
                                                    <td colspan="2">No Player Draft.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="shadow_box_sm">
                                    <h6>PLAYER QUEUE</h6>
                                    <ul class="custom_scroll">
                                        <li ng-repeat='player in playerList' ng-class="{active:livePlayerInfo.PlayerGUID == player.PlayerGUID}">
                                            <a href="javascript:void(0)" ng-click="selectPlayer(player)">
                                                <span>{{player.PlayerRoleShort}} - {{player.PlayerName}}, {{player.TeamName}}</span>
                                                <span>+</span>
                                            </a>
                                        </li>
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
                                    <td><a href="Scoreboard?ContestGUID={{ContestGUID}}&SeriesGUID={{SeriesGUID}}&UserGUID={{user.UserGUID}}&Week={{ContestInfo.WeekStart}}" target="_blank" class="themeClr">View Scoreboard</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{(Tabs == 'weeklyResults')?'show active':''}}" id="weeklyResults" role="tabpanel" aria-labelledby="weeklyResults-tab">
                <div class="container-fluid mt-4">
                    <div class="shadow_box px-4 pt-4">
                        <div class=" mb-4">
                            <h4>Weekly Results</h4>	
                            <div class="row" ng-if="JoinedContestUserList.length > 0">
                                <div class="col-sm-2 d-flex align-items-center ">
                                    <select  name="week" class="custom-select secondary_select" ng-model="week" ng-change="weekChange(week)">
                                        <option ng-repeat="week in ContestInfo.WeekTeamInfo" value="{{week.WeekID}}">Week {{week.WeekID}}</option>
                                    </select>		
                                </div>
                            </div>
                        </div>
                        <table class="table commn_table text-center table-borderless ">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Rank</th>
                                    <th class="text-left">User</th>
                                    <th>Weekly Points</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="user in JoinedContestUserList"  >
                                    <td>{{user.UserRank | RankFormat}}</td>
                                    <td>
                                        <a class="player_profile leaderboard">
                                            <img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg">
                                            <div>
                                                <span class="player_name">{{user.UserTeamCode}}</span>		
                                            </div>
                                        </a>
                                    </td>
                                    <td>{{(user.WeekTotalPoints)?user.WeekTotalPoints:0}}</td>
                                    <td><a href="Scoreboard?ContestGUID={{ContestGUID}}&SeriesGUID={{SeriesGUID}}&UserGUID={{user.UserGUID}}&Week={{week}}" target="_blank" class="themeClr">View Scoreboard</a></td>
                                </tr>
                                <tr ng-if="JoinedContestUserList.length == 0">
                                    <td colspan="5">No Data Available.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{(Tabs == 'playerStats')?'show active':''}}" id="playerStats" role="tabpanel" aria-labelledby="playerStats-tab">
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
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == 'QB'?'active':''}}" id="qb-tab" data-toggle="tab" ng-click="chnageTabStats('QB')" href="javascript:void(0)"role="tab" aria-controls="qb" aria-selected="false">QB</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == 'RB'?'active':''}}" id="rb-tab" data-toggle="tab" ng-click="chnageTabStats('RB')" href="javascript:void(0)" role="tab" aria-controls="rb" aria-selected="false">RB</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == 'WR'?'active':''}}" id="wr-tab" data-toggle="tab" ng-click="chnageTabStats('WR')" href="javascript:void(0)" role="tab" aria-controls="wr" aria-selected="false">WR</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == 'TE'?'active':''}}" id="te-tab" data-toggle="tab" ng-click="chnageTabStats('TE')" href="javascript:void(0)" role="tab" aria-controls="te" aria-selected="false">TE</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ActiveRoleTab == 'FLEX'?'active':''}}" id="def-tab" data-toggle="tab" ng-click="chnageTabStats('FLEX')" href="javascript:void(0)" role="tab" aria-controls="def" aria-selected="false">FLEX</a>
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
                                                <th rowspan="2">POS</th>
                                                <th rowspan="2">PLAYER</th>
                                                <th rowspan="2" class="text-center">FANTASY POINTS</th>
                                                <th colspan="2" class="text-center">PASSING</th>
                                                <th colspan="2" class="text-center">RUSHING</th>
                                                <th colspan="2" class="text-center">RECEIVING</th>
                                                <th colspan="3" class="text-center">OTHERS</th>
                                            </tr>
                                            <tr>
                                                <th>YDS</th>
                                                <th>TD</th>
                                                <th>YDS</th>
                                                <th>TD</th>
                                                <th>YDS</th>
                                                <th>TD</th>
                                                <th>FG</th>
                                                <th>FL</th>
                                                <th>TT</th>
                                            </tr>
                                        </thead>
                                        <tbody ng-show="ActiveRoleTab == ''" >
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer}" ng-if="player.hasOwnProperty('PlayerName')">
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">{{player.PlayerName}}, {{player.TeamName}} 
                                                </td>    
                                                <td class="text-center">{{player.PlayerBattingStats.total_points}}</td>
                                                <td>{{player.PlayerBattingStats.yards}}</td>
                                                <td>{{player.PlayerBattingStats.passing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_yards}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_yards}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.field_goals}}</td>
                                                <td>{{player.PlayerBattingStats.fumbles_lost}}</td>
                                                <td>{{player.PlayerBattingStats.total_tackles}}</td>
                                            </tr>
                                        </tbody>
                                        <tbody ng-show="ActiveRoleTab != ''" > 
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer}" ng-if="ActiveRoleTab == player.PlayerRoleShort && ActiveRoleTab != 'FLEX'" >
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">
                                                    {{player.PlayerName}}, {{player.TeamName}}
                                                </td>    
                                                <td class="text-center">{{player.PlayerBattingStats.total_points}}</td>
                                                <td>{{player.PlayerBattingStats.yards}}</td>
                                                <td>{{player.PlayerBattingStats.passing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_yards}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_yards}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.field_goals}}</td>
                                                <td>{{player.PlayerBattingStats.fumbles_lost}}</td>
                                                <td>{{player.PlayerBattingStats.total_tackles}}</td>
                                            </tr>
                                            <tr ng-repeat="player in PlayerStatList | filter:{PlayerName: searchPlayer}" ng-if="player.PlayerRoleShort != 'QB' && ActiveRoleTab == 'FLEX'">
                                                <td>
                                                    <span class="positionBox">{{player.PlayerRoleShort}}</span>
                                                </td>
                                                <td class="font-weight-500">
                                                    {{player.PlayerName}}, {{player.TeamName}}
                                                </td>    
                                                <td class="text-center">{{player.PlayerBattingStats.total_points}}</td>
                                                <td>{{player.PlayerBattingStats.yards}}</td>
                                                <td>{{player.PlayerBattingStats.passing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_yards}}</td>
                                                <td>{{player.PlayerBattingStats.rushing_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_yards}}</td>
                                                <td>{{player.PlayerBattingStats.receiving_touchdowns}}</td>
                                                <td>{{player.PlayerBattingStats.field_goals}}</td>
                                                <td>{{player.PlayerBattingStats.fumbles_lost}}</td>
                                                <td>{{player.PlayerBattingStats.total_tackles}}</td>
                                            </tr>
                                        </tbody>
                                    </table>  
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{(Tabs == 'schedule')?'show active':''}}" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                <h6 class="comingSoon">Coming Soon...</h6>
            </div>
        </div>
    </div>
</div>
<?php include('innerFooter.php'); ?>    
