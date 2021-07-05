<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sports_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->model('nba/AuctionDrafts_model');
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
                'SeriesType' => 'S.SeriesType',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'StatusID' => 'E.StatusID',
                'GameSportsType' => 'E.GameSportsType',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'AuctionDraftIsPlayed' => 'S.AuctionDraftIsPlayed',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'SeriesStartDateUTC' => 'S.SeriesStartDate as SeriesStartDateUTC',
                'SeriesEndDateUTC' => 'S.SeriesEndDate as SeriesEndDateUTC',
                'TotalMatches' => '(SELECT COUNT(*) AS TotalMatches
                FROM nba_sports_matches
                WHERE nba_sports_matches.SeriesID =  S.SeriesID ) AS TotalMatches',
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
        $this->db->from('tbl_entity E, nba_sports_series S');
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
        if (!empty($Where['GameSportsType'])) {
            $this->db->where("E.GameSportsType", $Where['GameSportsType']);
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
        $this->db->order_by('S.SeriesID', 'ASC');

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
        $this->db->from('nba_sports_set_match_types');
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
                'GameSportsType' => 'E.GameSportsType',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'SeriesName' => 'S.SeriesName',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'MatchID' => 'M.MatchID',
                'WeekID' => 'M.WeekID',
                'MatchIDLive' => 'M.MatchIDLive',
                'MatchTypeID' => 'M.MatchTypeID',
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
                //'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                //'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'TeamFlagLocal' => 'TL.TeamFlag as TeamFlagLocal',
                'TeamFlagVisitor' => 'TV.TeamFlag as TeamFlagVisitor',
                'MyTotalJoinedContest' => '(SELECT COUNT(DISTINCT nba_sports_contest_join.ContestID)
                                                FROM nba_sports_contest_join
                                                WHERE nba_sports_contest_join.MatchID =  M.MatchID AND UserID= ' . @$Where['UserID'] . ') AS MyTotalJoinedContest',
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
                'isJoinedContest' => '(select count(MatchID) from nba_sports_contest_join where MatchID = M.MatchID AND E.StatusID=' . (!is_array(@$Where['StatusID'])) ? @$Where['StatusID'] : 2 . ') as JoinedContests',
                'TotalUserWinning' => '(select SUM(UserWinningAmount) from nba_sports_contest_join where MatchID = M.MatchID AND UserID=' . @$Where['UserID'] . ') as TotalUserWinning',
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
        $this->db->from('tbl_entity E, nba_sports_series S, nba_sports_matches M, nba_sports_teams TL, nba_sports_teams TV, nba_sports_set_match_types MT');
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
            $this->db->or_like("M.MatchStartDateTime", $Where['Keyword']);
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
        if (!empty($Where['IsPlayerPointsUpdated'])) {
            $this->db->where("M.IsPlayerPointsUpdated", $Where['IsPlayerPointsUpdated']);
        }
        if (!empty($Where['MatchStartDateTime'])) {
            $this->db->where("M.MatchStartDateTime <=", $Where['MatchStartDateTime']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Yesterday') {
            $this->db->where("M.MatchStartDateTime <=", date('Y-m-d H:i:s'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'MyJoinedMatch') {
            $this->db->where('EXISTS (select 1 from nba_sports_contest_join J where J.MatchID = M.MatchID AND J.UserID=' . $Where['UserID'] . ')');
        }
        if (!empty($Where['StatusID'])) {
            if ($Where['StatusID'] == 2) {
                $Where['StatusID'] = array(2, 10);
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            } else {
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            }
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }
        if (!empty($Where['existingContests'])) {
            $StatusID = $Where['StatusID'];
            $this->db->where('EXISTS (select MatchID from nba_sports_contest where MatchID = M.MatchID AND E.StatusID=' . $StatusID . ')');
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
            $this->db->order_by('M.MatchID', 'ASC');

        } else {
            if (!empty($Where['OrderByToday']) && $Where['OrderByToday'] == 'Yes') {
                // $this->db->order_by('DATE(M.MatchStartDateTime)="' . date('Y-m-d') . '" DESC', null, FALSE);
                // $this->db->order_by('E.StatusID=1 DESC', null, FALSE);
                $this->db->order_by('E.StatusID=1 DESC', null, FALSE);
                $this->db->order_by('M.MatchStartDateTime', "ASC");
            } else {
                $this->db->order_by('E.StatusID', 'ASC');
                $this->db->order_by('M.MatchStartDateTime', 'ASC');
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
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        // echo $this->db->last_query();die;
        // if ($Query->num_rows() > 0) {
        if ($multiRecords) {
            if ($Query->num_rows() > 0) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    if (!empty($Record['TeamFlagLocal'])) {
                        $Records[$key]['TeamFlagLocal'] = (filter_var($Record['TeamFlagLocal'], FILTER_VALIDATE_URL)) ? $Record['TeamFlagLocal'] : BASE_URL . "uploads/TeamFlag/" . $Record['TeamFlagLocal'];
                    }
                    if (!empty($Record['TeamFlagVisitor'])) {
                        $Records[$key]['TeamFlagVisitor'] = (filter_var($Record['TeamFlagVisitor'], FILTER_VALIDATE_URL)) ? $Record['TeamFlagVisitor'] : BASE_URL . "uploads/TeamFlag/" . $Record['TeamFlagVisitor'];
                    }
                    $Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                    $Records[$key]['MatchLocation'] = (!empty($Record['MatchLocation'])) ? json_decode($Record['MatchLocation'], TRUE) : new stdClass();
                }
                $Return['Data']['Records'] = $Records;
            }
            if (!empty($Where['MyJoinedMatchesCount']) && $Where['MyJoinedMatchesCount'] == 1) {
                $Return['Data']['Statics'] = $this->db->query('SELECT (
                            SELECT COUNT(DISTINCT M.MatchID) AS `UpcomingJoinedContest` FROM `nba_sports_matches` M
                            JOIN `nba_sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE E.StatusID = 1 AND J.UserID ="' . @$Where['UserID'] . '" 
                        )as UpcomingJoinedContest,
                        ( SELECT COUNT(DISTINCT M.MatchID) AS `LiveJoinedContest` FROM `nba_sports_matches` M JOIN `nba_sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE  E.StatusID IN (2,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                        )as LiveJoinedContest,
                        ( SELECT COUNT(DISTINCT M.MatchID) AS `CompletedJoinedContest` FROM `nba_sports_matches` M JOIN `nba_sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = J.ContestID WHERE  E.StatusID IN (5,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                    )as CompletedJoinedContest'
                        )->row();
            }
            return $Return;
        } else {
            if ($Query->num_rows() > 0) {
                $Record = $Query->row_array();
                $Record['TeamFlagLocal'] = (filter_var($Record['TeamFlagLocal'], FILTER_VALIDATE_URL)) ? $Record['TeamFlagLocal'] : BASE_URL . "uploads/TeamFlag/" . $Record['TeamFlagLocal'];
                $Record['TeamFlagVisitor'] = (filter_var($Record['TeamFlagVisitor'], FILTER_VALIDATE_URL)) ? $Record['TeamFlagVisitor'] : BASE_URL . "uploads/TeamFlag/" . $Record['TeamFlagVisitor'];
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
                'TeamID'        => 'T.TeamID',
                'StatusID'      => 'E.StatusID',
                'GameSportsType'=> 'E.GameSportsType',
                'TeamIDLive'    => 'T.TeamIDLive',
                'TeamName'      => 'T.TeamName',
                'TeamStats'     => 'T.TeamStats',
                'FantasyPoints' => 'T.FantasyPoints',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag'      => 'T.TeamFlag',
                'IsPowerTeam'   => 'T.IsPowerTeam',
                'ByeWeek'       => 'T.ByeWeek',
                'TeamFlag'    => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",T.TeamFlag) as TeamFlag',
                //'TeamFlag'      => 'T.TeamFlag as TeamFlag',
                'Status'        => 'CASE E.StatusID
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
        $this->db->from('tbl_entity E, nba_sports_teams T');
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
        if (!empty($Where['GameSportsType'])) {
            $this->db->where("E.GameSportsType", $Where['GameSportsType']);
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
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['TeamStats'] = (!empty($Record['TeamStats']) ? json_decode($Record['TeamStats']) : '');
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['TeamStats'] = (!empty($Record['TeamStats']) ? json_decode($Record['TeamStats']) : new stdClass());
                $Record['ByeWeek'] = explode(',', $Record['ByeWeek']);
                return $Record;
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
        if (!empty($Input['FantasyPoints'])) {
            $UpdateArray['FantasyPoints'] = $Input['FantasyPoints'];
        }
        if (!empty($Input['TeamStats'])) {
            $UpdateArray['TeamStats'] = $Input['TeamStats'];
        }
        if (!empty($Input['IsPowerTeam'])) {
            $UpdateArray['IsPowerTeam'] = $Input['IsPowerTeam'];
        }
        if (!empty($Input['ByeWeek'])) {
            $UpdateArray['ByeWeek'] = $Input['ByeWeek'];
        }
        if (!empty($UpdateArray)) {
            $this->db->where('TeamID', $TeamID);
            $this->db->limit(1);
            $this->db->update('nba_sports_teams', $UpdateArray);
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
                'PlayerRole' => 'P.PlayerRole',
                'SeriesID' => 'M.SeriesID',
                'MatchID' => 'M.MatchID',
                'TeamID' => 'P.TeamID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                //'PlayerPic' => 'P.PlayerPic AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff'
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
        $this->db->from('tbl_entity E, nba_sports_players P');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag', 'PlayerRole', 'SeriesID', 'MatchID'))) {
            $this->db->from('nba_sports_teams T');
            $this->db->where("P.TeamID", "T.TeamID", FALSE);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['MatchID'])) {
            $this->db->where('EXISTS (select MatchID from nba_sports_matches M where (M.TeamIDLocal = P.TeamID OR M.TeamIDVisitor = P.TeamID) AND M.MatchID=' . $Where['MatchID'] . ')');
        }

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
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("P.TeamID", $Where['TeamID']);
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
            $MatchQuery = $this->db->query('SELECT E.StatusID FROM `nba_sports_matches` `M`,`tbl_entity` `E` WHERE M.`MatchID` = "' . $Where['MatchID'] . '" AND M.MatchID = E.EntityID LIMIT 1');
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
                        $this->db->from('nba_sports_users_teams SUT,nba_sports_users_team_players SUTP');
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
                        $TotalTeams = $this->db->query('Select count(*) as TotalTeams from nba_sports_users_teams WHERE MatchID="' . $Where['MatchID'] . '"')->row()->TotalTeams;

                        $this->db->select('count(SUTP.PlayerID) as TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.MatchID", $Where['MatchID']);
                        $this->db->from('nba_sports_users_teams SUT,nba_sports_users_team_players SUTP');
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
                    $this->db->from('nba_sports_users_teams SUT,nba_sports_users_team_players SUTP');
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

        $this->db->select('Points');
        $this->db->select('PointsTypeGUID,PointsTypeDescprition,PointsTypeShortDescription,PointsType,StatusID,Sort');
        $this->db->from('nba_sports_setting_points');
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        $this->db->order_by("Sort", 'ASC');
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
            for ($i = 0; $i < count($Input['Points']); $i++) {
                $updateArray[] = array(
                    'PointsTypeGUID' => $Input['PointsTypeGUID'][$i],
                    'Points' => $Input['Points'][$i],
                    'Sort' => $Input['Sort'][$i]
                );
            }
            /* Update points details to sports_setting_points table. */
            $this->db->update_batch('nba_sports_setting_points', $updateArray, 'PointsTypeGUID');
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
            $this->db->update('nba_sports_team_players', $Input);
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
            $this->db->update('nba_sports_series', $Input);
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

    function getAccessToken() {
        $this->load->helper('file');
        $AccessToken = "";
        if (file_exists(SPORTS_FILE_PATH)) {
            $AccessToken = read_file(SPORTS_FILE_PATH);
        }
        /* else {
          $AccessToken = $this->generateAccessToken();
          } */
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

    function callSportsAPI($ApiUrl) {
        $AccessToken = $this->getAccessToken();
        $Response = json_decode($this->ExecuteCurl($ApiUrl . $AccessToken), TRUE);
        if (@$Response['status'] == 'unauthorized' || @$Response['status_code'] == 403) {
            //$AccessToken = $this->generateAccessToken();
            // $Response = json_decode($this->ExecuteCurl($ApiUrl . $AccessToken), TRUE);
        }
        return $Response;
    }

    /*
      Description: To set series data (Entity API)
     */

    function getSeriesLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* Update Existing Series Status */
        $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesEndDate < "' . date('Y-m-d') . '"');
        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE AuctionDraftIsPlayed = "Yes" AND SeriesStartDate <= "' . date('Y-m-d') . '"');
        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE AuctionDraftIsPlayed = "Yes" AND SeriesEndDate < "' . date('Y-m-d') . '"');

        $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/seasons/?token=');
        if (empty($Response['response']['items'])) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        $SeriesData = array();
        foreach ($Response['response']['items'] as $Value) {
            if (!in_array($Value['sid'], array(date('Y'), date('Y') . date('y') + 1, date('Y') - 1 . date('y'))))
                continue;

            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/' . $Value['competitions_url'] . '?per_page=50&token=');
            /* To get All Series Data */
            //$SeriesIdsData = $this->db->query('SELECT GROUP_CONCAT(SeriesIDLive) AS SeriesIDsLive FROM sports_series WHERE YEAR(SeriesEndDate) IN (' . date('Y') . ',' . (date('Y') + 1) . ')')->row()->SeriesIDsLive;
            $SeriesIdsData = $this->db->query('SELECT GROUP_CONCAT(SeriesIDLive) AS SeriesIDsLive FROM sports_series')->row()->SeriesIDsLive;
            $SeriesIDsLive = array();
            if ($SeriesIdsData) {
                $SeriesIDsLive = explode(",", $SeriesIdsData);
            }

            foreach ($Response['response']['items'] as $Value) {
                if (in_array($Value['cid'], $SeriesIDsLive))
                    continue;

                /* Add series to entity table and get EntityID. */
                $SeriesGUID = get_guid();
                $SeriesData[] = array_filter(array(
                    'SeriesID' => $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2)),
                    'SeriesGUID' => $SeriesGUID,
                    'SeriesIDLive' => $Value['cid'],
                    'SeriesName' => $Value['title'],
                    'SeriesStartDate' => $Value['datestart'],
                    'SeriesEndDate' => $Value['dateend']
                ));
            }
        }
        if (!empty($SeriesData)) {
            $this->db->insert_batch('sports_series', $SeriesData);
        }
    }

    /*
      Description: To set matches data (Entity API)
     */

    function getMatchesLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* Get series data */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesEndDate' => date('Y-m-d')), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        /* To get All Match Types */
        $MatchTypesData = $this->getMatchTypes();
        $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeName');

        /* Get Live Matches Data */
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $SeriesValue['SeriesIDLive'] . '/matches/?per_page=150&token=');

            if (empty($Response['response']['items']))
                continue;
            foreach ($Response['response']['items'] as $key => $Value) {

                /* $this->db->trans_start(); */

                /* Managae Teams */
                $PreSquad = $Value['pre_squad'];
                $Verified = $Value['verified'];
                $LocalTeam = $Value['teama'];
                $VisitorTeam = $Value['teamb'];
                $LocalTeamData = $VisitorTeamData = array();

                if ($VisitorTeam['name'] == "TBA")
                    continue;

                /* To check if local team is already exist */
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $LocalTeam['team_id'] . ' LIMIT 1');
                $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamIDLocal) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $LocalTeamData[] = array(
                        'TeamID' => $TeamIDLocal,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $LocalTeam['team_id'],
                        'TeamName' => $LocalTeam['name'],
                        'TeamNameShort' => (!empty($LocalTeam['short_name'])) ? $LocalTeam['short_name'] : null,
                        'TeamFlag' => (!empty($LocalTeam['logo_url'])) ? $LocalTeam['logo_url'] : null
                    );
                }

                /* To check if visitor team is already exist */
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $VisitorTeam['team_id'] . ' LIMIT 1');
                $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamIDVisitor) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $VisitorTeamData[] = array(
                        'TeamID' => $TeamIDVisitor,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $VisitorTeam['team_id'],
                        'TeamName' => $VisitorTeam['name'],
                        'TeamNameShort' => (!empty($VisitorTeam['short_name'])) ? $VisitorTeam['short_name'] : null,
                        'TeamFlag' => (!empty($VisitorTeam['logo_url'])) ? $VisitorTeam['logo_url'] : null
                    );
                }
                $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);
                if (!empty($TeamsData)) {
                    $this->db->insert_batch('sports_teams', $TeamsData);
                }

                /* To check if match is already exist */
                $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $Value['match_id'] . ' LIMIT 1');
                $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                if (!$MatchID) {

                    if (strtotime(date('Y-m-d H:i:s')) >= strtotime(date('Y-m-d H:i', strtotime($Value['date_start'])))) {
                        continue;
                    }

                    /* Add matches to entity table and get EntityID. */
                    $MatchGUID = get_guid();
                    $MatchesAPIData = array(
                        'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1)),
                        'MatchGUID' => $MatchGUID,
                        'MatchIDLive' => $Value['match_id'],
                        'SeriesID' => $SeriesValue['SeriesID'],
                        'MatchTypeID' => $MatchTypeIdsData[$Value['format_str']],
                        'MatchNo' => $Value['subtitle'],
                        'MatchLocation' => $Value['venue']['location'],
                        'TeamIDLocal' => $TeamIDLocal,
                        'TeamIDVisitor' => $TeamIDVisitor,
                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($Value['date_start']))
                    );
                    $this->db->insert('sports_matches', $MatchesAPIData);
                } else {

                    if ($Query->row()->StatusID != 1)
                        continue; // Pending Match

                        /* Update Match Data */
                    $MatchesAPIData = array(
                        'MatchNo' => $Value['subtitle'],
                        'MatchLocation' => $Value['venue']['location'],
                        'TeamIDLocal' => $TeamIDLocal,
                        'TeamIDVisitor' => $TeamIDVisitor,
                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($Value['date_start'])),
                        'LastUpdatedOn' => date('Y-m-d H:i:s')
                    );
                    if ($PreSquad == "true") {
                        $MatchesAPIData["IsPreSquad"] = "Yes";
                        $this->getMatchWisePlayersLiveEntity(null, $MatchID);
                    }
                    $this->db->where('MatchID', $MatchID);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', $MatchesAPIData);
                }

                /* $this->db->trans_complete();
                  if ($this->db->trans_status() === false) {
                  return false;
                  } */
            }
        }
    }

    /*
      Description: To set players data match wise (Entity API)
     */

    function getMatchWisePlayersLiveEntity($CronID, $MatchID = "") {
        ini_set('max_execution_time', 300);

        /* Get series data */
        if (!empty($MatchID)) {
            $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID', array('StatusID' => array(1), "MatchID" => $MatchID), true, 0);
        } else {
            $MatchData = $this->getMatches('MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1), 'CronFilter' => 'OneDayDiff'), true, 1, 10);
        }
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
      Description: 	Cron jobs to get auction joined player points.

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

    function setContestWinners($CronID) {

        ini_set('max_execution_time', 300);


        $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor,CustomizeWinning', array('StatusID' => 5, 'IsWinningDistributed' => 'No', "LeagueType" => "Dfs"), true, 0);
        if (isset($Contests['Data']['Records'])) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(), 'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

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
                        $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
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
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
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
        $this->db->where("C.LeagueType", "Dfs");
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
                                "UserTeamID" => $WinnerValue['UserTeamID'],
                                "TransactionType" => 'Cr',
                                "Narration" => 'Join Contest Winning',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                        }

                        /** user join contest winning status update * */
                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
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

    function SetAuctionDraftWinner($CronID) {

        ini_set('max_execution_time', 300);

        $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor,CustomizeWinning', array('StatusID' => 5, 'IsWinningDistributed' => 'Yes', "IsWinningDistributeAmount" => "No", "LeagueType" => "Dfs"), true, 0);
        if (isset($Contests['Data']['Records'])) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(), 'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

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


                        /* if($WinnerValue['UserWinningAmount'] > 0){

                          $EmailArr = array(
                          "Name" => $WinnerValue['FirstName'],
                          "SeriesName" => $Value['SeriesName'],
                          "ContestName" => $Value['ContestName'],
                          "MatchNo" => $Value['MatchNo'],
                          "TeamNameLocal" => $Value['TeamNameLocal'],
                          "TeamNameVisitor" => $Value['TeamNameVisitor'],
                          "WinningAmount" => $WinnerValue['UserWinningAmount'],
                          "TotalPoints" => $WinnerValue['TotalPoints'],
                          "UserRank" => $WinnerValue['UserRank']
                          );
                          sendMail(array(
                          'emailTo' => $WinnerValue['Email'],
                          'emailSubject' => "Contest Winning - " . SITE_NAME,
                          'emailMessage' => emailTemplate($this->load->view('emailer/contest_winning', $EmailArr, true))
                          ));
                          } */
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

                /* Share Top 1 Winner Information On Social Media - Facebook */
                if ($Value['IsWinnerSocialFeed'] == "Yes") {

                    /* Require Facebook Graph Library */
                    require_once(APPPATH . 'libraries/facebook/src/Facebook/autoload.php');
                    $fb = new \Facebook\Facebook([
                        'app_id' => '537634479717824',
                        'app_secret' => '407360003a553102b650472b1b02499e',
                        'default_graph_version' => 'v3.2',
                    ]);

                    //Post property to Facebook
                    $linkData = [
                        'link' => SITE_HOST . ROOT_FOLDER,
                        'message' => @$OptionWinner[0]['FirstName'] . " has won the " . $Value['ContestName'] . " contest. Match - " . $Value['MatchNo'] . ". Team - " . $Value['TeamNameLocal'] . " Vs " . $Value['TeamNameVisitor'] . ".  Winning Amount - Rs." . @$OptionWinner[0]['UserWinningAmount'] . ". Total Points - " . @$OptionWinner[0]['TotalPoints'] . ". Rank - " . @$OptionWinner[0]['UserRank']
                    ];
                    $pageAccessToken = 'EAAHoZBcguvcABAEQYc67a09j4snTKHgZCZCDuiSngUS863ZAN5vf4RLZAoV6yqDCSp9VM6ad6FNjcZCM7ZBggrPB6QD9REuJ0GNn6SyOG6BHcUUReR8jVcS1mVb4fZCSq7U2yrSegfuxe14F1JiL1CloZB5vbI8J3ZBTrRZCocYACtKugZDZD';
                    try {
                        $response = $fb->post('/me/feed', $linkData, $pageAccessToken);
                    } catch (Facebook\Exceptions\FacebookResponseException $e) {
                        log_message('ERROR', 'Graph returned an error: ' . $e->getMessage());
                    } catch (Facebook\Exceptions\FacebookSDKException $e) {
                        log_message('ERROR', 'Facebook SDK returned an error: ' . $e->getMessage());
                    }
                    $graphNode = $response->getGraphNode();
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

}

?>
