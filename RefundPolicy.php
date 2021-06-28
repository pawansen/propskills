<?php include('header.php');?>
    <!--Main container sec start-->
    <div class="mainContainer about-us">
        <div class="comonBg">
            <div class="privacypolicySec">
                <div class="container burger text-white" ng-init='getPage("refund")'>
                    <div class="comman_heading">
                        <h2>{{content.Title}} </h2>
                    </div>
                    <div class="site_content_wrapr">
                      <div ng-bind-html='Content'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Main container sec end-->
<?php include('footerHome.php');?>