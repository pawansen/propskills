<?php
include('header.php');
?>
<!--Main container sec start-->
<div class="mainContainer about-us" >
    <div class="container burger text-white" ng-init='getPage("about")'>
        <div class="comman_heading">
            <h2 class="">{{content.Title}}  </h2>
        </div>
        <div class="site_content_wrapr">
            <div ng-bind-html='Content'></div>
            <p><a class="text-white" style="text-decoration: underline;"  href="mailto:Fr.support@hypo-igames.com">Fr.support@hypo-igames.com</a></p>
        </div>
    </div>
</div>
<?php include('footerHome.php'); ?>

