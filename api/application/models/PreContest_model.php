<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class PreContest_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
        $this->dbr = "";
    }
	function __destruct() {
        parent::__destruct();
    }

    /*
      Description:    ADD contest to system.
     */

          /*
      Description:    ADD contest to system.
     */

    function searchForId($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['Owners'] === $id) {
                return $array[$key];
            }
        }
        return null;
    }


    function addContest($Input = array(), $SessionUserID, $MatchID, $SeriesID, $StatusID = 1) {

        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();

        $Input['LeagueJoinDateTime'] = date('Y-m-d',strtotime($Input['LeagueJoinDateTime'])).' '.$Input['LeagueJoinTime'];
        //$LeagueJoinDateTime = strtotime($Input['LeagueJoinDateTime']);
        if(!empty($Input['TimeZone'])){
            $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime($Input['TimeZone'].' minutes', 0);
        }else{
            $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('+300 minutes', 0);
        }
        
        /* Add contest to contest table . */
        $RoosterSize = footballGetConfiguration($Input['SubGameType']);
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);
        $RoosterConfiguration = footballGetConfigurationPlayersRooster($Input['ContestSize']);
        $InsertData = array_filter(array(
            "UserID" => $SessionUserID,
            "ContestName" => @$Input['ContestName'],
            "LeagueType" => @$Input['LeagueType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "ContestFormat" => @$Input['ContestFormat'],
            'DraftPlayerSelectionCriteria' => (!empty($RoosterConfiguration)) ? json_encode($RoosterConfiguration) : null,
            "ContestType" => @$Input['ContestType'],
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => @$Input['IsPaid'],
            "IsConfirm" => @$Input['IsConfirm'],
            "IsAutoCreate" => @$Input['IsAutoCreate'],
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "SubGameType" => @$Input['SubGameType'],
            "GameType" => @$Input['GameType'],
            "ScoringType" => @$Input['ScoringType'],
            "PlayOff" => @$Input['PlayOff'],
            "WeekStart" => @$Input['WeekStart'],
            "WeekEnd" => @$Input['WeekStart'],
            "GamePlayType" => @$Input['GamePlayType'],
            "GameTimeLive" => @$Input['GameTimeLive'],
            "ContestDuration" => @$Input['ContestDuration'],
            "DailyDate" => @$Input['DailyDate'],
            "AdminPercent" => @$Input['AdminPercent'],
            "ContestSize" => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
            "EntryFee" => (@$Input['IsPaid'] == 'Yes') ? @$Input['EntryFee'] : 0,
            "NoOfWinners" => (@$Input['IsPaid'] == 'Yes') ? @$Input['NoOfWinners'] : 1,
            "EntryType" => @$Input['EntryType'],
            "UserJoinLimit" => (@$Input['EntryType'] == 'Multiple') ? @$Input['UserJoinLimit'] : 1,
            "CashBonusContribution" => @$Input['CashBonusContribution'],
            "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : json_encode(array($defaultCustomizeWinningObj)),
            "SeriesID" => @$SeriesID,
            "MatchID" => @$MatchID,
            "UserInvitationCode" => random_string('alnum', 6),
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "DraftTotalRounds" => $RoosterArray['RosterSize'],
            "DraftLiveRound" => 1,
            "RosterSize" => $RoosterArray['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0
        ));
       if ($Input['WeekStart'] == 0) {
           $InsertData  =   array_merge($InsertData, array(
                                'WeekStart' => $Input['WeekStart'],
                                'WeekEnd'   => $Input['WeekStart']
                            ));
        }
        $this->db->insert('sports_pre_contest', $InsertData);
        /*$PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $Input['WeekStart'],$Input['ContestDuration'],$Input['DailyDate']);
        if(!$PlayerIs) return false;*/
        $insert_id = $this->db->insert_id();

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $insert_id;
    }

    /*
      Description: Update contest to system.
     */

    function updateContest($Input = array(), $SessionUserID, $PreContestID, $StatusID = 1) {
        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];
        $Input['LeagueJoinDateTime'] = date('Y-m-d',strtotime($Input['LeagueJoinDateTime'])).' '.$Input['LeagueJoinTime'];
        $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']);

        $RoosterSize = footballGetConfiguration($Input['SubGameType']);
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);


        /* Add contest to contest table . */
        $UpdateData = array_filter(array(
            "ContestName" => @$Input['ContestName'],
            "ContestFormat" => @$Input['ContestFormat'],
            "ContestType" => @$Input['ContestType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => @$Input['IsPaid'],
            "IsConfirm" => @$Input['IsConfirm'],
            "IsAutoCreate" => @$Input['IsAutoCreate'],
            "GameType" => @$Input['GameType'],
            "GameTimeLive" => @$Input['GameTimeLive'],
            "AdminPercent" => @$Input['AdminPercent'],
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "SubGameType" => @$Input['SubGameType'],
            "ScoringType" => @$Input['ScoringType'],
            "PlayOff" => @$Input['PlayOff'],
            "WeekStart" => @$Input['WeekStart'],
            "WeekEnd" => @$Input['WeekStart'],
            "ContestDuration" => @$Input['ContestDuration'],
            "DailyDate" => @$Input['DailyDate'],
            "DraftTotalRounds" => $RoosterArray['RosterSize'],
            "ContestSize" => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
            "EntryFee" => (@$Input['IsPaid'] == 'Yes') ? @$Input['EntryFee'] : 0,
            "NoOfWinners" => (@$Input['IsPaid'] == 'Yes') ? @$Input['NoOfWinners'] : 1,
            "EntryType" => @$Input['EntryType'],
            "UserJoinLimit" => (@$Input['EntryType'] == 'Multiple') ? @$Input['UserJoinLimit'] : 1,
            "CashBonusContribution" => @$Input['CashBonusContribution'],
            "RosterSize" => $RoosterArray['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0,
            // "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : NULL,
            "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : array($defaultCustomizeWinningObj),
        ));
        if ($Input['WeekStart'] == 0) {
            $UpdateData = array_merge($UpdateData, array(
                'WeekStart' => $Input['WeekStart'],
                'WeekEnd'   => $Input['WeekStart']
            ));
        }
        $this->db->where('PreContestID', $PreContestID);
        $this->db->limit(1);
        $this->db->update('sports_pre_contest', $UpdateData);
    }


    /*
      Description: Delete contest to system.
     */

    function deleteContest($SessionUserID, $PreContestID) {
        $this->db->where('PreContestID', $PreContestID);
        $this->db->limit(1);
        $this->db->delete('sports_pre_contest');
    }

    function statusUpdateContest($Input, $PreContestID) {
        $Status = 2;
        if($Input['Status'] == "Cancelled"){
          $Status = 3;  
        }
        $this->db->where('PreContestID', $PreContestID);
        $this->db->limit(1);
        $this->db->update('sports_pre_contest',array('StatusID'=>$Status));
    }

    /*
      Description: Delete contest to system.
     */

    function deleteUpcomingPreContest($SessionUserID, $PreContestID) {
        $Contests = $this->dbr->query("SELECT
                                        C.ContestID
                                    FROM
                                        sports_contest C,tbl_entity E
                                    WHERE E.EntityID=C.ContestID AND NOT EXISTS
                                        (
                                        SELECT
                                            1
                                        FROM
                                            sports_contest_join
                                        WHERE
                                            sports_contest_join.ContestID = C.ContestID
                                    ) AND C.PreContestID = '" . $PreContestID . "' AND E.StatusID=1")->result_array();
        if (!empty($Contests)) {
            foreach ($Contests as $Rows) {
                $this->db->trans_start();

                /** delete entity rows * */
                $this->db->where('EntityID', $Rows['ContestID']);
                $this->db->limit(1);
                $this->db->delete('tbl_entity');

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
            }
        }
        $this->db->where('PreContestID', $PreContestID);
        $this->db->limit(1);
        $this->db->delete('sports_pre_contest');
    }

    /*
      Description: To get contest
     */


        /*
      Description: To get contest
     */
    function getPreContest($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PreContestID' => 'C.PreContestID',
                'StatusID' => 'C.StatusID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'GameType' => 'C.GameType',
                'ContestDuration' => 'C.ContestDuration',
                'DailyDate' => 'C.DailyDate',
                'AuctionUpdateTime' => 'C.AuctionUpdateTime',
                'AuctionBreakDateTime' => 'C.AuctionBreakDateTime',
                'AuctionTimeBreakAvailable' => 'C.AuctionTimeBreakAvailable',
                'AuctionIsBreakTimeStatus' => 'C.AuctionIsBreakTimeStatus',
                'LeagueType' => 'C.LeagueType',
                'LeagueJoinDateTime' => 'CONVERT_TZ(C.LeagueJoinDateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS LeagueJoinDateTime',
                'LeagueJoinDateTimeUTC' => 'C.LeagueJoinDateTime as LeagueJoinDateTimeUTC',
                'GameTimeLive' => 'C.GameTimeLive',
                'AdminPercent' => 'C.AdminPercent',
                'IsConfirm' => 'C.IsConfirm',
                "GamePlayType" => 'C.GamePlayType',
                'IsAutoCreate' => 'C.IsAutoCreate',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'WinningAmount' => 'C.WinningAmount',
                'RosterSize' => 'C.RosterSize',
                'PlayedRoster' => 'C.PlayedRoster',
                'BatchRoster' => 'C.BatchRoster',
                'ContestSize' => 'C.ContestSize',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'ScoringType' => 'C.ScoringType',
                'PlayOff' => 'C.PlayOff',
                'WeekStart' => 'C.WeekStart',
                'WeekEnd' => 'C.WeekEnd',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'EntryType' => 'C.EntryType',
                'UserJoinLimit' => 'C.UserJoinLimit',
                'MinimumUserJoined' => 'C.MinimumUserJoined',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'EntryType' => 'C.EntryType',
                'IsWinningDistributed' => 'C.IsWinningDistributed',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'StatusID' => 'C.StatusID',
                'AuctionStatusID' => 'C.AuctionStatusID',
                'SubGameTypeKey' => 'C.SubGameType SubGameTypeKey',
                'AuctionStatus' => 'CASE C.AuctionStatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "3" then "Cancelled"
                             when "5" then "Completed"
                             END as AuctionStatus',
                'Status' => 'CASE C.StatusID
                             when "1" then "Pending"
                             when "2" then "Active"
                             when "3" then "Inactive"
                             when "5" then "Completed"
                             END as Status',
                'SubGameType' => 'CASE C.SubGameType
                                when "ProFootballPreSeasonOwners" then "Pro (Pre Season)"
                                when "ProFootballRegularSeasonOwners" then "Pro (Regular Season)"
                                when "ProFootballPlayoffs" then "Pro (Playoffs)"
                                when "CollegeFootballRegularSeason" then "College (Regular Season)"
                                when "CollegeFootballPower5RegularSeason" then "Power 5 (Regular Season)"
                            END as SubGameType'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('C.PreContestID,C.ContestName,C.GamePlayType,C.MatchID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_pre_contest C');
        if (!empty($Where['Keyword'])) {
            if (is_array(json_decode($Where['Keyword'], true))) {
                $Where['Keyword'] = json_decode($Where['Keyword'], true);
                if (isset($Where['Keyword']['ContestName'])) {
                    $this->db->like("C.ContestName", @$Where['Keyword']['ContestName']);
                }
                if (isset($Where['Keyword']['GameType'])) {
                    $this->db->where("C.GameType", @$Where['Keyword']['GameType']);
                }
                if (isset($Where['Keyword']['ScoringType'])) {
                    $this->db->where("C.ScoringType", @$Where['Keyword']['ScoringType']);
                }
                if (isset($Where['Keyword']['PlayOff'])) {
                    $this->db->where("C.PlayOff", @$Where['Keyword']['PlayOff']);
                }
                if (isset($Where['Keyword']['ContestSize'])) {
                    $ContestSize = explode("-", $Where['Keyword']['ContestSize']);
                    if (count($ContestSize) > 1) {
                        $this->db->where("C.ContestSize >=", @$ContestSize[0]);
                        $this->db->where("C.ContestSize <=", @$ContestSize[1]);
                    } else {
                        $this->db->where("C.ContestSize >=", @$ContestSize[0]);
                    }
                }
                if (isset($Where['Keyword']['EntryFee'])) {
                    $EntryFee = explode("-", $Where['Keyword']['EntryFee']);
                    if (count($EntryFee) > 1) {
                        $this->db->where("C.EntryFee >=", $EntryFee[0]);
                        $this->db->where("C.EntryFee <=", $EntryFee[1]);
                    } else {
                        $this->db->where("C.EntryFee >=", $EntryFee[0]);
                    }
                }
            } else {
                $this->db->group_start();
                $this->db->like("C.ContestName", $Where['Keyword']);
                $this->db->or_like("C.GameType", $Where['Keyword']);
                $this->db->or_like("C.WinningAmount", $Where['Keyword']);
                $this->db->or_like("C.ContestSize", $Where['Keyword']);
                $this->db->or_like("C.EntryFee", $Where['Keyword']);
                // $this->db->or_like("M.MatchLocation", $Where['Keyword']);
                // $this->db->or_like("M.MatchNo", $Where['Keyword']);
                $this->db->group_end();
            }
        }
        if (!empty($Where['PreContestID'])) {
            $this->db->where("C.PreContestID", $Where['PreContestID']);
        }
        if (!empty($Where['AuctionStatusID'])) {
            $this->db->where("C.AuctionStatusID", $Where['AuctionStatusID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("C.UserID", $Where['UserID']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            // $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }

        if (!empty($Where['Filter']) && $Where['Filter'] == 'LiveAuction') {
            $CurrentDatetime = strtotime(date('Y-m-d H:i:s')) + 3600;
            $NextTime = date("Y-m-d H:i:s");
            $CurrentDatetime = strtotime(date('Y-m-d H:i:s')) - 3600;
            $PreTime = date("Y-m-d H:i:s", $CurrentDatetime);
            $this->db->where("C.LeagueJoinDateTime <=", $NextTime);
            //$this->db->where("C.LeagueJoinDateTime >=", $PreTime);
        }
        if (!empty($Where['Privacy']) && $Where['Privacy'] != 'All') {
            $this->db->where("C.Privacy", $Where['Privacy']);
        }
        if (!empty($Where['GamePlayType'])) {
            $this->db->where("C.GamePlayType", $Where['GamePlayType']);
        }
        if (!empty($Where['ContestType'])) {
            $this->db->where("C.ContestType", $Where['ContestType']);
        }
        if (!empty($Where['ContestFormat'])) {
            $this->db->where("C.ContestFormat", $Where['ContestFormat']);
        }
        if (!empty($Where['IsPaid'])) {
            $this->db->where("C.IsPaid", $Where['IsPaid']);
        }
        if (!empty($Where['WinningAmount'])) {
            $this->db->where("C.WinningAmount >=", $Where['WinningAmount']);
        }
        if (!empty($Where['ContestSize'])) {
            $this->db->where("C.ContestSize", $Where['ContestSize']);
        }
        if (!empty($Where['AutionInLive']) && $Where['AutionInLive'] == "Yes") {
            $this->db->where("C.LeagueJoinDateTime <=", date('Y-m-d H:i:s'));
            $this->db->where("C.AuctionUpdateTime <=", date('Y-m-d H:i:s'));
        }
        if (!empty($Where['EntryFee'])) {
            $this->db->where("C.EntryFee", $Where['EntryFee']);
        }
        if (!empty($Where['NoOfWinners'])) {
            $this->db->where("C.NoOfWinners", $Where['NoOfWinners']);
        }
        if (!empty($Where['EntryType'])) {
            $this->db->where("C.EntryType", $Where['EntryType']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("C.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where_in("C.StatusID", $Where['StatusID']);
        }
        if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] = 1) {
            $this->db->where('EXISTS (select ContestID from sports_contest_join JE where JE.ContestID = C.PreContestID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
        }
        if (!empty($Where['UserInvitationCode'])) {
            $this->db->where("C.UserInvitationCode", $Where['UserInvitationCode']);
        }
        if (!empty($Where['IsWinningDistributed'])) {
            $this->db->where("C.IsWinningDistributed", $Where['IsWinningDistributed']);
        }
        if (!empty($Where['ContestFull']) && $Where['ContestFull'] == 'No') {
            $this->db->having("TotalJoined !=", 'C.ContestSize', FALSE);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            if (!empty($Where['OrderByToday']) && $Where['OrderByToday'] == 'Yes') {
                $this->db->order_by('C.StatusID=2 DESC', null, FALSE);
            } else {
                $this->db->order_by('C.PreContestID', 'DESC');
            }
        }


        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        //$this->db->group_by('C.ContestID'); // Will manage later
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                $defaultCustomizeWinningObj = new stdClass();
                $defaultCustomizeWinningObj->From = 1;
                $defaultCustomizeWinningObj->To = 1;
                $defaultCustomizeWinningObj->Percent = 100;
                $defaultCustomizeWinningObj->WinningAmount = $Record['WinningAmount'];
                foreach ($Query->result_array() as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array($defaultCustomizeWinningObj);
                    //$Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                    $TotalAmountReceived =  0;
                    $TotalWinningAmount =  0;
                    $Records[$key]['NoOfWinners'] = ($Record['NoOfWinners'] == 0 ) ? 1 : $Record['NoOfWinners'];
                }

                $Return['Data']['Records'] = $Records;
            } else {
                $Record = $Query->row_array();
                $Record['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array();
                //$Record['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                $TotalAmountReceived = 0;
                $TotalWinningAmount =  0;

                return $Record;
            }
        }
        $Return['Data']['Records'] = empty($Records) ? array() : $Records;
        return $Return;
    }

    /*
      Description: Create Pre contest
     */

    function createPreContest($InsertID = '') {
        if (!empty($InsertID)) {
            $ContestData = $this->dbr->query('SELECT * FROM sports_pre_contest WHERE PreContestID = ' . $InsertID);
        } else {
            $ContestData = $this->dbr->query('SELECT * FROM sports_pre_contest');
        }

        if ($ContestData->num_rows() > 0) {

            /* Get matches of next 4 days */
            $DateTime = date('Y-m-d H:i', strtotime('+7 days', strtotime(date('Y-m-d H:i'))));
			$DateTimeComplete = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')));
			$MatchesData = $this->Sports_model->getMatches('SeriesID,MatchID,MatchTypeByApi', array('OrderBy' => 'MatchStartDateTime', 'Sequence' => 'ASC', 'StatusID' => 1, 'MatchStartDateTime' => $DateTime,'MatchStartDateTimeComplete' => $DateTimeComplete), TRUE, 1, 50);
            if ($MatchesData['Data']['TotalRecords'] == 0) {
                return FALSE;
            }
			
            foreach ($ContestData->result_array() as $Res) {

                if($Res['StatusID'] == 6) continue;

                $SeriesID = explode(",", $Res['SeriesIDs']);
                $FieldArray = array(
                    'ContestFormat' => $Res['ContestFormat'],
                    'ContestType' => $Res['ContestType'],
                    'ContestName' => $Res['ContestName'],
                    'Privacy' => $Res['Privacy'],
                    'IsPaid' => $Res['IsPaid'],
                    'AdminPercent' => $Res['AdminPercent'],
                    'IsConfirm' => $Res['IsConfirm'],
                    'UnfilledWinningPercent' => $Res['UnfilledWinningPercent'],
                    'WinningRatio' => $Res['WinningRatio'],
                    'WinUpTo' => $Res['WinUpTo'],
                    'SmartPool' => $Res['SmartPool'],
                    'IsAutoCreate' => $Res['IsAutoCreate'],
                    'PreContestID' => $Res['PreContestID'],
                    'ShowJoinedContest' => $Res['ShowJoinedContest'],
                    'WinningAmount' => $Res['WinningAmount'],
                    'ContestSize' => $Res['ContestSize'],
                    'unfilledWinningPercent' => $Res['unfilledWinningPercent'],
                    'CashBonusContribution' => $Res['CashBonusContribution'],
                    'UserJoinLimit' => $Res['UserJoinLimit'],
                    'EntryType' => $Res['EntryType'],
                    'EntryFee' => $Res['EntryFee'],
                    'NoOfWinners' => $Res['NoOfWinners'],
                    'CustomizeWinning' => $Res['CustomizeWinning'],
                    'IsWinnerSocialFeed' => $Res['IsWinnerSocialFeed'],
                    'IsWinningDistributed' => $Res['IsWinningDistributed'],
                    'IsVirtualUserJoined' => $Res['IsVirtualUserJoined'],
                    'VirtualUserJoinedPercentage' => $Res['VirtualUserJoinedPercentage'],
                    'WinningType' => "Paid Join Contest"
                );
                foreach ($MatchesData['Data']['Records'] as $Record) {
                    if($Record['MatchTypeByApi'] == "Real"){
                        if($Res['AllSeries'] == "No"){
                            if(!in_array($Record['SeriesID'],$SeriesID)){
                               continue;
                            }
                        }						
                        $GetContest = $this->dbr->query('SELECT * FROM sports_contest C, tbl_entity E WHERE E.EntityID=C.ContestID 
                            AND C.PreContestID = ' . $Res['PreContestID'] . ' AND C.MatchID = ' . $Record['MatchID'] . ' AND E.StatusID=1');
							
                        if ($GetContest->num_rows() == 0) {
							
                            $this->Contest_model->addContest($FieldArray, '125', $Record['MatchID'], $Record['SeriesID']);
                        }						
                    }
                }
            }
        }
    }
	
	   /*
      Description: Create Pre contest match wise
     */

	  function createMatchPreContest($MatchID = '',$InsertID= '') {
        if (!empty($InsertID)) {
            $ContestData = $this->dbr->query('SELECT * FROM sports_pre_contest WHERE PreContestID = ' . $InsertID);
        } else {
            $ContestData = $this->dbr->query('SELECT * FROM sports_pre_contest');
        }

        if ($ContestData->num_rows() > 0) {

            /* Get matches from MatchID*/     
			$MatchesData = $this->Sports_model->getMatches('SeriesID,MatchID,MatchTypeByApi', array('OrderBy' => 'MatchStartDateTime', 'Sequence' => 'ASC', 'StatusID' => 1, 'MatchID' => $MatchID ), False, 1, 1);
            if ($MatchesData['Data']['TotalRecords'] == 0) {
                return FALSE;
            }
			
            foreach ($ContestData->result_array() as $Res) {

                if($Res['StatusID'] == 6) continue;

                $SeriesID = explode(",", $Res['SeriesIDs']);
                $FieldArray = array(
                    'ContestFormat' => $Res['ContestFormat'],
                    'ContestType' => $Res['ContestType'],
                    'ContestName' => $Res['ContestName'],
                    'Privacy' => $Res['Privacy'],
                    'IsPaid' => $Res['IsPaid'],
                    'AdminPercent' => $Res['AdminPercent'],
                    'IsConfirm' => $Res['IsConfirm'],
                    'UnfilledWinningPercent' => $Res['UnfilledWinningPercent'],
                    'WinningRatio' => $Res['WinningRatio'],
                    'WinUpTo' => $Res['WinUpTo'],
                    'SmartPool' => $Res['SmartPool'],
                    'IsAutoCreate' => $Res['IsAutoCreate'],
                    'PreContestID' => $Res['PreContestID'],
                    'ShowJoinedContest' => $Res['ShowJoinedContest'],
                    'WinningAmount' => $Res['WinningAmount'],
                    'ContestSize' => $Res['ContestSize'],
                    'unfilledWinningPercent' => $Res['unfilledWinningPercent'],
                    'CashBonusContribution' => $Res['CashBonusContribution'],
                    'UserJoinLimit' => $Res['UserJoinLimit'],
                    'EntryType' => $Res['EntryType'],
                    'EntryFee' => $Res['EntryFee'],
                    'NoOfWinners' => $Res['NoOfWinners'],
                    'CustomizeWinning' => $Res['CustomizeWinning'],
                    'IsWinnerSocialFeed' => $Res['IsWinnerSocialFeed'],
                    'IsWinningDistributed' => $Res['IsWinningDistributed'],
                    'IsVirtualUserJoined' => $Res['IsVirtualUserJoined'],
                    'VirtualUserJoinedPercentage' => $Res['VirtualUserJoinedPercentage'],
                    'WinningType' => "Paid Join Contest"
                );
                foreach ($MatchesData['Data']['Records'] as $Record) {
                    if($Record['MatchTypeByApi'] == "Real"){
                        if($Res['AllSeries'] == "No"){
                            if(!in_array($Record['SeriesID'],$SeriesID)){
                               continue;
                            }
                        }						
                        $GetContest = $this->dbr->query('SELECT * FROM sports_contest C, tbl_entity E WHERE E.EntityID=C.ContestID 
                            AND C.PreContestID = ' . $Res['PreContestID'] . ' AND C.MatchID = ' . $Record['MatchID'] . ' AND E.StatusID=1');
							
                        if ($GetContest->num_rows() == 0) {
							
                            $this->Contest_model->addContest($FieldArray, '125', $Record['MatchID'], $Record['SeriesID']);
                        }						
                    }
                }
            }
        }
    }

}

?>