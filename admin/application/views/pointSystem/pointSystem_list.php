<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>
<div class="panel-body" ng-controller="PageController" ng-init="getList()"><!-- Body -->

	<!-- Top container -->
<!-- 	<div class="clearfix mt-2 mb-2">
		
			<div class="form-group float-right">
			  	<select class="form-control" ng-model="PointsCategory" ng-change="getList()">
					<option value="Normal">Normal</option>
					<option value="InPlay">InPlay</option>
					<option value="Reverse">Reverse</option>
				</select>
		  </div>				
		
	</div> -->
	<!-- Top container/ -->


	<!-- Data table -->
	<div class="table-responsive block_pad_md" > 
		<!-- loading -->
		<p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>
		<form method="post" id="generalPoint_form" name="generalPoint_form"  autocomplete='off'>
		<!-- data table for General Points -->
		<table class="table table-striped table-condensed table-hover table-sortable" ng-show="data.dataList.length > 0">
			<!-- table heading -->
			<thead>
				<tr>
					<th >Type of Points (OFFENSIVE)</th>
					<th style="width: 200px;">Points</th>
	<!-- 				<th style="width: 200px;">ODI</th>
					<th style="width: 200px;" >TEST</th> -->
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				
					<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.PointsTypeGUID}}" ng-if="row.PointsTypeShortDescription=='OFFENSIVE'">
					
						<td>
							<strong>{{row.PointsTypeDescprition}}</strong>
						</td>
						<td>
							<input type="text" class="form-control numeric " name="Points[]" ng-model="pointSystem[0].Points" ng-value="{{row.Points | number : 2 }}" >
										<input type="hidden" name="PointsTypeGUID[]" value="{{row.PointsTypeGUID}}">
						</td>
					</tr>
				
			</tbody>
		</table>
		<button class="btn btn-success btn-sm float-right" ng-click="updateGeneralPoints()" >	Submit</button>
		</br>
		</form>
		</br>
		<!-- data table for Bonus Point -->
		<form method="post" id="bonusPoint_form" name="bonusPoint_form"  autocomplete='off'>
		<table class="table table-striped table-condensed table-hover table-sortable" ng-show="data.dataList.length > 0">
			<!-- table heading -->
			<thead>
				<tr>
					<th style="width:700px;" >Type of Points (DEFENSIVE)</th>
					<th style="width: 200px;">Points</th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.PointsTypeGUID}}" ng-if="row.PointsTypeShortDescription=='DEFENSIVE'">
				
					<td>
						<strong>{{row.PointsTypeDescprition}}</strong>
					</td>
					<td>
						<input type="text" class="form-control numeric " name="Points[]" ng-model="pointSystem[0].Points" ng-value="{{row.Points | number : 2 }}" ng-keyup="ResetTimers(row.PointsTypeGUID,'Points',Points)">
						<input type="hidden" name="PointsTypeGUID[]" value="{{row.PointsTypeGUID}}">
					</td>
					<td>
					
				</tr>
			</tbody>
		</table>
		<button class="btn btn-success btn-sm float-right" ng-click="updateBonusPoint()" >	Submit</button>
		</br>
		</form>
		</br>
		<!-- data table for Economy Rate -->
		<form method="post" id="economyRate_form" name="economyRate_form"  autocomplete='off'>
		<table class="table table-striped table-condensed table-hover table-sortable" ng-show="data.dataList.length > 0">
			<!-- table heading -->
			<thead>
				<tr>
					<th style="width: 700px;" >Type of Points (KICKING)</th>
					<th style="width: 200px;">Points</th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.PointsTypeGUID}}" ng-if="row.PointsTypeShortDescription=='KICKING'">
				
					<td>
						<strong>{{row.PointsTypeDescprition}}</strong>
					</td>
					<td>
						<input type="text" class="form-control numeric " name="Points[]" ng-model="pointSystem[0].Points" ng-value="{{row.Points | number : 2 }}" ng-keyup="ResetTimers(row.PointsTypeGUID,'Points',Points)">
						<input type="hidden" name="PointsTypeGUID[]" value="{{row.PointsTypeGUID}}">
					</td>
				</tr>
			</tbody>
		</table>
		<button class="btn btn-success btn-sm float-right" ng-click="updateEconomyRate()" >	Submit</button>
		</br>
		</form>
		</br>
		<!-- no record -->
		<p class="no-records text-center" ng-if="data.noRecords">
			<span ng-if="data.dataList.length">No more records found.</span>
			<span ng-if="!data.dataList.length">No records found.</span>
		</p>
	</div>
	<!-- Data table/ -->


</div><!-- Body/ -->