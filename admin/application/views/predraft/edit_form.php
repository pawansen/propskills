<header class="panel-heading">
    <h1 class="h4">Contest Game Templates Edit</h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->


    <div class="form-area" ng-init="loadDatepicker();loadFormEdit();">

        <form id="edit_form" name="edit_form" autocomplete="off" >

            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Game Play Type</label>
                        <p class="text-primary">{{formData.GamePlayType}}</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Sports</label>
                        <p class="text-primary">{{formData.GameType}}</p>
                    </div>
                </div>

<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">League Type</label>
                        <p class="text-primary">{{formData.LeagueType}} Play</p>
                    </div>
                </div> -->

<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Sports Game Type</label>
                        <p class="text-primary">{{formData.SubGameType}}</p>
                    </div>
                </div> -->
                <input name="SubGameType" ng-model="SubGameType" type="hidden">    
<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Scoring Type</label>
                        <select id="IsPaid" ng-model="formData.ScoringType" name="ScoringType" class="form-control">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="PointLeague" ng-selected="formData.ScoringType == 'PointLeague' ? 'true' : 'false'">Point League</option>
                        </select>
                    </div>
                </div> -->

<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">PlayOff</label>
                        <select id="IsPaid" ng-model="formData.PlayOff" name="PlayOff" class="form-control">
                            <option value="" disabled selected="">Please Select</option>
                            <option value="No" ng-selected="formData.PlayOff == 'No' ? 'true' : 'false'">No</option>
                            <option value="Yes" ng-selected="formData.PlayOff == 'Yes' ? 'true' : 'false'">Yes</option>
                        </select>
                    </div>
                </div> -->
                
<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Week Start</label>
                        <select id="IsPaid" ng-model="formData.WeekStart" name="WeekStart" class="form-control" ng-change="getWeekDate(ContestDuration,WeekStart,SeriesGUID)">
                            <option value="">Please Select</option>
                            <option ng-repeat="(i, Week) in WeekArray" ng-selected="(i == formData.WeekStart ? true : false)" value="{{i}}">{{Week}}</option>
                        </select>
                    </div>
                </div> -->

<!--                 <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Contest Duration</label>
                        <select id="ContestDuration" ng-model="formData.ContestDuration" name="ContestDuration" class="form-control chosen-select" ng-change="getWeekDate(ContestDuration,WeekStart,SeriesGUID)">
                            <option value="">Please Select</option>
                            <option value="Daily">Day</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                        <small>Select this option contest points calculate weekly basic OR days basic.</small>
                    </div>
                </div> -->
                <input name="ContestDuration" ng-model="ContestDuration" type="hidden" value="Daily">    
<!--                 <div class="col-md-3" ng-if="formData.ContestDuration=='Daily'">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Day Date</label>
                        <select id="DailyDate" ng-model="formData.DailyDate" name="DailyDate" class="form-control" >
                            <option value="">Please Select</option>
                            <option ng-repeat="Value in DailyDateResponse" ng-selected="(formData.DailyDate == Value.MatchStartDateTime ? true : false)" value="{{Value.MatchStartDateTime}}">{{Value.MatchStartDateTime}}</option>
                        </select>
                    </div>
                </div> -->

<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Week End</label>
                        <select id="IsPaid" ng-model="formData.WeekEnd" name="WeekEnd" class="form-control">
                            <option value="">Select</option>
                             <option ng-repeat="Week in WeekArray" ng-if="WeekStart <= Week" value="{{Week}}">{{Week}}</option>    
                        </select>
                    </div>
                </div> -->

<!--                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">League Join Date </label>
                        <input name="LeagueJoinDateTime" id="LeagueJoinDateTime" readonly="" ng-model="formData.LeagueJoinDateTime" type="text" class="form-control" value="{{formData.LeagueJoinDateTime}}">
                        <small>Set league join date time on which time user can create a team.</small>
                    </div>
                </div> -->
