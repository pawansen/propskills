<header class="panel-heading">
    <h1 class="h4">Contest Game Templates Add</h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->


    <div class="form-area" ng-init="loadDatepicker();">

        <form id="add_form" name="add_form" autocomplete="off" >

            <div class="row">
                <!--                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">League Type</label>
                                        <select id="IsPaid" ng-model="LeagueType" name="LeagueType" class="form-control chosen-select">
                                            <option value="" disabled selected="">Please Select</option>
                                            <option value="Draft">Snake Draft</option>
                                        </select>
                                    </div>
                                </div>-->

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Game Play Type</label>
                        <select id="GamePlayType" ng-model="GamePlayType" name="GamePlayType" class="form-control chosen-select" ng-change="getTypeConfiguration(GamePlayType)">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="PICK_5">Pick 5 Fantasy</option>
                            <option value="CLASSIC_9">Classic 9 Fantasy</option>
                            <option value="PROP_5">Prop 5 Fantasy</option>
                            <option value="TD_ONLY">TD Only</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Sports Type</label>
                        <select id="IsPaid" ng-model="GameType" name="GameType" class="form-control chosen-select" ng-change="getFilterData(GameType)">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="Nfl">Pro Football</option>
                    <!--         <option value="Ncaaf">College Football</option> -->
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Sports Game Type</label>
                        <select id="SubGameType" ng-model="SubGameType" name="SubGameType" class="form-control" ng-change="getWeekAll(SubGameType)">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="ProFootballPreSeasonOwners">Pro (Pre Season)</option>
                            <option value="ProFootballRegularSeasonOwners">Pro (Regular Season)</option>
                            <option value="ProFootballPlayoffs">Pro (Playoffs)</option>
                        </select>
                    </div>
                </div>
                
<!--                     <div class="col-md-3" ng-if="GameType=='Ncaaf'">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Sports Game Type</label>
                        <select id="SubGameType" ng-model="SubGameType" name="SubGameType" class="form-control" ng-change="getTypeConfiguration(SubGameType)">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="CollegeFootballRegularSeason">College (Regular Season)</option>
                            <option value="CollegeFootballPower5RegularSeason">Power 5 (Regular Season)</option>
                        </select>
                    </div>
                </div> -->


                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Season</label>
                        <select id="Series" name="SeriesGUID" ng-model="SeriesGUID" class="form-control chosen-select" ng-change="getCurrentWeek(SeriesGUID);">
                            <option value="">Please Select</option>
                            <option ng-repeat="Series in filterData.SeiresData" value="{{Series.SeriesGUID}}">{{Series.SeriesName}}</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Contest Duration</label>
                        <select id="ContestDuration" ng-model="ContestDuration" name="ContestDuration" class="form-control" ng-change="getWeekDate(ContestDuration,WeekStart,SeriesGUID)">
                            <option value="">Please Select</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                        <small>Select this option contest points calculate weekly basic OR days basic.</small>
                    </div>
                </div> 

<!--                 <div class="col-md-6" ng-if="ContestDuration == 'Daily'">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Match</label>
                        <select id="MatchGUID" name="MatchGUID[]" class="form-control chosen-select1" multiple="">
                            <option value="">Please Select</option>
                            <option ng-repeat="match in MatchData" value="{{match.MatchGUID}}">{{match.TeamNameLocal}} Vs {{match.TeamNameVisitor}} ON {{match.MatchStartDateTime}}</option>
                        </select>
                    </div>
                </div> -->
<!--                 <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Scoring Type</label>
                        <select id="IsPaid" ng-model="ScoringType" name="ScoringType" class="form-control">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="PointLeague">Point League</option>
                            <option value="RoundRobin">Round Robin</option>
                        </select>
                    </div>
                </div> -->
                <div class="col-md-3" ng-if="ContestDuration == 'Weekly'">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Week</label>
                        <select id="IsPaid" ng-model="WeekStart" name="WeekStart" class="form-control" ng-change="getWeekDate(ContestDuration,WeekStart,SeriesGUID)">
                            <option value="">Please Select</option>
                            <option ng-repeat="(i, Week) in WeekArray" value="{{i}}" ng-if="i >= currentWeek.WeekID">{{Week}}</option>
                        </select>
                    </div>
                </div>
<!--                 <input ng-if="ContestDuration == 'Daily'" name="WeekStart" ng-model="WeekStart" type="hidden" value="1"> -->




<!--                 <div class="col-md-3" ng-if="ContestDuration=='Daily'">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Day Date</label>
                        <select id="DailyDate" ng-model="DailyDate" name="DailyDate" class="form-control" >
                            <option value="">Please Select</option>
                            <option ng-repeat="Value in DailyDateResponse" value="{{Value.MatchStartDateTime}}">{{Value.MatchStartDateTime}}</option>
                        </select>
                    </div>
                </div> -->
                 <input name="DailyDate" ng-model="DailyDate" type="hidden" value="2021-01-01">

