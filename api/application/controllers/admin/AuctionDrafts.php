<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class AuctionDrafts extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('AuctionDrafts_model');
        $this->load->model('Sports_model');
    }

    /*
      Name: 			add
      Description: 	Use to add contest to system.
      URL: 			/api_admin/contest/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('LeagueType', 'LeagueType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('LeagueJoinTime', 'LeagueJoinTime', 'trim|required');
        $this->form_validation->set_rules('MinimumUserJoined', 'Minimum User Joined', 'trim' . (!empty($this->Post['IsConfirm']) && $this->Post['IsConfirm'] == 'No' ? '|required|integer' : ''));
        $this->form_validation->set_rules('LeagueJoinDateTime', 'LeagueJoinDateTime', 'trim|required');
        $this->form_validation->set_rules('ContestDuration', 'Contest Duration', 'trim|required');
        $this->form_validation->set_rules('DailyDate', 'Day Date', 'trim' . (!empty($this->Post['ContestDuration']) && $this->Post['ContestDuration'] == 'Daily' ? '|required' : ''));
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required');
        $this->form_validation->set_rules('ContestSize', 'Draft Size', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('AdminPercent', 'AdminPercent', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('ScoringType', 'ScoringType', 'trim|required|in_list[PointLeague,RoundRobin]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required|in_list[Nfl,Ncaaf]');
        $this->form_validation->set_rules('SubGameType', 'SubGameType', 'trim|required');
        $this->form_validation->set_rules('WeekStart', 'WeekStart', 'trim');
        $this->form_validation->set_rules('WeekEnd', 'WeekEnd', 'trim');
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning'])) {
            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required');
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
        $TeamSize    = ($this->Post['GameType'] == "Nfl") ? 32 : 130;
        $this->Post['DraftTotalRounds'] = round($TeamSize / $ContestSize);
        if (!$this->AuctionDrafts_model->addContest($this->Post, $this->SessionUserID, $this->MatchID, $this->SeriesID)) {
            $this->Return['ResponseCode'] = 500;
            //$this->Return['Message'] = "An error occurred, please try again later.";
            $this->Return['Message'] = "Players not available.";
        } else {
            $this->Return['Message'] = "Contest created successfully.";
        }
    }

    /*
      Name: 			edit
      Description: 	Use to update contest to system.
      URL: 			/api_admin/contest/edit/
     */

    public function edit_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[update]');
        $this->form_validation->set_rules('LeagueJoinTime', 'LeagueJoinTime', 'trim|required');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestDuration', 'Contest Duration', 'trim|required');
        $this->form_validation->set_rules('DailyDate', 'Day Date', 'trim' . (!empty($this->Post['ContestDuration']) && $this->Post['ContestDuration'] == 'Daily' ? '|required' : ''));
        $this->form_validation->set_rules('MinimumUserJoined', 'MinimumUserJoined', 'trim|required|numeric');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'LeagueJoinDateTime', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal,Reverse,InPlay,Hot,Champion,Practice,More]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|numeric');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('ScoringType', 'ScoringType', 'trim|required|in_list[PointLeague,RoundRobin]');
        $this->form_validation->set_rules('GameType', 'GameType', 'trim|required|in_list[Nfl,Ncaaf]');
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
        $this->AuctionDrafts_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Description: To get joined contests data
     */

    public function getUserJoinedContests_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contests Data */
        $JoinedContestData = $this->AuctionDrafts_model->getJoinedContests(@$this->Post['Params'], array_merge($this->Post, array('SessionUserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->set_rules('StatusID', 'StatusID', 'trim');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'ContestType' => @$this->Post['ContestType'], 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->UserID,'StatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    function getSportsGameTypeConfiguration_post() {
        $this->form_validation->set_rules('SubGameType', 'SubGameType', 'trim');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $SportsType = footballGetConfiguration($this->Post['SubGameType']); 
        if (!empty($SportsType)) {
            $this->Return['Data'] = $SportsType;
        }
    }

    /*
      Description: To Cancel Contest
     */

    public function cancel_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Cancel Contests */
        $this->AuctionDrafts_model->cancelContest(@$this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest cancelled successfully.";
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
        // if ($TotalJoinedContest > 0){
        // 	$this->form_validation->set_message('validateAnyUserJoinedContest', 'You can not '.$Type.' this contest');
        // 	return FALSE;
        // }
        // else{
        return TRUE;
        // }
    }

    /**
     * Function Name: validateContestStatus
     * Description:   To validate contest status
     */
    public function validateContestStatus($ContestGUID) {
        $ContestData = $this->AuctionDrafts_model->getContests('Status,IsPaid,SeriesName,ContestName,MatchNo,TeamNameLocal,TeamNameVisitor,EntryFee', array('ContestID' => $this->ContestID));
        if ($ContestData['Status'] == 'Pending') {
            $this->Post['IsPaid'] = $ContestData['IsPaid'];
            $this->Post['EntryFee'] = $ContestData['EntryFee'];
            $this->Post['SeriesName'] = $ContestData['SeriesName'];
            $this->Post['ContestName'] = $ContestData['ContestName'];
            $this->Post['MatchNo'] = $ContestData['MatchNo'];
            $this->Post['TeamNameLocal'] = $ContestData['TeamNameLocal'];
            $this->Post['TeamNameVisitor'] = $ContestData['TeamNameVisitor'];
            return TRUE;
        } else {
            $this->form_validation->set_message('validateContestStatus', 'You can not cancel this contest.');
            return FALSE;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getContestWinningUsers_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->AuctionDrafts_model->getContestWinningUsers(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData['Data'];
        }
    }

    /*
      Description: 	Use to update contest status.
      URL: 			/api_admin/entity/changeStatus/
     */

    public function changeStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->Entity_model->updateEntityInfo($this->ContestID, array("StatusID" => $this->StatusID));
        $this->Return['Data'] = $this->AuctionDrafts_model->getContests('SeriesName,LeagueJoinDateTime,LeagueType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType', array_merge($this->Post, array('ContestID' => $this->ContestID, 'SessionUserID' => $this->SessionUserID)));
        $this->Return['Message'] = "Status has been changed.";
    }

}

?>