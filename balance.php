<!--addmoney-->
<div class="modal fade centerPopup site_sm_modal" popup-handler id="add_more_money" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true" >
    <div class="modal-dialog custom_popup small_popup">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h5 class="modal-title">Low Balance</h5>
            </div>
            <div class="modal-body p-4">
                <div class="row text-center ">
                    <div class="col">
                        <strong>Current Balance </strong>
                        <h5 class="themeClr mt-1" > {{moneyFormat(profileDetails.TotalCash)}}</h5>
                    </div>
                    <div class="col border-left">
                        <strong>Joining Amount </strong>
                        <h5 class="themeClr mt-1" > {{moneyFormat(ContestInfo.EntryFee)}}</h5>
                    </div>
                </div>
                <hr>
                <!-- ng-submit="selectPaymentMode(amount,addCashForm)" -->
                <form  name="addCashForm" novalidate="true" ng-submit="selectPaymentMode(amount,addCashForm)" class="">
                    <div class="row add_more_cash">
                        <div class="col-sm-6 form-group">
                            <label>Add cash to your account</label>
                            <input placeholder="Enter amount." class="form-control numeric" name="amount" type="text" ng-model="amount" numbers-only  ng-required="true" >
                            <div style="color:red" ng-show="cashSubmitted && addCashForm.amount.$error.required" class="form-error">
                                *Amount is Required
                            </div>
                            <div class="text-danger" ng-if="errorAmount">{{errorAmountMsg}}</div>
                        </div>
                        <div class="col-sm-6 add_cash_btns pr-0">
                            <label>Add More Cash</label>
                            <ul class="list-unstyled mb-0">
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(100)">$100</button></li>
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(200)">$200</button></li>
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(500)">$500</button></li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <button class="btn_secondary" >ADD CASH</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--addmoney