<!--                 <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Week End</label>
                        <select id="IsPaid" ng-model="WeekEnd" name="WeekEnd" class="form-control">
                            <option value="">Select</option>
                            <option ng-repeat="Week in WeekArray" ng-if="WeekStart <= Week" value="{{Week}}">{{Week}}</option>    
                        </select>
                    </div>
                </div> -->

                <input name="ScoringType" ng-model="ScoringType" type="hidden" value="PointLeague">
<!--                 <div class="col-md-3">

                    <div class="form-group">
                        <label class="control-label">League Join Date </label>
                        <input name="LeagueJoinDateTime" id="LeagueJoinDateTime" readonly="" ng-model="LeagueJoinDateTime" type="text" class="form-control" value="">
                        <small>Set league join date time on which time user can create a team.</small>
                    </div>
                </div> -->
                <input name="LeagueJoinDateTime" ng-model="LeagueJoinDateTime" type="hidden" value="2021-01-01">
<!-- 
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">League Join Time?</label>
                        <select id="LeagueJoinTime" ng-model="LeagueJoinTime" name="LeagueJoinTime" class="form-control chosen-select">
                            <option value="">Please Select</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="13:00:00">1:00 PM</option>
                            <option value="15:00:00">3:00 PM</option>
                            <option value="18:00:00">6:00 PM</option>
                            <option value="20:00:00">8:00 PM</option>
                            <option value="23:00:00">11:00 PM</option>

                        </select>
                    </div>
                </div> -->
                <input name="LeagueJoinTime" ng-model="LeagueJoinTime" type="hidden" value="10:00:00">
                <input name="LeagueType" ng-model="LeagueType" type="hidden" value="Draft">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label">Contest Name</label>
                        <input name="ContestName" type="text" class="form-control" placeholder="Contest Name" value="" maxlength="40">
                    </div>
                </div>


                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Is Paid Contest?</label>
                        <select id="IsPaid" ng-model="IsPaid" name="IsPaid" class="form-control chosen-select">
                            <option value="">Please Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>

                        </select>
                        <small>Select this option notifiy that contest is free or paid.</small>
                    </div>
                </div>


                <!--                <div class="col-md-3" ng-if="IsPaid == 'Yes'">
                                    <div class="form-group">
                                        <label class="control-label">Cash Bonus Contribution (%)</label>
                                        <input name="CashBonusContribution" ng-model="CashBonusContribution" placeholder="Cash Bonus Controbution in Percentage" type="text" class="form-control numeric" value="0" maxlength="3" ng-if="IsPaid == 'Yes'">
                                    </div>
                                </div>-->
                <input name="CashBonusContribution" ng-model="CashBonusContribution" type="hidden" value="0" ng-if="IsPaid == 'Yes'">
                <input name="ContestFormat" ng-model="ContestFormat" type="hidden" value="League">

                <!--                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Contest Format</label>
                                        <select id="ContestFormat" ng-model="ContestFormat" name="ContestFormat" class="form-control chosen-select">
                                            <option value="League">League</option>
                                        </select>
                                        <small></small>
                                    </div>
                                </div>-->

                <div class="col-md-3" ng-if="IsPaid == 'Yes'">
                    <div class="form-group">
                        <label class="control-label">Admin Charges (%)</label>
                        <input name="AdminPercent" ng-model="custom.AdminPercent" placeholder="Admin Charges in Percentage"  ng-init="custom.AdminPercent = '10'" type="text" class="form-control numeric" maxlength="3" ng-if="IsPaid == 'Yes'">
                    </div>
                </div>

                <div class="col-md-3" ng-if="IsPaid == 'Yes'" >
                    <div class="form-group">
                        <label class="control-label">Entry Fee</label>
                        <input name="EntryFee" ng-model="custom.EntryFee" type="text" placeholder="0" class="form-control numeric" value="" maxlength="40" ng-if="IsPaid == 'Yes'" >
                        <input name="EntryFee" ng-model="custom.EntryFee" type="text" ng-init="custom.EntryFee = '0'" placeholder="0" class="form-control numeric" value="0" maxlength="40" ng-if="IsPaid == 'No'">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label">Contest Size</label>
                         <select id="IsPaid" ng-model="custom.ContestSize" name="ContestSize" class="form-control">
                            <option value="">Please Select</option>
                            <option value="{{SportsGame.Owners}}" ng-repeat="SportsGame in getSportsGame">{{SportsGame.Owners}}</option>

                        </select>
