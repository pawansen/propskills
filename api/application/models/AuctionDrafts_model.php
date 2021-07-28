<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class AuctionDrafts_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
        $this->load->model('Settings_model');
    }

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
        $EntityGUID = get_guid();

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" => $SessionUserID, "StatusID" => $StatusID, "GameSportsType" => $Input['GameType']));
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
            "ContestID" => $EntityID,
            "ContestGUID" => $EntityGUID,
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
        $this->db->insert('sports_contest', $InsertData);
        /*$PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $Input['WeekStart'],$Input['ContestDuration'],$Input['DailyDate']);
        if(!$PlayerIs) return false;*/
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    /*
      Description:    ADD auction players
     */

    function addAuctionTeam($SeriesID, $ContestID, $GameType, $SubGameType) {
        $TeamsData = $this->getTeamsDrafts($GameType, $SubGameType);
        if ($TeamsData['TotalRecords'] > 0) {
            $Teams = $TeamsData['Records'];
            if (!empty($Teams)) {
                $InsertBatch = array();
                foreach ($Teams as $Team) {
                    $Temp['SeriesID'] = $SeriesID;
                    $Temp['ContestID'] = $ContestID;
                    $Temp['TeamID'] = $Team['TeamID'];
                    $Temp['BidCredit'] = 0;
                    $Temp['PlayerStatus'] = "Upcoming";
                    $InsertBatch[] = $Temp;
                }
                if (!empty($InsertBatch)) {
                    $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);
                }
            }
        }
    }

    function getTeamsDrafts($GameType, $SubGameType) {
        $Return['Records'] = array();
        $Return['TotalRecords'] = 0;
        $this->db->select("T.TeamID,T.TeamName,T.TeamNameShort");
        $this->db->from('sports_teams T,tbl_entity E');
        $this->db->where("T.TeamID", "E.EntityID", FALSE);
        $this->db->where("E.GameSportsType", ucfirst($GameType));
        if ($SubGameType == "CollegeFootballPower5RegularSeason") {
            $this->db->where("T.IsPowerTeam", "Yes");
        }
        if ($SubGameType == "CollegeFootballRegularSeason") {
            $this->db->where("T.IsCollegePlaying", "Yes");
        }
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['TotalRecords'] = $TempQ->num_rows();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Records'] = $Query->result_array();
        }
        return $Return;
    }

        /*
      Description:    ADD auction players
     */

    function addAuctionPlayer($SeriesID, $ContestID, $WeekID, $ContestDuration, $DailyDate) {

        if($ContestDuration == "Weekly"){
          $Query = $this->db->query("SELECT TeamIDLocal,TeamIDVisitor FROM sports_matches WHERE WeekID = '" . $WeekID . "' AND SeriesID = '" . $SeriesID . "'");
        }else{
          $Query = $this->db->query("SELECT TeamIDLocal,TeamIDVisitor FROM sports_matches WHERE SeriesID = '" . $SeriesID . "' AND DATE(MatchStartDateTime) = '" .$DailyDate . "'");
        }

        /**last this line comment open**/
        /*$Query = $this->db->query("SELECT TeamIDLocal,TeamIDVisitor FROM sports_matches WHERE WeekID = '" . $WeekID . "'");*/

        //$Query = $this->db->query('SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType="Nfl"');
        //$Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;

        $TeamData = ($Query->num_rows() > 0) ? $Query->result_array() : false;
        $TeamIDLocal = array_column($TeamData,'TeamIDLocal');
        $TeamIDVisitor = array_column($TeamData,'TeamIDVisitor');
        $AllTeam = array_unique(array_merge($TeamIDLocal,$TeamIDVisitor));
        //$AllTeam = array_column($Teams,'TeamID');
        if(empty($AllTeam)) return false;

        /*$AllRole = array('QuarterBack','RunningBack','WideReceiver',
            'TightEnd','LineBacker','CornerBack','OutsideLinebacker',
            'DefenseEnd','DefenseTackle','Safety');*/

        $AllRole = array('QuarterBack','RunningBack','WideReceiver',
            'TightEnd');

        /* Get Joined Contest Users */
        $this->db->select('P.PlayerID,P.PlayerName,P.PlayerRole,P.TeamID');
        $this->db->from('sports_players P,tbl_entity E');
        $this->db->where("E.EntityID", "P.PlayerID", FALSE);
        $this->db->where("P.IsPlayRoster", "Yes");
        $this->db->where("E.GameSportsType", "Nfl");
        $this->db->where_in("P.TeamID", $AllTeam);
        $this->db->where_in("P.PlayerRole", $AllRole);
        $this->db->order_by("P.PlayerSalary", "DESC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
                $playersData = $Query->result_array();
                if (!empty($playersData)) {
                    $InsertBatch = array();
                    $InsertBatchPlayer = array();
                    foreach ($playersData as $Player) {
                        $Temp['SeriesID'] = $SeriesID;
                        $Temp['ContestID'] = $ContestID;
                        $Temp['PlayerID'] = $Player['PlayerID'];
                        $Temp['TeamID'] = $Player['TeamID'];
                        $Temp['PlayerRole'] = $Player['PlayerRole'];
                        $Temp['BidCredit'] = 0;
                        $Temp['PlayerStatus'] = "Upcoming";
                        $InsertBatch[] = $Temp;

                        $Temp1['SeriesID'] = $SeriesID;
                        $Temp1['ContestID'] = $ContestID;
                        $Temp1['PlayerID'] = $Player['PlayerID'];
                        $Temp['TeamID'] = $Player['TeamID'];
                        $Temp1['PlayerRole'] = $Player['PlayerRole'];
                        $InsertBatchPlayer[] = $Temp1;
                    }
                    if (!empty($InsertBatch)) {
                        $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);
                        $Query = $this->db->query('SELECT SeriesID FROM sports_auction_draft_player_point WHERE SeriesID = "' . $SeriesID . '" LIMIT 1');
                        $SeriesID = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
                        if (!$SeriesID) {
                            $this->db->insert_batch('sports_auction_draft_player_point', $InsertBatchPlayer);
                        }
                    }
                }
                return true;
        }else{
            return false;
        }
        return true;
    }

    function getPlayersDraftFootball($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'TeamID' => 'P.TeamID',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerPosition' => 'UTP.PlayerPosition',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('tbl_auction_player_bid_status APBS,sports_series S,sports_contest C');
            $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            if (!empty($Where['SessionUserID'])) {
                $this->db->where("UT.UserID", @$Where['SessionUserID']);
            }
            if (!empty($Where['IsAssistant'])) {
                $this->db->where("UT.IsAssistant", @$Where['IsAssistant']);
            }
            if (!empty($Where['IsPreTeam'])) {
                $this->db->where("UT.IsPreTeam", @$Where['IsPreTeam']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("P.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        $this->db->where('EXISTS (select PlayerID FROM sports_team_players WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ')');
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsInjuries'])) {
            $this->db->where("P.IsInjuries", $Where['IsInjuries']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            //$this->db->order_by('P.PlayerSalary', 'DESC');
            //$this->db->order_by('P.PlayerID', 'DESC');
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

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                    $this->db->where('PlayerID', $Record['PlayerID']);
                    $this->db->from('sports_team_players');
                    $this->db->order_by("PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        $Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                    $Return['Data']['AuctionTopPlayerSubmitted'] = $AuctionTopPlayerSubmitted;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /* function addAuctionPlayer($SeriesID, $ContestID) {
      $playersData = $this->getPlayers("PlayerID", array('SeriesID' => $SeriesID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
      if ($playersData['Data']['TotalRecords'] > 0) {
      $Players = $playersData['Data']['Records'];
      if (!empty($Players)) {
      $InsertBatch = array();
      foreach ($Players as $Player) {
      $Temp['SeriesID'] = $SeriesID;
      $Temp['ContestID'] = $ContestID;
      $Temp['PlayerID'] = $Player['PlayerID'];
      $Temp['BidCredit'] = 0;
      $Temp['PlayerStatus'] = "Upcoming";
      $InsertBatch[] = $Temp;
      }
      if (!empty($InsertBatch)) {
      $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);
      }
      }
      }
      } */

    function getTeams($SeriesID) {
        $Return['Records'] = array();
        $Return['TotalRecords'] = 0;
        $this->db->select("T.TeamID,T.TeamName,T.TeamNameShort");
        $this->db->from('sports_matches M, sports_teams T');
        $this->db->where("T.TeamID", "M.TeamIDLocal", FALSE);
        //$this->db->where("T.TeamID", "M.TeamIDVisitor", FALSE);
        $this->db->where("M.SeriesID", $SeriesID);
        $this->db->group_by("T.TeamID");
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['TotalRecords'] = $TempQ->num_rows();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Records'] = $Query->result_array();
        }
        return $Return;
    }

    /*
      Description: Update contest to system.
     */

    function updateContest($Input = array(), $SessionUserID, $ContestID, $StatusID = 1) {
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
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
    }

    /*
      Description: Update auction game.
     */

    function getAuctionGameStatusUpdate($Input = array(), $ContestID, $AuctionStatusID) {


        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionStatusID" => $AuctionStatusID,
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
        $Rows = $this->db->affected_rows();
    }

    /*
      Description: Update auction player status.
     */

    function auctionPlayerStausUpdate($Input = array(), $SeriesID, $ContestID, $PlayerID) {
        $Return = array();
        $diffSeconds = 0;
        $AuctionOnBreak = 1;

        /** check auction completed * */
        $this->db->select('(CASE AuctionStatusID
                                                    when "1" then "Pending"
                                                    when "2" then "Running"
                                                    when "5" then "Completed"
                                                    END ) as AuctionStatus');
        $this->db->from("sports_contest");
        $this->db->where('ContestID', $ContestID);
        $this->db->where('AuctionStatusID', 5);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $ContestStatus = $Query->row_array();
            $Return['BreakTimeInSec'] = 0;
            $Return['AuctionTimeBreakAvailable'] = "";
            $Return['IsBreakTimeStatus'] = "";
            $Return['AuctionStatus'] = $ContestStatus['AuctionStatus'];
            $Return['Status'] = 1;
            return $Return;
        }

        /** unsold player management * */
        if ($Input['PlayerStatus'] == "Unsold") {
            /** To check player in assistant * */
            $BidUserID = "";
            $BidUserCredit = "";
            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID");
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            $this->db->where("UT.IsAssistant", "Yes");
            $this->db->where("UT.IsPreTeam", "Yes");
            $this->db->where("UT.ContestID", $ContestID);
            $this->db->where("UT.SeriesID", $SeriesID);
            $this->db->where("UTP.PlayerID", $PlayerID);
            $Query = $this->db->get();
            $PlayersAssistant = $Query->result_array();
            $Rows = $Query->num_rows();
            if ($Rows > 0) {

                /** To check assistant player single * */
                if ($Rows == 1) {
                    $CurrentBidCredit = 100000;
                    $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
                    $BidUserID = $PlayersAssistant[0]['UserID'];
                    $BidUserCredit = $CurrentBidCredit;

                    /** to check user available budget * */
                    $this->db->select("AuctionBudget");
                    $this->db->from('sports_contest_join');
                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                    $this->db->where("ContestID", $ContestID);
                    $this->db->where("SeriesID", $SeriesID);
                    $this->db->where("UserID", $PlayersAssistant[0]['UserID']);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        /* add player bid */
                        $InsertData = array(
                            "SeriesID" => $SeriesID,
                            "ContestID" => $ContestID,
                            "UserID" => $PlayersAssistant[0]['UserID'],
                            "PlayerID" => $PlayerID,
                            "BidCredit" => $CurrentBidCredit,
                            "DateTime" => date('Y-m-d H:i:s')
                        );

                        $this->db->insert('tbl_auction_player_bid', $InsertData);
                    } else {
                        $Return['BreakTimeInSec'] = 0;
                        $Return['AuctionTimeBreakAvailable'] = "";
                        $Return['IsBreakTimeStatus'] = "";
                        $Return['Status'] = 0;
                        return $Return;
                    }
                } else if ($Rows > 1) {
                    /** get second highest user* */
                    $SecondUser = $this->get_max($PlayersAssistant, 1);
                    $CurrentBidCredit = $AssistantBidCredit = $SecondUser['BidCredit'];
                    if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 100000;
                    } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 1000000;
                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 10000000;
                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 100000000;
                    }
                    /** get top user* */
                    $TopUser = $this->get_max($PlayersAssistant, 0);
                    $TopUserBidCredit = $TopUser['BidCredit'];
                    if ($CurrentBidCredit > $TopUserBidCredit) {
                        $CurrentBidCredit = $TopUserBidCredit;
                    }
                    $BidUserID = $TopUser['UserID'];
                    $BidUserCredit = $CurrentBidCredit;

                    /** to check user available budget * */
                    $this->db->select("AuctionBudget");
                    $this->db->from('sports_contest_join');
                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                    $this->db->where("ContestID", $ContestID);
                    $this->db->where("SeriesID", $SeriesID);
                    $this->db->where("UserID", $TopUser['UserID']);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        /* add player bid */
                        $InsertData = array(
                            "SeriesID" => $SeriesID,
                            "ContestID" => $ContestID,
                            "UserID" => $TopUser['UserID'],
                            "PlayerID" => $PlayerID,
                            "BidCredit" => $CurrentBidCredit,
                            "DateTime" => date('Y-m-d H:i:s')
                        );

                        $this->db->insert('tbl_auction_player_bid', $InsertData);
                    } else {
                        $Return['BreakTimeInSec'] = 0;
                        $Return['AuctionTimeBreakAvailable'] = "";
                        $Return['IsBreakTimeStatus'] = "";
                        $Return['Status'] = 0;
                        return $Return;
                    }
                }
            } else {
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Unsold",
                    "DateTime" => date('Y-m-d H:i:s'),
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $PlayerID);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            }
            $Return['BreakTimeInSec'] = 0;
            $Return['AuctionTimeBreakAvailable'] = "";
            $Return['IsBreakTimeStatus'] = "";
            $Return['Status'] = 1;
            return $Return;
        }

        if ($Input['PlayerStatus'] == "Sold") {
            /** check player in live for sold * */
            $this->db->select("PlayerID");
            $this->db->from('tbl_auction_player_bid_status');
            $this->db->where("ContestID", $ContestID);
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where("PlayerStatus", "Live");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() <= 0) {
                $Return['BreakTimeInSec'] = 0;
                $Return['AuctionTimeBreakAvailable'] = "";
                $Return['IsBreakTimeStatus'] = "";
                $Return['Status'] = 0;
                return $Return;
            }
        }

        /** live player management * */
        if ($Input['PlayerStatus'] == "Live") {

            /** check player in upcoming * */
            $this->db->select("PlayerID");
            $this->db->from('tbl_auction_player_bid_status');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("PlayerStatus", "Upcoming");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() <= 0) {
                $Return['BreakTimeInSec'] = 0;
                $Return['AuctionTimeBreakAvailable'] = "";
                $Return['IsBreakTimeStatus'] = "";
                $Return['Status'] = 0;
                return $Return;
            }


            /** check another player in live * */
            $this->db->select("PlayerID");
            $this->db->from('tbl_auction_player_bid_status');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("PlayerStatus", "Live");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Return['BreakTimeInSec'] = 0;
                $Return['AuctionTimeBreakAvailable'] = "";
                $Return['IsBreakTimeStatus'] = "";
                $Return['Status'] = 0;
                return $Return;
            }

            $this->db->select("AuctionIsBreakTimeStatus,AuctionBreakDateTime,AuctionTimeBreakAvailable");
            $this->db->from('sports_contest');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("AuctionIsBreakTimeStatus", "No");
            $this->db->where("AuctionTimeBreakAvailable", "No");
            $this->db->limit(1);
            $AuctionOnBreak = $this->db->get()->row_array();
        }
        if (!empty($AuctionOnBreak)) {
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => $Input['PlayerStatus'],
                "BidCredit" => @$Input['BidCredit'],
                "DateTime" => date('Y-m-d H:i:s'),
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $PlayerID);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            if ($Input['PlayerStatus'] == "Sold") {
                $this->db->select("UserID,PlayerID,BidCredit");
                $this->db->from("tbl_auction_player_bid");
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $PlayerID);
                $this->db->order_by("DateTime", "DESC");
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Response = $Query->row_array();
                    $this->addUserTeamPlayerAfterSold($Response['UserID'], $SeriesID, $ContestID, $PlayerID, $Response['BidCredit']);
                }
            }
            $AuctionBreakDateTime = $AuctionOnBreak['AuctionBreakDateTime'];
            $CurrentDateTime = date('Y-m-d H:i:s');
            $CurrentDateTime = new DateTime($CurrentDateTime);
            $AuctionBreakDateTime = new DateTime($AuctionBreakDateTime);
            $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
            $Return['BreakTimeInSec'] = $diffSeconds;
            $Return['AuctionTimeBreakAvailable'] = $AuctionOnBreak['AuctionTimeBreakAvailable'];
            $Return['IsBreakTimeStatus'] = $AuctionOnBreak['AuctionIsBreakTimeStatus'];
        } else {
            $Return['BreakTimeInSec'] = 0;
            $Return['AuctionTimeBreakAvailable'] = "";
            $Return['IsBreakTimeStatus'] = "";
            $Return['Status'] = 1;
        }

        if ($Input['PlayerStatus'] == "Unsold" || $Input['PlayerStatus'] == "Sold") {
            /** check upcoming player * */
            $this->db->select("PlayerID,BidCredit,PlayerStatus");
            $this->db->from("tbl_auction_player_bid_status");
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where_in('PlayerStatus', array("Upcoming", "Live"));
            //$this->db->order_by("DateTime", "DESC");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() <= 0) {
                /** auction completed * */
                $UpdateData = array(
                    "AuctionStatusID" => 5
                );
                $this->db->where('ContestID', $ContestID);
                $this->db->limit(1);
                $this->db->update('sports_contest', $UpdateData);
            }
        }

        /** get auction status * */
        $this->db->select('(CASE AuctionStatusID
                                                    when "1" then "Pending"
                                                    when "2" then "Running"
                                                    when "5" then "Completed"
                                                    END ) as AuctionStatus');
        $this->db->from("sports_contest");
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $ContestStatus = $Query->row_array();
            $Return['AuctionStatus'] = $ContestStatus['AuctionStatus'];
            $Return['Status'] = 1;
            return $Return;
        }

        return $Return;
    }

    function addUserTeamPlayerAfterSold($UserID, $SeriesID, $ContestID, $PlayerID, $BidCredit) {

        /** update player bid credit * */
        $UpdateData = array(
            "BidCredit" => $BidCredit,
        );
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('PlayerID', $PlayerID);
        $this->db->limit(1);
        $this->db->update('tbl_auction_player_bid_status', $UpdateData);


        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */

        $UserBudget = $this->getJoinedContestsUsers("ContestID,UserID,AuctionBudget", array('ContestID' => $ContestID, 'SeriesID' => $SeriesID, 'UserID' => $UserID), FALSE);
        if (!empty($UserBudget)) {
            $this->db->trans_start();

            $UserContestBudget = $UserBudget['AuctionBudget'];
            $UserContestBudget = $UserContestBudget - $BidCredit;
            /* update contest user budget. */
            $UpdateData = array(
                "AuctionBudget" => $UserContestBudget,
            );
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('UserID', $UserID);
            $this->db->limit(1);
            $this->db->update('sports_contest_join', $UpdateData);

            $UserTeamID = $this->db->query('SELECT T.UserTeamID from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.UserID = "' . $UserID . '" AND T.ContestID = "' . $ContestID . '" AND IsPreTeam = "No" AND IsAssistant="No" ')->row()->UserTeamID;
            if (empty($UserTeamID)) {
                $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $UserID, "StatusID" => 2));
                /* Add user team to user team table . */
                $teamName = "PostAuctionTeam 1";
                $InsertData = array(
                    "UserTeamID" => $EntityID,
                    "UserTeamGUID" => $EntityGUID,
                    "UserID" => $UserID,
                    "UserTeamName" => $teamName,
                    "UserTeamType" => "Auction",
                    "IsPreTeam" => "No",
                    "SeriesID" => $SeriesID,
                    "ContestID" => $ContestID,
                    "IsAssistant" => "No",
                );
                $this->db->insert('sports_users_teams', $InsertData);
                /* Add User Team Players */
                if (!empty($PlayerID)) {

                    /* Manage User Team Players */
                    $UserTeamPlayers = array(
                        'UserTeamID' => $EntityID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayerID,
                        'PlayerPosition' => "Player",
                        'BidCredit' => $BidCredit
                    );
                    $this->db->insert('sports_users_team_players', $UserTeamPlayers);
                }
            } else {
                /* Add User Team Players */
                if (!empty($PlayerID)) {
                    /* Manage User Team Players */
                    $UserTeamPlayers = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayerID,
                        'PlayerPosition' => "Player",
                        'BidCredit' => $BidCredit
                    );
                    $this->db->insert('sports_users_team_players', $UserTeamPlayers);
                }
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
        } else {
            return false;
        }
        return $EntityGUID;
    }

    /*
      Description: Delete contest to system.
     */

    function deleteContest($SessionUserID, $ContestID) {
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->delete('sports_contest');
    }

    /*
      Description: To get contest
     */
    function getContests($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'StatusID' => 'E.StatusID',
                'ContestID' => 'C.ContestID',
                'ContestGUID' => 'C.ContestGUID',
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
                'SeriesID' => 'S.SeriesID',
                'SeriesGUID' => 'S.SeriesGUID',
                'SeriesName' => 'S.SeriesName',
                'IsJoined' => '(SELECT IF( EXISTS(
                                SELECT EntryDate FROM sports_contest_join
                                WHERE sports_contest_join.ContestID =  C.ContestID AND UserID = ' . @$Where['SessionUserID'] . ' LIMIT 1), "Yes", "No")) AS IsJoined',
                // 'TotalJoined' => 'C.TotalJoinedTeams AS TotalJoined',
                'TotalJoined' => '(SELECT COUNT(0) FROM sports_contest_join
                                WHERE sports_contest_join.ContestID =  C.ContestID) AS TotalJoined',
                'StatusID' => 'E.StatusID',
                'AuctionStatusID' => 'C.AuctionStatusID',
                'SubGameTypeKey' => 'C.SubGameType SubGameTypeKey',
                'AuctionStatus' => 'CASE C.AuctionStatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "3" then "Cancelled"
                             when "5" then "Completed"
                             END as AuctionStatus',
                'Status' => 'CASE E.StatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "3" then "Cancelled"
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
        $this->db->select('C.ContestGUID,C.ContestName,(SELECT WeekName FROM sports_matches WHERE SeasonType = C.SubGameType AND WeekID=C.WeekStart LIMIT 1) WeekName,C.GamePlayType,C.MatchID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_contest C,sports_series S');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("S.SeriesID", "C.SeriesID", FALSE);
        $this->db->where("C.LeagueType !=", 'Dfs');
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
        if (!empty($Where['ContestID'])) {
            $this->db->where("C.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['ContestGUID'])) {
            $this->db->where("C.ContestGUID", $Where['ContestGUID']);
        }
        if (!empty($Where['LeagueType'])) {
            $this->db->where("C.LeagueType", $Where['LeagueType']);
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
            $this->db->where_in("E.StatusID", $Where['StatusID']);
        }
        if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] = 1) {
            $this->db->where('EXISTS (select ContestID from sports_contest_join JE where JE.ContestID = C.ContestID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
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
                $this->db->order_by('DATE(S.SeriesEndDate)="' . date('Y-m-d') . '" ASC', null, FALSE);
                $this->db->order_by('E.StatusID=2 DESC', null, FALSE);
            } else {
                $this->db->order_by('C.ContestID', 'DESC');
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
        // echo $this->db->last_query();exit;
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
                    $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                    $Records[$key]['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                    $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                    $Records[$key]['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                    $Records[$key]['NoOfWinners'] = ($Record['NoOfWinners'] == 0 ) ? 1 : $Record['NoOfWinners'];
                    if(!empty($Record['MatchID'])){
                        $this->db->select('M.MatchGUID,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor,M.MatchStartDateTime as MatchStartDateTimeUTC');
                        $this->db->where('MatchID', $Record['MatchID']);
                        $this->db->from('sports_matches M, sports_teams TL, sports_teams TV');
                        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
                        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
                        $this->db->limit(1);
                        $MatchDetails = $this->db->get()->row_array();
                        if (!empty($MatchDetails)) {
                            $Records[$key]['Match'] = $MatchDetails;
                        }
                    }
                }

                $Return['Data']['Records'] = $Records;
            } else {
                $Record = $Query->row_array();
                $Record['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array();
                //$Record['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                $Record['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                $Record['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                
                    if(!empty($Record['MatchID'])){
                        $this->db->select('M.MatchGUID,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor,M.MatchStartDateTime as MatchStartDateTimeUTC');
                        $this->db->where('MatchID', $Record['MatchID']);
                        $this->db->from('sports_matches M, sports_teams TL, sports_teams TV');
                        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
                        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
                        $this->db->limit(1);
                        $MatchDetails = $this->db->get()->row_array();
                        if (!empty($MatchDetails)) {
                            $Record['Match'] = $MatchDetails;
                        }
                    }

                return $Record;
            }
        }
        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C,tbl_entity E WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestID = E.EntityID AND E.StatusID != 3 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();
        }

        $Return['Data']['Records'] = empty($Records) ? array() : $Records;
        return $Return;
    }

    function getTotalContestCollections($ContestGUID) {
        return $this->db->query('SELECT SUM(C.EntryFee) as TotalAmountReceived FROM sports_contest C join sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalAmountReceived;
    }

    function getTotalWinningAmount($ContestGUID) {
        return $this->db->query('SELECT SUM(J.UserWinningAmount) as TotalWinningAmount FROM sports_contest C join sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalWinningAmount;
    }

    /*
      Description: Join contest
     */

    function joinContest($Input = array(), $SessionUserID, $ContestID, $SeriesID, $UserTeamID) {

        $this->db->trans_start();
        /* Add entry to join contest table . */
        $InsertData = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "SeriesID" => $SeriesID,
            "UserTeamID" => $UserTeamID,
            "EntryDate" => date('Y-m-d H:i:s')
        );
        $this->db->insert('sports_contest_join', $InsertData);
        /* Manage User Wallet */
        if (@$Input['IsPaid'] == 'Yes') {
            $ContestEntryRemainingFees = @$Input['EntryFee'];
            $CashBonusContribution = @$Input['CashBonusContribution'];
            $WalletAmountDeduction = 0;
            $WinningAmountDeduction = 0;
            $CashBonusDeduction = 0;
            if (!empty($CashBonusContribution) && @$Input['CashBonus'] > 0) {
                $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                if (@$Input['CashBonus'] >= $CashBonusContributionAmount) {
                    $CashBonusDeduction = $CashBonusContributionAmount;
                } else {
                    $CashBonusDeduction = @$Input['CashBonus'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WinningAmount'] > 0) {
                if (@$Input['WinningAmount'] >= $ContestEntryRemainingFees) {
                    $WinningAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WinningAmountDeduction = @$Input['WinningAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WalletAmount'] > 0) {
                if (@$Input['WalletAmount'] >= $ContestEntryRemainingFees) {
                    $WalletAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WalletAmountDeduction = @$Input['WalletAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
            }
            $InsertData = array(
                "Amount" => @$Input['EntryFee'],
                "WalletAmount" => $WalletAmountDeduction,
                "WinningAmount" => $WinningAmountDeduction,
                "CashBonus" => $CashBonusDeduction,
                "TransactionType" => 'Dr',
                "EntityID" => $ContestID,
                "UserTeamID" => $UserTeamID,
                "Narration" => 'Join Contest',
                "EntryDate" => date("Y-m-d H:i:s")
            );
            $WalletID = $this->Users_model->addToWallet($InsertData, $SessionUserID, 5);
            if (!$WalletID)
                return FALSE;

            $ContestsData = $this->db->query('Select C.ContestSize,C.IsAutoCreate,(SELECT COUNT(*)
                                                        FROM sports_contest_join
                                                        WHERE ContestID =  C.ContestID ) AS TotalJoined from sports_contest C WHERE  ContestID = ' . $ContestID . ' LIMIT 1')->result_array()[0]; 
            if ($ContestsData['TotalJoined'] >= $ContestsData['ContestSize'] && $ContestsData['IsAutoCreate'] == 'Yes') {
                $ContestData = $this->db->query('SELECT * FROM sports_contest WHERE ContestID = ' . $ContestID . ' LIMIT 1')->row_array();
                    /* Create Contest */
                $this->addContestAuto($ContestData, $ContestData['UserID'], $ContestData['SeriesID']);
            }
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $this->Users_model->getWalletDetails($SessionUserID);
    }

    function addContestAuto($Input = array(), $SessionUserID, $SeriesID, $StatusID = 1) {

        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();
        $EntityGUID = get_guid();

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" =>  $Input['UserID'], "StatusID" => $StatusID, "GameSportsType" => $Input['GameType']));
        
        /* Add contest to contest table . */

        $RoosterSize = footballGetConfiguration($Input['SubGameType']);
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);
        $RoosterConfiguration = footballGetConfigurationPlayersRooster($Input['ContestSize']);
        $InsertData = array_filter(array(
            "ContestID" => $EntityID,
            "ContestGUID" => $EntityGUID,
            "UserID" => $Input['UserID'],
            "ContestName" => @$Input['ContestName'],
            "LeagueType" => @$Input['LeagueType'],
            "LeagueJoinDateTime" => $Input['LeagueJoinDateTime'],
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', strtotime($Input['LeagueJoinDateTime']) + 3600) : null,
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
            "GamePlayType" => @$Input['GamePlayType'],
            "WeekEnd" => @$Input['WeekEnd'],
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
            "UserInvitationCode" => random_string('alnum', 6),
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "DraftTotalRounds" => $RoosterArray['RosterSize'],
            "DraftLiveRound" => 1,
            "RosterSize" => $RoosterArray['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0
        ));
        $this->db->insert('sports_contest', $InsertData);
        $PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $Input['WeekStart'],$Input['ContestDuration'],$Input['DailyDate']);
        if(!$PlayerIs) return false;
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    /*
      Description: To get joined contest
     */

    function getJoinedContests($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'MatchID' => 'M.MatchID',
                'MatchGUID' => 'M.MatchGUID',
                'StatusID' => 'E.StatusID',
                'MatchIDLive' => 'M.MatchIDLive',
                'MatchTypeID' => 'M.MatchTypeID',
                'MatchNo' => 'M.MatchNo',
                'MatchLocation' => 'M.MatchLocation',
                'MatchStartDateTime' => 'CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS MatchStartDateTime',
                'ContestID' => 'C.ContestID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'IsConfirm' => 'C.IsConfirm',
                'IsAutoCreate' => 'C.IsAutoCreate',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'WinningAmount' => 'C.WinningAmount',
                'ContestSize' => 'C.ContestSize',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'EntryType' => 'C.EntryType',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'UserID' => 'JC.UserID',
                'UserTeamID' => 'JC.UserTeamID',
                'JoinInning' => 'JC.JoinInning',
                'EntryDate' => 'JC.EntryDate',
                'TotalPoints' => 'JC.TotalPoints',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'SeriesID' => 'M.SeriesID',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'TeamNameShortLocal' => 'TL.TeamNameShort AS TeamNameShortLocal',
                'TeamNameShortVisitor' => 'TV.TeamNameShort AS TeamNameShortVisitor',
                'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'SeriesName' => 'S.SeriesName AS SeriesName',
                'TotalJoined' => '(SELECT COUNT(*) AS TotalJoined
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.ContestID =  C.ContestID ) AS TotalJoined',
                'UserTotalJoinedInMatch' => '(SELECT COUNT(*)
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.MatchID =  M.MatchID AND UserID= ' . $Where['SessionUserID'] . ') AS UserTotalJoinedInMatch',
                'UserRank' => 'JC.UserRank',
                'StatusID' => 'E.StatusID',
                'Status' => 'CASE E.StatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "3" then "Cancelled"
                when "5" then "Completed"
                END as Status',
                'MatchStartDateTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' %h:%i %p") MatchStartDateTime',
                'CurrentDateTime' => 'DATE_FORMAT(CONVERT_TZ(Now(),"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") CurrentDateTime',
                'MatchDate' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%Y-%m-%d") MatchDate',
                'MatchTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%H:%i:%s") MatchTime',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }

        $this->db->select('C.ContestGUID,C.ContestName,C.GamePlayType');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_contest C, sports_matches M, sports_teams TL, sports_teams TV,sports_series S,sports_contest_join JC');
        $this->db->where("C.ContestID", "JC.ContestID", FALSE);
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("M.MatchID", "C.MatchID", FALSE);
        $this->db->where("S.SeriesID", "C.SeriesID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->group_start();
            $this->db->like("C.ContestName", $Where['Keyword']);
            $this->db->or_like("M.MatchLocation", $Where['Keyword']);
            $this->db->or_like("M.MatchNo", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("C.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['SessionUserID'])) {
            $this->db->where("JC.UserID", $Where['SessionUserID']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("JC.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['Privacy'])) {
            $this->db->where("C.Privacy", $Where['Privacy']);
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
        if (!empty($Where['EntryFee'])) {
            $this->db->where("C.EntryFee", $Where['EntryFee']);
        }
        if (!empty($Where['NoOfWinners'])) {
            $this->db->where("C.NoOfWinners", $Where['NoOfWinners']);
        }
        if (!empty($Where['EntryType'])) {
            $this->db->where("C.EntryType", $Where['EntryType']);
        }
        // print_r($Where['MatchID']);die;
        if (!empty($Where['MatchID'])) {
            $this->db->where("C.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('M.MatchStartDateTime', 'ASC');
        //$this->db->group_by('C.ContestGUID');

        if (!empty($Where['getJoinedMatches']) && $Where['getJoinedMatches'] == 'Yes') {
            $this->db->group_by('C.MatchID');
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
        $Query = $this->db->get();
        //echo $this->db->laset_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array();
                }
                $Return['Data']['Records'] = $Records;
            } else {
                $Record = $Query->row_array();
                $Record['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array();
                return $Record;
            }
        } else {
            $Return['Data']['Records'] = array();
        }

        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '" 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();
        } else {
            $Return['Data']['Statics'] = $this->db->query('SELECT (
                SELECT COUNT(DISTINCT J.MatchID) AS `UpcomingJoinedContest` FROM `sports_contest_join` J, `tbl_entity` E , `sports_matches` M WHERE E.EntityID = J.ContestID AND J.MatchID=M.MatchID AND E.StatusID = 1 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                )as UpcomingJoinedContest,
                (
                SELECT COUNT(DISTINCT J.MatchID) AS `LiveJoinedContest` FROM `sports_contest_join` J, `tbl_entity` E , `sports_matches` M WHERE E.EntityID = J.ContestID AND J.MatchID=M.MatchID AND E.StatusID = 2 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                )as LiveJoinedContest,
                (
                SELECT COUNT(DISTINCT J.MatchID) AS `CompletedJoinedContest` FROM `sports_contest_join` J, `tbl_entity` E, `sports_matches` M WHERE E.EntityID = J.ContestID AND J.MatchID=M.MatchID AND E.StatusID = 5 AND J.UserID = "' . @$Where['SessionUserID'] . '"
            )as CompletedJoinedContest'
                    )->row();
        }

        return $Return;
    }

    /*
      Description: To get all players
     */

    function getPlayers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),"") AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('tbl_auction_player_bid_status APBS,sports_series S,sports_contest C');
            $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            if (!empty($Where['SessionUserID'])) {
                $this->db->where("UT.UserID", @$Where['SessionUserID']);
            }
            if (!empty($Where['IsAssistant'])) {
                $this->db->where("UT.IsAssistant", @$Where['IsAssistant']);
            }
            if (!empty($Where['IsPreTeam'])) {
                $this->db->where("UT.IsPreTeam", @$Where['IsPreTeam']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("P.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        $this->db->where('EXISTS (select PlayerID FROM sports_team_players WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ')');
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            $this->db->order_by('P.PlayerSalary', 'DESC');
            $this->db->order_by('P.PlayerID', 'DESC');
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

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                    $this->db->where('PlayerID', $Record['PlayerID']);
                    $this->db->from('sports_team_players');
                    $this->db->order_by("PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        $Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: ADD user team
     */

    function addUserTeam($Input = array(), $SessionUserID, $SeriesID, $ContestID, $StatusID = 2) {


        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $SessionUserID, "StatusID" => $StatusID));
        $UserTeamCount = $this->db->query('SELECT count(T.UserTeamID) as UserTeamsCount,U.Username from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.UserID = "' . $SessionUserID . '" ')->row();
        /* Add user team to user team table . */
        $teamName = "PreAuctionTeam 1";
        $InsertData = array(
            "UserTeamID" => $EntityID,
            "UserTeamGUID" => $EntityGUID,
            "UserID" => $SessionUserID,
            "UserTeamName" => $teamName,
            "UserTeamType" => @$Input['UserTeamType'],
            "IsPreTeam" => @$Input['IsPreTeam'],
            "SeriesID" => @$SeriesID,
            "ContestID" => @$ContestID,
            "IsAssistant" => "No",
        );
        $this->db->insert('sports_users_teams', $InsertData);

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->Sports_model->getPlayers('PlayerID,MatchID', array('SeriesID' => $SeriesID), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $EntityID,
                        'SeriesID' => @$SeriesID,
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                        'BidCredit' => $Value['BidCredit']
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return $EntityGUID;
    }

    /*
      Description: Assistant on off
     */

    function assistantTeamOnOff($Input = array(), $SessionUserID, $SeriesID, $ContestID, $UserTeamID) {

        $this->db->trans_start();

        /* Update Contest Status */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->where('UserID', $SessionUserID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('IsPreTeam', "Yes");
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('IsAssistant' => @$Input['IsAssistant']));

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: add auction player bid
     */

    function get_max($Array, $Index) {
        $All = array();
        foreach ($Array as $key => $value) {
            /* creating array where the key is transaction_no and
              the value is the array containing this transaction_no */
            $All[$value['BidCredit']] = $value;
        }
        /* now sort the array by the key (transaction_no) */
        krsort($All);
        /* get the second array and return it (see the link below) */
        return array_slice($All, $Index, 1)[0];
    }

    function addAuctionPlayerBid($Input = array(), $SessionUserID, $SeriesID, $ContestID, $PlayerID) {
        $Return = array();
        /** to check user already in bid * */
        $this->db->select("PlayerID,UserID,DateTime");
        $this->db->from('tbl_auction_player_bid');
        $this->db->where("PlayerID", $PlayerID);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->limit(1);
        $this->db->order_by("DateTime", "DESC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $PlayerBid = $Query->result_array();
            if (!empty($PlayerBid)) {
                if ($SessionUserID == $PlayerBid[0]['UserID']) {
                    $Return["Message"] = "You are currently in bid please wait next bid";
                    $Return["Status"] = 0;
                    return $Return;
                }
            }
        }

        /** to check auction in live * */
        $AuctionGames = $this->getContests('ContestID,AuctionBreakDateTime,AuctionStatus,SeriesID,AuctionTimeBreakAvailable,AuctionIsBreakTimeStatus', array('AuctionStatusID' => 2, 'ContestID' => $ContestID), FALSE);
        if (empty($AuctionGames)) {
            $Return["Message"] = "Auction not stared.";
            $Return["Status"] = 0;
            return $Return;
        }

        /** to check user available budget * */
        $this->db->select("AuctionBudget");
        $this->db->from('sports_contest_join');
        $this->db->where("AuctionBudget >=", $Input['BidCredit']);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("UserID", $SessionUserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            /** To check player in assistant * */
            $BidUserID = "";
            $BidUserCredit = "";
            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID");
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            $this->db->where("UT.IsAssistant", "Yes");
            $this->db->where("UT.IsPreTeam", "Yes");
            $this->db->where("UTP.BidCredit >", $Input['BidCredit']);
            $this->db->where("UT.ContestID", $ContestID);
            $this->db->where("UT.SeriesID", $SeriesID);
            $this->db->where("UTP.PlayerID", $PlayerID);
            $Query = $this->db->get();
            $PlayersAssistant = $Query->result_array();
            $Rows = $Query->num_rows();
            if ($Rows > 0) {
                /** To check assistant player single * */
                if ($Rows == 1) {

                    $CurrentBidCredit = $Input['BidCredit'];
                    $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
                    if ($AssistantBidCredit > $CurrentBidCredit) {
                        if (100000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000) {
                            $CurrentBidCredit = $CurrentBidCredit + 100000;
                        } else if (1000000 >= $CurrentBidCredit || $CurrentBidCredit < 10000000) {
                            $CurrentBidCredit = $CurrentBidCredit + 1000000;
                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 100000000) {
                            $CurrentBidCredit = $CurrentBidCredit + 10000000;
                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000000) {
                            $CurrentBidCredit = $CurrentBidCredit + 100000000;
                        }
                    }
                    $BidUserID = $PlayersAssistant[0]['UserID'];
                    $BidUserCredit = $CurrentBidCredit;

                    /** to check user available budget * */
                    $this->db->select("AuctionBudget");
                    $this->db->from('sports_contest_join');
                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                    $this->db->where("ContestID", $ContestID);
                    $this->db->where("SeriesID", $SeriesID);
                    $this->db->where("UserID", $PlayersAssistant[0]['UserID']);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        /* add player bid */
                        $InsertData = array(
                            "SeriesID" => $SeriesID,
                            "ContestID" => $ContestID,
                            "UserID" => $PlayersAssistant[0]['UserID'],
                            "PlayerID" => $PlayerID,
                            "BidCredit" => $CurrentBidCredit,
                            "DateTime" => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('tbl_auction_player_bid', $InsertData);
                    } else {
                        $Return["Message"] = "You have not insufficient budget";
                        $Return["Status"] = 0;
                        return $Return;
                    }
                } else if ($Rows > 1) {
                    /** get second highest user* */
                    $SecondUser = $this->get_max($PlayersAssistant, 1);
                    $CurrentBidCredit = $AssistantBidCredit = $SecondUser['BidCredit'];
                    if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 100000;
                    } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 1000000;
                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 10000000;
                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                        $CurrentBidCredit = $AssistantBidCredit + 100000000;
                    }
                    /** get top user* */
                    $TopUser = $this->get_max($PlayersAssistant, 0);
                    $TopUserBidCredit = $TopUser['BidCredit'];
                    if ($CurrentBidCredit > $TopUserBidCredit) {
                        $CurrentBidCredit = $TopUserBidCredit;
                    }
                    $BidUserID = $TopUser['UserID'];
                    $BidUserCredit = $CurrentBidCredit;

                    /** to check user available budget * */
                    $this->db->select("AuctionBudget");
                    $this->db->from('sports_contest_join');
                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                    $this->db->where("ContestID", $ContestID);
                    $this->db->where("SeriesID", $SeriesID);
                    $this->db->where("UserID", $TopUser['UserID']);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        /* add player bid */
                        $InsertData = array(
                            "SeriesID" => $SeriesID,
                            "ContestID" => $ContestID,
                            "UserID" => $TopUser['UserID'],
                            "PlayerID" => $PlayerID,
                            "BidCredit" => $CurrentBidCredit,
                            "DateTime" => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('tbl_auction_player_bid', $InsertData);
                    } else {
                        $Return["Message"] = "You have not insufficient budget";
                        $Return["Status"] = 0;
                        return $Return;
                    }
                }
            } else {
                $BidUserID = $SessionUserID;
                $BidUserCredit = $Input['BidCredit'];
                /* add player bid */
                $InsertData = array(
                    "SeriesID" => $SeriesID,
                    "ContestID" => $ContestID,
                    "UserID" => $SessionUserID,
                    "PlayerID" => $PlayerID,
                    "BidCredit" => @$Input['BidCredit'],
                    "DateTime" => date('Y-m-d H:i:s')
                );
                $this->db->insert('tbl_auction_player_bid', $InsertData);
            }
            if (!empty($BidUserID) && !empty($BidUserCredit)) {
                $UserData = $this->Users_model->getUsers("Email", array('UserID' => $BidUserID));
                $UserData['BidCredit'] = $BidUserCredit;
                $Return["Message"] = "You have not insufficient budget";
                $Return["Status"] = 1;
                $Return["Data"] = $UserData;
            }
        } else {
            $Return["Message"] = "You have not insufficient budget";
            $Return["Status"] = 0;
        }

        return $Return;
    }

    /*
      Description: get auction bid player time
     */

    function auctionBidTimeManagement($Input, $ContestID = "", $SeriesID = "") {
        $Players = array();
        $TempPlayer = array();
        /** get live auction * */
        $AuctionGames = $this->getContests('ContestID,AuctionBreakDateTime,AuctionStatus,SeriesID,AuctionTimeBreakAvailable,AuctionIsBreakTimeStatus', array('AuctionStatusID' => 2, 'ContestID' => $ContestID, 'SeriesID' => $SeriesID), TRUE, 1);
        if ($AuctionGames['Data']['TotalRecords'] > 0) {
            foreach ($AuctionGames['Data']['Records'] as $Auction) {
                $Players = array();
                /** get contest hold user time management * */
                $AuctionHoldDateTime = "";
                $this->db->select("ContestID,UserID,AuctionTimeBank,AuctionHoldDateTime");
                $this->db->from('sports_contest_join');
                $this->db->where("ContestID", $Auction['ContestID']);
                $this->db->where("SeriesID", $Auction['SeriesID']);
                $this->db->where("IsHold", "Yes");
                $Query = $this->db->get();
                $Rows = $Query->num_rows();
                $HoldUser = $Query->row_array();
                if (!empty($HoldUser)) {
                    $AuctionHoldDateTime = $HoldUser['AuctionHoldDateTime'];
                }
                /** get live player * */
                $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Live')));
                if (!empty($playersData)) {
                    $Players[] = $playersData;
                } else {
                    /** get upcoming player * */
                    $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Upcoming')));
                    if (!empty($playersData)) {
                        $Players[] = $playersData;
                    }
                }
                if (!empty($Players)) {
                    foreach ($Players as $key => $Player) {

                        $Players[$key]['AuctionTimeBreakAvailable'] = $Auction['AuctionTimeBreakAvailable'];
                        $Players[$key]['AuctionIsBreakTimeStatus'] = $Auction['AuctionIsBreakTimeStatus'];
                        /** auction break date time to current date time difference * */
                        $Players[$key]['BreakTimeInSec'] = 0;
                        if ($Auction['AuctionIsBreakTimeStatus'] == "Yes" && $Auction['AuctionTimeBreakAvailable'] == "No") {
                            $AuctionBreakDateTime = $Auction['AuctionBreakDateTime'];
                            $CurrentDateTime = date('Y-m-d H:i:s');
                            $CurrentDateTime = new DateTime($CurrentDateTime);
                            $AuctionBreakDateTime = new DateTime($AuctionBreakDateTime);
                            $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                            $Players[$key]['BreakTimeInSec'] = $diffSeconds;
                        }


                        /** to check player in already bid * */
                        $this->db->select("PlayerID,SeriesID,ContestID,BidCredit,DateTime");
                        $this->db->from('tbl_auction_player_bid');
                        $this->db->where("ContestID", $Player['ContestID']);
                        $this->db->where("SeriesID", $Player['SeriesID']);
                        $this->db->where("PlayerID", $Player['PlayerID']);
                        $this->db->order_by("DateTime", "DESC");
                        $this->db->limit(1);
                        $PlayerDetails = $this->db->get()->result_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        if (!empty($PlayerDetails)) {
                            $Players[$key]['IsSold'] = "UpcomingSold";
                            $DateTime = $PlayerDetails[0]['DateTime'];
                            /** get bid time difference in seconds * */
                            $Players[$key]['TimeDifference'] = strtotime($CurrentDateTime) - strtotime($DateTime);
                            if (!empty($AuctionHoldDateTime)) {
                                $Players[$key]['TimeDifference'] = strtotime($AuctionHoldDateTime) - strtotime($DateTime);
                            }
                        } else {
                            /** get bid time difference in seconds * */
                            if (!empty($Player['BidDateTime'])) {
                                $Players[$key]['TimeDifference'] = strtotime($CurrentDateTime) - strtotime($Player['BidDateTime']);

                                if (!empty($AuctionHoldDateTime)) {
                                    $Players[$key]['TimeDifference'] = strtotime($AuctionHoldDateTime) - strtotime($Player['BidDateTime']);
                                }
                            } else {
                                /** check first player and second player * */
                                $this->db->select("ContestID");
                                $this->db->from('tbl_auction_player_bid_status');
                                $this->db->where("ContestID", $Auction['ContestID']);
                                $this->db->where("SeriesID", $Auction['SeriesID']);
                                $this->db->where("DateTime is NOT NULL", NULL, FALSE);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Players[$key]['TimeDifference'] = 15;
                                } else {
                                    $Players[$key]['TimeDifference'] = 20;
                                }
                            }

                            $Players[$key]['IsSold'] = "UpcomingUnSold";
                        }
                    }
                    $TempPlayer[] = $Players[0];
                }
            }
        }

        return $TempPlayer;
    }

    /*
      Description: EDIT user team
     */

    function editUserTeam($Input = array(), $UserTeamID) {



        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('UserTeamName' => $Input['UserTeamName'], 'UserTeamType' => $Input['UserTeamType']));

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get SeriesID */
            $SeriesID = $this->db->query('SELECT SeriesID FROM sports_users_teams WHERE UserTeamID = ' . $UserTeamID . ' LIMIT 1')->row()->SeriesID;


            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->Sports_model->getPlayers('PlayerID,SeriesID', array('SeriesID' => $SeriesID), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                        'BidCredit' => $Value['BidCredit']
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: To get user teams
     */

    function getUserTeams($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserTeamID' => 'UT.UserTeamID',
                'MatchID' => 'UT.MatchID',
                'MatchInning' => 'UT.MatchInning'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('UT.UserTeamGUID,UT.UserTeamName,UT.UserTeamType');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_users_teams UT');
        $this->db->where("UT.UserTeamID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->like("UT.UserTeamName", $Where['Keyword']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("UT.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("UT.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['UserTeamType']) && $Where['UserTeamType'] != 'All') {
            $this->db->where("UT.UserTeamType", $Where['UserTeamType']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UT.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['MatchInning'])) {
            $this->db->where("UT.MatchInning", $Where['MatchInning']);
        }
        if (!empty($Where['UserID']) && empty($Where['UserTeamID'])) { // UserTeamID used to manage other user team details (On live score page)
            $this->db->where("UT.UserID", $Where['UserID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UT.UserTeamID', 'DESC');
        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (
                SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal"
                )as NormalContest,
                (
                SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse"
                )as ReverseContest,
                (
                SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '"
                )as JoinedContest,
                ( 
                SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '" 
            ) as TotalTeams'
                    )->row();
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

        $Query = $this->db->get();


        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                if (in_array('UserTeamPlayers', $Params)) {
                    foreach ($Return['Data']['Records'] as $key => $value) {
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerPic,PlayerCountry,PlayerRole,Points', array('UserTeamID' => $value['UserTeamID']));
                    }
                }
                return $Return;
            } else {
                $Record = $Query->row_array();
                if (in_array('UserTeamPlayers', $Params)) {
                    $UserTeamPlayers = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerPic,PlayerCountry,PlayerRole,Points,BidCredit,ContestGUID', array('UserTeamID' => $Where['UserTeamID']));
                    $Record['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                }
                return $Record;
            }
        }

        return FALSE;
    }

    /*
      Description: To get user team players
     */

    function getUserTeamPlayers($Field = '', $Where = array()) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerPosition' => 'UTP.PlayerPosition',
                'Points' => 'UTP.Points',
                'PlayerName' => 'P.PlayerName',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerSalary' => 'P.PlayerSalary',
                'PlayerRole' => 'TP.PlayerRole',
                'TeamGUID' => 'T.TeamGUID',
                'MatchType' => 'SM.MatchTypeName as MatchType',
                'BidCredit' => 'UTP.BidCredit'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_users_team_players UTP, sports_players P, sports_team_players TP,sports_teams T,sports_matches M,sports_set_match_types SM');
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        $this->db->where("UTP.PlayerID", "TP.PlayerID", FALSE);
        // $this->db->where("UTP.MatchID", "TP.MatchID", FALSE);
        $this->db->where("T.TeamID", "TP.TeamID", FALSE);
        // $this->db->where("M.MatchID", "TP.MatchID", FALSE);
        // $this->db->where("M.MatchTypeID", "SM.MatchTypeID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->like("P.PlayerName", $Where['Keyword']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UTP.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['PlayerRole'])) {
            $this->db->where("TP.PlayerRole", $Where['PlayerRole']);
        }
        if (!empty($Where['PlayerPosition'])) {
            $this->db->where("UTP.PlayerPosition", $Where['PlayerPosition']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->group_by('P.PlayerID');
        $this->db->order_by('P.PlayerName', 'ASC');
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = array();
            foreach ($Query->result_array() as $key => $Record) {
                $Records[] = $Record;
                if (array_keys_exist($Params, array('PlayerSalary'))) {
                    $Records[$key]['PlayerSalary'] = (!empty($Record['PlayerSalary'])) ? json_decode($Record['PlayerSalary']) : new stdClass();
                }

                if (array_keys_exist($Params, array('PointCredits'))) {
                    if ($Record['MatchType'] == 'T20') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20Credits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20Credits'] : 0;
                    } else if ($Record['MatchType'] == 'Test') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20iCredits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20iCredits'] : 0;
                    } else if ($Record['MatchType'] == 'T20I') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['ODICredits']) ? json_decode($Record['PlayerSalary'], TRUE)['ODICredits'] : 0;
                    } else if ($Record['MatchType'] == 'ODI') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['TestCredits']) ? json_decode($Record['PlayerSalary'], TRUE)['TestCredits'] : 0;
                    } else {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20Credits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20Credits'] : 0;
                    }
                }
            }
            return $Records;
        }
        return FALSE;
    }

    /*
      Description: To get contest winning users
     */

    function getContestWinningUsers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'TotalPoints' => 'JC.TotalPoints',
                'EntryFee' => 'C.EntryFee',
                'ContestSize' => 'C.ContestSize',
                'NoOfWinners' => 'C.NoOfWinners',
                'UserTeamName' => 'UT.UserTeamName',
                'FullName' => 'CONCAT_WS(" ",U.FirstName,U.LastName) FullName',
                'UserRank' => 'JC.UserRank'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('C.ContestName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_contest_join JC, sports_contest C, sports_users_teams UT, tbl_users U');
        $this->db->where("C.ContestID", "JC.ContestID", FALSE);
        $this->db->where("JC.UserTeamID", "UT.UserTeamID", FALSE);
        $this->db->where("JC.UserID", "U.UserID", FALSE);
        $this->db->where("JC.UserWinningAmount >", 0);
        if (!empty($Where['Keyword'])) {
            $this->db->like("C.ContestName", $Where['ContestName']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UserRank', 'ASC');

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
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description: To get joined contest users
     */

    function getJoinedContestsUsers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TotalPoints' => 'JC.TotalPoints',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'FirstName' => 'U.FirstName',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'Username' => 'U.Username',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'UserRank' => 'JC.UserRank',
                'AuctionTimeBank' => 'JC.AuctionTimeBank',
                'AuctionBudget' => 'JC.AuctionBudget',
                'AuctionUserStatus' => 'JC.AuctionUserStatus',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'UserRank' => 'JC.UserRank'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_contest_join JC, tbl_users U');
        //$this->db->where("JC.UserTeamID", "UT.UserTeamID", FALSE);
        $this->db->where("JC.UserID", "U.UserID", FALSE);
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("JC.UserID", $Where['UserID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('JC.UserWinningAmount', 'DESC');
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
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    //$UserTeamPlayers = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerRole,PlayerPic,TeamGUID,PlayerSalary,MatchType,PointCredits', array('UserTeamID' => $record['UserTeamID']));
                    // $Return['Data']['Records'][$key]['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                }
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    /*
      Description: To Cancel Contest
     */

    function cancelContest($Input = array(), $SessionUserID, $ContestID) {
        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,IsPaid,EntryFee,LeagueJoinDateTime', array('ContestID' => $ContestID), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {

            foreach ($ContestsUsers['Data']['Records'] as $Value) {

                $IsCancelled = (($Value['TotalJoined'] != $Value['ContestSize']) ? 1 : 0);
                if ($IsCancelled == 0)
                    continue;

                /* Update Contest Status */
                $this->db->where('EntityID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('AuctionStatusID' => 3));

                /* Get Joined Contest */
                $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID']), TRUE, 0);
                if (!$JoinedContestsUsers)
                    continue;

                foreach ($JoinedContestsUsers['Data']['Records'] as $Rows) {

                    /* Refund Wallet Money */
                    if (!empty($Value['EntryFee'])) {

                        /* Get Wallet Details */
                        $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                            'UserID' => $Rows['UserID'],
                            'EntityID' => $Value['ContestID'],
                            'Narration' => 'Join Contest'
                        ));

                        $InsertData = array(
                            "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['WinningAmount'],
                            "WalletAmount" => $WalletDetails['WalletAmount'],
                            "WinningAmount" => $WalletDetails['WinningAmount'],
                            "CashBonus" => $WalletDetails['CashBonus'],
                            "TransactionType" => 'Cr',
                            "EntityID" => $Value['ContestID'],
                            "Narration" => 'Cancel Contest',
                            "EntryDate" => date("Y-m-d H:i:s")
                        );
                        $this->Users_model->addToWallet($InsertData, $Rows['UserID'], 5);
                    }

                    /* Send Mail To Users */

                    /*send_mail(array(
                        'emailTo' => $Rows['Email'],
                        'template_id' => 'd-9683d71dcf0546bdb255e4edaffa09ba',
                        'Subject' => SITE_NAME . " Contest Cancelled",
                        "Name" => $Rows['FirstName'],
                        "ContestName" => $Value['ContestName'],
                        "EmailText" => $Value['LeagueJoinDateTime']
                    ));*/
                }
            }
        }
    }

    function cancelContest_old($Input = array(), $SessionUserID, $ContestID) {

        /* Update Contest Status */
        $this->db->where('EntityID', $ContestID);
        $this->db->limit(1);
        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

        /* Get Joined Contest */
        $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $ContestID), TRUE, 0);
        if (!$JoinedContestsUsers)
            exit;

        foreach ($JoinedContestsUsers['Data']['Records'] as $Value) {

            /* Refund Wallet Money */
            if (!empty($Input['EntryFee'])) {

                /* Get Wallet Details */
                $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                    'UserID' => $Value['UserID'],
                    'EntityID' => $ContestID,
                    'UserTeamID' => $Value['UserTeamID'],
                    'Narration' => 'Join Contest'
                ));

                $InsertData = array(
                    "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['WinningAmount'],
                    "WalletAmount" => $WalletDetails['WalletAmount'],
                    "WinningAmount" => $WalletDetails['WinningAmount'],
                    "CashBonus" => $WalletDetails['CashBonus'],
                    "TransactionType" => 'Cr',
                    "EntityID" => $ContestID,
                    "UserTeamID" => $Value['UserTeamID'],
                    "Narration" => 'Cancel Contest',
                    "EntryDate" => date("Y-m-d H:i:s")
                );
                $this->Users_model->addToWallet($InsertData, $Value['UserID'], 5);
            }

            /* Send Mail To Users */
            $EmailArr = array(
                "Name" => $Value['FirstName'],
                "SeriesName" => @$Input['SeriesName'],
                "ContestName" => @$Input['ContestName'],
                "MatchNo" => @$Input['MatchNo'],
                "TeamNameLocal" => @$Input['TeamNameLocal'],
                "TeamNameVisitor" => @$Input['TeamNameVisitor']
            );
            /* sendMail(array(
              'emailTo' => $Value['Email'],
              'emailSubject' => "Cancel Contest- " . SITE_NAME,
              'emailMessage' => emailTemplate($this->load->view('emailer/cancel_contest', $EmailArr, TRUE))
              )); */
        }
    }

    /*
      Description: To get joined contest users
     */

    function getContestBidHistory($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'FirstName' => 'U.FirstName',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'Username' => 'U.Username',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'BidCredit' => 'JC.BidCredit',
                'DateTime' => 'JC.DateTime',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_auction_player_bid JC, tbl_users U');

        $this->db->where("JC.UserID", "U.UserID", FALSE);
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("JC.UserID", $Where['UserID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('JC.DateTime', 'DESC');
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
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    //$UserTeamPlayers = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerRole,PlayerPic,TeamGUID,PlayerSalary,MatchType,PointCredits', array('UserTeamID' => $record['UserTeamID']));
                    // $Return['Data']['Records'][$key]['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                }
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    /*
      Description: To auto add minute in every hours
     */

    function auctionLiveAddMinuteInEveryHours($CronID) {


        /* Get Contests Data */
        $Contests = $this->getContests("ContestID,SeriesID,AuctionUpdateTime,LeagueJoinDateTimeUTC,AuctionTimeBreakAvailable", array('LeagueType' => 'Auction', "AuctionStatusID" => 2), TRUE, 1, 50);
        if (isset($Contests['Data']['Records'])) {
            /* $this->db->where('CronID', $CronID);
              $this->db->limit(1);
              $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query()), JSON_UNESCAPED_UNICODE))); */

            foreach ($Contests['Data']['Records'] as $Value) {
                $CurrentDatetime = strtotime(date('Y-m-d H:i:s'));
                $AuctionUpdateTime = strtotime($Value['AuctionUpdateTime']);
                if ($CurrentDatetime >= $AuctionUpdateTime) {
                    /** contest auction joined user get * */
                    $this->db->select("ContestID,UserID,AuctionTimeBank");
                    $this->db->from('sports_contest_join');
                    $this->db->where("ContestID", $Value['ContestID']);
                    $this->db->where("SeriesID", $Value['SeriesID']);
                    $Query = $this->db->get();
                    $Rows = $Query->num_rows();
                    if ($Rows > 0) {
                        $JoinedUsers = $Query->result_array();
                        foreach ($JoinedUsers as $User) {
                            /** contest auction user time bank update every hours * */
                            $UpdateData = array(
                                "AuctionTimeBank" => $User['AuctionTimeBank'] + 60
                            );
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserID', $User['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', $UpdateData);
                        }
                    }

                    /** contest auction break time update * */
                    $UpdateData = array(
                        "AuctionTimeBreakAvailable" => "Yes",
                        "AuctionUpdateTime" => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 60)
                    );
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', $UpdateData);
                }
            }
        }
        return true;
    }

    /*
      Description: Update user status.
     */

    function changeUserStatus($Input = array(), $UserID, $ContestID) {

        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionUserStatus" => $Input['AuctionUserStatus']
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        return true;
    }

    /*
      Description: Update contest status.
     */

    function changeContestStatus($ContestID) {

        /* Add contest to contest table . */
        /* Update Match Status */
        $this->db->where('EntityID', $ContestID);
        $this->db->limit(1);
        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));
        return true;
    }

    /*
      Description: Update user hold time.
     */

    function auctionHoldTimeUpdate($Input = array(), $UserID, $ContestID) {

        $AuctionTimeBank = $this->db->query('SELECT AuctionTimeBank FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' LIMIT 1')->row()->AuctionTimeBank;
        $RemainingTime = $AuctionTimeBank - $Input['HoldTime'];
        if ($RemainingTime < 0) {
            $RemainingTime = 0;
        }
        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionTimeBank" => $RemainingTime
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        return true;
    }

    /*
      Description: Update user status.
     */

    function changeUserContestStatusHoldOnOff($Input = array(), $UserID, $ContestID) {
        $Return = array();
        /* Add contest to contest table . */
        $UpdateData = array();
        $UpdateData['IsHold'] = $Input['IsHold'];
        if ($Input['IsHold'] == "Yes") {
            /** to check already user in hold * */
            $this->db->select("UserID");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("UserID", $UserID);
            $this->db->where("IsHold", "Yes");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Return["Message"] = "Auction already hold";
                $Return["Status"] = 0;
                return $Return;
            }

            $UpdateData['AuctionHoldDateTime'] = date("Y-m-d H:i:s");

            /** check user time left * */
            $AuctionTimeBank = $this->db->query('SELECT AuctionTimeBank FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' AND AuctionTimeBank <= 0 LIMIT 1')->row()->AuctionTimeBank;
            if (!empty($AuctionTimeBank)) {
                $Return["Message"] = "User hold time exceeded";
                $Return["Status"] = 0;
                return $Return;
            }
        }
        if ($Input['IsHold'] == "No") {

            /** to check already user in unhold * */
            $this->db->select("UserID");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("UserID", $UserID);
            $this->db->where("IsHold", "No");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Return["Message"] = "User alrady unhold";
                $Return["Status"] = 1;
                return $Return;
            }

            /** check user on hold * */
            $IsHold = $this->db->query('SELECT IsHold FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' AND IsHold= "Yes" LIMIT 1')->row()->IsHold;
            if (!empty($IsHold)) {
                /* update user time break . */
                $Query = $this->db->query('SELECT AuctionHoldDateTime,AuctionTimeBank FROM sports_contest_join WHERE ContestID = "' . $ContestID . '" AND UserID = "' . $UserID . '" LIMIT 1');
                $Contest = $Query->row_array();
                if (!empty($Contest)) {
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionHoldDateTime = new DateTime($Contest['AuctionHoldDateTime']);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionHoldDateTime->getTimestamp();
                    $AuctionTimeBank = $Contest['AuctionTimeBank'] - $diffSeconds;
                    if ($AuctionTimeBank < 0) {
                        $AuctionTimeBank = 0;
                    }
                    $UpdateData['AuctionTimeBank'] = $AuctionTimeBank;
                }

                /* get last player last bid . */
                $Input['Params'] = "ContestGUID,SeriesGUID,SeriesID,ContestID,TimeDifference,BidDateTime,PlayerStatus,PlayerGUID,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerSalary,PlayerSalaryCredit";
                $AuctionList = $this->auctionBidTimeManagement($Input, $ContestID);
                if (!empty($AuctionList)) {
                    $TimeDifference = abs($AuctionList[0]['TimeDifference']);
                    $PlayerStatus = abs($AuctionList[0]['PlayerStatus']);
                    /** update player table date time upcoming * */
                    if ($PlayerStatus == "Upcoming") {
                        $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                        $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                        /** update player table date time * */
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('SeriesID', $AuctionList[0]['SeriesID']);
                        $this->db->where('PlayerID', $AuctionList[0]['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', array("DateTime" => $CurrentDate));
                    }
                    /** update player table date time live * */
                    if ($PlayerStatus == "Live") {
                        /* get last player bid auction contest . */
                        $this->db->select("PlayerID,SeriesID,ContestID,UserID,BidCredit,DateTime");
                        $this->db->from('tbl_auction_player_bid');
                        $this->db->where("ContestID", $ContestID);
                        $this->db->where("PlayerID", $AuctionList[0]['PlayerID']);
                        $this->db->order_by("DateTime", "DESC");
                        $this->db->limit(1);
                        $LastBid = $this->db->get()->row_array();
                        if (!empty($LastBid)) {
                            $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                            $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                            /** update player table date time * */
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('SeriesID', $LastBid['SeriesID']);
                            $this->db->where('PlayerID', $LastBid['PlayerID']);
                            $this->db->where('UserID', $LastBid['UserID']);
                            $this->db->where('BidCredit', $LastBid['BidCredit']);
                            $this->db->where('DateTime', $LastBid['DateTime']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid', array("DateTime" => $CurrentDate));
                        } else {
                            /** update player table date time * */
                            $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                            $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                            /** update player table date time * */
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('SeriesID', $AuctionList[0]['SeriesID']);
                            $this->db->where('PlayerID', $AuctionList[0]['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', array("DateTime" => $CurrentDate));
                        }
                    }
                }
            } else {
                $Return["Message"] = "Auction already unhold";
                $Return["Status"] = 0;
                return $Return;
            }
        }
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        $Return["Message"] = "User hold status successfully updated";
        $Return["Status"] = 1;
        return $Return;
    }

    /*
      Description: aution on break
     */

    function auctionOnBreak($Input = array(), $ContestID) {
        $UpdateData = array();

        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionIsBreakTimeStatus" => $Input['AuctionIsBreakTimeStatus'],
            "AuctionTimeBreakAvailable" => $Input['AuctionTimeBreakAvailable']
        );
        if ($Input['AuctionIsBreakTimeStatus'] == "Yes") {
            $UpdateData['AuctionBreakDateTime'] = date('Y-m-d H:i:s');
        }
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
        return true;
    }

}

?>