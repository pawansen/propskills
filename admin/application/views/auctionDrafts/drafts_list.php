<header class="panel-heading">
  <h1 class="h4">Contest Games</h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2" >
		<span class="float-left records d-none d-sm-block">
			<span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
		</span>
		<div class="float-right">
			<button ng-if="data.dataList.length" class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model">Filter</button>
<!--			<button class="btn btn-success btn-sm ml-1" ng-click="loadFormAdd();">Add Contest</button>-->
            <button class="btn btn-success btn-sm ml-1" ng-click="loadFormAddContest();">Add Contest Game</button>
		</div>
		<div class="float-right">
			<button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()">Reset</button>&nbsp;
		</div>
	</div>
	<!-- Top container/ -->

	<nav>
        <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#" role="tab" aria-controls="nav-home" aria-selected="true" ng-click="applyFilter('Running');">Running</a>
            <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#" role="tab" aria-controls="nav-profile" aria-selected="false" ng-click="applyFilter('Pending');">Pending</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#" role="tab" aria-controls="nav-contact" aria-selected="false" ng-click="applyFilter('Completed');">Completed</a>
            <a class="nav-item nav-link" id="nav-withdraw-tab" data-toggle="tab" href="#" role="tab" aria-controls="nav-withdraw" aria-selected="false" ng-click="applyFilter('Cancelled')">Cancelled</a>
        </div>
    </nav>
	<!-- Data table -->
	<div class="table-responsive block_pad_md" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 
		<!-- loading -->
		<p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>

		<!-- data table -->
		<table class="table table-striped table-condensed table-hover table-sortable" ng-if="data.dataList.length">
			<!-- table heading -->
			<thead>
				<tr>
		          <th style="width: 300px;">Game Play Day</th>
		          <th style="width: 100px;" class="sort" ng-click="applyOrderedList('SubGameType','DESC')">Game Play Type &nbsp;<span class="sort_deactive"></th>
<!--          <th style="width: 100px;">Game Type</th>-->
					<th style="width: 250px;" class="sort" ng-click="applyOrderedList('ContestName','DESC')">Season&nbsp;<span class="sort_deactive"></th>
				    <th style="width: 100px;">Sports Game Type</th>
					<th style="width: 100px;">Is Confirm?</th>
					<th style="width: 70px;">Is Paid?</th>
					<th style="width: 70px;" class="sort" ng-click="applyOrderedList('ContestSize','DESC')">Size &nbsp;<span class="sort_deactive"></th>
				<!-- 	<th style="width: 70px;">Minimum Joined Limit</th> -->
<!--					<th style="width: 70px;">Privacy</th>-->
					<th style="width: 70px;" class="text-center sort" ng-click="applyOrderedList('EntryFee','DESC')">Fee&nbsp;<span class="sort_deactive"></th>
      				<th style="width: 70px;" class="text-center">Admin Fee %</th>
					<!-- <th style="width: 70px;" class="text-center">Entry Type</th> -->
					<th style="width: 70px;" class="text-center"># Winners</th>
					<th style="width: 100px;" class="text-center sort" ng-click="applyOrderedList('WinningAmount','DESC')">Winning Amount&nbsp;<span class="sort_deactive"></th>
<!-- 					<th style="width: 100px;" class="text-center sort" ng-click="applyOrderedList('LeagueJoinDateTime','DESC')">Game Date&nbsp;<span class="sort_deactive"></th> -->
					<th style="width: 100px;" class="text-center">Total Joined</th>
					<th style="width: 100px;" class="text-center">Amount Received</th>
					<th style="width: 100px;" class="text-center">Winning Distributed</th>
			<!-- 		<th style="width: 100px;" class="text-center">Draft Status</th> -->
					<th style="width: 100px;" class="text-center">Game Status</th>
					<th style="width: 100px;" class="text-center">Action</th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">



				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">
				
<!--                                         <td ng-if="row.GameType == 'Ncaaf'">College Football</td>
					<td ng-if="row.GameType == 'Nfl'">Pro Football</td> -->
					<td>
							<div class="text-success">{{row.DailyDate}}({{row.DailyDate | date : 'EEE' }})</div>
					</td>  
                           <td>
						<p>{{row.GamePlayType}}</p>
					</td>             
                                        <td>
						<p>{{row.SubGameType}}</p>
					</td>
<!--                                        <td>
						<p>{{row.GameType}}</p>
					</td>-->
					<td>
						<div class="content float-left"><strong><a href="javascript:void(0)" ng-click="loadContestJoinedUser(key,row.ContestGUID)">{{row.ContestName}}</a></strong>
							<div ng-if="row.SeriesName">({{row.SeriesName}})</div>
						</div>
					</td>
					<td>
						<p>{{row.IsConfirm}}</p>
					</td>
					<td>
						<p>{{row.IsPaid}}</p>
					</td>
					<td>
						<p>{{row.ContestSize}}</p>
					</td>
