<?php include('header.php'); ?>
<section class="burger common_bg teamNamePage" ng-cloak>
    <div class="container">
        <div class="shadow_box px-3 px-lg-0">
            <form name="createContestForm" ng-submit="(profileDetails.AllowPrivateContestFree == 'No')?check_balance_amount(createContestForm):CheckCreateContestForm(createContestForm)" novalidate="" autocomplete="off">
                <div class="guter">
                    <div class="row mt-5">
                        <div class="col-sm-12">
                            <div class="form-group teamName icon">
                            <div class="icons"><i class="fa fa-bars"></i></div>
                                <label for="">League Name</label>
                                <input type="text" name="" class="" placeholder="$ 100 Wildcast Special [$25 to 1st]">
                            </div>
                            <div class="text-right">
                                <p class="character">max 60 characters</p>
                            </div>
                        </div>
                    </div>

                    <div class="selectTeamLogo">
                        <h6>Choose Your Team Logo</h6>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="logo-box">
                                    <img src="assets/img/teamLogo-1.png" alt="">
                                    <div class="checkbox">
                                        <input class="styled-checkbox" name="checkbox" id="styled-checkbox-2" type="checkbox">
                                        <label for="styled-checkbox-2"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="logo-box">
                                    <img src="assets/img/teamLogo-2.png" alt="">
                                    <div class="checkbox">
                                        <input class="styled-checkbox" name="checkbox" id="styled-checkbox-3" type="checkbox">
                                        <label for="styled-checkbox-3"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="logo-box">
                                    <img src="assets/img/teamLogo-3.png" alt="">
                                    <div class="checkbox">
                                        <input class="styled-checkbox" name="checkbox" id="styled-checkbox" type="checkbox">
                                        <label for="styled-checkbox"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="logo-box select_logo-box">
                                    <img src="assets/img/plus-sign.png" alt="">
                                    <apan class="mt-2">Add Your Image</span>
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1"></label>
                                        <input type="file" class="form-control-file" id="exampleFormControlFile1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button class="btn_secondary cursor_pointer">CANCEL</button>
                        <button class="btn_trans_dark cursor_pointer">CONTINUE</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<?php include('innerFooter.php'); ?>    