<!--                 <div class="col-md-3">
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">Contest Name</label>
                        <input name="ContestName" ng-model="formData.ContestName" type="text" class="form-control" placeholder="Contest Name" value="{{formData.ContestName}}"  maxlength="40">
                    </div>
                </div>




                <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Is Paid Contest?</label>
                        <select id="IsPaid" ng-model="formData.IsPaid" name="IsPaid" class="form-control">
                            <option value="">Please Select</option>
                            <option value="Yes" ng-selected="formData.IsPaid == 'Yes' ? 'true' : 'false'" >Yes</option>
                            <option value="No" ng-selected="formData.IsPaid == 'No' ? 'true' : 'false'"  >No</option>

                        </select>
                        <small>Select this option notifiy that contest is free or paid.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">Admin Charges (%)</label>
                        <input name="AdminPercent" ng-model="custom.AdminPercent" placeholder="Admin Charges in Percentage" type="text" class="form-control numeric" maxlength="3" value="{{formData.AdminPercent}}">
                    </div>
                </div>
<!--                <div class="col-md-3" ng-if="formData.IsPaid == 'Yes'" ng-init="CashBonusContribution = 0" >
                    <div class="form-group">
                        <label class="control-label">Cash Bonus Contribution (%)</label>
                        <input name="CashBonusContribution" ng-model="formData.CashBonusContribution" placeholder="Cash Bonus Contribution in Percentage" type="text" class="form-control numeric" value="0" maxlength="3" ng-if="formData.IsPaid == 'Yes'">
                    </div>
                </div>-->
<!--                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Contest Format</label>
                        <select id="ContestFormat" ng-model="formData.ContestFormat" name="ContestFormat" class="form-control chosen-select">
                            <option value="">Please Select</option>
                            <option value="Head to Head" ng-selected="formData.ContestFormat == 'Head to Head' ? 'active' : ''">Head to Head</option>
                            <option value="League" ng-selected="formData.ContestFormat == 'League' ? 'active' : ''">League</option>
                        </select>
                        <small></small>
                    </div>
                </div>-->

                <div class="col-md-4" ng-if="formData.IsPaid == 'Yes'" >
                    <div class="form-group">
                        <label class="control-label">Entry Fee</label>
                        <input name="EntryFee" ng-model="custom.EntryFee" type="text" placeholder="0" class="form-control numeric" value="{{EntryFee}}" maxlength="40" ng-if="formData.IsPaid == 'Yes'" >
                        <input name="EntryFee" ng-model="custom.EntryFee" type="text" placeholder="0" ng-init="custom.EntryFee = '0'" class="form-control numeric" value="{{EntryFee}}" maxlength="40" ng-if="formData.IsPaid == 'No'" >
                    </div>
                </div>

<!--                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Entry Type</label>
                        <select id="EntryType" name="EntryType" ng-model="formData.EntryType" class="form-control chosen-select">
                            							<option value="">Please Select</option>
                            <option value="Single" ng-selected='formData.EntryType == "Single" ? "true" : "false"'>Single</option>
                            							<option value="Multiple" ng-selected='formData.EntryType=="Multiple" ? "true" : "false" '>Multiple</option>
                        </select>
                        <small>Select yes for multiple and no for single.</small>
                    </div>
                </div>-->
<!--                <div class="col-md-3" ng-if="formData.EntryType == 'Multiple'">
                    <div class="form-group">
                        <label class="control-label">No. of users to join contest</label>
                        <input name="UserJoinLimit" ng-model="formData.UserJoinLimit" placeholder="League Join Limit" type="text" class="form-control numeric" value="0" maxlength="5">
                    </div>
                </div>-->

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">Contest Size</label>
<!--                        <input name="ContestSize" ng-model="custom.ContestSize" type="text" class="form-control integer"  >-->
                        
                         <select id="IsPaid" ng-model="custom.ContestSize" name="ContestSize" class="form-control integer">
                            <option value="">Please Select</option>
                            <option value="{{SportsGame.Owners}}" ng-repeat="SportsGame in getSportsGame">{{SportsGame.Owners}}</option>

                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">Winning Amount</label>
                        <input name="WinningAmount" ng-model="custom.WinningAmount" placeholder="Winning Amount" type="text" class="form-control numeric" value="{{formData.WinningAmount}}" >
                    </div>
                </div>
                <input type="hidden" name="PreContestID" value="{{formData.PreContestID}}">
