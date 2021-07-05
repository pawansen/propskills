<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Utility_model');
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
        $this->load->model('Users_model');
        $this->load->model('nba/Sports_bsktbl_model');
    }


    /*
      Description: 	Cron jobs to get series data FOOTBALL.
      URL: 			/api/nba/utilities/getSeriesLive
     */

    public function getSeriesLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getSeriesLive');
        if (SPORTS_API_NFL) {
            $SeriesData = $this->Sports_football_model->getSeriesLiveNfl($CronID);
        }
        if (SPORTS_API_NCAAF) {
            $SeriesData = $this->Sports_football_model->getSeriesLiveNcaaf($CronID);
        }
        if (SPORTS_API_NFL_GOALSERVE) {
            //$SeriesData = $this->Sports_football_model->getSeriesLiveGoalServeNfl($CronID);
        }

        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get teams data FOOTBALL.
      URL: 			/api/nba/utilities/getTeamsLive
     */

    public function getTeamsLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getTeamsLive');
        if (SPORTS_API_NFL) {
            $TeamData = $this->Sports_football_model->getTeamsLiveNfl($CronID);
        }
        if (SPORTS_API_NCAAF) {
            $TeamData = $this->Sports_football_model->getTeamsLiveNcaaf($CronID);
        }
        if (SPORTS_API_NFL_GOALSERVE) {
            //$MatchesData = $this->Sports_football_model->getTeamsLiveNflGoalServe($CronID);
        }

        if (!empty($TeamData)) {
            $this->Return['Data'] = $TeamData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get matches data BASKETBALL. NEW CRON
      URL: 1			/api/nba/utilities/getMatchesLiveNba
     */

    public function getMatchesLiveNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('getMatchesLiveNba');
        if (SPORTS_API_NBA_GOALSERVE) {
            $MatchesData = $this->Sports_bsktbl_model->getMatchesLiveNbaGoalserve($CronID);
        }
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

        /*
      Description:  Cron jobs to get players data BASKETBALL. NEW CRON
      URL: 2      /api/nba/utilities/getPlayersLive
     */

    public function getPlayersLiveByTeamNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayersLiveByTeamNba');
        if (SPORTS_API_NFL_GOALSERVE) {
            $PlayersData = $this->Sports_bsktbl_model->getPlayersLiveByTeamNbaGoalserve($CronID);
        }
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to get players data BASKETBALL. NEW CRON
      URL: 3      /api/nba/utilities/getPlayersLiveByTeamInjuriesAndStatesNba
     */

    public function getPlayersLiveByTeamInjuriesAndStatesNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayersLiveByTeamInjuriesAndStatesNba');
        if (SPORTS_API_NFL_GOALSERVE) {
            $PlayersData = $this->Sports_bsktbl_model->getPlayersLiveByTeamInjuriesAndStatesNba($CronID);
        }
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get matches data FOOTBALL. NEW CRON
      URL: 4 			/api/nba/utilities/getMatchesScoreLiveNba
     */

    public function getMatchesScoreLiveNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('getMatchesScoreLiveNba');
        if (SPORTS_API_NFL_GOALSERVE) {
            $MatchesData = $this->Sports_bsktbl_model->getMatchesScoreLiveNbaGoalServeByDate($CronID);
            //$MatchesData = $this->Sports_bsktbl_model->getMatchesScoreLiveNbaGoalServe($CronID);
        }
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

     /*
      Description:  Cron jobs to get player points. NEW CRON
      URL: 5         /api/nba/utilities/getPlayerPointsNba
     */

    public function getPlayerPointsNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayerPoints');
        // $this->Sports_football_model->getPlayersPointSessionLongGoalServe();
        $this->Sports_bsktbl_model->getPlayersPointGoalServe($CronID);
        // $this->Sports_football_model->updateDailyWeeklyUserTeamPoints();
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get matches data FOOTBALL. NEW CRON
      URL:6 			/api/nba/utilities/contestUserTeamCalculetePointsNba
     */

    public function contestUserTeamCalculetePointsNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('contestUserTeamCalculetePoints');
        if (SPORTS_API_NFL_GOALSERVE) {
            $MatchesData = $this->Sports_bsktbl_model->contestUserTeamCalculetePointsGoalServe($CronID);
        }
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Auto create season long weekly contest for Drafting. NEW CRON
      URL: 7      /api/nba/utilities/autoContestCompleteDailyWinningAssignNba
      Cron Status : Run Every 1 Hour
     */
    public function autoContestCompleteDailyWinningAssignNba_get() {
        $this->SnakeDrafts_model->autoCompleteDaily();
        $this->Sports_bsktbl_model->setContestWinners();
    }

    /*
      Description:  Cron jobs to auto amount distribute contest winner. NEW CRON
      URL: 8     /api/nba/utilities/amountDistributeContestWinnerNba
    */

    public function amountDistributeContestWinnerNba_get() {
        $CronID = $this->Utility_model->insertCronLogs('amountDistributeContestWinner');
        $this->Sports_bsktbl_model->amountDistributeContestWinner($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto cancel contest.
      URL:      /api/utilities/autoCancelContest
     */

    public function autoCancelContest_get() {
        $this->SnakeDrafts_model->autoCancelContest();
    }

    /*
      Description:  Cron jobs to auto  match wise player states updates. NEW CRON
      URL:      /api/nba/utilities/matchWisePlayerStatsUpdates
     */

    public function matchWisePlayerStatsUpdates_get() {
        $this->Sports_bsktbl_model->matchWisePlayerStatsUpdates();
    }

    /*
      Description:  Auto create season long weekly contest for Drafting. NEW CRON
      URL:      /api/utilities/autoCreateSeasonLongWeeklyDraft
      Cron Status : Run Every 1 Hour
     */
    public function autoCreateSeasonLongWeeklyDraft_get() {
        $this->SnakeDrafts_model->autoCreateSeasonLongWeeklyDraft();
    }

    /*
      Description:  Cron jobs to auto  weekly player states updates. NEW CRON
      URL:      /api/utilities/amountDistributeContestWinner
     */

    public function weeklyPlayerStatsUpdates_get() {
        $this->Sports_football_model->weeklyPlayerStatsUpdates();
    }

    /*
      Description: 	Cron jobs to auto draft team submit.
      URL: 			/api/utilities/autoDraftTeamSubmit
     */

    public function autoDraftTeamSubmit_get() {
        $this->SnakeDrafts_model->autoDraftTeamSubmit();
    }

    /*
      Description: 	Cron jobs to auto NFL team draft waiver wire request.
      URL: 			/api/utilities/applyWaiverWireRequest
     */

    public function nflTeamDraftWaiverWireRequest_get() {
        $this->SnakeDrafts_model->nflTeamDraftWaiverWireRequest();
    }

    /*
      Description: 	Cron jobs to auto NCAAF team draft waiver wire request.
      URL: 			/api/utilities/applyWaiverWireRequest
     */

    public function ncaafTeamDraftWaiverWireRequest_get() {
        $this->SnakeDrafts_model->ncaafTeamDraftWaiverWireRequest();
    }

    /*
      Description: 	Cron jobs to get player stats data.
      URL: 			/api/utilities/getPlayerStatsLive
     */

    public function getPlayerStatsLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayerStatsLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $PlayersStatsData = $this->Sports_model->getPlayerStatsLiveEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $PlayersStatsData = $this->Sports_model->getPlayerStatsLiveCricketApi($CronID);
        }
        if (!empty($PlayersStatsData)) {
            $this->Return['Data'] = $PlayersStatsData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto cancel contest refund amount.
      URL: 			/api/utilities/refundAmountCancelContest
     */

    public function refundAmountCancelContest_get() {
        $CronID = $this->Utility_model->insertCronLogs('refundAmountCancelContest');
        $this->Sports_model->refundAmountCancelContest($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }



    /*
      Description: 	Cron jobs to get joined player points.
      URL: 			/api/utilities/getJoinedContestPlayerPoints
     */

    public function getJoinedContestPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
        $this->Sports_model->getJoinedContestTeamPoints($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get auction joined player points.
      URL: 			/api/utilities/getAuctionJoinedUserTeamsPlayerPoints
     */

    public function getAuctionDraftJoinedUserTeamsPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getAuctionJoinedUserTeamsPlayerPoints');
        $this->Sports_model->getAuctionDraftJoinedUserTeamsPlayerPoints($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto set winner.
      URL: 			/api/utilities/setContestWinners
     */

    public function setContestWinners_get() {
        $CronID = $this->Utility_model->insertCronLogs('setContestWinners');
        $this->Sports_football_model->setContestWinnersSeasonLong();
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto add minute in every hours.
      URL: 			/api/utilities/liveAuctionAddMinuteInEveryHours
     */

    public function auctionLiveAddMinuteInEveryHours_get() {
        $CronID = $this->Utility_model->insertCronLogs('liveAuctionAddMinuteInEveryHours');
        $this->AuctionDrafts_model->auctionLiveAddMinuteInEveryHours($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto draft team submit if user not submit in 15 minutes.
      URL: 			/api/utilities/draftTeamAutoSubmit
     */

    public function draftTeamAutoSubmit_get() {
        $CronID = $this->Utility_model->insertCronLogs('draftTeamAutoSubmit');
        $this->SnakeDrafts_model->draftTeamAutoSubmit($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: To get statics
     */

    public function dashboardStatics_post() {
        $SiteStatics = new stdClass();
        $SiteStatics = $this->db->query('SELECT
                                            TotalUsers,
                                            TotalContest,
                                            TodayContests,
                                            TotalDeposits,
                                            TotalWithdraw,
                                            TodayDeposit,
                                            NewUsers,
                                            TotalDeposits - TotalWithdraw AS TotalEarning,
                                            PendingWithdraw
                                        FROM
                                            (SELECT
                                                (
                                                    SELECT
                                                        COUNT(UserID) AS `TotalUsers`
                                                    FROM
                                                        `tbl_users`
                                                    WHERE
                                                        `UserTypeID` = 2
                                                ) AS TotalUsers,
                                                (
                                                    SELECT
                                                        COUNT(UserID) AS `NewUsers`
                                                    FROM
                                                        `tbl_users` U, `tbl_entity` E
                                                    WHERE
                                                        U.`UserTypeID` = 2 AND U.UserID = E.EntityID AND DATE(E.EntryDate) = "' . date('Y-m-d') . '"
                                                ) AS NewUsers,
                                                (
                                                    SELECT
                                                        COUNT(ContestID) AS `TotalContest`
                                                    FROM
                                                        `sports_contest`
                                                ) AS TotalContest,
                                                (
                                                    SELECT
                                                        COUNT(ContestID)
                                                    FROM
                                                        `sports_contest` C, `tbl_entity` E
                                                    WHERE
                                                        C.ContestID = E.EntityID AND DATE(E.EntryDate) = "' . date('Y-m-d') . '"
                                                ) AS TodayContests,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`WalletAmount`),0) AS TotalDeposits
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `Narration`= "Deposit Money" AND
                                                        `StatusID` = 5
                                                ) AS TotalDeposits,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`WalletAmount`),0) AS TodayDeposit
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `Narration`= "Deposit Money" AND
                                                        `StatusID` = 5 AND DATE(EntryDate) = "' . date('Y-m-d') . '"
                                                ) AS TodayDeposit,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_withdrawal`
                                                    WHERE
                                                        `StatusID` = 2
                                                ) AS TotalWithdraw,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_withdrawal`
                                                    WHERE
                                                        `StatusID` = 1
                                                ) AS PendingWithdraw
                                            ) Total'
                )->row();
        $this->Return['Data'] = $SiteStatics;
    }

    /*
      Description:  Use to get app version details
      URL:      /api/utilities/getAppVersionDetails
     */

    public function getAppVersionDetails_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('UserAppVersion', 'UserAppVersion', 'trim|required');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */

        $VersionData = $this->Utility_model->getAppVersionDetails();
        if (!empty($VersionData)) {
            $this->Return['Data'] = $VersionData;
        }
    }

    /*
      Name: 			createVirtualUsers
      Description: 	create virtual user users
      URL: 			/utilities/createVirtualUsers/
     */

    public function createVirtualUsers_get() {

        $tlds = array("com");
        $char = "0123456789abcdefghijklmnopqrstuvwxyz";
        $Limit = 5000;
        $Names = $this->Utility_model->getDummyNames($Limit);
        for ($j = 0; $j < $Limit; $j++) {
            $UserName = $Names[$j]['names'];
            $UserUnique = str_replace(" ", "", $UserName);
            $ulen = mt_rand(5, 10);
            $dlen = mt_rand(7, 17);
            $email = "";
            for ($i = 1; $i <= $ulen; $i++) {
                $email .= substr($char, mt_rand(0, strlen($char)), 1);
            }
            $email .= "@";
            $email .= "gmail";
            $email .= ".";
            $email .= $tlds[mt_rand(0, (sizeof($tlds) - 1))];
            $username = strtolower($UserUnique) . substr(md5(microtime()), rand(0, 26), 4);
            $Input = array();
            $Input['Email'] = $username . "@gmail.com";
            $Input['Username'] = $username;
            $Input['FirstName'] = $UserName;
            $Input['Password'] = 'A@123456';
            $Input['Source'] = "Direct";
            $Input['PanStatus'] = 2;
            $Input['BankStatus'] = 2;
            $Input['DocumentStatus'] = 2;
            $UserID = $this->Users_model->addUser($Input, 3, 1, 2);
            if ($UserID) {
                $this->Utility_model->generateReferralCode($UserID);
                $WalletData = array(
                    "Amount" => 10000,
                    "CashBonus" => 0,
                    "TransactionType" => 'Cr',
                    "Narration" => 'Deposit Money',
                    "EntryDate" => date("Y-m-d H:i:s")
                );
                $this->Users_model->addToWallet($WalletData, $UserID, 5);
            }
        }
    }

    /*
      Name: 			createVirtualUserTeams
      Description: 	create virtual user team
      URL: 			/utilities/createVirtualUserTeams/
     */

    public function createVirtualUserTeams_get() {

        $AllUsers = $this->Users_model->getUsers('UserID', array('UserTypeID' => 3), true, 1, 3000);
        if (!empty($AllUsers)) {
            $MatchContest = $this->Contest_model->getContests('MatchID', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes"), TRUE);
            if (!empty($MatchContest['Data']['Records'])) {
                $Matches = array_unique(array_column($MatchContest['Data']['Records'], "MatchID"));
                foreach ($Matches as $Rows) {
                    $Match = $this->Sports_model->getMatches('SeriesID,MatchID,TeamIDLocal,TeamIDVisitor', array('StatusID' => array(1), 'MatchID' => $Rows), False, 1, 1);
                    if (!empty($Match)) {
                        $MatchID = $Match['MatchID'];
                        $SeriesID = $Match['SeriesID'];
                        $playersData = $this->Sports_model->getPlayers('PlayerID,TeamID,PlayerRole,PlayerSalary', array('MatchID' => $MatchID, 'RandData' => "rand()"), TRUE, 1, 50);
                        if (!empty($playersData)) {
                            $unique = 0;
                            foreach ($AllUsers['Data']['Records'] as $users) {
                                if ($unique % 2 == 0) {
                                    $localteamIDS = $Match['TeamIDLocal'];
                                    $visitorteamIDS = $Match['TeamIDVisitor'];
                                } else {
                                    $visitorteamIDS = $Match['TeamIDLocal'];
                                    $localteamIDS = $Match['TeamIDVisitor'];
                                }
                                $this->createTeamProcessByMatch($playersData, $localteamIDS, $visitorteamIDS, $SeriesID, $users['UserID'], $MatchID);
                                $unique++;
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Name: 			createTeamProcessByMatch
      Description: 	virtual usercommon create team
      URL: 			/testApp/createTeamProcessByMatch/
     */

    public function createTeamProcessByMatch($matchPlayer, $localteam_id, $visitorteam_id, $series_id, $user_id, $match_id) {
        $returnArray = array();
        $playerCount = 1;
        $secondPlayerCount = 1;
        $batsman = 0;
        $wicketkeeper = 0;
        $bowler = 0;
        $allrounder = 0;
        $teamCount = 0;
        $teamB = array();
        $Arr1 = array();
        $Arr2 = array();
        $Arr3 = array();
        $Arr4 = array();
        $Arr5 = array();
        $Arr6 = array();
        $Arr7 = array();
        $Arr8 = array();
        $creditPoints = 0;
        $points = 0;
        $selectedViceCaptainPlayer = [];
        $selectedCaptainPlayer = [];

        foreach ($matchPlayer['Data']['Records'] as $player) {
            if (count($playerCount) <= 7) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                $creditPoints += 9;
                if ($teamId == $localteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'ViceCaptain';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr1[] = $temp;
                            $wicketkeeper++;
                        }
                    } if ($batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr2[] = $temp;
                            $batsman++;
                        }
                    }if ($bowler < 2) {
                        if ($playerRole == 'BOWLER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr3[] = $temp;
                            $bowler++;
                        }
                    }if ($allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Captain';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr4[] = $temp;
                            $allrounder++;
                        }
                    }
                }
            }
            $playerCount++;
            $res1 = array_merge($Arr1, $Arr2, $Arr3, $Arr4);
        }
        foreach ($matchPlayer['Data']['Records'] as $player) {
            if (count($secondPlayerCount) <= 4) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                if ($teamId == $visitorteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'ViceCaptain';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr5[] = $temp1;
                            $wicketkeeper++;
                        }
                    } if ($batsman < 4) {
                        if ($playerRole == 'BATSMAN') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr6[] = $temp1;
                            $batsman++;
                        }
                    }if ($bowler < 4) {
                        if ($playerRole == 'BOWLER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr7[] = $temp1;
                            $bowler++;
                        }
                    }if ($allrounder < 2) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr8[] = $temp1;
                            $allrounder++;
                        }
                    }
                }
            }
            $secondPlayerCount++;
            $res2 = array_merge($Arr5, $Arr6, $Arr7, $Arr8);
        }
        $playing11 = array_merge($res2, $res1);
        if (count($playing11) == 11) {
            $this->Contest_model->addUserTeam(array('UserTeamPlayers' => json_encode($playing11), 'UserTeamType' => 'Normal'), $user_id, $match_id);
        }
        return true;
    }

    /*
      Name: 			autoJoinContestVirtualUser
      Description: 	join virtual user contest
      URL: 			/testApp/autoJoinContestVirtualUser/
     */

    public function autoJoinContestVirtualUser_get() {

        $UtcDateTime = date('Y-m-d H:i');
        $UtcDateTime = date('Y-m-d H:i', strtotime($UtcDateTime));
        $NextDateTime = strtotime($UtcDateTime) + 3600 * 20;
        $MatchDateTime = date('Y-m-d H:i', $NextDateTime);

        $Contests = $this->Contest_model->getContests('IsPaid,EntryFee,CashBonusContribution,WinningAmount,MatchID,IsDummyJoined,ContestID,ContestSize,TotalJoined,MatchStartDateTimeUTC,VirtualUserJoinedPercentage', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", "ContestFull" => "No"), TRUE);
        //print_r($Contests);exit;
        if (!empty($Contests['Data']['Records'])) {
            foreach ($Contests['Data']['Records'] as $Rows) {
                $Seconds = strtotime($Rows['MatchStartDateTimeUTC']) - strtotime($UtcDateTime);
                $Hours = $Seconds / 60 / 60;

                $dummyJoinedContest = 0;
                $dummyJoinedContests = $this->Contest_model->getTotalDummyJoinedContest($Rows['ContestID']);

                if ($dummyJoinedContests) {
                    $dummyJoinedContest = $dummyJoinedContests;
                }

                $totalJoined = $Rows['TotalJoined'];
                $contestSize = $Rows['ContestSize'];
                $joinDummyUser = $Rows['VirtualUserJoinedPercentage'];
                $dummyUserPercentage = round(($contestSize * $joinDummyUser) / 100);
                //echo $dummyJoinedContest .">=". $dummyUserPercentage;exit;
                if ($dummyJoinedContest >= $dummyUserPercentage) {
                    $this->Contest_model->UpdateVirtualJoinContest($Rows['ContestID']);
                    continue;
                }
                if ($hours > 7 || $Rows['IsDummyJoined'] == 0) {
                    if ($Rows['IsDummyJoined'] == 0) {
                        $dummyUserPercentage = round(($dummyUserPercentage * 40 / 100));
                    } else {
                        continue;
                    }
                } else if ($hours > 4 || ($Rows['IsDummyJoined'] == 1 && $hours < 4)) {
                    if ($Rows['IsDummyJoined'] == 1) {
                        $dummyUserPercentage = round(($dummyUserPercentage * 40 / 100));
                    } else {
                        continue;
                    }
                } else {
                    if ($Rows['IsDummyJoined'] >= 2 && $hours < 3) {
                        $dummyUserPercentage = round(($dummyUserPercentage * 100 / 100)) - $dummyJoinedContest;
                    } else {
                        continue;
                    }
                }

                $isEliglibleJoin = $totalJoined + $dummyUserPercentage;
                if (!($isEliglibleJoin <= $contestSize)) {
                    $dummyUserPercentage = $contestSize - $totalJoined - 5;
                }

                $VitruelTeamPlayer = $this->Contest_model->GetVirtualTeamPlayerMatchWise($Rows['MatchID'], $dummyUserPercentage);
                if (!empty($VitruelTeamPlayer)) {
                    foreach ($VitruelTeamPlayer as $usersTeam) {
                        $userTeamPlayers = json_decode($usersTeam['Players']);
                        $myPlayers = '';
                        $c = 0;
                        $vc = 0;
                        foreach ($userTeamPlayers as $player) {
                            $myPlayers .= $player->PlayerID . ",";
                            if ($player->PlayerPosition == "Captain") {
                                $captain_player = $player->PlayerID;
                                $c++;
                            }
                            if ($player->PlayerPosition == "ViceCaptain") {
                                $vice_captain_player = $player->PlayerID;
                                $vc++;
                            }
                        }
                        if (isset($myPlayers) && isset($captain_player) && isset($vice_captain_player)) {
                            $myPlayers = rtrim($myPlayers, ",");
                            if (!empty($usersTeam['UserTeamID'])) {
                                if ($c > 1 || $vc > 1) {
                                    continue;
                                }
                                $PostInput = array();

                                $this->db->select('ContestID');
                                $this->db->from('sports_contest_join');
                                $this->db->where("ContestID", $Rows['ContestID']);
                                $this->db->where("UserID", $usersTeam['UserID']);
                                $this->db->where("UserTeamID", $usersTeam['UserTeamID']);
                                $this->db->limit(1);
                                $Query = $this->db->get();
                                if ($Query->num_rows() <= 0) {
                                    $PostInput['IsPaid'] = $this->Contest_model->joinContest($Rows, $usersTeam['UserID'], $Rows['ContestID'], $Rows['MatchID'], $usersTeam['UserTeamID']);
                                }
                            }
                        }
                    }
                    $this->Contest_model->ContestUpdateVirtualTeam($Rows['ContestID'], $Rows['IsDummyJoined']);
                }
            }
        }
    }

    /*
      Description:  Use to get referel amount details.
      URL:      /api/utilities/getReferralDetails
     */

    public function getReferralDetails_post() {
        $ReferByQuery = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "ReferByDepositBonus" AND StatusID = 2 LIMIT 1');
        $ReferToQuery = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "ReferToDepositBonus" AND StatusID = 2 LIMIT 1');
        $this->Return['Data']['ReferByBonus'] = ($ReferByQuery->num_rows() > 0) ? $ReferByQuery->row()->ConfigTypeValue : 0;
        $this->Return['Data']['ReferToBonus'] = ($ReferToQuery->num_rows() > 0) ? $ReferToQuery->row()->ConfigTypeValue : 0;
    }

    /*
      Description:  Use to update wallet opening balance
      URL:      /api/utilities/updateOpeningBalance
     */

    public function updateOpeningBalance_get() {

        /* Reset Entries */
        $this->db->query('UPDATE `tbl_users_wallet` SET `OpeningWalletAmount` = 0,`OpeningWinningAmount`=0,`OpeningCashBonus`=0,`ClosingWalletAmount`=0,`ClosingWinningAmount`=0,`ClosingCashBonus` =0');
        $Query = $this->db->query('SELECT `UserID` FROM `tbl_users_wallet` GROUP BY UserID');
        if ($Query->num_rows() > 0) {
            foreach ($Query->result_array() as $key => $Record) {
                $Query1 = $this->db->query('SELECT * FROM `tbl_users_wallet` WHERE `UserID` = ' . $Record['UserID'] . ' ORDER BY `WalletID` ASC');
                foreach ($Query1->result_array() as $key1 => $Record1) {
                    $Query2 = $this->db->query('SELECT * FROM `tbl_users_wallet` WHERE `UserID` = ' . $Record['UserID'] . ' AND WalletID < ' . $Record1['WalletID'] . ' ORDER BY `WalletID` DESC LIMIT 1');
                    if ($Query2->num_rows() > 0) {
                        $OpeningWalletAmount = $Query2->row()->ClosingWalletAmount;
                        $OpeningWinningAmount = $Query2->row()->ClosingWinningAmount;
                        $OpeningCashBonus = $Query2->row()->ClosingCashBonus;
                        $ClosingWalletAmount = ($Record1['StatusID'] == 5) ? (($OpeningWalletAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWalletAmount + $Record1['WalletAmount'] : $OpeningWalletAmount - $Record1['WalletAmount'] ) : $Record1['WalletAmount']) : $OpeningWalletAmount;
                        $ClosingWinningAmount = ($Record1['StatusID'] == 5) ? (($OpeningWinningAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWinningAmount + $Record1['WinningAmount'] : $OpeningWinningAmount - $Record1['WinningAmount'] ) : $Record1['WinningAmount']) : $OpeningWinningAmount;
                        $ClosingCashBonus = ($Record1['StatusID'] == 5) ? (($OpeningCashBonus != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningCashBonus + $Record1['CashBonus'] : $OpeningCashBonus - $Record1['CashBonus'] ) : $Record1['CashBonus']) : $OpeningCashBonus;
                    } else {
                        $OpeningWalletAmount = $OpeningWinningAmount = $OpeningCashBonus = 0;
                        $ClosingWalletAmount = ($Record1['StatusID'] == 5) ? (($OpeningWalletAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWalletAmount + $Record1['WalletAmount'] : $OpeningWalletAmount - $Record1['WalletAmount'] ) : $Record1['WalletAmount']) : 0;
                        $ClosingWinningAmount = ($Record1['StatusID'] == 5) ? (($OpeningWinningAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWinningAmount + $Record1['WinningAmount'] : $OpeningWinningAmount - $Record1['WinningAmount'] ) : $Record1['WinningAmount']) : 0;
                        $ClosingCashBonus = ($Record1['StatusID'] == 5) ? (($OpeningCashBonus != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningCashBonus + $Record1['CashBonus'] : $OpeningCashBonus - $Record1['CashBonus'] ) : $Record1['CashBonus']) : 0;
                    }
                    $UpdateArr = array(
                        'OpeningWalletAmount' => $OpeningWalletAmount,
                        'OpeningWinningAmount' => $OpeningWinningAmount,
                        'OpeningCashBonus' => $OpeningCashBonus,
                        'ClosingWalletAmount' => $ClosingWalletAmount,
                        'ClosingWinningAmount' => $ClosingWinningAmount,
                        'ClosingCashBonus' => $ClosingCashBonus
                    );
                    $this->db->where('WalletID', $Record1['WalletID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_users_wallet', $UpdateArr);
                }
            }
        }
    }

    public function updateContestTeams_get() {
        $this->SnakeDrafts_model->updateContestTeams();
    }


    /*
      Name : banner list
      Description : User to get banner list
      URL : /utilities/bannerList/
     */

    public function bannerList_post() {
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        $data = $this->Utility_model->bannerList('', array_merge($this->Post,array('StatusID' => $this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($data) {
            $this->Return['Data'] = $data['Data'];
        } else {
            $this->Return['Data'] = new StdClass();
        }
    }

    /*
      Name : contest standing
      Description : to get users contest standing
      URL : api/nba/utilities/contestStanding/
     */

    public function contestStanding_post() {
        $Standing = $this->db->query('
          SELECT U.UserID,Username,FirstName, 
          IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","user-img.svg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic,
          (SELECT COUNT(CJ.ContestID) FROM nba_sports_contest_join CJ WHERE CJ.UserID=U.UserID) AS JoinContestCount,
          (SELECT COUNT(CJ.ContestID) FROM nba_sports_contest_join CJ WHERE CJ.UserID=U.UserID AND CJ.UserWinningAmount>0) AS WinContestCount
          FROM tbl_users U 
          HAVING JoinContestCount >= 10
          ORDER BY WinContestCount DESC'
        )->result_array();
        $this->Return['Data'] = $Standing;
    }

}

// */1 * * * * wget -qO- http://18.134.48.214/Matchaddauto/addMatchOfActiveSeries
