<?php include('header.php'); ?>

<section class="burger common_bg " ng-controller="profileController" ng-cloak>
    <div class="container" ng-init="getProfileInfo();">
        <div class="shadow_box">
            <div class="profile_page guter">
            <div class="profile_meta text-center mb-5">
                <span class="profile_img">
                    <form name="fileUpload" enctype="multipart/form-data" id="fileUpload">
                        <label for="file"><i class="fas fa-plus"></i>
                            <input ngf-select ngf-accept="'image/*'" ng-change="pictureFile(picFile,'')" ng-model="picFile" name="file" id="file" type="file" class="d-none">
                        </label>
                    </form>
                    <img ng-src="{{profileDetails.ProfilePic}}" ng-if="profileDetails.ProfilePic" on-error-src="assets/img/default.jpg" alt="profile" class="rounded-circle">
                </span>
                
                <h6 class="text_captilize">{{profileDetails.FullName}}</h6>

                <!-- <a href="javascript:;" class="themeClr">EDIT PROFILE</a> -->
                <!-- <aside class="mutedClr">RANK</aside>
                <h4 class="themeClr font-weight-bold mt-1">5th</h4> -->
            </div>  
                <div class="profile_header">
                    <div class="row align-items-center">
                        <!-- <div class="col-6 col-md-2 col-sm-3 py-sm-0">
                            <h5 class="themeClr mb-0 ">{{moneyFormat(profileDetails.TotalCash)}}</h5>
                            <label for="">Balance</label>
                        </div>   -->
                        <div class="col-md-3 col-sm-3">
                            <a href="javascript:void(0);" ng-click="openPopup('add_money');"  class="btn_secondary"><i class="far fa-money-bill-alt mr-2"></i> Add Cash</a>
                        </div>
                        <div class="col-md-3 offset-md-6 text-lg-right">
                            <a href="javascript:void(0);"  ng-click="openPopup('withdrawPopup')" class="btn_primary"><i class="fas fa-credit-card mr-2"></i>Withdraw</a></li>
                        </div>
                    </div>  
                </div>
                <form class="profile_form" name="userform" ng-submit="updateProfile(userform)" novalidate="">
                    <div class="row form-group align-items-center">
                        <div class="col-md-3">
                            <label for="">Name</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control " name="name" ng-model="profileDetails.FirstName" ng-required="true">
                            <span></span>
                            <div  ng-show="submitted && userform.name.$error.required" class="text-danger form-error">
                                *Name is required.
                            </div>
                        </div>
                    </div>
                    <div class="row form-group align-items-center">
                        <div class="col-md-3">
                            <label for="">Team Name</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="teamName" ng-model="profileDetails.UserTeamCode" ng-required="true" minlength="6" maxLength="16">
                            <span></span>
                            <div  ng-show="submitted && userform.teamName.$error.required" class="text-danger form-error">
                                *Team Name is required.
                            </div>
                            <div  ng-show="submitted && userform.teamName.$error.minlength" class="text-danger form-error">
                                *Team Name should be minimum 6 character long.
                            </div>
                        </div>
                    </div>
                    <div class="row form-group align-items-center">
                        <div class="col-md-3">
                            <label for="">Email</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="email" ng-model="profileDetails.Email" readonly="true">
                            <span></span>
                        </div>
                    </div>
                    <div class="row form-group align-items-center">
                        <div class="col-md-3">
                            <label for="">Birth Date</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" placeholder="BirthDate" ng-model="profileDetails.BirthDate" name="BirthDate" readonly="true">
                            <span></span>
                        </div>
                    </div>
                    <div class="row form-group align-items-center">
                        <div class="col-md-3">
                            <label for="">State</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" placeholder="State" ng-model="profileDetails.StateName" name="StateName" >
                            <span></span>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <button class="btn_sm_primary btn_sm_border btncol-md-3">SAVE</button>
                    </div>
                </form> 
            </div>
        </div>
    </div>

</section>
<?php include('innerFooter.php'); ?>