<!-- 					<td>
						<p ng-if='row.IsConfirm=="No"'>{{row.MinimumUserJoined}}</p>
						<p ng-if='row.IsConfirm=="Yes"'>0</p>
					</td> -->
					<td>
						<p>${{row.EntryFee}}</p>
					</td>
                    <td>
						<p>{{row.AdminPercent > 0 ? row.AdminPercent : 0}}</p>
					</td>
<!-- 					<td>
						<p>{{row.EntryType}}</p>
					</td> -->
					<td>
						<p>{{row.NoOfWinners}}</p>
					</td>
					<td>
						<p>${{row.WinningAmount}}</p>
					</td>
<!-- 					<td>
						<p>{{row.Match.MatchStartDateTimeUTC}}</p>
					</td> -->
					<td class="text-center">
						<p>{{row.TotalJoined}}</p>
					</td>
					<td class="text-center">
						<p>${{row.TotalAmountReceived}}</p>
					</td>
					<td class="text-center">
						<p>${{row.TotalWinningAmount}}</p>
					</td>

<!-- 					<td class="text-center"><span ng-class="{Pending:'text-danger', Running:'text-info',Cancelled:'text-danger',Completed:'text-success'}[row.AuctionStatus]">{{row.AuctionStatus}}</span></td> -->


					<td class="text-center"><span ng-class="{Pending:'text-danger', Running:'text-success',Cancelled:'text-danger',Completed:'text-success'}[row.Status]">{{row.Status}}</span></td>
					<td class="text-center">
						<div class="dropdown">
							<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8230;</button>
							<div class="dropdown-menu dropdown-menu-left">
								<a class="dropdown-item" href="" ng-if="row.Status=='Pending'" ng-click="loadFormEditContest(key, row.ContestGUID)">Edit</a>
								<a class="dropdown-item" href="" ng-if="row.Status=='Pending'" ng-click="loadFormStatus(key, row.ContestGUID)">Status</a>
								<a class="dropdown-item" href="" ng-if="row.Status=='Pending'" ng-click="editStatus('Cancelled',row.ContestGUID,'delete')">Delete</a>
								<a class="dropdown-item" href="" ng-if="row.Status=='Cancelled'" ng-click="deleteData(row.ContestGUID)">Delete</a>
							</div>
						</div>
						<div class="dropdown" ng-if="row.Status!='Pending'">
							<span>-</span>
						</div>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- no record -->
		<p class="no-records text-center" ng-if="data.noRecords">
			<span ng-if="data.dataList.length">No more records found.</span>
			<span ng-if="!data.dataList.length">No records found.</span>
		</p>
	</div>
	<!-- Data table/ -->




	<!-- Filter Modal -->
	<div class="modal fade" id="filter_model"  ng-init="getFilterData()">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Filters</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>

				<!-- Filter form -->
				<form id="filterForm1" role="form" autocomplete="off" class="ng-pristine ng-valid">
					<div class="modal-body">
						<div class="form-area">
							<div class="row">
								<div class="col-md-8">
									<div class="form-group">
										<label class="filter-col" for="StatusID">Status</label>
										<select id="StatusID" name="StatusID" class="form-control chosen-select">
											<option value="">Please Select</option>
											<option value="2">Active</option>
											<option value="6">Inactive</option>
										</select>   
									</div>
								</div>
							</div>

						</div> <!-- form-area /-->
					</div> <!-- modal-body /-->

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
						<button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
					</div>

				</form>
				<!-- Filter form/ -->
			</div>
		</div>
	</div>



	<!-- add Modal -->
	<div class="modal fade" id="add_model">
		<div class="modal-dialog modal-md customContestModal  modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Add Contest</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLAdd"></div>
			</div>
		</div>
	</div>



	<!-- edit Modal -->
	<div class="modal fade" id="edit_model">
		<div class="modal-dialog modal-md customContestModal  modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Edit <?php echo $this->ModuleData['ModuleName'];?></h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLEdit"></div>
			</div>
		</div>
	</div>

	<!-- status Modal -->
	<div class="modal fade" id="status_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5"><?php echo $this->ModuleData['ModuleName'];?></h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLEdit"></div>
			</div>
		</div>
	</div>

	<!-- contest joined user Modal -->
	<div class="modal fade" id="contestJoinedUsers_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5"><?php echo $this->ModuleData['ModuleName'];?></h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLEdit"></div>
			</div>
		</div>
	</div>


	<!-- delete Modal -->
	<div class="modal fade" id="delete_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Delete <?php echo $this->ModuleData['ModuleName'];?></h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<!-- form -->
				<form id="edit_form" name="edit_form" autocomplete="off" ng-include="templateURLDelete">
				</form>
				<!-- /form -->
			</div>
		</div>
	</div>
</div><!-- Body/ -->