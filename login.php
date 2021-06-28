<?php include('header.php'); ?>

<section class="login_signup_wrapr burger " ng-controller="homeController"  ng-cloak>
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-4 col-md-8 offset-md-2 offset-lg-4">
        <form id="signin" name="signin" ng-submit="signIn(signin)" novalidate="" autocomplete="false" class="site_form login_form">
          <h1 class="themeclr">Sign in</h1>
          <div class="form-group">
            <img src="assets/img/user.svg" alt="user" class="img-fluid">
            <div class="field_group">
              <label for="">User Name/Email</label>
              <input type="text" placeholder="Username/Email" name="username" ng-model="loginData.Username" ng-required="true">
              <span></span>
            </div>                
            <div style="color:red" ng-show="LoginSubmitted && (signin.username.$error.required || !signin.username.$valid)" class="form-error">
              *Username/Email is required.
            </div>
          </div>
          <div class="form-group">
            <img src="assets/img/padlock.svg" alt="user" class="img-fluid">
            <div class="field_group">
              <label for="">Password</label>
              <input type="password"  placeholder="Password" name="Password" ng-model="loginData.Password" ng-change="removeMassage()" ng-required="true">
              <span></span>
            </div>
            <div style="color:red" ng-show="LoginSubmitted && (signin.Password.$error.required || !signin.Password.$valid)" class="form-error">
                *Password is required.
            </div>
          </div>
          <div class="text-right"><a href="javascript:void(0);" data-toggle="modal" data-target="#forgotPassword" data-dismiss="modal" class="themeClr">Forgot Password?</a></div>
          <div class="from_btn_wrapr">
            <input type="submit" value="Login" class="btn_primary mt-4 mb-3 w-100">
          </div>
          <div class="text-center">Don’t have an account? <a href="signup" class="themeClr">Register</a></div>
        </form>
      </div>
    </div>
  </div>

  <!--forgotpaasword-->
    <div class="modal fade centerPopup site_sm_modal" id="forgotPassword" popup-handler>
        <div class="modal-dialog custom_popup small_popup">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header text-center">
                        <button type="button" class="close" data-dismiss="modal">×</button>
                        <h4 class="modal-title w-100">Forget Your Password?</h4>
                    </div>
                <div class="modal-body clearfix comon_body ammount_popup">
                    <form class="form_commen" id="forgotPasswordForm" name="forgotPasswordForm" ng-submit="sendEmailForgotPassword(forgotPasswordForm)" novalidate="">
                        <div class="form-group">
                            <label>No worries! Enter your email below and we’ll send you a recovery otp.</label>
                            <input placeholder="Email" class="form-control" name="Keyword" type="text" ng-model="forgotPasswordData.Keyword" ng-required="true" >
                            <div style="color:red" ng-show="forgotEmailSubmitted && (forgotPasswordForm.Keyword.$error.required || !forgotPasswordForm.Keyword.$valid)" class="form-error" >
                                *Email is required.
                            </div>
                        </div>
                        <div class="button_right text-center">
                            <button class="btn_sm_primary">SEND</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- verify forgot password -->
    <div class="modal fade centerPopup site_sm_modal" id="verifyForgotPassword" popup-handler data-backdrop="static" data-keyboard="false" >
        <div class="modal-dialog custom_popup small_popup">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4 class="modal-title w-100">Forget Your Password?</h4>
                </div>
                <div class="modal-body clearfix comon_body ammount_popup">
                    <form class="form_commen" name="verifyforgotPassword" ng-submit="verifyForgotPassword(verifyforgotPassword)" novalidate="">
                        <div class="form-group">
                            <label>No worries! Enter your OTP below sent to your registered email.</label>
                            <input placeholder="One Time Password" name="opt" ng-model="forgotPassword.OTP" numbers-only class="form-control" type="text" ng-required="true">
                            <div style="color:red" ng-show="forgotPasswordSubmitted && (verifyforgotPassword.opt.$error.required || !verifyforgotPassword.opt.$valid)" class="form-error">
                                *OTP is required.
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" ng-model="forgotPassword.Password" placeholder="New Password" class="form-control" ng-change="removeMassage()"  ng-pattern="/^(?=.*[0-9])(?=.*[A-Z])([a-zA-Z0-9@_%]+)$/" ng-minlength="6" ng-required="true" >
                            <div style="color:red" ng-show="forgotPasswordSubmitted && verifyforgotPassword.password.$error.required" class="form-error">
                                *Password is required.
                            </div>
                            <div style="color:red" ng-show="verifyforgotPassword.password.$error.pattern || verifyforgotPassword.password.$error.minlength" class="form-error">*Password must have one capital, one number and 6 character long.
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="password" compare-to="forgotPassword.Password" ng-model="confirmPass" name="confirmPass" placeholder="Confirm New Password" class="form-control" ng-required="true" ng-change="removeMassage()">
                            <div style="color:red" ng-show="forgotPasswordSubmitted && verifyforgotPassword.confirmPass.$error.required" class="form-error">
                                *Confirm password is required.
                            </div>
                            <div style="color:red" ng-show="!verifyforgotPassword.confirmPass.$error.required && verifyforgotPassword.confirmPass.$error.compareTo">
                                *Your password must match.
                            </div>
                        </div>
                        <div class="button_right text-center">
                            <button class="btn_sm_primary">SUBMIT</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('footerHome.php')?>