<!--                        <input name="ContestSize" ng-model="custom.ContestSize" type="numeric" class="form-control integer" min="4" max="8" value="4">-->
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label">Prize Pool</label>
                        <input name="WinningAmount" ng-model="custom.WinningAmount" placeholder="Winning Amount" type="text" class="form-control numeric" value="0" ng-if="IsPaid == 'Yes'" readonly="">
                        <input name="WinningAmount" ng-model="custom.WinningAmount" ng-init="custom.WinningAmount = '0'" placeholder="Winning Amount" type="text" class="form-control numeric" value="0" ng-if="IsPaid == 'No'" >
                    </div>
                </div>

                <input name="EntryType" ng-model="EntryType" type="hidden" value="Single">
                <!--                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Entry Type</label>
                                        <select id="EntryType" name="EntryType" ng-model="EntryType = 'Single'" class="form-control chosen-select">
                                            <option value="Single" >Single</option>
                                        </select>
                                        <small>Select option to notify if contest is single.</small>
                                    </div>
                                </div>-->

                <!-- <div class="col-md-3" ng-if="EntryType=='Multiple'" ng-init="UserJoinLimit=0">
                        <div class="form-group">
                                <label class="control-label">No. of users to join contest</label>
                                <input name="UserJoinLimit" ng-model="UserJoinLimit" placeholder="League Join Limit" type="text" class="form-control numeric" value="0" maxlength="5">
                        </div>
                </div> -->

                <input name="ContestType" ng-model="ContestType" type="hidden" value="Normal">
                <!--                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Contest Type</label>
                                        <select id="ContestType" name="ContestType" ng-model="ContestType = 'Normal'" class="form-control chosen-select">
                                            <option value="Normal">Normal</option>
                                        </select>
                                        <small>Select option to notify contest type.</small>
                                    </div>
                                </div>-->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Confirm Contest</label>
                                        <select id="IsConfirm" name="IsConfirm" ng-model="IsConfirm" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <small>Select option to notify contest is confirm contest or not.</small>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Auto Create</label>
                                        <select id="IsAutoCreate" name="IsAutoCreate" ng-model="IsAutoCreate" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <small>Select option to auto create same contest.</small>
                                    </div>
                                </div>
               <!--  <input name="IsConfirm" ng-model="IsConfirm" type="hidden" value="Yes"> -->
                <input name="ShowJoinedContest" ng-model="ShowJoinedContest" type="hidden" value="Yes">
                <input name="MinimumUserJoined" ng-model="MinimumUserJoined" type="hidden" value="1">
                <!--                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Show Joined Users</label>
                                        <select id="ShowJoinedContest" name="ShowJoinedContest" ng-model="ShowJoinedContest" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <small>Select option to notify joined user in contests.</small>
                                    </div>
                                </div>-->
                                <div class="col-md-3" ng-if="IsConfirm == 'No'">
                                    <div class="form-group">
                                        <label class="control-label">Minimum User Joined Required</label>
                                        <input name="MinimumUserJoined" ng-model="MinimumUserJoined" type="text"  placeholder="0" class="form-control numeric" value="">
                                    </div>
                                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Customize Winnings</label>
                        <!-- <input type="checkbox" ng-model="custom.winnings" ng-click="customizeWin()" > -->
                    </div>
                </div>
            </div>
            <div class="row" >
                <div class="col-md-10">
                    <div class="form-group">
                        <input type="text" class="form-control" ng-model="custom.NoOfWinners"  name="NoOfWinners" ng-change="changeWinners()" >
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <a href="javascript:void(0)" class="btn btn-primary btn-sm" ng-click="Showform()" >Set</a>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div ng-show="showField" class="creatcontast_list">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Winning %</th>
                                    <th>Winning Amount</th>
                                    <th><button class="btn btn-submit" type="button" ng-click="addField()" >+</button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="r in custom.choices">
                                    <td>
                                        <table>
                                            <tr>
                                                <td>
                                                    {{number}}

                                                    <label>From</label><select class="form-control" ng-model="r.From" ng-options="number for number in r.numbers" disabled="true"></select>
                                                </td>
                                                <td>
                                                    <label>To</label><select class="form-control"  ng-init="DataForm.To = r.numbers[0]" ng-change="changePercent($index)" ng-model="r.To" ng-options="number for number in r.numbers"></select>
                                                </td>
                                            </tr>
                                        </table> 


                                    </td>
                                    <td>
                                        <label>Percent</label> <input type="text" ng-model="r.percent" name="percent" class="form-control" ng-change="changePercent($index)" valid-number>
                                    </td>
                                    <td><label>Amount</label> <input type="text" class="form-control" ng-model="r.amount"></td>
                                    <td><button type="button" class="btn btn-submit" ng-click="removeField($index)">-</button></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                    <div style="color:red" ng-show="percent_error">*Percent field is required</div>
                    <div style="color:red" ng-show="calculation_error">*{{calculation_error_msg}}</div>
                </div>
            </div>

        </form>
        <hr>
        <button type="submit" class="btn btn-success btn-sm"  ng-click="addData()">Save</button>
    </div>




</div><!-- Body/ -->