<?php include('header.php'); ?>
  <!--Main container sec start-->
  	<div class="mainContainer" ng-controller="headerController" ng-init="payPalReq()" ng-cloak >
	    <div class="burger common_bg">
	      	<div class="paymentSec">
		        <div class="container">
		        	<div class="row">
		        		<div class="col-md-6 offset-md-3 text-center ">
		        			<div class=" mb-4   text-white paymentBoxHeader">
			        			<h2>Select Payment Method</h2>
			        			<p>Pay for {{moneyFormat(amount)}}</p>
			        		</div>
		        			<div class="shadow_box p-0 overflow-hidden">
		        				<div class="bg_secondary text-white">
		        					<h5 class="text-center mb-0 py-3">Pay Via</h5>
		        				</div>			
						        <div class="paymentBox p-5">
						            <div class="paymentBody ">
				              		    <p>After clicking on the “Paypal” button, you will be directed to a secure gateway for payment.</p>
										 <div id="paypal-button"  class="my-4"></div>
										<!-- <br> -->
						                <!-- <button class="btn btn-submit" ng-click="payTmReq(amount)" >Paytm</button> -->
						                <p>By proceeding, you have read and agreed to Fandom Royale <a target="_blank" href="TermsAndConditions">Terms and Conditions</a> and <a target="_blank" href="privacyPolicy">Privacy Policy</a></p> 
						            </div>
						        </div>
					        </div>
					    </div>
		        	</div>
		       	</div>
	      	</div>
	    </div>
  	</div>
  <!--Main container sec end-->
<?php include('innerFooter.php'); ?>