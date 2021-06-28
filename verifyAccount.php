<?php include('header.php'); ?>
<!--Main container sec start-->
<div class="mainContainer" ng-controller="myAccountController" ng-cloak >
    <div class="common_bg py-5">
    	<div class="container-fluid">
	       	 <div class="row">
                <div class="col-md-10 offset-md-1 ">
                    <div class="innerPageWrapr">
                        <div class="innerHeader">
                            <h4>Verify Your Account</h4>
                        </div>
                        <div class="innerPageContent">
                            <div class="veryfy_condition">
                                <h6>After this, enjoy 1- click cash withdrawals forever! </h6>

                                <ul class="list-unstyled">
                                    <li>1. This info is needed for W-9 tax reasons and to verify age.</li>
                                    <li>2. Max size 4MB. Formats - .jpg .jpeg .png .pdf only.</li>
                                    <li>3. We don’t accept password-protected docs.</li>
                                </ul>
                                <ul class="list-unstyled veryfy_icn">
                                    <li class="red"><i class="fa fa-info-circle" aria-hidden="true"></i> Pending</li>
                                    <li class="green"><i class="fa fa-check-circle" aria-hidden="true"></i> Verified
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-content">
                                <div class="tab-pane active" id="aadharCard">
                                    <form ng-submit="uploadLicenseDetails(licenseDetailsForm, licenseImage)"  name="licenseDetailsForm" enctype="multipart/form-data" novalidate="" class="veryfy_form">
                                        <div class="form-group dis_status p-0">
                                            <strong>License/ID Card Status :</strong> <span ng-class="{Pending:'text-danger', Verified:'text-success',Rejected:'text-danger'}[Details.PanStatus]">{{Details.PanStatus}}</span>  
                                        </div>
                                        <div class="row" >
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="">Full Legal Name</label>
                                                    <input type="text" class="form-control" name="LegalName" ng-disabled="Details.PanStatus == 'Verified' || Details.PanStatus == 'Pending'" ng-required="true" ng-model="LicenseDetails.LegalName" >
                                                    <div ng-show="LicenseSubmitted && licenseDetailsForm.LegalName.$error.required" class="text-danger form-error">
                                                        *Full Legal Name is required.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="">Social Security Number/EIN</label>
                                                    <input type="text" class="form-control" name="ssn" ng-disabled="Details.PanStatus == 'Verified' || Details.PanStatus == 'Pending'" ng-required="true" ng-model="LicenseDetails.SocialSecurityNumber" ng-pattern="/^(?!000|666)[0-8][0-9]{2}(?!00)[0-9]{2}(?!0000)[0-9]{4}$/">
                                                    <div ng-show="LicenseSubmitted && licenseDetailsForm.ssn.$error.required" class="text-danger form-error">
                                                        *Social Security Number is required.
                                                    </div>
                                                    <div  ng-show="LicenseSubmitted && licenseDetailsForm.ssn.$error.pattern" class="text-danger form-error">
                                                        *Enter valid Social Security Number.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 customCheckbox">
                                                <div class="form-group">
                                                    <label class="">U.S. Citizen Status <small class="themeClr">(a YES or NO answer to whether or not the user is a U.S. Citizen or Green Card holder.)</small></label>
                                                    <div class="d-flex custom-control custom-radio custom-control-inline">
                                                        <input type="radio" id="customRadioInline1" class="custom-control-input" ng-disabled="Details.PanStatus == 'Verified' || Details.PanStatus == 'Pending'" name="CitizenStatus" value="Yes" ng-model="LicenseDetails.CitizenStatus">
                                                        <label class="custom-control-label" for="customRadioInline1">Yes</label>
                                                    </div>
                                                    <div class="custom-control custom-radio custom-control-inline">
                                                        <input type="radio" id="customRadioInline2" class="custom-control-input" ng-disabled="Details.PanStatus == 'Verified' || Details.PanStatus == 'Pending'" name="CitizenStatus" value="No" ng-model="LicenseDetails.CitizenStatus"> 
                                                        <label class="custom-control-label" for="customRadioInline2">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group custom_filetype">
                                                    <label>Upload License/ID Card</label>
                                                    <div class="input-file-container">
                                                        <input class="input-file" id="my-aadhar" type="file" ngf-select ngf-accept="'image/*'" ng-model="licenseImage" name="file" ng-disabled="Details.PanStatus == 'Verified' || Details.PanStatus == 'Pending'" ng-required="true" onchange="angular.element(this).scope().SelectLicenseFile(event)">
                                                    <div class="licenseImg">
                                                            <img ng-src="{{LicenseImage}}" ng-if="LicenseImage != ''" alt="" />
                                                        </div>
                                                    </div>
                                                    <div ng-show="LicenseSubmitted && licenseDetailsForm.file.$error.required" class="text-danger form-error">
                                                        *License/ID Card image is required.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 veryfy-label">
                                                    <button type="submit" class="btn_primary" ng-if="Details.PanStatus != 'Verified' && Details.PanStatus != 'Pending'" >Submit For Verification</button>
                                                    <label class="btn btn-success" ng-if="Details.PanStatus == 'Pending'" >License Submitted</label>
                                                    <label class="btn btn-success" ng-if="Details.PanStatus == 'Verified'" >License/ ID Card details verified</label>
                                            </div>
                                        </div>
                                    </form>
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