<!--                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Contest Type</label>
                        <select id="ContestType" name="ContestType" ng-model="formData.ContestType" class="form-control chosen-select">
                            							<option value="">Please Select</option>
                            <option value="Normal" ng-selected='formData.ContestType == "Normal" ? "true" : "false"' >Normal</option>
                            							<option value="Reverse" ng-selected='formData.ContestType=="Reverse" ? "true" : "false" ' >Reverse</option>
                                                                                    <option value="InPlay" ng-selected='formData.ContestType=="InPlay" ? "true" : "false" ' >InPlay</option>
                        </select>
                        <small>Select yes for multiple and no for single.</small>
                    </div>
                </div>-->
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Confirm Contest</label>
                        <select id="IsConfirm" name="IsConfirm" ng-model="formData.IsConfirm" class="form-control">
                            <option value="">Please Select</option>
                            <option value="Yes" ng-selected='formData.IsConfirm == "Yes" ? "true" : "false"' >Yes</option>
                            <option value="No" ng-selected='formData.IsConfirm == "No" ? "true" : "false"' >No</option>
                        </select>
                    </div>
                </div>
                                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Auto Create</label>
                                        <select id="IsAutoCreate" name="IsAutoCreate" ng-model="IsAutoCreate" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-selected='formData.IsAutoCreate == "Yes" ? "true" : "false"' value="Yes">Yes</option>
                                            <option ng-selected='formData.IsAutoCreate == "No" ? "true" : "false"' value="No">No</option>
                                        </select>
                                        <small>Select option to auto create same contest.</small>
                                    </div>
                                </div>
<!--                <div class="col-md-3">
                    <div class="form-group">
                        <label class="filter-col" for="ParentCategory">Show Joined Users</label>
                        <select id="ShowJoinedContest" name="ShowJoinedContest" ng-model="formData.ShowJoinedContest" class="form-control chosen-select">
                            <option value="">Please Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                        <small>Select option to notify joined user in contests.</small>
                    </div>
                </div>-->

         <!--        <div style="color:red" ng-show="winningamount_error">*Please enter winning amount first.</div> -->
                <div class="col-md-3" ng-if="formData.IsConfirm == 'No'">
                    <div class="form-group">
                        <label class="control-label">Minimum User Joined Required</label>
                        <input name="MinimumUserJoined" type="text" ng-model="formData.MinimumUserJoined" class="form-control integer"  value="{{formData.MinimumUserJoined}}">						
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group" ng-if="formData.IsPaid == 'Yes'" >
                        <label class="filter-col" for="ParentCategory">Customize Winnings</label>
                        <!-- <input type="checkbox" ng-model="custom.winnings" ng-click="customizeWin()" > -->
                    </div>
                </div>
            </div>
            <div class="row" >
                <div class="col-md-10">
                    <div class="form-group">
                        <input type="text" class="form-control" name="NoOfWinners" ng-model="custom.NoOfWinners" ng-change="changeWinners()" >
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <a href="javascript:void(0)" class="btn btn-secondary btn-sm" ng-click="Showform()" >Set</a>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="creatcontast_list">
                        <table style="width: 100%; ">
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
                                                    <label>From</label><select class="form-control" ng-model="r.From" ng-options="number for number in r.numbers" ng-selected="" disabled="true"></select>
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
        <button type="submit" class="btn btn-success btn-sm" ng-disabled="addDataLoading" ng-click="editData()">Save</button>
    </div>




</div><!-- Body/ -->