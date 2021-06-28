<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class SnakeDrafts extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('SnakeDrafts_model');
        $this->load->model('Sports_model');
    }

    /*
      Name:             add
      Description:  Use to add contest to system.
      URL:          /contest/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'League Join Date TIme', 'trim|required');
        $this->form_validation->set_rules('ScoringType', 'ScoringType', 'trim|required|in_list[PointLeague,RoundRobin]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required|in_list[Nfl,Ncaaf]');
        $this->form_validation->set_rules('PlayOff', 'PlayOff', 'trim');
        $this->form_validation->set_rules('WeekStart', 'WeekStart', 'trim');
        $this->form_validation->set_rules('WeekEnd', 'WeekEnd', 'trim');
        $this->form_validation->set_rules('AllowPrivateContestFree', 'AllowPrivateContestFree', 'trim|required|in_list[Yes,No]' . ($this->Post['AllowPrivateContestFree'] == "No" ? "|callback_validatePaidContest" : ""));
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning'])) {
            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                $TotalPercent += $Value['Percent'];
                $TotalWinningAmount += $TotalWinners * $Value['WinningAmount'];
            }

            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }

            /* Check Total Percent */
            if ($TotalPercent < 100 || $TotalPercent > 100) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners Percent should be 100%.";
                exit;
            }

            /* Check Total Winning Amount */
            if ($TotalWinningAmount != $this->Post['WinningAmount']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }

            /* Check user wallet amount */
            if ($this->Post['AllowPrivateContestFree'] == "Yes") {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
        }
        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $DraftTotalRounds = 0;
        $ContestSize = $this->Post['ContestSize'];
        $TeamSize = ($this->Post['GameType'] == "Nfl") ? 32 : 130;
        $this->Post['DraftTotalRounds'] = round($TeamSize / $ContestSize);
        $ContestID = $this->SnakeDrafts_model->addContest($this->Post, $this->SessionUserID, @$this->MatchID, $this->SeriesID);
        if (!$ContestID) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Contest created successfully.";
            $this->Return['Data'] = $this->SnakeDrafts_model->getContests('CustomizeWinning,MatchScoreDetails,UserID,ContestFormat,ContestType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,UserInvitationCode,SeriesGUID', array('ContestID' => $ContestID));
        }
    }

    /**
     * Function Name: validatePaidContest
     * Description:   To validate user paid private contest
     */
    public function validatePaidContest() {
        /* To Check Wallet Amount, If Contest Is Paid */
        if ($this->Post['Privacy'] == 'Yes') {
            $this->load->model('Users_model');
            $UserData = $this->Users_model->getUsers('TotalCash,WalletAmount,WinningAmount,CashBonus', array('UserID' => $this->SessionUserID));
            $this->Post['WalletAmount'] = $UserData['WalletAmount'];
            $this->Post['WinningAmount'] = $UserData['WinningAmount'];
            $TotalWalletAmount = $UserData['WalletAmount'] + $UserData['WinningAmount'];
            $PrivateContestFee = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "PrivateContestFee" LIMIT 1');
            $PrivateContestFee = $PrivateContestFee->row()->ConfigTypeValue;
            $TotalContestFee = round($PrivateContestFee * $this->Post['ContestSize'], 2);
            $this->Post['TotalContestFee'] = $TotalContestFee;
            if ($TotalWalletAmount < $TotalContestFee) {
                $this->form_validation->set_message('validatePaidContest', 'Insufficient wallet amount.');
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    /*
      Name:             edit
      Description:  Use to update contest to system.
      URL:          /contest/edit/
     */

    public function edit_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[update]');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|integer');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('ScoringType', 'ScoringType', 'trim|required|in_list[PointLeague,RoundRobin]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required|in_list[Nfl,Ncaaf]');
        $this->form_validation->set_rules('PlayOff', 'PlayOff', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WeekStart', 'WeekStart', 'trim');
        $this->form_validation->set_rules('WeekEnd', 'WeekEnd', 'trim');
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning'])) {
            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                $TotalPercent += $Value['Percent'];
                $TotalWinningAmount += $TotalWinners * $Value['WinningAmount'];
            }

            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }

            /* Check Total Percent */
            if ($TotalPercent < 100 || $TotalPercent > 100) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners Percent should be 100%.";
                exit;
            }

            /* Check Total Winning Amount */
            if ($TotalWinningAmount != $this->Post['WinningAmount']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
        }
        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $DraftTotalRounds = 0;
        $ContestSize = $this->Post['ContestSize'];
        $TeamSize = ($this->Post['GameType'] == "Nfl") ? 32 : 130;
        $this->Post['DraftTotalRounds'] = round($TeamSize / $ContestSize);
        $this->SnakeDrafts_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Name:             delete
      Description:  Use to delete contest to system.
      URL:          /contest/delete/
     */

    public function delete_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[delete]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Delete Contests Data */
        $this->SnakeDrafts_model->deleteContest($this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest deleted successfully.";
    }

    /*
      Description: To get contests data
     */

    public function getContests_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('StatusID', 'StatusID', 'trim');
        $this->form_validation->set_rules('AuctionStatus', 'AuctionStatus', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'AuctionStatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    /*
      Description: To get contests data
     */

    public function getContestsByType_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */

        $ContestData = array();

        $ContestTypes[] = array('Key' => 'Hot Contest', 'TagLine' => 'Filling Fast. Join Now!', 'Where' => array('ContestType' => 'Hot'));
        $ContestTypes[] = array('Key' => 'Contests for Champions', 'TagLine' => 'High Entry Fees, Intense Competition', 'Where' => array('ContestType' => 'Champion'));
        $ContestTypes[] = array('Key' => 'Head To Head Contest', 'TagLine' => 'The Ultimate Face Off', 'Where' => array('ContestType' => 'Head to Head'));
        $ContestTypes[] = array('Key' => 'Practice Contest', 'TagLine' => 'Hone Your Skills', 'Where' => array('ContestType' => 'Practice'));
        $ContestTypes[] = array('Key' => 'More Contest', 'TagLine' => 'Keep Winning!', 'Where' => array('ContestType' => 'More'));
        $ContestTypes[] = array('Key' => 'Mega Contest', 'TagLine' => 'Get ready for mega winnings!', 'Where' => array('ContestType' => 'Mega'));
        $ContestTypes[] = array('Key' => 'Winner Takes All', 'TagLine' => 'Everything To Play For', 'Where' => array('ContestType' => 'Winner Takes All'));
        $ContestTypes[] = array('Key' => 'Only For Beginners', 'TagLine' => 'Play Your First Contest Now', 'Where' => array('ContestType' => 'Only For Beginners'));

        foreach ($ContestTypes as $key => $Contests) {

            array_push($ContestData, $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID), $Contests['Where']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize'])['Data']);
            $ContestData[$key]['Key'] = $Contests['Key'];
            $ContestData[$key]['TagLine'] = $Contests['TagLine'];
        }

        $Statics = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . $this->SessionUserID . '" AND C.MatchID = "' . $this->MatchID . '"
                    )as JoinedContest,(
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . $this->SessionUserID . '" AND MatchID = "' . $this->MatchID . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();

        if (!empty($ContestData)) {
            $this->Return['Data']['Results'] = $ContestData;
            $this->Return['Data']['Statics'] = $Statics;
        }
    }

    /*
      Description: To get contest detail
     */

    public function getContest_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|numeric');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID, 'MatchID' => $this->MatchID, 'SessionUserID' => $this->SessionUserID)));
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }


        /*
      Description: To get contest detail
     */

    public function getWeekDate_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|numeric');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getWeekDate($this->SeriesID,$this->Post['WeekID']);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

            /*
      Description: To get contest detail
     */

    public function getCurrentWeek_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getCurrentWeek(@$this->SeriesID);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Description: To get contest detail
     */

    public function leaveDraftRoom_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->leaveDraftRoom($this->ContestID, $this->UserID);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Name:             join
      Description:  Use to join contest to system.
      URL:          /contest/join/
     */

    public function join_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        // $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateUserJoinContest');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Join Contests */
        /** Check match on going to live* */
        $ContestDetails = $this->SnakeDrafts_model->getContests("GameTimeLive,ContestID,LeagueJoinDateTime,LeagueJoinDateTimeUTC,IsAutoDraft", array("ContestID" => $this->ContestID));
        $LeagueJoinDateTime = $ContestDetails['LeagueJoinDateTimeUTC'];
        $currentDateTime = date('Y-m-d H:i:s');
        if (strtotime($LeagueJoinDateTime) < strtotime($currentDateTime)) {
            /* $this->SnakeDrafts_model->changeContestStatus($this->ContestID); */
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can not join the contest because time is over.";
        } else {
            $JoinContest = $this->SnakeDrafts_model->joinContest($this->Post, $this->SessionUserID, $this->ContestID, $this->SeriesID, $this->UserTeamID, @$ContestDetails['IsAutoDraft']);
            if (!$JoinContest) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Data'] = $JoinContest;
                $this->Return['Message'] = "Contest joined successfully.";
            }
        }
    }

    /*
      Name:             getDraftTeams
      Description:  Use to add contest to system.
      URL:          /Snakedrafts/getDraftTeams/
     */

    public function getDraftTeams_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $Teams = $this->SnakeDrafts_model->getDraftTeams(@$this->Post['Params'], array_merge($this->Post, array("SeriesID" => @$this->SeriesID, "ContestID" => @$this->ContestID, 'UserID' => @$this->UserID)), TRUE);
        $this->Return['Data'] = $Teams['Data'];
        $this->Return['Message'] = "Teams successfully found.";
    }

    public function getJoinedDraftAllTeams_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */


        $AllTeams = $this->SnakeDrafts_model->getJoinedDraftAllTeams($this->ContestID, $this->SeriesID, $this->UserID);
        $this->Return['Data'] = $AllTeams;
        $this->Return['Message'] = "Teams successfully found.";
    }

    /*
      Description: To get players data
     */

    public function getPlayers_post() {
        // $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->SnakeDrafts_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->SessionUserID, 'PlayerID' => @$this->PlayerID, 'UserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData['Data'];
        }
    }

        /*
      Description: To get sports points
     */

    public function getPoints_post() {
        $this->form_validation->set_rules('PointsCategory', 'PointsCategory', 'trim|in_list[Normal,InPlay,Reverse]');
        $this->form_validation->validation($this);  /* Run validation */

        $PointsData = $this->SnakeDrafts_model->getPoints($this->Post);
        if (!empty($PointsData)) {
            $this->Return['Data'] = $PointsData['Data'];
        }
    }

    public function getTeamsData_post() {
        // $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        // $this->form_validation->set_rules('TeamGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Team,TeamID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        $TeamData = $this->SnakeDrafts_model->getTeamsData(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->SessionUserID, 'TeamID' => @$this->TeamID, 'UserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($TeamData)) {
            $this->Return['Data'] = $TeamData['Data'];
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayersDraft_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->SnakeDrafts_model->getPlayersDraftAll(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->UserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            if(!empty($PlayersData['Data']['Records'])){
                $Roosters = @array_column($PlayersData['Data']['Records'], 'RoosterRole')[0];
                $RoosterPlayer = array_count_values(array_column($PlayersData['Data']['Records'], 'PlayerSelectTypeRole'));
                $RoosterArray = array();
                if($PlayersData['Data']['TotalRecords'] < array_sum($Roosters)){
                     foreach($Roosters as $Key=>$Row){
                        if($RoosterPlayer[$Key] < $Row){
                            for($i=1; $i<= $Row - $RoosterPlayer[$Key]; $i++){
                                $RoosterArray[]['PlayerSelectTypeRole'] = $Key;
                            }
                        } 
                     }  
                }
                $PlayersData['Data']['Records'] = array_merge($PlayersData['Data']['Records'],$RoosterArray);
            }
            $Query = $this->db->query('SELECT IsAutoDraft FROM sports_contest_join WHERE ContestID = "' . @$this->ContestID . '" AND UserID = "' . @$this->UserID . '" LIMIT 1');
            $PlayersData['Data']['IsAutoDraft'] = $Query->row()->IsAutoDraft;
            $this->Return['Data'] = $PlayersData['Data'];
        }else{
           $ContestsUsers = $this->SnakeDrafts_model->getContests('ContestID,ContestSize,DraftTotalRounds,Privacy', array('ContestID' => $this->ContestID));
           if($ContestsUsers['Privacy'] == "No"){
                $Roosters = footballGetConfigurationPlayersRooster($ContestsUsers['ContestSize']);
           }else{
                $Roosters = footballGetConfigurationPlayersRoosterPrivate($ContestsUsers['DraftTotalRounds']);
           }
           
           $RoosterArray = array();
           foreach($Roosters as $Key=>$Row){
                for($i=1; $i<= $Row; $i++){
                    $RoosterArray[]['PlayerSelectTypeRole'] = $Key;
                }
           }
           $PlayersData['Data'] = $this->SnakeDrafts_model->addUserTeamDraft($this->UserID, $this->SeriesID, $this->ContestID);
           $PlayersData['Data']['TotalRecords'] = $ContestsUsers['DraftTotalRounds'];
           $PlayersData['Data']['Records'] = $RoosterArray;
           $Query = $this->db->query('SELECT IsAutoDraft FROM sports_contest_join WHERE ContestID = "' . @$this->ContestID . '" AND UserID = "' . @$this->UserID . '" LIMIT 1');
           $PlayersData['Data']['IsAutoDraft'] = $Query->row()->IsAutoDraft;
           $this->Return['Data'] = $PlayersData['Data']; 
        }
    }


    /*
      Description: To get players data
     */

    public function getPlayersAll_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->SnakeDrafts_model->getPlayersAll(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => @$this->SeriesID, 'SessionUserID' => @$this->UserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        $this->Return['Data'] = $PlayersData['Data'];
        
    }

        /*
      Description: To get players data
     */

    public function getPlayersMyTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->SnakeDrafts_model->getPlayersMyTeam(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->UserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            if(!empty($PlayersData['Data']['Records'])){
                $Roosters = @array_column($PlayersData['Data']['Records'], 'RoosterRole')[0];
                $RoosterPlayer = array_count_values(array_column($PlayersData['Data']['Records'], 'PlayerSelectTypeRole'));
                $RoosterArray = array();
                if($PlayersData['Data']['TotalRecords'] < array_sum($Roosters)){
                     foreach($Roosters as $Key=>$Row){
                        if($RoosterPlayer[$Key] < $Row){
                            for($i=1; $i<= $Row - $RoosterPlayer[$Key]; $i++){
                                $RoosterArray[]['PlayerSelectTypeRole'] = $Key;
                            }
                        } 
                     }  
                }
                $PlayersData['Data']['Records'] = array_merge($PlayersData['Data']['Records'],$RoosterArray);
            }
            $this->Return['Data'] = $PlayersData['Data'];
        }else{
           $ContestsUsers = $this->SnakeDrafts_model->getContests('ContestID,ContestSize,DraftTotalRounds,Privacy', array('ContestID' => $this->ContestID));
           if($ContestsUsers['Privacy'] == "No"){
              $Roosters = footballGetConfigurationPlayersRooster($ContestsUsers['ContestSize']);
           }else{
              $Roosters = footballGetConfigurationPlayersRoosterPrivate($ContestsUsers['DraftTotalRounds']);
           }
           $RoosterArray = array();
           foreach($Roosters as $Key=>$Row){
                for($i=1; $i<= $Row; $i++){
                    $RoosterArray[]['PlayerSelectTypeRole'] = $Key;
                }
           }
           $PlayersData['Data'] = $this->SnakeDrafts_model->addUserTeamDraft($this->UserID, $this->SeriesID, $this->ContestID);
           $PlayersData['Data']['TotalRecords'] = $ContestsUsers['DraftTotalRounds'];
           $PlayersData['Data']['Records'] = $RoosterArray;
           $this->Return['Data'] = $PlayersData['Data']; 
        }
    }

    /*
      Name:             addUserTeam
      Description:  Use to create team to system.
      URL:          /api_admin/contest/addUserTeam/
     */

    public function addUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('IsPreTeam', 'IsPreTeam', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $UserTeam = $this->SnakeDrafts_model->addUserTeam($this->Post, $this->SessionUserID, $this->SeriesID, $this->ContestID);
        if (!$UserTeam) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data']['UserTeamGUID'] = $UserTeam;
            $this->Return['Message'] = "Team created successfully.";
        }
    }

    /*
      Name:             assistantTeamOnOff
      Description:  Use to on off assistant.
      URL:          /api_admin/auctionDrafts/assistantTeamOnOff/
     */

    public function autoDraftOnOff_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('IsAutoDraft', 'IsAutoDraft', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserTeam = $this->SnakeDrafts_model->autoDraftOnOff($this->Post, $this->SessionUserID, $this->SeriesID, $this->ContestID);
        $this->Return['Message'] = "Auto Draft updated successfully.";
    }


    public function assistantTeamOnOff_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserTeam = $this->SnakeDrafts_model->assistantTeamOnOff($this->Post, $this->SessionUserID, $this->SeriesID, $this->ContestID, $this->UserTeamID);
        $this->Return['Message'] = "Assistant updated successfully.";
    }

    /*
      Name:             editUserTeam
      Description:  Use to update team to system.
      URL:          /api_admin/auctionDrafts/editUserTeam/
     */

    public function editUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $edit = $this->SnakeDrafts_model->editUserTeam(array_merge($this->Post, array('SeriesID' => @$this->SeriesID)), $this->UserTeamID);

        if (!$edit) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Team updated successfully.";
        }
    }

    /*
      Description: To get user teams data
     */

    public function getUserTeams_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get User Teams Data */
        $UserTeams = $this->SnakeDrafts_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'SeriesID' => $this->SeriesID, 'UserTeamID' => @$this->UserTeamID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($UserTeams)) {
            $this->Return['Data'] = (!empty($this->Post['UserTeamGUID'])) ? $UserTeams : $UserTeams['Data'];
        }
    }

    /*
      Name:             getRounds
      Description:  get live drafts round.
      URL:          /api_admin/auctionDrafts/getRounds/
     */

    public function getRounds_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */

        $ContestRound = $this->SnakeDrafts_model->getContests('ContestID,DraftLiveRound,DraftTotalRounds', array("LeagueType" => "Draft", "ContestID" => $this->ContestID), TRUE, 1);
        if (!empty($ContestRound)) {
            $Rounds = $this->SnakeDrafts_model->getRounds($this->SeriesID, $this->ContestID, $ContestRound['Data']['Records'][0]['DraftTotalRounds']);
            $this->Return['Data'] = $Rounds;
            if ($ContestRound['Data']['TotalRecords'] > 0) {
                $this->Return['DraftLiveRound'] = $ContestRound['Data']['Records'][0]['DraftLiveRound'];
            } else {
                $this->Return['DraftLiveRound'] = "0";
            }
            $this->Return['Message'] = "Rounds listed.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Rounds not found";
        }
    }

    /*
      Name:             addAuctionPlayerBid
      Description:  Use to create team to system.
      URL:          /api_admin/auctionDrafts/addAuctionPlayerBid/
     */

    public function addAuctionPlayerBid_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserID = @$this->SessionUserID;
        if (empty($this->Post['SessionKey'])) {
            $UserID = @$this->UserID;
        }
        $AuctionBid = $this->SnakeDrafts_model->addAuctionPlayerBid($this->Post, $UserID, $this->SeriesID, $this->ContestID, $this->PlayerID);
        if ($AuctionBid['Status'] == 1) {
            $this->Return['Data'] = $AuctionBid['Data'];
            $this->Return['Message'] = "Player Bid successfully added.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $AuctionBid['Message'];
        }
    }

    /*
      Name:             getDraftGameInLive
      Description:  get live draft.
      URL:          /api_admin/auctionDrafts/getAuctionGameInLive/
     */

    public function getDraftGameInLive_post() {
        $AuctionGames = $this->SnakeDrafts_model->getContests('ContestID,ContestGUID,AuctionStatusID,AuctionStatus,SeriesGUID,GameType', array('Filter' => 'LiveAuction', 'AuctionStatusID' => 1, "LeagueType" => "Draft"), TRUE, 1);
        if (!empty($AuctionGames)) {
            $this->Return['Data'] = $AuctionGames;
        }
    }

    /*
      Name:             getAuctionGameStatusUpdate
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/getAuctionGameStatusUpdate/
     */

    public function getDraftGameStatusUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->getDraftGameStatusUpdate($this->Post, $this->ContestID, $this->StatusID);
        if ($AuctionStatus) {
            $this->Return['Message'] = "Auction status successfully updated.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Auction status already updated.";
        }
    }

    /*
      Name:             userLiveStatusUpdate
      Description:  set user onging to live.
      URL:          /api_admin/auctionDrafts/userLiveStatusUpdate/
     */

    public function userLiveStatusUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('UserStatus', 'UserStatus', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->userLiveStatusUpdate($this->Post, $this->ContestID, $this->UserID, $this->SeriesID);
        if ($AuctionStatus) {
            $this->Return['Message'] = "User status successfully updated.";
            $this->Return['Data']['DraftUserLiveTime'] = date('Y-m-d H:i:s');
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User status already updated.";
        }
    }

    /*
      Name:             roundUpdate
      Description:  draft round update.
      URL:          /api_admin/auctionDrafts/roundUpdate/
     */

    public function roundUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('DraftLiveRound', 'DraftLiveRound', 'trim|required|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->roundUpdate($this->Post, $this->ContestID, $this->SeriesID);
        $this->Return['Message'] = "User status successfully updated.";
    }

    /*
      Name:             getUserInLive
      Description:  get user on going to live
      URL:          /api_admin/auctionDrafts/getUserInLive/
     */

    public function getUserInLive_post() {
        /* check game live */
        $UserList = $this->SnakeDrafts_model->getUserInLive();
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'];
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Name:             checkUserDraftInlive
      Description:  get user on going to live
      URL:          /api_admin/auctionDrafts/checkUserInliveDraft/
     */

    public function checkUserDraftInlive_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->validation($this);  /* Run validation */
        /* check game live */
        $UserList = $this->SnakeDrafts_model->checkUserDraftInlive($this->Post, $this->SeriesID, $this->ContestID);
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'][0];
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Name:             draftPlayerSold
      Description:  draft player sold.
      URL:          /api_admin/auctionDrafts/draftPlayerSold/
     */

    public function draftPlayerSold_post() {
        $this->form_validation->set_rules('PlayerStatus', 'PlayerStatus', 'trim|in_list[Sold,Unsold]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('PlayerRole', 'PlayerRole', 'trim');
        $this->form_validation->validation($this);  /* Run validation */
        $Draft = $this->SnakeDrafts_model->draftPlayerSold($this->Post, $this->SeriesID, $this->ContestID, $this->UserID, @$this->PlayerID);
        $Draft['Data']['SeriesGUID'] = $this->Post['SeriesGUID'];
        $Draft['Data']['ContestGUID'] = $this->Post['ContestGUID'];
        $this->Return['ResponseCode'] = 200;
        $this->Return['Data'] = $Draft['Data'];
        $this->Return['Message'] = $Draft['Message'];
    }

    /*
      Name:             getRoundNextUserInLive
      Description:  get round next user in live
      URL:          /api_admin/auctionDrafts/getRoundNextUserInLive/
     */

    public function getRoundNextUserInLive_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->validation($this);  /* Run validation */

        /* check game live */
        $UserList = $this->SnakeDrafts_model->getRoundNextUserInLive($this->Post, $this->SeriesID, $this->ContestID);
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'];
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Description:  Use to update user status.
     */

    public function changeUserStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('DraftUserStatus', 'DraftUserStatus', 'trim|required|in_list[Online,Offline]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->SnakeDrafts_model->changeUserStatus($this->Post, $this->UserID, $this->ContestID);
        $this->Return['Message'] = "Status has been changed.";
    }

    /**
     * Function Name: validateCheckAuctionInLive
     * Description:   To validate if check auction in live
     */
    public function validateCheckAuctionInLive($ContestGUID) {
        $AuctionGames = $this->SnakeDrafts_model->getContests('ContestID,AuctionStatusID', array('ContestGUID' => $ContestGUID), TRUE, 1);
        if ($AuctionGames['Data']['TotalRecords'] > 0) {
            if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 1) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction not started');
                return FALSE;
            } else if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 5) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction completed');
                return FALSE;
            } else if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 3) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction cancelled');
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
        if ($TotalJoinedContest > 0) {
            $this->form_validation->set_message('validateAnyUserJoinedContest', 'You can not ' . $Type . ' this contest');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateMatchStatus
     * Description:   To validate match status
     */
    public function validateMatchStatus($UserTeamGUID) {
        $MatchStatus = $this->db->query("SELECT E.StatusID FROM sports_users_teams UT, tbl_entity E WHERE UT.MatchID = E.EntityID AND UT.UserTeamGUID = '" . $UserTeamGUID . "' ")->row()->StatusID;
        if ($MatchStatus != 1) {
            $this->form_validation->set_message('validateMatchStatus', 'Sorry, you can not edit team.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateMatchStatusInnings
     * Description:   To validate match status & innings
     */
    public function validateMatchStatusInnings($MatchInning) {
        if (empty($MatchInning)) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'The MatchInning field is required.');
            return FALSE;
        }
        $MatchData = $this->Sports_model->getMatches('MatchType,Status,MatchScoreDetails', array('MatchID' => $this->MatchID));
        if ($MatchData['Status'] != 'Running' || empty($MatchData['MatchScoreDetails'])) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'You can not create team.');
            return FALSE;
        }
        if ($MatchData['MatchType'] == 'Test' && !in_array($MatchInning, array('First', 'Second', 'Third', 'Fourth'))) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'Match Inning field must be one of: First,Second,Third,Fourth.');
            return FALSE;
        }
        if ($MatchData['MatchType'] != 'Test' && !in_array($MatchInning, array('First', 'Second'))) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'Match Inning field must be one of: First,Second.');
            return FALSE;
        }
        $MatchOvers = ($MatchInning == 'First') ? $MatchData['MatchScoreDetails']['TeamScoreLocal']['Overs'] : $MatchData['MatchScoreDetails']['TeamScoreVisitor']['Overs'];
        $MatchOverBalls = (!empty($MatchOvers)) ? $this->getOverBalls($MatchOvers) : 0; // Over should be between 0.1 To 22.5 (1-137 Balls)
        if ($MatchOverBalls < 1 || $MatchOverBalls > 137) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'You can create team between 0.1 to 22.5 overs.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: getOverBalls
     * Description:   To get over balls
     */
    public function getOverBalls($TotalOvers) {
        $TotalBalls = 0;
        if (is_float($TotalOvers)) {
            list($Overs, $Balls) = explode('.', $TotalOvers);
            $TotalBalls = ($Overs * 6) + $Balls;
        } else {
            $TotalBalls = $TotalOvers * 6;
        }
        return $TotalBalls;
    }

    /**
     * Function Name: validateUserJoinContest
     * Description:   To validate user join contest
     */
    public function validateUserJoinContest($ContestGUID) {
        $ContestData = $this->SnakeDrafts_model->getContests('MatchID,ContestSize,Privacy,IsPaid,EntryType,EntryFee,UserInvitationCode,ContestID,ContestType,UserJoinLimit,CashBonusContribution', array('ContestID' => $this->ContestID));
        if (!empty($ContestData)) {

            /* Get Match Status */
            $MatchData = $this->Sports_model->getMatches('MatchType,Status,MatchScoreDetails', array('MatchID' => $ContestData['MatchID']));
            if ($ContestData['ContestType'] == 'InPlay') {

                /* To check Join Inning Field */
                if (empty($this->Post['JoinInning'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'The JoinInning field is required.');
                    return FALSE;
                }
                if ($MatchData['MatchType'] == 'Test' && !in_array($this->Post['JoinInning'], array('First', 'Second', 'Third', 'Fourth'))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'JoinInning field must be one of: First,Second,Third,Fourth.');
                    return FALSE;
                }
                if ($MatchData['MatchType'] != 'Test' && !in_array($this->Post['JoinInning'], array('First', 'Second'))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'JoinInning field must be one of: First,Second.');
                    return FALSE;
                }
                if ($MatchData['Status'] != 'Running') {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join only running matches contest.');
                    return FALSE;
                }

                /* To check Over Condition between (0.1 - 22.5) */
                $MatchOvers = ($this->Post['JoinInning'] == 'First') ? $MatchData['MatchScoreDetails']['TeamScoreLocal']['Overs'] : $MatchData['MatchScoreDetails']['TeamScoreVisitor']['Overs'];
                $MatchOverBalls = (!empty($MatchOvers)) ? $this->getOverBalls($MatchOvers) : 0; // Over should be between 0.1 To 22.5 (1-137 Balls)
                if ($MatchOverBalls < 1 || $MatchOverBalls > 137) {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join contest between 0.1 to 22.5 overs.');
                    return FALSE;
                }

                /* Check Join Contest Size Limit */
                if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE  `JoinInning` = "' . $this->Post['JoinInning'] . '" AND `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Join Contest limit is exceeded.');
                    return FALSE;
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID'], 'JoinInning' => $this->Post['JoinInning']);
                if ($ContestData['EntryType'] == 'Multiple') {
                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->SnakeDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                // if (!$this->SnakeDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID'], 'MatchInning' => $this->Post['JoinInning']))) {
                //     $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                //     return FALSE;
                // }
            } else {

                if ($MatchData['Status'] != 'Pending') {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join only upcoming matches contest.');
                    return FALSE;
                }

                /* Check Join Contest Size Limit */

                if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Join Contest limit is exceeded.');
                    return FALSE;
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID']);
                if ($ContestData['EntryType'] == 'Multiple') {

                    /* Get User Join Limit */
                    if ($this->db->query('SELECT COUNT(*) AS `TotalJoined` FROM `sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'] . ' AND UserID = ' . $this->SessionUserID)->row()->TotalJoined >= $ContestData['UserJoinLimit']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'You can join this contest only ' . $ContestData['UserJoinLimit'] . ' times.');
                        return FALSE;
                    }


                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->SnakeDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                // if (!$this->SnakeDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID']))) {
                //     $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                //     return FALSE;
                // }

                /* To Check Contest Privacy */
                if ($ContestData['Privacy'] == 'Yes') {
                    if (empty($this->Post['UserInvitationCode'])) {
                        $this->form_validation->set_message('validateUserJoinContest', 'The User Invitation Code field is required.');
                        return FALSE;
                    }
                    if ($ContestData['UserInvitationCode'] != $this->Post['UserInvitationCode']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'Invalid User Invitation Code.');
                        return FALSE;
                    }
                }
            }

            /* To Check Wallet Amount, If Contest Is Paid */
            if ($ContestData['IsPaid'] == 'Yes') {
                $this->load->model('Users_model');
                $UserData = $this->Users_model->getUsers('TotalCash,WalletAmount,WinningAmount,CashBonus', array('UserID' => $this->SessionUserID));
                $this->Post['WalletAmount'] = $UserData['WalletAmount'];
                $this->Post['WinningAmount'] = $UserData['WinningAmount'];
                $this->Post['CashBonus'] = $UserData['CashBonus'];

                $ContestEntryRemainingFees = @$ContestData['EntryFee'];
                $CashBonusContribution = @$ContestData['CashBonusContribution'];
                $WalletAmountDeduction = 0;
                $WinningAmountDeduction = 0;
                $CashBonusDeduction = 0;
                if (!empty($CashBonusContribution) && @$UserData['CashBonus'] > 0) {
                    $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                    if (@$UserData['CashBonus'] >= $CashBonusContributionAmount) {
                        $CashBonusDeduction = $CashBonusContributionAmount;
                    } else {
                        $CashBonusDeduction = @$UserData['CashBonus'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
                }
                if ($ContestEntryRemainingFees > 0 && @$UserData['WinningAmount'] > 0) {
                    if (@$UserData['WinningAmount'] >= $ContestEntryRemainingFees) {
                        $WinningAmountDeduction = $ContestEntryRemainingFees;
                    } else {
                        $WinningAmountDeduction = @$UserData['WinningAmount'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
                }
                if ($ContestEntryRemainingFees > 0 && @$UserData['WalletAmount'] > 0) {
                    if (@$UserData['WalletAmount'] >= $ContestEntryRemainingFees) {
                        $WalletAmountDeduction = $ContestEntryRemainingFees;
                    } else {
                        $WalletAmountDeduction = @$UserData['WalletAmount'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
                }
                if ($ContestEntryRemainingFees > 0) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Insufficient wallet amount.');
                    return FALSE;
                }
            }
            $this->Post['IsPaid'] = $ContestData['IsPaid'];
            $this->Post['EntryFee'] = $ContestData['EntryFee'];
            $this->Post['CashBonusContribution'] = $ContestData['CashBonusContribution'];
            return TRUE;
        } else {
            $this->form_validation->set_message('validateUserJoinContest', 'Invalid ContestGUID.');
            return FALSE;
        }
    }

    /*
      Function Name : getPrivateContest
      Description : To get private contest by contest code
     */

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('UserInvitationCode', 'UserInvitationCode', 'trim|required');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */

        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserInvitationCode' => @$this->Post['UserInvitationCode'], "SeriesID" => $this->SeriesID)), FALSE);
        if ($ContestData) {
            if (isset($ContestData['Data']['Records'])) {
                $this->Return['Data'] = array();
            } else {
                $this->Return['Data'] = $ContestData;
            }
        }
    }

    /*
      Description: To get joined contest users data
     */

    public function getJoinedContestsUsers_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $JoinedContestData = $this->SnakeDrafts_model->getJoinedContestsUsers(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => @$this->ContestID, 'SeriesID' => @$this->SeriesID, 'SessionUserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description: To get joined contest users data
     */

    public function contestUserLeaderboard_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $JoinedContestData = $this->SnakeDrafts_model->contestUserLeaderboard(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => @$this->ContestID, 'SeriesID' => @$this->SeriesID, 'SessionUserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Name:             draftTeamSubmit
      Description:  user submit draft team after complete draft.
      URL:          /api/snakeDrafts/draftTeamSubmit/
     */

    public function draftTeamSubmit_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeams', 'UserTeams', 'trim');
        $this->Post['UserTeams'] = json_decode($this->Post['UserTeams'], true);
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $ContestDetails = $this->SnakeDrafts_model->getContests('RosterSize,PlayedRoster,BatchRoster,GameType,WeekStart', array('ContestID' => $this->ContestID));
        $RosterSize = $ContestDetails['RosterSize'];
        $PlayedRoster = $ContestDetails['PlayedRoster'];
        $BenchRoster = $ContestDetails['BatchRoster'];
        $WeekStart = $ContestDetails['WeekStart'];
        $GameType = $ContestDetails['GameType'];
        if (!empty($this->Post['UserTeams']) && is_array($this->Post['UserTeams'])) {
            if (count($this->Post['UserTeams']) > $RosterSize) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team length can't greater than " . $RosterSize;
                exit;
            }
            $PlayerPoisitions = array_count_values(array_column($this->Post['UserTeams'], 'TeamPlayingStatus'));
            if ($PlayerPoisitions['Play'] != $PlayedRoster) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Minimum $PlayedRoster start lineup teams is required.";
                exit;
            } else if (!empty($PlayerPoisitions['Bench']) && $PlayerPoisitions['Bench'] != $BenchRoster) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Minimum $BenchRoster bench lineup teams is required.";
                exit;
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Teams Required.";
            exit;
        }
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')) - 14400;
        $Sql = "SELECT MatchID FROM sports_matches M INNER JOIN tbl_entity E ON E.EntityID=M.MatchID"
                . " WHERE E.GameSportsType = '" . $GameType . "' AND M.WeekID='" . $WeekStart . "' AND M.MatchStartDateTime >='" . date('Y-m-d H:i:s', $CurrentDateTime) . "' "
                . " LIMIT 1";
        $IsWeekStart = $this->Sports_model->customQuery($Sql, TRUE);
        if (!empty($IsWeekStart)) {
            if (!$this->SnakeDrafts_model->draftTeamPlayersSubmit($this->Post, $this->UserTeamID, $this->SeriesID, $this->SessionUserID, $GameType)) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Message'] = "Team Submitted successfully.";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Contest Week Started.";
        }
    }

    /*
      Description: To get free agent team
     */

    public function getFreeAgentTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->set_rules('SubGameTypeKey', 'SubGameTypeKey', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $AllMatchesList = $this->SnakeDrafts_model->getFreeAgentTeam($this->ContestID, $this->Post['WeekID'], $this->Post['SubGameTypeKey']);
        if (!empty($AllMatchesList)) {
            $this->Return['Data'] = $AllMatchesList;
        }
    }

    /*
      Description: To get my squad team
     */

    public function getMySquadTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->set_rules('SubGameTypeKey', 'SubGameTypeKey', 'trim|required');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $AllMatchesList = $this->SnakeDrafts_model->getMySquadTeam($this->ContestID, $this->Post['WeekID'], $this->SessionUserID, $this->Post['SubGameTypeKey'], @$this->StatusID);
        if (!empty($AllMatchesList)) {
            $this->Return['Data'] = $AllMatchesList;
        }
    }

    /*
      Description: To get my squad team
     */

    public function getMyPlayingRooster_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->set_rules('SubGameTypeKey', 'SubGameTypeKey', 'trim|required');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $AllMatchesList = $this->SnakeDrafts_model->getMyPlayingRooster($this->ContestID, $this->Post['WeekID'], $this->UserID, $this->Post['SubGameTypeKey'], @$this->StatusID);
        if (!empty($AllMatchesList)) {
            $this->Return['Data'] = $AllMatchesList;
        }
    }

    /*
      Description: To add waiver wire teams
     */

    public function requestFreeAgentUserTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('DropTeamID', 'TeamGUID', 'trim|required');
        $this->form_validation->set_rules('CatchTeamID', 'CatchTeamGUID', 'trim|required');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->set_rules('Type', 'Type', 'trim|required|in_list[Free,Wire]');
        $this->form_validation->validation($this);  /* Run validation */

        $IsApply = $this->SnakeDrafts_model->requestFreeAgentUserTeam($this->ContestID, $this->SessionUserID, $this->UserTeamID, $this->Post['DropTeamID'], $this->Post['CatchTeamID'], $this->Post['WeekID'], $this->Post['Type']);
        if ($IsApply) {
            if ($IsApply == 2) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Sorry the team will start match next 24 Hours.";
            } else {
                $this->Return['Message'] = "Successfully changed";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry the team is not available in free agent.";
        }
    }

    /*
      Description: To add waiver wire teams
     */

    public function requestWaiverWireUserTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('WaiverTeams', 'WaiverTeams', 'trim|required');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->set_rules('Type', 'Type', 'trim|required|in_list[Free,Wire]');
        $this->form_validation->validation($this);  /* Run validation */

        $IsApply = $this->SnakeDrafts_model->requestWaiverWireUserTeam($this->ContestID, $this->SessionUserID, $this->UserTeamID, $this->Post['WaiverTeams'], $this->Post['WeekID'], $this->Post['Type']);
        if ($IsApply) {
            $this->Return['Message'] = "Successfully request added";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry the team request failed, Please try gain.";
        }
    }

    /*
      Description: To cancel waiver wire teams
     */

    public function cancelRequestWaiverWireUserTeam_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('WireID', 'WireID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $IsApply = $this->SnakeDrafts_model->cancelRequestWaiverWireUserTeam($this->ContestID, $this->SessionUserID, $this->Post['WireID']);
        if ($IsApply) {
            $this->Return['Message'] = "Successfully request cancelled";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry the cancel request failed, Please try gain.";
        }
    }

    /*
      Description: To get waiver transaction
     */

    public function getWaiverTransaction_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('WeekID', 'WeekID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $TransactionHistory = $this->SnakeDrafts_model->getWaiverTransaction($this->ContestID, $this->Post['WeekID'], @$this->UserID);
        if ($TransactionHistory) {
            $this->Return['Data'] = $TransactionHistory;
        }
    }

    /*
      Description: To get waiver transaction
     */

    public function getWaiverTransactionByWeek_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */
        $TransactionHistory = $this->SnakeDrafts_model->getWaiverTransactionByWeek($this->ContestID);
        if ($TransactionHistory) {
            $this->Return['Data'] = $TransactionHistory;
        }
    }

        /*
      Description: To get waiver transaction
     */

    public function getDraftPlayerDropAddTransactions_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */
        $TransactionHistory = $this->SnakeDrafts_model->getDraftPlayerDropAddTransactions($this->ContestID);
        if ($TransactionHistory) {
            $this->Return['Data'] = $TransactionHistory;
        }
    }

        /*
      Description: To add waiver wire teams
     */

    public function removeDraftPlayer_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('PlayerSelectTypeRole', 'PlayerSelectTypeRole', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $IsApply = $this->SnakeDrafts_model->removeDraftPlayer($this->ContestID, $this->SessionUserID, $this->UserTeamID, $this->PlayerID, $this->Post['PlayerSelectTypeRole']);
        if ($IsApply['Status'] == 1) {
             $this->Return['Message'] = $IsApply['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $IsApply['Message'];
        }
    }

        /*
      Description: To add waiver wire teams
     */

    public function addDraftPlayer_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('PlayerSelectTypeRole', 'PlayerSelectTypeRole', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $IsApply = $this->SnakeDrafts_model->addDraftPlayer($this->ContestID, $this->SessionUserID, $this->UserTeamID, $this->PlayerID, $this->Post['PlayerSelectTypeRole']);
        if ($IsApply['Status'] == 1) {
             $this->Return['Message'] = $IsApply['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $IsApply['Message'];
        }
    }
    /*
      Description: Contest invite friends
     */

    public function contestInviteFriends_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('Phone', 'Phone', 'trim');
        $this->form_validation->validation($this);  /* Run validation */

        $TransactionHistory = $this->SnakeDrafts_model->InviteContest($this->Post, $this->ContestID, $this->SessionUserID);
        if ($TransactionHistory) {
            $this->Return['Message'] = "Successfully invited";
        }
    }

    /*
      Description: To get my squad team
     */

    public function getContestInvite_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $Invite = $this->SnakeDrafts_model->getContestInvite($this->ContestID, $this->SessionUserID);
        if (!empty($Invite)) {
            $this->Return['Data'] = $Invite;
        }
    }

    /*
      Name:         Add Private Contest
      Description:  Use to add private contest to system.
      URL:          /SnakeDrafts/addPrivateContest/
     */

    public function addPrivateContest_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]|callback_addWeekEndforSeasonLong');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required|in_list[Nfl,Ncaaf]');
        $this->form_validation->set_rules('SubGameType', 'SubGameType', 'trim|required');
        $this->form_validation->set_rules('ScoringType', 'ScoringType', 'trim|required|in_list[PointLeague,RoundRobin]');
        $this->form_validation->set_rules('ContestDuration', 'Contest Duration', 'trim|required|in_list[Weekly,SeasonLong]|callback_checkUserWalletFee[ContestDuration]');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'League Join Date Time', 'trim|required');
        $this->form_validation->set_rules('LeagueType', 'LeagueType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('InvitePermission', 'InvitePermission', 'trim|required|in_list[ByCreator,ByAnyone]');
        $this->form_validation->set_rules('PrivatePointScoring', 'Point Scoring', 'trim');
        $this->form_validation->set_rules('PlayOff', 'PlayOff', 'trim');
        $this->form_validation->set_rules('WeekStart', 'WeekStart', 'trim');
        $this->form_validation->set_rules('WeekEnd', 'WeekEnd', 'trim');
        $this->form_validation->set_rules('MinimumUserJoined', 'MinimumUserJoined', 'trim|required|callback_validateMinimumUserJoin[MinimumUserJoined]');
        $this->form_validation->set_rules('RosterSize','RosterSize','trim|required|integer');
        // $this->form_validation->set_rules('AllowPrivateContestFree', 'AllowPrivateContestFree', 'trim|required|in_list[Yes,No]' . ($this->Post['AllowPrivateContestFree'] == "No" ? "|callback_validatePaidContest" : ""));
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning'])) {
            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                $TotalPercent += $Value['Percent'];
                $TotalWinningAmount += $TotalWinners * $Value['WinningAmount'];
            }
            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }
            /* Check Total Percent */
            if ($TotalPercent < 100 || $TotalPercent > 100) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners Percent should be 100%.";
                exit;
            }
            /* Check Total Winning Amount */
            if ($TotalWinningAmount != $this->Post['WinningAmount']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
            /* Check user wallet amount */
            if ($this->Post['AllowPrivateContestFree'] == "Yes") {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
        }
        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $DraftTotalRounds = 0;
        $ContestSize = $this->Post['ContestSize'];
        $TeamSize = ($this->Post['GameType'] == "Nfl") ? 32 : 130;
        $this->Post['DraftTotalRounds'] = round($TeamSize / $ContestSize);
        $ContestID = $this->SnakeDrafts_model->addPrivateContest($this->Post, $this->SessionUserID, @$this->MatchID, $this->SeriesID);
        if (!$ContestID) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Contest created and joined successfully.";
            $this->Return['Data'] = $this->SnakeDrafts_model->getContests('CustomizeWinning,MatchScoreDetails,UserID,ContestFormat,ContestType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,UserInvitationCode,SeriesGUID', array('ContestID' => $ContestID));
        }
    }    
    /*
      Name:         Get Roster Details
      Description:  Use to Roster Details According to size.
      URL:          /SnakeDrafts/RosterDetails/
     */
    public function RosterDetails_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('RosterSize','RosterSize','trim|required|integer');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->Return['Message']    = "Success.";
        $this->Return['Data']       =  GetRosterDetails($this->Post['RosterSize']);
    }
    /*
      Name:         checkUserWallet
      Description:  CallBack Use to Check user wallet amt.
     */
    public function checkUserWalletFee($ContestDuration) {
        $PrivateContestFeeSeasonLong = $this->Settings_model->getSiteSettings("PrivateContestFeeSeasonLong");
        $PrivateContestFeeWeek = $this->Settings_model->getSiteSettings("PrivateContestFeeWeek");
        $UserData = $this->Users_model->getUsers('WalletAmount,WinningAmount', array('UserID' => $this->SessionUserID));
        $this->Post['WalletAmount'] = $UserData['WalletAmount'];
        $this->Post['UserWinningAmount'] = $UserData['WinningAmount'];
        $TotalUserWalletAmount = $UserData['WalletAmount'] + $UserData['WinningAmount'];
        if ($ContestDuration == 'Weekly') {
            $this->Post['UserPrivateContestFee'] = $PrivateContestFeeWeek;
            $TotalJoinFee = $PrivateContestFeeWeek + $this->Post['EntryFee'];
            if ($TotalUserWalletAmount >= $TotalJoinFee) {
                return TRUE;
            }
            $this->form_validation->set_message('checkUserWalletFee', 'Wallet Amount must be $'.$TotalJoinFee.'.');
            return FALSE;
        }elseif ($ContestDuration == 'SeasonLong') {
            $this->Post['UserPrivateContestFee'] = $PrivateContestFeeSeasonLong;
            $TotalJoinFee = $PrivateContestFeeSeasonLong + $this->Post['EntryFee'];
            if ($TotalUserWalletAmount >= $TotalJoinFee) {
                return TRUE;
            }
            $this->form_validation->set_message('checkUserWalletFee', 'Wallet Amount must be $'.$TotalJoinFee.'.');
            return FALSE;
        }
        return FALSE;
    }
    /*
      Name:         validateMinimumUserJoin
      Description:  CallBack Use to validate Minimum User Join.
     */
    public function validateMinimumUserJoin($MinimumUserJoined) {

        if ($MinimumUserJoined != 2) {
                $this->form_validation->set_message('validateMinimumUserJoin', 'Minimum user joined limit should be 2 for Weekly.');
                return FALSE;
        }

        return TRUE;

        if ($this->Post['ContestDuration'] == 'Weekly') {
            if ($MinimumUserJoined != 2) {
                $this->form_validation->set_message('validateMinimumUserJoin', 'Minimum user joined limit should be 2 for Weekly.');
                return FALSE;
            }
        }elseif ($this->Post['ContestDuration'] == 'SeasonLong') {
            if ($this->Post['ContestSize'] == 6 || $this->Post['ContestSize'] == 8) {
                if ($MinimumUserJoined != 4) {
                    $this->form_validation->set_message('validateMinimumUserJoin', 'Minimum user joined limit should be 4 for Season Long.');
                    return FALSE;
                }
            }
            if ($this->Post['ContestSize'] == 10 || $this->Post['ContestSize'] == 12) {
                if ($MinimumUserJoined != 8) {
                    $this->form_validation->set_message('validateMinimumUserJoin', 'Minimum user joined limit should be 8 for Season Long.');
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    public function addWeekEndforSeasonLong(){
        if ($this->Post['ContestDuration'] == 'SeasonLong') {
            $SeriesData = $this->Sports_model->getSeries("SeriesType", array('SeriesID' => $this->SeriesID), FALSE, @$this->Post['PageNo'], @$this->Post['PageSize']);
            if (!empty($SeriesData) && $SeriesData['SeriesType'] == 'Regular') {
                $this->Post['WeekEnd'] = 20;
            }elseif (!empty($SeriesData) && $SeriesData['SeriesType'] == 'Playoffs') {
                $this->Post['WeekEnd'] = 20;
            }else{
                $this->form_validation->set_message('addWeekEndforSeasonLong', 'Something went wrong. please try again.');
                return FALSE;
            }
        }else{
            $this->Post['WeekEnd'] = $this->Post['WeekStart'];
        }
        return TRUE;

    }
}

?>