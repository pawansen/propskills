<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2" >
		<span class="float-left records d-none d-sm-block">
			<span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
		</span>
	</div>
	<!-- Top container/ -->


	<!-- Data table -->
	<div class="table-responsive block_pad_md" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 
		<!-- loading -->
		<p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>

		<!-- data table -->
		<table class="table table-striped table-condensed table-hover table-sortable" ng-if="data.dataList.length">
			<!-- table heading -->
			<thead>
				<tr>
					<th>Contest Name</th>
					<th style="width: 100px;">Contest Type</th>
					<th class="text-center" style="width: 200px;">Contest Joined</th>
					<th class="text-center" style="width: 100px;">Contest Size</th>
					<th class="text-center" style="width: 100px;" class="text-center">Fee</th>
					<th class="text-center" style="width: 100px;" class="text-center">No. of Winners</th>
					<th class="text-center" style="width: 100px;" class="text-center">Winning Amount</th>
					<th style="width: 100px;" class="text-center">Match Date</th>
					<th style="width: 100px;" class="text-center">Status</th>
					<th style="width: 100px;" class="text-center">Action</th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">



				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">
				
					<td>
						<div class="content float-left"><strong>{{row.ContestName}}</strong>
							<div ng-if="row.TeamNameLocal">({{row.TeamNameLocal}} v/s {{row.TeamNameVisitor}})</div><div ng-if="!row.TeamNameLocal">-</div>
						</div>
					</td>
					<td>
						<p>{{!row.ContestType ? '-' : row.ContestType }}</p>
					</td>
					
					<td class="text-center">
						<p>{{row.TotalJoined}}</p>
					</td>

					<td class="text-center" >
						<p>{{row.ContestSize}}</p>
					</td>
					
					<td class="text-center" >
						<p>{{row.EntryFee}}</p>
					</td>
					
					<td class="text-center" >
						<p>{{row.NoOfWinners}}</p>
					</td>
					<td class="text-center" >
						<p>{{row.WinningAmount}}</p>
					</td>
					<td>
						<p>{{row.MatchStartDateTime}}</p>
					</td>
					<td class="text-center"><span ng-class="{Pending:'text-danger', Running:'text-success',Cancelled:'text-danger',Completed:'text-success'}[row.Status]">{{row.Status}}</span></td> 

					<td class="text-center" ng-if="row.TotalJoined > 0">
						<div class="dropdown" >
							<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8230;</button>
							<div class="dropdown-menu dropdown-menu-left">
								<a class="dropdown-item" href="" ng-click="loadFormList(key, row.ContestGUID)">Participated Teams</a>
							</div>
						</div>
					</td>
					<td class="text-center" ng-if="row.TotalJoined == 0">
						-
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
		<div class="modal-dialog modal-md" role="document">
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
	<div class="modal fade" id="list_model">
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