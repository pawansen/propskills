<!--withdraw money modal-->
<div class="modal fade centerPopup site_sm_modal" popup-handler id="withdrawPopup" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true" >
    <div class="modal-dialog custom_popup small_popup">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"> Withdraw Funds </h4>
            </div>
            <div class="modal-body clearfix comon_body ammount_popup" ng-init="mode = 'Bank'">
                <form name="withdrawForm" novalidate="true">
                    <div class="form-group">
                        <label>How much you would like to withdraw.</label>
                        <input placeholder="$50" class="form-control numeric" name="amount" type="text" ng-model="WinningAmount"  ng-required="true"  >
                        <div style="color:red" ng-show="withdrawSubmitted && withdrawForm.amount.$error.required" class="form-error">
                            *Amount is Required.
                        </div>
                    </div>
                    </div>
                    <div class="form-group" >
                        <div class="button_right text-center" >
                            <button class="btn_primary text-center" ng-click="withdrawRequest(withdrawForm, WinningAmount, mode)">Withdraw</button>
                        </div>
                        <ul class="mt-2">
                            <li>* Minimum withdrawal amount is ${{profileDetails.MinimumWithdrawalLimitBank}}.</li>
                            <li>* Only amount from winnings can be withdrawn.</li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>