<!--addmoney-->
<div class="modal fade site_sm_modal" popup-handler id="add_money" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true" >
    <div class="modal-dialog ">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add funds to Your Account</h5>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body ">
                <form ng-submit="selectPaymentMode(amount,addCashForm)" name="addCashForm" novalidate="true">
                    <div class="row align-items-end">
                        <div class="col-sm-6">
                            <label>How much you would like to add to join contest</label>
                            <input placeholder="Enter amount." class="form-control numeric" name="amount" type="text" ng-model="amount" numbers-only  ng-required="true" ng-readonly="isPromoCode">
                            <div style="color:red" ng-show="cashSubmitted && addCashForm.amount.$error.required" class="form-error">
                                *Amount is Required
                            </div>
                            <div class="text-danger" ng-if="errorAmount">{{errorAmountMsg}}</div>
                        </div>
                        <div class="col-sm-6 add_cash_btns pr-0">
                            <label>Add More Cash</label>
                            <ul class="list-unstyled mb-0">
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(100)"  >$ 100</button></li>
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(200)"  >$ 200</button></li>
                                <li><button type="button" class="btn_sm_primary" ng-click="addMoreCash(500)" >$ 500</button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="">
                            <!-- <p>*Wallet or Deposit amount can not be more than ${{profileDetails.MaximumDepositLimit}}.</p> -->
                            <!-- <input class="custom-control-input" id="customCheck1" type="checkbox" name="promoCode" ng-model="isPromoCode" ng-click="resetPromo(isPromoCode)" > -->
                             <!-- <label class="custom-control-label" for="customCheck1"> Have a promo code.</label> -->
                        </div>
                    </div>
                    <div class="input-group form-group" ng-if="isPromoCode && !PromoCodeFlag">
                        <input type="text" class="form-control" name="promocode" ng-model="PromoCode">
                        <div class="input-group-append">
                            <a href="javascript:void(0)" class="btn_sm_primary" ng-click="applyPromoCode(PromoCode,amount)" >Apply</a>
                        </div>
                    </div>
                    <div class="promocodeList" ng-if="isPromoCode" >
                    <p ng-if="PromoCodeFlag" class="h6"><span>Coupon Code </span>: {{PromoCode}} <a href="javascript:void(0)" ng-click="removeCoupon()"><i class="fa fa-trash"></i></a></p>
                    <p class="h6" ng-if="GotCashBonus>0"><span>Cash Bonus </span>: ${{GotCashBonus}}</p>
                    </div>
                    <div class="text-center"><!-- href="paymentMethod?amount={{amount}}" -->
                        <button class="btn_secondary" >ADD CASH</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--addmoney