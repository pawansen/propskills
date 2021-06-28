<?php include('header.php');?>
    <!--Main container sec start-->
    <div class="mainContainer">
        <div class="comonBg">
            <div class="conditionSec">
                <div class="container burger text-white" ng-init='getPage("terms")'>
                    <div class="comman_heading">
                        <h2>{{content.Title}}</h2>
                    </div>
                <div class="site_content_wrapr ">
                    <div ng-bind-html='Content'></div>
                    <style>
                        p.text-lowercase:first-letter{
                            text-transform: uppercase;
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>
    <!--Main container sec end-->
<?php include('footerHome.php');?>