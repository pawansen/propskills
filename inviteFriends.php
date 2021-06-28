<?php include('header.php'); ?>
<div class="mainContainer" ng-controller="inviteController" ng-cloak ng-init="getContest()">
    <div class="common_bg py-5">
    	<div class="container-fluid">
	       	 <div class="col-md-10 offset-md-1 ">
	            <div class="innerPageWrapr">
                    <div class="innerHeader">
                        <h4>Invite Your Friend's</h4>
                    </div>
                    <div class="innerPageContent">
                        <div class="mb-4 text-center">
                            <h5 class="themeClr d-block">League Invite Code</h5>
                            <span class="invite_code">{{Contest.UserInvitationCode}}</span>
                            <input type="hidden" name="code" id="inviteCode" value={{Contest.UserInvitationCode}}>
                        </div>
                        <div class="mb-4 text-center">
                            <label for="">Share this on </label>
                            <div class="border_social">
                                <a href="javascript:;" class="facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="javascript:;" class="twitter" data-js="twitter-share"><i class="fab fa-twitter"></i></a>
                                <a href="https://api.whatsapp.com/send?text=Play with me on Fandom Royale. Click https://www.FandomRoyale.com/ to login to the portal and Use contest code: {{Contest.UserInvitationCode}} to join the contest." target="_blank"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="row align-items-end">
                                <div class="offset-md-3 col-md-6 pr-md-0">
                                    <label for="">Email to Friend</label>
                                    <form name="inviteFriendEmail" ng-submit="sendEmailInvitation(inviteFriendEmail)" novalidate="" autocomplete="off" class=" invite_form">
                                        <input type="text" name="email" ng-model="email" class="form-control" placeholder="Enter Invite Friend Email" ng-pattern="/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/" ng-required="true">
                                        <div ng-show="submitted && inviteFriendEmail.email.$error.required" class="form-error text-danger">
                                            *Email is required.
                                        </div>
                                        <div ng-show="submitted && inviteFriendEmail.email.$error.pattern" class="form-error text-danger">
                                            *Please enter valid email.
                                        </div>
                                        <button ><i class="fas fa-at"></i></button>    
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

<?php include('innerFooter.php'); ?>    
<script type="text/javascript">
$(document).ready(function(){
    // $('.facebook').click( function(){
    //     var shareurl = $(this).data('shareurl');
    //     window.open('https://www.facebook.com/dialog/feed?app_id=2261225134126697&name=FandomRoyale&caption=Play with me on Fandom Royale. Click https://www.FandomRoyale.com/ to login to the portal and Use contest code: '+$("#inviteCode").val()+' to join the contest.', 'Fantasy', 
    //     'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
    //     return false;
    // });

    var twitterShare = document.querySelector('[data-js="twitter-share"]');
    twitterShare.onclick = function(e) {
        e.preventDefault();    
        var twitterWindow = window.open("https://twitter.com/intent/tweet?text=Play with me on Fandom Royale. Click https://www.FandomRoyale.com/ to login to the portal and Use contest code: "+$("#inviteCode").val()+" to join the contest.", 'twitter-popup', 'height=350,width=600');
        if(twitterWindow.focus) { twitterWindow.focus(); }
            return false;
    }
});
</script>