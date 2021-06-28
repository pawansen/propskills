<?php include('header.php'); ?>
<section class="login_signup_wrapr burger " ng-controller="homeController" ng-init="getStates()" ng-cloak>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2 ">
                <form class="site_form" id="signup" name="signup" autocomplete="off" ng-submit="signUp(signup)" novalidate="">
                    <h1 class="themeclr">Sign up</h1>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/user.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Full Name</label>
                                    <input type="text" name="fullName" placeholder="Full Name" ng-model="formData.FullName" ng-change="removeMassage()" ng-pattern="/^[a-zA-Z\s]*$/" ng-required="true" class="text_captilize">
                                    <span></span>
                                </div>								
                                <div ng-show="signupSubmitted && signup.fullName.$error.required" class="form-error text-danger">
                                    *Full Name is required.
                                </div>
                                <div ng-show="signup.fullName.$error.pattern" class="form-error text-danger">
                                    *Please enter valid full name.
                                </div>
                            </div>
                        </div>	
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/user.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">User Name</label>
                                    <input type="text" placeholder="Username" name="username" ng-model="formData.Username" ng-minlength="5" autocomplete="off" ng-maxlength="16" ng-required="true">
                                    <span></span>
                                </div>								
                                <div ng-show="signupSubmitted && signup.username.$error.required" class="form-error text-danger">
                                    *Username is required.
                                </div>
                                <div ng-show="signup.username.$error.minlength" class="form-error text-danger">
                                    *Username should be minimum 5 character long.
                                </div>
                                <div ng-show="signup.username.$error.maxlength" class="form-error text-danger">
                                    *Username couldn't be more than 16 character long.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/envelope.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Email</label>
                                    <input type="text" placeholder="Enter Your Email" name="email" ng-model="formData.Email" ng-change="removeMassage()" ng-pattern="/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/" ng-required="true">
                                    <span></span>
                                </div>
                                <div ng-show="signupSubmitted && signup.email.$error.required" class="form-error text-danger">
                                    *Email is required.
                                </div>
                                <div ng-show="signup.email.$error.pattern" class="form-error text-danger">
                                    *Please enter valid email.
                                </div>								
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/envelope.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Confirm Email</label>
                                    <input type="text" placeholder="Confirm Your Email" name="confirmEmail" compare-to="formData.Email" ng-model="confirmEmail" ng-change="removeMassage()" ng-required="true">
                                    <span></span>
                                </div>
                                <div ng-show="signupSubmitted && signup.confirmEmail.$error.required" class="form-error text-danger">
                                    *Confirm Email is required.
                                </div>
                                <div class="form-error text-danger" ng-show="!signup.confirmEmail.$error.required && signup.confirmEmail.$error.compareTo">
                                    *Your Email must match.
                                </div>							
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/padlock.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Password</label>
                                    <input type="password"  placeholder="Password" name="password" ng-model="formData.Password" ng-change="removeMassage()"  ng-pattern="/^(?=.*[0-9])(?=.*[A-Z])([a-zA-Z0-9@_%]+)$/" ng-minlength="6" ng-required="true">
                                    <span></span>
                                </div>								
                                <div ng-show="signupSubmitted && signup.password.$error.required" class="form-error text-danger">
                                    *Password is required.
                                </div>
                                <div ng-show="signup.password.$error.pattern || signup.password.$error.minlength" class="form-error text-danger">
                                    *Password must have one capital, one number and 6 character long.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/padlock.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Confirm Password</label>
                                    <input type="password" name="confrim_password" placeholder="Confirm Password" compare-to="formData.Password" ng-model="confrim_password" ng-change="removeMassage()" ng-required="true">
                                    <span></span>
                                </div>
                                <div ng-show="signupSubmitted && signup.confrim_password.$error.required" class="form-error text-danger">
                                    *Confirm Password is required.
                                </div>
                                <div class="form-error text-danger" ng-show="!signup.confrim_password.$error.required && signup.confrim_password.$error.compareTo">
                                    Your password must match.
                                </div>
                            </div>
                        </div>
                        
                    </div>
                   <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/address.png" alt="state" class="img-fluid">
                                <div class="field_group">
                                    <label for="">State/Province (only eligible states/provinces listed)</label>
                                    <select name="State" ng-model="formData.StateName"  id="selectpicker">
                                        <option value="">Select State</option>
                                        <option ng-repeat="state in stateList" value="{{state.StateName}}">{{state.StateName}}</option>
                                    </select>
                                    <span></span>
                                </div>
                                <!-- <div ng-show="signupSubmitted && (signup.state.$error.required || !signup.state.$valid)" class="form-error">
                                    *State is required.
                                </div> -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <img src="assets/img/calendar.svg" alt="user" class="img-fluid">
                                <div class="field_group">
                                    <label for="">Birth Date</label>
                                    <div class="dropdown  dropdown-start-parent">
                                        <span class="dateTime_field">
                                            <input type="text" name="DOB" id="dropdownStart" ng-required="true"
                                                placeholder="DOB" role="button" data-toggle="dropdown"
                                                data-target=".dropdown-start-parent" class="form-control"
                                                value="{{formData.BirthDate | date:'MM-dd-yyyy'}}" readonly></span>
                                            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                                <datetimepicker data-ng-model="formData.BirthDate"
                                                    data-datetimepicker-config="{ dropdownSelector: '#dropdownStart', renderOn: 'end-date-changed',startView:'day', minView:'day' }"
                                                    data-on-set-time="startDateOnSetTime()"
                                                    data-before-render="startDateBeforeRender($dates)"></datetimepicker>
                                            </ul>
                                    </div>
                                    <span></span>
                                </div>
                                <div ng-show="signupSubmitted && signup.BirthDate.$error.required" class="form-error text-danger">
                                    *Birth date is required.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 form-group checkbox_custom justify-content-center d-flex flex-column align-items-center">
                        <div class="custom-control custom-checkbox customCheckbox agree_txt">
                            <input type="checkbox" ng-required="true" name="agree" ng-model="formData.isagree" class="custom-control-input" id="termsCheck">
                            <label class="custom-control-label" for="termsCheck"> I agree to Fantasy </label>
                            <a href="TermsAndConditions" target="_blank" class="themeClr">T&amp;C's </a>
                        </div>
                        <div class="form-error text-danger" ng-show="signupSubmitted && signup.agree.$error.required">*You must need to agree with condition.</div>
                    </div>
                    <div class="from_btn_wrapr text-center">
                        <input type="submit" value="Sign Up" class="btn_primary mt-4 mb-3 px-5">
                    </div>
                    <div class="text-center">Already have an account? <a href="login" class="themeClr">Sign in</a></div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php include('footerHome.php') ?>