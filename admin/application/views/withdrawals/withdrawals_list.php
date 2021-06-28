<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>
<div class="panel-body" ng-controller="PageController" ><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2">
		<span class="float-left records hidden-sm-down">
			<span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
		</span>

		<div class="float-right">
			<form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
				<input type="text" class="form-control" name="Keyword" placeholder="Search">
			</form>
		</div>
		<div class="float-right">
			<button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model">Filter</button>&nbsp;
			
		</div>
	</div>
	<!-- Top container/ -->



	<!-- Data table -->
	<div class="table-responsive block_pad_md" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 

		<!-- loading -->
		<p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>
		<form name="records_form" id="records_form">
			<!-- data table -->
			<table class="table table-striped table-hover" ng-if="data.dataList.length">
				<!-- table heading -->
				<thead>
					<tr>
						<!-- <th style="width: 50px;" class="text-center" ng-if="data.dataList.length>1"><input type="checkbox" name="select-all" id="select-all" class="mt-1" ></th> -->	
						<th style="width: 300px;min-width:200px;">User</th>
						<th style="width: 100px;min-width:200px;">Amount</th>
						<th>Payment</th>
						<th style="width: 120px;">Status</th>
						<th style="width: 120px;">Entry Date</th>
						<th style="width: 100px;" class="text-center">Action</th>

					</tr>
				</thead>
				<!-- table body -->
				<tbody>
					<tr scope="row" ng-repeat="(key, row) in data.dataList">

						<td class="listed sm clearfix">
							<span >{{row.FirstName}}</span>
							<div ng-if="row.Email"><a href="mailto:{{row.Email}}" target="_top">{{row.Email}}</a></div><div ng-if="!row.Email">-</div>
							
						</td> 
						<td>
							<span ng-if="row.Amount">{{row.Amount}}</span><span ng-if="!row.Amount">-</span>
							
						</td> 
						<td>
							<span ng-if="!row.MediaBANK.MediaCaption.FullName">-</span><br>
							<span ng-if="row.MediaBANK.MediaCaption.FullName"> Name : {{row.MediaBANK.MediaCaption.FullName}}</span><br>
							<span ng-if="row.MediaBANK.MediaCaption.Bank"> Bank : {{row.MediaBANK.MediaCaption.Bank}}</span>
							<span ng-if="!row.MediaBANK.MediaCaption.FullName">-</span><br>
							<span ng-if="row.MediaBANK.MediaCaption.AccountNumber"> A/C : {{row.MediaBANK.MediaCaption.AccountNumber}}</span>
							<span ng-if="!row.MediaBANK.MediaCaption.AccountNumber">-</span><br>
							<span ng-if="row.MediaBANK.MediaCaption.IFSCCode"> IFSC : {{row.MediaBANK.MediaCaption.IFSCCode}}</span>
							<span ng-if="!row.MediaBANK.MediaCaption.IFSCCode">-</span>
						</td>
						<td>
							<span ng-if="row.EntryDate">{{row.EntryDate}}</span><span ng-if="!row.EntryDate">-</span>
						</td> 
						<td>
							<span ng-if="row.Status" ng-class="{Pending:'text-danger', Verified:'text-success',Rejected:'text-danger'}[row.Status]">{{row.Status}}</span><span ng-if="!row.Status">-</span>
						</td>
						<td class="text-center">
							<div class="dropdown">
								<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ng-if="data.UserGUID!=row.UserGUID">&#8230;</button>
								<div class="dropdown-menu dropdown-menu-left">
									<a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.WithdrawalID)">Action</a>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<!-- no record -->
		<p class="no-records text-center" ng-if="data.noRecords">
			<span ng-if="data.dataList.length">No more records found.</span>
			<span ng-if="!data.dataList.length">No records found.</span>
		</p>
	</div>
	<!-- Data table/ -->
	
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
										<label class="filter-col" for="Status">Status</label>
										<select id="Status" name="Status" class="form-control chosen-select">
											<option value="">Please Select</option>
											<option value="Verified">Verified</option>
											<option value="Pending">Pending</option>
											<option value="Deleted">Deleted</option>
											<option value="Blocked">Blocked</option>
											<option value="Hidden">Hidden</option>
										</select>   
									</div>
								</div>
							</div>

						</div> <!-- form-area /-->
					</div> <!-- modal-body /-->

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm1').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
						<button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
					</div>

				</form>
				<!-- Filter form/ -->
			</div>
		</div>
	</div>

	<!-- edit Modal -->
	<div class="modal fade" id="edit_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Withdrawal Request</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<!-- form -->
				<form id="edit_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
				</form>
				<!-- /form -->
			</div>
		</div>
	</div>

</div><!-- Body/ -->