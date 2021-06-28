<?php include('header.php'); ?>
<section class="burger common_bg " ng-controller="myAccountController" ng-init="getAccountInfo(true)" ng-cloak>
    <div class="container">
        <div class="shadow_box">
            <div class="mx-md-5 mx-3">
                <div class="profile_meta text-center mb-lg-5 mb-4">
                    <span class="profile_img"><img ng-src="{{profileDetails.ProfilePic}}" on-error-src="assets/img/default.jpg" alt="" class="rounded-circle"></span>
                    <h6 class="themeClr text_captilize"> {{profileDetails.FirstName}}</h6>
                    <a href="mailto:{{profileDetails.Email}}" class="text-muted">{{profileDetails.Email}}</a>
                    <!-- <p class="mt-3 text-danger">Wallet or Deposit amount can not be more than ${{profileDetails.MaximumDepositLimit}}.</p> -->
                </div>	
                <div class="row">
                    <div class="col-md-4 pr-md-0">
                        <div class="payment_item">
                            <h6>Total Cash</h6>
                            <h5 class="themeClr">{{moneyFormat(profileDetails.TotalCash)}}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 pr-md-0">
                        <div class="payment_item">
                            <h6>Cash Available</h6>
                            <h5 class="themeClr">{{moneyFormat(profileDetails.WalletAmount)}}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 ">
                        <div class="payment_item">
                            <h6>Cash Won</h6>
                            <h5 class="themeClr">{{moneyFormat(profileDetails.WinningAmount)}}</h5>
                        </div>
                    </div>
                </div>
                <div class="text-center myAccountBtns">
                    <a href="javascript:void(0);" ng-click="openPopup('add_money');" class="btn_secondary mr-2"><i class="far fa-money-bill-alt mr-2"></i> Add Cash</a>
                    <a href="javascript:void(0);" ng-click="openPopup('withdrawPopup')" class="btn_primary"><i class="fas fa-credit-card mr-2"></i> Withdraw Cash</a>
                </div>
            </div>
        </div>
        <div class="shadow_box">
            <div class="mx-md-5 ">
                <h5 class="themeClr text-center mb-4">Transaction  History </h5>
                <ul class="nav nav-pills mb-4 border_tabs px-3 px-md-0" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="{{activeTab=='transaction' ? 'active' : '' }}" id="pills-home-tab" data-toggle="pill" ng-click="ChangeTab('transaction');" href="javascript:void(0)" role="tab" aria-controls="pills-home" aria-selected="true">Cash Transaction</a>
                    </li>
                    <li class="nav-item">
                        <a class="{{activeTab=='withdrawal' ? 'active' : '' }}" id="withdrawal" data-toggle="pill" ng-click="ChangeTab('withdrawal');" href="javascript:void(0)" role="tab" aria-controls="pills-profile" aria-selected="false">Cash Withdraw </a>
                    </li>
                </ul>
                <div class="tab-content px-3 px-md-0 " id="pills-tabContent">
                    <div class="tab-pane fade {{activeTab=='transaction' ? 'active show' : '' }}" id="transaction" role="tabpanel" aria-labelledby="pills-home-tab">
                        <div class="table-responsive tableResponsive">
                            <table class="table table-borderless table_scroll myAccountTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th>Opening Balance</th>
                                        <th>Cr.</th>
                                        <th>Dr.</th>
                                        <th>Available Balance </th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody class="dfs_custom_scroll"  scrolly>
                                    <tr ng-repeat="transactionDetails in transactions" ng-if="TotalTransactionCount > 0" >

                                        <td>{{transactionDetails.TransactionID}}</td>
                                        <td>{{(transactionDetails.Narration == 'Join Contest Winning')?'Contest Winnings':transactionDetails.Narration}}</td>
                                        <td>{{transactionDetails.Status}}</td>
                                        <td>
                                            {{ moneyFormat(transactionDetails.OpeningBalance)}}
                                        </td>
                                        <td>
                                            {{ transactionDetails.TransactionType == 'Cr' ? moneyFormat(transactionDetails.Amount) : moneyFormat(0.00)}}</td>
                                        <td>
                                            {{ transactionDetails.TransactionType == 'Dr' ? moneyFormat(transactionDetails.Amount) : moneyFormat(0.00)}}</td>
                                        <td>
                                            {{ moneyFormat(transactionDetails.ClosingBalance)}}
                                        </td>
                                        <td>{{transactionDetails.EntryDate | myDateFormat}}</td>
                                    </tr>
                                    <tr ng-if="TotalTransactionCount == 0" >
                                        <td colspan="8" class="text-center">No transactions found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade {{activeTab=='withdrawal' ? 'active show' : '' }}" id="withdrawal" role="tabpanel" aria-labelledby="pills-profile-tab">
                        <table class="table table-borderless table_scroll">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Payment Source</th>
                                    <th>Amount</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="dfs_custom_scroll"  scrolly>
                                <tr ng-repeat="transactionDetails in WithdrawTransactions" ng-if="TotalWithdrawTransactionCount > 0">
                                    <td>{{transactionDetails.PaymentGateway}}</td>
                                    <td>{{moneyFormat(transactionDetails.Amount)}}</td>
                                    <td>{{transactionDetails.EntryDate| myDateFormat}}</td>
                                    <td>{{transactionDetails.Status}}</td>
                                </tr>
                                <tr ng-if="TotalWithdrawTransactionCount == 0" >
                                    <td colspan="4" class="text-center">No transactions found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--transaction message modal -->
<div class="modal fade centerPopup site_sm_modal" popup-handler id="TransactionModal" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true" >
    <div class="modal-dialog custom_popup small_popup">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title">Message</h4>
            </div>
            <div class="modal-body clearfix comon_body ammount_popup">
                <div class="form-group">
                    <div class="form-group">
                        <p class="text-center">{{TransactionMessage}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('innerFooter.php'); ?>
