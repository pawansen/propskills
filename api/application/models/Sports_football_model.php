<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sports_football_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->model('AuctionDrafts_model');
        $this->load->model('SnakeDrafts_model');
    }

    /*
      Description: Custom query set
     */

    public function customQuery($Sql, $Single = false, $UpdDelete = false, $NoReturn = false) {
        $Query = $this->db->query($Sql);
        if ($Single) {
            return $Query->row_array();
        } elseif ($UpdDelete) {
            return $this->db->affected_rows();
        } elseif (!$NoReturn) {
            return $Query->result_array();
        } else {
            return true;
        }
    }

    /*
      Description: To get all series
     */

    function getSeries($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesID' => 'S.SeriesID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'StatusID' => 'E.StatusID',
                'GameSportsType' => 'E.GameSportsType',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'AuctionDraftIsPlayed' => 'S.AuctionDraftIsPlayed',
                'SeriesYear' => 'S.SeriesYear',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'SeriesStartDateUTC' => 'S.SeriesStartDate as SeriesStartDateUTC',
                'SeriesEndDateUTC' => 'S.SeriesEndDate as SeriesEndDateUTC',
                'TotalMatches' => '(SELECT COUNT(*) AS TotalMatches
                FROM sports_matches
                WHERE sports_matches.SeriesID =  S.SeriesID ) AS TotalMatches',
                'CurrentWeek' => "(SELECT SM.WeekID 
                FROM sports_matches SM,tbl_entity E
                WHERE E.EntityID=SM.MatchID AND SM.SeriesID =  S.SeriesID AND SM.MatchStartDateTime <='" . date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 14400) . "' AND E.StatusID=1 LIMIT 1) AS CurrentWeek ",
                'Status' => 'CASE E.StatusID
                when "2" then "Active"
                when "6" then "Inactive"
                END as Status',
                'AuctionDraftStatus' => 'CASE S.AuctionDraftStatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "5" then "Completed"
                END as AuctionDraftStatus',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('S.SeriesGUID,S.SeriesName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_series S');
        $this->db->where("S.SeriesID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->like("S.SeriesName", $Where['Keyword']);
        }
        if (!empty($Where['DraftAuctionPlay']) && $Where['DraftAuctionPlay'] == "Yes") {
            $this->db->where("S.AuctionDraftIsPlayed", "Yes");
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("S.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['SeriesStartDate'])) {
            $this->db->where("S.SeriesStartDate >=", $Where['SeriesStartDate']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.SeriesEndDate >=", $Where['SeriesEndDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['GameSportsType'])) {
            $this->db->where("E.GameSportsType", $Where['GameSportsType']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }

        /** open after code production mode * */
        if (!empty($Where['AuctionDraftStatusID'])) {
            $this->db->where("S.AuctionDraftStatusID", $Where['AuctionDraftStatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('E.StatusID', 'ASC');
        $this->db->order_by('S.SeriesStartDate', 'DESC');
        $this->db->order_by('S.SeriesName', 'ASC');

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
                $Return['Data']['Records'] = $Query->result_array();
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description: Use to match type data.
     */

    function getMatchTypes($MatchTypeID = '') {
        $this->db->select('*');
        $this->db->from('sports_set_match_types');
        if ($MatchTypeID) {
            $this->db->where("MatchTypeID", $MatchTypeID);
        }
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            return $Query->result_array();
        }
        return FALSE;
    }

    /*
      Description: To get all matches
     */

    function getMatches($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesID' => 'S.SeriesID',
                'SeriesGUID' => 'S.SeriesGUID',
                'StatusID' => 'E.StatusID',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'SeriesName' => 'S.SeriesName',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'MatchID' => 'M.MatchID',
                'SeasonType' => 'M.SeasonType',
                'WeekID' => 'M.WeekID',
                'WeekName' => 'M.WeekName',
                'MatchNo' => 'M.MatchNo',
                'MatchIDLive' => 'M.MatchIDLive',
                'MatchTypeID' => 'M.MatchTypeID',
                'ScoreIDLive' => 'M.ScoreIDLive',
                'MatchNo' => 'M.MatchNo',
                'MatchLocation' => 'M.MatchLocation',
                'IsPreSquad' => 'M.IsPreSquad',
                'IsPlayerPointsUpdated' => 'M.IsPlayerPointsUpdated',
                'MatchScoreDetails' => 'M.MatchScoreDetails',
                'MatchStartDateTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") MatchStartDateTime',
                'CurrentDateTime' => 'DATE_FORMAT(CONVERT_TZ(Now(),"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") CurrentDateTime',
                'MatchDate' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%Y-%m-%d") MatchDate',
                'MatchTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%H:%i:%s") MatchTime',
                'MatchStartDateTimeUTC' => 'M.MatchStartDateTime as MatchStartDateTimeUTC',
                'MatchStartDateTimeEST' => 'M.MatchStartDateTimeEST',
                'ServerDateTimeUTC' => 'UTC_TIMESTAMP() as ServerDateTimeUTC',
                'TeamIDLocal' => 'TL.TeamID AS TeamIDLocal',
                'TeamIDVisitor' => 'TV.TeamID AS TeamIDVisitor',
                'TeamGUIDLocal' => 'TL.TeamGUID AS TeamGUIDLocal',
                'TeamGUIDVisitor' => 'TV.TeamGUID AS TeamGUIDVisitor',
                'TeamIDLiveLocal' => 'TL.TeamIDLive AS TeamIDLiveLocal',
                'TeamIDLiveVisitor' => 'TV.TeamIDLive AS TeamIDLiveVisitor',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'TeamNameShortLocal' => 'TL.TeamNameShort AS TeamNameShortLocal',
                'TeamNameShortVisitor' => 'TV.TeamNameShort AS TeamNameShortVisitor',
                'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'MyTotalJoinedContest' => '(SELECT COUNT(DISTINCT sports_contest_join.ContestID)
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.MatchID =  M.MatchID AND UserID= ' . @$Where['UserID'] . ') AS MyTotalJoinedContest',
                'Status' => 'CASE E.StatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "3" then "Cancelled"
                when "5" then "Completed"  
                when "8" then "Abandoned"  
                when "9" then "No Result" 
                when "10" then "Reviewing" 
                END as Status',
                'MatchType' => 'MT.MatchTypeName AS MatchType',
                'LastUpdateDiff' => 'IF(M.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", M.LastUpdatedOn))) LastUpdateDiff',
                'isJoinedContest' => '(select count(MatchID) from sports_contest_join where MatchID = M.MatchID AND E.StatusID=' . (!is_array(@$Where['StatusID'])) ? @$Where['StatusID'] : 2 . ') as JoinedContests',
                'TotalUserWinning' => '(select SUM(UserWinningAmount) from sports_contest_join where MatchID = M.MatchID AND E.StatusID=' . (!is_array(@$Where['StatusID'])) ? @$Where['StatusID'] : 2 . ' AND UserID=' . @$Where['UserID'] . ') as TotalUserWinning',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('M.MatchGUID,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_series S, sports_matches M, sports_teams TL, sports_teams TV, sports_set_match_types MT');
        $this->db->where("M.SeriesID", "S.SeriesID", FALSE);
        $this->db->where("M.MatchID", "E.EntityID", FALSE);
        $this->db->where("M.MatchTypeID", "MT.MatchTypeID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->group_start();
            $this->db->like("S.SeriesName", $Where['Keyword']);
            $this->db->or_like("M.MatchNo", $Where['Keyword']);
            $this->db->or_like("M.MatchLocation", $Where['Keyword']);
            $this->db->or_like("TL.TeamName", $Where['Keyword']);
            $this->db->or_like("TV.TeamName", $Where['Keyword']);
            $this->db->or_like("TL.TeamNameShort", $Where['Keyword']);
            $this->db->or_like("TV.TeamNameShort", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("S.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.SeriesEndDate", $Where['SeriesEndDate']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("M.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['WeekID'])) {
            $this->db->where("M.WeekID", $Where['WeekID']);
        }
        if (!empty($Where['GameKey'])) {
            $this->db->where("M.MatchNo", $Where['GameKey']);
        }
        if (!empty($Where['ScoreID'])) {
            $this->db->where("M.ScoreIDLive", $Where['ScoreID']);
        }
        if (!empty($Where['PlayerStatsUpdate'])) {
            $this->db->where("M.PlayerStatsUpdate", $Where['PlayerStatsUpdate']);
        }
        if (!empty($Where['MatchCompleteDateTime'])) {
            $this->db->where("M.MatchCompleteDateTime <", $Where['MatchCompleteDateTime']);
        }
        if (!empty($Where['MatchTypeID'])) {
            $this->db->where("M.MatchTypeID", $Where['MatchTypeID']);
        }
        if (!empty($Where['TeamIDLocal'])) {
            $this->db->where("M.TeamIDLocal", $Where['TeamIDLocal']);
        }
        if (!empty($Where['IsPreSquad'])) {
            $this->db->where("M.IsPreSquad", $Where['IsPreSquad']);
        }
        if (!empty($Where['TeamIDVisitor'])) {
            $this->db->where("M.TeamIDVisitor", $Where['TeamIDVisitor']);
        }
        if (!empty($Where['MatchStartDateTimeEST'])) {
            $this->db->where("M.MatchStartDateTimeEST", $Where['MatchStartDateTimeEST']);
        }
        if (!empty($Where['IsPlayerPointsUpdated'])) {
            $this->db->where("M.IsPlayerPointsUpdated", $Where['IsPlayerPointsUpdated']);
        }
        if (!empty($Where['MatchStartDateTime'])) {
            $this->db->where("M.MatchStartDateTime <=", $Where['MatchStartDateTime']);
        }
        if (!empty($Where['MatchStartDateTimePrev'])) {
            $this->db->where("M.MatchStartDateTime >=", $Where['MatchStartDateTimePrev']);
            $this->db->where("M.MatchStartDateTime <=", date('Y-m-d H:i:s'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }
        if (!empty($Where['MatchCurrentDate'])) {
            $this->db->where("DATE(M.MatchStartDateTime)", $Where['MatchCurrentDate']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Yesterday') {
            $this->db->where("M.MatchStartDateTime <=", date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 14400));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'MyJoinedMatch') {
            $this->db->where('EXISTS (select 1 from sports_contest_join J where J.MatchID = M.MatchID AND J.UserID=' . $Where['UserID'] . ')');
        }
        if (!empty($Where['StatusID'])) {
            if(is_array($Where['StatusID'])){
              $this->db->where_in("E.StatusID", $Where['StatusID']);
            }else{
              $this->db->where("E.StatusID", $Where['StatusID']);
            }
        }
        if (!empty($Where['GameSportsType'])) {
            $this->db->where("E.GameSportsType", $Where['GameSportsType']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }
        if (!empty($Where['existingContests'])) {
            $StatusID = $Where['StatusID'];
            $this->db->where('EXISTS (select MatchID from sports_contest where MatchID = M.MatchID AND E.StatusID=' . $StatusID . ')');
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('E.StatusID', 'ASC');
            $this->db->order_by('M.MatchStartDateTime', 'ASC');
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
        // if ($Query->num_rows() > 0) {
        if ($multiRecords) {
            if ($Query->num_rows() > 0) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                }
                $Return['Data']['Records'] = $Records;
            }
            if (!empty($Where['MyJoinedMatchesCount']) && $Where['MyJoinedMatchesCount'] == 1) {
                $Return['Data']['Statics'] = $this->db->query('SELECT (
                            SELECT COUNT(DISTINCT M.MatchID) AS `UpcomingJoinedContest` FROM `sports_matches` M
                            JOIN `sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE E.StatusID = 1 AND J.UserID ="' . @$Where['UserID'] . '" 
                        )as UpcomingJoinedContest,
                        ( SELECT COUNT(DISTINCT M.MatchID) AS `LiveJoinedContest` FROM `sports_matches` M JOIN `sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE  E.StatusID IN (2,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                        )as LiveJoinedContest,
                        ( SELECT COUNT(DISTINCT M.MatchID) AS `CompletedJoinedContest` FROM `sports_matches` M JOIN `sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE  E.StatusID IN (5,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                    )as CompletedJoinedContest'
                        )->row();
            }
            return $Return;
        } else {
            if ($Query->num_rows() > 0) {
                $Record = $Query->row_array();
                $Record['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                return $Record;
            }
        }
        // }
        return FALSE;
    }

    /*
      Description: To get all teams
     */

    function getTeams($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TeamID' => 'T.TeamID',
                'StatusID' => 'E.StatusID',
                'TeamIDLive' => 'T.TeamIDLive',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'TeamFlag' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",T.TeamFlag) as TeamFlag',
                'Status' => 'CASE E.StatusID
                when "2" then "Active"
                when "6" then "Inactive"
                END as Status',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('T.TeamName,T.TeamGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_teams T');
        $this->db->where("T.TeamID", "E.EntityID", FALSE);

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("T.TeamName", $Where['Keyword']);
            $this->db->or_like("T.TeamNameShort", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("T.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['TeamIDLive'])) {
            $this->db->where("T.TeamIDLive", $Where['TeamIDLive']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('T.TeamName', 'ASC');

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
      Description : To Update Team Flag
     */

    function updateTeamFlag($TeamID, $Input = array()) {
        $UpdateArray = array();
        if (!empty($Input['TeamFlag'])) {
            $UpdateArray['TeamFlag'] = $Input['TeamFlag'];
        }
        if (!empty($Input['TeamName'])) {
            $UpdateArray['TeamName'] = $Input['TeamName'];
        }
        if (!empty($UpdateArray)) {
            $this->db->where('TeamID', $TeamID);
            $this->db->limit(1);
            $this->db->update('sports_teams', $UpdateArray);
        }
        return TRUE;
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
                'TeamGUID' => 'T.TeamGUID',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'PlayerID' => 'P.PlayerID',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerRole' => 'TP.PlayerRole',
                'IsPlaying' => 'TP.IsPlaying',
                'TotalPoints' => 'TP.TotalPoints',
                'PointsData' => 'TP.PointsData',
                'SeriesID' => 'TP.SeriesID',
                'MatchID' => 'TP.MatchID',
                'TeamID' => 'TP.TeamID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'PlayerSalary' => 'TP.PlayerSalary',
                'PlayerSalaryCredit' => 'TP.PlayerSalary PlayerSalaryCredit',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
                'TotalPointCredits' => '(SELECT SUM(`TotalPoints`) FROM `sports_team_players` WHERE `PlayerID` = TP.PlayerID AND `SeriesID` = TP.SeriesID) TotalPointCredits'
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
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag', 'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID', 'MatchID'))) {
            $this->db->from('sports_teams T,sports_matches M, sports_team_players TP,sports_set_match_types SSM');
            $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
            $this->db->where("TP.TeamID", "T.TeamID", FALSE);
            $this->db->where("TP.MatchID", "M.MatchID", FALSE);
            $this->db->where("M.MatchTypeID", "SSM.MatchTypeID", FALSE);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("TP.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
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
            $this->db->order_by('P.PlayerName', 'ASC');
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
        $MatchStatus = 0;
        if (!empty($Where['MatchID'])) {
            /* Get Match Status */
            $MatchQuery = $this->db->query('SELECT E.StatusID FROM `sports_matches` `M`,`tbl_entity` `E` WHERE M.`MatchID` = "' . $Where['MatchID'] . '" AND M.MatchID = E.EntityID LIMIT 1');
            $MatchStatus = ($MatchQuery->num_rows() > 0) ? $MatchQuery->row()->StatusID : 0;
        }
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerSalary'] = $Record['PlayerSalary'];
                    $Records[$key]['PointCredits'] = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];

                    if (in_array('MyTeamPlayer', $Params)) {
                        $this->db->select('SUTP.PlayerID,SUTP.MatchID');
                        $this->db->where('SUTP.MatchID', $Where['MatchID']);
                        $this->db->where('SUT.UserID', $Where['UserID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $MyPlayers = $this->db->get()->result_array();
                        if (!empty($MyPlayers)) {
                            foreach ($MyPlayers as $k => $value) {
                                if ($value['PlayerID'] == $Record['PlayerID']) {
                                    $Records[$key]['MyPlayer'] = 'Yes';
                                } else {
                                    $Records[$key]['MyPlayer'] = 'No';
                                }
                            }
                        } else {
                            $Records[$key]['MyPlayer'] = 'No';
                        }
                    }

                    if (in_array('PlayerSelectedPercent', $Params)) {
                        $TotalTeams = $this->db->query('Select count(*) as TotalTeams from sports_users_teams WHERE MatchID="' . $Where['MatchID'] . '"')->row()->TotalTeams;

                        $this->db->select('count(SUTP.PlayerID) as TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.MatchID", $Where['MatchID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = ($TotalTeams > 0 ) ? round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) : 0;
                    }

                    if (in_array('TopPlayer', $Params)) {
                        $Wicketkipper = $this->findKeyValuePlayers($Records, "WicketKeeper");
                        $Batsman = $this->findKeyValuePlayers($Records, "Batsman");
                        $Bowler = $this->findKeyValuePlayers($Records, "Bowler");
                        $Allrounder = $this->findKeyValuePlayers($Records, "AllRounder");
                        usort($Batsman, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Bowler, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Wicketkipper, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Allrounder, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });

                        $TopBatsman = array_slice($Batsman, 0, 4);
                        $TopBowler = array_slice($Bowler, 0, 3);
                        $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
                        $TopAllrounder = array_slice($Allrounder, 0, 3);

                        $AllPlayers = array();
                        $AllPlayers = array_merge($TopBatsman, $TopBowler);
                        $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
                        $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

                        rsort($AllPlayers, function($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                    }

                    if (in_array($Record['PlayerID'], array_column($AllPlayers, 'PlayerID'))) {
                        $Records[$key]['TopPlayer'] = 'Yes';
                    } else {
                        $Records[$key]['TopPlayer'] = 'No';
                    }
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerSalary'] = $Record['PlayerSalary'];
                $Record['PointCredits'] = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                if (in_array('MyTeamPlayer', $Params)) {
                    $this->db->select('SUTP.PlayerID,SUTP.MatchID');
                    $this->db->where('SUTP.MatchID', $Where['MatchID']);
                    $this->db->where('SUT.UserID', $Where['UserID']);
                    $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                    $MyPlayers = $this->db->get()->result_array();
                    foreach ($MyPlayers as $key => $value) {
                        if ($value['PlayerID'] == $Record['PlayerID']) {
                            $Records['MyPlayer'] = 'Yes';
                        } else {
                            $Records['MyPlayer'] = 'No';
                        }
                    }
                }

                if (in_array('TopPlayer', $Params)) {
                    $Wicketkipper = $this->findKeyValuePlayers($Records, "WicketKeeper");
                    $Batsman = $this->findKeyValuePlayers($Records, "Batsman");
                    $Bowler = $this->findKeyValuePlayers($Records, "Bowler");
                    $Allrounder = $this->findKeyValuePlayers($Records, "AllRounder");
                    usort($Batsman, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Bowler, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Wicketkipper, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Allrounder, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    $TopBatsman = array_slice($Batsman, 0, 4);
                    $TopBowler = array_slice($Bowler, 0, 3);
                    $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
                    $TopAllrounder = array_slice($Allrounder, 0, 3);
                    $AllPlayers = array();
                    $AllPlayers = array_merge($TopBatsman, $TopBowler);
                    $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
                    $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

                    rsort($AllPlayers, function($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                }
                if (in_array($Record['PlayerID'], array_column($AllPlayers, 'PlayerID'))) {
                    $Records['TopPlayer'] = 'Yes';
                } else {
                    $Records['TopPlayer'] = 'No';
                }

                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: Use to get sports points.
     */

    function getPoints($Where = array()) {
        switch (@$Where['PointsCategory']) {
            case 'InPlay':
                $this->db->select('PointsT20InPlay PointsT20, PointsODIInPlay PointsODI, PointsTESTInPlay PointsTEST');
                break;
            case 'Reverse':
                $this->db->select('PointsT20Reverse PointsT20, PointsODIReverse PointsODI, PointsTESTReverse PointsTEST');
                break;
            default:
                $this->db->select('PointsT20,PointsODI,PointsTEST');
                break;
        }
        $this->db->select('PointsTypeGUID,PointsTypeDescprition,PointsTypeShortDescription,PointsType,PointsInningType,PointsScoringField,StatusID');
        $this->db->from('sports_setting_points');
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        $this->db->order_by("PointsType", 'ASC');
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to update points.
     */

    function updatePoints($Input = array()) {
        if (!empty($Input)) {
            $PointsCategory = ($Input['PointsCategory'] != 'Normal') ? $Input['PointsCategory'] : '';
            for ($i = 0; $i < count($Input['PointsT20']); $i++) {
                $updateArray[] = array(
                    'PointsTypeGUID' => $Input['PointsTypeGUID'][$i],
                    'PointsT20' . $PointsCategory => $Input['PointsT20'][$i],
                    'PointsTEST' . $PointsCategory => $Input['PointsTEST'][$i],
                    'PointsODI' . $PointsCategory => $Input['PointsODI'][$i]
                );
            }

            /* Update points details to sports_setting_points table. */
            $this->db->update_batch('sports_setting_points', $updateArray, 'PointsTypeGUID');
            // $this->db->cache_delete('sports', 'getPoints'); //Delete Cache
        }
    }

    /*
      Description: Use to update player role.
     */

    function updatePlayerRole($PlayerID, $MatchID, $Input = array()) {
        if (!empty($Input)) {
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where('MatchID', $MatchID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $Input);
            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
        }
    }

    /*
      Description: Use to auction draft play.
     */

    function updateAuctionPlayStatus($SeriesID, $Input = array()) {
        if (!empty($Input)) {
            $this->db->where('SeriesID', $SeriesID);
            $this->db->limit(1);
            $this->db->update('sports_series', $Input);
        }
    }

    /*
      Description: Use to update player salary.
     */

    function updatePlayerSalary($Input = array(), $PlayerID) {
        if (!empty($Input)) {
            $UpdateData = array(
                'PlayerSalary' => json_encode(array(
                    'T20Credits' => @$Input['T20Credits'],
                    'T20iCredits' => @$Input['T20iCredits'],
                    'ODICredits' => @$Input['ODICredits'],
                    'TestCredits' => @$Input['TestCredits']
                )),
                'IsAdminSalaryUpdated' => 'Yes'
            );

            $this->db->where('PlayerID', $PlayerID);
            $this->db->limit(1);
            $this->db->update('sports_players', $UpdateData);
            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
        }
    }

    function updatePlayerSalaryMatch($Input = array(), $PlayerID, $MatchID) {
        if (!empty($Input)) {
            $UpdateData = array(
                'PlayerSalary' => $Input['PlayerSalaryCredit'],
                'IsAdminUpdate' => 'Yes'
            );
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where('MatchID', $MatchID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $UpdateData);
        }
    }

    /*
      Description: To Excecute curl request
     */

    function ExecuteCurl($Url, $Params = '') {
        $Curl = curl_init($Url);
        if (!empty($Params)) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);
        }
        curl_setopt($Curl, CURLOPT_HEADER, 0);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, TRUE);
        $Response = curl_exec($Curl);
        curl_close($Curl);
        $Result = json_decode($Response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $Response;
        } else {
            return gzdecode($Response);
        }
    }

    /*
      Description: To get access token
     */

    function getAccessToken($Sports = "") {
        $this->load->helper('file');
        $AccessToken = "";
        if (!empty($Sports)) {
            if (file_exists(SPORTS_FILE_PATH_SPORTS . $Sports . ".txt")) {
                $AccessToken = read_file(SPORTS_FILE_PATH_SPORTS . $Sports . ".txt");
            }
        } else {
            if (file_exists(SPORTS_FILE_PATH)) {
                $AccessToken = read_file(SPORTS_FILE_PATH);
            }
        }
        /* else {
          $AccessToken = $this->generateAccessToken();
          } */
        return '4115dad0c0e04ec2b5772da483a51851';
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To generate access token
     */

    function generateAccessToken() {
        /* For Sports Entity Api */
        if (SPORTS_API_NAME == 'ENTITY') {
            $Response = json_decode($this->ExecuteCurl(SPORTS_API_URL_ENTITY . '/v2/auth/', array('access_key' => SPORTS_API_ACCESS_KEY_ENTITY, 'secret_key' => SPORTS_API_SECRET_KEY_ENTITY, 'extend' => 1)), TRUE);
            if ($Response['status'] == 'ok')
                $AccessToken = $Response['response']['token'];
        }

        /* For Sports Cricket Api */
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $Response = json_decode($this->ExecuteCurl(SPORTS_API_URL_CRICKETAPI . '/rest/v2/auth/', array('access_key' => SPORTS_API_ACCESS_KEY_CRICKETAPI, 'secret_key' => SPORTS_API_SECRET_KEY_CRICKETAPI, 'app_id' => SPORTS_API_APP_ID_CRICKETAPI, 'device_id' => SPORTS_API_DEVICE_ID_CRICKETAPI)), TRUE);
            if ($Response['status'])
                $AccessToken = $Response['auth']['access_token'];
        }
        if (empty($AccessToken))
            exit;

        /* Update Access Token */
        $this->load->helper('file');
        write_file(SPORTS_FILE_PATH, $AccessToken, 'w');
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To fetch sports api data
     */

    function callSportsAPI($ApiUrl, $Sports = "") {
        $AccessToken = $this->getAccessToken($Sports);
        $Response = json_decode($this->ExecuteCurl($ApiUrl . $AccessToken), TRUE);
        if (@$Response['status'] == 'unauthorized' || @$Response['statusCode'] == 403) {
            return false;
        }
        return $Response;
    }

    /*
      Description: To set series data (FOOTBALL API NFL)
     */

    function getSeriesLiveNfl($CronID) {
        ini_set('max_execution_time', 120);
        /** get nfl series */
        $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/CurrentSeason?key=', "nfl");
        if (!$Response)
        return true;

        $SeriesIDLive = "nfl_" . $Response;
        $Query = $this->db->query("SELECT S.SeriesID FROM sports_series S,tbl_entity E WHERE S.SeriesID=E.EntityID AND S.SeriesIDLive = '" . $SeriesIDLive . "' AND E.GameSportsType='Nfl' LIMIT 1");
        $Season = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
        if (empty($Season)) {
            /* Add series to entity table and get EntityID. */
            $SeriesGUID = get_guid();
            $SeriesData[] = array_filter(array(
                'SeriesID' => $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2, "GameSportsType" => "Nfl")),
                'SeriesGUID' => $SeriesGUID,
                'SeriesIDLive' => $SeriesIDLive,
                'SeriesYear' => $Response,
                'SeriesName' => "Pro Season " . $Response,
                'AuctionDraftIsPlayed' => 'Yes'
            ));
            $this->db->insert_batch('sports_series', $SeriesData);
        }
    }

    /*
      Description: To set series data (FOOTBALL API NCAAF)
     */

    function getSeriesLiveNcaaf($CronID) {
         return true;
        ini_set('max_execution_time', 120);
        /** get nfl series */
        $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/scores/json/CurrentSeason?key=', "ncaaf");
        if (!$Response)
            return true;

        $SeriesIDLive = "ncaaf_" . $Response;
        $Query = $this->db->query("SELECT S.SeriesID FROM sports_series S,tbl_entity E WHERE S.SeriesID=E.EntityID AND S.SeriesIDLive = '" . $SeriesIDLive . "' AND E.GameSportsType='Ncaaf' LIMIT 1");
        $Season = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
        if (empty($Season)) {
            /* Add series to entity table and get EntityID. */
            $SeriesGUID = get_guid();
            $SeriesData[] = array_filter(array(
                'SeriesID' => $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2, "GameSportsType" => "Ncaaf")),
                'SeriesGUID' => $SeriesGUID,
                'SeriesIDLive' => $SeriesIDLive,
                'SeriesYear' => $Response,
                'SeriesName' => "College Football Season " . $Response,
                'AuctionDraftIsPlayed' => 'Yes'
            ));
            $this->db->insert_batch('sports_series', $SeriesData);
        }
    }

    /*
      Description: To set team data (FOOTBALL API NFL)
     */

    function getTeamsLiveNfl($CronID) {
        ini_set('max_execution_time', 120);

        /** NFL data get * */
        $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/Teams?key=', 'nfl');
        if (empty($Response)) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        //dump($Response);
        $TeamData = array();
        foreach ($Response as $Value) {
            /* To check if local team is already exist */
            $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = " . $Value['GlobalTeamID'] . " AND T.TeamKey='" . $Value['Key'] . "' AND E.GameSportsType='Nfl' LIMIT 1");
            $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
            if (!$TeamID) {
                /* Add team to entity table and get EntityID. */
                $TeamGUID = get_guid();
                $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Nfl"));
                $TeamData[] = array(
                    'TeamID' => $TeamID,
                    'TeamGUID' => $TeamGUID,
                    'TeamIDLive' => $Value['GlobalTeamID'],
                    'TeamKey' => $Value['Key'],
                    'TeamName' => $Value['FullName'],
                    'TeamNameShort' => (!empty($Value['Name'])) ? $Value['Name'] : null,
                    'TeamFlag' => (!empty($Value['WikipediaLogoUrl'])) ? $Value['WikipediaLogoUrl'] : base_url() . SPORTS_TEAM_LOGO,
                );
            } else {
                $TeamUpdate = array(
                    'TeamName' => $Value['FullName'],
                    'TeamNameShort' => (!empty($Value['Name'])) ? $Value['Name'] : null,
                    'TeamFlag' => (!empty($Value['WikipediaLogoUrl'])) ? $Value['WikipediaLogoUrl'] : base_url() . SPORTS_TEAM_LOGO,
                );
                $this->db->where('TeamIDLive', $Value['GlobalTeamID']);
                $this->db->where('TeamKey', $Value['Key']);
                $this->db->limit(1);
                $this->db->update('sports_teams', $TeamUpdate);
            }
        }
        if (!empty($TeamData)) {
            $this->db->insert_batch('sports_teams', $TeamData);
        }
    }

    /*
      Description: To set team data (FOOTBALL API NCAAF)
     */

    function getTeamsLiveNcaaf($CronID) {
        return true;
        ini_set('max_execution_time', 120);

        /** NFL data get * */
        $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/scores/json/LeagueHierarchy?key=', 'ncaaf');
        if (empty($Response)) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        $TeamData = array();
        foreach ($Response as $Value) {
            $ConferenceID = $Value['ConferenceID'];
            $ConferenceName = $Value['Name'];
            $Teams = $Value['Teams'];
            if (!empty($Teams)) {
                foreach ($Teams as $Team) {
                    /* To check if local team is already exist */
                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = " . $Team['GlobalTeamID'] . " AND T.TeamKey='" . $Team['Key'] . "' AND E.GameSportsType='Ncaaf' LIMIT 1");
                    $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                    if (!$TeamID) {
                        /* Add team to entity table and get EntityID. */
                        $TeamGUID = get_guid();
                        $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Ncaaf"));
                        $TeamData[] = array(
                            'TeamID' => $TeamID,
                            'TeamGUID' => $TeamGUID,
                            'TeamIDLive' => $Team['GlobalTeamID'],
                            'TeamKey' => $Team['Key'],
                            'TeamName' => $Team['School'],
                            'TeamNameShort' => (!empty($Team['Name'])) ? $Team['Name'] : null,
                            'TeamFlag' => (!empty($Team['TeamLogoUrl'])) ? $Team['TeamLogoUrl'] : base_url() . SPORTS_TEAM_LOGO,
                            //'TeamStats' => json_encode($Team),
                            'ConferenceID' => $Team['ConferenceID'],
                            'ConferenceName' => $Team['Conference'],
                            'IsCollegePlaying' => "Yes"
                        );
                    } else {
                        $TeamUpdate = array(
                            'TeamName' => $Team['School'],
                            'TeamNameShort' => (!empty($Team['Name'])) ? $Team['Name'] : null,
                            'TeamFlag' => (!empty($Team['TeamLogoUrl'])) ? $Team['TeamLogoUrl'] : base_url() . SPORTS_TEAM_LOGO,
                            //'TeamStats' => json_encode($Team),
                            'ConferenceID' => $Team['ConferenceID'],
                            'ConferenceName' => $Team['Conference'],
                            'IsCollegePlaying' => "Yes"
                        );
                        $this->db->where('TeamIDLive', $Team['GlobalTeamID']);
                        $this->db->where('TeamKey', $Team['Key']);
                        $this->db->limit(1);
                        $this->db->update('sports_teams', $TeamUpdate);
                    }
                }
            }
        }
        if (!empty($TeamData)) {
            $this->db->insert_batch('sports_teams', $TeamData);
        }
    }

    /*
      Description: To set matches data (FOOTBALL API NFL)
     */

    function getMatchesLiveNfl($CronID) {
        ini_set('max_execution_time', 120);

        /* Get series data */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesYear', array('StatusID' => 2, 'GameSportsType' => 'Nfl'), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        $MatchTypesData = $this->getMatchTypes();
        $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeName');
        /* Get Live Matches Data */
        $MatchGroup = array();
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {

            $MatchGroup['ProFootballRegularSeasonOwners'] = $SeriesValue['SeriesYear'];
            $MatchGroup['ProFootballPlayoffs'] = $SeriesValue['SeriesYear'] . "POST";

            foreach ($MatchGroup as $MatchKey => $Group) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/Schedules/' . $Group . '?key=', 'nfl');
                if (empty($Response))
                    continue;

                $AllTeamsByWeek = array();
                foreach ($Response as $key => $Value) {

                    /* $this->db->trans_start(); */

                    /* Managae Teams */
                    $Date = new DateTime($Value['DateTime']);
                    $GameKey = $Value['GameKey'];
                    $GlobalGameID = $Value['GlobalGameID'];
                    $SeasonType = $MatchKey;
                    $Week = $Value['Week'];
                    $ScoreID = $Value['ScoreID'];
                    $DateTime = $Date->format('Y-m-d H:i:s');
                    $LocalTeamKey = $Value['HomeTeam'];
                    $VisitorTeamKey = $Value['AwayTeam'];
                    $GlobalAwayTeamID = $Value['GlobalAwayTeamID'];
                    $GlobalHomeTeamID = $Value['GlobalHomeTeamID'];
                    $LocalTeamData = $VisitorTeamData = array();

                    /* To check if local team is already exist */
                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = '" . $GlobalHomeTeamID . "' AND T.TeamKey = '" . $LocalTeamKey . "' AND E.GameSportsType='Nfl' LIMIT 1");
                    $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;

                    /* To check if visitor team is already exist */
                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = '" . $GlobalAwayTeamID . "' AND T.TeamKey = '" . $VisitorTeamKey . "' AND E.GameSportsType='Nfl' LIMIT 1");
                    $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;

                    if (!empty($TeamIDLocal) && !empty($TeamIDVisitor)) {
                        /* To check if match is already exist */
                        $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $GlobalGameID . ' AND E.GameSportsType="Nfl" LIMIT 1');
                        $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                        if (!$MatchID) {
                            /* Add matches to entity table and get EntityID. */
                            if (empty($GlobalGameID))
                                continue;

                            $MatchGUID = get_guid();
                            $MatchID = $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1, "GameSportsType" => "Nfl"));
                            $MatchesAPIData = array(
                                'MatchID' => $MatchID,
                                'MatchGUID' => $MatchGUID,
                                'MatchIDLive' => $GlobalGameID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'MatchTypeID' => $MatchTypeIdsData['Football NFL'],
                                'MatchNo' => $GameKey,
                                'MatchLocation' => json_encode($Value['StadiumDetails']),
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => $DateTime,
                                'WeekID' => $Week,
                                'ScoreIDLive' => $ScoreID,
                                'SeasonType' => $SeasonType
                            );
                            $this->db->insert('sports_matches', $MatchesAPIData);

                            /* add matches wise team manage score and drafting */
                            $MatchesData = array();
                            $MatchesData[] = array(
                                'MatchID' => $MatchID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'TeamID' => $TeamIDLocal
                            );
                            $MatchesData[] = array(
                                'MatchID' => $MatchID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'TeamID' => $TeamIDVisitor
                            );
                            $this->db->insert_batch('sports_team_players', $MatchesData);
                        } else {
                            if ($Query->row()->StatusID != 1)
                                continue; // Pending Match

                                /* Update Match Data */
                            $MatchesAPIData = array(
                                'MatchLocation' => json_encode($Value['StadiumDetails']),
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => $DateTime,
                                'LastUpdatedOn' => date('Y-m-d H:i:s'),
                                'SeasonType' => $SeasonType
                            );
                            $this->db->where('MatchID', $MatchID);
                            $this->db->limit(1);
                            $this->db->update('sports_matches', $MatchesAPIData);

                            $MatchTeamDataLocal = array();
                            $MatchTeamDataVisitor = array();
                            /** local team data **/
                            $Query = $this->db->query('SELECT P.PlayerID,P.PlayerRole FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $TeamIDLocal . ' AND E.GameSportsType="Nfl"');
                            $Players = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
                            if(!empty($Players)){
                                foreach($Players as $Player){
                                    $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDLocal.'');
                                    if($Query->num_rows() <= 0){
                                        $MatchTeamDataLocal[] = array(
                                            'PlayerID' => $Player['PlayerID'],
                                            'PlayerRole' => $Player['PlayerRole'],
                                            'MatchID' => $MatchID,
                                            'SeriesID' => $SeriesValue['SeriesID'],
                                            'TeamID' => $TeamIDLocal,
                                            'WeekID' => $Week
                                        );
                                    }
                                }
                                if(!empty($MatchTeamDataLocal)){
                                   $this->db->insert_batch('sports_team_players', $MatchTeamDataLocal);  
                                }
                               
                            }

                            /** visitor team data **/
                            $Query = $this->db->query('SELECT P.PlayerID,P.PlayerRole  FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $TeamIDVisitor . ' AND E.GameSportsType="Nfl"');
                            $Players = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;

                            if(!empty($Players)){
                                foreach($Players as $Player){
                                    $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDVisitor.'');
                                    if($Query->num_rows() <= 0){
                                        $MatchTeamDataVisitor[] = array(
                                            'PlayerID' => $Player['PlayerID'],
                                            'PlayerRole' => $Player['PlayerRole'],
                                            'MatchID' => $MatchID,
                                            'SeriesID' => $SeriesValue['SeriesID'],
                                            'TeamID' => $TeamIDVisitor,
                                            'WeekID' => $Week
                                        );
                                    }
                                }
                                if(!empty($MatchTeamDataVisitor)){
                                  $this->db->insert_batch('sports_team_players', $MatchTeamDataVisitor);  
                                }
                                
                            }

                            // $MatchesData = array(
                            //     'WeekID' => $Week
                            // );
                            // $this->db->where('MatchID', $MatchID);
                            // $this->db->where('SeriesID', $SeriesValue['SeriesID']);
                            // $this->db->update('sports_team_players', $MatchesData);
                        }
                    }

                    /* $this->db->trans_complete();
                      if ($this->db->trans_status() === false) {
                      return false;
                      } */
                }
            }
        }

        $this->getByeWeek();
    }



    /*
      Description: To set matches data (FOOTBALL API NCAAF)
     */

    function getMatchesLiveNcaaf($CronID) {
         return true;
        ini_set('max_execution_time', 120);

        /* Get series data */

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesYear', array('StatusID' => 2, 'GameSportsType' => 'Ncaaf'), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        $MatchTypesData = $this->getMatchTypes();
        $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeName');
        $MatchGroup = array();
        /* Get Live Matches Data */
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {

            $MatchGroup['CollegeFootballRegularSeason'] = $SeriesValue['SeriesYear'];
            $MatchGroup['CollegeFootballPower5RegularSeason'] = $SeriesValue['SeriesYear'] . "POST";

            foreach ($MatchGroup as $MatchKey => $Group) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/scores/json/Games/' . $Group . '?key=', 'ncaaf');
                if (empty($Response))
                    continue;

                foreach ($Response as $key => $Value) {

                    /* $this->db->trans_start(); */

                    /* Managae Teams */
                    $Date = new DateTime($Value['DateTime']);
                    $GlobalGameID = $Value['GlobalGameID'];
                    $SeasonType = $MatchKey;
                    $Week = $Value['Week'];
                    $ScoreID = $Value['GameID'];
                    $DateTime = $Date->format('Y-m-d H:i:s');
                    $LocalTeamKey = $Value['HomeTeam'];
                    $VisitorTeamKey = $Value['AwayTeam'];
                    $GlobalAwayTeamID = $Value['GlobalAwayTeamID'];
                    $GlobalHomeTeamID = $Value['GlobalHomeTeamID'];
                    $LocalTeamData = $VisitorTeamData = array();

                    /* To check if local team is already exist */
                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = '" . $GlobalHomeTeamID . "' AND T.TeamKey = '" . $LocalTeamKey . "' AND E.GameSportsType='Ncaaf' LIMIT 1");
                    $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                    if (!$TeamIDLocal) {
                        /* Add team to entity table and get EntityID. */
                        $TeamGUID = get_guid();
                        $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Ncaaf"));
                        $LocalTeamData[] = array(
                            'TeamID' => $TeamIDLocal,
                            'TeamGUID' => $TeamGUID,
                            'TeamIDLive' => $GlobalHomeTeamID,
                            'TeamKey' => $LocalTeamKey,
                            'TeamName' => $Value['HomeTeamName'],
                            'TeamFlag' => base_url() . SPORTS_TEAM_LOGO
                        );
                    }

                    /* To check if visitor team is already exist */
                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = '" . $GlobalAwayTeamID . "' AND T.TeamKey = '" . $VisitorTeamKey . "' AND E.GameSportsType='Ncaaf' LIMIT 1");
                    $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                    if (!$TeamIDVisitor) {
                        /* Add team to entity table and get EntityID. */
                        $TeamGUID = get_guid();
                        $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Ncaaf"));
                        $VisitorTeamData[] = array(
                            'TeamID' => $TeamIDVisitor,
                            'TeamGUID' => $TeamGUID,
                            'TeamIDLive' => $GlobalAwayTeamID,
                            'TeamKey' => $VisitorTeamKey,
                            'TeamName' => $Value['AwayTeamName'],
                            'TeamFlag' => base_url() . SPORTS_TEAM_LOGO
                        );
                    }
                    $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);

                    if (!empty($TeamsData)) {
                        $this->db->insert_batch('sports_teams', $TeamsData);
                    }

                    if (!empty($TeamIDLocal) && !empty($TeamIDVisitor)) {
                        /* To check if match is already exist */
                        $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $GlobalGameID . ' AND E.GameSportsType="Ncaaf" LIMIT 1');
                        $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                        if (!$MatchID) {
                            /* Add matches to entity table and get EntityID. */
                            if (empty($GlobalGameID))
                                continue;

                            $MatchGUID = get_guid();
                            $MatchID = $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1, "GameSportsType" => "Ncaaf"));
                            $MatchesAPIData = array(
                                'MatchID' => $MatchID,
                                'MatchGUID' => $MatchGUID,
                                'MatchIDLive' => $GlobalGameID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'MatchTypeID' => $MatchTypeIdsData['Football NCAAF'],
                                'MatchLocation' => json_encode($Value['Stadium']),
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => $DateTime,
                                'WeekID' => $Week,
                                'ScoreIDLive' => $ScoreID,
                                'SeasonType' => $SeasonType
                            );
                            $this->db->insert('sports_matches', $MatchesAPIData);

                            /* add matches wise team manage score and drafting */
                            $MatchesData = array();
                            $MatchesData[] = array(
                                'MatchID' => $MatchID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'TeamID' => $TeamIDLocal
                            );
                            $MatchesData[] = array(
                                'MatchID' => $MatchID,
                                'SeriesID' => $SeriesValue['SeriesID'],
                                'TeamID' => $TeamIDVisitor
                            );
                            //$this->db->insert_batch('sports_team_players', $MatchesData);
                        } else {
                            if ($Query->row()->StatusID != 1)
                                continue; // Pending Match

                                /* Update Match Data */
                            $MatchesAPIData = array(
                                'MatchLocation' => json_encode($Value['Stadium']),
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => $DateTime,
                                'LastUpdatedOn' => date('Y-m-d H:i:s'),
                                'SeasonType' => $SeasonType
                            );
                            $this->db->where('MatchID', $MatchID);
                            $this->db->limit(1);
                            $this->db->update('sports_matches', $MatchesAPIData);

                            $MatchTeamDataLocal = array();
                            $MatchTeamDataVisitor = array();
                            /** local team data **/
                            $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $$TeamIDLocal . ' AND E.GameSportsType="Nfl"');
                            $Players = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                            if(!empty($Players)){
                                foreach($Players as $Player){
                                    $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDLocal.'');
                                    if($Query->num_rows() <= 0){
                                        $MatchTeamDataLocal[] = array(
                                            'PlayerID' => $Player['PlayerID'],
                                            'PlayerRole' => $Player['PlayerRole'],
                                            'MatchID' => $MatchID,
                                            'SeriesID' => $SeriesValue['SeriesID'],
                                            'TeamID' => $TeamIDLocal,
                                            'WeekID' => $Week
                                        );
                                    }
                                }
                                $this->db->insert_batch('sports_team_players', $MatchTeamDataLocal);
                            }

                            /** visitor team data **/
                            $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $$TeamIDVisitor . ' AND E.GameSportsType="Nfl"');
                            $Players = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;

                            if(!empty($Players)){
                                foreach($Players as $Player){
                                    $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDVisitor.'');
                                    if($Query->num_rows() <= 0){
                                        $MatchTeamDataVisitor[] = array(
                                            'PlayerID' => $Player['PlayerID'],
                                            'PlayerRole' => $Player['PlayerRole'],
                                            'MatchID' => $MatchID,
                                            'SeriesID' => $SeriesValue['SeriesID'],
                                            'TeamID' => $TeamIDVisitor,
                                            'WeekID' => $Week
                                        );
                                    }
                                }
                                $this->db->insert_batch('sports_team_players', $MatchTeamDataVisitor);
                            }
                        }
                    }


                    /* $this->db->trans_complete();
                      if ($this->db->trans_status() === false) {
                      return false;
                      } */
                }
            }
        }
    }

    /*
      Description: To bye week team team (FOOTBALL API NFL)
     */

    function getByeWeek() {

        /** NFL * */
        $NflTeams = $this->SnakeDrafts_model->getDraftTeams("TeamName,GameSportsType", array("GameType" => "Nfl"), TRUE);
        $AllTeamsByWeek = array();
        foreach ($NflTeams['Data']['Records'] as $Rows) {
            for ($i = 1; $i <= 15; $i++) {
                $Query = $this->db->query('SELECT M.MatchID FROM sports_matches M,tbl_entity E WHERE M.WeekID=' . $i . ' AND '
                        . 'M.MatchID = E.EntityID AND E.GameSportsType="Nfl" AND (M.TeamIDLocal=' . $Rows['TeamID'] . ' OR M.TeamIDVisitor=' . $Rows['TeamID'] . ') LIMIT 1');
                if (empty($Query->row()->MatchID)) {
                    $this->db->where('TeamID', $Rows['TeamID']);
                    $this->db->limit(1);
                    $this->db->update('sports_teams', array('ByeWeek' => $i));
                }
            }
        }

        /** NCAAF * */
        /* $NflTeams = $this->SnakeDrafts_model->getDraftTeams("TeamName,GameSportsType,ByeWeek", array("GameType" => "Ncaaf"), TRUE);
          $AllTeamsByWeek = array();
          foreach ($NflTeams['Data']['Records'] as $Rows) {
          for ($i = 1; $i <= 17; $i++) {
          $Query = $this->db->query('SELECT M.MatchID FROM sports_matches M,tbl_entity E WHERE M.WeekID=' . $i . ' AND '
          . 'M.MatchID = E.EntityID AND E.GameSportsType="Ncaaf" AND (M.TeamIDLocal=' . $Rows['TeamID'] . ' OR M.TeamIDVisitor=' . $Rows['TeamID'] . ') LIMIT 1');
          if (empty($Query->row()->MatchID)) {
          $this->db->where('TeamID', $Rows['TeamID']);
          $this->db->limit(1);
          $this->db->update('sports_teams', array('ByeWeek' => $i));
          }
          }
          } */
    }

        /*
      Description: To set players data by team (FOOTBALL API NFL)
     */

    function getPlayersLiveByTeamNfl($CronID) {
        ini_set('max_execution_time', 180);

        /* Get team data */
        $Query = $this->db->query('SELECT T.TeamID,T.TeamIDLive,T.TeamKey FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType="Nfl"');
        $Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
        if (!$Teams) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($Teams as $Team) {
            $TeamIDLive = $Team['TeamIDLive'];
            $TeamID = $Team['TeamID'];
            $TeamKey = $Team['TeamKey'];
            $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/Players/' . $TeamKey . '?key=', 'nfl');
            if (empty($Response))
                continue;

            $PlayersAPIData = array();
            foreach ($Response as $Key => $Value) {

                $PlayerIDLive = $Value['PlayerID'];
                $Team = $Value['Team'];
                $PlayerName = $Value['FirstName'] . " " . $Value['FirstName'];
                $PlayerLiveRole = $Value['Position'];
                $Status = $Value['Status'];
                $PositionCategory = $Value['PositionCategory'];
                $PlayerRole = "";

                /* player role system */
                $Roles = array('QB' => "QuarterBack", 'RB' => "RunningBack", 'FB' => "FullBack", 'WR' => "WideReceiver", 'TE' => "TightEnd",
                    'C' => "Center", 'G' => "Guard", 'OT' => "OffenseTackle", 'DE' => "DefenseEnd", 'DT' => "DefenseTackle", 'LB' => "LineBacker",
                    'CB' => "CornerBack", 'S' => "Safety", 'SS' => "Safety", 'PK' => "Placekicker", 'LS' => "LongSnapper",
                    'P' => "Punter", 'NT' => "DefenseEnd", 'OG' => "OffensiveGuard", 'OLB' => "OutsideLinebacker", 'K' => "Kicker");
                $PlayerRole = $Roles[strtoupper($PlayerLiveRole)];

                /* player posotion category */
                $Category = array("OFF" => "Offense", "DEF" => "Defense", "ST" => "Special Teams");

                $PlayerPositionCategory = $Category[$PositionCategory];
                /* check player role */
                if (!$PlayerRole)
                    continue;

                /* To check if player is already exist */
                $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.PlayerIDLive = ' . $PlayerIDLive . ' AND E.GameSportsType="Nfl" LIMIT 1');
                $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                if ($PlayerID)
                    continue;

                /* Add players to entity table and get EntityID. */
                $PlayerGUID = get_guid();
                $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2, "GameSportsType" => "Nfl"));
                $PlayersAPIData[] = array(
                    'TeamID' => $TeamID,
                    'PlayerID' => $PlayerID,
                    'PlayerGUID' => $PlayerGUID,
                    'PlayerIDLive' => $PlayerIDLive,
                    'PlayerName' => $PlayerName,
                    'PlayerRole' => $PlayerRole,
                    'Position' => $PlayerPositionCategory,
                    'PlayerPic' => $Value['PhotoUrl'],
                    'PlayerBattingStats' => json_encode($Value)
                );
            }
            if (!empty($PlayersAPIData)) {
                $this->db->insert_batch('sports_players', $PlayersAPIData);
            }
        }
    }

    /*
      Description: To get matches live score(FOOTBALL API NFL)
     */

    function getMatchesScoreLiveNfl($CronID) {
        ini_set('max_execution_time', 120);
        /* Get series data */

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesYear,GameSportsType,CurrentWeek', array('StatusID' => 2, "GameSportsType" => "Nfl"), true, 0);
        if (!$SeriesData) {
            exit;
        }
        $MatchGroup = array();
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            $MatchGroup['ProFootballRegularSeasonOwners'] = $SeriesValue['SeriesYear'];
            $MatchGroup['ProFootballPlayoffs'] = $SeriesValue['SeriesYear'] . "POST";
            foreach ($MatchGroup as $Group) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/GameStatsByWeek/' . $Group . '/' . $SeriesValue['CurrentWeek'] . '?key=', 'nfl');
                
                if (!$Response) continue;

                foreach ($Response as $Rows) {
                    $GameKey = $Rows['GameKey'];
                    $ScoreID = $Rows['ScoreID'];
                    $MatchesData = $this->getMatches('MatchID,ScoreIDLive,MatchIDLive,SeriesID,WeekID,SeasonType,MatchNo,TeamIDLocal,TeamIDVisitor,TeamIDLiveLocal,TeamIDLiveVisitor', array("GameSportsType" => "Nfl", "SeriesID" => $SeriesValue['SeriesID'], "GameKey" => $GameKey, "ScoreID" => $ScoreID), false, 0);

                    $PlayersData = $this->Sports_football_model->getPlayers("PlayerID,PlayerIDLive,MatchID", array('MatchID'=>$MatchesData['MatchID'],'SeriesID'=>$SeriesValue['SeriesID']), TRUE,1,300);

                    if($PlayersData['Data']['TotalRecords'] <= 0) continue;

                    $AllPLayers = array_column( $PlayersData['Data']['Records'], 'PlayerID','PlayerIDLive');
      
                    if (!$MatchesData) continue;

                    $MatchesAPIData = array(
                        'MatchScoreDetails' => json_encode($Rows),
                        'LastUpdatedOn' => date('Y-m-d H:i:s'),
                    );
                    $this->db->where('MatchID', $MatchesData['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', $MatchesAPIData);

                    $ResponsePlayers = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/stats/json/BoxScoreByScoreIDV3/' . $ScoreID . '?key=', 'nfl');

                    if(!empty($ResponsePlayers['PlayerGames'])){
                        foreach($ResponsePlayers['PlayerGames'] as $PlayerValue){

                            $MatchesAPIData = array(
                                'PointsData' => json_encode($PlayerValue)
                            );
                            $this->db->where('PlayerID', $AllPLayers[$PlayerValue['PlayerID']]);
                            $this->db->where('MatchID', $MatchesData['MatchID']);
                            $this->db->limit(1);
                            $this->db->update('sports_team_players', $MatchesAPIData);
                        }
                    }

                    /** calculate match team points * */
                    //$this->calculateMatchTeamPointsNfl($MatchesData['MatchID'], $Rows);

                    if ($Rows['HasStarted'] && !$Rows['IsGameOver']) {
                        /** get current week contest * */
                        $this->db->select("C.ContestID");
                        $this->db->from('sports_contest C, tbl_entity E');
                        $this->db->where("C.ContestID", "E.EntityID", FALSE);
                        $this->db->where("C.GameType", "Nfl");
                        $this->db->where("C.WeekStart", $SeriesValue['CurrentWeek']);
                        $this->db->where("C.SeriesID", $SeriesValue['SeriesID']);
                        $this->db->where("E.StatusID", 1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Contests = $Query->result_array();
                            foreach ($Contests as $Contest) {
                                $this->db->where('EntityID', $Contest['ContestID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('StatusID' => 2));
                            }
                        } 
                    }
                    if($Rows['IsGameOver'] && !$Rows['Canceled'] && !$Rows['IsInProgress']) {
                        $this->db->where('EntityID', $MatchesData['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('StatusID' => 5));
                    }else if($Rows['Canceled'] && !$Rows['IsInProgress']){
                        $this->db->where('EntityID', $MatchesData['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('StatusID' => 3));
                    }else if(!$Rows['Canceled'] && $Rows['IsInProgress']){
                        $this->db->where('EntityID', $MatchesData['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('StatusID' => 2));
                    }
                }
            }
        }
    }

    /*
      Description: To get matches live score(FOOTBALL API NFL)
     */
    function getMatchesScoreLiveNflGoalServeByDate($CronID) {

        $Dates = array(date('Y-m-d', strtotime('-5 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('-4 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('-3 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('-2 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d')))),date('Y-m-d'));
        // $Dates = array();
        // for($i=1;$i<=31;$i++){
        //     $Dates[] = '2020-09-'.$i;
        // }
            foreach($Dates as $Date){
                $url = SPORTS_API_URL_GOALSERVE . '/football/nfl-scores?date='.date('d.m.Y',strtotime($Date));
                $xml = simplexml_load_string(file_get_contents($url));
                if (!empty($xml)) {
                    for ($i = 0; $i < count($xml->category->match); $i++) {
                        $MatchIDLive = (int) $xml->category->match[$i]['contestID'];
                        $MatchStatus = (string) $xml->category->match[$i]['status'];
                            $options = array(
                                'table' => 'sports_matches',
                                'select' => 'MatchID,MatchIDLive,WeekID,MatchStartDateTimeEST',
                                'where' => array(
                                            'MatchIDLive' => $MatchIDLive,
                                        ),
                                'single'=> true
                                );
                            $MatchData = $this->customGet($options);
                            if(!empty($MatchData)){
                                $MatchID = $MatchData->MatchID;
                                $WeekID =  $MatchData->WeekID;
                                $MatchStartDateTimeEST =  $MatchData->MatchStartDateTimeEST;

                                $options = array(
                                    'table' => 'tbl_entity',
                                    'select' => 'StatusID',
                                    'where' => array(
                                                'EntityID' => $MatchID,'StatusID'=> 5
                                            ),
                                    'single'=> true
                                    );
                                $StatusData = $this->customGet($options);
                                if(empty($StatusData)){

                                $PlayersScore = array();
                                $TeamScore = array();
                                if (strtolower($MatchStatus) != "postponed" || strtolower($MatchStatus) != "not started") {

                                    $localteam_id = (int) $xml->category->match[$i]->hometeam['id'];
                                    $visitorteam_id = (int) $xml->category->match[$i]->awayteam['id'];
                                    $passingLocalArrayCount = count($xml->category->match[$i]->passing->hometeam->player);
                                    $passingVisitorArrayCount = count($xml->category->match[$i]->passing->awayteam->player);
                                    $passPlayers = array();
                                    $rushingPlayers = array();
                                    $receivingPlayers = array();
                                    $fumblesPlayers = array();
                                    $interceptionsPlayer = array();
                                    $defensivePlayers = array();
                                    $kick_returnsPlayers = array();
                                    $punt_returnsPlayer = array();
                                    $kickingPlayer = array();
                                    $puntingPlayers = array();

                                    for ($j = 0; $j < $passingLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['id'];

                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->passing->hometeam->player[$j]['name']);
                                        $passPlayers['name'] = $playername ;

                                        $passPlayers['comp_att'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['comp_att'];
                                        $passPlayers['yards'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['yards'];
                                        $passPlayers['average'] = (float) $xml->category->match[$i]->passing->hometeam->player[$j]['average'];
                                        $passPlayers['passing_touch_downs'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['passing_touch_downs'];
                                        $passPlayers['interceptions'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['interceptions'];
                                        $passPlayers['sacks'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['sacks'];
                                        $passPlayers['rating'] = (float) $xml->category->match[$i]->passing->hometeam->player[$j]['rating'];
                                        $passPlayers['two_pt'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['two_pt'];

                                        $passPlayersData = json_encode($passPlayers);
                                        $PlayersScore[$player_id]['passing'] = $passPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, $passPlayersData);
                                    }

                                    for ($j = 0; $j < $passingVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->passing->awayteam->player[$j]['name']);
                                        $passPlayers['name'] = $playername;
                                        $passPlayers['comp_att'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['comp_att'];
                                        $passPlayers['yards'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['yards'];
                                        $passPlayers['average'] = (float) $xml->category->match[$i]->passing->awayteam->player[$j]['average'];
                                        $passPlayers['passing_touch_downs'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['passing_touch_downs'];
                                        $passPlayers['interceptions'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['interceptions'];
                                        $passPlayers['sacks'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['sacks'];
                                        $passPlayers['rating'] = (float) $xml->category->match[$i]->passing->awayteam->player[$j]['rating'];
                                        $passPlayers['two_pt'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['two_pt'];

                                        $passPlayersData = json_encode($passPlayers);
                                        $PlayersScore[$player_id]['passing'] = $passPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, $passPlayersData);
                                    }

                                    $rushingLocalArrayCount = count($xml->category->match[$i]->rushing->hometeam->player);
                                    $rushingVisitorArrayCount = count($xml->category->match[$i]->rushing->awayteam->player);
                                    for ($j = 0; $j < $rushingLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['name']);
                                        $rushingPlayers['name'] =  $playername ;

                                        $rushingPlayers['total_rushes'] = (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['total_rushes'];
                                        $rushingPlayers['yards'] = (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['yards'];
                                        $rushingPlayers['average'] = (float) $xml->category->match[$i]->rushing->hometeam->player[$j]['average'];
                                        $rushingPlayers['rushing_touch_downs'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['rushing_touch_downs'];
                                        $rushingPlayers['longest_rush'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['longest_rush'];
                                        $rushingPlayers['two_pt'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['two_pt'];

                                        $rushPlayersData = json_encode($rushingPlayers);
                                        $PlayersScore[$player_id]['russing'] = $rushingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", $rushPlayersData);
                                    }
                                    for ($j = 0; $j < $rushingVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['name']);
                                        $rushingPlayers['name'] = $playername  ;
                                        $rushingPlayers['total_rushes'] = (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['total_rushes'];
                                        $rushingPlayers['yards'] = (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['yards'];
                                        $rushingPlayers['average'] = (float) $xml->category->match[$i]->rushing->awayteam->player[$j]['average'];
                                        $rushingPlayers['rushing_touch_downs'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['rushing_touch_downs'];
                                        $rushingPlayers['longest_rush'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['longest_rush'];
                                        $rushingPlayers['two_pt'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['two_pt'];

                                        $rushPlayersData = json_encode($rushingPlayers);
                                        $PlayersScore[$player_id]['russing'] = $rushingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", $rushPlayersData);
                                    }

                                    $receivingLocalArrayCount = count($xml->category->match[$i]->receiving->hometeam->player);
                                    $receivingVisitorArrayCount = count($xml->category->match[$i]->receiving->awayteam->player);

                                    for ($j = 0; $j < $receivingLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['name']);
                                        $receivingPlayers['name'] =  $playername ;
                                        $receivingPlayers['total_receptions'] = (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['total_receptions'];
                                        $receivingPlayers['yards'] = (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['yards'];
                                        $receivingPlayers['average'] = (float) $xml->category->match[$i]->receiving->hometeam->player[$j]['average'];
                                        $receivingPlayers['receiving_touch_downs'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['receiving_touch_downs'];
                                        $receivingPlayers['longest_reception'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['longest_reception'];
                                        $receivingPlayers['two_pt'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['two_pt'];

                                        $receivePlayersData = json_encode($receivingPlayers);
                                        $PlayersScore[$player_id]['receiving'] = $receivingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", $receivePlayersData);
                                    }
                                    for ($j = 0; $j < $receivingVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['name']);
                                        $receivingPlayers['name'] =  $playername;
                                        $receivingPlayers['total_receptions'] = (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['total_receptions'];
                                        $receivingPlayers['yards'] = (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['yards'];
                                        $receivingPlayers['average'] = (float) $xml->category->match[$i]->receiving->awayteam->player[$j]['average'];
                                        $receivingPlayers['receiving_touch_downs'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['receiving_touch_downs'];
                                        $receivingPlayers['longest_reception'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['longest_reception'];
                                        $receivingPlayers['two_pt'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['two_pt'];

                                        $receivePlayersData = json_encode($receivingPlayers);
                                        $PlayersScore[$player_id]['receiving'] = $receivingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", $receivePlayersData);
                                    }

                                    $fumblesLocalArrayCount = count($xml->category->match[$i]->fumbles->hometeam->player);
                                    $fumblesVisitorArrayCount = count($xml->category->match[$i]->fumbles->awayteam->player);

                                    for ($j = 0; $j < $fumblesLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->fumbles->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['name']);
                                        $fumblesPlayers['name'] =  $playername;
                                        $fumblesPlayers['total'] = (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['total'];
                                        $fumblesPlayers['lost'] = (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['lost'];
                                        $fumblesPlayers['rec'] = (float) $xml->category->match[$i]->fumbles->hometeam->player[$j]['rec'];
                                        $fumblesPlayers['rec_td'] = (int) $xml->category->match[$i]->fumbles->hometeam->player[$j]['rec_td'];

                                        $fumblePlayersData = json_encode($fumblesPlayers);
                                        $PlayersScore[$player_id]['fumbles'] = $fumblesPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", $fumblePlayersData);
                                    }

                                    for ($j = 0; $j < $fumblesVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->fumbles->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['name']);
                                        $fumblesPlayers['name'] =  $playername;
                                        $fumblesPlayers['total'] = (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['total'];
                                        $fumblesPlayers['lost'] = (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['lost'];
                                        $fumblesPlayers['rec'] = (float) $xml->category->match[$i]->fumbles->awayteam->player[$j]['rec'];
                                        $fumblesPlayers['rec_td'] = (int) $xml->category->match[$i]->fumbles->awayteam->player[$j]['rec_td'];

                                        $fumblePlayersData = json_encode($fumblesPlayers);
                                        $PlayersScore[$player_id]['fumbles'] = $fumblesPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", $fumblePlayersData);
                                    }

                                    $interceptionsLocalArrayCount = count($xml->category->match[$i]->interceptions->hometeam->player);
                                    $interceptionsVisitorArrayCount = count($xml->category->match[$i]->interceptions->awayteam->player);

                                    for ($j = 0; $j < $interceptionsLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->interceptions->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['name']);
                                        $interceptionsPlayers['name'] =  $playername;
                                        $interceptionsPlayers['total_interceptions'] = (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['total_interceptions'];
                                        $interceptionsPlayers['yards'] = (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['yards'];
                                        $interceptionsPlayers['intercepted_touch_downs'] = (float) $xml->category->match[$i]->interceptions->hometeam->player[$j]['intercepted_touch_downs'];

                                        $interceptionsPlayersData = json_encode($interceptionsPlayers);
                                        $PlayersScore[$player_id]['interceptions'] = $interceptionsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", $interceptionsPlayersData, "", "", "", "", "");
                                    }

                                    for ($j = 0; $j < $interceptionsVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->interceptions->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['name']);
                                        $interceptionsPlayers['name'] =  $playername;
                                        $interceptionsPlayers['total_interceptions'] = (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['total_interceptions'];
                                        $interceptionsPlayers['yards'] = (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['yards'];
                                        $interceptionsPlayers['intercepted_touch_downs'] = (float) $xml->category->match[$i]->interceptions->awayteam->player[$j]['intercepted_touch_downs'];

                                        $interceptionsPlayersData = json_encode($interceptionsPlayers);
                                        $PlayersScore[$player_id]['interceptions'] = $interceptionsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", $interceptionsPlayersData, "", "", "", "", "");
                                    }

                                    $defensiveLocalArrayCount = count($xml->category->match[$i]->defensive->hometeam->player);
                                    $defensiveVisitorArrayCount = count($xml->category->match[$i]->defensive->awayteam->player);

                                    for ($j = 0; $j < $defensiveLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->defensive->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['name']);
                                        $defensivePlayers['name'] =  $playername;
                                        $defensivePlayers['tackles'] = (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['tackles'];
                                        $defensivePlayers['unassisted_tackles'] = (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['unassisted_tackles'];
                                        $defensivePlayers['sacks'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['sacks'];
                                        $defensivePlayers['tfl'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['tfl'];
                                        $defensivePlayers['passes_defended'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['passes_defended'];
                                        $defensivePlayers['qb_hts'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['qb_hts'];
                                        $defensivePlayers['interceptions_for_touch_downs'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['interceptions_for_touch_downs'];

                                        $defensivePlayersData = json_encode($defensivePlayers);
                                        $PlayersScore[$player_id]['defensive'] = $defensivePlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", $defensivePlayersData, "", "", "", "");
                                    }

                                    for ($j = 0; $j < $defensiveVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->defensive->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['name']);
                                        $defensivePlayers['name'] = $playername  ;
                                        $defensivePlayers['tackles'] = (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['tackles'];
                                        $defensivePlayers['unassisted_tackles'] = (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['unassisted_tackles'];
                                        $defensivePlayers['sacks'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['sacks'];
                                        $defensivePlayers['tfl'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['tfl'];
                                        $defensivePlayers['passes_defended'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['passes_defended'];
                                        $defensivePlayers['qb_hts'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['qb_hts'];
                                        $defensivePlayers['interceptions_for_touch_downs'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['interceptions_for_touch_downs'];

                                        $defensivePlayersData = json_encode($defensivePlayers);
                                        $PlayersScore[$player_id]['defensive'] = $defensivePlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", $defensivePlayersData, "", "", "", "");
                                    }

                                    $kick_returnsLocalArrayCount = count($xml->category->match[$i]->kick_returns->hometeam->player);
                                    $kick_returnsVisitorArrayCount = count($xml->category->match[$i]->kick_returns->awayteam->player);

                                    for ($j = 0; $j < $kick_returnsLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['name']);
                                        $kick_returnsPlayers['name'] =  $playername;
                                        $kick_returnsPlayers['total'] = (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['total'];
                                        $kick_returnsPlayers['yards'] = (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['yards'];
                                        $kick_returnsPlayers['average'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['average'];
                                        $kick_returnsPlayers['lg'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['lg'];
                                        $kick_returnsPlayers['td'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['td'];

                                        $kick_returnsPlayersData = json_encode($kick_returnsPlayers);
                                        $PlayersScore[$player_id]['kick_returns'] = $kick_returnsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", $kick_returnsPlayersData, "", "", "");
                                    }

                                    for ($j = 0; $j < $kick_returnsVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['name']);
                                        $kick_returnsPlayers['name'] =  $playername;
                                        $kick_returnsPlayers['total'] = (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['total'];
                                        $kick_returnsPlayers['yards'] = (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['yards'];
                                        $kick_returnsPlayers['average'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['average'];
                                        $kick_returnsPlayers['lg'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['lg'];
                                        $kick_returnsPlayers['td'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['td'];

                                        $kick_returnsPlayersData = json_encode($kick_returnsPlayers);
                                        $PlayersScore[$player_id]['kick_returns'] = $kick_returnsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", $kick_returnsPlayersData, "", "", "");
                                    }

                                    $punt_returnsLocalArrayCount = count($xml->category->match[$i]->punt_returns->hometeam->player);
                                    $punt_returnsVisitorArrayCount = count($xml->category->match[$i]->punt_returns->awayteam->player);

                                    for ($j = 0; $j < $punt_returnsLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->punt_returns->hometeam->playerv['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['name']);
                                        $punt_returnsPlayers['name'] = $playername  ;
                                        $punt_returnsPlayers['total'] = (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['total'];
                                        $punt_returnsPlayers['yards'] = (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['yards'];
                                        $punt_returnsPlayers['average'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['average'];
                                        $punt_returnsPlayers['lg'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['lg'];
                                        $punt_returnsPlayers['td'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['td'];

                                        $punt_returnsPlayersData = json_encode($punt_returnsPlayers);
                                        $PlayersScore[$player_id]['punt_returns'] = $punt_returnsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", $punt_returnsPlayersData, "", "");
                                    }

                                    for ($j = 0; $j < $punt_returnsVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['name']);
                                        $punt_returnsPlayers['name'] =  $playername;
                                        $punt_returnsPlayers['total'] = (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['total'];
                                        $punt_returnsPlayers['yards'] = (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['yards'];
                                        $punt_returnsPlayers['average'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['average'];
                                        $punt_returnsPlayers['lg'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['lg'];
                                        $punt_returnsPlayers['td'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['td'];

                                        $punt_returnsPlayersData = json_encode($punt_returnsPlayers);
                                        $PlayersScore[$player_id]['punt_returns'] = $punt_returnsPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", $punt_returnsPlayersData, "", "");
                                    }

                                    $kickingLocalArrayCount = count($xml->category->match[$i]->kicking->hometeam->player);
                                    $kickingVisitorArrayCount = count($xml->category->match[$i]->kicking->awayteam->player);

                                    for ($j = 0; $j < $kickingLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->kicking->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['name']);
                                        $kickingPlayers['name'] = $playername;
                                        $kickingPlayers['field_goals'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals'];
                                        $kickingPlayers['pct'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['pct'];
                                        $kickingPlayers['long'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['long'];
                                        $kickingPlayers['extra_point'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['extra_point'];
                                        $kickingPlayers['points'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['points'];
                                        $kickingPlayers['field_goals_from_1_19_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_1_19_yards'];
                                        $kickingPlayers['field_goals_from_20_29_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_20_29_yards'];
                                        $kickingPlayers['field_goals_from_30_39_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_30_39_yards'];
                                        $kickingPlayers['field_goals_from_40_49_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_40_49_yards'];
                                        $kickingPlayers['field_goals_from_50_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_50_yards'];

                                        $kickingPlayersData = json_encode($kickingPlayers);
                                        $PlayersScore[$player_id]['kicking'] = $kickingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", $kickingPlayersData, "");
                                    }

                                    for ($j = 0; $j < $kickingVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->kicking->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['name']);
                                        $kickingPlayers['name'] =  $playername ;
                                        $kickingPlayers['field_goals'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals'];
                                        $kickingPlayers['pct'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['pct'];
                                        $kickingPlayers['long'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['long'];
                                        $kickingPlayers['extra_point'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['extra_point'];
                                        $kickingPlayers['points'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['points'];
                                        $kickingPlayers['field_goals_from_1_19_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_1_19_yards'];
                                        $kickingPlayers['field_goals_from_20_29_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_20_29_yards'];
                                        $kickingPlayers['field_goals_from_30_39_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_30_39_yards'];
                                        $kickingPlayers['field_goals_from_40_49_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_40_49_yards'];
                                        $kickingPlayers['field_goals_from_50_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_50_yards'];

                                        $kickingPlayersData = json_encode($kickingPlayers);
                                        $PlayersScore[$player_id]['kicking'] = $kickingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", $kickingPlayersData, "");
                                    }

                                    $puntingLocalArrayCount = count($xml->category->match[$i]->punting->hometeam->player);
                                    $puntingVisitorArrayCount = count($xml->category->match[$i]->punting->awayteam->player);

                                    for ($j = 0; $j < $puntingLocalArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->punting->hometeam->player[$j]['id'];
                                        $team_id = $localteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->punting->hometeam->player[$j]['name']);
                                        $puntingPlayers['name'] =  $playername;


                                        $puntingPlayers['total'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['total'];

                                        $puntingPlayers['yards'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['yards'];

                                        $puntingPlayers['average'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['average'];

                                        $puntingPlayers['touchbacks'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['touchbacks'];

                                        $puntingPlayers['in20'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['in20'];

                                        $puntingPlayers['lg'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['lg'];

                                        $puntingPlayersData = json_encode($puntingPlayers);
                                        $PlayersScore[$player_id]['punting'] = $puntingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", "", $puntingPlayersData);
                                    }

                                    for ($j = 0; $j < $puntingVisitorArrayCount; $j++) {

                                        $player_id = (int) $xml->category->match[$i]->punting->awayteam->player[$j]['id'];
                                        $team_id = $visitorteam_id;
                                        $playername = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', (string) $xml->category->match[$i]->punting->awayteam->player[$j]['name']);
                                        $puntingPlayers['name'] =  $playername ;
                                        $puntingPlayers['total'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['total'];
                                        $puntingPlayers['yards'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['yards'];
                                        $puntingPlayers['average'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['average'];
                                        $puntingPlayers['touchbacks'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['touchbacks'];
                                        $puntingPlayers['in20'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['in20'];
                                        $puntingPlayers['lg'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['lg'];

                                        $puntingPlayersData = json_encode($puntingPlayers);
                                        $PlayersScore[$player_id]['punting'] = $puntingPlayers;
                                        //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", "", $puntingPlayersData);
                                    }
                                    $team_stats = $xml->category->match[$i]->team_stats;
                                    if (!empty($team_stats)) {
                                        $hometeam = $xml->category->match[$i]->team_stats->hometeam;
                                        $awayteam = $xml->category->match[$i]->team_stats->awayteam;
                                        if (!empty($hometeam)) {
                                            $team_id = $localteam_id;
                                            $firstdownTotal['total'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['total'];
                                            $firstdownTotal['passing'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['passing'];
                                            $firstdownTotal['rushing'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['rushing'];
                                            $firstdownTotal['from_penalties'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['from_penalties'];
                                            $firstdownTotal['third_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->hometeam->first_downs['third_down_efficiency'];
                                            $firstdownTotal['fourth_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->hometeam->first_downs['fourth_down_efficiency'];

                                            $plays = (int) $xml->category->match[$i]->team_stats->hometeam->plays['total'];

                                            $yards['total'] = (int) $xml->category->match[$i]->team_stats->hometeam->yards['total'];
                                            $yards['yards_per_play'] = (float) $xml->category->match[$i]->team_stats->hometeam->yards['yards_per_play'];
                                            $yards['total_drives'] = (float) $xml->category->match[$i]->team_stats->hometeam->yards['total_drives'];

                                            $passing['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['total'];
                                            $passing['comp_att'] = (string) $xml->category->match[$i]->team_stats->hometeam->passing['comp_att'];
                                            $passing['yards_per_pass'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['yards_per_pass'];
                                            $passing['interceptions_thrown'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['interceptions_thrown'];
                                            $passing['sacks_yards_lost'] = (string) $xml->category->match[$i]->team_stats->hometeam->passing['sacks_yards_lost'];

                                            $rushings['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->rushings['total'];
                                            $rushings['attempts'] = (string) $xml->category->match[$i]->team_stats->hometeam->rushings['attempts'];
                                            $rushings['yards_per_rush'] = (string) $xml->category->match[$i]->team_stats->hometeam->rushings['yards_per_rush'];

                                            $red_zone['made_att'] = (string) $xml->category->match[$i]->team_stats->hometeam->red_zone['made_att'];
                                            $penalties = (float) $xml->category->match[$i]->team_stats->hometeam->penalties['total'];

                                            $turnovers['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['total'];
                                            $turnovers['lost_fumbles'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['lost_fumbles'];
                                            $turnovers['interceptions'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['interceptions'];

                                            $posession = (float) $xml->category->match[$i]->team_stats->hometeam->posession['total'];
                                            $interceptions = (float) $xml->category->match[$i]->team_stats->hometeam->interceptions['total'];
                                            $fumbles_recovered = (float) $xml->category->match[$i]->team_stats->hometeam->fumbles_recovered['total'];
                                            $sacks = (float) $xml->category->match[$i]->team_stats->hometeam->sacks['total'];
                                            $safeties = (float) $xml->category->match[$i]->team_stats->hometeam->safeties['total'];
                                            $int_touchdowns = (float) $xml->category->match[$i]->team_stats->hometeam->int_touchdowns['total'];
                                            $points_against = (float) $xml->category->match[$i]->team_stats->hometeam->points_against['total'];
                                            $TeamScore['localteam'] = array(
                                                'first_downs' => $firstdownTotal,
                                                'plays' => $plays,
                                                'yards' => $yards,
                                                'passing' => $passing,
                                                'rushings' => $rushings,
                                                'red_zone' => $red_zone,
                                                'penalties' => $penalties,
                                                'turnovers' => $turnovers,
                                                'posession' => $posession,
                                                'interceptions' => $interceptions,
                                                'fumbles_recovered' => $fumbles_recovered,
                                                'sacks' => $sacks,
                                                'safeties' => $safeties,
                                                'int_touchdowns' => $int_touchdowns,
                                                'points_against' => $points_against,
                                            );
                                        }

                                        if (!empty($awayteam)) {
                                            $team_id = $visitorteam_id;

                                            $firstdownTotal['total'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['total'];
                                            $firstdownTotal['passing'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['passing'];
                                            $firstdownTotal['rushing'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['rushing'];
                                            $firstdownTotal['from_penalties'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['from_penalties'];
                                            $firstdownTotal['third_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->awayteam->first_downs['third_down_efficiency'];
                                            $firstdownTotal['fourth_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->awayteam->first_downs['fourth_down_efficiency'];

                                            $plays = (int) $xml->category->match[$i]->team_stats->awayteam->plays['total'];

                                            $yards['total'] = (int) $xml->category->match[$i]->team_stats->awayteam->yards['total'];
                                            $yards['yards_per_play'] = (float) $xml->category->match[$i]->team_stats->awayteam->yards['yards_per_play'];
                                            $yards['total_drives'] = (float) $xml->category->match[$i]->team_stats->awayteam->yards['total_drives'];

                                            $passing['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['total'];
                                            $passing['comp_att'] = (string) $xml->category->match[$i]->team_stats->awayteam->passing['comp_att'];
                                            $passing['yards_per_pass'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['yards_per_pass'];
                                            $passing['interceptions_thrown'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['interceptions_thrown'];
                                            $passing['sacks_yards_lost'] = (string) $xml->category->match[$i]->team_stats->awayteam->passing['sacks_yards_lost'];

                                            $rushings['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->rushings['total'];
                                            $rushings['attempts'] = (string) $xml->category->match[$i]->team_stats->awayteam->rushings['attempts'];
                                            $rushings['yards_per_rush'] = (string) $xml->category->match[$i]->team_stats->awayteam->rushings['yards_per_rush'];

                                            $red_zone['made_att'] = (string) $xml->category->match[$i]->team_stats->awayteam->red_zone['made_att'];
                                            $penalties = (float) $xml->category->match[$i]->team_stats->awayteam->penalties['total'];

                                            $turnovers['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['total'];
                                            $turnovers['lost_fumbles'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['lost_fumbles'];
                                            $turnovers['interceptions'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['interceptions'];

                                            $posession = (float) $xml->category->match[$i]->team_stats->awayteam->posession['total'];
                                            $interceptions = (float) $xml->category->match[$i]->team_stats->awayteam->interceptions['total'];
                                            $fumbles_recovered = (float) $xml->category->match[$i]->team_stats->awayteam->fumbles_recovered['total'];
                                            $sacks = (float) $xml->category->match[$i]->team_stats->awayteam->sacks['total'];
                                            $safeties = (float) $xml->category->match[$i]->team_stats->awayteam->safeties['total'];
                                            $int_touchdowns = (float) $xml->category->match[$i]->team_stats->awayteam->int_touchdowns['total'];
                                            $points_against = (float) $xml->category->match[$i]->team_stats->awayteam->points_against['total'];
                                            $TeamScore['visitorteam'] = array(
                                                'match_id' => $MatchIDLive,
                                                'team_id' => $team_id,
                                                'first_downs' => $firstdownTotal,
                                                'plays' => $plays,
                                                'yards' => $yards,
                                                'passing' => $passing,
                                                'rushings' => $rushings,
                                                'red_zone' => $red_zone,
                                                'penalties' => $penalties,
                                                'turnovers' => $turnovers,
                                                'posession' => $posession,
                                                'interceptions' => $interceptions,
                                                'fumbles_recovered' => $fumbles_recovered,
                                                'sacks' => $sacks,
                                                'safeties' => $safeties,
                                                'int_touchdowns' => $int_touchdowns,
                                                'points_against' => $points_against
                                            );
                                        }
                                    }


                                    $options = array(
                                        'table' => 'tbl_entity',
                                        'data' => array('StatusID' => 2,'ModifiedDate'=>date('Y-m-d H:i:s')),
                                        'where' => array(
                                                'EntityID' => $MatchID,
                                        )
                                    );
                                    $this->customUpdate($options);

                                    if (strtolower($MatchStatus) == "final" || strtolower($MatchStatus) == "after over time") {
                                        //$this->matchPlayerPointsCalculation();
                                        $TeamScore['PlayersScore'] = $PlayersScore;
                                        $options = array(
                                            'table' => 'sports_matches',
                                            'data' => array('MatchScoreDetails' => json_encode($TeamScore)),
                                            'where' => array(
                                                'MatchID' => $MatchID,
                                            )
                                        );
                                        $this->customUpdate($options);

                                        $options = array(
                                            'table' => 'tbl_entity',
                                            'data' => array('StatusID' => 5,'ModifiedDate'=>date('Y-m-d H:i:s')),
                                            'where' => array(
                                                'EntityID' => $MatchID,
                                            )
                                        );
                                        $this->customUpdate($options);

                                        /* Update Contest Status */
                                        $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType="Nfl" AND C.ContestDuration="Weekly" AND C.WeekStart = ' . $WeekID);

                                        $this->db->query("UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType='Nfl' AND C.ContestDuration='Daily' AND C.DailyDate <= '" . date('Y-m-d',strtotime($MatchStartDateTimeEST))."'");

                                        $this->db->query("UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType='Nfl' AND C.ContestDuration='SeasonLong' AND C.LeagueJoinDateTime <= '" . date('Y-m-d H:i:s',strtotime($MatchStartDateTimeEST))."'");

                                        foreach($PlayersScore as $Keys=>$PlayerState){
                                            $this->db->query("UPDATE sports_team_players JOIN sports_players ON sports_team_players.PlayerID = sports_players.PlayerID SET `sports_team_players`.`ScoreData` = '".json_encode($PlayerState)."' WHERE `sports_team_players`.`MatchID` ='".$MatchID."' AND `sports_players`.`PlayerIDLive` ='".$Keys."' ");
                                        }
                                    }
                                } else if (strtolower($MatchStatus) == "cancelled" || strtolower($MatchStatus) == "abandoned") {
                                    $options = array(
                                        'table' => 'sports_matches',
                                        'data' => array('MatchScoreDetails' => json_encode($TeamScore)),
                                        'where' => array(
                                            'MatchID' => $MatchID,
                                        )
                                    );
                                    $this->customUpdate($options);

                                    $options = array(
                                        'table' => 'tbl_entity',
                                        'data' => array('StatusID' => 3,'ModifiedDate'=>date('Y-m-d H:i:s')),
                                        'where' => array(
                                            'EntityID' => $MatchID,
                                        )
                                    );
                                    $this->customUpdate($options);
                                }
                                }
                            }
                    }
                }
            }
    }


    /*
      Description: To get matches live score(FOOTBALL API NFL)
     */
    function getMatchesScoreLiveNflGoalServe($CronID) {
        return true;

        $prevDate = date('Y-m-d H:i:s', strtotime('-5 day', strtotime(date('Y-m-d'))));
        $MatchesData = $this->getMatches('MatchID,ScoreIDLive,MatchIDLive,SeriesID,WeekID,SeasonType,MatchNo,TeamIDLocal,TeamIDVisitor,TeamIDLiveLocal,TeamIDLiveVisitor,MatchStartDateTime,MatchStartDateTimeUTC,MatchStartDateTimeEST', array("GameSportsType" => "Nfl", "StatusID" => array(1,2,5),'MatchStartDateTimePrev'=>$prevDate,'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'),true,0);
        if($MatchesData['Data']['TotalRecords'] > 0){
            foreach($MatchesData['Data']['Records'] as $Row){
                //dump($MatchesData['Data']['Records'] );
                $url = SPORTS_API_URL_GOALSERVE . '/football/nfl-scores?date='.date('d.m.Y',strtotime($Row['MatchStartDateTimeEST']));
                $xml = simplexml_load_string(file_get_contents($url));
                if (!empty($xml)) {
                    for ($i = 0; $i < count($xml->category->match); $i++) {
                        $MatchIDLive = (int) $xml->category->match[$i]['contestID'];
                        $MatchStatus = (string) $xml->category->match[$i]['status'];
                        if ($MatchIDLive == $Row['MatchIDLive']) {

                            $PlayersScore = array();
                            $TeamScore = array();
                            if (strtolower($MatchStatus) != "postponed" || strtolower($MatchStatus) != "not started") {

                                $localteam_id = (int) $xml->category->match[$i]->hometeam['id'];
                                $visitorteam_id = (int) $xml->category->match[$i]->awayteam['id'];
                                $passingLocalArrayCount = count($xml->category->match[$i]->passing->hometeam->player);
                                $passingVisitorArrayCount = count($xml->category->match[$i]->passing->awayteam->player);
                                $passPlayers = array();
                                $rushingPlayers = array();
                                $receivingPlayers = array();
                                $fumblesPlayers = array();
                                $interceptionsPlayer = array();
                                $defensivePlayers = array();
                                $kick_returnsPlayers = array();
                                $punt_returnsPlayer = array();
                                $kickingPlayer = array();
                                $puntingPlayers = array();

                                for ($j = 0; $j < $passingLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['id'];

                                    $team_id = $localteam_id;

                                    $passPlayers['name'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['name'];

                                    $passPlayers['comp_att'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['comp_att'];
                                    $passPlayers['yards'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['yards'];
                                    $passPlayers['average'] = (float) $xml->category->match[$i]->passing->hometeam->player[$j]['average'];
                                    $passPlayers['passing_touch_downs'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['passing_touch_downs'];
                                    $passPlayers['interceptions'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['interceptions'];
                                    $passPlayers['sacks'] = (string) $xml->category->match[$i]->passing->hometeam->player[$j]['sacks'];
                                    $passPlayers['rating'] = (float) $xml->category->match[$i]->passing->hometeam->player[$j]['rating'];
                                    $passPlayers['two_pt'] = (int) $xml->category->match[$i]->passing->hometeam->player[$j]['two_pt'];

                                    $passPlayersData = json_encode($passPlayers);
                                    $PlayersScore[$player_id]['passing'] = $passPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, $passPlayersData);
                                }

                                for ($j = 0; $j < $passingVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $passPlayers['name'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['name'];
                                    $passPlayers['comp_att'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['comp_att'];
                                    $passPlayers['yards'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['yards'];
                                    $passPlayers['average'] = (float) $xml->category->match[$i]->passing->awayteam->player[$j]['average'];
                                    $passPlayers['passing_touch_downs'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['passing_touch_downs'];
                                    $passPlayers['interceptions'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['interceptions'];
                                    $passPlayers['sacks'] = (string) $xml->category->match[$i]->passing->awayteam->player[$j]['sacks'];
                                    $passPlayers['rating'] = (float) $xml->category->match[$i]->passing->awayteam->player[$j]['rating'];
                                    $passPlayers['two_pt'] = (int) $xml->category->match[$i]->passing->awayteam->player[$j]['two_pt'];

                                    $passPlayersData = json_encode($passPlayers);
                                    $PlayersScore[$player_id]['passing'] = $passPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, $passPlayersData);
                                }

                                $rushingLocalArrayCount = count($xml->category->match[$i]->rushing->hometeam->player);
                                $rushingVisitorArrayCount = count($xml->category->match[$i]->rushing->awayteam->player);
                                for ($j = 0; $j < $rushingLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $rushingPlayers['name'] = (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['name'];
                                    $rushingPlayers['total_rushes'] = (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['total_rushes'];
                                    $rushingPlayers['yards'] = (string) $xml->category->match[$i]->rushing->hometeam->player[$j]['yards'];
                                    $rushingPlayers['average'] = (float) $xml->category->match[$i]->rushing->hometeam->player[$j]['average'];
                                    $rushingPlayers['rushing_touch_downs'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['rushing_touch_downs'];
                                    $rushingPlayers['longest_rush'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['longest_rush'];
                                    $rushingPlayers['two_pt'] = (int) $xml->category->match[$i]->rushing->hometeam->player[$j]['two_pt'];

                                    $rushPlayersData = json_encode($rushingPlayers);
                                    $PlayersScore[$player_id]['russing'] = $rushingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", $rushPlayersData);
                                }
                                for ($j = 0; $j < $rushingVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $rushingPlayers['name'] = (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['name'];
                                    $rushingPlayers['total_rushes'] = (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['total_rushes'];
                                    $rushingPlayers['yards'] = (string) $xml->category->match[$i]->rushing->awayteam->player[$j]['yards'];
                                    $rushingPlayers['average'] = (float) $xml->category->match[$i]->rushing->awayteam->player[$j]['average'];
                                    $rushingPlayers['rushing_touch_downs'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['rushing_touch_downs'];
                                    $rushingPlayers['longest_rush'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['longest_rush'];
                                    $rushingPlayers['two_pt'] = (int) $xml->category->match[$i]->rushing->awayteam->player[$j]['two_pt'];

                                    $rushPlayersData = json_encode($rushingPlayers);
                                    $PlayersScore[$player_id]['russing'] = $rushingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", $rushPlayersData);
                                }

                                $receivingLocalArrayCount = count($xml->category->match[$i]->receiving->hometeam->player);
                                $receivingVisitorArrayCount = count($xml->category->match[$i]->receiving->awayteam->player);

                                for ($j = 0; $j < $receivingLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $receivingPlayers['name'] = (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['name'];
                                    $receivingPlayers['total_receptions'] = (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['total_receptions'];
                                    $receivingPlayers['yards'] = (string) $xml->category->match[$i]->receiving->hometeam->player[$j]['yards'];
                                    $receivingPlayers['average'] = (float) $xml->category->match[$i]->receiving->hometeam->player[$j]['average'];
                                    $receivingPlayers['receiving_touch_downs'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['receiving_touch_downs'];
                                    $receivingPlayers['longest_reception'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['longest_reception'];
                                    $receivingPlayers['two_pt'] = (int) $xml->category->match[$i]->receiving->hometeam->player[$j]['two_pt'];

                                    $receivePlayersData = json_encode($receivingPlayers);
                                    $PlayersScore[$player_id]['receiving'] = $receivingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", $receivePlayersData);
                                }
                                for ($j = 0; $j < $receivingVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $receivingPlayers['name'] = (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['name'];
                                    $receivingPlayers['total_receptions'] = (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['total_receptions'];
                                    $receivingPlayers['yards'] = (string) $xml->category->match[$i]->receiving->awayteam->player[$j]['yards'];
                                    $receivingPlayers['average'] = (float) $xml->category->match[$i]->receiving->awayteam->player[$j]['average'];
                                    $receivingPlayers['receiving_touch_downs'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['receiving_touch_downs'];
                                    $receivingPlayers['longest_reception'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['longest_reception'];
                                    $receivingPlayers['two_pt'] = (int) $xml->category->match[$i]->receiving->awayteam->player[$j]['two_pt'];

                                    $receivePlayersData = json_encode($receivingPlayers);
                                    $PlayersScore[$player_id]['receiving'] = $receivingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", $receivePlayersData);
                                }

                                $fumblesLocalArrayCount = count($xml->category->match[$i]->fumbles->hometeam->player);
                                $fumblesVisitorArrayCount = count($xml->category->match[$i]->fumbles->awayteam->player);

                                for ($j = 0; $j < $fumblesLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->fumbles->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $fumblesPlayers['name'] = (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['name'];
                                    $fumblesPlayers['total'] = (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['total'];
                                    $fumblesPlayers['lost'] = (string) $xml->category->match[$i]->fumbles->hometeam->player[$j]['lost'];
                                    $fumblesPlayers['rec'] = (float) $xml->category->match[$i]->fumbles->hometeam->player[$j]['rec'];
                                    $fumblesPlayers['rec_td'] = (int) $xml->category->match[$i]->fumbles->hometeam->player[$j]['rec_td'];

                                    $fumblePlayersData = json_encode($fumblesPlayers);
                                    $PlayersScore[$player_id]['fumbles'] = $fumblesPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", $fumblePlayersData);
                                }

                                for ($j = 0; $j < $fumblesVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->fumbles->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $fumblesPlayers['name'] = (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['name'];
                                    $fumblesPlayers['total'] = (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['total'];
                                    $fumblesPlayers['lost'] = (string) $xml->category->match[$i]->fumbles->awayteam->player[$j]['lost'];
                                    $fumblesPlayers['rec'] = (float) $xml->category->match[$i]->fumbles->awayteam->player[$j]['rec'];
                                    $fumblesPlayers['rec_td'] = (int) $xml->category->match[$i]->fumbles->awayteam->player[$j]['rec_td'];

                                    $fumblePlayersData = json_encode($fumblesPlayers);
                                    $PlayersScore[$player_id]['fumbles'] = $fumblesPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", $fumblePlayersData);
                                }

                                $interceptionsLocalArrayCount = count($xml->category->match[$i]->interceptions->hometeam->player);
                                $interceptionsVisitorArrayCount = count($xml->category->match[$i]->interceptions->awayteam->player);

                                for ($j = 0; $j < $interceptionsLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->interceptions->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $interceptionsPlayers['name'] = (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['name'];
                                    $interceptionsPlayers['total_interceptions'] = (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['total_interceptions'];
                                    $interceptionsPlayers['yards'] = (string) $xml->category->match[$i]->interceptions->hometeam->player[$j]['yards'];
                                    $interceptionsPlayers['intercepted_touch_downs'] = (float) $xml->category->match[$i]->interceptions->hometeam->player[$j]['intercepted_touch_downs'];

                                    $interceptionsPlayersData = json_encode($interceptionsPlayers);
                                    $PlayersScore[$player_id]['interceptions'] = $interceptionsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", $interceptionsPlayersData, "", "", "", "", "");
                                }

                                for ($j = 0; $j < $interceptionsVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->interceptions->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $interceptionsPlayers['name'] = (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['name'];
                                    $interceptionsPlayers['total_interceptions'] = (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['total_interceptions'];
                                    $interceptionsPlayers['yards'] = (string) $xml->category->match[$i]->interceptions->awayteam->player[$j]['yards'];
                                    $interceptionsPlayers['intercepted_touch_downs'] = (float) $xml->category->match[$i]->interceptions->awayteam->player[$j]['intercepted_touch_downs'];

                                    $interceptionsPlayersData = json_encode($interceptionsPlayers);
                                    $PlayersScore[$player_id]['interceptions'] = $interceptionsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", $interceptionsPlayersData, "", "", "", "", "");
                                }

                                $defensiveLocalArrayCount = count($xml->category->match[$i]->defensive->hometeam->player);
                                $defensiveVisitorArrayCount = count($xml->category->match[$i]->defensive->awayteam->player);

                                for ($j = 0; $j < $defensiveLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->defensive->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $defensivePlayers['name'] = (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['name'];
                                    $defensivePlayers['tackles'] = (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['tackles'];
                                    $defensivePlayers['unassisted_tackles'] = (string) $xml->category->match[$i]->defensive->hometeam->player[$j]['unassisted_tackles'];
                                    $defensivePlayers['sacks'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['sacks'];
                                    $defensivePlayers['tfl'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['tfl'];
                                    $defensivePlayers['passes_defended'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['passes_defended'];
                                    $defensivePlayers['qb_hts'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['qb_hts'];
                                    $defensivePlayers['interceptions_for_touch_downs'] = (float) $xml->category->match[$i]->defensive->hometeam->player[$j]['interceptions_for_touch_downs'];

                                    $defensivePlayersData = json_encode($defensivePlayers);
                                    $PlayersScore[$player_id]['defensive'] = $defensivePlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", $defensivePlayersData, "", "", "", "");
                                }

                                for ($j = 0; $j < $defensiveVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->defensive->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $defensivePlayers['name'] = (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['name'];
                                    $defensivePlayers['tackles'] = (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['tackles'];
                                    $defensivePlayers['unassisted_tackles'] = (string) $xml->category->match[$i]->defensive->awayteam->player[$j]['unassisted_tackles'];
                                    $defensivePlayers['sacks'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['sacks'];
                                    $defensivePlayers['tfl'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['tfl'];
                                    $defensivePlayers['passes_defended'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['passes_defended'];
                                    $defensivePlayers['qb_hts'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['qb_hts'];
                                    $defensivePlayers['interceptions_for_touch_downs'] = (float) $xml->category->match[$i]->defensive->awayteam->player[$j]['interceptions_for_touch_downs'];

                                    $defensivePlayersData = json_encode($defensivePlayers);
                                    $PlayersScore[$player_id]['defensive'] = $defensivePlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", $defensivePlayersData, "", "", "", "");
                                }

                                $kick_returnsLocalArrayCount = count($xml->category->match[$i]->kick_returns->hometeam->player);
                                $kick_returnsVisitorArrayCount = count($xml->category->match[$i]->kick_returns->awayteam->player);

                                for ($j = 0; $j < $kick_returnsLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $kick_returnsPlayers['name'] = (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['name'];
                                    $kick_returnsPlayers['total'] = (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['total'];
                                    $kick_returnsPlayers['yards'] = (string) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['yards'];
                                    $kick_returnsPlayers['average'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['average'];
                                    $kick_returnsPlayers['lg'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['lg'];
                                    $kick_returnsPlayers['td'] = (float) $xml->category->match[$i]->kick_returns->hometeam->player[$j]['td'];

                                    $kick_returnsPlayersData = json_encode($kick_returnsPlayers);
                                    $PlayersScore[$player_id]['kick_returns'] = $kick_returnsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", $kick_returnsPlayersData, "", "", "");
                                }

                                for ($j = 0; $j < $kick_returnsVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $kick_returnsPlayers['name'] = (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['name'];
                                    $kick_returnsPlayers['total'] = (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['total'];
                                    $kick_returnsPlayers['yards'] = (string) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['yards'];
                                    $kick_returnsPlayers['average'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['average'];
                                    $kick_returnsPlayers['lg'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['lg'];
                                    $kick_returnsPlayers['td'] = (float) $xml->category->match[$i]->kick_returns->awayteam->player[$j]['td'];

                                    $kick_returnsPlayersData = json_encode($kick_returnsPlayers);
                                    $PlayersScore[$player_id]['kick_returns'] = $kick_returnsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", $kick_returnsPlayersData, "", "", "");
                                }

                                $punt_returnsLocalArrayCount = count($xml->category->match[$i]->punt_returns->hometeam->player);
                                $punt_returnsVisitorArrayCount = count($xml->category->match[$i]->punt_returns->awayteam->player);

                                for ($j = 0; $j < $punt_returnsLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->punt_returns->hometeam->playerv['id'];
                                    $team_id = $localteam_id;

                                    $punt_returnsPlayers['name'] = (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['name'];
                                    $punt_returnsPlayers['total'] = (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['total'];
                                    $punt_returnsPlayers['yards'] = (string) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['yards'];
                                    $punt_returnsPlayers['average'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['average'];
                                    $punt_returnsPlayers['lg'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['lg'];
                                    $punt_returnsPlayers['td'] = (float) $xml->category->match[$i]->punt_returns->hometeam->player[$j]['td'];

                                    $punt_returnsPlayersData = json_encode($punt_returnsPlayers);
                                    $PlayersScore[$player_id]['punt_returns'] = $punt_returnsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", $punt_returnsPlayersData, "", "");
                                }

                                for ($j = 0; $j < $punt_returnsVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $punt_returnsPlayers['name'] = (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['name'];
                                    $punt_returnsPlayers['total'] = (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['total'];
                                    $punt_returnsPlayers['yards'] = (string) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['yards'];
                                    $punt_returnsPlayers['average'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['average'];
                                    $punt_returnsPlayers['lg'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['lg'];
                                    $punt_returnsPlayers['td'] = (float) $xml->category->match[$i]->punt_returns->awayteam->player[$j]['td'];

                                    $punt_returnsPlayersData = json_encode($punt_returnsPlayers);
                                    $PlayersScore[$player_id]['punt_returns'] = $punt_returnsPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", $punt_returnsPlayersData, "", "");
                                }

                                $kickingLocalArrayCount = count($xml->category->match[$i]->kicking->hometeam->player);
                                $kickingVisitorArrayCount = count($xml->category->match[$i]->kicking->awayteam->player);

                                for ($j = 0; $j < $kickingLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->kicking->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $kickingPlayers['name'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['name'];
                                    $kickingPlayers['field_goals'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals'];
                                    $kickingPlayers['pct'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['pct'];
                                    $kickingPlayers['long'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['long'];
                                    $kickingPlayers['extra_point'] = (string) $xml->category->match[$i]->kicking->hometeam->player[$j]['extra_point'];
                                    $kickingPlayers['points'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['points'];
                                    $kickingPlayers['field_goals_from_1_19_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_1_19_yards'];
                                    $kickingPlayers['field_goals_from_20_29_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_20_29_yards'];
                                    $kickingPlayers['field_goals_from_30_39_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_30_39_yards'];
                                    $kickingPlayers['field_goals_from_40_49_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_40_49_yards'];
                                    $kickingPlayers['field_goals_from_50_yards'] = (float) $xml->category->match[$i]->kicking->hometeam->player[$j]['field_goals_from_50_yards'];

                                    $kickingPlayersData = json_encode($kickingPlayers);
                                    $PlayersScore[$player_id]['kicking'] = $kickingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", $kickingPlayersData, "");
                                }

                                for ($j = 0; $j < $kickingVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->kicking->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $kickingPlayers['name'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['name'];
                                    $kickingPlayers['field_goals'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals'];
                                    $kickingPlayers['pct'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['pct'];
                                    $kickingPlayers['long'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['long'];
                                    $kickingPlayers['extra_point'] = (string) $xml->category->match[$i]->kicking->awayteam->player[$j]['extra_point'];
                                    $kickingPlayers['points'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['points'];
                                    $kickingPlayers['field_goals_from_1_19_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_1_19_yards'];
                                    $kickingPlayers['field_goals_from_20_29_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_20_29_yards'];
                                    $kickingPlayers['field_goals_from_30_39_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_30_39_yards'];
                                    $kickingPlayers['field_goals_from_40_49_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_40_49_yards'];
                                    $kickingPlayers['field_goals_from_50_yards'] = (float) $xml->category->match[$i]->kicking->awayteam->player[$j]['field_goals_from_50_yards'];

                                    $kickingPlayersData = json_encode($kickingPlayers);
                                    $PlayersScore[$player_id]['kicking'] = $kickingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", $kickingPlayersData, "");
                                }

                                $puntingLocalArrayCount = count($xml->category->match[$i]->punting->hometeam->player);
                                $puntingVisitorArrayCount = count($xml->category->match[$i]->punting->awayteam->player);

                                for ($j = 0; $j < $puntingLocalArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->punting->hometeam->player[$j]['id'];
                                    $team_id = $localteam_id;

                                    $puntingPlayers['name'] = (string) $xml->category->match[$i]->punting->hometeam->player[$j]['name'];


                                    $puntingPlayers['total'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['total'];

                                    $puntingPlayers['yards'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['yards'];

                                    $puntingPlayers['average'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['average'];

                                    $puntingPlayers['touchbacks'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['touchbacks'];

                                    $puntingPlayers['in20'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['in20'];

                                    $puntingPlayers['lg'] = (float) $xml->category->match[$i]->punting->hometeam->player[$j]['lg'];

                                    $puntingPlayersData = json_encode($puntingPlayers);
                                    $PlayersScore[$player_id]['punting'] = $puntingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", "", $puntingPlayersData);
                                }

                                for ($j = 0; $j < $puntingVisitorArrayCount; $j++) {

                                    $player_id = (int) $xml->category->match[$i]->punting->awayteam->player[$j]['id'];
                                    $team_id = $visitorteam_id;

                                    $puntingPlayers['name'] = (string) $xml->category->match[$i]->punting->awayteam->player[$j]['name'];
                                    $puntingPlayers['total'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['total'];
                                    $puntingPlayers['yards'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['yards'];
                                    $puntingPlayers['average'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['average'];
                                    $puntingPlayers['touchbacks'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['touchbacks'];
                                    $puntingPlayers['in20'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['in20'];
                                    $puntingPlayers['lg'] = (float) $xml->category->match[$i]->punting->awayteam->player[$j]['lg'];

                                    $puntingPlayersData = json_encode($puntingPlayers);
                                    $PlayersScore[$player_id]['punting'] = $puntingPlayers;
                                    //$this->matchLiveScoreInsertData($match_id, $team_id, $player_id, "", "", "", "", "", "", "", "", "", $puntingPlayersData);
                                }
                                $team_stats = $xml->category->match[$i]->team_stats;
                                if (!empty($team_stats)) {
                                    $hometeam = $xml->category->match[$i]->team_stats->hometeam;
                                    $awayteam = $xml->category->match[$i]->team_stats->awayteam;
                                    if (!empty($hometeam)) {
                                        $team_id = $localteam_id;
                                        $firstdownTotal['total'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['total'];
                                        $firstdownTotal['passing'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['passing'];
                                        $firstdownTotal['rushing'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['rushing'];
                                        $firstdownTotal['from_penalties'] = (int) $xml->category->match[$i]->team_stats->hometeam->first_downs['from_penalties'];
                                        $firstdownTotal['third_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->hometeam->first_downs['third_down_efficiency'];
                                        $firstdownTotal['fourth_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->hometeam->first_downs['fourth_down_efficiency'];

                                        $plays = (int) $xml->category->match[$i]->team_stats->hometeam->plays['total'];

                                        $yards['total'] = (int) $xml->category->match[$i]->team_stats->hometeam->yards['total'];
                                        $yards['yards_per_play'] = (float) $xml->category->match[$i]->team_stats->hometeam->yards['yards_per_play'];
                                        $yards['total_drives'] = (float) $xml->category->match[$i]->team_stats->hometeam->yards['total_drives'];

                                        $passing['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['total'];
                                        $passing['comp_att'] = (string) $xml->category->match[$i]->team_stats->hometeam->passing['comp_att'];
                                        $passing['yards_per_pass'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['yards_per_pass'];
                                        $passing['interceptions_thrown'] = (float) $xml->category->match[$i]->team_stats->hometeam->passing['interceptions_thrown'];
                                        $passing['sacks_yards_lost'] = (string) $xml->category->match[$i]->team_stats->hometeam->passing['sacks_yards_lost'];

                                        $rushings['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->rushings['total'];
                                        $rushings['attempts'] = (string) $xml->category->match[$i]->team_stats->hometeam->rushings['attempts'];
                                        $rushings['yards_per_rush'] = (string) $xml->category->match[$i]->team_stats->hometeam->rushings['yards_per_rush'];

                                        $red_zone['made_att'] = (string) $xml->category->match[$i]->team_stats->hometeam->red_zone['made_att'];
                                        $penalties = (float) $xml->category->match[$i]->team_stats->hometeam->penalties['total'];

                                        $turnovers['total'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['total'];
                                        $turnovers['lost_fumbles'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['lost_fumbles'];
                                        $turnovers['interceptions'] = (float) $xml->category->match[$i]->team_stats->hometeam->turnovers['interceptions'];

                                        $posession = (float) $xml->category->match[$i]->team_stats->hometeam->posession['total'];
                                        $interceptions = (float) $xml->category->match[$i]->team_stats->hometeam->interceptions['total'];
                                        $fumbles_recovered = (float) $xml->category->match[$i]->team_stats->hometeam->fumbles_recovered['total'];
                                        $sacks = (float) $xml->category->match[$i]->team_stats->hometeam->sacks['total'];
                                        $safeties = (float) $xml->category->match[$i]->team_stats->hometeam->safeties['total'];
                                        $int_touchdowns = (float) $xml->category->match[$i]->team_stats->hometeam->int_touchdowns['total'];
                                        $points_against = (float) $xml->category->match[$i]->team_stats->hometeam->points_against['total'];
                                        $TeamScore['localteam'] = array(
                                            'first_downs' => $firstdownTotal,
                                            'plays' => $plays,
                                            'yards' => $yards,
                                            'passing' => $passing,
                                            'rushings' => $rushings,
                                            'red_zone' => $red_zone,
                                            'penalties' => $penalties,
                                            'turnovers' => $turnovers,
                                            'posession' => $posession,
                                            'interceptions' => $interceptions,
                                            'fumbles_recovered' => $fumbles_recovered,
                                            'sacks' => $sacks,
                                            'safeties' => $safeties,
                                            'int_touchdowns' => $int_touchdowns,
                                            'points_against' => $points_against,
                                        );
                                    }

                                    if (!empty($awayteam)) {
                                        $team_id = $visitorteam_id;

                                        $firstdownTotal['total'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['total'];
                                        $firstdownTotal['passing'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['passing'];
                                        $firstdownTotal['rushing'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['rushing'];
                                        $firstdownTotal['from_penalties'] = (int) $xml->category->match[$i]->team_stats->awayteam->first_downs['from_penalties'];
                                        $firstdownTotal['third_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->awayteam->first_downs['third_down_efficiency'];
                                        $firstdownTotal['fourth_down_efficiency'] = (string) $xml->category->match[$i]->team_stats->awayteam->first_downs['fourth_down_efficiency'];

                                        $plays = (int) $xml->category->match[$i]->team_stats->awayteam->plays['total'];

                                        $yards['total'] = (int) $xml->category->match[$i]->team_stats->awayteam->yards['total'];
                                        $yards['yards_per_play'] = (float) $xml->category->match[$i]->team_stats->awayteam->yards['yards_per_play'];
                                        $yards['total_drives'] = (float) $xml->category->match[$i]->team_stats->awayteam->yards['total_drives'];

                                        $passing['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['total'];
                                        $passing['comp_att'] = (string) $xml->category->match[$i]->team_stats->awayteam->passing['comp_att'];
                                        $passing['yards_per_pass'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['yards_per_pass'];
                                        $passing['interceptions_thrown'] = (float) $xml->category->match[$i]->team_stats->awayteam->passing['interceptions_thrown'];
                                        $passing['sacks_yards_lost'] = (string) $xml->category->match[$i]->team_stats->awayteam->passing['sacks_yards_lost'];

                                        $rushings['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->rushings['total'];
                                        $rushings['attempts'] = (string) $xml->category->match[$i]->team_stats->awayteam->rushings['attempts'];
                                        $rushings['yards_per_rush'] = (string) $xml->category->match[$i]->team_stats->awayteam->rushings['yards_per_rush'];

                                        $red_zone['made_att'] = (string) $xml->category->match[$i]->team_stats->awayteam->red_zone['made_att'];
                                        $penalties = (float) $xml->category->match[$i]->team_stats->awayteam->penalties['total'];

                                        $turnovers['total'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['total'];
                                        $turnovers['lost_fumbles'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['lost_fumbles'];
                                        $turnovers['interceptions'] = (float) $xml->category->match[$i]->team_stats->awayteam->turnovers['interceptions'];

                                        $posession = (float) $xml->category->match[$i]->team_stats->awayteam->posession['total'];
                                        $interceptions = (float) $xml->category->match[$i]->team_stats->awayteam->interceptions['total'];
                                        $fumbles_recovered = (float) $xml->category->match[$i]->team_stats->awayteam->fumbles_recovered['total'];
                                        $sacks = (float) $xml->category->match[$i]->team_stats->awayteam->sacks['total'];
                                        $safeties = (float) $xml->category->match[$i]->team_stats->awayteam->safeties['total'];
                                        $int_touchdowns = (float) $xml->category->match[$i]->team_stats->awayteam->int_touchdowns['total'];
                                        $points_against = (float) $xml->category->match[$i]->team_stats->awayteam->points_against['total'];
                                        $TeamScore['visitorteam'] = array(
                                            'match_id' => $match_id,
                                            'team_id' => $team_id,
                                            'first_downs' => $firstdownTotal,
                                            'plays' => $plays,
                                            'yards' => $yards,
                                            'passing' => $passing,
                                            'rushings' => $rushings,
                                            'red_zone' => $red_zone,
                                            'penalties' => $penalties,
                                            'turnovers' => $turnovers,
                                            'posession' => $posession,
                                            'interceptions' => $interceptions,
                                            'fumbles_recovered' => $fumbles_recovered,
                                            'sacks' => $sacks,
                                            'safeties' => $safeties,
                                            'int_touchdowns' => $int_touchdowns,
                                            'points_against' => $points_against
                                        );
                                    }
                                }
                                if (strtolower($MatchStatus) == "final" || strtolower($MatchStatus) == "after over time") {
                                    //$this->matchPlayerPointsCalculation();
                                    $TeamScore['PlayersScore'] = $PlayersScore;
                                    $options = array(
                                        'table' => 'sports_matches',
                                        'data' => array('MatchScoreDetails' => json_encode($TeamScore)),
                                        'where' => array(
                                            'MatchID' => $Row['MatchID'],
                                        )
                                    );
                                    $this->customUpdate($options);

                                    $options = array(
                                        'table' => 'tbl_entity',
                                        'data' => array('StatusID' => 5,'ModifiedDate'=>date('Y-m-d H:i:s')),
                                        'where' => array(
                                            'EntityID' => $Row['MatchID'],
                                        )
                                    );
                                    $this->customUpdate($options);

                                    /* Update Contest Status */
                                    $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType="Nfl" AND C.ContestDuration="Weekly" AND C.WeekStart = ' . $Row['WeekID']);

                                    $this->db->query("UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType='Nfl' AND C.ContestDuration='Daily' AND C.DailyDate <= '" . date('Y-m-d',strtotime($Row['MatchStartDateTimeEST']))."'");

                                    $this->db->query("UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND E.GameSportsType='Nfl' AND C.ContestDuration='SeasonLong' AND C.LeagueJoinDateTime <= '" . date('Y-m-d H:i:s',strtotime($Row['MatchStartDateTimeEST']))."'");
                                }
                            } else if (strtolower($MatchStatus) == "cancelled" || strtolower($MatchStatus) == "abandoned") {
                                $options = array(
                                    'table' => 'sports_matches',
                                    'data' => array('MatchScoreDetails' => json_encode($TeamScore)),
                                    'where' => array(
                                        'MatchID' => $Row['MatchID'],
                                    )
                                );
                                $this->customUpdate($options);

                                $options = array(
                                    'table' => 'tbl_entity',
                                    'data' => array('StatusID' => 3,'ModifiedDate'=>date('Y-m-d H:i:s')),
                                    'where' => array(
                                        'EntityID' => $Row['MatchID'],
                                    )
                                );
                                $this->customUpdate($options);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description: To common funtion find key value
     */

    function findKeyValueArrayPoints($Array, $Key) {
        $WinnerUsers = array();
        foreach ($Array as $Rows) {
            if ($Rows['PointsTypeGUID'] == $Key) {
                $WinnerUsers = $Rows;
            }
        }
        return $WinnerUsers;
    }

        /*
      Description: To calculate points according to keys
     */

    function calculatePointsFootball($Points = array(), $ScoreValue) {

        $PlayerPoints = array('PointsTypeGUID' => $Points['PointsTypeGUID'], 'PointsTypeShortDescription' => $Points['PointsTypeShortDescription'], 'DefinedPoints' => $Points['Points'], 'ScoreValue' => (!empty($ScoreValue)) ? $ScoreValue : "0");
        switch ($Points['PointsTypeGUID']) {

            case 'PassingTouchdowns':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'TacklesForLoss':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'SoloTackles':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'SackYards':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;     
            case 'Sacks':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;     
            case 'RushingYards':
                if(!empty($ScoreValue)){
                    $PointVal = $ScoreValue/10;
                    $PlayerPoints['CalculatedPoints'] = $PointVal * $Points['Points']; 
                }else{
                   $PlayerPoints['CalculatedPoints'] = 0; 
                }
                return $PlayerPoints;
                break;     
            case 'RushingTouchdowns':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'RussingYards100Plus':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;   
            case 'Receptions':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break; 
            case 'ReceivingYards':
                if(!empty($ScoreValue)){
                    $PointVal = $ScoreValue/10;
                    $PlayerPoints['CalculatedPoints'] = $PointVal * $Points['Points']; 
                }else{
                   $PlayerPoints['CalculatedPoints'] = 0; 
                }
                return $PlayerPoints;
                break;
            case 'ReceivingTouchdowns':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'ReceivingYards100Plus':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'PATMade':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;   
            case 'PassingYards':
                if(!empty($ScoreValue)){
                    $PointVal = $ScoreValue/25;
                    $PlayerPoints['CalculatedPoints'] = $PointVal * $Points['Points']; 
                }else{
                   $PlayerPoints['CalculatedPoints'] = 0; 
                }
                return $PlayerPoints;
                break;   
            case 'PassingYards300Plus':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'AssistedTackles':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;    
            case 'PassingInterceptions':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;  
            case 'PassesDefended':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;  
            case 'Interceptions':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;  
            case 'FumblesRecovered':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;  
            case 'FumblesLost':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;  
            case 'FumblesForced':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break; 
            case 'FumbleRecoveredforTouchdown':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break; 
            case 'FGMade50yards':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break; 
            case 'FGMade049yards':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break; 
            case 'DefensiveTouchdowns':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points['Points'] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            default:
                return false;
                break;
        }
    }

    /*
      Description: To get player points
     */

    function getPlayersPointGoalServe($CronID, $MatchIDScore = "") {

        ini_set('max_execution_time', 300);

        if (!empty($MatchIDScore)) {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID', array('MatchID' => $MatchIDScore,'OrderBy' => 'M.MatchStartDateTime', 'Sequence' => 'DESC'), true, 1, 10);
        } else {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID', array('Filter' => 'Yesterday', 'StatusID' => array(2, 5), 'IsPlayerPointsUpdated' => 'No', 'OrderBy' => 'M.MatchStartDateTime', 'Sequence' => 'DESC'), true, 1, 10);
        }
        /* Get Live Matches Data */
        if (!empty($LiveMatches)) {

            $PointsDataArr = $this->SnakeDrafts_model->getPoints(array('StatusID'=>1));
            $PointsConfiguration =  $PointsDataArr['Data']['Records'];
            $MatchTypes = array('Football NFL' => 'Points');
            /* Sorting Keys */
            $PointsSortingKeys = array('DEFENSIVE', 'OFFENSIVE', 'KICKING');
            foreach ($LiveMatches['Data']['Records'] as $Value) {
                if (empty((array) $Value['MatchScoreDetails']))
                    continue;

                /* Get Match Players */
                $MatchPlayers = $this->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID']), true, 0);
                if ($MatchPlayers['Data']['TotalRecords'] <= 0) {
                    continue;
                }
                /* Get Match Live Score Data */
                $BatsmanPlayers = $BowlingPlayers = $FielderPlayers = $AllPalyers = $AllPlayeRoleData = array();
                foreach ($Value['MatchScoreDetails']['PlayersScore'] as $PlayerKey=>$PlayerSubValue) {
                        if (isset($PlayerSubValue['passing'])) {
                            $AllPalyers[$PlayerKey]['PassingTouchdowns'] = $PlayerSubValue['passing']['passing_touch_downs'];
                            $AllPalyers[$PlayerKey]['PassingYards'] = $PlayerSubValue['passing']['yards'];
                            $AllPalyers[$PlayerKey]['PassingInterceptions'] = $PlayerSubValue['passing']['interceptions'];
                            $AllPalyers[$PlayerKey]['PassingYards300Plus'] = ($PlayerSubValue['passing']['yards'] >= 300) ? 1 : 0;
                        }

                        if (isset($PlayerSubValue['defensive'])) {
                            $AllPalyers[$PlayerKey]['TacklesForLoss'] = $PlayerSubValue['defensive']['unassisted_tackles'];
                            $AllPalyers[$PlayerKey]['AssistedTackles'] = $PlayerSubValue['defensive']['tackles'];
                            $AllPalyers[$PlayerKey]['Sacks'] = $PlayerSubValue['defensive']['sacks'];
                            $AllPalyers[$PlayerKey]['PassesDefended'] = $PlayerSubValue['defensive']['passes_defended'];
                            $AllPalyers[$PlayerKey]['Interceptions'] = $PlayerSubValue['defensive']['interceptions_for_touch_downs'];
                        }

                        if (isset($PlayerSubValue['russing'])) {
                            $AllPalyers[$PlayerKey]['RushingYards'] = $PlayerSubValue['russing']['yards'];
                            $AllPalyers[$PlayerKey]['RushingTouchdowns'] = $PlayerSubValue['russing']['rushing_touch_downs'];
                            $AllPalyers[$PlayerKey]['RussingYards100Plus'] = ($PlayerSubValue['russing']['yards'] >= 100) ? 1 : 0;
                        }

                        if (isset($PlayerSubValue['receiving'])) {
                            $AllPalyers[$PlayerKey]['Receptions'] = $PlayerSubValue['receiving']['total_receptions'];
                            $AllPalyers[$PlayerKey]['ReceivingYards'] = $PlayerSubValue['receiving']['yards'];
                            $AllPalyers[$PlayerKey]['ReceivingTouchdowns'] = $PlayerSubValue['receiving']['receiving_touch_downs'];
                            $AllPalyers[$PlayerKey]['ReceivingYards100Plus'] = ($PlayerSubValue['receiving']['yards'] >= 100) ? 1 : 0;
                        }

                        if (isset($PlayerSubValue['kicking'])) {
                            $AllPalyers[$PlayerKey]['PATMade'] = $PlayerSubValue['kicking']['points'];
                            $AllPalyers[$PlayerKey]['FGMade50yards'] = $PlayerSubValue['kicking']['field_goals_from_50_yards'];
                            $AllPalyers[$PlayerKey]['FGMade049yards'] = $PlayerSubValue['kicking']['field_goals_from_1_19_yards'] + $PlayerSubValue['kicking']['field_goals_from_20_29_yards'] + $PlayerSubValue['kicking']['field_goals_from_30_39_yards'] + $PlayerSubValue['kicking']['field_goals_from_40_49_yards'];
                        }

                        if (isset($PlayerSubValue['fumbles'])) {
                            $AllPalyers[$PlayerKey]['FumblesRecovered'] = $PlayerSubValue['fumbles']['rec'];
                        }

                        if (isset($PlayerSubValue['fumbles'])) {
                            $AllPalyers[$PlayerKey]['FumblesRecovered'] = $PlayerSubValue['fumbles']['rec'];
                            $AllPalyers[$PlayerKey]['FumblesLost'] = $PlayerSubValue['fumbles']['lost'];
                            $AllPalyers[$PlayerKey]['FumbleRecoveredforTouchdown'] = $PlayerSubValue['fumbles']['rec_td'];
                        }

                }
                if (empty($AllPalyers)) {
                    continue;
                }
                $AllPlayersLiveIds = array_keys($AllPalyers);
                
                foreach($AllPalyers as $PlayerLive=>$ScoreValue){
                    $PointsData = array();
                    if(!empty($ScoreValue)){
                        foreach($PointsConfiguration as $PointScore){
                            if(!empty($ScoreValue[$PointScore['PointsTypeGUID']])){
                                $PointsData[] = $this->calculatePointsFootball($PointScore,$ScoreValue[$PointScore['PointsTypeGUID']]);
                            }else{
                                $PointScore['CalculatedPoints']=0;
                                $PointsData[] = $PointScore;
                            }
                        }
                    }
                    if(!empty($PointsData)){
                        $TotalPoints = 0;
                        foreach($PointsData as $Point){
                            $TotalPoints += $Point['CalculatedPoints'];
                        }

                        $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.PlayerIDLive = ' . $PlayerLive . ' LIMIT 1');
                        $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                        /* Update Player Points Data */
                        $UpdateData = array_filter(array(
                            'TotalPoints' => $TotalPoints,
                            'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null
                        ));

                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->where('PlayerID', $PlayerID);
                        $this->db->limit(1);
                        $this->db->update('sports_team_players', $UpdateData);
                    }
                }
                /* Update Match Player Points Status */
                if ($Value['StatusID'] == 5) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array('IsPlayerPointsUpdated' => 'Yes'));
                }
            }
        }
        return true;
    }

    /*
      Description: To get player points
     */

    function getPlayersPointSessionLongGoalServe($MatchIDScore = "") {

        /*Get Running Contest */ 
        $this->db->select("SeriesID,ContestID,WeekStart as WeekID,PrivatePointScoring");
        $this->db->from('tbl_entity as E, sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 2);
        $Query = $this->db->get();
        $getRunningContest = $Query->result_array();
        if (!empty($getRunningContest)) {
            foreach ($getRunningContest as $key => $RunContVal) {
                /* Get User Joined Contests Data */
                $PointsConfiguration = json_decode($RunContVal['PrivatePointScoring'],true);
                $MatchTypes = array('Football NFL' => 'Points');
                /* Sorting Keys */
                $PointsSortingKeys = array('DEFENSIVE', 'OFFENSIVE', 'KICKING');
                $this->db->select("WeekID,UserTeamID,UserID");
                $this->db->from('sports_users_teams');
                $this->db->where("ContestID", $RunContVal['ContestID']);
                $QueryTeam = $this->db->get();
                if ($QueryTeam->num_rows() > 0) {
                    $GetCurrentWeek = $QueryTeam->row_array();
                    $GetCurrentWeekID = $GetCurrentWeek['WeekID'];
                    $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID', array('WeekID' => $GetCurrentWeekID, 'StatusID' => array(2, 5), 'OrderBy' => 'M.MatchStartDateTime', 'Sequence' => 'DESC'), true, 1, 100);
                     /* Get Live Matches Data */
                    if (!empty($LiveMatches)) {

                        foreach ($LiveMatches['Data']['Records'] as $Value) {

                            if (empty((array) $Value['MatchScoreDetails']))
                                continue;

                            $UpdateData = array();

                            /* Get Match Players */
                            $MatchPlayers = $this->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID']), true, 0);
                            if ($MatchPlayers['Data']['TotalRecords'] <= 0) {
                                continue;
                            }
                            /* Get Match Live Score Data */
                            $BatsmanPlayers = $BowlingPlayers = $FielderPlayers = $AllPalyers = $AllPlayeRoleData = array();
                            foreach ($Value['MatchScoreDetails']['PlayersScore'] as $PlayerKey=>$PlayerSubValue) {
                                    if (isset($PlayerSubValue['passing'])) {
                                        $AllPalyers[$PlayerKey]['PassingTouchdowns'] = $PlayerSubValue['passing']['passing_touch_downs'];
                                        $AllPalyers[$PlayerKey]['PassingYards'] = $PlayerSubValue['passing']['yards'];
                                        $AllPalyers[$PlayerKey]['PassingInterceptions'] = $PlayerSubValue['passing']['interceptions'];
                                        $AllPalyers[$PlayerKey]['PassingYards300Plus'] = ($PlayerSubValue['passing']['yards'] >= 300) ? 1 : 0;
                                    }

                                    if (isset($PlayerSubValue['defensive'])) {
                                        $AllPalyers[$PlayerKey]['TacklesForLoss'] = $PlayerSubValue['defensive']['unassisted_tackles'];
                                        $AllPalyers[$PlayerKey]['AssistedTackles'] = $PlayerSubValue['defensive']['tackles'];
                                        $AllPalyers[$PlayerKey]['Sacks'] = $PlayerSubValue['defensive']['sacks'];
                                        $AllPalyers[$PlayerKey]['PassesDefended'] = $PlayerSubValue['defensive']['passes_defended'];
                                        $AllPalyers[$PlayerKey]['Interceptions'] = $PlayerSubValue['defensive']['interceptions_for_touch_downs'];
                                    }

                                    if (isset($PlayerSubValue['russing'])) {
                                        $AllPalyers[$PlayerKey]['RushingYards'] = $PlayerSubValue['russing']['yards'];
                                        $AllPalyers[$PlayerKey]['RushingTouchdowns'] = $PlayerSubValue['russing']['rushing_touch_downs'];
                                        $AllPalyers[$PlayerKey]['RussingYards100Plus'] = ($PlayerSubValue['russing']['yards'] >= 100) ? 1 : 0;
                                    }

                                    if (isset($PlayerSubValue['receiving'])) {
                                        $AllPalyers[$PlayerKey]['Receptions'] = $PlayerSubValue['receiving']['total_receptions'];
                                        $AllPalyers[$PlayerKey]['ReceivingYards'] = $PlayerSubValue['receiving']['yards'];
                                        $AllPalyers[$PlayerKey]['ReceivingTouchdowns'] = $PlayerSubValue['receiving']['receiving_touch_downs'];
                                        $AllPalyers[$PlayerKey]['ReceivingYards100Plus'] = ($PlayerSubValue['receiving']['yards'] >= 100) ? 1 : 0;
                                    }

                                    if (isset($PlayerSubValue['kicking'])) {
                                        $AllPalyers[$PlayerKey]['PATMade'] = $PlayerSubValue['kicking']['points'];
                                        $AllPalyers[$PlayerKey]['FGMade50yards'] = $PlayerSubValue['kicking']['field_goals_from_50_yards'];
                                        $AllPalyers[$PlayerKey]['FGMade049yards'] = $PlayerSubValue['kicking']['field_goals_from_1_19_yards'] + $PlayerSubValue['kicking']['field_goals_from_20_29_yards'] + $PlayerSubValue['kicking']['field_goals_from_30_39_yards'] + $PlayerSubValue['kicking']['field_goals_from_40_49_yards'];
                                    }

                                    if (isset($PlayerSubValue['fumbles'])) {
                                        $AllPalyers[$PlayerKey]['FumblesRecovered'] = $PlayerSubValue['fumbles']['rec'];
                                    }

                                    if (isset($PlayerSubValue['fumbles'])) {
                                        $AllPalyers[$PlayerKey]['FumblesRecovered'] = $PlayerSubValue['fumbles']['rec'];
                                        $AllPalyers[$PlayerKey]['FumblesLost'] = $PlayerSubValue['fumbles']['lost'];
                                        $AllPalyers[$PlayerKey]['FumbleRecoveredforTouchdown'] = $PlayerSubValue['fumbles']['rec_td'];
                                    }

                            }
                            if (empty($AllPalyers)) {
                                continue;
                            }
                            $AllPlayersLiveIds = array_keys($AllPalyers);
                            
                            foreach($AllPalyers as $PlayerLive=>$ScoreValue){
                                $PointsData = array();
                                if(!empty($ScoreValue)){
                                    foreach($PointsConfiguration as $PointScore){
                                        if(!empty($ScoreValue[$PointScore['PointsTypeGUID']])){
                                            $PointsData[] = $this->calculatePointsFootball($PointScore,$ScoreValue[$PointScore['PointsTypeGUID']]);
                                        }else{
                                            $PointScore['CalculatedPoints']=0;
                                            $PointsData[] = $PointScore;
                                        }
                                    }
                                }
                                if(!empty($PointsData)){
                                    $TotalPoints = 0;
                                    foreach($PointsData as $Point){
                                        $TotalPoints += $Point['CalculatedPoints'];
                                    }

                                    $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.PlayerIDLive = ' . $PlayerLive . ' LIMIT 1');
                                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                                    /* Update Player Points Data */
                                    $UpdateData[] = array(
                                        'TotalPoints' => $TotalPoints,
                                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null,
                                        'PlayerID'=> $PlayerID
                                    );
                                }
                            }
                            $GetUserTeams = $QueryTeam->result_array();
                            $GetUserTeamsID = array_column($GetUserTeams, 'UserTeamID');
                            if(!empty($UpdateData)){
                                foreach($UpdateData as $RowVal){
                                    $MatchesAPIData = array(
                                        'PointsData' => $RowVal['PointsData'],
                                        'Points' => $RowVal['TotalPoints']
                                    );
                                    $this->db->where_in('UserTeamID', $GetUserTeamsID);
                                    $this->db->where('PlayerID', $RowVal['PlayerID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_users_team_players', $MatchesAPIData);
                                }
                            }
                        }

                    }
                }
                    
            }
        }
        if (!empty($getRunningContest)) {
          $this->calculatePrivateSessionLongTeamPlayerPoints($getRunningContest);
        }
    }

    /*
      Description: To get player points
     */
    function calculatePrivateSessionLongTeamPlayerPoints($getRunningContest){
        foreach ($getRunningContest as $key => $RunContVal) {
            /* get Player Points Data */
            $this->db->select("UserID,ContestID,TotalPoints");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $RunContVal['ContestID']);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
               $GetJoinedUser = $Query->result_array();
               foreach($GetJoinedUser as $Row){
                    /* get Player Points Data */
                    $UserTotalPoints = 0;

                    /** update player points weekly table **/
                    $this->db->select("WeekID,UserTeamID,UserID");
                    $this->db->from('sports_users_teams_weekly');
                    $this->db->where("ContestID", $RunContVal['ContestID']);
                    $this->db->where("UserID", $Row['UserID']);
                    $QueryTeam = $this->db->get();
                    if ($QueryTeam->num_rows() > 0) {
                       $GetUserTeams = $QueryTeam->result_array();
                       foreach($GetUserTeams as $Value){
                            /* get Player Points Data */
                            $this->db->select("SUM(Points) as TotalPoints,UserTeamID");
                            $this->db->from('sports_users_team_players_weekly');
                            $this->db->where("UserTeamID", $Value['UserTeamID']);
                            $Query = $this->db->get();
                            $TotalPoints = $Query->row_array();
                            $UserTotalPoints += $TotalPoints['TotalPoints'];
                            /* Update Player Points Data */
                            $this->db->where('UserTeamID', $Value['UserTeamID']);
                            $this->db->where('ContestID', $RunContVal['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams_weekly', array('TotalPoints' =>$TotalPoints['TotalPoints']));
                       }
                    }

                    /** update player points current week table **/
                    $this->db->select("WeekID,UserTeamID,UserID");
                    $this->db->from('sports_users_teams');
                    $this->db->where("ContestID", $RunContVal['ContestID']);
                    $this->db->where("UserID", $Row['UserID']);
                    $QueryTeam = $this->db->get();
                    if ($QueryTeam->num_rows() > 0) {
                       $GetUserTeams = $QueryTeam->result_array();
                       foreach($GetUserTeams as $Value){
                            /* get Player Points Data */
                            $this->db->select("SUM(Points) as TotalPoints,UserTeamID");
                            $this->db->from('sports_users_team_players');
                            $this->db->where("UserTeamID", $Value['UserTeamID']);
                            $Query = $this->db->get();
                            $TotalPoints = $Query->row_array();
                            $UserTotalPoints += $TotalPoints['TotalPoints'];
                            /* Update Player Points Data */
                            $this->db->where('UserTeamID', $Value['UserTeamID']);
                            $this->db->where('ContestID', $RunContVal['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', array('TotalPoints' =>$TotalPoints['TotalPoints']));
                       }
                    }

                    /* Update Player Points Data */
                    $this->db->where('UserID', $Row['UserID']);
                    $this->db->where('ContestID', $RunContVal['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' =>$UserTotalPoints));
               }
            }
            $this->updateRankByContestDraft($RunContVal['ContestID']);
            $this->updateRankByContestDraftSessionWeekly($RunContVal['ContestID']);
        }
    }

    /*
     Description: To get player points
    */

    function updateDailyWeeklyUserTeamPoints(){
        /*Get Running Contest */ 
        $this->db->select("SeriesID,ContestID,WeekStart as WeekID,PrivatePointScoring");
        $this->db->from('tbl_entity as E, sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration !=", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 2);
        $Query = $this->db->get();
        $getRunningContest = $Query->result_array();
        foreach ($getRunningContest as $key => $RunContVal) {
            /* get Player Points Data */
            $this->db->select("UserID,ContestID,TotalPoints");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $RunContVal['ContestID']);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
               $GetJoinedUser = $Query->result_array();
               foreach($GetJoinedUser as $Row){

                    /** update player points current week table **/
                    $this->db->select("WeekID,UserTeamID,UserID");
                    $this->db->from('sports_users_teams');
                    $this->db->where("ContestID", $RunContVal['ContestID']);
                    $this->db->where("UserID", $Row['UserID']);
                    $QueryTeam = $this->db->get();
                    if ($QueryTeam->num_rows() > 0) {
                       $GetUserTeams = $QueryTeam->result_array();
                       foreach($GetUserTeams as $Value){
                            /* get Player Points Data */
                            $this->db->select("SUM(Points) as TotalPoints,UserTeamID");
                            $this->db->from('sports_users_team_players');
                            $this->db->where("UserTeamID", $Value['UserTeamID']);
                            $Query = $this->db->get();
                            $TotalPoints = $Query->row_array();
                            /* Update Player Points Data */
                            $this->db->where('UserTeamID', $Value['UserTeamID']);
                            $this->db->where('ContestID', $RunContVal['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', array('TotalPoints' =>$TotalPoints['TotalPoints']));
                       }
                    }
               }
            }
        }
    }

        /*
      Description: To update rank
     */
    function updateRankByContestDraftSessionWeekly($ContestID) {

        /** update player points weekly table **/
        $this->db->select("WeekID,UserTeamID,UserID");
        $this->db->from('sports_users_teams_weekly');
        $this->db->where("ContestID", $ContestID);
        $this->db->group_by('WeekID');
        $QueryTeam = $this->db->get();
        if ($QueryTeam->num_rows() > 0) {
        $GetUserTeams = $QueryTeam->result_array();
            foreach($GetUserTeams as $Value){
                $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                             ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                             FROM sports_users_teams_weekly WHERE sports_users_teams_weekly.ContestID = '" . $ContestID . "' AND sports_users_teams_weekly.WeekID='".$Value['WeekID']."')) AS UserRank,ContestID,UserTeamID,sports_users_teams_weekly.UserID,UserTeamID
                             FROM sports_users_teams_weekly,tbl_users 
                             WHERE sports_users_teams_weekly.ContestID = '" . $ContestID . "' AND sports_users_teams_weekly.WeekID='".$Value['WeekID']."' AND tbl_users.UserID = sports_users_teams_weekly.UserID
                         ");
                $results = $query->result_array();

                if (!empty($results)) {
                    $this->db->trans_start();
                    foreach ($results as $rows) {
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('UserID', $rows['UserID']);
                        $this->db->where('UserTeamID', $rows['UserTeamID']);
                        $this->db->limit(1);
                        $this->db->update('sports_users_teams_weekly', array('Rank' => $rows['UserRank']));
                    }
                    $this->db->trans_complete();
                }
            }
        }
    }

    /*
      Description: To get matches live score(FOOTBALL API NCAAF)
     */

    function getMatchesScoreLiveNcaaf($CronID) {
        return true;
        ini_set('max_execution_time', 120);
        /* Get series data */

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesYear,GameSportsType,CurrentWeek', array('StatusID' => 2, "GameSportsType" => "Ncaaf"), true, 0);
        if (!$SeriesData) {
            exit;
        }
        $MatchGroup = array();
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            $MatchGroup['CollegeFootballRegularSeason'] = $SeriesValue['SeriesYear'];
            $MatchGroup['CollegeFootballPower5RegularSeason'] = $SeriesValue['SeriesYear'] . "POST";
            foreach ($MatchGroup as $Group) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/scores/json/TeamGameStatsByWeek/' . $Group . '/' . $SeriesValue['CurrentWeek'] . '?key=', 'ncaaf');
                if (!$Response) {
                    continue;
                }
                foreach ($Response as $Rows) {
                    $GameKey = $Rows['GameKey'];
                    $ScoreID = $Rows['ScoreID'];
                    $MatchesData = $this->getMatches('MatchID,ScoreIDLive,MatchIDLive,SeriesID,WeekID,SeasonType,MatchNo,TeamIDLocal,TeamIDVisitor,TeamIDLiveLocal,TeamIDLiveVisitor', array("GameSportsType" => "Ncaaf", "SeriesID" => $SeriesValue['SeriesID'], "GameKey" => $GameKey, "ScoreID" => $ScoreID), false, 0);
                    if (!$MatchesData) {
                        exit;
                    }
                    $MatchesAPIData = array(
                        'MatchScoreDetails' => json_encode($Rows),
                        'LastUpdatedOn' => date('Y-m-d H:i:s'),
                    );
                    $this->db->where('MatchID', $MatchesData['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', $MatchesAPIData);

                    $this->calculateMatchTeamPointsNcaaf($MatchesData['MatchID'], $Rows);

                    /** get current week contest * */
                    $this->db->select("C.ContestID");
                    $this->db->from('sports_contest C, tbl_entity E');
                    $this->db->where("C.ContestID", "E.EntityID", FALSE);
                    $this->db->where("C.GameType", "Ncaaf");
                    $this->db->where("C.WeekStart", $SeriesValue['CurrentWeek']);
                    $this->db->where("E.StatusID", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $Contests = $Query->result_array();
                        foreach ($Contests as $Contest) {
                            $this->db->where('EntityID', $Contest['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_entity', array('StatusID' => 2));
                        }
                    }
                    if (isset($Rows['IsGameOver']) && $Rows['IsGameOver']) {
                        $this->db->where('EntityID', $MatchesData['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('StatusID' => 5));
                    }
                }
            }
        }
    }

    /*
      Description: To calculate matches team points(FOOTBALL API NFL)
     */

    function calculateMatchTeamPointsNfl($MatchID, $Score) {
        ini_set('max_execution_time', 120);
        /* Get series data */

        $AwayTeamPoints = 0;
        $HomeTeamPoints = 0;
        $AwayTeamPointData = array();
        $HomeTeamPointData = array();
        $HomeTeamPointData['TotalPoints'] = 0;
        $AwayTeamPointData['TotalPoints'] = 0;
        $HomeTeamPointData['Win'] = 0;
        $AwayTeamPointData['Win'] = 0;
        $HomeTeamPointData['Loss'] = 0;
        $AwayTeamPointData['Loss'] = 0;
        $HomeTeamPointData['Tie'] = 0;
        $AwayTeamPointData['Tie'] = 0;
        $HomeTeamPointData['ScorePoints'] = 0;
        $AwayTeamPointData['ScorePoints'] = 0;
        $AwayTeamPointData['Score'] = 0;
        $HomeTeamPointData['Score'] = 0;
        $HomeTeamPointData['PointDifference'] = 0;
        $AwayTeamPointData['PointDifference'] = 0;
        $HomeTeamPointData['DifferenceScorePoint'] = 0;
        $AwayTeamPointData['DifferenceScorePoint'] = 0;
        $HomeTeamPointData['OffensiveYards'] = 0;
        $AwayTeamPointData['OffensiveYards'] = 0;
        $HomeTeamPointData['OffensiveYardsScorePoints'] = 0;
        $AwayTeamPointData['OffensiveYardsScorePoints'] = 0;
        $HomeTeamPointData['DefensiveYards'] = 0;
        $AwayTeamPointData['DefensiveYards'] = 0;
        $HomeTeamPointData['DefensiveYardsScorePoints'] = 0;
        $AwayTeamPointData['DefensiveYardsScorePoints'] = 0;

        $AwayTeamKey = trim($Score['AwayTeam']);
        $HomeTeamKey = trim($Score['HomeTeam']);
        $AwayScore = $AwayTeamPointData['Score'] = $Score['AwayScore'];
        $HomeScore = $HomeTeamPointData['Score'] = $Score['HomeScore'];
        $TotalScore = $Score['TotalScore'];
        $AwayOffensiveYards = $Score['AwayOffensiveYards'];
        $HomeOffensiveYards = $Score['HomeOffensiveYards'];


        /** Team Win Points * */
        if ($HomeScore != $AwayScore) {
            if ($HomeScore > $AwayScore) {
                /** home team points * */
                $HomeTeamPoints += 10;
                $HomeTeamPointData['Score'] = $HomeScore;
                $HomeTeamPointData['Win'] = 1;
                $HomeTeamPointData['ScorePoints'] = 10;
                $AwayTeamPointData['Loss'] = 1;

                /** Home Win Point Difference * */
                $PointDifference = $HomeScore - $AwayScore;
                $TotalPoints = $PointDifference * 0.5;
                $HomeTeamPoints += $TotalPoints;
                $HomeTeamPointData['PointDifference'] = $PointDifference;
                $HomeTeamPointData['DifferenceScorePoint'] = $TotalPoints;

                /** Away Lose Point Difference * */
                $AwayTeamPoints -= $TotalPoints;
                $AwayTeamPointData['PointDifference'] = $PointDifference;
                $AwayTeamPointData['DifferenceScorePoint'] = "-" . $TotalPoints;
            } else {
                /** away team points * */
                $AwayTeamPoints += 10;
                $AwayTeamPointData['Score'] = $AwayScore;
                $AwayTeamPointData['Win'] = 1;
                $AwayTeamPointData['ScorePoints'] = 10;
                $HomeTeamPointData['Loss'] = 1;

                /** Away Win Point Difference * */
                $PointDifference = $AwayScore - $HomeScore;
                $TotalPoints = $PointDifference * 0.5;
                $AwayTeamPoints += $TotalPoints;
                $AwayTeamPointData['PointDifference'] = $PointDifference;
                $AwayTeamPointData['DifferenceScorePoint'] = $TotalPoints;

                /** Home Lose Point Difference * */
                $HomeTeamPoints -= $TotalPoints;
                $HomeTeamPointData['PointDifference'] = $PointDifference;
                $HomeTeamPointData['DifferenceScorePoint'] = "-" . $TotalPoints;
            }
        } else {
            $HomeTeamPointData['Tie'] = 1;
            $AwayTeamPointData['Tie'] = 1;
            /** home team points * */
            $HomeTeamPointData['Score'] = $HomeScore;
            $HomeTeamPointData['Win'] = 0;
            $HomeTeamPointData['ScorePoints'] = 0;

            /** away team points * */
            $AwayTeamPointData['Score'] = $HomeScore;
            $AwayTeamPointData['Win'] = 0;
            $AwayTeamPointData['ScorePoints'] = 0;
        }

        /** Away Team Total Offensive Yards Gained  * */
        if ($AwayOffensiveYards > 0) {
            $AwayTeamPointData['OffensiveYards'] = $AwayOffensiveYards;
            $YardsPoint = $AwayOffensiveYards * 0.1;
            $AwayTeamPointData['OffensiveYardsScorePoints'] = $YardsPoint;
            $AwayTeamPoints += $YardsPoint;

            /** Home Team Total Defensive Yards Allowed  * */
            $HomeTeamPointData['DefensiveYards'] = $AwayOffensiveYards;
            $YardsPoint = $AwayOffensiveYards * 0.05;
            $HomeTeamPointData['DefensiveYardsScorePoints'] = "-" . $YardsPoint;
            $HomeTeamPoints -= $YardsPoint;
        }

        /** Home Team Total Offensive Yards Gained  * */
        if ($HomeOffensiveYards > 0) {
            $HomeTeamPointData['OffensiveYards'] = $HomeOffensiveYards;
            $YardsPoint = $HomeOffensiveYards * 0.1;
            $HomeTeamPointData['OffensiveYardsScorePoints'] = $YardsPoint;
            $HomeTeamPoints += $YardsPoint;

            /** Away Team Total Defensive Yards Allowed  * */
            $AwayTeamPointData['DefensiveYards'] = $HomeOffensiveYards;
            $YardsPoint = $HomeOffensiveYards * 0.05;
            $AwayTeamPointData['DefensiveYardsScorePoints'] = "-" . $YardsPoint;
            $AwayTeamPoints -= $YardsPoint;
        }

        $HomeTeamPointData['TotalPoints'] = $HomeTeamPoints;
        $AwayTeamPointData['TotalPoints'] = $AwayTeamPoints;

        /* Get Away team data */
        $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType='Nfl' AND T.TeamKey='" . $AwayTeamKey . "'");
        $AwayTeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : FALSE;

        /* Get Home team data */
        $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType='Nfl' AND T.TeamKey='" . $HomeTeamKey . "'");
        $HomeTeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : FALSE;
        /** Away team update * */
        if ($AwayTeamID) {
            $ScoreTeamData = array(
                'TotalPoints' => $AwayTeamPoints,
                'PointsData' => json_encode($AwayTeamPointData)
            );
            $this->db->where('MatchID', $MatchID);
            $this->db->where('TeamID', $AwayTeamID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $ScoreTeamData);
        }

        /** Home team update * */
        if ($HomeTeamID) {
            $ScoreTeamData = array(
                'TotalPoints' => $HomeTeamPoints,
                'PointsData' => json_encode($HomeTeamPointData)
            );
            $this->db->where('MatchID', $MatchID);
            $this->db->where('TeamID', $HomeTeamID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $ScoreTeamData);
        }

        return true;
    }

    /*
      Description: To calculate matches team points(FOOTBALL API NCAAF)
     */

    function calculateMatchTeamPointsNcaaf($MatchID, $Score) {
        ini_set('max_execution_time', 120);
        /* Get series data */

        $AwayTeamPoints = 0;
        $HomeTeamPoints = 0;
        $AwayTeamPointData = array();
        $HomeTeamPointData = array();
        $HomeTeamPointData['TotalPoints'] = 0;
        $AwayTeamPointData['TotalPoints'] = 0;
        $HomeTeamPointData['Win'] = 0;
        $AwayTeamPointData['Win'] = 0;
        $HomeTeamPointData['Loss'] = 0;
        $AwayTeamPointData['Loss'] = 0;
        $HomeTeamPointData['Tie'] = 0;
        $AwayTeamPointData['Tie'] = 0;
        $HomeTeamPointData['ScorePoints'] = 0;
        $AwayTeamPointData['ScorePoints'] = 0;
        $AwayTeamPointData['Score'] = 0;
        $HomeTeamPointData['Score'] = 0;
        $HomeTeamPointData['PointDifference'] = 0;
        $AwayTeamPointData['PointDifference'] = 0;
        $HomeTeamPointData['DifferenceScorePoint'] = 0;
        $AwayTeamPointData['DifferenceScorePoint'] = 0;
        $HomeTeamPointData['OffensiveYards'] = 0;
        $AwayTeamPointData['OffensiveYards'] = 0;
        $HomeTeamPointData['OffensiveYardsScorePoints'] = 0;
        $AwayTeamPointData['OffensiveYardsScorePoints'] = 0;
        $HomeTeamPointData['DefensiveYards'] = 0;
        $AwayTeamPointData['DefensiveYards'] = 0;
        $HomeTeamPointData['DefensiveYardsScorePoints'] = 0;
        $AwayTeamPointData['DefensiveYardsScorePoints'] = 0;

        $AwayTeamKey = trim($Score['AwayTeam']);
        $HomeTeamKey = trim($Score['HomeTeam']);
        $AwayScore = $AwayTeamPointData['Score'] = $Score['AwayScore'];
        $HomeScore = $HomeTeamPointData['Score'] = $Score['HomeScore'];
        $TotalScore = $Score['TotalScore'];
        $AwayOffensiveYards = $Score['AwayOffensiveYards'];
        $HomeOffensiveYards = $Score['HomeOffensiveYards'];


        /** Team Win Points * */
        if ($HomeScore != $AwayScore) {
            if ($HomeScore > $AwayScore) {
                /** home team points * */
                $HomeTeamPoints += 10;
                $HomeTeamPointData['Score'] = $HomeScore;
                $HomeTeamPointData['Win'] = 1;
                $HomeTeamPointData['ScorePoints'] = 10;
                $AwayTeamPointData['Loss'] = 1;

                /** Home Win Point Difference * */
                $PointDifference = $HomeScore - $AwayScore;
                $TotalPoints = $PointDifference * 0.5;
                $HomeTeamPoints += $TotalPoints;
                $HomeTeamPointData['PointDifference'] = $PointDifference;
                $HomeTeamPointData['DifferenceScorePoint'] = $TotalPoints;

                /** Away Lose Point Difference * */
                $AwayTeamPoints -= $TotalPoints;
                $AwayTeamPointData['PointDifference'] = $PointDifference;
                $AwayTeamPointData['DifferenceScorePoint'] = "-" . $TotalPoints;
            } else {
                /** away team points * */
                $AwayTeamPoints += 10;
                $AwayTeamPointData['Score'] = $AwayScore;
                $AwayTeamPointData['Win'] = 1;
                $AwayTeamPointData['ScorePoints'] = 10;
                $HomeTeamPointData['Loss'] = 1;

                /** Away Win Point Difference * */
                $PointDifference = $AwayScore - $HomeScore;
                $TotalPoints = $PointDifference * 0.5;
                $AwayTeamPoints += $TotalPoints;
                $AwayTeamPointData['PointDifference'] = $PointDifference;
                $AwayTeamPointData['DifferenceScorePoint'] = $TotalPoints;

                /** Home Lose Point Difference * */
                $HomeTeamPoints -= $TotalPoints;
                $HomeTeamPointData['PointDifference'] = $PointDifference;
                $HomeTeamPointData['DifferenceScorePoint'] = "-" . $TotalPoints;
            }
        } else {
            $HomeTeamPointData['Tie'] = 1;
            $AwayTeamPointData['Tie'] = 1;
            /** home team points * */
            $HomeTeamPointData['Score'] = $HomeScore;
            $HomeTeamPointData['Win'] = 0;
            $HomeTeamPointData['ScorePoints'] = 0;

            /** away team points * */
            $AwayTeamPointData['Score'] = $HomeScore;
            $AwayTeamPointData['Win'] = 0;
            $AwayTeamPointData['ScorePoints'] = 0;
        }

        /** Away Team Total Offensive Yards Gained  * */
        if ($AwayOffensiveYards > 0) {
            $AwayTeamPointData['OffensiveYards'] = $AwayOffensiveYards;
            $YardsPoint = $AwayOffensiveYards * 0.1;
            $AwayTeamPointData['OffensiveYardsScorePoints'] = $YardsPoint;
            $AwayTeamPoints += $YardsPoint;

            /** Home Team Total Defensive Yards Allowed  * */
            $HomeTeamPointData['DefensiveYards'] = $AwayOffensiveYards;
            $YardsPoint = $AwayOffensiveYards * 0.05;
            $HomeTeamPointData['DefensiveYardsScorePoints'] = "-" . $YardsPoint;
            $HomeTeamPoints -= $YardsPoint;
        }

        /** Home Team Total Offensive Yards Gained  * */
        if ($HomeOffensiveYards > 0) {
            $HomeTeamPointData['OffensiveYards'] = $HomeOffensiveYards;
            $YardsPoint = $HomeOffensiveYards * 0.1;
            $HomeTeamPointData['OffensiveYardsScorePoints'] = $YardsPoint;
            $HomeTeamPoints += $YardsPoint;

            /** Away Team Total Defensive Yards Allowed  * */
            $AwayTeamPointData['DefensiveYards'] = $HomeOffensiveYards;
            $YardsPoint = $HomeOffensiveYards * 0.05;
            $AwayTeamPointData['DefensiveYardsScorePoints'] = "-" . $YardsPoint;
            $AwayTeamPoints -= $YardsPoint;
        }

        $HomeTeamPointData['TotalPoints'] = $HomeTeamPoints;
        $AwayTeamPointData['TotalPoints'] = $AwayTeamPoints;

        /* Get Away team data */
        $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType='Ncaaf' AND T.TeamKey='" . $AwayTeamKey . "'");
        $AwayTeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : FALSE;

        /* Get Home team data */
        $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType='Ncaaf' AND T.TeamKey='" . $HomeTeamKey . "'");
        $HomeTeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : FALSE;
        /** Away team update * */
        if ($AwayTeamID) {
            $ScoreTeamData = array(
                'TotalPoints' => $AwayTeamPoints,
                'PointsData' => json_encode($AwayTeamPointData)
            );
            $this->db->where('MatchID', $MatchID);
            $this->db->where('TeamID', $AwayTeamID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $ScoreTeamData);
        }

        /** Home team update * */
        if ($HomeTeamID) {
            $ScoreTeamData = array(
                'TotalPoints' => $HomeTeamPoints,
                'PointsData' => json_encode($HomeTeamPointData)
            );
            $this->db->where('MatchID', $MatchID);
            $this->db->where('TeamID', $HomeTeamID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $ScoreTeamData);
        }

        return true;
    }

    /*
      Description: To calculate user team points(FOOTBALL API NFL)
     */

    function contestUserTeamCalculetePointsNfl($CronID) {
        ini_set('max_execution_time', 120);

        /* Get series data */
        $CurrentWeek = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/nfl/scores/json/CurrentWeek?key=', 'nfl');
        //$CurrentWeek = 3;
        if ($CurrentWeek > 0) {
            /** get running contest * */
            $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
            $this->db->from('sports_contest C,tbl_entity E');
            $this->db->where("E.EntityID", "C.ContestID", FALSE);
            $this->db->where("C.AuctionStatusID", 5);
            $this->db->where("E.StatusID", 2);
            $this->db->where("$CurrentWeek BETWEEN `WeekStart` AND `WeekEnd`");
            $this->db->where("E.GameSportsType", "Nfl");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Contests = $Query->result_array();
                foreach ($Contests as $Contest) {
                    /** get running contest week team * */
                    $this->db->select('T.UserTeamID,T.WeekID');
                    $this->db->from('sports_users_teams T');
                    $this->db->where("T.ContestID", $Contest['ContestID']);
                    $this->db->where("T.WeekID", $CurrentWeek);
                    $this->db->where("T.AuctionTopPlayerSubmitted", "Yes");
                    $this->db->where("T.IsPreTeam", "No");
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $UserTeams = $Query->result_array();
                        foreach ($UserTeams as $Team) {
                            $TeamTotalPoints = 0;
                            $Win = 0;
                            $Loss = 0;
                            $Tie = 0;
                            /** user team list * */
                            $this->db->select('TP.SeriesID,TP.UserTeamID,TP.TeamID,TP.TeamPlayingStatus,TP.Points');
                            $this->db->from('sports_users_team_players TP');
                            $this->db->where("TP.UserTeamID", $Team['UserTeamID']);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $UserTeamList = $Query->result_array();
                                foreach ($UserTeamList as $Rows) {
                                    /** user team points * */
                                    $this->db->select('SeriesID,TeamID,WeekID,TotalPoints,PointsData');
                                    $this->db->from('sports_team_players');
                                    $this->db->where("SeriesID", $Rows['SeriesID']);
                                    $this->db->where("TeamID", $Rows['TeamID']);
                                    $this->db->where("WeekID", $Team['WeekID']);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $TeamPoint = $Query->row_array();
                                        $PointsData = json_decode($TeamPoint['PointsData'], TRUE);
                                        if ($Rows['TeamPlayingStatus'] == "Play") {
                                            $TeamTotalPoints += $TeamPoint['TotalPoints'];
                                            $Win += $PointsData['Win'];
                                            $Loss += $PointsData['Loss'];
                                            $Tie += $PointsData['Tie'];
                                        }
                                        /** update team point * */
                                        $ScoreTeamData = array(
                                            'Points' => $TeamPoint['TotalPoints'],
                                        );
                                        $this->db->where('UserTeamID', $Team['UserTeamID']);
                                        $this->db->where('TeamID', $TeamPoint['TeamID']);
                                        $this->db->limit(1);
                                        $this->db->update('sports_users_team_players', $ScoreTeamData);
                                    }
                                }
                            }

                            /** update team point * */
                            $ScoreTeamData = array(
                                'TotalPoints' => $TeamTotalPoints,
                                'Win' => $Win,
                                'Loss' => $Loss,
                                'Tie' => $Tie,
                            );
                            $this->db->where('UserTeamID', $Team['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', $ScoreTeamData);
                        }

                        /** contest week rank * */
                        $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_users_teams WHERE ContestID = '" . $Contest['ContestID'] . "' AND WeekID = '" . $CurrentWeek . "')) AS UserRank,ContestID,UserTeamID,TotalPoints
                         FROM sports_users_teams 
                         WHERE ContestID = '" . $Contest['ContestID'] . "'  AND WeekID = '" . $CurrentWeek . "'");
                        $AllRanks = $query->result_array();
                        if (!empty($AllRanks)) {
                            /** update team rank * */
                            foreach ($AllRanks as $Rank) {
                                $ScoreTeamData = array(
                                    'Rank' => $Rank['UserRank'],
                                );
                                $this->db->where('UserTeamID', $Rank['UserTeamID']);
                                $this->db->where('ContestID', $Rank['ContestID']);
                                $this->db->where('WeekID', $CurrentWeek);
                                $this->db->limit(1);
                                $this->db->update('sports_users_teams', $ScoreTeamData);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description: To calculate user team points(FOOTBALL API NCAAF)
     */

    function contestUserTeamCalculetePointsNcaaf($CronID) {
        ini_set('max_execution_time', 120);

        /* Get series data */
        $CurrentWeek = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/scores/json/CurrentWeek?key=', 'ncaaf');
        //$CurrentWeek = 3;
        if ($CurrentWeek > 0) {
            /** get running contest * */
            $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
            $this->db->from('sports_contest C,tbl_entity E');
            $this->db->where("E.EntityID", "C.ContestID", FALSE);
            $this->db->where("C.AuctionStatusID", 5);
            $this->db->where("E.StatusID", 2);
            $this->db->where("$CurrentWeek BETWEEN `WeekStart` AND `WeekEnd`");
            $this->db->where("E.GameSportsType", "Ncaaf");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Contests = $Query->result_array();
                foreach ($Contests as $Contest) {
                    /** get running contest week team * */
                    $this->db->select('T.UserTeamID,T.WeekID');
                    $this->db->from('sports_users_teams T');
                    $this->db->where("T.ContestID", $Contest['ContestID']);
                    $this->db->where("T.WeekID", $CurrentWeek);
                    $this->db->where("T.AuctionTopPlayerSubmitted", "Yes");
                    $this->db->where("T.IsPreTeam", "No");
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $UserTeams = $Query->result_array();
                        foreach ($UserTeams as $Team) {
                            $TeamTotalPoints = 0;
                            $Win = 0;
                            $Loss = 0;
                            $Tie = 0;
                            /** user team list * */
                            $this->db->select('TP.SeriesID,TP.UserTeamID,TP.TeamID,TP.TeamPlayingStatus,TP.Points');
                            $this->db->from('sports_users_team_players TP');
                            $this->db->where("TP.UserTeamID", $Team['UserTeamID']);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $UserTeamList = $Query->result_array();
                                foreach ($UserTeamList as $Rows) {
                                    /** user team points * */
                                    $this->db->select('SeriesID,TeamID,WeekID,TotalPoints,PointsData');
                                    $this->db->from('sports_team_players');
                                    $this->db->where("SeriesID", $Rows['SeriesID']);
                                    $this->db->where("TeamID", $Rows['TeamID']);
                                    $this->db->where("WeekID", $Team['WeekID']);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $TeamPoint = $Query->row_array();
                                        $PointsData = json_decode($TeamPoint['PointsData'], TRUE);
                                        if ($Rows['TeamPlayingStatus'] == "Play") {
                                            $TeamTotalPoints += $TeamPoint['TotalPoints'];
                                            $Win += $PointsData['Win'];
                                            $Loss += $PointsData['Loss'];
                                            $Tie += $PointsData['Tie'];
                                        }

                                        /** update team point * */
                                        $ScoreTeamData = array(
                                            'Points' => $TeamPoint['TotalPoints'],
                                        );
                                        $this->db->where('UserTeamID', $Team['UserTeamID']);
                                        $this->db->where('TeamID', $TeamPoint['TeamID']);
                                        $this->db->limit(1);
                                        $this->db->update('sports_users_team_players', $ScoreTeamData);
                                    }
                                }
                            }

                            /** update team point * */
                            $ScoreTeamData = array(
                                'TotalPoints' => $TeamTotalPoints,
                                'Win' => $Win,
                                'Loss' => $Loss,
                                'Tie' => $Tie,
                            );
                            $this->db->where('UserTeamID', $Team['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', $ScoreTeamData);
                        }

                        /** contest week rank * */
                        $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_users_teams WHERE ContestID = '" . $Contest['ContestID'] . "' AND WeekID = '" . $CurrentWeek . "')) AS UserRank,ContestID,UserTeamID,TotalPoints
                         FROM sports_users_teams 
                         WHERE ContestID = '" . $Contest['ContestID'] . "'  AND WeekID = '" . $CurrentWeek . "'");
                        $AllRanks = $query->result_array();
                        if (!empty($AllRanks)) {
                            /** update team rank * */
                            foreach ($AllRanks as $Rank) {
                                $ScoreTeamData = array(
                                    'Rank' => $Rank['UserRank'],
                                );
                                $this->db->where('UserTeamID', $Rank['UserTeamID']);
                                $this->db->where('ContestID', $Rank['ContestID']);
                                $this->db->where('WeekID', $CurrentWeek);
                                $this->db->limit(1);
                                $this->db->update('sports_users_teams', $ScoreTeamData);
                            }
                        }
                    }
                }
            }
        }
    }

        /*
      Description: To calculate user team points(FOOTBALL API NFL)
     */

    function contestUserTeamCalculetePointsGoalServe($CronID) {

        ini_set('max_execution_time', 300);
        /* Get Matches Live */
        $LiveMatcheContest = $this->SnakeDrafts_model->getContests('ContestID,WeekStart,ContestDuration,DailyDate', array('StatusID' => 2,"GameSportsType" => "Nfl"), true, 0);
        if (!$LiveMatcheContest) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($LiveMatcheContest['Data']['Records'] as $Value) {

            if($Value['ContestDuration'] == 'Daily'){
                $MatchesData = $this->getMatches('MatchID', array("GameSportsType" => "Nfl", "StatusID" => array(2,5),'MatchCurrentDate'=>$Value['DailyDate']),true,0);
            }else{
                $MatchesData = $this->getMatches('MatchID', array("GameSportsType" => "Nfl", "StatusID" => array(2,5),'WeekID'=>$Value['WeekStart']),true,0);  
            }
            if($MatchesData['Data']['TotalRecords'] <= 0) continue;
            $MatchIDs = array_column($MatchesData['Data']['Records'], "MatchID");
            $ContestID = $Value['ContestID'];

            /* To Get Match Players */
            $Contests = $this->SnakeDrafts_model->getJoinedContests('ContestID,UserID,UserTeamID', array('ContestID' => $ContestID), true, 0);
            if (!empty($Contests['Data']['Records'])) {

                foreach ($Contests['Data']['Records'] as $Row) {

                    /* Player Points Multiplier */
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2, 'Player' => 1);
                    $UserTeamPlayers = $this->SnakeDrafts_model->getPlayersMyTeam('PlayerRoleShort,TeamName,UserTeamGUID,PlayerID,UserTeamID', array('ContestID' => $Row['ContestID'],'SessionUserID' => $Row['UserID'],'MySquadPlayer'=>'Yes','IsPreTeam'=>'No','PlayerBidStatus'=>'Yes'), true, 0);

                    if(empty($UserTeamPlayers)) continue;
                    $TotalPointsSum=0;
                    foreach ($UserTeamPlayers['Data']['Records'] as $UserTeamValue) {
                        $this->db->select('SUM(TotalPoints) as TotalPoint');
                        $this->db->from('sports_team_players');
                        $this->db->where('PlayerID', $UserTeamValue['PlayerID']);
                        $this->db->where_in('MatchID', $MatchIDs);
                        $Query = $this->db->get();
                        $Points = $Query->row_array()['TotalPoint'];
                        $TotalPointsSum += $Points;
                    
                        /* Update Player Points */
                        $this->db->where('UserTeamID', $UserTeamValue['UserTeamID']);
                        $this->db->where('PlayerID', $UserTeamValue['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('sports_users_team_players', array('Points' => $Points));
                    }
                    /* Update Player Total Points */
                    $this->db->where('UserID', $Row['UserID']);
                    $this->db->where('ContestID', $Row['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' => $TotalPointsSum, 'ModifiedDate' => date('Y-m-d H:i:s')));
                }
            }
            $this->updateRankByContestDraft($ContestID);
        }
        
    }

    /*
      Description: To update rank
     */
    function updateRankByContestDraft($ContestID) {
        if (!empty($ContestID)) {
            $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_contest_join WHERE sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID,sports_contest_join.UserID
                         FROM sports_contest_join,tbl_users 
                         WHERE sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = sports_contest_join.UserID
                     ");
            $results = $query->result_array();
            if (!empty($results)) {
                $this->db->trans_start();
                foreach ($results as $rows) {
                    $this->db->where('ContestID', $rows['ContestID']);
                    $this->db->where('UserID', $rows['UserID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('UserRank' => $rows['UserRank']));
                }
                $this->db->trans_complete();
            }
        }
    }

    /*
      Description: To set players data by team (FOOTBALL API NCAAF)
     */

    function getPlayersLiveByTeamNcaaf($CronID) {
        ini_set('max_execution_time', 180);

        /* Get team data */
        $Query = $this->db->query('SELECT T.TeamID,T.TeamIDLive,T.TeamKey FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType="Ncaaf"');
        $Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
        if (!$Teams) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($Teams as $Team) {
            $TeamIDLive = $Team['TeamIDLive'];
            $TeamID = $Team['TeamID'];
            $TeamKey = $Team['TeamKey'];
            $Response = $this->callSportsAPI(SPORTS_API_URL_FOOTBALL . '/v3/cfb/stats/json/Players/' . $TeamKey . '?key=', 'ncaaf');
            if (empty($Response))
                continue;

            $PlayersAPIData = array();
            foreach ($Response as $Key => $Value) {
                $PlayerIDLive = $Value['PlayerID'];
                $Team = $Value['Team'];
                $PlayerName = $Value['FirstName'] . " " . $Value['FirstName'];
                $PlayerLiveRole = $Value['Position'];
                $Status = $Value['InjuryStatus'];
                $PositionCategory = $Value['PositionCategory'];
                $PlayerRole = "";

                /* player role system */
                $Roles = array('QB' => "QuarterBack", 'RB' => "RunningBack", 'FB' => "FullBack", 'WR' => "WideReceiver", 'TE' => "TightEnd",
                    'C' => "Center", 'G' => "Guard", 'OT' => "OffenseTackle", 'DE' => "DefenseEnd", 'DT' => "DefenseTackle", 'LB' => "LineBacker",
                    'CB' => "CornerBack", 'S' => "Safety", 'SS' => "Safety", 'PK' => "Placekicker", 'LS' => "LongSnapper",
                    'P' => "Punter", 'NT' => "DefenseEnd", 'OG' => "OffensiveGuard", 'OLB' => "OutsideLinebacker", 'OL' => "OutsideLinebacker", 'K' => "Kicker");
                $PlayerRole = (isset($Roles[strtoupper($PlayerLiveRole)])) ? $Roles[strtoupper($PlayerLiveRole)] : "Center";
                /* player posotion category */
                $Category = array("OFF" => "Offense", "DEF" => "Defense", "ST" => "Special Teams");

                $PlayerPositionCategory = $Category[$PositionCategory];
                /* check player role */
                if (!$PlayerRole)
                    continue;

                /* To check if player is already exist */
                $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.PlayerIDLive = ' . $PlayerIDLive . ' AND E.GameSportsType="Ncaaf" LIMIT 1');
                $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                if ($PlayerID)
                    continue;

                /* Add players to entity table and get EntityID. */
                $PlayerGUID = get_guid();
                $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2, "GameSportsType" => "Ncaaf"));
                $PlayersAPIData[] = array(
                    'TeamID' => $TeamID,
                    'PlayerID' => $PlayerID,
                    'PlayerGUID' => $PlayerGUID,
                    'PlayerIDLive' => $PlayerIDLive,
                    'PlayerName' => $PlayerName,
                    'PlayerRole' => $PlayerRole,
                    'Position' => $PlayerPositionCategory,
                    'PlayerBattingStats' => json_encode($Value)
                );
            }
            if (!empty($PlayersAPIData)) {
                $this->db->insert_batch('sports_players', $PlayersAPIData);
            }
        }
    }

    /*
      Description: To set players data match wise (Entity API)
     */

    function getPlayersLive($CronID, $MatchID = "") {
        ini_set('max_execution_time', 300);

        /* Get series data */
        if (!empty($MatchID)) {
            $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID', array('StatusID' => array(1), "MatchID" => $MatchID), true, 0);
        } else {
            $MatchData = $this->getMatches('MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor', array('StatusID' => array(1)), true, 1, 10);
        }
        // print_r($MatchData);exit;
        if (!$MatchData) {
            if (!empty($CronID)) {
                $this->db->where('CronID', $CronID);
                $this->db->limit(1);
                $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                exit;
            }
        }
        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper', 'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        foreach ($MatchData['Data']['Records'] as $Value) {

            $MatchID = $Value['MatchID'];
            $SeriesID = $Value['SeriesID'];
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $Value['SeriesIDLive'] . '/squads/' . $Value['MatchIDLive'] . '?token=');
            if (empty($Response['response']['squads']))
                continue;

            /* To check if any team is created */
            $TotalJoinedTeams = $this->db->query("SELECT COUNT(*) TotalJoinedTeams FROM `sports_users_teams` WHERE `MatchID` = " . $MatchID)->row()->TotalJoinedTeams;

            //print_r($Response['response']['squads']);exit;
            foreach ($Response['response']['squads'] as $SquadsValue) {
                $TeamID = $SquadsValue['team_id'];
                $Players = $SquadsValue['players'];
                $TeamPlayersData = array();
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $TeamID . '" LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (empty($TeamID)) {
                    /* Add team to entity table and get EntityID. */
                    $TeamDetails = $SquadsValue['team'];
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $SquadsValue['team_id'],
                        'TeamName' => $TeamDetails['title'],
                        'TeamNameShort' => strtoupper($TeamDetails['abbr'])
                    ));
                    $this->db->insert('sports_teams', $TeamData);
                }
                //echo $TeamID;exit;
                $this->db->trans_start();
                if (!empty($Players)) {
                    foreach ($Players as $Player) {
                        if (isset($Player['pid']) && !empty($Player['pid'])) {
                            /* To check if player is already exist */
                            $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = ' . $Player['pid'] . ' LIMIT 1');
                            $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                            if (!$PlayerID) {
                                /* Add players to entity table and get EntityID. */
                                $PlayerGUID = get_guid();
                                $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                                $PlayersAPIData = array(
                                    'PlayerID' => $PlayerID,
                                    'PlayerGUID' => $PlayerGUID,
                                    'PlayerIDLive' => $Player['pid'],
                                    'PlayerName' => $Player['title'],
                                    'PlayerSalary' => $Player['fantasy_player_rating'],
                                    'PlayerCountry' => ($Player['country']) ? strtoupper($Player['country']) : null,
                                    'PlayerBattingStyle' => ($Player['batting_style']) ? $Player['batting_style'] : null,
                                    'PlayerBowlingStyle' => ($Player['bowling_style']) ? $Player['bowling_style'] : null
                                );
                                $this->db->insert('sports_players', $PlayersAPIData);
                            } else {
                                $PlayersAPIData = array(
                                    'PlayerName' => $Player['title'],
                                    'PlayerSalary' => $Player['fantasy_player_rating'],
                                    'PlayerCountry' => ($Player['country']) ? strtoupper($Player['country']) : null,
                                    'PlayerBattingStyle' => ($Player['batting_style']) ? $Player['batting_style'] : null,
                                    'PlayerBowlingStyle' => ($Player['bowling_style']) ? $Player['bowling_style'] : null
                                );
                                $this->db->where('PlayerID', $PlayerID);
                                $this->db->update('sports_players', $PlayersAPIData);
                            }
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $SeriesID . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {
                                if (!empty($PlayerRolesArr[strtolower($Player['playing_role'])])) {
                                    $TeamPlayersData[] = array(
                                        'SeriesID' => $SeriesID,
                                        'MatchID' => $MatchID,
                                        'TeamID' => $TeamID,
                                        'PlayerID' => $PlayerID,
                                        'PlayerSalary' => $Player['fantasy_player_rating'],
                                        'IsPlaying' => "No",
                                        'PlayerRole' => $PlayerRolesArr[strtolower($Player['playing_role'])]
                                    );
                                }
                            } else {
                                if ($TotalJoinedTeams > 0) {
                                    continue;
                                }
                                /* Update Fantasy Points */
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('MatchID', $MatchID);
                                $this->db->where('TeamID', $TeamID);
                                $this->db->where('PlayerID', $PlayerID);
                                $this->db->limit(1);
                                $this->db->update('sports_team_players', array('PlayerSalary' => $Player['fantasy_player_rating'], 'PlayerRole' => $PlayerRolesArr[strtolower($Player['playing_role'])]));
                            }
                        }
                    }
                }

                if (!empty($TeamPlayersData)) {
                    $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
            }
        }
    }

    /*
      Description: To set player stats (Entity API)
     */

    function getPlayerStatsLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* To get All Player Stats Data */
        $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID', array('StatusID' => 5, 'PlayerStatsUpdate' => 'No', 'MatchCompleteDateTime' => date('Y-m-d H:i:s')), true, 0);
        if (!$MatchData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($MatchData['Data']['Records'] as $Value) {
            $PlayerData = $this->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
            if (empty($PlayerData))
                continue;

            foreach ($PlayerData['Data']['Records'] as $Player) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/players/' . $Player['PlayerIDLive'] . '/stats/?token=');
                $BattingStats = (!empty($Response['response']['batting'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'run4', 'run6', 'runs', 'balls', 'run50', 'notout', 'run100', 'strike', 'average', 'catches', 'highest', 'innings', 'matches', 'stumpings', 'match_id', 'inning_id'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'Fours', 'Sixes', 'Runs', 'Balls', 'Fifties', 'NotOut', 'Hundreds', 'StrikeRate', 'Average', 'Catches', 'HighestScore', 'Innings', 'Matches', 'Stumpings', 'MatchID', 'InningID'), json_encode($Response['response']['batting'])) : null;
                $BowlingStats = (!empty($Response['response']['bowling'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'match_id', 'inning_id', 'innings', 'matches', 'balls', 'overs', 'runs', 'wickets', 'bestinning', 'bestmatch', 'econ', 'average', 'strike', 'wicket4i', 'wicket5i', 'wicket10m'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'MatchID', 'InningID', 'Innings', 'Matches', 'Balls', 'Overs', 'Runs', 'Wickets', 'BestInning', 'BestMatch', 'Economy', 'Average', 'StrikeRate', 'FourPlusWicketsInSingleInning', 'FivePlusWicketsInSingleInning', 'TenPlusWicketsInSingleInning'), json_encode($Response['response']['bowling'])) : null;
                /* Update Player Stats */
                $PlayerStats = array(
                    'PlayerBattingStats' => $BattingStats,
                    'PlayerBowlingStats' => $BowlingStats,
                    'LastUpdatedOn' => date('Y-m-d H:i:s')
                );
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('sports_players', $PlayerStats);
            }

            $MatchUpdate = array(
                'PlayerStatsUpdate' => "Yes",
            );
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('sports_matches', $MatchUpdate);
        }
    }

    /*
      Description: To get match live score (Entity API)
     */

    function getMatchScoreLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID', array('Filter' => 'Yesterday', 'StatusID' => array(1, 2, 10)), true, 1, 20);
        //$LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID', array('MatchID' => '371671'), true, 1, 5);
        if (!$LiveMatches) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        $MatchStatus = array("live" => 2, "abandoned" => 8, "cancelled" => 3, "no result" => 9);
        $ContestStatus = array("live" => 2, "abandoned" => 5, "cancelled" => 3, "no result" => 5);
        $InningsStatus = array(1 => 'scheduled', 2 => 'completed', 3 => 'live', 4 => 'abandoned');

        foreach ($LiveMatches['Data']['Records'] as $Value) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/matches/' . $Value['MatchIDLive'] . '/scorecard/?token=');
            if (!empty($Response)) {
                if ($Response['status'] == "ok" && !empty($Response['response'])) {

                    $MatchStatusLive = strtolower($Response['response']['status_str']);
                    $MatchStatusLiveCheck = $Response['response']['status'];
                    $GameState = $Response['response']['game_state'];
                    $Verified = $Response['response']['verified'];
                    $PreSquad = $Response['response']['pre_squad'];
                    $StatusNote = strtolower($Response['response']['status_note']);
                    if ($GameState != 7 || $GameState != 6) {
                        if ($MatchStatusLiveCheck == 2 || $MatchStatusLiveCheck == 3) {
                            /** set is playing player 22 * */
                            $ResponsePlayerSquad = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/matches/' . $Value['MatchIDLive'] . '/squads/?token=');
                            if (!empty($ResponsePlayerSquad)) {
                                if ($ResponsePlayerSquad['status'] == 'ok') {
                                    $squadTeamA = $ResponsePlayerSquad['response']['teama']['squads'];
                                    $squadTeamB = $ResponsePlayerSquad['response']['teamb']['squads'];
                                    $PlayingPlayerIDs = array();
                                    foreach ($squadTeamA as $aRows) {
                                        if ($aRows['playing11'] == 'true') {
                                            $PlayingPlayerIDs[] = $aRows['player_id'];
                                        }
                                    }
                                    foreach ($squadTeamB as $bRows) {
                                        if ($bRows['playing11'] == 'true') {
                                            $PlayingPlayerIDs[] = $bRows['player_id'];
                                        }
                                    }
                                    if (count($PlayingPlayerIDs) > 20) {
                                        $PlayersIdsData = array();
                                        $PlayersData = $this->Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
                                        if ($PlayersData) {
                                            $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                                        }
                                        foreach ($PlayingPlayerIDs as $IsPlayer) {
                                            $this->db->where('MatchID', $Value['MatchID']);
                                            $this->db->where('PlayerID', $PlayersIdsData[$IsPlayer]);
                                            $this->db->limit(1);
                                            $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                                        }
                                    }
                                }
                            }

                            if ($MatchStatusLive == 'scheduled' || $MatchStatusLive == 'rescheduled')
                                continue;

                            if (empty($Response['response']['players']))
                                continue;

                            $LivePlayersData = array_column($Response['response']['players'], 'title', 'pid');
                            $MatchScoreDetails = $InningsData = array();
                            $MatchScoreDetails['StatusLive'] = $Response['response']['status_str'];
                            $MatchScoreDetails['StatusNote'] = $Response['response']['status_note'];
                            $MatchScoreDetails['TeamScoreLocal'] = array('Name' => $Response['response']['teama']['name'], 'ShortName' => $Response['response']['teama']['short_name'], 'LogoURL' => $Response['response']['teama']['logo_url'], 'Scores' => @$Response['response']['teama']['scores'], 'Overs' => @$Response['response']['teama']['overs']);
                            $MatchScoreDetails['TeamScoreVisitor'] = array('Name' => $Response['response']['teamb']['name'], 'ShortName' => $Response['response']['teamb']['short_name'], 'LogoURL' => $Response['response']['teamb']['logo_url'], 'Scores' => @$Response['response']['teamb']['scores'], 'Overs' => @$Response['response']['teamb']['overs']);
                            $MatchScoreDetails['MatchVenue'] = @$Response['response']['venue']['name'] . ", " . $Response['response']['venue']['location'];
                            $MatchScoreDetails['Result'] = @$Response['response']['result'];
                            $MatchScoreDetails['Toss'] = @$Response['response']['toss']['text'];
                            $MatchScoreDetails['ManOfTheMatchPlayer'] = @$Response['response']['man_of_the_match']['name'];
                            foreach ($Response['response']['innings'] as $InningsValue) {
                                $BatsmanData = $BowlersData = $FielderData = $AllPlayingXI = array();

                                /* Manage Batsman Data */
                                foreach ($InningsValue['batsmen'] as $BatsmenValue) {
                                    $BatsmanData[] = array(
                                        'Name' => @$LivePlayersData[$BatsmenValue['batsman_id']],
                                        'PlayerIDLive' => $BatsmenValue['batsman_id'],
                                        'Role' => $BatsmenValue['role'],
                                        'Runs' => $BatsmenValue['runs'],
                                        'BallsFaced' => $BatsmenValue['balls_faced'],
                                        'Fours' => $BatsmenValue['fours'],
                                        'Sixes' => $BatsmenValue['sixes'],
                                        'HowOut' => $BatsmenValue['how_out'],
                                        'IsPlaying' => ($BatsmenValue['how_out'] == 'Not out') ? 'Yes' : 'No',
                                        'StrikeRate' => $BatsmenValue['strike_rate']
                                    );
                                    $AllPlayingXI[$BatsmenValue['batsman_id']]['batting'] = array(
                                        'Name' => @$LivePlayersData[$BatsmenValue['batsman_id']],
                                        'PlayerIDLive' => $BatsmenValue['batsman_id'],
                                        'Role' => $BatsmenValue['role'],
                                        'Runs' => $BatsmenValue['runs'],
                                        'BallsFaced' => $BatsmenValue['balls_faced'],
                                        'Fours' => $BatsmenValue['fours'],
                                        'Sixes' => $BatsmenValue['sixes'],
                                        'HowOut' => $BatsmenValue['how_out'],
                                        'IsPlaying' => ($BatsmenValue['how_out'] == 'Not out') ? 'Yes' : 'No',
                                        'StrikeRate' => $BatsmenValue['strike_rate']
                                    );
                                }

                                /* Manage Bowler Data */
                                foreach ($InningsValue['bowlers'] as $BowlersValue) {
                                    $BowlersData[] = array(
                                        'Name' => @$LivePlayersData[$BowlersValue['bowler_id']],
                                        'PlayerIDLive' => $BowlersValue['bowler_id'],
                                        'Overs' => $BowlersValue['overs'],
                                        'Maidens' => $BowlersValue['maidens'],
                                        'RunsConceded' => $BowlersValue['runs_conceded'],
                                        'Wickets' => $BowlersValue['wickets'],
                                        'NoBalls' => $BowlersValue['noballs'],
                                        'Wides' => $BowlersValue['wides'],
                                        'Economy' => $BowlersValue['econ']
                                    );
                                    $AllPlayingXI[$BowlersValue['bowler_id']]['bowling'] = array(
                                        'Name' => @$LivePlayersData[$BowlersValue['bowler_id']],
                                        'PlayerIDLive' => $BowlersValue['bowler_id'],
                                        'Overs' => $BowlersValue['overs'],
                                        'Maidens' => $BowlersValue['maidens'],
                                        'RunsConceded' => $BowlersValue['runs_conceded'],
                                        'Wickets' => $BowlersValue['wickets'],
                                        'NoBalls' => $BowlersValue['noballs'],
                                        'Wides' => $BowlersValue['wides'],
                                        'Economy' => $BowlersValue['econ']
                                    );
                                }

                                /* Manage Fielder Data */
                                foreach ($InningsValue['fielder'] as $FielderValue) {
                                    $FielderData[] = array(
                                        'Name' => $FielderValue['fielder_name'],
                                        'PlayerIDLive' => $FielderValue['fielder_id'],
                                        'Catches' => $FielderValue['catches'],
                                        'RunOutThrower' => $FielderValue['runout_thrower'],
                                        'RunOutCatcher' => $FielderValue['runout_catcher'],
                                        'RunOutDirectHit' => $FielderValue['runout_direct_hit'],
                                        'Stumping' => $FielderValue['stumping']
                                    );
                                    $AllPlayingXI[$FielderValue['fielder_id']]['fielding'] = array(
                                        'Name' => $FielderValue['fielder_name'],
                                        'PlayerIDLive' => $FielderValue['fielder_id'],
                                        'Catches' => $FielderValue['catches'],
                                        'RunOutThrower' => $FielderValue['runout_thrower'],
                                        'RunOutCatcher' => $FielderValue['runout_catcher'],
                                        'RunOutDirectHit' => $FielderValue['runout_direct_hit'],
                                        'Stumping' => $FielderValue['stumping']
                                    );
                                }

                                $InningsData[] = array(
                                    'Name' => $InningsValue['name'],
                                    'ShortName' => $InningsValue['short_name'],
                                    'Scores' => $InningsValue['scores'],
                                    'Status' => $InningsStatus[$InningsValue['status']],
                                    'ScoresFull' => $InningsValue['scores_full'],
                                    'BatsmanData' => $BatsmanData,
                                    'BowlersData' => $BowlersData,
                                    'FielderData' => $FielderData,
                                    'AllPlayingData' => $AllPlayingXI,
                                    'ExtraRuns' => array('Byes' => $InningsValue['extra_runs']['byes'], 'LegByes' => $InningsValue['extra_runs']['legbyes'], 'Wides' => $InningsValue['extra_runs']['wides'], 'NoBalls' => $InningsValue['extra_runs']['noballs'])
                                );
                            }
                            $MatchScoreDetails['Innings'] = $InningsData;

                            $MatchCompleteDateTime = date('Y-m-d H:i:s', strtotime("+2 hours"));
                            /* Update Match Data */
                            $this->db->trans_start();

                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->limit(1);
                            $this->db->update('sports_matches', array('MatchScoreDetails' => json_encode($MatchScoreDetails), 'MatchCompleteDateTime' => $MatchCompleteDateTime));

                            $this->getPlayerPoints(0, $Value['MatchID']);

                            if ($Value['StatusID'] != 2) {
                                /* Update Contest Status */
                                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID'] . '  AND E.StatusID != 3');

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));
                            }
                            if (strtolower($MatchStatusLive) == "completed" && $StatusNote != "abandoned") {

                                $this->getPlayerPoints(0, $Value['MatchID']);

                                $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']);

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 10));
                                if ($Verified == "true") {
                                    /* Update Contest Status */
                                    $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID'] . ' AND E.StatusID != 3');

                                    /* Update Match Status */
                                    $this->db->where('EntityID', $Value['MatchID']);
                                    $this->db->limit(1);
                                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 5));
                                }
                            }

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        } else {
                            if ($StatusNote == 'no result') {
                                $MatchStatusLive = 'no result';
                            }
                            if ($MatchStatusLiveCheck == 4) {
                                $MatchStatusLive = 'abandoned';
                            }
                            if (strpos($StatusNote, 'abandoned') !== false) {
                                $MatchStatusLive = 'abandoned';
                            }
                            if (strpos($StatusNote, 'scheduled') !== false) {
                                $MatchStatusLive = 'scheduled';
                            }
                            $this->db->trans_start();
                            if ($MatchStatusLiveCheck == 4) {
                                /* Update Contest Status */
                                //$this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 3 WHERE C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID'] . ' AND E.StatusID != 3');

                                $this->autoCancelContestMatchAbonded($Value['MatchID']);

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 8));
                            }
                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContestMatchAbonded($MatchID) {

        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $ContestsUsers = $this->Contest_model->getContests('ContestID,Privacy,EntryFee,TotalJoined,ContestFormat,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor', array('StatusID' => array(1, 2), 'Filter' => 'YesterdayToday', "MatchID" => $MatchID), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {
            foreach ($ContestsUsers['Data']['Records'] as $Value) {
                $MatchStartDateTime = strtotime($Value['MatchStartDateTime']) - 19800; // -05:30 Hours
                $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
                if (($MatchStartDateTime - $CurrentDateTime) > 0) {
                    continue;
                }
                /* Update Contest Status */
                $this->db->where('EntityID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
            }
        }
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContest($CronID) {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $ContestsUsers = $this->Contest_model->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor', array('StatusID' => array(1, 2), 'Filter' => 'MatchLive', 'IsConfirm' => "No", "IsPaid" => "Yes", "LeagueType" => "Dfs"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
        foreach ($ContestsUsers['Data']['Records'] as $Value) {

            $MatchStartDateTime = strtotime($Value['MatchStartDateTime']) - 19800; // -05:30 Hours
            $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
            if (($MatchStartDateTime - $CurrentDateTime) > 300)
                continue;

            $IsCancelled = (($Value['IsConfirm'] == 'No' && $Value['TotalJoined'] != $Value['ContestSize']) ? 1 : 0);
            if ($IsCancelled == 0)
                continue;

            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
        }
    }

    /*
      Description: To cancel contest refund amount
     */

    function refundAmountCancelContest($CronID) {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,C.ContestSize,C.IsConfirm');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.IsRefund", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("E.StatusID", 3);
        $this->db->limit(15);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $ContestsUsers = $Query->result_array();
            foreach ($ContestsUsers as $Value) {

                $this->db->trans_start();

                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.IsRefund');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                $this->db->where("JC.IsRefund", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    foreach ($JoinedContestsUsers as $JoinValue) {
                        /* Refund Wallet Money */
                        if (!empty($Value['EntryFee'])) {
                            /* Get Wallet Details */
                            $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                'UserID' => $JoinValue['UserID'],
                                'EntityID' => $Value['ContestID'],
                                'UserTeamID' => $JoinValue['UserTeamID'],
                                'Narration' => 'Cancel Contest'
                            ));
                            if (!empty($WalletDetails)) {
                                /** update user refund status Yes */
                                $this->db->where('ContestID', $JoinValue['ContestID']);
                                $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                $this->db->where('UserID', $JoinValue['UserID']);
                                $this->db->limit(1);
                                $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                continue;
                            }

                            /* Get Wallet Details */
                            $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                'UserID' => $JoinValue['UserID'],
                                'EntityID' => $Value['ContestID'],
                                'UserTeamID' => $JoinValue['UserTeamID'],
                                'Narration' => 'Join Contest'
                            ));
                            if (!empty($WalletDetails)) {
                                $InsertData = array(
                                    "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'],
                                    "WalletAmount" => $WalletDetails['WalletAmount'],
                                    "WinningAmount" => $WalletDetails['WinningAmount'],
                                    "CashBonus" => $WalletDetails['CashBonus'],
                                    "TransactionType" => 'Cr',
                                    "EntityID" => $Value['ContestID'],
                                    "UserTeamID" => $JoinValue['UserTeamID'],
                                    "Narration" => 'Cancel Contest',
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
                            }

                            /** update user refund status Yes */
                            $this->db->where('ContestID', $JoinValue['ContestID']);
                            $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                            $this->db->where('UserID', $JoinValue['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                        }
                    }
                    /* Update Contest Refund Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => "Yes"));
                } else {
                    /* Update Contest Refund Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => "Yes"));
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    return FALSE;
                }
            }
        }
    }

    /*
      Description: To get player points
     */

    function getPlayerPoints($CronID, $MatchIDScore = "") {

        ini_set('max_execution_time', 300);

        if (!empty($MatchIDScore)) {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID', array('MatchID' => $MatchIDScore, 'StatusID' => array(2, 5, 10), 'OrderBy' => 'M.MatchStartDateTime', 'Sequence' => 'DESC'), true, 1, 10);
        } else {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID', array('Filter' => 'Yesterday', 'StatusID' => array(2, 5, 10), 'IsPlayerPointsUpdated' => 'No', 'OrderBy' => 'M.MatchStartDateTime', 'Sequence' => 'DESC'), true, 1, 10);
        }

        /* Get Live Matches Data */
        if (!empty($LiveMatches)) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(), 'LiveMatches' => $LiveMatches), JSON_UNESCAPED_UNICODE)));

            /* Get Points Data */
            $PointsDataArr = $this->getPoints();
            $StatringXIArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'StatringXI');
            $CaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'CaptainPointMP');
            $ViceCaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'ViceCaptainPointMP');
            $BattingMinimumRunsArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'BattingMinimumRuns');
            $MinimumRunScoreStrikeRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumRunScoreStrikeRate');
            $MinimumOverEconomyRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumOverEconomyRate');
            $MatchTypes = array('ODI' => 'PointsODI', 'List A' => 'PointsODI', 'T20' => 'PointsT20', 'T20I' => 'PointsT20', 'Test' => 'PointsTEST', 'Woman ODI' => 'PointsODI', 'Woman T20' => 'PointsT20');

            /* Sorting Keys */
            $PointsSortingKeys = array('SB', 'RUNS', '4s', '6s', 'STB', 'BTB', 'DUCK', 'WK', 'MD', 'EB', 'BWB', 'RO', 'ST', 'CT');
            foreach ($LiveMatches['Data']['Records'] as $Value) {
                if (empty((array) $Value['MatchScoreDetails']))
                    continue;

                $StatringXIPoints = (isset($StatringXIArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($StatringXIArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $CaptainPointMPPoints = (isset($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $ViceCaptainPointMPPoints = (isset($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "1.5";
                $BattingMinimumRunsPoints = (isset($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]]) : "15";
                $MinimumRunScoreStrikeRate = (isset($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]]) : "10";
                $MinimumOverEconomyRate = (isset($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]]) : "1";

                /* Get Match Players */
                $MatchPlayers = $this->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID'], 'IsPlaying' => 'Yes'), true, 0);
                if (!$MatchPlayers) {
                    continue;
                }

                /* Get Match Live Score Data */
                $BatsmanPlayers = $BowlingPlayers = $FielderPlayers = $AllPalyers = $AllPlayeRoleData = array();
                foreach ($Value['MatchScoreDetails']['Innings'] as $PlayerID) {
                    foreach ($PlayerID['AllPlayingData'] as $PlayerKey => $PlayerSubValue) {
                        if (isset($PlayerSubValue['batting'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['batting']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['batting']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Role'] = $PlayerSubValue['batting']['Role'];
                            $AllPalyers[$PlayerKey]['Runs'] = $PlayerSubValue['batting']['Runs'];
                            $AllPalyers[$PlayerKey]['BallsFaced'] = $PlayerSubValue['batting']['BallsFaced'];
                            $AllPalyers[$PlayerKey]['Fours'] = $PlayerSubValue['batting']['Fours'];
                            $AllPalyers[$PlayerKey]['Sixes'] = $PlayerSubValue['batting']['Sixes'];
                            $AllPalyers[$PlayerKey]['HowOut'] = $PlayerSubValue['batting']['HowOut'];
                            $AllPalyers[$PlayerKey]['IsPlaying'] = $PlayerSubValue['batting']['IsPlaying'];
                            $AllPalyers[$PlayerKey]['StrikeRate'] = $PlayerSubValue['batting']['StrikeRate'];
                        }
                        if (isset($PlayerSubValue['bowling'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['bowling']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['bowling']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Overs'] = $PlayerSubValue['bowling']['Overs'];
                            $AllPalyers[$PlayerKey]['Maidens'] = $PlayerSubValue['bowling']['Maidens'];
                            $AllPalyers[$PlayerKey]['RunsConceded'] = $PlayerSubValue['bowling']['RunsConceded'];
                            $AllPalyers[$PlayerKey]['Wickets'] = $PlayerSubValue['bowling']['Wickets'];
                            $AllPalyers[$PlayerKey]['NoBalls'] = $PlayerSubValue['bowling']['NoBalls'];
                            $AllPalyers[$PlayerKey]['Wides'] = $PlayerSubValue['bowling']['Wides'];
                            $AllPalyers[$PlayerKey]['Economy'] = $PlayerSubValue['bowling']['Economy'];
                        }
                        if (isset($PlayerSubValue['fielding'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['fielding']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['fielding']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Catches'] = $PlayerSubValue['fielding']['Catches'];
                            $AllPalyers[$PlayerKey]['RunOutThrower'] = $PlayerSubValue['fielding']['RunOutThrower'];
                            $AllPalyers[$PlayerKey]['RunOutCatcher'] = $PlayerSubValue['fielding']['RunOutCatcher'];
                            $AllPalyers[$PlayerKey]['RunOutDirectHit'] = $PlayerSubValue['fielding']['RunOutDirectHit'];
                            $AllPalyers[$PlayerKey]['Stumping'] = $PlayerSubValue['fielding']['Stumping'];
                        }
                    }
                }
                if (empty($AllPalyers)) {
                    continue;
                }

                $AllPlayersLiveIds = array_keys($AllPalyers);
                foreach ($MatchPlayers['Data']['Records'] as $PlayerValue) {

                    $this->IsStrikeRate = $this->IsEconomyRate = $this->IsBattingState = $this->IsBowlingState = $PlayerTotalPoints = "0";
                    $this->defaultStrikeRatePoints = $this->defaultEconomyRatePoints = $this->defaultBattingPoints = $this->defaultBowlingPoints = $PointsData = array();
                    $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI', 'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints, 'ScoreValue' => "1", 'CalculatedPoints' => $StatringXIPoints);
                    $ScoreData = $AllPalyers[$PlayerValue['PlayerIDLive']];

                    /* To Check Player Is Played Or Not */
                    if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds) && !empty($ScoreData)) {
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP', 'StatringXI', 'ViceCaptainPointMP')))
                                    continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP', 'StatringXI')))
                                    continue;
                            }
                            $allKeys = array_keys($ScoreData);
                            if (($DeleteKey = array_search('Name', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }
                            if (($DeleteKey = array_search('PlayerIDLive', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }

                            /** calculate points * */
                            foreach ($allKeys as $ScoreValue) {
                                $calculatePoints = $this->calculatePoints($PointValue, $Value['MatchType'], $MinimumRunScoreStrikeRate, @$ScoreData[$PointValue['PointsScoringField']], @$ScoreData['BallsFaced'], @$ScoreData['Overs'], @$ScoreData['Runs'], $MinimumOverEconomyRate, $PlayerValue['PlayerRole']);
                                if (is_array($calculatePoints)) {
                                    $PointsData[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'], 'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'], 'DefinedPoints' => strval($calculatePoints['DefinedPoints']), 'ScoreValue' => strval($calculatePoints['ScoreValue']), 'CalculatedPoints' => strval($calculatePoints['CalculatedPoints']));
                                }
                            }
                        }

                        /* Manage Single Strike Rate & Economy Rate & Bowling & Batting State */
                        if ($this->IsStrikeRate == 0) {
                            $PointsData['STB'] = $this->defaultStrikeRatePoints;
                        }
                        if ($this->IsEconomyRate == 0) {
                            $PointsData['EB'] = $this->defaultEconomyRatePoints;
                        }
                        if ($this->IsBattingState == 0) {
                            $PointsData['BTB'] = $this->defaultBattingPoints;
                        }
                        if ($this->IsBowlingState == 0) {
                            $PointsData['BWB'] = $this->defaultBowlingPoints;
                        }
                    } else {
                        $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI', 'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints, 'ScoreValue' => "1", 'CalculatedPoints' => $StatringXIPoints);
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP', 'StatringXI', 'ViceCaptainPointMP')))
                                    continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP', 'StatringXI')))
                                    continue;
                            }
                            if (in_array($PointValue['PointsTypeGUID'], array('StrikeRate50N74.99', 'StrikeRate75N99.99', 'StrikeRate100N149.99', 'StrikeRate150N199.99', 'StrikeRate200NMore', 'EconomyRate5.01N7.00Balls', 'EconomyRate5.01N8.00Balls', 'EconomyRate7.01N10.00Balls', 'EconomyRate8.01N10.00Balls', 'EconomyRate10.01N12.00Balls', 'EconomyRateAbove12.1Balls', 'FourWickets', 'FiveWickets', 'SixWickets', 'SevenWicketsMore', 'EightWicketsMore', 'For50runs', 'For100runs', 'For150runs', 'For200runs', 'For300runs', 'MinimumRunScoreStrikeRate', 'MinimumOverEconomyRate')))
                                continue;
                            $PointsData[$PointValue['PointsTypeShortDescription']] = array('PointsTypeGUID' => $PointValue['PointsTypeGUID'], 'PointsTypeShortDescription' => $PointValue['PointsTypeShortDescription'], 'DefinedPoints' => "0", 'ScoreValue' => "0", 'CalculatedPoints' => "0");
                        }
                    }

                    /* Sort Points Keys Data */
                    $OrderedArray = array();
                    foreach ($PointsSortingKeys as $SortValue) {
                        unset($PointsData[$SortValue]['PointsTypeShortDescription']);
                        $OrderedArray[] = $PointsData[$SortValue];
                    }
                    $PointsData = $OrderedArray;

                    /* Calculate Total Points */
                    if (!empty($PointsData)) {
                        foreach ($PointsData as $PointValue) {
                            if ($PointValue['CalculatedPoints'] > 0) {
                                $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                            } else {
                                $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                            }
                        }
                    }

                    /* Update Player Points Data */
                    $UpdateData = array_filter(array(
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null
                    ));

                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->where('PlayerID', $PlayerValue['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('sports_team_players', $UpdateData);
                }
                // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                // $this->db->cache_delete('admin', 'matches'); //Delete Cache

                /* Update Match Player Points Status */
                if ($Value['StatusID'] == 5) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array('IsPlayerPointsUpdated' => 'Yes'));

                    /* Update Final player points before complete match */
                    /* $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
                      $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']);
                      $this->Utility_model->updateCronLogs($CronID); */
                }

                /** auction draft series wise player point update * */
                $this->updateAuctionDraftPlayerPoint($Value['SeriesID']);
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    /*
      Description: Update series wise auction draft player point
     */

    function updateAuctionDraftPlayerPoint($SeriesID) {

        $this->db->select("MatchID,SeriesID,PlayerID,SUM(TotalPoints) as TotalPoints");
        $this->db->from('sports_team_players');
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("TotalPoints > ", 0);
        $this->db->group_by("PlayerID");
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $Players = $Query->result_array();
            foreach ($Players as $Player) {
                $UpdateData = array(
                    "TotalPoints" => $Player['TotalPoints'],
                    "UpdateDateTime" => date('Y-m-d H:i:s')
                );
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('sports_auction_draft_player_point', $UpdateData);
            }
        }
        return true;
    }

    /*
      Description: To get joined contest player points
     */

    function getJoinedContestPlayerPoints($CronID, $StatusArr = array(2)) {

        ini_set('max_execution_time', 300);

        /* To Get Joined Running Contest */
        $Contests = $this->Contest_model->getJoinedContests('MatchTypeID,ContestID,UserID,MatchID,UserTeamID', array('StatusID' => $StatusArr, 'Filter' => 'YesterdayToday', "LeagueType" => "Dfs"), true, 0);
        if (!empty($Contests['Data']['Records'])) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(), 'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

            /* Get Vice Captain Points */
            $ViceCaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();

            /* Get Captain Points */
            $CaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();

            /* Match Types */
            $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI');
            $ContestIDArray = array();
            foreach ($Contests['Data']['Records'] as $Value) {
                $ContestIDArray[] = $Value['ContestID'];
                /* Player Points Multiplier */
                if (IS_VICECAPTAIN) {
                    $PositionPointsMultiplier = array('ViceCaptain' => $ViceCaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]], 'Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]], 'Player' => 1);
                } else {
                    $PositionPointsMultiplier = array('Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]], 'Player' => 1);
                }

                $UserTotalPoints = 0;

                /* To Get Match Players */
                $MatchPlayers = $this->getPlayers('PlayerID,TotalPoints', array('MatchID' => $Value['MatchID']), true, 0);
                $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');
                /* To Get User Team Players */
                $UserTeamPlayers = $this->Contest_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);
                foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                    if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']]))
                        continue;

                    $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                    $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                    /* Update Player Points */
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                    $this->db->limit(1);
                    $this->db->update('sports_users_team_players', array('Points' => $Points));
                }
                /* Update Player Total Points */
                $this->db->where('UserTeamID', $Value['UserTeamID']);
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
            }
            $ContestIDArray = array_unique($ContestIDArray);
            $this->updateRankByContest($ContestIDArray);
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    function getJoinedContestTeamPoints($CronID, $MatchID = "", $StatusArr = array(2)) {

        ini_set('max_execution_time', 300);
        /* Get Matches Live */
        if (!empty($MatchID)) {
            $LiveMatcheContest = $this->Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => array(2, 5), 'MatchID' => $MatchID, "LeagueType" => "Dfs"), true, 0);
        } else {
            $LiveMatcheContest = $this->Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => $StatusArr, 'Filter' => 'YesterdayToday', "LeagueType" => "Dfs"), true, 0);
        }
        if (!$LiveMatcheContest) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($LiveMatcheContest['Data']['Records'] as $Value) {
            $MatchIDLive = $Value['MatchIDLive'];
            $MatchID = $Value['MatchID'];
            $ContestID = $Value['ContestID'];

            /* To Get Match Players */
            $MatchPlayers = $this->getPlayers('PlayerID,TotalPoints', array('MatchID' => $Value['MatchID']), true, 0);
            $Contests = $this->Contest_model->getJoinedContests('MatchTypeID,ContestID,UserID,MatchID,UserTeamID', array('ContestID' => $ContestID), true, 0);
            if (!empty($Contests['Data']['Records'])) {

                /* Match Types */
                $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI', '8' => 'PointsODI');
                foreach ($Contests['Data']['Records'] as $Value) {
                    /* Player Points Multiplier */
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2, 'Player' => 1);
                    $UserTotalPoints = 0;
                    $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');
                    /* To Get User Team Players */
                    $UserTeamPlayers = $this->Contest_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);

                    foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                        if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']]))
                            continue;

                        $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                        /* Update Player Points */
                        $this->db->where('UserTeamID', $Value['UserTeamID']);
                        $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                        $this->db->limit(1);
                        $this->db->update('sports_users_team_players', array('Points' => $Points));
                        //echo $this->db->last_query();exit;
                    }
                    /* Update Player Total Points */
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
                }
            }
            $this->updateRankByContest($ContestID);
        }
    }

    /*
      Description: To update rank
     */

    function updateRankByContest($ContestID) {
        if (!empty($ContestID)) {
            $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_contest_join WHERE sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID
                         FROM sports_contest_join,tbl_users 
                         WHERE sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = sports_contest_join.UserID
                     ");
            $results = $query->result_array();
            if (!empty($results)) {
                $this->db->trans_start();
                foreach ($results as $rows) {
                    $this->db->where('ContestID', $rows['ContestID']);
                    $this->db->where('UserTeamID', $rows['UserTeamID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('UserRank' => $rows['UserRank']));
                }
                $this->db->trans_complete();
            }
        }
    }

    /*
      Description:  Cron jobs to get auction joined player points.

     */

    function getAuctionDraftJoinedUserTeamsPlayerPoints($CronID) {
        ini_set('max_execution_time', 300);
        /** get series play in auction * */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array("AuctionDraftStatusID" => 2, "AuctionDraftIsPlayed" => "Yes"), true, 0);
        if ($SeriesData['Data']['TotalRecords'] == 0) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        $ContestIDArray = array();
        /* Get Vice Captain Points */
        $ViceCaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();
        /* Get Captain Points */
        $CaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();
        foreach ($SeriesData['Data']['Records'] as $Rows) {
            /** get series walse player point * */
            $this->db->select("SeriesID,PlayerID,TotalPoints");
            $this->db->from('sports_auction_draft_player_point');
            $this->db->where("SeriesID", $Rows['SeriesID']);
            $this->db->where("TotalPoints > ", 0);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $MatchPlayers = $Query->result_array();
                /* Get Matches Live */
                $Sql = "Select J.ContestID,J.SeriesID,J.AuctionStatusID,CJ.UserID,CJ.UserTeamID "
                        . " FROM sports_contest J INNER JOIN sports_contest_join CJ ON CJ.ContestID=J.ContestID"
                        . " WHERE J.AuctionStatusID = 5 AND J.SeriesID='" . $Rows['SeriesID'] . "' AND LeagueType != 'Dfs' AND CJ.UserTeamID IS NOT NULL";
                $ContestsJoined = $this->customQuery($Sql);

                foreach ($ContestsJoined as $Value) {
                    $SeriesID = $Value['SeriesID'];
                    $ContestID = $Value['ContestID'];
                    /* Match Types */
                    $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI');
                    $ContestIDArray[] = $Value['ContestID'];
                    /* Player Points Multiplier */
                    $UserID = $Value["UserID"];
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2, 'Player' => 1);
                    $UserTotalPoints = 0;

                    // $PlayersPointsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerID');

                    /* To Get User Team Players */
                    $UserTeamPlayers = $this->AuctionDrafts_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);
                    foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                        if (!isset($PlayersIdsArr[$UserTeamValue['PlayerID']]))
                            continue;

                        $Points = ($PlayersIdsArr[$UserTeamValue['PlayerID']] != 0) ? $PlayersIdsArr[$UserTeamValue['PlayerID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                        /* Update Player Points */
                        $this->db->where('UserTeamID', $Value['UserTeamID']);
                        $this->db->where('PlayerID', $UserTeamValue['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('sports_users_team_players', array('Points' => $Points));
                    }
                    /* Update Player Total Points */
                    $this->db->where('UserID', $UserID);
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
                    $ContestIDArray = array_unique($ContestIDArray);

                    /* else {
                      $this->db->where('CronID', $CronID);
                      $this->db->limit(1);
                      $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                      exit;
                      } */
                }
            }
        }

        $this->updateRankByContestAuction($ContestIDArray);
    }

    /*
      Description: To update rank
     */

    function updateRankByContestAuction($ContestIDs) {
        if (!empty($ContestIDs)) {
            foreach ($ContestIDs as $ContestID) {
                $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_contest_join WHERE sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID
                         FROM sports_contest_join,tbl_users 
                         WHERE sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = sports_contest_join.UserID
                     ");
                $results = $query->result_array();
                if (!empty($results)) {
                    $this->db->trans_start();
                    foreach ($results as $rows) {
                        $this->db->where('ContestID', $rows['ContestID']);
                        $this->db->where('UserTeamID', $rows['UserTeamID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('UserRank' => $rows['UserRank']));
                    }
                    $this->db->trans_complete();
                }
            }
        }
    }

    /*
      Description: To set contest winners
     */

    function setContestWinners() {

        ini_set('max_execution_time', 300);


        // $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor,CustomizeWinning', array('StatusID' => 5, 'IsWinningDistributed' => 'No', "LeagueType" => "Dfs"), true, 0);

        $this->db->select("SeriesID,ContestID,WeekStart as WeekID,DailyDate,ContestDuration,WinningAmount,NoOfWinners,EntryFee,CustomizeWinning");
        $this->db->from('tbl_entity as E, sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration !=", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 5);
        $this->db->where("E.GameSportsType", 'Nfl');
        $this->db->where("C.IsWinningDistributed", "No");
        $Query = $this->db->get();
        $Contests = $Query->result_array();
        if ($Contests) {
            foreach ($Contests as $Value) {

                $JoinedContestsUsers = $this->getJoinedContestsPlayers('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], 'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0);
                if (empty($JoinedContestsUsers)){
                    /* update contest winner amount distribute flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
                    continue;
                }

                $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);
                $userWinnersData = $OptionWinner = array();
                $CustomizeWinning = $Value['CustomizeWinning'];
                if (empty($CustomizeWinning)) {
                    $CustomizeWinning[] = array(
                        'From' => 1,
                        'To' => $Value['NoOfWinners'],
                        'Percent' => 100,
                        'WinningAmount' => $Value['WinningAmount']
                    );
                }else{
                   $CustomizeWinning = json_decode($Value['CustomizeWinning'],true);  
                }
                /** calculate amount according to rank or contest winning configuration */
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = $TotalAmount = $AmountPerUser = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        $FromWinner = $CustomizeWinning[$J]['From'];
                        $ToWinner = $CustomizeWinning[$J]['To'];
                        if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                            $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                            if ($WinnerValue > 1) {
                                $L = 0;
                                for ($k = 1; $k < $WinnerValue; $k++) {
                                    if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                        if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                            $Flag = 1;
                                        } else {
                                            $L = $L + 1;
                                            if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                    $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                    $Flag = 1;
                                                }
                                            }
                                        }
                                    }
                                    if ($Flag == 0) {
                                        if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $AmountPerUser = $TotalAmount / $WinnerValue;
                    $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $AmountPerUser);
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {

                        $this->db->trans_start();

                        /** join contest user winning amount update * */
                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('UserWinningAmount' => $WinnerValue['UserWinningAmount'], 'ModifiedDate' => date('Y-m-d H:i:s')));

                        $this->db->trans_complete();
                        if ($this->db->trans_status() === false) {
                            return false;
                        }
                    }
                }

                /* update contest winner amount distribute flag set YES */
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
            }
        }
    }

        /*
      Description: To set contest winners
     */

    function setContestWinnersSeasonLong() {
        $this->db->select("SeriesID,ContestID,WeekStart as WeekID,DailyDate,ContestDuration,WinningAmount,NoOfWinners,EntryFee,CustomizeWinning");
        $this->db->from('tbl_entity as E, sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 5);
        $this->db->where("E.GameSportsType", 'Nfl');
        $this->db->where("C.IsWinningDistributed", "No");
        $Query = $this->db->get();
        $Contests = $Query->result_array();
        if ($Contests) {
            foreach ($Contests as $Value) {

                $JoinedContestsUsers = $this->getJoinedContestsPlayers('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], 'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0);
                if (empty($JoinedContestsUsers)){
                    /* update contest winner amount distribute flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
                    continue;
                }

                $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);
                $userWinnersData = $OptionWinner = array();
                $CustomizeWinning = $Value['CustomizeWinning'];
                if (empty($CustomizeWinning)) {
                    $CustomizeWinning[] = array(
                        'From' => 1,
                        'To' => $Value['NoOfWinners'],
                        'Percent' => 100,
                        'WinningAmount' => $Value['WinningAmount']
                    );
                }else{
                   $CustomizeWinning = json_decode($Value['CustomizeWinning'],true);  
                }
                /** calculate amount according to rank or contest winning configuration */
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = $TotalAmount = $AmountPerUser = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        $FromWinner = $CustomizeWinning[$J]['From'];
                        $ToWinner = $CustomizeWinning[$J]['To'];
                        if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                            $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                            if ($WinnerValue > 1) {
                                $L = 0;
                                for ($k = 1; $k < $WinnerValue; $k++) {
                                    if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                        if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                            $Flag = 1;
                                        } else {
                                            $L = $L + 1;
                                            if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                    $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                    $Flag = 1;
                                                }
                                            }
                                        }
                                    }
                                    if ($Flag == 0) {
                                        if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $AmountPerUser = $TotalAmount / $WinnerValue;
                    $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $AmountPerUser);
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {

                        $this->db->trans_start();

                        /** join contest user winning amount update * */
                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('UserWinningAmount' => $WinnerValue['UserWinningAmount'], 'ModifiedDate' => date('Y-m-d H:i:s')));

                        $this->db->trans_complete();
                        if ($this->db->trans_status() === false) {
                            return false;
                        }
                    }
                }

                /* update contest winner amount distribute flag set YES */
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
            }
        }
    }


    /*
      Description: To get joined contest users
     */

    function getJoinedContestsPlayers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
                'UserTeamCode' => 'U.UserTeamCode',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'UserRank' => 'JC.UserRank',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'AuctionTimeBank' => 'JC.AuctionTimeBank',
                'AuctionBudget' => 'JC.AuctionBudget',
                'AuctionUserStatus' => 'JC.AuctionUserStatus',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'DraftUserPosition' => 'JC.DraftUserPosition',
                'DraftUserLive' => 'JC.DraftUserLive'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID,JC.UserTeamID,U.UserID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_contest_join JC, tbl_users U');
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
            if (!empty($Where['SessionUserID'])) {
                $this->db->order_by('JC.UserID=' . $Where['SessionUserID'] . ' DESC', null, FALSE);
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
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    /*
      Description: To set auction draft winners
     */

    function amountDistributeContestWinner($CronID) {

        ini_set('max_execution_time', 300);

        /* Get Joined Contest Users */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.IsWinningDistributed", "Yes");
        $this->db->where("C.IsWinningDistributeAmount", "No");
        $this->db->where("C.ContestDuration", "SeasonLong");
        $this->db->where("E.StatusID", 5);
        $this->db->limit(15);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            foreach ($Contests as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank,JC.UserWinningAmount,JC.IsWinningDistributeAmount');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                $this->db->where("JC.IsWinningDistributeAmount", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    foreach ($JoinedContestsUsers as $WinnerValue) {

                        $this->db->trans_start();

                        if ($WinnerValue['UserWinningAmount'] > 0) {
                            /** update user wallet * */
                            $WalletData = array(
                                "Amount" => $WinnerValue['UserWinningAmount'],
                                "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                "EntityID" => $WinnerValue['ContestID'],
                                "TransactionType" => 'Cr',
                                "Narration" => 'Join Contest Winning',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                        }

                        /** user join contest winning status update * */
                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('IsWinningDistributeAmount' => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));

                        $this->db->trans_complete();
                        if ($this->db->trans_status() === false) {
                            return false;
                        }
                    }

                    /* Update Contest Winning Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                } else {
                    /* Update Contest Winning Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    /*
      Description: To set contest winners amount distribute
     */

    function SetAuctionDraftWinner() {

        ini_set('max_execution_time', 300);

        $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor,CustomizeWinning', array('StatusID' => 5, 'IsWinningDistributed' => 'Yes', "IsWinningDistributeAmount" => "No", "LeagueType" => "Dfs"), true, 0);
        if (!empty($Contests['Data']['Records'])) {

            foreach ($Contests['Data']['Records'] as $Value) {

                $JoinedContestsUsers = $this->Contest_model->getJoinedContestsUsers('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], 'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0);
                if (!$JoinedContestsUsers)
                    continue;


                $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');

                $AllRankWinners = array_count_values($AllUsersRank);
                $userWinnersData = $OptionWinner = array();
                $CustomizeWinning = $Value['CustomizeWinning'];
                if (empty($CustomizeWinning)) {
                    $CustomizeWinning[] = array(
                        'From' => 1,
                        'To' => $Value['NoOfWinners'],
                        'Percent' => 100,
                        'WinningAmount' => $Value['WinningAmount']
                    );
                }
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = $TotalAmount = $AmountPerUser = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        $FromWinner = $CustomizeWinning[$J]['From'];
                        $ToWinner = $CustomizeWinning[$J]['To'];
                        if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                            $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                            if ($WinnerValue > 1) {
                                $L = 0;
                                for ($k = 1; $k < $WinnerValue; $k++) {
                                    if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                        if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                            $Flag = 1;
                                        } else {
                                            $L = $L + 1;
                                            if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                    $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                    $Flag = 1;
                                                }
                                            }
                                        }
                                    }
                                    if ($Flag == 0) {
                                        if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $AmountPerUser = $TotalAmount / $WinnerValue;
                    $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $AmountPerUser);
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }

                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {

                        $this->db->trans_start();

                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('UserWinningAmount' => $WinnerValue['UserWinningAmount'], 'ModifiedDate' => date('Y-m-d H:i:s')));

                        if ($WinnerValue['UserWinningAmount'] > 0) {
                            $WalletData = array(
                                "Amount" => $WinnerValue['UserWinningAmount'],
                                "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                "TransactionType" => 'Cr',
                                "Narration" => 'Join Contest Winning',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                        }
                        $this->db->trans_complete();
                        if ($this->db->trans_status() === false) {
                            return false;
                        }
                    }
                }

                /* update contest winner amount distribute flag set YES */
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));

            }
        }
    }

    /*
      Description: To common funtion find key value
     */

    function findKeyValueArray($JoinedContestsUsers, $Rank, $AmountPerUser) {
        $WinnerUsers = array();
        foreach ($JoinedContestsUsers as $Rows) {
            if ($Rows['UserRank'] == $Rank) {
                $Temp['UserID'] = $Rows['UserID'];
                $Temp['FirstName'] = $Rows['FirstName'];
                $Temp['Email'] = $Rows['Email'];
                $Temp['UserWinningAmount'] = $AmountPerUser;
                $Temp['UserRank'] = $Rows['UserRank'];
                $Temp['TotalPoints'] = $Rows['TotalPoints'];
                $Temp['UserTeamID'] = $Rows['UserTeamID'];
                $WinnerUsers[] = $Temp;
            }
        }
        return $WinnerUsers;
    }

    /*
      Description: To set matches data (Cricket API)
     */

    function getMatchesLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);

        /* Get Live Matches Data */
        $DatesArr = array(date('Y-m'), date('Y-m', strtotime('+1 month')));
        foreach ($DatesArr as $DateValue) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/schedule/?date=' . $DateValue . '&access_token=');

            if (!$Response['status']) {
                $this->db->where('CronID', $CronID);
                $this->db->limit(1);
                $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                exit;
            }
            $LiveMatchesData = @$Response['data']['months'][0]['days'];
            if (empty($LiveMatchesData))
                continue;

            /* To get All Series Data */
            $SeriesIdsData = array();
            $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2), true, 0);
            if ($SeriesData) {
                $SeriesIdsData = array_column($SeriesData['Data']['Records'], 'SeriesID', 'SeriesIDLive');
            }

            /* To get All Match Types */
            $MatchTypesData = $this->getMatchTypes();
            $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeNameCricketAPI');

            foreach ($LiveMatchesData as $key => $Value) {
                if (empty($Value['matches']))
                    continue;

                $this->db->trans_start();

                foreach ($Value['matches'] as $MatchValue) {

                    /* Manage Series Data */
                    if (!isset($SeriesIdsData[$MatchValue['season']['key']])) {

                        /* Add series to entity table and get EntityID. */
                        $SeriesGUID = get_guid();
                        $SeriesID = $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2));
                        $SeriesData = array_filter(array(
                            'SeriesID' => $SeriesID,
                            'SeriesGUID' => $SeriesGUID,
                            'SeriesIDLive' => $MatchValue['season']['key'],
                            'SeriesName' => $MatchValue['season']['name']
                        ));

                        $this->db->insert('sports_series', $SeriesData);
                        // $this->db->cache_delete('sports', 'getSeries'); //Delete Cache       
                        // $this->db->cache_delete('admin', 'matches'); //Delete Cache       
                        // $this->db->cache_delete('admin', 'series'); //Delete Cache  
                        $SeriesIdsData[$MatchValue['season']['key']] = $SeriesID;
                    } else {
                        $SeriesID = $SeriesIdsData[$MatchValue['season']['key']];
                    }

                    /* Manage Teams */
                    $LocalTeam = $MatchValue['teams']['a'];
                    $VisitorTeam = $MatchValue['teams']['b'];
                    $LocalTeamData = $VisitorTeamData = $TeamLiveIds = array();
                    if ($LocalTeam['key'] == 'tbc' || $VisitorTeam['key'] == 'tbc')
                        continue;

                    /* To check if local team is already exist */
                    $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $LocalTeam['key'] . '" LIMIT 1');
                    $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                    if (!$TeamIDLocal) {

                        /* Add team to entity table and get EntityID. */
                        $TeamGUID = get_guid();
                        $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                        $LocalTeamData[] = array(
                            'TeamID' => $TeamIDLocal,
                            'TeamGUID' => $TeamGUID,
                            'TeamIDLive' => $LocalTeam['key'],
                            'TeamName' => $LocalTeam['name'],
                            'TeamNameShort' => strtoupper($LocalTeam['key'])
                        );
                    }

                    /* To check if visitor team is already exist */
                    $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $VisitorTeam['key'] . '" LIMIT 1');
                    $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                    if (!$TeamIDVisitor) {

                        /* Add team to entity table and get EntityID. */
                        $TeamGUID = get_guid();
                        $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                        $VisitorTeamData[] = array(
                            'TeamID' => $TeamIDVisitor,
                            'TeamGUID' => $TeamGUID,
                            'TeamIDLive' => $VisitorTeam['key'],
                            'TeamName' => $VisitorTeam['name'],
                            'TeamNameShort' => strtoupper($VisitorTeam['key'])
                        );
                    }
                    $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);
                    if (!empty($TeamsData)) {
                        $this->db->insert_batch('sports_teams', $TeamsData);
                        // $this->db->cache_delete('sports', 'getTeams');
                        // $this->db->cache_delete('admin', 'teams');
                    }

                    /* Manage Matches */

                    /* To check if match is already exist */
                    $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = "' . $MatchValue['key'] . '" LIMIT 1');
                    $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                    if (!$MatchID) {

                        /* Add matches to entity table and get EntityID. */
                        $MatchGUID = get_guid();
                        $MatchStatusArr = array('completed' => 5, 'notstarted' => 1, 'started' => 2);
                        $MatchesAPIData = array(
                            'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => $MatchStatusArr[$MatchValue['status']])),
                            'MatchGUID' => $MatchGUID,
                            'MatchIDLive' => $MatchValue['key'],
                            'SeriesID' => $SeriesID,
                            'MatchTypeID' => $MatchTypeIdsData[$MatchValue['format']],
                            'MatchNo' => $MatchValue['related_name'],
                            'MatchLocation' => $MatchValue['venue'],
                            'TeamIDLocal' => $TeamIDLocal,
                            'TeamIDVisitor' => $TeamIDVisitor,
                            'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['start_date']['iso']))
                        );
                        $this->db->insert('sports_matches', $MatchesAPIData);
                        // $this->db->cache_delete('contest', 'getMatches'); //Delete Cache
                        // $this->db->cache_delete('sports', 'getMatches'); //Delete Cache
                        // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                    } else {

                        if ($Query->row()->StatusID != 1)
                            continue; // Pending Match

                            /* Update Match Data */
                        $MatchesAPIData = array(
                            'MatchNo' => $MatchValue['related_name'],
                            'MatchLocation' => $MatchValue['venue'],
                            'TeamIDLocal' => $TeamIDLocal,
                            'TeamIDVisitor' => $TeamIDVisitor,
                            'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['start_date']['iso'])),
                            'LastUpdatedOn' => date('Y-m-d H:i:s')
                        );
                        $this->db->where('MatchID', $MatchID);
                        $this->db->limit(1);
                        $this->db->update('sports_matches', $MatchesAPIData);
                        // $this->db->cache_delete('contest', 'getMatches'); //Delete Cache
                        // $this->db->cache_delete('sports', 'getMatches'); //Delete Cache
                        // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                    }
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
            }
        }
    }

    /*
      Description: To set players data (Entity API)
     */

    function getPlayersLiveEntity($CronID) {
        ini_set('max_execution_time', 300);

        /* Get series data */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesEndDate' => date('Y-m-d')), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper', 'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        foreach ($SeriesData['Data']['Records'] as $Value) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $Value['SeriesIDLive'] . '/squads/?token=');

            if (empty($Response['response']['squads']))
                continue;
            foreach ($Response['response']['squads'] as $SquadsValue) {

                $this->db->trans_start();

                /* To check if visitor team is already exist */
                $IsNewTeam = false;
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $SquadsValue['team_id'] . ' LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamID) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $SquadsValue['team']['tid'],
                        'TeamName' => $SquadsValue['team']['title'],
                        'TeamNameShort' => $SquadsValue['team']['abbr'],
                        'TeamFlag' => $SquadsValue['team']['thumb_url'],
                    ));
                    $IsNewTeam = true;
                    $this->db->insert('sports_teams', $TeamData);
                }
                if (!$IsNewTeam) {

                    /* To get all match ids */
                    $Query = $this->db->query('SELECT MatchID FROM `sports_matches` WHERE `SeriesID` = ' . $Value['SeriesID'] . ' AND (`TeamIDLocal` = ' . $TeamID . ' OR `TeamIDVisitor` = ' . $TeamID . ')');
                    $MatchIds = ($Query->num_rows() > 0) ? array_column($Query->result_array(), 'MatchID') : array();
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }

                foreach ($SquadsValue['players'] as $PlayerValue) {

                    $this->db->trans_start();

                    /* To check if player is already exist */
                    $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = ' . $PlayerValue['pid'] . ' LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                    if (!$PlayerID) {

                        /* Add players to entity table and get EntityID. */
                        $PlayerGUID = get_guid();
                        $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                        $PlayersAPIData = array(
                            'PlayerID' => $PlayerID,
                            'PlayerGUID' => $PlayerGUID,
                            'PlayerIDLive' => $PlayerValue['pid'],
                            'PlayerName' => $PlayerValue['title'],
                            'PlayerCountry' => ($PlayerValue['country']) ? strtoupper($PlayerValue['country']) : null,
                            'PlayerBattingStyle' => ($PlayerValue['batting_style']) ? $PlayerValue['batting_style'] : null,
                            'PlayerBowlingStyle' => ($PlayerValue['bowling_style']) ? $PlayerValue['bowling_style'] : null
                        );
                        $this->db->insert('sports_players', $PlayersAPIData);
                    }

                    /* To check If match player is already exist */
                    if (!$IsNewTeam && !empty($MatchIds)) {
                        $TeamPlayersData = array();
                        foreach ($MatchIds as $MatchID) {
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $Value['SeriesID'] . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {
                                $TeamPlayersData[] = array(
                                    'SeriesID' => $Value['SeriesID'],
                                    'MatchID' => $MatchID,
                                    'TeamID' => $TeamID,
                                    'PlayerID' => $PlayerID,
                                    'PlayerRole' => $PlayerRolesArr[strtolower($PlayerValue['playing_role'])]
                                );
                            }
                        }
                        if (!empty($TeamPlayersData)) {
                            $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                        }
                    }

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        return false;
                    }
                }
            }
        }
    }

    /*
      Description: To set players data (Cricket API)
     */

    function getPlayersLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);

        /* Get matches data */
        $MatchesData = $this->getMatches('MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1, 2)), true, 1, 10);
        if (!$MatchesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        foreach ($MatchesData['Data']['Records'] as $Value) {

            /* Get Both Teams */
            $TeamsArr = array($Value['TeamIDLiveLocal'] => $Value['SeriesIDLive'] . "_" . $Value['TeamIDLiveLocal'], $Value['TeamIDLiveVisitor'] => $Value['SeriesIDLive'] . "_" . $Value['TeamIDLiveVisitor']);
            foreach ($TeamsArr as $TeamKey => $TeamValue) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/season/' . $Value['SeriesIDLive'] . '/team/' . $TeamValue . '/?access_token=');
                if (empty($Response['data']['players_key']))
                    continue;

                $this->db->trans_start();

                /* To check if visitor team is already exist */
                $IsNewTeam = false;
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $TeamKey . '" LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamID) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $TeamKey,
                        'TeamName' => $Response['data']['name'],
                        'TeamNameShort' => strtoupper($TeamKey)
                    ));
                    $IsNewTeam = true;
                    $this->db->insert('sports_teams', $TeamData);
                    // $this->db->cache_delete('sports', 'getTeams');
                    // $this->db->cache_delete('admin', 'teams');
                }
                if (!$IsNewTeam) {

                    /* To get all match ids */
                    $Query = $this->db->query('SELECT MatchID FROM `sports_matches` WHERE `SeriesID` = ' . $Value['SeriesID'] . ' AND (`TeamIDLocal` = ' . $TeamID . ' OR `TeamIDVisitor` = ' . $TeamID . ')');
                    $MatchIds = ($Query->num_rows() > 0) ? array_column($Query->result_array(), 'MatchID') : array();
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }

                /* Insert All Players */
                foreach ($Response['data']['players_key'] as $PlayerIDLive) {

                    $this->db->trans_start();

                    /* To check if player is already exist */
                    $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = "' . $PlayerIDLive . '" LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                    if (!$PlayerID) {

                        /* Add players to entity table and get EntityID. */
                        $PlayerGUID = get_guid();
                        $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                        $PlayersAPIData = array(
                            'PlayerID' => $PlayerID,
                            'PlayerGUID' => $PlayerGUID,
                            'PlayerIDLive' => $PlayerIDLive,
                            'PlayerName' => $Response['data']['players'][$PlayerIDLive]['name'],
                            'PlayerBattingStyle' => @$Response['data']['players'][$PlayerIDLive]['batting_style'][0],
                            'PlayerBowlingStyle' => @$Response['data']['players'][$PlayerIDLive]['bowling_style'][0],
                        );
                        $this->db->insert('sports_players', $PlayersAPIData);
                        // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                        // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                    }

                    /* To check If match player is already exist */
                    if (!$IsNewTeam && !empty($MatchIds)) {
                        $TeamPlayersData = array();
                        foreach ($MatchIds as $MatchID) {
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $Value['SeriesID'] . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {

                                /* Get Player Role */
                                $Keeper = $Response['data']['players'][$PlayerIDLive]['identified_roles']['keeper'];
                                $Batsman = $Response['data']['players'][$PlayerIDLive]['identified_roles']['batsman'];
                                $Bowler = $Response['data']['players'][$PlayerIDLive]['identified_roles']['bowler'];
                                $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));
                                $TeamPlayersData[] = array(
                                    'SeriesID' => $Value['SeriesID'],
                                    'MatchID' => $MatchID,
                                    'TeamID' => $TeamID,
                                    'PlayerID' => $PlayerID,
                                    'PlayerRole' => $PlayerRole
                                );
                            }
                        }
                        if (!empty($TeamPlayersData)) {
                            $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                        }
                    }

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        return false;
                    }
                }
            }

            /* Get Player Credit Points */
            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v3/fantasy-match-credits/' . $Value['MatchIDLive'] . '/?access_token=');
            if (!empty($Response['data']['fantasy_points'])) {
                foreach ($Response['data']['fantasy_points'] as $PlayerValue) {
                    $PlayerArr[] = array(
                        'PlayerSalary' => json_encode(array($Value['MatchType'] . 'Credits' => $PlayerValue['credit_value'])),
                        'PlayerIDLive' => $PlayerValue['player'],
                        'LastUpdatedOn' => date('Y-m-d H:i:s')
                    );
                }
                $this->db->update_batch('sports_players', $PlayerArr, 'PlayerIDLive');
                // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                // $this->db->cache_delete('admin', 'matches'); //Delete Cache
            }

            /* Update Last Updated Status */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('sports_matches', array('LastUpdatedOn' => date('Y-m-d H:i:s')));

            // $this->db->cache_delete('contest', 'getMatches'); //Delete Cache
            // $this->db->cache_delete('sports', 'getMatches'); //Delete Cache
            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
        }
    }

    /*
      Description: To set player stats (Cricket API)
     */

    function getPlayerStatsLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);

        /* To get All Player Stats Data */
        $PlayersData = $this->getPlayers('PlayerIDLive,PlayerID,LastUpdateDiff', array('IsAdminSalaryUpdated' => 'No', 'CronFilter' => 'OneDayDiff'), true, 1, 25);
        // $PlayersData = $this->getPlayers('TeamGUID, TeamName, TeamNameShort, TeamFlag, PlayerRole, IsPlaying, TotalPoints, PointsData, SeriesID, MatchID,PlayerIDLive,PlayerID,LastUpdateDiff', array('MatchID' => '341904'), true, 1, 25);
        if (!$PlayersData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        foreach ($PlayersData['Data']['Records'] as $Value) {

            /* Call Player Stats API */
            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/player/' . $Value['PlayerIDLive'] . '/league/icc/stats/?access_token=');

            /* Manage Batting Stats */
            $BattingStats = new stdClass();
            $BowlingStats = new stdClass();

            /* Test Batting Stats */
            $BattingStats->Test = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['test']['batting']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['test']['batting']['innings'],
                        'NotOut' => @(string) $Response['data']['player']['stats']['test']['batting']['not_outs'],
                        'Runs' => @(string) $Response['data']['player']['stats']['test']['batting']['runs'],
                        'Balls' => @(string) $Response['data']['player']['stats']['test']['batting']['balls'],
                        'HighestScore' => @(string) $Response['data']['player']['stats']['test']['batting']['high_score'],
                        'Hundreds' => @(string) $Response['data']['player']['stats']['test']['batting']['hundreds'],
                        'Fifties' => @(string) $Response['data']['player']['stats']['test']['batting']['fifties'],
                        'Fours' => @(string) $Response['data']['player']['stats']['test']['batting']['fours'],
                        'Sixes' => @(string) $Response['data']['player']['stats']['test']['batting']['sixes'],
                        'Average' => @(string) $Response['data']['player']['stats']['test']['batting']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['test']['batting']['strike_rate'],
                        'Catches' => @(string) $Response['data']['player']['stats']['test']['fielding']['catches'],
                        'Stumpings' => @(string) $Response['data']['player']['stats']['test']['fielding']['stumpings']
            );
            /* ODI Batting Stats */
            $BattingStats->ODI = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['one-day']['batting']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['one-day']['batting']['innings'],
                        'NotOut' => @(string) $Response['data']['player']['stats']['one-day']['batting']['not_outs'],
                        'Runs' => @(string) $Response['data']['player']['stats']['one-day']['batting']['runs'],
                        'Balls' => @(string) $Response['data']['player']['stats']['one-day']['batting']['balls'],
                        'HighestScore' => @(string) $Response['data']['player']['stats']['one-day']['batting']['high_score'],
                        'Hundreds' => @(string) $Response['data']['player']['stats']['one-day']['batting']['hundreds'],
                        'Fifties' => @(string) $Response['data']['player']['stats']['one-day']['batting']['fifties'],
                        'Fours' => @(string) $Response['data']['player']['stats']['one-day']['batting']['fours'],
                        'Sixes' => @(string) $Response['data']['player']['stats']['one-day']['batting']['sixes'],
                        'Average' => @(string) $Response['data']['player']['stats']['one-day']['batting']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['one-day']['batting']['strike_rate'],
                        'Catches' => @(string) $Response['data']['player']['stats']['one-day']['fielding']['catches'],
                        'Stumpings' => @(string) $Response['data']['player']['stats']['one-day']['fielding']['stumpings']
            );
            /* T20 Batting Stats */
            $BattingStats->T20 = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['t20']['batting']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['t20']['batting']['innings'],
                        'NotOut' => @(string) $Response['data']['player']['stats']['t20']['batting']['not_outs'],
                        'Runs' => @(string) $Response['data']['player']['stats']['t20']['batting']['runs'],
                        'Balls' => @(string) $Response['data']['player']['stats']['t20']['batting']['balls'],
                        'HighestScore' => @(string) $Response['data']['player']['stats']['t20']['batting']['high_score'],
                        'Hundreds' => @(string) $Response['data']['player']['stats']['t20']['batting']['hundreds'],
                        'Fifties' => @(string) $Response['data']['player']['stats']['t20']['batting']['fifties'],
                        'Fours' => @(string) $Response['data']['player']['stats']['t20']['batting']['fours'],
                        'Sixes' => @(string) $Response['data']['player']['stats']['t20']['batting']['sixes'],
                        'Average' => @(string) $Response['data']['player']['stats']['t20']['batting']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['t20']['batting']['strike_rate'],
                        'Catches' => @(string) $Response['data']['player']['stats']['t20']['fielding']['catches'],
                        'Stumpings' => @(string) $Response['data']['player']['stats']['t20']['fielding']['stumpings']
            );

            /* Test Bowling Stats */
            $BowlingStats->Test = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['test']['bowling']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['test']['bowling']['innings'],
                        'Balls' => @(string) $Response['data']['player']['stats']['test']['bowling']['balls'],
                        'Overs' => "",
                        'Runs' => @(string) $Response['data']['player']['stats']['test']['bowling']['runs'],
                        'Wickets' => @(string) $Response['data']['player']['stats']['test']['bowling']['wickets'],
                        'BestInning' => @(string) $Response['data']['player']['stats']['test']['bowling']['best_innings']['wickets'] . '/' . @$Response['data']['player']['stats']['test']['bowling']['best_innings']['runs'],
                        'BestMatch' => "",
                        'Economy' => @(string) $Response['data']['player']['stats']['test']['bowling']['economy'],
                        'Average' => @(string) $Response['data']['player']['stats']['test']['bowling']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['test']['bowling']['strike_rate'],
                        'FourPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['test']['bowling']['four_wickets'],
                        'FivePlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['test']['bowling']['five_wickets'],
                        'TenPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['test']['bowling']['ten_wickets']
            );

            /* ODI Bowling Stats */
            $BowlingStats->ODI = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['innings'],
                        'Balls' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['balls'],
                        'Overs' => "",
                        'Runs' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['runs'],
                        'Wickets' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['wickets'],
                        'BestInning' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['best_innings']['wickets'] . '/' . @$Response['data']['player']['stats']['one-day']['bowling']['best_innings']['runs'],
                        'BestMatch' => "",
                        'Economy' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['economy'],
                        'Average' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['strike_rate'],
                        'FourPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['four_wickets'],
                        'FivePlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['five_wickets'],
                        'TenPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['one-day']['bowling']['ten_wickets']
            );

            /* T20 Bowling Stats */
            $BowlingStats->T20 = (object) array(
                        'MatchID' => (string) 0,
                        'InningID' => (string) 0,
                        'Matches' => @(string) $Response['data']['player']['stats']['t20']['bowling']['matches'],
                        'Innings' => @(string) $Response['data']['player']['stats']['t20']['bowling']['innings'],
                        'Balls' => @(string) $Response['data']['player']['stats']['t20']['bowling']['balls'],
                        'Overs' => "",
                        'Runs' => @(string) $Response['data']['player']['stats']['t20']['bowling']['runs'],
                        'Wickets' => @(string) $Response['data']['player']['stats']['t20']['bowling']['wickets'],
                        'BestInning' => @(string) $Response['data']['player']['stats']['t20']['bowling']['best_innings']['wickets'] . '/' . @$Response['data']['player']['stats']['t20']['bowling']['best_innings']['runs'],
                        'BestMatch' => "",
                        'Economy' => @(string) $Response['data']['player']['stats']['t20']['bowling']['economy'],
                        'Average' => @(string) $Response['data']['player']['stats']['t20']['bowling']['average'],
                        'StrikeRate' => @(string) $Response['data']['player']['stats']['t20']['bowling']['strike_rate'],
                        'FourPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['t20']['bowling']['four_wickets'],
                        'FivePlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['t20']['bowling']['five_wickets'],
                        'TenPlusWicketsInSingleInning' => @(string) $Response['data']['player']['stats']['t20']['bowling']['ten_wickets']
            );

            /* Get Player Role */
            // $Keeper  = $Response['data']['player']['identified_roles']['keeper'];
            // $Batsman = $Response['data']['player']['identified_roles']['batsman'];
            // $Bowler  = $Response['data']['player']['identified_roles']['bowler'];
            // $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));

            /* Get Player Credits */
            // $PlayerCredits = $this->getPlayerCredits($BattingStats,$BowlingStats,$PlayerRole);

            /* Update Player Stats */
            $PlayerStats = array(
                'PlayerBattingStats' => json_encode($BattingStats),
                'PlayerBowlingStats' => json_encode($BowlingStats),
                'LastUpdatedOn' => date('Y-m-d H:i:s')
            );
            $this->db->where('PlayerID', $Value['PlayerID']);
            $this->db->limit(1);
            $this->db->update('sports_players', $PlayerStats);
        }
        // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
        // $this->db->cache_delete('admin', 'matches'); //Delete Cache
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getMatchScoreLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);

        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('Filter' => 'Today', 'StatusID' => array(1, 2)), true, 1, 10);

        // $LiveMatches = $this->getMatches('MatchIDLive,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchID' => '341904'), true,0);

        if (!$LiveMatches) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        $MatchStatus = array('completed' => 5, "started" => 2, "notstarted" => 9);
        $ContestStatus = array('completed' => 5, "started" => 2, "notstarted" => 9, "Abandoned" => 5, "Cancelled" => 3, "No Result" => 5);
        $InningsStatus = array(1 => 'Scheduled', 2 => 'Completed', 3 => 'Live', 4 => 'Abandoned');
        foreach ($LiveMatches['Data']['Records'] as $Value) {

            if ($Value['Status'] == 'Pending' && (strtotime(date('Y-m-d H:i:s')) + 19800 >= strtotime($Value['MatchStartDateTime']))) { // +05:30

                /* Update Match Status */
                $this->db->where('EntityID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));

                /* Update Contest Status */
                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
            }

            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $Value['MatchIDLive'] . '/?access_token=');
            $MatchStatusLive = @$Response['data']['card']['status'];


            if ($Value['Status'] == 'Running' && $MatchStatusLive != 'notstarted') {

                /* Update Match Status */
                $this->db->where('EntityID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => $MatchStatus[$MatchStatusLive]));

                /* Update Contest Status */
                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = ' . $ContestStatus[$MatchStatusLive] . ' WHERE  E.StatusID = 2 AND C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
            }

            /* Get Match Players Live */
            if (empty($Response['data']['card']['players']))
                continue;
            foreach ($Response['data']['card']['players'] as $PlayerIdLive => $Player) {
                $LivePlayersData[$PlayerIdLive] = $Player['name'];
            }

            /* Get Playing XI */
            $PlayingXIArr = array_merge(empty($Response['data']['card']['teams']['a']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['a']['match']['playing_xi'], empty($Response['data']['card']['teams']['b']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['b']['match']['playing_xi']);

            // if ($MatchStatusLive == 'notstarted' && !empty($PlayingXIArr) && $Value['IsPlayingXINotificationSent'] == 'No') {
            // if ($MatchStatusLive == 'notstarted' && !empty($PlayingXIArr) ) {
            if ($MatchStatusLive == 'completed' && !empty($PlayingXIArr)) {

                /* Get Match Players */
                $PlayersIdsData = array();
                $PlayersData = $this->Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);

                if ($PlayersData) {
                    $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                }
                foreach ($PlayingXIArr as $PlayerIdLiveNew => $PlayerValue) {

                    /* Update Playing XI Status */
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->where('PlayerID', $PlayersIdsData[$PlayerValue]);
                    $this->db->limit(1);
                    $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                }
            }

            if (!in_array($MatchStatusLive, array('started', 'completed'))) {
                continue;
            }
            $MatchScoreDetails = $InningsData = array();
            $MatchScoreDetails['StatusLive'] = ($MatchStatusLive == 'started') ? 'Live' : (($MatchStatusLive == 'notstarted') ? 'Not Started' : 'Completed');
            $MatchScoreDetails['StatusNote'] = (!empty($Response['data']['card']['msgs']['result'])) ? $Response['data']['card']['msgs']['result'] : '';
            $MatchScoreDetails['TeamScoreLocal'] = array('Name' => $Response['data']['card']['teams']['a']['name'], 'ShortName' => $Response['data']['card']['teams']['a']['short_name'], 'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['a_1']['runs'] . '/' . @$Response['data']['card']['innings']['a_1']['wickets'], 'Overs' => @$Response['data']['card']['innings']['a_1']['overs']);
            $MatchScoreDetails['TeamScoreVisitor'] = array('Name' => $Response['data']['card']['teams']['b']['name'], 'ShortName' => $Response['data']['card']['teams']['b']['short_name'], 'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['b_1']['runs'] . '/' . @$Response['data']['card']['innings']['b_1']['wickets'], 'Overs' => @$Response['data']['card']['innings']['b_1']['overs']);
            $MatchScoreDetails['MatchVenue'] = @$Response['data']['card']['venue'];
            $MatchScoreDetails['Result'] = (!empty($Response['data']['cards']['msgs']['result'])) ? $Response['data']['cards']['msgs']['result'] : '';
            $MatchScoreDetails['Toss'] = @$Response['data']['card']['toss']['str'];
            $MatchScoreDetails['ManOfTheMatchPlayer'] = (!empty($LivePlayersData[@$Response['data']['card']['man_of_match']])) ? $LivePlayersData[@$Response['data']['card']['man_of_match']] : '';
            foreach ($Response['data']['card']['innings'] as $InnningKey => $InningsValue) {
                $BatsmanData = $BowlersData = $FielderData = $InningPlayers = $AllPlayingXI = array();

                $InningPlayers[] = $InningsValue['batting_order'];
                $InningPlayers[] = $InningsValue['bowling_order'];
                $InningPlayers[] = $InningsValue['wicket_order'];
                $InningPlayers = array_unique(call_user_func_array('array_merge', $InningPlayers));
                foreach ($InningPlayers as $InningPlayer) {

                    /* Get Player Details */
                    $PlayerDetails = @$Response['data']['card']['players'][$InningPlayer];

                    /* Get Player Role */
                    $Keeper = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['keeper'];
                    $Batsman = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['batsman'];
                    $Bowler = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['bowler'];
                    $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));

                    /* Batting */
                    if (isset($PlayerDetails['match']['innings'][1]['batting']['balls'])) {

                        $BatsmanData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => @$InningPlayer,
                            'Role' => @$PlayerRole,
                            'Runs' => (!empty($PlayerDetails['match']['innings'][1]['batting']['runs'])) ? $PlayerDetails['match']['innings'][1]['batting']['runs'] : "",
                            'BallsFaced' => (!empty($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? $PlayerDetails['match']['innings'][1]['batting']['balls'] : "",
                            'Fours' => (!empty($PlayerDetails['match']['innings'][1]['batting']['fours'])) ? $PlayerDetails['match']['innings'][1]['batting']['fours'] : "",
                            'Sixes' => (!empty($PlayerDetails['match']['innings'][1]['batting']['sixes'])) ? $PlayerDetails['match']['innings'][1]['batting']['sixes'] : "",
                            'HowOut' => (!empty($PlayerDetails['match']['innings'][1]['batting']['out_str'])) ? $PlayerDetails['match']['innings'][1]['batting']['out_str'] : "",
                            'IsPlaying' => (@$PlayerDetails['match']['innings'][1]['batting']['dismissed'] == 1) ? 'No' : ((isset($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? 'Yes' : ''),
                            'StrikeRate' => (!empty($PlayerDetails['match']['innings'][1]['batting']['strike_rate'])) ? $PlayerDetails['match']['innings'][1]['batting']['strike_rate'] : ""
                        );
                        $AllPlayingXI[@$InningPlayer]['batting'] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => @$InningPlayer,
                            'Role' => @$PlayerRole,
                            'Runs' => (!empty($PlayerDetails['match']['innings'][1]['batting']['runs'])) ? $PlayerDetails['match']['innings'][1]['batting']['runs'] : "",
                            'BallsFaced' => (!empty($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? $PlayerDetails['match']['innings'][1]['batting']['balls'] : "",
                            'Fours' => (!empty($PlayerDetails['match']['innings'][1]['batting']['fours'])) ? $PlayerDetails['match']['innings'][1]['batting']['fours'] : "",
                            'Sixes' => (!empty($PlayerDetails['match']['innings'][1]['batting']['sixes'])) ? $PlayerDetails['match']['innings'][1]['batting']['sixes'] : "",
                            'HowOut' => (!empty($PlayerDetails['match']['innings'][1]['batting']['out_str'])) ? $PlayerDetails['match']['innings'][1]['batting']['out_str'] : "",
                            'IsPlaying' => (@$PlayerDetails['match']['innings'][1]['batting']['dismissed'] == 1) ? 'No' : ((isset($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? 'Yes' : ''),
                            'StrikeRate' => (!empty($PlayerDetails['match']['innings'][1]['batting']['strike_rate'])) ? $PlayerDetails['match']['innings'][1]['batting']['strike_rate'] : ""
                        );
                    }

                    /* Bowling */
                    if (!empty(@$PlayerDetails['match']['innings'][1]['bowling'])) {

                        $BowlersData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Overs' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['overs'] : '',
                            'Maidens' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'] : '',
                            'RunsConceded' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['runs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['runs'] : '',
                            'Wickets' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['wickets'])) ? $PlayerDetails['match']['innings'][1]['bowling']['wickets'] : '',
                            'NoBalls' => '',
                            'Wides' => '',
                            'Economy' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['economy'])) ? $PlayerDetails['match']['innings'][1]['bowling']['economy'] : ''
                        );

                        $AllPlayingXI[@$InningPlayer]['bowling'] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Overs' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['overs'] : '',
                            'Maidens' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'] : '',
                            'RunsConceded' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['runs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['runs'] : '',
                            'Wickets' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['wickets'])) ? $PlayerDetails['match']['innings'][1]['bowling']['wickets'] : '',
                            'NoBalls' => '',
                            'Wides' => '',
                            'Economy' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['economy'])) ? $PlayerDetails['match']['innings'][1]['bowling']['economy'] : ''
                        );
                    }

                    /* Fielding */
                    if (!empty(@$PlayerDetails['match']['innings'][1]['fielding'])) {

                        $FielderData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Catches' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['catches'])) ? $PlayerDetails['match']['innings'][1]['fielding']['catches'] : '',
                            'RunOutThrower' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutCatcher' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutDirectHit' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'Stumping' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['stumbeds'])) ? $PlayerDetails['match']['innings'][1]['fielding']['stumbeds'] : ''
                        );

                        $AllPlayingXI[@$InningPlayer]['fielding'] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Catches' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['catches'])) ? $PlayerDetails['match']['innings'][1]['fielding']['catches'] : '',
                            'RunOutThrower' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutCatcher' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutDirectHit' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'Stumping' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['stumbeds'])) ? $PlayerDetails['match']['innings'][1]['fielding']['stumbeds'] : ''
                        );
                    }
                }

                /* Get Team Details */
                $TeamName = (strpos($InnningKey, 'a') !== false) ? $Response['data']['card']['teams']['a']['name'] : $Response['data']['card']['teams']['b']['name'];
                $TeamShortName = (strpos($InnningKey, 'a') !== false) ? $Response['data']['card']['teams']['a']['short_name'] : $Response['data']['card']['teams']['b']['short_name'];
                $InningsData[] = array(
                    'Name' => $TeamName . ' inning',
                    'ShortName' => $TeamShortName . ' inn.',
                    'Scores' => $InningsValue['runs'] . "/" . $InningsValue['wickets'],
                    'Status' => '',
                    'ScoresFull' => $InningsValue['runs'] . "/" . $InningsValue['wickets'] . " (" . $InningsValue['overs'] . " ov)",
                    'BatsmanData' => $BatsmanData,
                    'BowlersData' => $BowlersData,
                    'FielderData' => $FielderData,
                    'AllPlayingData' => $AllPlayingXI,
                    'ExtraRuns' => array('Byes' => $InningsValue['extras'], 'LegByes' => @$InningsValue['extras'], 'Wides' => $InningsValue['wide'], 'NoBalls' => $InningsValue['noball'])
                );
            }
            $MatchScoreDetails['Innings'] = $InningsData;
            $this->db->trans_start();

            /* Update Match Data */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('sports_matches', array('MatchScoreDetails' => json_encode($MatchScoreDetails)));

            if ($MatchStatusLive == 'completed') {

                /* Update Match Status */
                $this->db->where('EntityID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 5));

                /* Update Contest Status */
                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE  E.StatusID = 2 AND  C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
            }

            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                return false;
            }
            // $this->db->cache_delete('contest', 'getMatches'); //Delete Cache
            // $this->db->cache_delete('sports', 'getMatches'); //Delete Cache
            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
        }
    }

    /*
      Description: To calculate points according to keys
     */

    function calculatePoints($Points = array(), $MatchType, $BattingMinimumRuns, $ScoreValue, $BallsFaced = 0, $Overs = 0, $Runs = 0, $MinimumOverEconomyRate = 0, $PlayerRole) {
        /* Match Types */
        $MatchTypes = array('ODI' => 'PointsODI', 'List A' => 'PointsODI', 'T20' => 'PointsT20', 'T20I' => 'PointsT20', 'Test' => 'PointsTEST', 'Woman ODI' => 'PointsODI', 'Woman T20' => 'PointsT20');
        $MatchTypeField = $MatchTypes[$MatchType];
        $PlayerPoints = array('PointsTypeGUID' => $Points['PointsTypeGUID'], 'PointsTypeShortDescription' => $Points['PointsTypeShortDescription'], 'DefinedPoints' => strval($Points[$MatchTypeField]), 'ScoreValue' => (!empty($ScoreValue)) ? strval($ScoreValue) : "0");
        switch ($Points['PointsTypeGUID']) {
            case 'ThreeWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 3) ? strval($Points[$MatchTypeField]) : "0";
                $this->defaultBowlingPoints = $PlayerPoints;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'FourWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 4) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'FiveWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 5) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'SixWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 6) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'SevenWicketsMore':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 7) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'EightWicketsMore':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 8) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'RunOUT':
            case 'Stumping':
            case 'Four':
            case 'Six':
            case 'EveryRunScored':
            case 'Catch':
            case 'Wicket':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points[$MatchTypeField] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'Maiden':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points[$MatchTypeField] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'Duck':
                if ($ScoreValue <= 0 && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($BallsFaced >= 1) ? $Points[$MatchTypeField] : 0;
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                return $PlayerPoints;
                break;
            case 'StrikeRate0N49.99':
                $PlayerPoints['CalculatedPoints'] = "0";
                $this->defaultStrikeRatePoints = $PlayerPoints;
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 0.1 && $ScoreValue <= 49.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate50N74.99':
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 50 && $ScoreValue <= 74.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate75N99.99':
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 75 && $ScoreValue <= 99.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate100N149.99':
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 100 && $ScoreValue <= 149.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate150N199.99':
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 150 && $ScoreValue <= 199.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate200NMore':
                if ($Runs >= $BattingMinimumRuns) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 200) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate0N5Balls':
                $PlayerPoints['CalculatedPoints'] = "0";
                $this->defaultEconomyRatePoints = $PlayerPoints;
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 0.1 && $ScoreValue <= 5) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate5.01N7.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 5.01 && $ScoreValue <= 7) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate5.01N8.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 5.01 && $ScoreValue <= 8) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate7.01N10.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 7.01 && $ScoreValue <= 10) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate8.01N10.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 8.01 && $ScoreValue <= 10) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate10.01N12.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 10.01 && $ScoreValue <= 12) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRateAbove12.1Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 12.1) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'For30runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 30 && $ScoreValue < 50) ? strval($Points[$MatchTypeField]) : "0";
                $this->defaultBattingPoints = $PlayerPoints;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For50runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 50 && $ScoreValue < 100) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For100runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 100 && $ScoreValue < 150) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For150runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 150 && $ScoreValue < 200) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For200runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 200 && $ScoreValue < 300) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For300runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 300) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            default:
                return false;
                break;
        }
    }

    /*
      Description: Find sub arrays from multidimensional array
     */

    function findSubArray($DataArray, $keyName, $Value) {
        $Data = array();
        foreach ($DataArray as $Row) {
            if ($Row[$keyName] == $Value)
                $Data[] = $Row;
        }
        return $Data;
    }

    /*
      Description: Use to get player credits (salary)
     */

    function getPlayerCredits($BattingStats = array(), $BowlingStats = array(), $PlayerRole) {

        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper', 'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        $PlayerCredits = array('T20Credits' => 0, 'T20iCredits' => 0, 'ODICredits' => 0, 'TestCredits' => 0);
        $PlayerRole = (SPORTS_API_NAME == 'ENTITY') ? @$PlayerRolesArr[$PlayerRole] : $PlayerRole;
        if (empty($PlayerRole))
            return $PlayerCredits;

        /* Get Player Credits */
        if ($PlayerRole == 'Batsman') {
            if (isset($BattingStats->T20)) {
                $PlayerCredits['T20Credits'] = $this->getT20BattingCredits($BattingStats->T20);
            }
            if (isset($BattingStats->T20i)) {
                $PlayerCredits['T20iCredits'] = $this->getT20iBattingCredits($BattingStats->T20i);
            }
            if (isset($BattingStats->ODI)) {
                $PlayerCredits['ODICredits'] = $this->getODIBattingCredits($BattingStats->ODI);
            }
            if (isset($BattingStats->Test)) {
                $PlayerCredits['TestCredits'] = $this->getTestBattingCredits($BattingStats->Test);
            }
        } else if ($PlayerRole == 'Bowler') {
            if (isset($BowlingStats->T20)) {
                $PlayerCredits['T20Credits'] = $this->getT20BowlingCredits($BowlingStats->T20);
            }
            if (isset($BowlingStats->T20i)) {
                $PlayerCredits['T20iCredits'] = $this->getT20iBowlingCredits($BowlingStats->T20i);
            }
            if (isset($BowlingStats->ODI)) {
                $PlayerCredits['ODICredits'] = $this->getODIBowlingCredits($BowlingStats->ODI);
            }
            if (isset($BowlingStats->Test)) {
                $PlayerCredits['TestCredits'] = $this->getTestBowlingCredits($BowlingStats->Test);
            }
        } else if ($PlayerRole == 'WicketKeeper') {
            if (isset($BattingStats->T20)) {
                $T20Credits = $this->getT20BattingCredits($BattingStats->T20);
                if ($T20Credits < 10.5) {
                    $T20Credits = number_format((float) ($T20Credits + 0.5), 2, '.', '');
                }
                $PlayerCredits['T20Credits'] = $T20Credits;
            }
            if (isset($BattingStats->T20i)) {
                $T20iCredits = $this->getT20iBattingCredits($BattingStats->T20i);
                if ($T20iCredits < 10.5) {
                    $T20iCredits = number_format((float) ($T20iCredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['T20iCredits'] = $T20iCredits;
            }
            if (isset($BattingStats->ODI)) {
                $ODICredits = $this->getODIBattingCredits($BattingStats->ODI);
                if ($ODICredits < 10.5) {
                    $ODICredits = number_format((float) ($ODICredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['ODICredits'] = $ODICredits;
            }
            if (isset($BattingStats->Test)) {
                $TestCredits = $this->getTestBattingCredits($BattingStats->Test);
                if ($TestCredits < 10.5) {
                    $TestCredits = number_format((float) ($TestCredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['TestCredits'] = $TestCredits;
            }
        } else if ($PlayerRole == 'AllRounder') {
            if (isset($BattingStats->T20) && isset($BowlingStats->T20)) {
                $T20BattingCredits = $this->getT20BattingCredits($BattingStats->T20);
                $T20BowlingCredits = $this->getT20BowlingCredits($BowlingStats->T20);
                $T20CreditPoints = number_format((float) ($T20BattingCredits + $T20BowlingCredits) / 2, 2, '.', '');
                if ($T20CreditPoints >= 6 && $T20CreditPoints <= 6.49) {
                    $T20CreditPoints = 6.5;
                } else if ($T20CreditPoints >= 6.5 && $T20CreditPoints <= 6.99) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 7 && $T20CreditPoints <= 7.49) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 7.5 && $T20CreditPoints <= 7.99) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 8 && $T20CreditPoints <= 8.49) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints >= 8.50) {
                    $T20CreditPoints = 10.5;
                }
                $PlayerCredits['T20Credits'] = $T20CreditPoints;
            }
            if (isset($BattingStats->T20i) && isset($BowlingStats->T20i)) {
                $T20iBattingCredits = $this->getT20iBattingCredits($BattingStats->T20i);
                $T20iBowlingCredits = $this->getT20iBowlingCredits($BowlingStats->T20i);
                $T20iCreditPoints = number_format((float) ($T20iBattingCredits + $T20iBowlingCredits) / 2, 2, '.', '');
                if ($T20iCreditPoints >= 6 && $T20iCreditPoints <= 6.49) {
                    $T20iCreditPoints = 6.5;
                } else if ($T20iCreditPoints >= 6.5 && $T20iCreditPoints <= 6.99) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 7 && $T20iCreditPoints <= 7.49) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 7.5 && $T20iCreditPoints <= 7.99) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 8 && $T20iCreditPoints <= 8.49) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints >= 8.50) {
                    $T20iCreditPoints = 10.5;
                }
                $PlayerCredits['T20iCredits'] = $T20iCreditPoints;
            }
            if (isset($BattingStats->ODI) && isset($BowlingStats->ODI)) {
                $ODIBattingCredits = $this->getODIBattingCredits($BattingStats->ODI);
                $ODIBowlingCredits = $this->getODIBowlingCredits($BowlingStats->ODI);
                $ODICreditPoints = number_format((float) ($ODIBattingCredits + $ODIBowlingCredits) / 2, 2, '.', '');
                if ($ODICreditPoints >= 6 && $ODICreditPoints <= 6.49) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 6.5 && $ODICreditPoints <= 6.99) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 7 && $ODICreditPoints <= 7.24) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 7.25 && $ODICreditPoints <= 7.49) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 7.5 && $ODICreditPoints <= 7.74) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 7.75 && $ODICreditPoints <= 8.25) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints >= 8.26 && $ODICreditPoints <= 8.75) {
                    $ODICreditPoints = 10;
                } else if ($ODICreditPoints > 8.75) {
                    $ODICreditPoints = 10.5;
                }
                $PlayerCredits['ODICredits'] = $ODICreditPoints;
            }
            if (isset($BattingStats->Test) && isset($BowlingStats->Test)) {
                $TestBattingCredits = $this->getTestBattingCredits($BattingStats->Test);
                $TestBowlingCredits = $this->getTestBowlingCredits($BowlingStats->Test);
                $TestCreditPoints = number_format((float) ($TestBattingCredits + $TestBowlingCredits) / 2, 2, '.', '');
                if ($TestCreditPoints >= 6 && $TestCreditPoints <= 6.49) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 6.5 && $TestCreditPoints <= 6.99) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 7 && $TestCreditPoints <= 7.24) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 7.25 && $TestCreditPoints <= 7.49) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 7.5 && $TestCreditPoints <= 7.74) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 7.75 && $TestCreditPoints <= 8.25) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints >= 8.26 && $TestCreditPoints <= 8.75) {
                    $TestCreditPoints = 10;
                } else if ($TestCreditPoints > 8.75) {
                    $TestCreditPoints = 10.5;
                }
                $PlayerCredits['TestCredits'] = $TestCreditPoints;
            }
        }
        return $PlayerCredits;
    }

    /*
      Description: Use to get T20 batting credits
     */

    function getT20BattingCredits($T20Batting = array()) {
        $T20CreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20Batting)) {
            $T20BattingAverage = $T20Batting->Average;
            $StrikeRate = $T20Batting->StrikeRate;
            $InningPlayed = $T20Batting->Innings;
            if (!empty($T20BattingAverage)) {
                $T20CreditPoints = number_format((float) ($StrikeRate / 100) * $T20BattingAverage, 2, '.', '');
                if ($T20CreditPoints >= 20 && $T20CreditPoints <= 25) {
                    $T20CreditPoints = 7;
                } else if ($T20CreditPoints >= 25.01 && $T20CreditPoints <= 30) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 30.01 && $T20CreditPoints <= 35) {
                    $T20CreditPoints = 8;
                } else if ($T20CreditPoints >= 35.01 && $T20CreditPoints <= 40) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 40.01 && $T20CreditPoints <= 45) {
                    $T20CreditPoints = 9;
                } else if ($T20CreditPoints >= 45.01 && $T20CreditPoints <= 50) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 50.01 && $T20CreditPoints <= 55) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints > 55) {
                    $T20CreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20CreditPoints > 7 && $T20CreditPoints <= 7.99) {
                        $T20CreditPoints = 7;
                    } else if ($T20CreditPoints >= 8) {
                        $T20CreditPoints = $T20CreditPoints - 1;
                    }
                }
            }
        }
        return $T20CreditPoints;
    }

    /*
      Description: Use to get T20i batting credits
     */

    function getT20iBattingCredits($T20iBatting = array()) {
        $T20iCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20iBatting)) {
            $T20iBattingAverage = $T20iBatting->Average;
            $StrikeRate = $T20iBatting->StrikeRate;
            $InningPlayed = $T20iBatting->Innings;
            if (!empty($T20iBattingAverage)) {
                $T20iCreditPoints = number_format((float) ($StrikeRate / 100) * $T20iBattingAverage, 2, '.', '');
                if ($T20iCreditPoints >= 20 && $T20iCreditPoints <= 25) {
                    $T20iCreditPoints = 7;
                } else if ($T20iCreditPoints >= 25.01 && $T20iCreditPoints <= 30) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 30.01 && $T20iCreditPoints <= 35) {
                    $T20iCreditPoints = 8;
                } else if ($T20iCreditPoints >= 35.01 && $T20iCreditPoints <= 40) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 40.01 && $T20iCreditPoints <= 45) {
                    $T20iCreditPoints = 9;
                } else if ($T20iCreditPoints >= 45.01 && $T20iCreditPoints <= 50) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 50.01 && $T20iCreditPoints <= 55) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints > 55) {
                    $T20iCreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20iCreditPoints > 7 && $T20iCreditPoints <= 7.99) {
                        $T20iCreditPoints = 7;
                    } else if ($T20iCreditPoints >= 8) {
                        $T20iCreditPoints = $T20iCreditPoints - 1;
                    }
                }
            }
        }
        return $T20iCreditPoints;
    }

    /*
      Description: Use to get ODI batting credits
     */

    function getODIBattingCredits($ODIBatting = array()) {
        $ODICreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($ODIBatting)) {
            $ODIBattingAverage = $ODIBatting->Average;
            $OdiStrikeRate = $ODIBatting->StrikeRate;
            $InningPlayed = $ODIBatting->Innings;
            if (!empty($ODIBattingAverage)) {
                $ODICreditPoints = number_format((float) (sqrt($OdiStrikeRate / 100)) * $ODIBattingAverage, 2, '.', '');
                if ($ODICreditPoints >= 20 && $ODICreditPoints <= 30) {
                    $ODICreditPoints = 6.5;
                } else if ($ODICreditPoints >= 30.01 && $ODICreditPoints <= 35) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 35.01 && $ODICreditPoints <= 40) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 40.01 && $ODICreditPoints <= 45) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 45.01 && $ODICreditPoints <= 50) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 50.01 && $ODICreditPoints <= 55) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 55.01 && $ODICreditPoints <= 60) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints > 60) {
                    $ODICreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($ODICreditPoints > 7 && $ODICreditPoints <= 7.99) {
                        $ODICreditPoints = 7;
                    } else if ($ODICreditPoints >= 8) {
                        $ODICreditPoints = $ODICreditPoints - 1;
                    }
                }
            }
        }
        return $ODICreditPoints;
    }

    /*
      Description: Use to get Test batting credits
     */

    function getTestBattingCredits($TestBatting = array()) {
        $TestCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($TestBatting)) {
            $InningPlayed = $TestBatting->Innings;
            $TestBattingAverage = $TestBatting->Average;
            if (!empty($TestBattingAverage)) {
                $TestCreditPoints = number_format((float) $TestBattingAverage, 2, '.', '');
                if ($TestCreditPoints >= 20 && $TestCreditPoints <= 30) {
                    $TestCreditPoints = 6.5;
                } else if ($TestCreditPoints >= 30.01 && $TestCreditPoints <= 35) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 35.01 && $TestCreditPoints <= 40) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 40.01 && $TestCreditPoints <= 45) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 45.01 && $TestCreditPoints <= 50) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 50.01 && $TestCreditPoints <= 55) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 55.01 && $TestCreditPoints <= 60) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints > 60) {
                    $TestCreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($TestCreditPoints > 7 && $TestCreditPoints <= 7.99) {
                        $TestCreditPoints = 7;
                    } else if ($TestCreditPoints >= 8) {
                        $TestCreditPoints = $TestCreditPoints - 1;
                    }
                }
            }
        }
        return $TestCreditPoints;
    }

    /*
      Description: Use to get T20 bowling credits
     */

    function getT20BowlingCredits($T20Bowling = array()) {
        $T20CreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20Bowling)) {
            $T20BowlingAverage = $T20Bowling->Average;
            $T20BowlingWickets = $T20Bowling->Wickets;
            $T20BowlingInnings = $T20Bowling->Innings;
            $T20BowlingEconomyRate = $T20Bowling->Economy;
            $InningPlayed = $T20Bowling->Innings;
            if (!empty($T20BowlingAverage)) {
                $T20CreditPoints = number_format((float) (($T20BowlingEconomyRate * $T20BowlingEconomyRate) * $T20BowlingInnings) / (10 * $T20BowlingWickets), 2, '.', '');
                if ($T20CreditPoints > 8) {
                    $T20CreditPoints = 7;
                } else if ($T20CreditPoints >= 7.01 && $T20CreditPoints <= 8) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 6.01 && $T20CreditPoints <= 7) {
                    $T20CreditPoints = 8;
                } else if ($T20CreditPoints >= 5.01 && $T20CreditPoints <= 6) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 4.51 && $T20CreditPoints <= 5) {
                    $T20CreditPoints = 9;
                } else if ($T20CreditPoints >= 4.01 && $T20CreditPoints <= 4.5) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 3.51 && $T20CreditPoints <= 4) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints <= 3.5) {
                    $T20CreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20CreditPoints > 7 && $T20CreditPoints <= 7.99) {
                        $T20CreditPoints = 7;
                    } else if ($T20CreditPoints >= 8) {
                        $T20CreditPoints = $T20CreditPoints - 1;
                    }
                }
            }
        }
        return $T20CreditPoints;
    }

    /*
      Description: Use to get T20i bowling credits
     */

    function getT20iBowlingCredits($T20iBowling = array()) {
        $T20iCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20iBowling)) {
            $T20iBowlingAverage = $T20iBowling->Average;
            $T20iBowlingWickets = $T20iBowling->Wickets;
            $T20iBowlingInnings = $T20iBowling->Innings;
            $T20iBowlingEconomyRate = $T20iBowling->Economy;
            $InningPlayed = $T20iBowling->Innings;
            if (!empty($T20iBowlingAverage)) {
                $T20iCreditPoints = number_format((float) (($T20iBowlingEconomyRate * $T20iBowlingEconomyRate) * $T20iBowlingInnings) / (10 * $T20iBowlingWickets), 2, '.', '');
                if ($T20iCreditPoints > 8) {
                    $T20iCreditPoints = 7;
                } else if ($T20iCreditPoints >= 7.01 && $T20iCreditPoints <= 8) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 6.01 && $T20iCreditPoints <= 7) {
                    $T20iCreditPoints = 8;
                } else if ($T20iCreditPoints >= 5.01 && $T20iCreditPoints <= 6) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 4.51 && $T20iCreditPoints <= 5) {
                    $T20iCreditPoints = 9;
                } else if ($T20iCreditPoints >= 4.01 && $T20iCreditPoints <= 4.5) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 3.51 && $T20iCreditPoints <= 4) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints <= 3.5) {
                    $T20iCreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20iCreditPoints > 7 && $T20iCreditPoints <= 7.99) {
                        $T20iCreditPoints = 7;
                    } else if ($T20iCreditPoints >= 8) {
                        $T20iCreditPoints = $T20iCreditPoints - 1;
                    }
                }
            }
        }
        return $T20iCreditPoints;
    }

    /*
      Description: Use to get ODI bowling credits
     */

    function getODIBowlingCredits($ODIBowling = array()) {
        $ODICreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($ODIBowling)) {
            $ODIBowlingAverage = $ODIBowling->Average;
            $ODIBowlingWickets = $ODIBowling->Wickets;
            $ODIBowlingInnings = $ODIBowling->Innings;
            $ODIBowlingEconomyRate = $ODIBowling->Economy;
            $InningPlayed = $ODIBowling->Innings;
            if (!empty($ODIBowlingAverage)) {
                $ODICreditPoints = number_format((float) ($ODIBowlingEconomyRate * $ODIBowlingInnings) / $ODIBowlingWickets, 2, '.', '');
                if ($ODICreditPoints > 7) {
                    $ODICreditPoints = 6.5;
                } else if ($ODICreditPoints >= 6.01 && $ODICreditPoints <= 7) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 5.01 && $ODICreditPoints <= 6) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 4.01 && $ODICreditPoints <= 5) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 3.01 && $ODICreditPoints <= 4) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 2.51 && $ODICreditPoints <= 3) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 2.01 && $ODICreditPoints <= 2.5) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints <= 2) {
                    $ODICreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($ODICreditPoints > 7 && $ODICreditPoints <= 7.99) {
                        $ODICreditPoints = 7;
                    } else if ($ODICreditPoints >= 8) {
                        $ODICreditPoints = $ODICreditPoints - 1;
                    }
                }
            }
        }
        return $ODICreditPoints;
    }

    /*
      Description: Use to get Test bowling credits
     */

    function getTestBowlingCredits($TestBowling = array()) {
        $TestCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($TestBowling)) {
            $TestBowlingAverage = $TestBowling->Average;
            $TestBowlingWickets = $TestBowling->Wickets;
            $TestBowlingInnings = $TestBowling->Innings;
            $InningPlayed = $TestBowling->Innings;
            if (!empty($TestBowlingAverage)) {
                $TestCreditPoints = number_format((float) ($TestBowlingWickets / $TestBowlingInnings), 2, '.', '');
                if ($TestCreditPoints > 7) {
                    $TestCreditPoints = 6.5;
                } else if ($TestCreditPoints >= 6.01 && $TestCreditPoints <= 7) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 5.01 && $TestCreditPoints <= 6) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 4.01 && $TestCreditPoints <= 5) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 3.01 && $TestCreditPoints <= 4) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 2.51 && $TestCreditPoints <= 3) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 2.01 && $TestCreditPoints <= 2.5) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints <= 2) {
                    $TestCreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($TestCreditPoints > 7 && $TestCreditPoints <= 7.99) {
                        $TestCreditPoints = 7;
                    } else if ($TestCreditPoints >= 8) {
                        $TestCreditPoints = $TestCreditPoints - 1;
                    }
                }
            }
        }
        return $TestCreditPoints;
    }

    function match_players_best_played($Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $return['code'] = 200;

        $MatchData = $this->Sports_model->getMatches('MatchID,TeamIDLocal,TeamIDVisitor,SeriesID,MatchType,PlayerSalary', array('MatchID' => $Where['MatchID']), TRUE, 0);
        $dataArr = array();

        $playersData = $this->Sports_model->getPlayers('PlayerRole,PlayerPic,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID,PlayerBattingStats,PlayerBowlingStats,IsPlaying,PointsData,PlayerSalary,TeamNameShort,PlayerPosition,TotalPoints', array_merge($this->Post, array('MatchID' => @$this->MatchID)), TRUE, 0);
        $Wicketkipper = $this->findKeyValuePlayers($playersData['Data']['Records'], "WicketKeeper");
        $Batsman = $this->findKeyValuePlayers($playersData['Data']['Records'], "Batsman");
        $Bowler = $this->findKeyValuePlayers($playersData['Data']['Records'], "Bowler");
        $Allrounder = $this->findKeyValuePlayers($playersData['Data']['Records'], "AllRounder");

        usort($Batsman, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Bowler, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Wicketkipper, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Allrounder, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });

        $TopBatsman = array_slice($Batsman, 0, 4);
        $TopBowler = array_slice($Bowler, 0, 3);
        $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
        $TopAllrounder = array_slice($Allrounder, 0, 3);

        $AllPlayers = array();
        $AllPlayers = array_merge($TopBatsman, $TopBowler);
        $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
        $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

        rsort($AllPlayers, function($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        foreach ($AllPlayers as $key => $value) {
            $AllPlayers[$key]['PlayerPosition'] = 'Player';
            $AllPlayers[0]['PlayerPosition'] = 'Captain';
            $AllPlayers[1]['PlayerPosition'] = 'ViceCaptain';
            $TotalCalculatedPoints += $value['TotalPoints'];
        }
        // print_r($TotalCalculatedPoints); die;
        $Records['Data']['Records'] = $AllPlayers;
        $Records['Data']['TotalPoints'] = $TotalCalculatedPoints;
        $Records['Data']['TotalRecords'] = count($AllPlayers);
        if ($AllPlayers) {
            return $Records;
        } else {
            return false;
        }
    }

    function findKeyValuePlayers($array, $value) {
        if (is_array($array)) {
            $players = array();
            foreach ($array as $key => $rows) {
                if ($rows['PlayerRole'] == $value) {
                    $players[] = $array[$key];
                }
            }
            return $players;
        }
        return false;
    }

    function findKeyArrayDiff($array, $value) {
        if (is_array($array)) {
            $players = array();
            foreach ($array as $key => $rows) {
                if ($rows['PlayerID'] == $value) {
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    /*
      Description: To get sports best played players of the match
     */

    function getMatchBestPlayers($Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        /* Get Match Players */
        $playersData = $this->Sports_model->getPlayers('PlayerRole,PlayerPic,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID,IsPlaying,PlayerSalary,TeamNameShort,PlayerPosition,TotalPoints', array('MatchID' => $Where['MatchID'], 'OrderBy' => 'TotalPoints', 'Sequence' => 'DESC', 'IsPlaying' => 'Yes'), TRUE, 0);
        if (!$playersData) {
            return false;
        }
        $finalXIPlayers = array();
        foreach ($playersData['Data']['Records'] as $Key => $Value) {
            $Row = $Value;
            $Row['PlayerPosition'] = ($Key == 0) ? 'Captain' : (($Key == 1) ? 'ViceCaptain' : 'Player');
            $Row['TotalPoints'] = strval(($Key == 0) ? 2 * $Row['TotalPoints'] : (($Key == 1) ? 1.5 * $Row['TotalPoints'] : $Row['TotalPoints']));
            array_push($finalXIPlayers, $Row);
        }

        $Batsman = $this->findKeyValuePlayers($finalXIPlayers, "Batsman");
        $Bowler = $this->findKeyValuePlayers($finalXIPlayers, "Bowler");
        $Wicketkipper = $this->findKeyValuePlayers($finalXIPlayers, "WicketKeeper");
        $Allrounder = $this->findKeyValuePlayers($finalXIPlayers, "AllRounder");

        $TopBatsman = array_slice($Batsman, 0, 4);
        $TopBowler = array_slice($Bowler, 0, 3);
        $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
        $TopAllrounder = array_slice($Allrounder, 0, 3);

        $BatsmanSort = $BowlerSort = $WicketKipperSort = $AllRounderSort = array();
        foreach ($TopBatsman as $BatsmanValue) {
            $BatsmanSort[] = $BatsmanValue['TotalPoints'];
        }
        array_multisort($BatsmanSort, SORT_DESC, $TopBatsman);

        foreach ($TopBowler as $BowlerValue) {
            $BowlerSort[] = $BowlerValue['TotalPoints'];
        }
        array_multisort($BowlerSort, SORT_DESC, $TopBowler);

        foreach ($TopWicketkipper as $WicketKipperValue) {
            $WicketKipperSort[] = $WicketKipperValue['TotalPoints'];
        }
        array_multisort($WicketKipperSort, SORT_DESC, $TopWicketkipper);

        foreach ($TopAllrounder as $AllrounderValue) {
            $AllRounderSort[] = $AllrounderValue['TotalPoints'];
        }
        array_multisort($AllRounderSort, SORT_DESC, $TopAllrounder);

        $AllPlayers = array();
        $AllPlayers = array_merge($TopBatsman, $TopBowler);
        $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
        $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

        $TotalCalculatedPoints = 0;
        foreach ($AllPlayers as $Value) {
            $TotalCalculatedPoints += $Value['TotalPoints'];
        }

        $Records['Data']['Records'] = $AllPlayers;
        $Records['Data']['TotalPoints'] = strval($TotalCalculatedPoints);
        $Records['Data']['TotalRecords'] = count($AllPlayers);
        if ($AllPlayers) {
            return $Records;
        } else {
            return FALSE;
        }
    }

    /*
      Description: To get sports player fantasy stats series wise
     */

    function getPlayerFantasyStats($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'MatchNo' => 'M.MatchNo',
                'MatchLocation' => 'M.MatchLocation',
                'MatchStartDateTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") MatchStartDateTime',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'TeamNameShortLocal' => 'TL.TeamNameShort AS TeamNameShortLocal',
                'TeamNameShortVisitor' => 'TV.TeamNameShort AS TeamNameShortVisitor',
                'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'TotalPoints' => 'TP.TotalPoints',
                'TotalTeams' => '(SELECT COUNT(UserTeamID) FROM `sports_users_teams` WHERE `MatchID` = M.MatchID) TotalTeams'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('M.MatchGUID,M.MatchID,TP.PlayerID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_matches M, sports_teams TL, sports_teams TV, sports_team_players TP');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("M.MatchID", "TP.MatchID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("TP.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("TP.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('M.MatchStartDateTime', 'DESC');
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
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    if (in_array('PlayerSelectedPercent', $Params)) {
                        $this->db->select('COUNT(SUTP.PlayerID) TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.MatchID", $Record['MatchID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = ($Record['TotalTeams'] > 0) ? strval(round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2)) : "0";
                    }
                    unset($Records[$key]['PlayerID'], $Records[$key]['MatchID']);
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                if (in_array('PlayerSelectedPercent', $Params)) {
                    $this->db->select('COUNT(SUTP.PlayerID) TotalPlayer');
                    $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                    $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                    $this->db->where("SUTP.MatchID", $Record['MatchID']);
                    $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                    $Players = $this->db->get()->row();
                    $Record['PlayerSelectedPercent'] = ($Record['TotalTeams'] > 0) ? strval(round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2)) : "0";
                }
                unset($Record['PlayerID'], $Record['MatchID']);
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: To set matches game data (Goalserve API)
     */

    function getLeagueMatchesLiveGoalServe($CronID) {
        ini_set('max_execution_time', 120);

        /* Update Existing Series Status */

        $Response = $this->ExecuteCurlXML(SPORTS_API_URL_GOALSERVE . '/football/nfl-shedule');
        $Response = @simplexml_load_string($Response);
        if (empty($Response)) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        for ($i = 0; $i < count($Response->tournament); $i++) {
            $SeriesIDLive = (int) $Response->tournament[$i]['id'];
            $SeriesName = (string) $Response->tournament[$i]['name'];
            if (strtolower($SeriesName) != "regular season")
                continue;

            for ($j = 0; $j < count($Response->tournament[$i]->week); $j++) {
                $weekName = (string) $Response->tournament[$i]->week[$j]['name'];
                $Week = explode(" ", trim($weekName));
                $WeekID = $Week[1];
                for ($p = 0; $p < count($Response->tournament[$i]->week[$j]->matches); $p++) {
                    for ($k = 0; $k < count($Response->tournament[$i]->week[$j]->matches[$p]->match); $k++) {
                        $MatchTime = (string) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]['time'];
                        $MatchIDLive = (int) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]['contestID'];
                        $MatchDate = (string) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]['formatted_date'];
                        $MatchDate = date('Y-m-d', strtotime($MatchDate));
                        $MatchTime = date('H:i', strtotime($MatchTime));
                        $MatchDateTime = $MatchDate . ' ' . $MatchTime;
                        $LocalteamIDLive = (int) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]->hometeam['id'];
                        $VisitorteamIDLive = (int) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]->awayteam['id'];
                        $LocalteamName = (string) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]->hometeam['name'];
                        $VisitorteamName = (string) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]->awayteam['name'];
                        $MatchStatus = strtolower((string) $Response->tournament[$i]->week[$j]->matches[$p]->match[$k]['status']);
                        //if ($MatchStatus == "not started") {
                        if (!empty($LocalteamIDLive) && !empty($VisitorteamIDLive)) {
                            $SeriesID = $this->setSeries($SeriesIDLive, $SeriesName);
                            $LocalTeamID = $this->setTeam($LocalteamIDLive, $LocalteamName);
                            $VisitorTeamID = $this->setTeam($VisitorteamIDLive, $VisitorteamName);

                            /* To check if match is already exist */
                            $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $MatchIDLive . ' LIMIT 1');
                            $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : FALSE;
                            if (!$MatchID) {

                                /* Add matches to entity table and get EntityID. */
                                $MatchGUID = get_guid();
                                $MatchesAPIData = array(
                                    'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1)),
                                    'MatchGUID' => $MatchGUID,
                                    'MatchIDLive' => $MatchIDLive,
                                    'SeriesID' => $SeriesID,
                                    'MatchTypeID' => 12,
                                    'WeekID' => $WeekID,
                                    'TeamIDLocal' => $LocalTeamID,
                                    'TeamIDVisitor' => $VisitorTeamID,
                                    'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchDateTime))
                                );
                                $this->db->insert('sports_matches', $MatchesAPIData);
                            } else {
                                if ($Query->row()->StatusID != 1)
                                    continue; // Pending Match

                                    /* Update Match Data */
                                $MatchesAPIData = array(
                                    'TeamIDLocal' => $LocalTeamID,
                                    'TeamIDVisitor' => $VisitorTeamID,
                                    'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchDateTime)),
                                    'LastUpdatedOn' => date('Y-m-d H:i:s')
                                );
                                $this->db->where('MatchID', $MatchID);
                                $this->db->limit(1);
                                $this->db->update('sports_matches', $MatchesAPIData);
                            }
                        }
                        // }
                    }
                }
            }
        }
    }

    /*
      Description: To set players data
     */

    function getPlayersLiveGoalServe($CronID) {
        ini_set('max_execution_time', 180);

        /* Get team data */
        $Query = $this->db->query('SELECT TeamGUID,TeamID,TeamIDLive,TeamFlag FROM sports_teams');
        $Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
        if (!$Teams) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($Teams as $Team) {
            $TeamGUID = $Team['TeamGUID'];
            $TeamIDLive = $Team['TeamIDLive'];
            $TeamID = $Team['TeamID'];
            $TeamFlag = $Team['TeamFlag'];

            $Response = $this->ExecuteCurlXML(SPORTS_API_URL_GOALSERVE . '/football/' . $TeamIDLive . '_rosters');
            $Response = @simplexml_load_string($Response);
            if (empty($Response))
                continue;

            $TeamFlagImage = "";
            if (!$TeamFlag) {
                $TeamFlagLive = $Response->image;
                if (!empty($TeamFlagLive)) {
                    $image_base64 = base64_decode($TeamFlagLive);
                    $f = finfo_open();
                    $mime_type = finfo_buffer($f, $image_base64, FILEINFO_MIME_TYPE);
                    if ($mime_type == "image/png") {
                        $team_image = 'uploads/TeamFlag/' . $TeamGUID . '.png';
                        $TeamFlagImage = $TeamGUID . '.png';
                        file_put_contents($team_image, $image_base64);
                    } else if ($mime_type == "image/jpeg") {
                        $team_image = 'uploads/TeamFlag/' . $TeamGUID . '.jpeg';
                        $TeamFlagImage = $TeamGUID . '.jpeg';
                        file_put_contents($team_image, $image_base64);
                    } else if ($mime_type == "image/jpg") {
                        $team_image = 'uploads/TeamFlag/' . $TeamGUID . '.jpg';
                        $TeamFlagImage = $TeamGUID . '.jpg';
                        file_put_contents($team_image, $image_base64);
                    } else if ($mime_type == "image/gif") {
                        $team_image = 'uploads/TeamFlag/' . $TeamGUID . '.gif';
                        $TeamFlagImage = $TeamGUID . '.gif';
                        file_put_contents($team_image, $image_base64);
                    }
                }
            }
            if (!empty($TeamFlagImage)) {
                $this->db->where('TeamID', $TeamID);
                $this->db->limit(1);
                $this->db->update('sports_teams', array('TeamFlag' => $TeamFlagImage));
            }

            for ($i = 0; $i < count($Response->position); $i++) {
                $position = strtolower((string) $Response->position[$i]['name']);
                for ($j = 0; $j < count($Response->position[$i]->player); $j++) {
                    $PlayerIDLive = (int) $Response->position[$i]->player[$j]['id'];
                    $PlayerName = (string) $Response->position[$i]->player[$j]['name'];
                    $play_role = strtoupper((string) $Response->position[$i]->player[$j]['position']);
                    $player_salarycap = ltrim((string) $Response->position[$i]->player[$j]['salarycap'], '$');
                    $pRole = "";
                    if ($play_role == "QB") {
                        $pRole = "QuarterBack";
                    } else if ($play_role == "RB") {
                        $pRole = "RunningBack";
                    } else if ($play_role == "FB") {
                        $pRole = "FullBack";
                    } else if ($play_role == "WR") {
                        $pRole = "WideReceiver";
                    } else if ($play_role == "TE") {
                        $pRole = "TightEnd";
                    } else if ($play_role == "C") {
                        $pRole = "Center";
                    } else if ($play_role == "G") {
                        $pRole = "Guard";
                    } else if ($play_role == "OT") {
                        $pRole = "OffenseTackle";
                    } else if ($play_role == "DE") {
                        $pRole = "DefenseEnd";
                    } else if ($play_role == "DT") {
                        $pRole = "DefenseTackle";
                    } else if ($play_role == "LB") {
                        $pRole = "LineBacker";
                    } else if ($play_role == "CB") {
                        $pRole = "CornerBack";
                    } else if ($play_role == "S") {
                        $pRole = "Safety";
                    } else if ($play_role == "PK") {
                        $pRole = "Placekicker";
                    } else if ($play_role == "LS") {
                        $pRole = "LongSnapper";
                    } else if ($play_role == "P") {
                        $pRole = "Punter";
                    } else if ($play_role == "NT") {
                        $pRole = "DefenseEnd";
                    }

                    /* To check if player is already exist */
                    $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = ' . $PlayerIDLive . ' LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                    if ($PlayerID)
                        continue;

                    $this->db->trans_start();
                    /* Add players to entity table and get EntityID. */
                    $PlayerGUID = get_guid();
                    $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                    $PlayersAPIData = array(
                        'TeamID' => $TeamID,
                        'PlayerID' => $PlayerID,
                        'PlayerGUID' => $PlayerGUID,
                        'PlayerIDLive' => $PlayerIDLive,
                        'PlayerName' => $PlayerName,
                        'PlayerRole' => $pRole,
                        'PlayerSalary' => $player_salarycap,
                        'Position' => ucwords($position)
                    );
                    $this->db->insert('sports_players', $PlayersAPIData);

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        return FALSE;
                    }
                }
            }
        }

        $this->setMatchPlayers();
    }

    /*
      Description: To set matches player data
     */

    function setMatchPlayers() {
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesYear' => date('Y')), TRUE, 0);
        if (empty($SeriesData))
            return FALSE;

        /* To get all match ids */
        if (empty($SeriesData['Data']['Records']))
            return FALSE;

        foreach ($SeriesData['Data']['Records'] as $Series) {
            $Query = $this->db->query('SELECT MatchID,WeekID,TeamIDLocal,TeamIDVisitor FROM `sports_matches` WHERE `SeriesID` = ' . $Series['SeriesID'] . '');
            $Matches = ($Query->num_rows() > 0) ? $Query->result_array() : array();
            if (!empty($Matches)) {
                foreach ($Matches as $Match) {
                    $MatchID = $Match['MatchID'];
                    $WeekID = $Match['WeekID'];
                    $TeamIDs = $Match['TeamIDLocal'] . ',' . $Match['TeamIDVisitor'];
                    $Query = $this->db->query('SELECT PlayerID,PlayerRole,PlayerSalary,TeamID,Position FROM `sports_players` WHERE `TeamID` IN (' . $TeamIDs . ')');
                    $Players = ($Query->num_rows() > 0) ? $Query->result_array() : array();
                    if (!empty($Players)) {
                        foreach ($Players as $Player) {
                            $PlayerID = $Player['PlayerID'];
                            $PlayerRole = $Player['PlayerRole'];
                            $PlayerSalary = str_replace(",", "", $Player['PlayerSalary']);
                            $Position = $Player['Position'];
                            $TeamID = $Player['TeamID'];

                            if (empty($PlayerRole))
                                continue;

                            $Query = $this->db->query('SELECT PlayerID FROM `sports_team_players` WHERE `SeriesID` = ' . $Series['SeriesID'] . ' AND `MatchID` = ' . $MatchID . ' AND `PlayerID` = ' . $PlayerID . ' LIMIT 1');
                            $PlayerExists = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                            if ($PlayerExists)
                                continue;

                            $MatchPlayerData = array(
                                'PlayerID' => $PlayerID,
                                'SeriesID' => $Series['SeriesID'],
                                'MatchID' => $MatchID,
                                'TeamID' => $TeamID,
                                'PlayerRole' => $PlayerRole,
                                'Position' => $Position,
                                'PlayerSalary' => $PlayerSalary,
                                'IsPlaying' => 'Yes'
                            );
                            $this->db->insert('sports_team_players', $MatchPlayerData);
                        }
                    }
                }
            }
        }
    }

    /*
      Description: (GoalServe API Data)
     */

    function ExecuteCurlXML($Url, $Params = '') {
        $Curl = curl_init($Url);
        if (!empty($Params)) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);
        }
        curl_setopt($Curl, CURLOPT_HEADER, 0);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, TRUE);
        $Response = curl_exec($Curl);
        curl_close($Curl);
        return $Response;
    }

    /*
      Description: To set series data (GoalServe API)
     */

    function setSeries($SeriesID, $SeriesName) {
        /* To get All Series Data */
        $Year = date('Y');
        $Query = $this->db->query('SELECT SeriesID FROM sports_series WHERE SeriesIDLive=' . $SeriesID . ' AND SeriesYear=' . $Year . ' LIMIT 1');
        $SeriesIdsData = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : FALSE;
        if ($SeriesIdsData)
            return $SeriesIdsData;

        /* Add series to entity table and get EntityID. */
        $SeriesGUID = get_guid();
        $SeriesEntityID = $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2));
        $InsertData = array_filter(array(
            'SeriesID' => $SeriesEntityID,
            'SeriesGUID' => $SeriesGUID,
            'SeriesIDLive' => $SeriesID,
            'SeriesName' => $SeriesName,
            'SeriesYear' => $Year
        ));
        $this->db->insert('sports_series', $InsertData);
        return $SeriesEntityID;
    }

    /*
      Description: To set team data (GoalServe API)
     */

    function setTeam($TeamIDLive, $TeamName) {
        /* To get All Series Data */
        $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $TeamIDLive . ' LIMIT 1');
        $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : FALSE;
        if ($TeamID)
            return $TeamID;

        /* Add series to entity table and get EntityID. */
        $TeamGUID = get_guid();
        $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
        $InsertData = array_filter(array(
            'TeamID' => $TeamID,
            'TeamGUID' => $TeamGUID,
            'TeamIDLive' => $TeamIDLive,
            'TeamName' => $TeamName,
            'TeamNameShort' => NULL,
            'TeamFlag' => NULL
        ));
        $this->db->insert('sports_teams', $InsertData);
        return $TeamID;
    }

    /*
      Description: To set custom insert
     */
    public function customInsert($options) {
        $table = false;
        $data = false;

        extract($options);

        $this->db->insert($table, $data);

        return $this->db->insert_id();
    }

    /*
      Description: To set custom get
     */
    public function customGet($options) {

        $select = false;
        $table = false;
        $join = false;
        $order = false;
        $limit = false;
        $offset = false;
        $where = false;
        $or_where = false;
        $single = false;
        $group_by = false;
        $like = false;
        $or_like = false;
        $where_in = false;
        $where_not_in = false;
        $between = false;

        extract($options);

        if ($select != false)
            $this->db->select($select);

        if ($table != false)
            $this->db->from($table);

        if ($where != false)
            $this->db->where($where);
        
        if ($between != false)
            $this->db->where($between);

        if ($or_where != false)
            $this->db->or_where($or_where);

        if ($where_in != false) {
            foreach ($where_in as $key => $win) {
                $this->db->where_in($key, $win);
            }
        }
        
        if ($where_not_in != false) {
            foreach ($where_not_in as $key => $win) {
                $this->db->where_not_in($key, $win);
            }
        }

        if ($limit != false) {

            if (!is_array($limit)) {
                $this->db->limit($limit);
            } else {
                foreach ($limit as $limitval => $offset) {
                    $this->db->limit($limitval, $offset);
                }
            }
        }

        if ($like != false)
            $this->db->like($like);
        
        if($or_like != false){
            $this->db->or_like($like); 
        }

        if ($group_by != false) {

            $this->db->group_by($group_by);
        }


        if ($order != false) {

            if (is_array($order)) {
                foreach ($order as $key => $value) {

                    if (is_array($value)) {
                        foreach ($order as $orderby => $orderval) {
                            $this->db->order_by($orderby, $orderval);
                        }
                    } else {
                        $this->db->order_by($key, $value);
                    }
                }
            } else {
                $this->db->order_by($order);
            }
        }




        if ($join != false) {

            foreach ($join as $key => $value) {

                if (is_array($value)) {
                    if (count($value) == 3) {
                        $this->db->join($value[0], $value[1], $value[2]);
                    } else {
                        foreach ($value as $key1 => $value1) {
                            $this->db->join($key1, $value1);
                        }
                    }
                } else {
                    $this->db->join($key, $value);
                }
            }
        }


        $query = $this->db->get();

        if ($single) {
            return $query->row();
        }


        return $query->result();
    }

    /*
      Description: To set custom update
     */
    public function customUpdate($options) {
        $table = false;
        $where = false;
        $orwhere = false;
        $data = false;

        extract($options);

        if (!empty($where)) {
            $this->db->where($where);
        }

        // using or condition in where  
        if (!empty($orwhere)) {
            $this->db->or_where($orwhere);
        }
        $this->db->update($table, $data);

        return $this->db->affected_rows();
    }

    /*
      Description: To set matches data (FOOTBALL API NFL)
     */

    function getMatchesLiveNflGoalserve($CronID) {
        $return['code'] = 200;
        $return['response'] = new stdClass();
        $url = SPORTS_API_URL_GOALSERVE . '/football/nfl-shedule';
        $xml = simplexml_load_string(file_get_contents($url));
        if (!empty($xml)) {
            $MatchTypesData = $this->getMatchTypes();
            $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeName');

            for ($i = 0; $i < count($xml->tournament); $i++) {
                $seriesId = (int) $xml->tournament[$i]['id'];
                $seriesName = (string) trim($xml->tournament[$i]['name']);
                for ($j = 0; $j < count($xml->tournament[$i]->week); $j++) {
                    $weekName = (string) strtolower($xml->tournament[$i]->week[$j]['name']);
                    $WeekID = 0;
                    $PreWeekName = "";
                    if($i==0){
                        if (preg_match("/\bweek\b/i", $weekName)) {
                            $WeekID = trim(str_replace("week", "", $weekName));
                            $weekName = trim(str_replace("week", "p s", $weekName));
                        }
                        $PreSeasonName = explode(" ", $weekName);
                        
                        foreach ($PreSeasonName as $w) {
                          $PreWeekName .= $w[0];
                        }

                    }else if($i==1){
                        $WeekID = trim(str_replace("week", "", $weekName));
                    }elseif($i==2){
                        if($weekName == 'wild card'){
                            $WeekID = 19; //18;
                        }else if($weekName == 'divisional round'){
                            $WeekID = 20;//19;
                        }else if($weekName == 'conference championships'){
                            $WeekID = 21;//20;
                        }else if($weekName == 'pro bowl'){
                            $WeekID = 22;//21;
                            //continue;
                        }else if($weekName == 'super bowl'){
                            $WeekID = 23; //22;
                            //continue;
                        }
                    }

                    $SeasonType = "ProFootballRegularSeasonOwners";
                    $SeriesType = "Regular";
                    if($seriesName == "Pre Season"){
                        $SeriesType = "Pre";
                        $SeasonType = "ProFootballPreSeasonOwners";
                    } else if($seriesName == "Post Season"){
                        $SeriesType = "Playoffs";
                        $SeasonType = "ProFootballPlayoffs";
                    }
                    $SeriesIDLive = trim($seriesId);
                    $Query = $this->db->query("SELECT S.SeriesID FROM sports_series S,tbl_entity E WHERE S.SeriesID=E.EntityID AND S.SeriesIDLive = '" . $SeriesIDLive . "' AND E.GameSportsType='Nfl' LIMIT 1");
                    $SeriesID = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
                    if (empty($SeriesID)) {
                        /* Add series to entity table and get EntityID. */
                        $SeriesGUID = get_guid();
                        $SeriesID = $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2, "GameSportsType" => "Nfl"));
                        $SeriesData = array_filter(array(
                            'SeriesID' => $SeriesID,
                            'SeriesGUID' => $SeriesGUID,
                            'SeriesIDLive' => $SeriesIDLive,
                            'SeriesType' => $SeriesType,
                            'SeriesYear' => date('Y'),
                            'SeriesName' => "Nfl ".$seriesName .' '. date('Y'),
                            'AuctionDraftIsPlayed' => 'Yes'
                        ));
                        $this->db->insert('sports_series', $SeriesData);
                     }
                    for ($p = 0; $p < count($xml->tournament[$i]->week[$j]->matches); $p++) {

                        for ($k = 0; $k < count($xml->tournament[$i]->week[$j]->matches[$p]->match); $k++) {

                            $match_time = (string) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]['time'];
                            $MatchIDLive = (int) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]['contestID'];
                            $match_date = (string) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]['formatted_date'];
                            $match_date = date('Y-m-d', strtotime($match_date));
                            $match_time = date('H:i', strtotime($match_time));
                            $match_date_time = $match_date . ' ' . $match_time;
                            $localteam_id_live = (int) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]->hometeam['id'];
                            $visitorteam_id_live = (int) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]->awayteam['id'];
                            $localteam_name = (string) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]->hometeam['name'];
                            $visitorteam_name = (string) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]->awayteam['name'];
                            $match_status = strtolower((string) $xml->tournament[$i]->week[$j]->matches[$p]->match[$k]['status']);
                            if ($match_status == "not started") {
                                if (!empty($localteam_id_live) && !empty($visitorteam_id_live)) {
                          
                                    /* To check if local team is already exist */
                                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = " . $localteam_id_live . " AND E.GameSportsType='Nfl' LIMIT 1");
                                    $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                                    if (!$TeamIDLocal) {
                                        /* Add team to entity table and get EntityID. */
                                        $TeamGUID = get_guid();
                                        $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Nfl"));
                                        $TeamData = array(
                                            'TeamID' => $TeamIDLocal,
                                            'TeamGUID' => $TeamGUID,
                                            'TeamIDLive' => $localteam_id_live,
                                            'TeamName' => $localteam_name
                                        );
                                        $this->db->insert('sports_teams', $TeamData);
                                    }

                                    /* To check if local team is already exist */
                                    $Query = $this->db->query("SELECT T.TeamID FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND T.TeamIDLive = " . $visitorteam_id_live . " AND E.GameSportsType='Nfl' LIMIT 1");
                                    $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                                    if (!$TeamIDVisitor) {
                                        /* Add team to entity table and get EntityID. */
                                        $TeamGUID = get_guid();
                                        $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2, "GameSportsType" => "Nfl"));
                                        $TeamData = array(
                                            'TeamID' => $TeamIDVisitor,
                                            'TeamGUID' => $TeamGUID,
                                            'TeamIDLive' => $visitorteam_id_live,
                                            'TeamName' => $visitorteam_name
                                        );
                                        $this->db->insert('sports_teams', $TeamData);
                                    }
                                    /* To check if match is already exist */
                                    $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $MatchIDLive . ' AND E.GameSportsType="Nfl" LIMIT 1');
                                    $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                                    if (!$MatchID) {
                                        $MatchGUID = get_guid();
                                        $MatchID = $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1, "GameSportsType" => "Nfl"));
                                        $MatchesAPIData = array(
                                            'MatchID' => $MatchID,
                                            'MatchGUID' => $MatchGUID,
                                            'MatchIDLive' => $MatchIDLive,
                                            'SeriesID' => $SeriesID,
                                            'MatchTypeID' => $MatchTypeIdsData['Football NFL'],
                                            'MatchNo' => 1,
                                            'TeamIDLocal' => $TeamIDLocal,
                                            'TeamIDVisitor' => $TeamIDVisitor,
                                            'MatchStartDateTime' => $match_date_time,
                                            'WeekID' => $WeekID,
                                            'WeekName' => strtoupper($PreWeekName),
                                            'ScoreIDLive' => $MatchIDLive,
                                            'SeasonType' => $SeasonType
                                        );
                                        $this->db->insert('sports_matches', $MatchesAPIData);
                                    }else{


                                        $this->db->where('MatchID', $MatchID);
                                        $this->db->limit(1);
                                        $this->db->update('sports_matches', array('MatchStartDateTimeEST' => date('Y-m-d', strtotime($match_date)),
                                            'MatchStartDateTime' => $match_date_time,'TeamIDLocal'=>$TeamIDLocal,'TeamIDVisitor'=>$TeamIDVisitor,'WeekID'=>$WeekID));
                                    

                                        $MatchTeamDataLocal = array();
                                        $MatchTeamDataVisitor = array();
                                        /** local team data **/
                                        $Query = $this->db->query('SELECT P.PlayerID,P.PlayerRole FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $TeamIDLocal . ' AND E.GameSportsType="Nfl"');
                                        $Players = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
                                        if(!empty($Players)){
                                            foreach($Players as $Player){
                                                $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDLocal.'');
                                                if($Query->num_rows() <= 0){
                                                    $MatchTeamDataLocal[] = array(
                                                        'PlayerID' => $Player['PlayerID'],
                                                        'PlayerRole' => $Player['PlayerRole'],
                                                        'MatchID' => $MatchID,
                                                        'SeriesID' => $SeriesID,
                                                        'TeamID' => $TeamIDLocal,
                                                        'WeekID' => $WeekID
                                                    );
                                                }else{
                                                    $this->db->where('MatchID', $MatchID);
                                                    $this->db->where('PlayerID', $Player['PlayerID']);
                                                    $this->db->limit(1);
                                                    $this->db->update('sports_team_players', array('TeamID' => $TeamIDLocal));  
                                                }
                                            }
                                            if(!empty($MatchTeamDataLocal)){
                                               $this->db->insert_batch('sports_team_players', $MatchTeamDataLocal);  
                                            }
                                           
                                        }

                                        /** visitor team data **/
                                        $Query = $this->db->query('SELECT P.PlayerID,P.PlayerRole  FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.TeamID = ' . $TeamIDVisitor . ' AND E.GameSportsType="Nfl"');
                                        $Players = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;

                                        if(!empty($Players)){
                                            foreach($Players as $Player){
                                                $Query = $this->db->query('SELECT T.PlayerID,T.PlayerRole FROM sports_team_players T WHERE T.PlayerID='.$Player['PlayerID'].' AND T.MatchID='.$MatchID.' AND T.TeamID='.$TeamIDVisitor.'');
                                                if($Query->num_rows() <= 0){
                                                    $MatchTeamDataVisitor[] = array(
                                                        'PlayerID' => $Player['PlayerID'],
                                                        'PlayerRole' => $Player['PlayerRole'],
                                                        'MatchID' => $MatchID,
                                                        'SeriesID' => $SeriesID,
                                                        'TeamID' => $TeamIDVisitor,
                                                        'WeekID' => $WeekID
                                                    );
                                                }else{
                                                    $this->db->where('MatchID', $MatchID);
                                                    $this->db->where('PlayerID', $Player['PlayerID']);
                                                    $this->db->limit(1);
                                                    $this->db->update('sports_team_players', array('TeamID' => $TeamIDVisitor));  
                                                }
                                            }
                                            if(!empty($MatchTeamDataVisitor)){
                                              $this->db->insert_batch('sports_team_players', $MatchTeamDataVisitor);  
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /*
      Description: To set matches data (FOOTBALL API NFL)
     */
    function getPlayersLiveByTeamNflGoalserve($CronID) {
        $return['code'] = 200;
        $return['response'] = new stdClass();

        $Query = $this->db->query('SELECT T.TeamID,T.TeamGUID,T.TeamIDLive,T.TeamKey,T.TeamName,T.TeamFlag FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType="Nfl" order by T.TeamID ASC');
        $Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
        if (!$Teams) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        if (!empty($Teams)) {
            foreach ($Teams as $rows) {
                $IsPlayerRoster = array();
                $TeamIDLive = trim($rows['TeamIDLive']);
                $TeamID = trim($rows['TeamID']);
                $TeamGUID = trim($rows['TeamGUID']);
                $TeamName = trim($rows['TeamName']);
                $url1=$url = SPORTS_API_URL_GOALSERVE . '/football/' . $TeamIDLive . '_rosters';
                $xml = simplexml_load_string(file_get_contents($url));
                if (!empty($xml)) {
                        $team_flag = $xml->image;
                        $team_image = "";

                        if (!empty($team_flag) && empty($rows['TeamFlag'])) {
                            $image_base64 = base64_decode($team_flag);
                            $f = finfo_open();
                            $mime_type = finfo_buffer($f, $image_base64, FILEINFO_MIME_TYPE);
                            if ($mime_type == "image/png") {
                                $team_image = $TeamGUID . '.png';
                                $team_image1 = 'uploads/TeamFlag/'.$TeamGUID . '.png';
                                file_put_contents($team_image1, $image_base64);
                            }
                            if ($mime_type == "image/jpeg") {
                                $team_image = $TeamGUID . '.jpeg';
                                $team_image1 = 'uploads/TeamFlag/'.$TeamGUID . '.jpeg';
                                file_put_contents($team_image1, $image_base64);
                            }
                            if ($mime_type == "image/jpg") {
                                $team_image = $TeamGUID . '.jpg';
                                $team_image1 = 'uploads/TeamFlag/'.$TeamGUID . '.jpg';
                                file_put_contents($team_image1, $image_base64);
                            }
                            if ($mime_type == "image/gif") {
                                $team_image =  $TeamGUID . '.gif';
                                $team_image1 = 'uploads/TeamFlag/'.$TeamGUID . '.gif';
                                file_put_contents($team_image1, $image_base64);
                            }
                            if (!empty($team_image)) {
                                $option = array(
                                    'table' => 'sports_teams',
                                    'data' => array(
                                        'TeamFlag' => $team_image
                                    ),
                                    'where' => array(
                                        'TeamID' => $TeamID,
                                    )
                                );
                                $this->customUpdate($option);
                            }
                        }
                        for ($i = 0; $i < count($xml->position); $i++) {
                            $position = (string) $xml->position[$i]['name'];
                            for ($j = 0; $j < count($xml->position[$i]->player); $j++) {
                                $IsPlayerRoster[] =$PlayerIDLive = (int) $xml->position[$i]->player[$j]['id'];
                                $PlayerName = (string) $xml->position[$i]->player[$j]['name'];
                                $PlayerLiveRole = strtoupper((string) $xml->position[$i]->player[$j]['position']);
                                $player_salarycap = ltrim((string) $xml->position[$i]->player[$j]['salarycap'], '$');
                                $player_salarycap = str_replace(',', '', $player_salarycap);
                                $pRole = "";

                                /* player role system */
                                $Roles = array('QB' => "QuarterBack", 'RB' => "RunningBack", 'FB' => "FullBack", 'WR' => "WideReceiver", 'TE' => "TightEnd",
                                'C' => "Center", 'G' => "Guard", 'OT' => "OffenseTackle", 'DE' => "DefenseEnd", 'DT' => "DefenseTackle", 'LB' => "LineBacker",
                                'CB' => "CornerBack", 'S' => "Safety", 'SS' => "Safety", 'PK' => "Kicker", 'LS' => "LongSnapper",
                                'P' => "Punter", 'NT' => "DefenseEnd", 'OG' => "OffensiveGuard", 'OLB' => "OutsideLinebacker", 'K' => "Kicker",'DB' => "Defensive Backs");

                                /* player posotion category */
                                $Category = array("OFF" => "Offense", "DEF" => "Defense", "ST" => "Special Teams");

                                /* To check if player is already exist */
                                $Query = $this->db->query('SELECT P.PlayerID FROM sports_players P,tbl_entity E WHERE P.PlayerID=E.EntityID AND P.PlayerIDLive = ' . $PlayerIDLive . ' AND E.GameSportsType="Nfl" LIMIT 1');
                                //$PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : FALSE;
                                if ($Query->num_rows() == 0){
                                        $PlayerGUID = get_guid();
                                        $player_image = "";
                                        $url = SPORTS_API_URL_GOALSERVE. '/football/usa?playerimage=' . $PlayerIDLive;
                                        $xmlPlayer = simplexml_load_string(file_get_contents($url));
                                        if (!empty($xmlPlayer)) {
                                            $playerImage = $xmlPlayer[0];
                                            $image_base64 = base64_decode($playerImage);
                                            $f = finfo_open();
                                            $mime_type = finfo_buffer($f, $image_base64, FILEINFO_MIME_TYPE);
                                            if ($mime_type == "image/png") {
                                                $player_image = 'uploads/PlayerPic/' . $PlayerGUID . '.png';
                                                $player_image1 = $PlayerGUID . '.png';
                                                file_put_contents($player_image, $image_base64);
                                            }
                                            if ($mime_type == "image/jpeg") {
                                                $player_image = 'uploads/PlayerPic/' . $PlayerGUID . '.jpeg';
                                                $player_image1 = $PlayerGUID . '.jpeg';
                                                file_put_contents($player_image, $image_base64);
                                            }
                                            if ($mime_type == "image/jpg") {
                                                $player_image = 'uploads/PlayerPic/' . $PlayerGUID . '.jpg';
                                                $player_image1 = $PlayerGUID . '.jpg';
                                                file_put_contents($player_image, $image_base64);
                                            }
                                            if ($mime_type == "image/gif") {
                                                $player_image = 'uploads/PlayerPic/' . $PlayerGUID . '.gif';
                                                $player_image1 = $PlayerGUID . '.gif';
                                                file_put_contents($player_image, $image_base64);
                                            }
                                        }
                                        $salary_str = (!empty($player_salarycap)) ? $player_salarycap : '480,000';
                                        /* Add players to entity table and get EntityID. */

                                        $PlayerRole = $Roles[strtoupper($PlayerLiveRole)];
                                       
                                        if(!empty($PlayerRole)){
                                            $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2, "GameSportsType" => "Nfl"));
                                            $PlayersAPIData = array(
                                                'TeamID' => $TeamID,
                                                'PlayerID' => $PlayerID,
                                                'PlayerGUID' => $PlayerGUID,
                                                'PlayerIDLive' => $PlayerIDLive,
                                                'PlayerName' => $PlayerName,
                                                'PlayerRole' => $PlayerRole,
                                                'Position' => $position,
                                                'PlayerPic' => $player_image1,
                                                'PlayerSalary'=>$salary_str,
                                                'IsPlayRoster' => 'Yes'
                                            );
                                            $this->db->insert('sports_players', $PlayersAPIData);
                                        }
                                }else{
                                        $this->db->where('PlayerIDLive', $PlayerIDLive);
                                        $this->db->limit(1);
                                        $this->db->update('sports_players', array('IsPlayRoster' => 'Yes',
                                        'TeamID' => $TeamID));
                                }
                            }
                        }
                        if(!empty($IsPlayerRoster)){
                            $this->db->where('TeamID', $TeamID);
                            $this->db->update('sports_players', array('IsPlayRoster' => 'No'));
                            foreach($IsPlayerRoster as $PlayerID){
                                $this->db->where('PlayerIDLive', $PlayerID);
                                $this->db->where('TeamID', $TeamID);
                                $this->db->limit(1);
                                $this->db->update('sports_players', array('IsPlayRoster' => 'Yes'));
                            }
                        }
                }
            }
        }
    }

        /*
      Description: To set matches data (FOOTBALL API NFL)
     */
    function getPlayersLiveByTeamInjuriesAndStates($CronID) {
        $return['code'] = 200;
        $return['response'] = new stdClass();

        $Query = $this->db->query('SELECT T.TeamID,T.TeamGUID,T.TeamIDLive,T.TeamKey,T.TeamName,T.TeamFlag FROM sports_teams T,tbl_entity E WHERE T.TeamID=E.EntityID AND E.GameSportsType="Nfl" order by T.TeamID ASC');
        $Teams = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
        if (!$Teams) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        if (!empty($Teams)) {
            foreach ($Teams as $rows) {

                $TeamIDLive = trim($rows['TeamIDLive']);
                $TeamID = trim($rows['TeamID']);
                $TeamGUID = trim($rows['TeamGUID']);
                $TeamName = trim($rows['TeamName']);

                $this->db->where('TeamID', $TeamID);
                $this->db->update('sports_players', array('IsInjuries' => "Active"));

                /** update player status **/
                $url = SPORTS_API_URL_GOALSERVE . '/football/' . $TeamIDLive . '_injuries';
                $xml1 = simplexml_load_string(file_get_contents($url));
                if(!empty($xml1)){
                    for ($i = 0; $i < count($xml1->report); $i++) {
                        $player_id = $xml1->report[$i]['player_id'];
                        $this->db->where('PlayerIDLive', $player_id);
                        $this->db->limit(1);
                        $this->db->update('sports_players', array('IsInjuries' => $xml1->report[$i]['status']));
                    }
                }

                $Query = $this->db->query('SELECT T.PlayerID,T.PlayerIDLive,T.PlayerRole,T.TeamID FROM sports_players T,tbl_entity E WHERE T.PlayerID=E.EntityID AND E.GameSportsType="Nfl" AND T.TeamID='.$TeamID.' ');
                $Players = ($Query->num_rows() > 0) ? $Query->result_array() : FALSE;
                $Players = array_column($Players,'PlayerRole','PlayerIDLive');
   
                /** update player status **/
                $url = SPORTS_API_URL_GOALSERVE . '/football/' . $TeamIDLive . '_player_stats';
                $xml2 = simplexml_load_string(file_get_contents($url));
                if(!empty($xml2)){
                    for ($i = 0; $i < count($xml2->category); $i++) {
                        $Category = (string)$xml2->category[$i]['name'];
                        for ($j = 0; $j < count($xml2->category[$i]->player); $j++) {

                                $PlayerStats = array_filter(array(
                                    'rank' => (string) $xml2->category[$i]->player[$j]['rank'],
                                    'name' => (string) $xml2->category[$i]->player[$j]['name'],
                                    'passing_attempts' => (isset($xml2->category[$i]->player[$j]['passing_attempts'])) ? (string) $xml2->category[$i]->player[$j]['passing_attempts'] : 0,
                                    'completions' => (isset($xml2->category[$i]->player[$j]['completions'])) ? (string) $xml2->category[$i]->player[$j]['completions'] : 0,
                                    'completion_pct' => isset($xml2->category[$i]->player[$j]['completion_pct']) ? (string) $xml2->category[$i]->player[$j]['completion_pct'] : 0,
                                    'yards_per_pass_avg' => (isset($xml2->category[$i]->player[$j]['yards_per_pass_avg'])) ? (string) $xml2->category[$i]->player[$j]['yards_per_pass_avg'] : 0,
                                    'longest_pass' => (isset($xml2->category[$i]->player[$j]['longest_pass'])) ? (string) $xml2->category[$i]->player[$j]['longest_pass'] : 0,
                                    'passing_touchdowns' => (isset($xml2->category[$i]->player[$j]['passing_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['passing_touchdowns'] : 0,
                                    'passing_touchdowns_pct' => (isset($xml2->category[$i]->player[$j]['passing_touchdowns_pct']))?(string) $xml2->category[$i]->player[$j]['passing_touchdowns_pct']:0,
                                    'interceptions' => (isset($xml2->category[$i]->player[$j]['interceptions']))?(string) $xml2->category[$i]->player[$j]['interceptions']:0,
                                    'fumbles_returned_for_touchdowns' => (isset($xml2->category[$i]->player[$j]['fumbles_returned_for_touchdowns']))?(string) $xml2->category[$i]->player[$j]['fumbles_returned_for_touchdowns']:0,
                                    'quaterback_rating' => isset($xml2->category[$i]->player[$j]['quaterback_rating'])?(string) $xml2->category[$i]->player[$j]['quaterback_rating']:0,

                                    'rushing_attempts' => isset($xml2->category[$i]->player[$j]['rushing_attempts'])?(string) $xml2->category[$i]->player[$j]['rushing_attempts']:0,
                                    'yards_per_rush_avg' => isset($xml2->category[$i]->player[$j]['yards_per_rush_avg'])?(string) $xml2->category[$i]->player[$j]['yards_per_rush_avg']:0,
                                    'longest_rush' => isset($xml2->category[$i]->player[$j]['longest_rush'])?(string) $xml2->category[$i]->player[$j]['longest_rush']:0,
                                    'over_20_yards' => isset($xml2->category[$i]->player[$j]['over_20_yards'])?(string) $xml2->category[$i]->player[$j]['over_20_yards']:0,
                                    'rushing_touchdowns' => isset($xml2->category[$i]->player[$j]['rushing_touchdowns'])?(string) $xml2->category[$i]->player[$j]['rushing_touchdowns']:0,
                                    'fumbles' => isset($xml2->category[$i]->player[$j]['fumbles'])?(string) $xml2->category[$i]->player[$j]['fumbles']:0,
                                    'fumbles_lost' => isset($xml2->category[$i]->player[$j]['fumbles_lost'])?(string) $xml2->category[$i]->player[$j]['fumbles_lost']:0,
                                    'rushing_first_downs' => isset($xml2->category[$i]->player[$j]['rushing_first_downs'])?(string) $xml2->category[$i]->player[$j]['rushing_first_downs']:0,
                                    'receptions' => isset($xml2->category[$i]->player[$j]['receptions'])?(string) $xml2->category[$i]->player[$j]['receptions']:0,
                                    'receiving_targets' => isset($xml2->category[$i]->player[$j]['receiving_targets'])?(string) $xml2->category[$i]->player[$j]['receiving_targets']:0,
                                    'receiving_yards' => isset($xml2->category[$i]->player[$j]['receiving_yards'])?(string) $xml2->category[$i]->player[$j]['receiving_yards']:0,
                                    'yards_per_reception_avg' => isset($xml2->category[$i]->player[$j]['yards_per_reception_avg'])?(string) $xml2->category[$i]->player[$j]['yards_per_reception_avg']:0,
                                    'receiving_touchdowns' => isset($xml2->category[$i]->player[$j]['receiving_touchdowns'])?(string) $xml2->category[$i]->player[$j]['receiving_touchdowns']:0,
                                    'yards_after_catch' => isset($xml2->category[$i]->player[$j]['yards_after_catch'])?(string) $xml2->category[$i]->player[$j]['yards_after_catch']:0,
                                    'receiving_first_downs' => isset($xml2->category[$i]->player[$j]['receiving_first_downs'])?(string) $xml2->category[$i]->player[$j]['receiving_first_downs']:0,
                                    'unassisted_tackles' => isset($xml2->category[$i]->player[$j]['unassisted_tackles'])?(string) $xml2->category[$i]->player[$j]['unassisted_tackles']:0,
                                    'assisted_tackles' => isset($xml2->category[$i]->player[$j]['assisted_tackles'])?(string) $xml2->category[$i]->player[$j]['assisted_tackles']:0,
                                    'total_tackles' => isset($xml2->category[$i]->player[$j]['total_tackles'])?(string) $xml2->category[$i]->player[$j]['total_tackles']:0,
                                    'intercepted_returned_yards' => isset($xml2->category[$i]->player[$j]['intercepted_returned_yards'])?(string) $xml2->category[$i]->player[$j]['intercepted_returned_yards']:0,
                                    'return_touchdowns' => isset($xml2->category[$i]->player[$j]['return_touchdowns'])?(string) $xml2->category[$i]->player[$j]['return_touchdowns']:0,
                                    'total_touchdowns' => isset($xml2->category[$i]->player[$j]['total_touchdowns'])?(string) $xml2->category[$i]->player[$j]['total_touchdowns']:0,
                                    'field_goals' => isset($xml2->category[$i]->player[$j]['field_goals'])?(string) $xml2->category[$i]->player[$j]['field_goals']:0,
                                    'total_points' => isset($xml2->category[$i]->player[$j]['total_points'])?(string) $xml2->category[$i]->player[$j]['total_points']:0,
                                    'kickoff_returned_attempts' => isset($xml2->category[$i]->player[$j]['kickoff_returned_attempts'])?(string) $xml2->category[$i]->player[$j]['kickoff_returned_attempts']:0,
                                    'kickoff_return_yards' => isset($xml2->category[$i]->player[$j]['kickoff_return_yards'])?(string) $xml2->category[$i]->player[$j]['kickoff_return_yards']:0,
                                    'punts_returned' => isset($xml2->category[$i]->player[$j]['punts_returned'])?(string) $xml2->category[$i]->player[$j]['punts_returned']:0,
                                    'field_goals_made' => isset($xml2->category[$i]->player[$j]['field_goals_made'])?(string) $xml2->category[$i]->player[$j]['field_goals_made']:0,
                                    'punts' => isset($xml2->category[$i]->player[$j]['punts'])?(string) $xml2->category[$i]->player[$j]['punts']:0,
                                    'touchbacks' => isset($xml2->category[$i]->player[$j]['touchbacks'])?(string) $xml2->category[$i]->player[$j]['touchbacks']:0,
                                    'two_point_conversions' => isset($xml2->category[$i]->player[$j]['two_point_conversions'])?(string) $xml2->category[$i]->player[$j]['two_point_conversions']:0,
                                    'return_touchdowns' => isset($xml2->category[$i]->player[$j]['return_touchdowns'])?(string) $xml2->category[$i]->player[$j]['return_touchdowns']:0
                                ));

                                $PlayerRole = $Players[(int)$xml2->category[$i]->player[$j]['id']];

                                if(isset($xml2->category[$i]->player[$j]['total_points_per_game'])
                                   && !empty(isset($xml2->category[$i]->player[$j]['total_points_per_game']))){
                                    $PlayerStats['total_points_per_game'] = (string) $xml2->category[$i]->player[$j]['total_points_per_game'];
                                }

                                if(isset($xml2->category[$i]->player[$j]['yards'])
                                   && !empty(isset($xml2->category[$i]->player[$j]['yards']))){
                                    $PlayerStats['yards'] = (string) $xml2->category[$i]->player[$j]['yards'];
                                }


                                if(isset($xml2->category[$i]->player[$j]['yards_per_game'])
                                   && !empty(isset($xml2->category[$i]->player[$j]['yards_per_game']))){
                                    if($PlayerRole == "QuarterBack" && $Category == "Passing"){
                                        $PlayerStats['yards_per_game'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                    }else if($PlayerRole == "RunningBack" && $Category == "Rushing"){
                                        $PlayerStats['yards_per_game'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                    }else if($PlayerRole == "WideReceiver" && $Category == "Receiving"){
                                        $PlayerStats['yards_per_game'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                    }else if($PlayerRole == "TightEnd" && $Category == "Receiving"){
                                        $PlayerStats['yards_per_game'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                    }
                                }

                                $PlayerUpdates = array();
                                if(!empty($PlayerStats)){
                                    $PlayerUpdates['PlayerBattingStats'] = json_encode($PlayerStats);
                                }
                                    if($Category == 'Passing'){
                                      $Passing['fumbles'] = (isset($xml2->category[$i]->player[$j]['fumbles'])) ? (string) $xml2->category[$i]->player[$j]['fumbles'] : 0 ;
                                      $Passing['interceptions'] = (isset($xml2->category[$i]->player[$j]['interceptions'])) ? (string) $xml2->category[$i]->player[$j]['interceptions'] : 0 ;
                                      $Passing['rank'] = (isset($xml2->category[$i]->player[$j]['rank'])) ? (string) $xml2->category[$i]->player[$j]['rank'] : 0 ;
                                      $Passing['yards'] = (isset($xml2->category[$i]->player[$j]['yards'])) ? (string) $xml2->category[$i]->player[$j]['yards'] : 0 ;
                                      $Passing['yards_per_game'] = (isset($xml2->category[$i]->player[$j]['yards_per_game'])) ? (string) $xml2->category[$i]->player[$j]['yards_per_game'] : 0 ;
                                      $Passing['passing_touchdowns'] = (isset($xml2->category[$i]->player[$j]['passing_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['passing_touchdowns'] : 0 ;
                                      $PlayerUpdates['Passing'] = json_encode($Passing);
                                    }

                                     if($Category == 'Rushing'){
                                      $Rushing['fumbles'] = (isset($xml2->category[$i]->player[$j]['fumbles'])) ? (string) $xml2->category[$i]->player[$j]['fumbles'] : 0 ;
                                      $Rushing['yards_per_game'] = (isset($xml2->category[$i]->player[$j]['yards_per_game'])) ? (string) $xml2->category[$i]->player[$j]['yards_per_game'] : 0 ;
                                      $Rushing['rushing_yards'] = (isset($xml2->category[$i]->player[$j]['yards'])) ? (string) $xml2->category[$i]->player[$j]['yards'] : 0 ;
                                      $Rushing['rushing_touchdowns'] = (isset($xml2->category[$i]->player[$j]['rushing_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['rushing_touchdowns'] : 0 ;
                                      $Rushing['fumbles_lost'] = (isset($xml2->category[$i]->player[$j]['fumbles_lost'])) ? (string) $xml2->category[$i]->player[$j]['fumbles_lost'] : 0 ;
                                      $PlayerUpdates['Rushing'] = json_encode($Rushing);
                                    }

                                     if($Category == 'Receiving'){
                                      $Receiving['receptions'] = (isset($xml2->category[$i]->player[$j]['receptions'])) ? (string) $xml2->category[$i]->player[$j]['receptions'] : 0 ;
                                      $Receiving['fumbles'] = (isset($xml2->category[$i]->player[$j]['fumbles'])) ? (string) $xml2->category[$i]->player[$j]['fumbles'] : 0 ;
                                      $Receiving['yards_per_game'] = (isset($xml2->category[$i]->player[$j]['yards_per_game'])) ? (string) $xml2->category[$i]->player[$j]['yards_per_game'] : 0 ;
                                      $Receiving['receiving_yards'] = (isset($xml2->category[$i]->player[$j]['receiving_yards'])) ? (string) $xml2->category[$i]->player[$j]['receiving_yards'] : 0 ;
                                      $Receiving['receiving_touchdowns'] = (isset($xml2->category[$i]->player[$j]['receiving_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['receiving_touchdowns'] : 0 ;
                                      $Receiving['fumbles_lost'] = (isset($xml2->category[$i]->player[$j]['fumbles_lost'])) ? (string) $xml2->category[$i]->player[$j]['fumbles_lost'] : 0 ;
                                      $PlayerUpdates['Receiving'] = json_encode($Receiving);
                                    }

                                    if($Category == 'Scoring'){
                                      $Scoring['return_touchdowns'] = (isset($xml2->category[$i]->player[$j]['return_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['return_touchdowns'] : 0 ;
                                       $Scoring['two_point_conversions'] = (isset($xml2->category[$i]->player[$j]['two_point_conversions'])) ? (string) $xml2->category[$i]->player[$j]['two_point_conversions'] : 0 ;
                                      $Scoring['field_goals'] = (isset($xml2->category[$i]->player[$j]['field_goals'])) ? (string) $xml2->category[$i]->player[$j]['field_goals'] : 0 ;
                                      $Scoring['total_points_per_game'] = (isset($xml2->category[$i]->player[$j]['total_points_per_game'])) ? (string) $xml2->category[$i]->player[$j]['total_points_per_game'] : 0 ;
                                      $Scoring['total_points'] = (isset($xml2->category[$i]->player[$j]['total_points'])) ? (string) $xml2->category[$i]->player[$j]['total_points'] : 0 ;
                                      $PlayerUpdates['Scoring'] = json_encode($Scoring);
                                    }

                                    if($Category == 'Defense'){
                                      $Defense['fumbles_returned_for_touchdowns'] = (isset($xml2->category[$i]->player[$j]['fumbles_returned_for_touchdowns'])) ? (string) $xml2->category[$i]->player[$j]['fumbles_returned_for_touchdowns'] : 0 ;
                                      $Defense['total_tackles'] = (isset($xml2->category[$i]->player[$j]['total_tackles'])) ? (string) $xml2->category[$i]->player[$j]['total_tackles'] : 0 ;
                                      $PlayerUpdates['Defense'] = json_encode($Defense);
                                    }

                                    if(isset($xml2->category[$i]->player[$j]['yards_per_game'])
                                       && !empty(isset($xml2->category[$i]->player[$j]['yards_per_game']))){
                                        if($PlayerRole == "QuarterBack" && $Category == "Passing"){
                                            $PlayerUpdates['YardsPerGame'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                        }else if($PlayerRole == "RunningBack" && $Category == "Rushing"){
                                            $PlayerUpdates['YardsPerGame'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                        }else if($PlayerRole == "WideReceiver" && $Category == "Receiving"){
                                            $PlayerUpdates['YardsPerGame'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                        }else if($PlayerRole == "TightEnd" && $Category == "Receiving"){
                                            $PlayerUpdates['YardsPerGame'] = (string) $xml2->category[$i]->player[$j]['yards_per_game'];  
                                        }
                                    }

                                    if($PlayerRole == "QuarterBack" && $Category == "Passing"){
                                        $PlayerUpdates['Yards'] = (string) $xml2->category[$i]->player[$j]['yards'];  
                                    }else if($PlayerRole == "RunningBack" && $Category == "Rushing"){
                                        $PlayerUpdates['Yards'] = (string) $xml2->category[$i]->player[$j]['yards'];  
                                    }else if($PlayerRole == "WideReceiver" && $Category == "Receiving"){
                                        $PlayerUpdates['Yards'] = (string) $xml2->category[$i]->player[$j]['receiving_yards'];  
                                    }else if($PlayerRole == "TightEnd" && $Category == "Receiving"){
                                        $PlayerUpdates['Yards'] = (string) $xml2->category[$i]->player[$j]['receiving_yards'];  
                                    }
                               

                                    if($PlayerRole == "QuarterBack" && $Category == "Passing"){
                                        $PlayerUpdates['TotalTouchdowns'] = (string) $xml2->category[$i]->player[$j]['passing_touchdowns'];
                                        $PlayerUpdates['Rank'] = (string) $xml2->category[$i]->player[$j]['rank'];   
                                    }else if($PlayerRole == "RunningBack" && $Category == "Rushing"){
                                        $PlayerUpdates['TotalTouchdowns'] = (string) $xml2->category[$i]->player[$j]['rushing_touchdowns'];
                                        $PlayerUpdates['Rank'] = (string) $xml2->category[$i]->player[$j]['rank'];  
                                    }else if($PlayerRole == "WideReceiver" && $Category == "Receiving"){
                                        $PlayerUpdates['TotalTouchdowns'] = (string) $xml2->category[$i]->player[$j]['receiving_touchdowns']; 
                                        $PlayerUpdates['Rank'] = (string) $xml2->category[$i]->player[$j]['rank']; 
                                    }else if($PlayerRole == "TightEnd" && $Category == "Receiving"){
                                        $PlayerUpdates['TotalTouchdowns'] = (string) $xml2->category[$i]->player[$j]['receiving_touchdowns']; 
                                        $PlayerUpdates['Rank'] = (string) $xml2->category[$i]->player[$j]['rank']; 
                                    }
                                if(!empty($PlayerUpdates)){
                                    $player_id = $xml2->category[$i]->player[$j]['id'];
                                    $this->db->where('PlayerIDLive', $player_id);
                                    $this->db->limit(1);
                                    $this->db->update('sports_players', $PlayerUpdates); 
                                }

                        }

                    }

                }
                
            }
        }
    }

        /*
      Description: To get player points
     */

    function weeklyPlayerStatsUpdates() {

                $WeekID=$this->SnakeDrafts_model->getCurrentWeekLast();
                $Players = array();
                $this->db->select("TP.MatchID,P.PlayerID,P.WeeklyStats,TP.ScoreData,TP.TotalPoints");
                $this->db->from('sports_team_players TP,sports_players P');
                $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
                $this->db->where("TP.WeekID",  $WeekID['WeekID']);
                $this->db->where('TP.ScoreData is NOT NULL', NULL, FALSE);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Players = $Query->result_array();
                }
                $PlayerStats = array();
                if(!empty($Players)){
                     foreach($Players as $Player){
                        $WeekStats = array();
                        $WeekStats['total_points'] = $Player['TotalPoints'];
                        $WeekStats['yards'] = 0;
                        $WeekStats['passing_touchdowns'] = 0;
                        $WeekStats['rushing_yards'] = 0;
                        $WeekStats['rushing_touchdowns'] = 0;
                        $WeekStats['receiving_yards'] = 0;
                        $WeekStats['receiving_touchdowns'] = 0;
                        $WeekStats['field_goals'] = 0;
                        $WeekStats['fumbles_lost'] = 0;
                        $WeekStats['total_tackles'] = 0;
                        $ScoreData = json_decode($Player['ScoreData'],true);
                       
                        if(isset($ScoreData['passing'])){
                            $WeekStats['yards'] = $ScoreData['passing']['yards'];
                            $WeekStats['passing_touchdowns'] = $ScoreData['passing']['passing_touch_downs'];
                        }
                        if(isset($ScoreData['russing'])){
                            $WeekStats['rushing_yards'] = $ScoreData['russing']['yards'];
                            $WeekStats['rushing_touchdowns'] = $ScoreData['russing']['rushing_touch_downs'];
                        }
                        if(isset($ScoreData['receiving'])){
                            $WeekStats['receiving_yards'] = $ScoreData['receiving']['yards'];
                            $WeekStats['receiving_touchdowns'] = $ScoreData['receiving']['receiving_touch_downs'];
                        }
                        if(isset($ScoreData['fumbles'])){
                            $WeekStats['fumbles_lost'] = $ScoreData['fumbles']['lost'];
                        }
                        if(isset($ScoreData['defensive'])){
                            $WeekStats['total_tackles'] = $ScoreData['defensive']['tackles'];
                        }
                        if(isset($ScoreData['kicking'])){
                            $WeekStats['field_goals'] = $ScoreData['kicking']['field_goals'];
                        }

                        $this->db->where('PlayerID', $Player['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('sports_players', array('WeeklyStats' => json_encode($WeekStats)));
                     }
                }
    }

}

?>
