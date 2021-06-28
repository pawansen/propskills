<!--Footer sec start-->
<footer class="pb-3 site-footer bg_secondary">
  <div class="container">
    <div class="row pt-5 pb-4">
        <div class="col-lg-6 col-md-7">
            <div class="row">
                <div class="col-sm-4">
                    <h6>quick links</h6>
                    <ul class="footer_menu list-unstyled ">
                        <li><a href="AboutUs">About Fandom Royal </a></li>
                        <li><a href="contactUs">Contact Us</a></li>
                        <li><a href="Rules&info" >Rules & Info </a></li>
                    </ul>
                </div>
                <div class="col-sm-4">
                    <h6>Support</h6>
                    <ul class="footer_menu list-unstyled">
                        <li><a href="RefundPolicy" >Refund Policy </a></li>
                        <li><a href="TermsAndConditions">Terms & Condition</a></li>
                        <li><a href="privacyPolicy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-sm-4">
                    <h6>News & Resources</h6>
                    <ul class="footer_menu list-unstyled">
                        <li><a href="javascript:;">ESPN</a></li>
                        <li><a href="javascript:;">NFL</a></li>
                        <li><a href="javascript:;">NBA</a></li>
                        <li><a href="javascript:;">MLB</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4 offset-lg-2 col-md-5">
            <h6>Stay In Touch With US</h6>
            <div class="social_menu ">
                <a href="javascript:;"><i class="fab fa-facebook-f"></i></a>
                <a href="javascript:;"><i class="fab fa-twitter"></i></a>
                <a href="javascript:;"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="btm_footer text-center">
        <span class="copyright">Copyright © {{Date | date:'y'}}  All Rights Reserved. </span>
    </div>
  </div>
</footer>
<div loading class="loader_wrapr" id="loderBG">
    <div class="pre_loader"><span></span><span></span></div>
</div>

<!--Footer sec end-->
</main>
<add-cash></add-cash>
<add-more-cash></add-more-cash>
<add-withdrawal-request></add-withdrawal-request>
<script src="assets/js/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/6.26.0/polyfill.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/i18n/defaults-*.min.js"></script>

<!-- load angular -->
<script src="assets/js/angular-modules/angular.min.js"></script>
<!-- angular storage -->
<script src="assets/js/angular-modules/ngStorage.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular-cookies.js"></script>
<!-- MAIN CONTROLLER -->
<script src="assets/js/app.js?version=<?= $VERSION ?>"></script>

<script type="text/javascript">
    var base_url = "<?php echo $base_url;?>";
    var UserGUID, UserTypeID, ParentCategoryGUID = '';
    app.constant('environment', {
        base_url: "<?php echo $base_url;?>",
        api_url: "<?php echo $api_url;?>",
        image_base_url: '<?php echo $base_url; ?>assets/img/',
        brand_name: 'fandomroyale'
    });
    app.config(function(socialProvider){
        // socialProvider.setGoogleKey("760247147566-mt9iqjkc4jbjiidd9oj48s7bku07lmt3.apps.googleusercontent.com");
        // socialProvider.setFbKey({appId: "299723280854076", apiVersion: "v2.11"});
    });
</script>
<!-- common service -->
<script src="assets/js/services/database.fac.js?version=<?= $VERSION ?>"></script>
<!-- common directive -->
<script src="assets/js/directive/design-directive.lib.js?version=<?= $VERSION ?>"></script>
<!-- helper -->
<script src="assets/js/helper/helper.js?version=<?= $VERSION ?>"></script>
<!-- social ligin library -->
<script src="assets/js/angularjs-social-login/angularjs-social-login.js"></script>
<!-- home controller -->
<script src="assets/js/controllers/profile.js?version=<?= $VERSION ?>"></script>
<!-- file upload -->
<script src="assets/js/jquery.form.js"></script>
<!-- Angular animate js -->
<script src="assets/js/angular-animate.min.js"></script>
<!-- header controller -->
<script src="assets/js/controllers/header.js?version=<?= $VERSION ?>"></script>
<!-- home controller -->
<script src="assets/js/controllers/home.js?version=<?= $VERSION ?>"></script>

<script src="assets/js/angular-modules/ng-file-upload-master/dist/ng-file-upload.min.js"></script> 

<script src="assets/js/angular-modules/ng-file-upload-master/dist/ng-file-upload-shim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>

<script src="assets/js/slick.min.js"></script>
<script src="assets/js/jquery.hover-slider.js"></script>
<script src="assets/js/wow.min.js"></script>
<script src="assets/js/custom.js?version=<?= $VERSION ?>"></script>
<script src="assets/js/sweetalert.min.js"></script>
<script type="text/javascript" src="draft/node_modules/angularjs-bootstrap-datetimepicker/src/js/datetimepicker.js"></script>
<script type="text/javascript" src="draft/node_modules/angularjs-bootstrap-datetimepicker/src/js/datetimepicker.templates.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-sanitize/1.5.7/angular-sanitize.min.js"></script>

</body>

</html>


