<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class SnakeDrafts_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('nba/Sports_model');
        $this->load->model('Settings_model');
    }

    /*
      Description:    ADD contest to system.
     */

    function addContest($Input = array(), $SessionUserID, $MatchID, $SeriesID, $StatusID = 1) {
        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();
        $EntityGUID = get_guid();
        $Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftTeamPlayerLimit = (!empty($Series['DraftTeamPlayerLimit'])) ? $Series['DraftTeamPlayerLimit'] : 8;

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" => $SessionUserID, "StatusID" => $StatusID));
        $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('+300 minutes', 0);

        $RoosterSize = footballGetConfigurationPrivate();
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);
        $RoosterConfiguration = footballGetConfigurationPlayersRoosterPrivate($RoosterArray['RosterSize']);
        /* Add contest to contest table . */
        $InsertData = array_filter(array(
            "ContestID"     => $EntityID,
            "ContestGUID"   => $EntityGUID,
            "UserID"        => $SessionUserID,
            //"ContestName" => (!empty(@$Input['ContestName'])) ? @$Input['ContestName'] : (@$Input['IsPaid'] == "Yes") ? "Win ".@$Input['WinningAmount'] : "Win Skill",
            "ContestName" => @$Input['ContestName'],
            "LeagueType" => @$Input['LeagueType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "ContestFormat" => @$Input['ContestFormat'],
            "ContestType" => @$Input['ContestType'],
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => (@$Input['IsPaid']) ? @$Input['IsPaid'] : "No",
            "IsConfirm" => @$Input['IsConfirm'],
            "LeagueType" => "Draft",
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "GameType"      => @$Input['GameType'],
            "GameTimeLive"  => @$Input['GameTimeLive'],
            "AdminPercent"  => @$Input['AdminPercent'],
            "ContestSize"   => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
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
            "ScoringType" => @$Input['ScoringType'],
            "PlayOff" => @$Input['PlayOff'],
            "WeekStart" => @$Input['WeekStart'],
            "WeekEnd" => @$Input['WeekEnd'],
            "DraftTotalRounds" => $RoosterArray['RosterSize'],
            "DraftLiveRound" => 1,
            "RosterSize" => $RoosterArray['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0,
            "SubGameType"   => @$Input['SubGameType'],
            'DraftPlayerSelectionCriteria' => (!empty($RoosterConfiguration)) ? json_encode($RoosterConfiguration) : null,
            "ContestDuration"       => @$Input['ContestDuration'],
            "InvitePermission"      => @$Input['InvitePermission'],
            "PrivatePointScoring"   => (!empty($Input['PrivatePointScoring'])) ? $Input['PrivatePointScoring'] : '',
        ));
        $this->db->insert('sports_contest', $InsertData);
        
        if ($this->Post['Privacy'] == 'Yes') {            
            /** Take Pivate Contest Fee From User**/
                $UserPrivateContestFee = @$Input['UserPrivateContestFee'];
                $WalletAmountDeduction = 0;
                $WinningAmountDeduction = 0;
                /** Duduct From Wallet**/ 
                if ($UserPrivateContestFee > 0 && @$Input['WalletAmount'] > 0) {
                    if (@$Input['WalletAmount'] >= $UserPrivateContestFee) {
                        $WalletAmountDeduction = $UserPrivateContestFee;
                    }
                }
                /** Duduct From Winning**/ 
                if ($UserPrivateContestFee > 0 && @$Input['UserWinningAmount'] > 0 && $WalletAmountDeduction==0) {
                    if (@$Input['UserWinningAmount'] >= $UserPrivateContestFee) {
                        $WinningAmountDeduction = $UserPrivateContestFee;
                    }
                }

                $InsertData = array(
                    "Amount" => @$Input['UserPrivateContestFee'],
                    "WalletAmount" => $WalletAmountDeduction,
                    "WinningAmount" => $WinningAmountDeduction,
                    "CashBonus" => 0,
                    "TransactionType" => 'Dr',
                    "EntityID" => $EntityID,
                    "Narration" => 'Private Contest Fee',
                    "EntryDate" => date("Y-m-d H:i:s")
                );

                $this->Users_model->addToWallet($InsertData, $SessionUserID, 5);
        }

        $PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $Input['WeekStart'],$Input['ContestDuration'],$Input['DailyDate']);
        if(!$PlayerIs) return false;
        // $this->addAuctionPlayer($SeriesID, $EntityID);
        // $this->joinPrivateContest($Input, $SessionUserID, $EntityID, $SeriesID);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $EntityID;
    }


    /*
      Description:    ADD contest to system.
     */

    function addPrivateContest($Input = array(), $SessionUserID, $MatchID, $SeriesID, $StatusID = 1) {
        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();
        $EntityGUID = get_guid();
        $Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftTeamPlayerLimit = (!empty($Series['DraftTeamPlayerLimit'])) ? $Series['DraftTeamPlayerLimit'] : 8;

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" => $SessionUserID, "StatusID" => $StatusID));
        if(!empty($Input['TimeZone'])){
            $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime($Input['TimeZone'].' minutes', 0);
        }else{
            $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('+300 minutes', 0);
        }
        

        $RoosterSize = footballGetConfigurationPrivate();
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);

        $RoosterConfiguration = footballGetConfigurationPlayersRoosterPrivate($Input['RosterSize']);
        /* Add contest to contest table . */
        $InsertData = array_filter(array(
            "ContestID"     => $EntityID,
            "ContestGUID"   => $EntityGUID,
            "UserID"        => $SessionUserID,
            //"ContestName" => (!empty(@$Input['ContestName'])) ? @$Input['ContestName'] : (@$Input['IsPaid'] == "Yes") ? "Win ".@$Input['WinningAmount'] : "Win Skill",
            "ContestName" => @$Input['ContestName'],
            "LeagueType" => @$Input['LeagueType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "ContestFormat" => @$Input['ContestFormat'],
            "ContestType" => @$Input['ContestType'],
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => (@$Input['IsPaid']) ? @$Input['IsPaid'] : "No",
            "IsAutoDraft" => (@$Input['IsAutoDraft']) ? @$Input['IsAutoDraft'] : "No",
            "IsConfirm" => @$Input['IsConfirm'],
            "LeagueType" => "Draft",
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "GameType"      => @$Input['GameType'],
            "GameTimeLive"  => @$Input['GameTimeLive'],
            "AdminPercent"  => @$Input['AdminPercent'],
            "ContestSize"   => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
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
            "ScoringType" => @$Input['ScoringType'],
            "PlayOff" => @$Input['PlayOff'],
            "WeekStart" => @$Input['WeekStart'],
            "WeekEnd" => @$Input['WeekEnd'],
            "DraftTotalRounds" => $Input['RosterSize'],
            "DraftLiveRound" => 1,
            "RosterSize" => $Input['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0,
            "SubGameType"   => @$Input['SubGameType'],
            'DraftPlayerSelectionCriteria' => (!empty($RoosterConfiguration)) ? json_encode($RoosterConfiguration) : null,
            "ContestDuration"       => @$Input['ContestDuration'],
            "InvitePermission"      => @$Input['InvitePermission'],
            "UserPrivateContestFee" => @$Input['UserPrivateContestFee'],
            "PrivatePointScoring"   => (!empty($Input['PrivatePointScoring'])) ? $Input['PrivatePointScoring'] : '',
        ));
        $this->db->insert('sports_contest', $InsertData);

        if ($this->Post['Privacy'] == 'Yes') {
            $ContestEntryRemainingFees = @$Input['UserPrivateContestFee'];
            $CashBonusContribution = 0;
            $WalletAmountDeduction = 0;
            $WinningAmountDeduction = 0;
            $CashBonusDeduction = 0;
            $Input['CashBonus'] = 0;
            if (!empty($CashBonusContribution) && @$Input['CashBonus'] > 0) {
                $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                if (@$Input['CashBonus'] >= $CashBonusContributionAmount) {
                    $CashBonusDeduction = $CashBonusContributionAmount;
                } else {
                    $CashBonusDeduction = @$Input['CashBonus'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WalletAmount'] > 0) {
                if (@$Input['WalletAmount'] >= $ContestEntryRemainingFees) {
                    $WalletAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WalletAmountDeduction = @$Input['WalletAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['UserWinningAmount'] > 0) {
                if (@$Input['UserWinningAmount'] >= $ContestEntryRemainingFees) {
                    $WinningAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WinningAmountDeduction = @$Input['UserWinningAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
            }
            $InsertData = array(
                "Amount" => @$Input['UserPrivateContestFee'],
                "WalletAmount" => $WalletAmountDeduction,
                "WinningAmount" => $WinningAmountDeduction,
                "CashBonus" => 0,
                "TransactionType" => 'Dr',
                "EntityID" => $EntityID,
                "Narration" => 'Private Contest Fee',
                "EntryDate" => date("Y-m-d H:i:s")
            );
            $WalletID = $this->Users_model->addToWallet($InsertData, $SessionUserID, 5);
        }

        $this->joinContestPrivate($Input, $SessionUserID, $EntityID, $SeriesID, '', $Input['IsAutoDraft']);

        $PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $Input['WeekStart'],$Input['ContestDuration'],$Input['DailyDate']);
        if(!$PlayerIs) return false;
        // $this->addAuctionPlayer($SeriesID, $EntityID);
        

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $EntityID;
    }

    function joinContestPrivate($Input = array(), $SessionUserID, $ContestID, $SeriesID, $UserTeamID='', $IsAutoDraft='') {

        $UserData = $this->Users_model->getUsers('TotalCash,WalletAmount,WinningAmount,CashBonus', array('UserID' => $SessionUserID));
        $Input['WalletAmount'] = $UserData['WalletAmount'];
        $Input['WinningAmount'] = $UserData['WinningAmount'];
        $Input['CashBonus'] = $UserData['CashBonus'];

        $this->db->trans_start();
        /* Add entry to join contest table . */
        $DraftUserPosition = 0;
        $this->db->select("COUNT(UserID) as Joined");
        $this->db->from("sports_contest_join");
        $this->db->where("ContestID", $ContestID);
        $Query = $this->db->get();
        $Result = $Query->row_array();
        if (isset($Result['Joined'])) {
            $DraftUserPosition = $Result['Joined'] + 1;
        }
        $InsertData = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "SeriesID" => $SeriesID,
            "UserTeamID" => $UserTeamID,
            "IsAutoDraft" => $IsAutoDraft,
            "DraftUserPosition" => $DraftUserPosition,
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
            $Input['CashBonus'] = 0;
            if (!empty($CashBonusContribution) && @$Input['CashBonus'] > 0) {
                $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                if (@$Input['CashBonus'] >= $CashBonusContributionAmount) {
                    $CashBonusDeduction = $CashBonusContributionAmount;
                } else {
                    $CashBonusDeduction = @$Input['CashBonus'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WalletAmount'] > 0) {
                if (@$Input['WalletAmount'] >= $ContestEntryRemainingFees) {
                    $WalletAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WalletAmountDeduction = @$Input['WalletAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WinningAmount'] > 0) {
                if (@$Input['WinningAmount'] >= $ContestEntryRemainingFees) {
                    $WinningAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WinningAmountDeduction = @$Input['WinningAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
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
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return true;

        /* update contest round * */
        /* $this->autoShuffleRoundUpdate($ContestID); */

        //return $this->Users_model->getWalletDetails($SessionUserID);
    }

    function searchForId($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['Owners'] === $id) {
                return $array[$key];
            }
        }
        return null;
    }

    function updateContestTeams() {
        $Contests = $this->getContests('ContestID,GameType,SubGameTypeKey,SeriesID', array('AuctionStatusID' => 1), true, 0);

        if ($Contests['Data']['TotalRecords'] > 0) {
            foreach ($Contests['Data']['Records'] as $Contest) {
                $this->addAuctionTeam($Contest['SeriesID'], $Contest['ContestID'], $Contest['GameType'], $Contest['SubGameTypeKey']);
            }
        }
    }

    /*
      Description: Join contest
     */

    function joinPrivateContest($Input = array(), $SessionUserID, $ContestID, $SeriesID) {

        $this->db->trans_start();
        /* Add entry to join contest table . */
        $DraftUserPosition = 0;
        $this->db->select("COUNT(UserID) as Joined");
        $this->db->from("sports_contest_join");
        $this->db->where("ContestID", $ContestID);
        $Query = $this->db->get();
        $Result = $Query->row_array();
        if (isset($Result['Joined'])) {
            $DraftUserPosition = $Result['Joined'] + 1;
        }
        $InsertData = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "SeriesID" => $SeriesID,
            "DraftUserPosition" => $DraftUserPosition,
            "EntryDate" => date('Y-m-d H:i:s')
        );
        $this->db->insert('sports_contest_join', $InsertData);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description:    ADD auction teams
     */

    function addAuctionTeam($SeriesID, $ContestID, $GameType, $SubGameType) {
        $TeamsData = $this->getTeamsDrafts($GameType, $SubGameType);
        if ($TeamsData['TotalRecords'] > 0) {

            /* Delete Teams */

            $this->db->delete('tbl_auction_player_bid_status', array('ContestID' => $ContestID, 'SeriesID' => $SeriesID));

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
        return TRUE;
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

    function addAuctionPlayer_old($SeriesID, $ContestID) {
        $playersData = $this->getPlayersDraft("PlayerID,PlayerName,PlayerRole", array('SeriesID' => $SeriesID, 'OrderBy' => "PlayerID", "Sequence" => "ASC"), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if ($playersData['Data']['TotalRecords'] > 0) {
            $Players = $playersData['Data']['Records'];
            if (!empty($Players)) {
                $InsertBatch = array();
                $InsertBatchPlayer = array();
                foreach ($Players as $Player) {
                    $Temp['SeriesID'] = $SeriesID;
                    $Temp['ContestID'] = $ContestID;
                    $Temp['PlayerID'] = $Player['PlayerID'];
                    $Temp['PlayerRole'] = $Player['PlayerRole'];
                    $Temp['BidCredit'] = 0;
                    $Temp['PlayerStatus'] = "Upcoming";
                    $InsertBatch[] = $Temp;

                    $Temp1['SeriesID'] = $SeriesID;
                    $Temp1['ContestID'] = $ContestID;
                    $Temp1['PlayerID'] = $Player['PlayerID'];
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
        }
    }

    function addAuctionPlayer($SeriesID, $ContestID, $MatchID, $ContestDuration, $DailyDate) {

        $Query = $this->db->query("SELECT TeamIDLocal,TeamIDVisitor FROM nba_sports_matches WHERE MatchID = '" . $MatchID . "'");

        $TeamData = ($Query->num_rows() > 0) ? $Query->result_array() : false;
        $TeamIDLocal = array_column($TeamData,'TeamIDLocal');
        $TeamIDVisitor = array_column($TeamData,'TeamIDVisitor');
        $AllTeam = array_unique(array_merge($TeamIDLocal,$TeamIDVisitor));

        if(empty($AllTeam)) return false;

        $AllRole = array('PointGuard','Center','SmallForward',
            'ShootingGuard','PowerForward');

        /* Get Joined Contest Users */
        $this->db->select('P.PlayerID,P.PlayerName,P.PlayerRole,P.TeamID');
        $this->db->from('nba_sports_players P,tbl_entity E');
        $this->db->where("E.EntityID", "P.PlayerID", FALSE);
        $this->db->where("P.IsPlayRoster", "Yes");
        $this->db->where("E.GameSportsType", "Nba");
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
                        $this->db->insert_batch('nba_tbl_auction_player_bid_status', $InsertBatch);

                        $Query = $this->db->query('SELECT SeriesID FROM nba_sports_auction_draft_player_point WHERE SeriesID = "' . $SeriesID . '" LIMIT 1');
                        $SeriesID = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
                        if (!$SeriesID) {
                            $this->db->insert_batch('nba_sports_auction_draft_player_point', $InsertBatchPlayer);
                        }
                    }
                }
                return true;
        }else{
            return false;
        }
        return true;
    }

    function getPlayersDraft($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
        }else{
            $this->db->order_by('P.YardsPerGame', 'DESC');
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

    function getPlayersDraftAll($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'PlayerSelectTypeRole' => 'UTP.PlayerSelectTypeRole',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => 'APBS.BidCredit as BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'PlayerStatus' => 'APBS.PlayerStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerRole' => 'P.PlayerRole PlayerRole',
                'PlayerPosition' => 'P.Position as PlayerPosition',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'IsAutoDraft' => '(SELECT IF( EXISTS(
                                SELECT EntryDate FROM nba_sports_contest_join
                                WHERE nba_sports_contest_join.ContestID =  C.ContestID AND UserID = ' . @$Where['SessionUserID'] . ' AND  nba_sports_contest_join.IsAutoDraft="Yes" LIMIT 1), "Yes", "No")) AS IsAutoDraft',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'IsInjuries' => 'P.IsInjuries',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'PlayerRoleShort' => 'CASE P.PlayerRole
                             when "ShootingGuard" then "SG"
                             when "Center" then "C"
                             when "PowerForward" then "PF"
                             when "PointGuard" then "PG"
                             when "SmallForward" then "SF"
                             END as PlayerRoleShort',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName,P.Game,P.MatchStats');
        if (!empty($Field))
        $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_players P,nba_tbl_auction_player_bid_status APBS,nba_sports_series S,nba_sports_contest C,nba_sports_teams T');

        if (!empty($Where['TotalPoints']) && $Where['TotalPoints'] == 'Yes') {
            $this->db->select('TP.TotalPoints,TP.PointsData');
            $this->db->from('nba_sports_team_players TP');
            $this->db->where("TP.PlayerID", "APBS.PlayerID", FALSE);
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
        $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
        $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
        $this->db->where("T.TeamID", "P.TeamID", FALSE);
        
        if (!empty($Where['PlayerStatus'])) {
            $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("APBS.ContestID", $Where['ContestID']);
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP');
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

        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['PlayerRoleShort'])) {
            $Role="";
            if($Where['PlayerRoleShort'] == "PG"){
               $Role="PointGuard";
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "SF"){
               $Role="SmallForward"; 
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "SG"){
               $Role="ShootingGuard"; 
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "C"){
               $Role="Center";
               $this->db->where("P.PlayerRole", $Role);  
           }else if($Where['PlayerRoleShort'] == "PF"){
                $Role="PowerForward";
                $this->db->where("P.PlayerRole", $Role);  
           }else if($Where['PlayerRoleShort'] == "FLEX"){
               $Role=array('PointGuard','SmallForward','ShootingGuard',
                     'Center','PowerForward');
               $this->db->where_in("P.PlayerRole", $Role);  
           }else if($Where['PlayerRoleShort'] == "PF/C"){
               $Role=array('Center','PowerForward');
               $this->db->where_in("P.PlayerRole", $Role);  
           }
            
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsPlayRoster'])) {
            $this->db->where("P.IsPlayRoster", $Where['IsPlayRoster']);
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
        $this->db->order_by('P.YardsPerGame', 'DESC');
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {

            // $this->db->order_by('P.IsInjuries', 'ASC');
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
        // echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";

                    $GameStats = array();

                    $GameStats['minutes'] = 0;
                    $GameStats['offensive_rebounds_per_game'] = 0;
                    $GameStats['defensive_rebounds_per_game'] = 0;
                    $GameStats['rebounds_per_game'] = 0;
                    $GameStats['points_per_game'] = 0;
                    $GameStats['assists_per_game'] = 0;
                    $GameStats['steals_per_game'] = 0;
                    $GameStats['blocks_per_game'] = 0;
                    $GameStats['turnovers_per_game'] = 0;
                    $GameStats['fg_made_per_game'] = 0;
                    $GameStats['fg_attempts_per_game'] = 0;
                    $GameStats['three_point_made_per_game'] = 0;
                    $GameStats['three_point_attempts_per_game'] = 0;
                    $GameStats['free_throws_made_per_game'] = 0;
                    $GameStats['free_throws_attempts_per_game'] = 0;
                   

                if(!empty($Record['Game'])){
                    $Game = json_decode($Record['Game'],true);
                    $GameStats['minutes'] = $Game['minutes'];
                    $GameStats['points_per_game'] = $Game['points_per_game'];
                    $GameStats['offensive_rebounds_per_game'] = $Game['offensive_rebounds_per_game'];
                    $GameStats['defensive_rebounds_per_game'] = $Game['defensive_rebounds_per_game'];
                    $GameStats['rebounds_per_game'] = $Game['rebounds_per_game'];
                    $GameStats['assists_per_game'] = $Game['assists_per_game'];
                    $GameStats['steals_per_game'] = $Game['steals_per_game'];
                    $GameStats['blocks_per_game'] = $Game['blocks_per_game'];
                    $GameStats['turnovers_per_game'] = $Game['turnovers_per_game'];
                }
                if(!empty($Record['MatchStats'])){
                    $MatchStats = json_decode($Record['MatchStats'],true);
                    $GameStats['total_points'] = $MatchStats['total_points'];
                }

                    $Records[$key]['PlayerBattingStats'] = $GameStats;

                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
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
                $GameStats = array();

                $GameStats['minutes'] = 0;
                $GameStats['total_points'] = 0;
                $GameStats['offensive_rebounds_per_game'] = 0;
                $GameStats['defensive_rebounds_per_game'] = 0;
                $GameStats['rebounds_per_game'] = 0;
                $GameStats['points_per_game'] = 0;
                $GameStats['assists_per_game'] = 0;
                $GameStats['steals_per_game'] = 0;
                $GameStats['blocks_per_game'] = 0;
                $GameStats['turnovers_per_game'] = 0;
                $GameStats['fg_made_per_game'] = 0;
                $GameStats['fg_attempts_per_game'] = 0;
                $GameStats['three_point_made_per_game'] = 0;
                $GameStats['three_point_attempts_per_game'] = 0;
                $GameStats['free_throws_made_per_game'] = 0;
                $GameStats['free_throws_attempts_per_game'] = 0;

                $Record['PlayerBattingStats'] = (!empty($Record['MatchStats'])) ? json_decode($Record['MatchStats']) : $GameStats;
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                return $Record;
            }
        }
        return FALSE;
    }

    function getPlayersAll($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'PlayerRole' => 'P.PlayerRole PlayerRole',
                'PlayerPosition' => 'P.Position as PlayerPosition',
                'IsAutoDraft' => '(SELECT IF( EXISTS(
                                SELECT EntryDate FROM nba_sports_contest_join
                                WHERE nba_sports_contest_join.ContestID =  C.ContestID AND UserID = ' . @$Where['SessionUserID'] . ' AND  nba_sports_contest_join.IsAutoDraft="Yes" LIMIT 1), "Yes", "No")) AS IsAutoDraft',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'WeeklyStats' => 'P.WeeklyStats',
                'MatchStats' => 'P.MatchStats',
                'IsInjuries' => 'P.IsInjuries',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'TeamName' => 'T.TeamName',
                'PlayerRoleShort' => 'CASE P.PlayerRole
                             when "PointGuard" then "PG"
                             when "Center" then "C"
                             when "SmallForward" then "SF"
                             when "ShootingGuard" then "SG"
                             when "PowerForward" then "PF"
                             END as PlayerRoleShort',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName,P.MatchStats,P.Game,P.Shooting');
        if (!empty($Field))
        $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_players P,nba_sports_teams T');
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        $this->db->where("T.TeamID", "P.TeamID", FALSE);
        
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

        if (!empty($Where['TeamID'])) {
            $this->db->where("T.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['PlayerRoleShort'])) {
            $Role="";
            if($Where['PlayerRoleShort'] == "PG"){
               $Role="PointGuard";
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "SF"){
               $Role="SmallForward"; 
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "SG"){
               $Role="ShootingGuard"; 
               $this->db->where("P.PlayerRole", $Role); 
           }else if($Where['PlayerRoleShort'] == "C"){
               $Role="Center";
               $this->db->where("P.PlayerRole", $Role);  
           }else if($Where['PlayerRoleShort'] == "PF"){
                $Role="PowerForward";
                $this->db->where("P.PlayerRole", $Role);  
           }
           else if($Where['PlayerRoleShort'] == "FLEX"){
               $Role=array('PointGuard','SmallForward','ShootingGuard',
                     'Center','PowerForward');
               $this->db->where_in("P.PlayerRole", $Role);  
           }
            
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsPlayRoster'])) {
            $this->db->where("P.IsPlayRoster", $Where['IsPlayRoster']);
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
            $this->db->order_by('P.IsInjuries', 'ASC');
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

                    $GameStats = array();
                    $GameStats['total_points'] = 0;
                    $GameStats['minutes'] = 0;
                    $GameStats['offensive_rebounds_per_game'] = 0;
                    $GameStats['defensive_rebounds_per_game'] = 0;
                    $GameStats['rebounds_per_game'] = 0;
                    $GameStats['points_per_game'] = 0;
                    $GameStats['assists_per_game'] = 0;
                    $GameStats['steals_per_game'] = 0;
                    $GameStats['blocks_per_game'] = 0;
                    $GameStats['turnovers_per_game'] = 0;
                    $GameStats['fg_made_per_game'] = 0;
                    $GameStats['fg_attempts_per_game'] = 0;
                    $GameStats['three_point_made_per_game'] = 0;
                    $GameStats['three_point_attempts_per_game'] = 0;
                    $GameStats['free_throws_made_per_game'] = 0;
                    $GameStats['free_throws_attempts_per_game'] = 0;

                if(!empty($Record['Game'])){
                    $Game = json_decode($Record['Game'],true);
                    $GameStats['minutes'] = $Game['minutes'];
                    $GameStats['points_per_game'] = $Game['points_per_game'];
                    $GameStats['offensive_rebounds_per_game'] = $Game['offensive_rebounds_per_game'];
                    $GameStats['defensive_rebounds_per_game'] = $Game['defensive_rebounds_per_game'];
                    $GameStats['rebounds_per_game'] = $Game['rebounds_per_game'];
                    $GameStats['assists_per_game'] = $Game['assists_per_game'];
                    $GameStats['steals_per_game'] = $Game['steals_per_game'];
                    $GameStats['blocks_per_game'] = $Game['blocks_per_game'];
                    $GameStats['turnovers_per_game'] = $Game['turnovers_per_game'];
                }
                if(!empty($Record['Shooting'])){
                    $Shooting = json_decode($Record['Shooting'],true);
                    $GameStats['fg_made_per_game'] = $Shooting['fg_made_per_game'];
                    $GameStats['fg_attempts_per_game'] = $Shooting['fg_attempts_per_game'];
                    $GameStats['three_point_made_per_game'] = $Shooting['three_point_made_per_game'];
                    $GameStats['three_point_attempts_per_game'] = $Shooting['three_point_attempts_per_game'];
                    $GameStats['free_throws_made_per_game'] = $Shooting['free_throws_made_per_game'];
                    $GameStats['free_throws_attempts_per_game'] = $Shooting['free_throws_attempts_per_game'];
                }
                if(!empty($Record['MatchStats'])){
                    $MatchStats = json_decode($Record['MatchStats'],true);
                    $GameStats['total_points'] = $MatchStats['total_points'];
                }

                    $Records[$key]['PlayerBattingStats'] = $GameStats;
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $GameStats = array();
                $GameStats['total_points'] = 0;
                $GameStats['minutes'] = 0;
                $GameStats['offensive_rebounds_per_game'] = 0;
                $GameStats['defensive_rebounds_per_game'] = 0;
                $GameStats['rebounds_per_game'] = 0;
                $GameStats['points_per_game'] = 0;
                $GameStats['assists_per_game'] = 0;
                $GameStats['steals_per_game'] = 0;
                $GameStats['blocks_per_game'] = 0;
                $GameStats['turnovers_per_game'] = 0;
                $GameStats['fg_made_per_game'] = 0;
                $GameStats['fg_attempts_per_game'] = 0;
                $GameStats['three_point_made_per_game'] = 0;
                $GameStats['three_point_attempts_per_game'] = 0;
                $GameStats['free_throws_made_per_game'] = 0;
                $GameStats['free_throws_attempts_per_game'] = 0;
                
                $Record['PlayerBattingStats'] = (!empty($Record['MatchStats'])) ? json_decode($Record['MatchStats']) : $GameStats;
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) :  new stdClass();
                return $Record;
            }
        }
        return FALSE;
    }

    function getPlayersMyTeam($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'IsInjuries' => 'P.IsInjuries',
                'BidCredit' => 'UTP.BidCredit',
                'TotalPoints' => 'UTP.Points TotalPoints',
                'PointsDataPrivate' => 'UTP.PointsData PointsDataPrivate',
                'ContestID' => 'UT.ContestID as ContestID',
                'SeriesID' => 'UT.SeriesID as SeriesID',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserTeamID' => 'UT.UserTeamID',
                'UserID' => 'UT.UserID',
                'PlayerRole' => 'P.PlayerRole PlayerRole',
                'PlayerPosition' => 'P.Position as PlayerPosition',
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
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'PlayerRoleShort' => 'CASE P.PlayerRole
                             when "PointGuard" then "PG"
                             when "Center" then "C"
                             when "SmallForward" then "SF"
                             when "ShootingGuard" then "SG"
                             when "PowerForward" then "PF"
                             END as PlayerRoleShort',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName,C.ContestSize,C.RosterSize,UTP.PlayerSelectTypeRole,C.Privacy,P.Game,P.Shooting,P.MatchStats');
        if (!empty($Field))
        $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_players P,nba_sports_series S,nba_sports_contest C,nba_sports_teams T,nba_sports_users_teams UT, nba_sports_users_team_players UTP');
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
        $this->db->where("S.SeriesID", "UT.SeriesID", FALSE);
        $this->db->where("C.ContestID", "UT.ContestID", FALSE);
        $this->db->where("T.TeamID", "P.TeamID", FALSE);
        
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

                        $GameStats = array();

                    $GameStats['minutes'] = 0;
                    $GameStats['offensive_rebounds_per_game'] = 0;
                    $GameStats['defensive_rebounds_per_game'] = 0;
                    $GameStats['rebounds_per_game'] = 0;
                    $GameStats['points_per_game'] = 0;
                    $GameStats['assists_per_game'] = 0;
                    $GameStats['steals_per_game'] = 0;
                    $GameStats['blocks_per_game'] = 0;
                    $GameStats['turnovers_per_game'] = 0;
                    $GameStats['fg_made_per_game'] = 0;
                    $GameStats['fg_attempts_per_game'] = 0;
                    $GameStats['three_point_made_per_game'] = 0;
                    $GameStats['three_point_attempts_per_game'] = 0;
                    $GameStats['free_throws_made_per_game'] = 0;
                    $GameStats['free_throws_attempts_per_game'] = 0;

                    if(!empty($Record['Game'])){
                        $Game = json_decode($Record['Game'],true);

                        $GameStats['minutes'] = $Game['minutes'];
                        $GameStats['points_per_game'] = $Game['points_per_game'];
                        $GameStats['offensive_rebounds_per_game'] = $Game['offensive_rebounds_per_game'];
                        $GameStats['defensive_rebounds_per_game'] = $Game['defensive_rebounds_per_game'];
                        $GameStats['rebounds_per_game'] = $Game['rebounds_per_game'];
                        $GameStats['assists_per_game'] = $Game['assists_per_game'];
                        $GameStats['steals_per_game'] = $Game['steals_per_game'];
                        $GameStats['blocks_per_game'] = $Game['blocks_per_game'];
                        $GameStats['turnovers_per_game'] = $Game['turnovers_per_game'];
                    }
                    if(!empty($Record['Shooting'])){
                        $Shooting = json_decode($Record['Shooting'],true);

                        $GameStats['fg_made_per_game'] = $Shooting['fg_made_per_game'];
                        $GameStats['fg_attempts_per_game'] = $Shooting['fg_attempts_per_game'];
                        $GameStats['three_point_made_per_game'] = $Shooting['three_point_made_per_game'];
                        $GameStats['three_point_attempts_per_game'] = $Shooting['three_point_attempts_per_game'];
                        $GameStats['free_throws_made_per_game'] = $Shooting['free_throws_made_per_game'];
                        $GameStats['free_throws_attempts_per_game'] = $Shooting['free_throws_attempts_per_game'];
                    }
                    if(!empty($Record['MatchStats'])){
                        $MatchStats = json_decode($Record['MatchStats'],true);
                        $GameStats['total_points'] = $MatchStats['total_points'];
                    }

                    $Records[$key]['PlayerBattingStats'] = $GameStats;

                    //$Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();

                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    if($Records['Privacy'] == 'No'){
                        $Records[$key]['RoosterRole'] = basketballGetConfigurationPlayersRooster($Record['ContestSize']);
                    }else{
                        $Records[$key]['RoosterRole'] = basketballGetConfigurationPlayersRoosterPrivate($Record['RosterSize']);
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
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
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

    function addAuctionPlayerRandom($SeriesID, $ContestID) {
        $playersData = $this->getPlayers("PlayerID,PlayerSalary", array('SeriesID' => $SeriesID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($playersData['Data']['TotalRecords'] > 0) {
            $Players = $playersData['Data']['Records'];
            if (!empty($Players)) {
                $PlayerCatOne = array();
                $PlayerCatTwo = array();
                $Temp = array();
                foreach ($Players as $Rows) {
                    $Temp["PlayerID"] = $Rows["PlayerID"];
                    $Temp["PlayerSalary"] = $Rows["PlayerSalary"];
                    if ($Rows["PlayerSalary"] >= 9) {
                        $PlayerCatOne[] = $Temp;
                    } else {
                        $PlayerCatTwo[] = $Temp;
                    }
                }
                shuffle($PlayerCatOne);
                shuffle($PlayerCatTwo);
                $Players = array_merge($PlayerCatOne, $PlayerCatTwo);
                shuffle($Players);
                $InsertBatch = array();
                $TempPlayer = array();
                foreach ($Players as $Player) {
                    $TempPlayer['SeriesID'] = $SeriesID;
                    $TempPlayer['ContestID'] = $ContestID;
                    $TempPlayer['PlayerID'] = $Player['PlayerID'];
                    $TempPlayer['BidCredit'] = 0;
                    $TempPlayer['PlayerStatus'] = "Upcoming";
                    $TempPlayer['CreateDateTime'] = date("Y-m-d H:i:s");
                    $InsertBatch[] = $TempPlayer;
                }
                if (!empty($InsertBatch)) {
                    $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);
                }
            }
        }
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
        $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('-330 minutes', 0);
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
            "GameType" => @$Input['GameType'],
            "GameTimeLive" => @$Input['GameTimeLive'],
            "AdminPercent" => @$Input['AdminPercent'],
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "ScoringType" => @$Input['ScoringType'],
            "PlayOff" => @$Input['PlayOff'],
            "WeekStart" => @$Input['WeekStart'],
            "WeekEnd" => @$Input['WeekStart'],
            "DraftTotalRounds" => @$Input['DraftTotalRounds'],
            "ContestSize" => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
            "EntryFee" => (@$Input['IsPaid'] == 'Yes') ? @$Input['EntryFee'] : 0,
            "NoOfWinners" => (@$Input['IsPaid'] == 'Yes') ? @$Input['NoOfWinners'] : 1,
            "EntryType" => @$Input['EntryType'],
            "UserJoinLimit" => (@$Input['EntryType'] == 'Multiple') ? @$Input['UserJoinLimit'] : 1,
            "CashBonusContribution" => @$Input['CashBonusContribution'],
            // "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : NULL,
            "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : array($defaultCustomizeWinningObj),
        ));
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
    }

    function getTeams($SeriesID, $ContestID) {
        $Return['Records'] = array();
        $Return['TotalRecords'] = 0;
        $this->db->select(" DISTINCT T.TeamID", FALSE);
        $this->db->select('APBS.PlayerStatus,T.TeamGUID,T.TeamName,T.TeamNameShort,T.TeamFlag, T.TeamFlag AS TeamFlag ', FALSE);
        $this->db->from('tbl_auction_player_bid_status APBS, sports_teams T');
        $this->db->where("APBS.TeamID", "T.TeamID", FALSE);
        $this->db->where("APBS.SeriesID", $SeriesID, FALSE);
        $this->db->where("APBS.ContestID", $ContestID, FALSE);
        $this->db->order_by('T.TeamID', "DESC");
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
      Description: Update auction game.
     */

    function getDraftGameStatusUpdate($Input = array(), $ContestID, $AuctionStatusID) {


        /* check contest cancel or not * */
        $Contest = $this->autoCancelAuction($ContestID);
        if ($Contest) {
            return false;
        }

        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionStatusID" => $AuctionStatusID,
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('nba_sports_contest', $UpdateData);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: To Auto Cancel Auction
     */

    function autoCancelAuction($ContestID) {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,MinimumUserJoined', array('AuctionStatusID' => 1, 'ContestID' => $ContestID, 'IsConfirm' => "No", "IsPaid" => "Yes"), true, 0);

        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            return false;
        }
        foreach ($ContestsUsers['Data']['Records'] as $Value) {

            $IsCancelled = (($Value['IsConfirm'] == 'No' && $Value['TotalJoined'] < $Value['MinimumUserJoined']) ? 1 : 0);
            if ($IsCancelled == 0)
                return false;

            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

            /* Update auction Status */
            $this->db->where('ContestID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('nba_sports_contest', array('AuctionStatusID' => 3));

            /* Get Joined Contest */
            $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID']), true, 0);
            if (!$JoinedContestsUsers)
                return false;

            foreach ($JoinedContestsUsers['Data']['Records'] as $JoinValue) {

                /* Refund Wallet Money */
                if (!empty($Value['EntryFee'])) {

                    /* Get Wallet Details */
                    $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                        'UserID' => $JoinValue['UserID'],
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
                        "UserTeamID" => $JoinValue['UserTeamID'],
                        "Narration" => 'Cancel Contest',
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
                }

                /* Send Mail To Users */
                $EmailArr = array(
                    "Name" => $JoinValue['FirstName'],
                    "SeriesName" => $Value['SeriesName'],
                    "ContestName" => $Value['ContestName'],
                    "MatchNo" => $Value['MatchNo'],
                    "TeamNameLocal" => $Value['TeamNameLocal'],
                    "TeamNameVisitor" => $Value['TeamNameVisitor']
                );
                /* sendMail(array(
                  'emailTo' => $JoinValue['Email'],
                  'emailSubject' => "Cancel Contest- " . SITE_NAME,
                  'emailMessage' => emailTemplate($this->load->view('emailer/cancel_contest', $EmailArr, true))
                  )); */
            }

            return true;
        }
    }

    /*
      Description: Update user live status.
     */

    function userLiveStatusUpdate($Input = array(), $ContestID, $UserID, $SeriesID) {

        /** to update other user offline * */
        $UpdateDatas = array(
            "DraftUserLive" => "No"
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->update('nba_sports_contest_join', $UpdateDatas);

        /* user status update . */
        $UpdateData = array(
            "DraftUserLive" => $Input['UserStatus'],
            "DraftUserLiveTime" => date('Y-m-d H:i:s'),
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->limit(1);
        $this->db->update('nba_sports_contest_join', $UpdateData);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: Update draft round.
     */

    function roundUpdate($Input = array(), $ContestID, $SeriesID) {

        /** to update other user offline * */
        $UpdateDatas = array(
            "DraftLiveRound" => $Input['DraftLiveRound']
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->update('nba_sports_contest', $UpdateDatas);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: get user in live
     */

    function checkUserDraftInlive($Input, $SeriesID, $ContestID) {
        $Return = array();
        $Return["Status"] = 0;
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,AuctionStatus,SeriesID,SeriesGUID', array('AuctionStatusID' => 2, 'LeagueType' => "Draft", "ContestID" => $ContestID, "SeriesID" => $SeriesID), TRUE, 1);
        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                /** to get user live and time difference * */
                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.AuctionUserStatus");
                $this->db->from('nba_sports_contest_join J, tbl_users U');
                $this->db->where("J.DraftUserLive", "Yes");
                $this->db->where("U.UserID", "J.UserID", FALSE);
                $this->db->where("J.ContestID", $Draft['ContestID']);
                $this->db->where("J.SeriesID", $Draft['SeriesID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $LiveUser = $Query->row_array();
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                    $Users[$Key]["UserLiveInTimeSeconds"] = $diffSeconds;
                    $Users[$Key]["ContestID"] = $Draft['ContestID'];
                    $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                    $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                    $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                    $Users[$Key]["UserID"] = $LiveUser['UserID'];
                    $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                    $Users[$Key]["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                    $Users[$Key]["UserStatus"] = "Live";
                    $Users[$Key]["DraftUserLiveTime"] = $LiveUser['DraftUserLiveTime'];
                } else {
                    /** to get user live and time difference * */
                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.AuctionUserStatus");
                    $this->db->from('nba_sports_contest_join J, tbl_users U');
                    $this->db->where("U.UserID", "J.UserID", FALSE);
                    $this->db->where("J.ContestID", $Draft['ContestID']);
                    $this->db->where("J.SeriesID", $Draft['SeriesID']);
                    $this->db->where("J.DraftUserPosition", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $LiveUser = $Query->row_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                        $Users[$Key]["UserLiveInTimeSeconds"] = 0;
                        $Users[$Key]["ContestID"] = $Draft['ContestID'];
                        $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                        $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                        $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                        $Users[$Key]["UserID"] = $LiveUser['UserID'];
                        $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                        $Users[$Key]["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                        $Users[$Key]["UserStatus"] = "Upcoming";
                        $Users[$Key]["DraftUserLiveTime"] = $LiveUser['DraftUserLiveTime'];
                    }
                }
            }
            $U = array();
            foreach ($Users as $Rows) {
                $U[] = $Rows;
            }
            $Return["Data"] = $U;
            $Return["Message"] = "Users in live";
            $Return["Status"] = 1;
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    /*
      Description: get user in live
     */

    function getUserInLive() {
        $Return = array();
        $Return["Status"] = 0;
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,AuctionStatus,SeriesID,SeriesGUID,MatchGUID,GameType', array('AuctionStatusID' => 2, 'LeagueType' => "Draft"), TRUE, 1);
        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                /** to get user live and time difference * */
                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.AuctionUserStatus");
                $this->db->from('nba_sports_contest_join J, tbl_users U');
                $this->db->where("J.DraftUserLive", "Yes");
                $this->db->where("U.UserID", "J.UserID", FALSE);
                $this->db->where("J.ContestID", $Draft['ContestID']);
                $this->db->where("J.SeriesID", $Draft['SeriesID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $LiveUser = $Query->row_array();
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                    $Users[$Key]["UserLiveInTimeSeconds"] = $diffSeconds;
                    $Users[$Key]["ContestID"] = $Draft['ContestID'];
                    $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                    $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                    $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                    $Users[$Key]["MatchGUID"] = $Draft['MatchGUID'];
                    $Users[$Key]["GameType"] = $Draft['GameType'];
                    $Users[$Key]["UserID"] = $LiveUser['UserID'];
                    $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                    $Users[$Key]["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                    $Users[$Key]["UserStatus"] = "Live";
                } else {
                    /** to get user live and time difference * */
                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.AuctionUserStatus");
                    $this->db->from('nba_sports_contest_join J, tbl_users U');
                    $this->db->where("U.UserID", "J.UserID", FALSE);
                    $this->db->where("J.ContestID", $Draft['ContestID']);
                    $this->db->where("J.SeriesID", $Draft['SeriesID']);
                    $this->db->where("J.DraftUserPosition", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $LiveUser = $Query->row_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                        $Users[$Key]["UserLiveInTimeSeconds"] = 0;
                        $Users[$Key]["ContestID"] = $Draft['ContestID'];
                        $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                        $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                        $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                        $Users[$Key]["MatchGUID"] = $Draft['MatchGUID'];
                        $Users[$Key]["GameType"] = $Draft['GameType'];
                        $Users[$Key]["UserID"] = $LiveUser['UserID'];
                        $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                        $Users[$Key]["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                        $Users[$Key]["UserStatus"] = "Upcoming";
                    }
                }
            }
            $U = array();
            foreach ($Users as $Rows) {
                $U[] = $Rows;
            }
            $Return["Data"] = $U;
            $Return["Message"] = "Users in live";
            $Return["Status"] = 1;
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    /*
      Description: get round next user in live
     */

    function draftRoundUpdate($ContestID, $SeriesID, $Round) {
        /* Add contest to contest table . */
        $UpdateData = array_filter(array(
            "DraftLiveRound" => $Round
        ));
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('nba_sports_contest', $UpdateData);
        return true;
    }

    function getRoundNextUserInLive($Input, $SeriesID, $ContestID) {
        $Return = array();
        $Return["Status"] = 0;
        $Return['Message'] = "Record Not found";
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,SeriesID,SeriesGUID,DraftTotalRounds,TotalJoined,DraftLiveRound,GameType', array('AuctionStatusID' => 2, 'LeagueType' => "Draft", "ContestID" => $ContestID, "SeriesID" => $SeriesID), TRUE, 1);

        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                if ($Draft['DraftLiveRound'] <= $Draft['DraftTotalRounds']) {

                    /** check last player in live * */
                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.AuctionUserStatus");
                    $this->db->from('nba_sports_contest_join J, tbl_users U');
                    $this->db->where("J.DraftUserLive", "Yes");
                    $this->db->where("U.UserID", "J.UserID", FALSE);
                    $this->db->where("J.ContestID", $ContestID);
                    $this->db->where("J.SeriesID", $SeriesID);
                    $Query = $this->db->get();
                    if ($Query->num_rows() == 0) {
                        /** check last player in live * */
                        $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName,J.AuctionUserStatus");
                        $this->db->from('nba_sports_contest_join J, tbl_users U');
                        $this->db->where("U.UserID", "J.UserID", FALSE);
                        $this->db->where("J.ContestID", $ContestID);
                        $this->db->where("J.SeriesID", $SeriesID);
                        $this->db->where("J.DraftUserPosition", 1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $LiveUser = $Query->row_array();
                            $CurrentDateTime = date('Y-m-d H:i:s');
                            $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                            $Users["UserLiveInTimeSeconds"] = 0;
                            $Users["ContestID"] = $Draft['ContestID'];
                            $Users["SeriesID"] = $Draft['SeriesID'];
                            $Users["ContestGUID"] = $Draft['ContestGUID'];
                            $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                            $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                            $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                            $Users["GameType"] = $Draft['GameType'];
                            $Users["UserID"] = $LiveUser['UserID'];
                            $Users["UserGUID"] = $LiveUser['UserGUID'];
                            $Users["FirstName"] = $LiveUser['FirstName'];
                            $Users["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                            $Return["Status"] = 1;
                            $Return["Data"] = $Users;
                            $Return['Message'] = "User in live";
                        }
                    } else {
                        /** check round even or odd * */
                        if (($Draft['DraftLiveRound'] % 2) != 0) {
                            /** value odd number * */
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName,J.AuctionUserStatus");
                            $this->db->from('nba_sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.DraftUserPosition", $Draft['TotalJoined']);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $LiveUser = $Query->row_array();
                                $CurrentDateTime = date('Y-m-d H:i:s');
                                $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                $CurrentDateTime = new DateTime($CurrentDateTime);
                                $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                $Users["ContestID"] = $Draft['ContestID'];
                                $Users["SeriesID"] = $Draft['SeriesID'];
                                $Users["ContestGUID"] = $Draft['ContestGUID'];
                                $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                $Users["DraftNextRound"] = $Draft['DraftLiveRound'] + 1;
                                $Users["GameType"] = $Draft['GameType'];
                                $Users["UserID"] = $LiveUser['UserID'];
                                $Users["UserGUID"] = $LiveUser['UserGUID'];
                                $Users["FirstName"] = $LiveUser['FirstName'];
                                $Users["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                                $Return["Status"] = 1;
                                $Return["Data"] = $Users;
                                $Return['Message'] = "User in live";
                                $this->draftRoundUpdate($Draft['ContestID'], $Draft['SeriesID'], $Draft['DraftLiveRound'] + 1);
                            } else {
                                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,J.AuctionUserStatus");
                                $this->db->from('nba_sports_contest_join J, tbl_users U');
                                $this->db->where("J.DraftUserLive", "Yes");
                                $this->db->where("U.UserID", "J.UserID", FALSE);
                                $this->db->where("J.ContestID", $ContestID);
                                $this->db->where("J.SeriesID", $SeriesID);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $CurrentUser = $Query->row_array();
                                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,U.FirstName,J.AuctionUserStatus");
                                    $this->db->from('nba_sports_contest_join J, tbl_users U');
                                    $this->db->where("J.DraftUserLive", "No");
                                    $this->db->where("J.DraftUserPosition", $CurrentUser['DraftUserPosition'] + 1);
                                    $this->db->where("U.UserID", "J.UserID", FALSE);
                                    $this->db->where("J.ContestID", $ContestID);
                                    $this->db->where("J.SeriesID", $SeriesID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $NextUser = $Query->row_array();
                                        $CurrentDateTime = date('Y-m-d H:i:s');
                                        $DraftUserLiveTime = $NextUser['DraftUserLiveTime'];
                                        $CurrentDateTime = new DateTime($CurrentDateTime);
                                        $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                        $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                        $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                        $Users["ContestID"] = $Draft['ContestID'];
                                        $Users["SeriesID"] = $Draft['SeriesID'];
                                        $Users["ContestGUID"] = $Draft['ContestGUID'];
                                        $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                        $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                        $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                                        $Users["GameType"] = $Draft['GameType'];
                                        $Users["UserID"] = $NextUser['UserID'];
                                        $Users["UserGUID"] = $NextUser['UserGUID'];
                                        $Users["FirstName"] = $NextUser['FirstName'];
                                        $Users["DraftUserTimer"] = ($NextUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                                        $Return["Status"] = 1;
                                        $Return["Data"] = $Users;
                                        $Return['Message'] = "User in live";
                                    }
                                }
                            }
                        } else {
                            /* value odd number * */
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName,J.AuctionUserStatus");
                            $this->db->from('nba_sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.DraftUserPosition", 1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $LiveUser = $Query->row_array();
                                $CurrentDateTime = date('Y-m-d H:i:s');
                                $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                $CurrentDateTime = new DateTime($CurrentDateTime);
                                $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                $Users["ContestID"] = $Draft['ContestID'];
                                $Users["SeriesID"] = $Draft['SeriesID'];
                                $Users["ContestGUID"] = $Draft['ContestGUID'];
                                $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                $Users["DraftNextRound"] = $Draft['DraftLiveRound'] + 1;
                                $Users["GameType"] = $Draft['GameType'];
                                $Users["UserID"] = $LiveUser['UserID'];
                                $Users["UserGUID"] = $LiveUser['UserGUID'];
                                $Users["FirstName"] = $LiveUser['FirstName'];
                                $Users["DraftUserTimer"] = ($LiveUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                                $Return["Status"] = 1;
                                $Return["Data"] = $Users;
                                $Return['Message'] = "User in live";
                                $this->draftRoundUpdate($Draft['ContestID'], $Draft['SeriesID'], $Draft['DraftLiveRound'] + 1);
                            } else {
                                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,J.AuctionUserStatus");
                                $this->db->from('nba_sports_contest_join J, tbl_users U');
                                $this->db->where("J.DraftUserLive", "Yes");
                                $this->db->where("U.UserID", "J.UserID", FALSE);
                                $this->db->where("J.ContestID", $ContestID);
                                $this->db->where("J.SeriesID", $SeriesID);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $CurrentUser = $Query->row_array();
                                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,U.FirstName,J.AuctionUserStatus");
                                    $this->db->from('nba_sports_contest_join J, tbl_users U');
                                    $this->db->where("J.DraftUserLive", "No");
                                    $this->db->where("J.DraftUserPosition", $CurrentUser['DraftUserPosition'] - 1);
                                    $this->db->where("U.UserID", "J.UserID", FALSE);
                                    $this->db->where("J.ContestID", $ContestID);
                                    $this->db->where("J.SeriesID", $SeriesID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $NextUser = $Query->row_array();
                                        $CurrentDateTime = date('Y-m-d H:i:s');
                                        $DraftUserLiveTime = $NextUser['DraftUserLiveTime'];
                                        $CurrentDateTime = new DateTime($CurrentDateTime);
                                        $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                        $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                        $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                        $Users["ContestID"] = $Draft['ContestID'];
                                        $Users["SeriesID"] = $Draft['SeriesID'];
                                        $Users["ContestGUID"] = $Draft['ContestGUID'];
                                        $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                        $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                        $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                                        $Users["GameType"] = $Draft['GameType'];
                                        $Users["UserID"] = $NextUser['UserID'];
                                        $Users["UserGUID"] = $NextUser['UserGUID'];
                                        $Users["FirstName"] = $NextUser['FirstName'];
                                        $Users["DraftUserTimer"] = ($NextUser['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                                        $Return["Status"] = 1;
                                        $Return["Data"] = $Users;
                                        $Return['Message'] = "User in live";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    function addDraftUserTeam($UserID, $ContestID, $SeriesID, $MatchID) {
        /** check is assistant and unsold player * */
        $UserTeamID = $this->db->query('SELECT T.UserTeamID from `nba_sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.UserID = "' . $UserID . '" AND T.ContestID = "' . $ContestID . '" AND IsPreTeam = "No" AND IsAssistant="No" ')->row()->UserTeamID;
        if (empty($UserTeamID)) {
            $EntityGUID = get_guid();
            $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $UserID, "StatusID" => 2,"GameSportsType" => 'Nba'));
            /* Add user team to user team table . */
            $TeamName = "PostSnakeTeam 1";
            $UserTeamID = $EntityID;
            $InsertData = array(
                "UserTeamID" => $EntityID,
                "UserTeamGUID" => $EntityGUID,
                "UserID" => $UserID,
                "UserTeamName" => $TeamName,
                "UserTeamType" => "Draft",
                "IsPreTeam" => "No",
                "SeriesID" => $SeriesID,
                "ContestID" => $ContestID,
                "MatchID" => $MatchID,
                "IsAssistant" => "No",
                "AuctionTopPlayerSubmitted" => "Yes"
            );
            $this->db->insert('nba_sports_users_teams', $InsertData);
        }
        return $UserTeamID;
    }

    function addDraftUserTeamSquad($UserTeamID, $UserID, $ContestID, $SeriesID, $Player) {
        /** dynamic player role * */
        $Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftPlayerSelectionCriteria = (!empty($Series['DraftPlayerSelectionCriteria'])) ? json_decode($Series['DraftPlayerSelectionCriteria'], TRUE) : "";
        $DraftPlayerSelectionCriteria = (!empty($DraftPlayerSelectionCriteria)) ? $DraftPlayerSelectionCriteria : array("Wk" => 1, "Bat" => 4, "Ar" => 2, "Bowl" => 4);
        $DraftTeamPlayerLimit = (!empty($Series['DraftTeamPlayerLimit'])) ? $Series['DraftTeamPlayerLimit'] : 15;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            foreach ($DraftSquad as $Key => $PlayerSquad) {
                $DraftSquad[$Key]['PlayerRole'] = $this->db->query('SELECT S.PlayerRole from `tbl_auction_player_bid_status` S  WHERE S.SeriesID = "' . $SeriesID . '" AND S.ContestID = "' . $ContestID . '" AND S.PlayerID = "' . $PlayerSquad['PlayerID'] . '"')->row()->PlayerRole;
            }
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));
            /** check bowler role * */
            if (@$PlayerRoles['Bowler'] < $DraftPlayerSelectionCriteria['Bowl'] && $Player['PlayerRole'] == "Bowler") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold"
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['Batsman'] < $DraftPlayerSelectionCriteria['Bat'] && $Player['PlayerRole'] == "Batsman") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold"
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['AllRounder'] < $DraftPlayerSelectionCriteria['Ar'] && $Player['PlayerRole'] == "AllRounder") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold"
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['WicketKeeper'] < $DraftPlayerSelectionCriteria['Wk'] && $Player['PlayerRole'] == "WicketKeeper") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold"
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else {
                //echo 1;exit;
                /** check total player in squad * */
                if ($Rows < $DraftTeamPlayerLimit) {
                    /** insert user team player squad * */
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerPosition" => "Player",
                        "PlayerID" => $Player['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "DateTime" => date('Y-m-d H:i:s'),
                    );
                    $this->db->insert('sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold"
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $Player['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Player['PlayerID'],
                "SeriesID" => $SeriesID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);

            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold"
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            return true;
        }
    }

    function addDraftUserTeamSquadAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Team, $RoosterSize) {
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        /** dynamic player role * */
        $ContestCriteria = $this->getContests('ContestID,DraftLiveRound,DraftPlayerSelectionCriteria', array("ContestID" => $ContestID), FALSE, 1);

        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1,"C"=> 1, "FLEX" => 3);

        $DraftTeamPlayerLimit = $RoosterSize;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('nba_sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            /** check is assistant and unsold player * */
            $this->db->select('UTP.PlayerID,UT.UserTeamID,UT.UserID,BS.PlayerStatus,ST.PlayerName,(CASE BS.PlayerRole
                                         when "PointGuard" then "PG"
                                         when "Center" then "C"
                                         when "SmallForward" then "SF"
                                         when "ShootingGuard" then "SG"
                                         when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
            $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP,nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            $this->db->where("UT.ContestID", "BS.ContestID", FALSE);
            $this->db->where("UT.SeriesID", "BS.SeriesID", FALSE);
            $this->db->where("UTP.PlayerID", "BS.PlayerID", FALSE);
            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
            $this->db->where("UT.IsAssistant", "Yes");
            $this->db->where("UT.IsPreTeam", "Yes");
            $this->db->where("UT.UserTeamType", "Draft");
            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
            $this->db->where("UT.ContestID", $ContestID);
            $this->db->where("UT.SeriesID", $SeriesID);
            $this->db->where("UT.UserID", $UserID);
            $this->db->order_by("UTP.AuctionDraftAssistantPriority", "ASC");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $AssistantPlayers = $Query->result_array();
                foreach ($AssistantPlayers as $Assistant) {
                    if ($Rows < $DraftTeamPlayerLimit) {
                        $AllRoles = $this->db->query('SELECT PlayerSelectTypeRole from `nba_sports_users_team_players` WHERE PlayerSelectTypeRole != "FLEX" AND PlayerSelectTypeRole != "" AND UserTeamID = "' . $UserTeamID .'"')->result_array();
                        if(!empty($AllRoles))
                        { 
                            $Exist = 'yes';
                            $target = array('PF','SG','SF','C','PG');
                            $AllRoles=  array_column($AllRoles,'PlayerSelectTypeRole');
                            foreach ($target as $ExistValue) {
                                if (!in_array($ExistValue, $AllRoles)) {
                                    $Exist = 'no';
                                }
                            }
                            if ($Exist=='yes') {
                                $Assistant['PlayerRoleShort'] = 'FLEX';
                            }
                        }
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerID" => $Assistant['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "MatchID" => $MatchID,
                            "DateTime" => date('Y-m-d H:i:s'),
                            "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                            "PlayerSelectTypeRole" =>$Assistant['PlayerRoleShort']
                        );
                        $this->db->insert('nba_sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold"
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $Assistant['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $Assistant;
                        return $Return;
                    } else {
                        $Return["Status"] = 0;
                        $Return["Player"] = $Assistant;
                        return $Return;
                    }
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerID" => $Team['PlayerID'],
                "SeriesID" => $SeriesID,
                "MatchID" => $MatchID,
                "DateTime" => date('Y-m-d H:i:s'),
                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                "PlayerSelectTypeRole" =>$Team['PlayerRole']
            );
            $this->db->insert('nba_sports_users_team_players', $InsertData);
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold"
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Team['PlayerID']);
            $this->db->limit(1);
            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Team;
            return $Return;
        }
    }

    function addDraftUserTeamSquadNotAssistantOLD($UserTeamID, $UserID, $ContestID, $SeriesID, $Team, $RoosterSize) {
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        /** dynamic player role * */
        $DraftTeamPlayerLimit = $RoosterSize;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            /** check total player in squad * */
            if ($Rows < $DraftTeamPlayerLimit) {
                /** check is assistant and unsold player * */
                $this->db->select("BS.PlayerStatus,BS.PlayerID");
                $this->db->from('tbl_auction_player_bid_status BS,sports_players ST');
                $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                $this->db->where("BS.ContestID", $ContestID);
                $this->db->where("BS.SeriesID", $SeriesID);
                $this->db->order_by("ST.FantasyPoints", "DESC");
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $AllPlayer = $Query->row_array();
                    /** insert user team player squad * */
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerID" => $AllPlayer['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "DateTime" => date('Y-m-d H:i:s'),
                    );
                    $this->db->insert('sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold"
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                    $Return["Status"] = 1;
                    $Return["Player"] = $AllPlayer;
                    return $Return;
                } else {
                    $Return["Status"] = 0;
                    return $Return;
                }
            } else {
                $Return["Status"] = 0;
                return $Return;
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerID" => $Team['PlayerID'],
                "SeriesID" => $SeriesID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold"
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Team['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Team;
            return $Return;
        }
    }

    function addDraftUserTeamSquadIsPlayer($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Team, $RoosterSize, $PlayerRole='') {
        
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        $ContestCriteria = $this->getContests('ContestID,DraftPlayerSelectionCriteria,DraftLiveRound,RosterSize', array("ContestID" => $ContestID), FALSE, 1);
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1,"C" => 1,"FLEX"=>3);
        /** dynamic player role * */
        $DraftTeamPlayerLimit = $RoosterSize;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerSelectTypeRole PlayerRole");
        $this->db->from('nba_sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));
    
            $DraftLiveRound = $ContestCriteria['DraftLiveRound'];
            $CriteriaRounds = $ContestCriteria['RosterSize'];
            if ($DraftLiveRound <= $CriteriaRounds) {

                if ($PlayerRole == 'PF/C') {
                    if (@$PlayerRoles['PF/C'] < $DraftPlayerSelectionCriteria['PF/C']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                 "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for PF/C is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }
                
                if ($Team['PlayerRole'] == "PG" && $PlayerRole !='FLEX') {
                    if (@$PlayerRoles['PG'] < $DraftPlayerSelectionCriteria['PG']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for PG is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }

                if ($Team['PlayerRole'] == "SG" && $PlayerRole !='FLEX') {
                    if (@$PlayerRoles['SG'] < $DraftPlayerSelectionCriteria['SG']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                 "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for SG is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }

                if ($Team['PlayerRole'] == "SF" && $PlayerRole !='FLEX') {
                    if (@$PlayerRoles['SF'] < $DraftPlayerSelectionCriteria['SF']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                 "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for SF is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }

                if ($Team['PlayerRole'] == "PF" && $PlayerRole !='FLEX') {
                    if (@$PlayerRoles['PF'] < $DraftPlayerSelectionCriteria['PF']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                 "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for PF is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }

                if ($Team['PlayerRole'] == "C" && $PlayerRole !='FLEX') {
                    if (@$PlayerRoles['C'] < $DraftPlayerSelectionCriteria['C']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "MatchID" =>  $MatchID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                "PlayerSelectTypeRole" =>$PlayerRole
                            );
                            $this->db->insert('nba_sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for C is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }
                if ($PlayerRole == "FLEX") {
                    $AllRoles = $this->db->query('SELECT PlayerSelectTypeRole from `nba_sports_users_team_players` WHERE PlayerSelectTypeRole != "FLEX" AND PlayerSelectTypeRole != "" AND UserTeamID = "' . $UserTeamID .'"')->result_array();
                    if(!empty($AllRoles))
                    { 
                        $Exist = 'yes';
                        $target = array('PF','SG','SF','C','PG');
                        $AllRoles=  array_column($AllRoles,'PlayerSelectTypeRole');
                        foreach ($target as $ExistValue) {
                            if (!in_array($ExistValue, $AllRoles)) {
                                $Exist = 'no';
                            }
                        }
                        if ($Exist=='yes') {
                            if (@$PlayerRoles['FLEX'] < $DraftPlayerSelectionCriteria['FLEX']) {
                            /** check total player in squad * */
                                if ($Rows < $DraftTeamPlayerLimit) {
                                    /** insert user team player squad * */
                                    $InsertData = array(
                                        "UserTeamID" => $UserTeamID,
                                        "PlayerPosition" => "Player",
                                        "PlayerID" => $Team['PlayerID'],
                                        "SeriesID" => $SeriesID,
                                        "MatchID" =>  $MatchID,
                                        "DateTime" => date('Y-m-d H:i:s'),
                                        "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                         "PlayerSelectTypeRole" =>$PlayerRole
                                    );
                                    $this->db->insert('nba_sports_users_team_players', $InsertData);
                                    /* Add contest to contest table . */
                                    $UpdateData = array_filter(array(
                                        "PlayerStatus" => "Sold",
                                        "DateTime" => date('Y-m-d H:i:s')
                                    ));
                                    $this->db->where('SeriesID', $SeriesID);
                                    $this->db->where('ContestID', $ContestID);
                                    $this->db->where('PlayerID', $Team['PlayerID']);
                                    $this->db->limit(1);
                                    $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                    $Return["Status"] = 1;
                                    $Return["Player"] = $Team;
                                    return $Return;
                                } else {
                                    $Return["Status"] = 0;
                                    $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                                    return $Return;
                                }
                            } else {
                                $Return["Status"] = 0;
                                $Return['Message'] = "Minimum Criteria for FLEX is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                                return $Return;
                            }
                        }else{
                            $Return["Status"] = 0;
                            $Return['Message'] = "Please select player for all other positions before the FLEX position";
                            return $Return;
                        }                        
                    }else{
                        $Return["Status"] = 0;
                        $Return['Message'] = "Please select player for all other positions before the FLEX position";
                        return $Return;
                    }
                }

                /*if ($Team['PlayerRole'] == "DEF") {
                    if (@$PlayerRoles['DEF'] < $DraftPlayerSelectionCriteria['DEF']) {
                        if ($Rows < $DraftTeamPlayerLimit) {
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Team['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "DateTime" => date('Y-m-d H:i:s'),
                                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                 "PlayerSelectTypeRole" =>$Team['PlayerRole']
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Team['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Team;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for DEF is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Players";
                        return $Return;
                    }
                }*/
            }else{
                /** check player role condition * */
                if ($Rows < $DraftTeamPlayerLimit) {
                    /** insert user team player squad * */
                     if ($PlayerRole == "FLEX") {
                            $AllRoles = $this->db->query('SELECT PlayerID from `nba_sports_users_team_players` WHERE 
                            UserTeamID = "' . $UserTeamID . '" AND PlayerSelectTypeRole = "PG" AND PlayerSelectTypeRole = "SG" AND PlayerSelectTypeRole = "SF" AND PlayerSelectTypeRole = "PF" AND PlayerSelectTypeRole = "C"')->row()->PlayerID;
                        if(empty($AllRoles))
                        { 
                            $Return["Status"] = 0;
                               $Return['Message'] = "Please select player for all other positions before the FLEX position";
                             return $Return;

                        }
                   }
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerPosition" => "Player",
                        "PlayerID" => $Team['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "MatchID" =>  $MatchID,
                        "DateTime" => date('Y-m-d H:i:s'),
                        "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                         "PlayerSelectTypeRole" =>$PlayerRole
                    );
                    $this->db->insert('nba_sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold",
                        "DateTime" => date('Y-m-d H:i:s')
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $Team['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                    $Return["Status"] = 1;
                    $Return["Player"] = $Team;
                    return $Return;
                } else {
                    $Return["Status"] = 0;
                    $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                    return $Return;
                }
            }
        } else {
         
            if ($PlayerRole == "FLEX") {
                    $AllRoles = $this->db->query('SELECT PlayerID from `nba_sports_users_team_players` WHERE 
                            UserTeamID = "' . $UserTeamID . '" AND PlayerSelectTypeRole = "PG" AND PlayerSelectTypeRole = "SG" AND PlayerSelectTypeRole = "SF" AND PlayerSelectTypeRole = "PF" AND PlayerSelectTypeRole = "C"')->row()->PlayerID;
                if(empty($AllRoles))
                { 
                    $Return["Status"] = 0;
                       $Return['Message'] = "Please select player for all other positions before the FLEX position";
                     return $Return;

                }
            }
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Team['PlayerID'],
                "SeriesID" => $SeriesID,
                "MatchID" =>  $MatchID,
                "DateTime" => date('Y-m-d H:i:s'),
                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                 "PlayerSelectTypeRole" =>$PlayerRole
            );
            $this->db->insert('nba_sports_users_team_players', $InsertData);
            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Team['PlayerID']);
            $this->db->limit(1);
            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Team;
            return $Return;
        }
    }

    function addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Team, $RoosterSize) {
        
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        $ContestCriteria = $this->getContests('ContestID,DraftPlayerSelectionCriteria,DraftLiveRound,RosterSize', array("ContestID" => $ContestID), FALSE, 1);
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1,"C" => 1,"FLEX"=>3);
        /** dynamic player role * */
        $DraftTeamPlayerLimit = $RoosterSize;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerSelectTypeRole PlayerRole");
        $this->db->from('nba_sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));
            $DraftLiveRound = $ContestCriteria['DraftLiveRound'];
            $CriteriaRounds = $ContestCriteria['RosterSize'];
            if ($DraftLiveRound <= $CriteriaRounds) {

                    if (@$PlayerRoles['PG'] < $DraftPlayerSelectionCriteria['PG']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                         when "PointGuard" then "PG"
                                         when "Center" then "C"
                                         when "SmallForward" then "SF"
                                         when "ShootingGuard" then "SG"
                                         when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.PlayerRole", "PointGuard");
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
                                );
                                
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }

                    if (@$PlayerRoles['C'] < $DraftPlayerSelectionCriteria['C']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                when "PointGuard" then "PG"
                                when "Center" then "C"
                                when "SmallForward" then "SF"
                                when "ShootingGuard" then "SG"
                                when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.PlayerRole", "Center");
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
                                );
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }

                    if (@$PlayerRoles['SF'] < $DraftPlayerSelectionCriteria['SF']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.PlayerRole", "SmallForward");
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
                                );
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }

                    if (@$PlayerRoles['SG'] < $DraftPlayerSelectionCriteria['SG']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.PlayerRole", "ShootingGuard");
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
                                );
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }

                    if (@$PlayerRoles['PF'] < $DraftPlayerSelectionCriteria['PF']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.PlayerRole", "PowerForward");
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
                                );
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }

                    if (@$PlayerRoles['FLEX'] < $DraftPlayerSelectionCriteria['FLEX']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {

                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where_in("BS.PlayerRole", array("PointGuard", "Center","SmallForward","ShootingGuard","PowerForward"));
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                           // $this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->row_array();
                                /** insert user team player squad * */
                                $InsertData = array(
                                    "UserTeamID" => $UserTeamID,
                                    "PlayerPosition" => "Player",
                                    "PlayerID" => $AllPlayer['PlayerID'],
                                    "SeriesID" => $SeriesID,
                                    "MatchID" => $MatchID,
                                    "DateTime" => date('Y-m-d H:i:s'),
                                    "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                                    "PlayerSelectTypeRole" =>"FLEX"
                                );
                                $this->db->insert('nba_sports_users_team_players', $InsertData);
                                /* Add contest to contest table . */
                                $UpdateData = array_filter(array(
                                    "PlayerStatus" => "Sold",
                                    "DateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                                $this->db->limit(1);
                                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                                $Return["Status"] = 1;
                                $Return["Player"] = $AllPlayer;
                                return $Return;
                            }
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    }
            }else{
                /** check player role condition * */
                if ($Rows < $DraftTeamPlayerLimit) {
                    /** insert user team player squad * */
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerPosition" => "Player",
                        "PlayerID" => $Team['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "MatchID" => $MatchID,
                        "DateTime" => date('Y-m-d H:i:s'),
                        "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                         "PlayerSelectTypeRole" =>$PlayerRole
                    );
                    $this->db->insert('nba_sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold",
                        "DateTime" => date('Y-m-d H:i:s')
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $Team['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                    $Return["Status"] = 1;
                    $Return["Player"] = $Team;
                    return $Return;
                } else {
                    $Return["Status"] = 0;
                    $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                    return $Return;
                }
            }
        } else {
            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRoleShort,BS.PlayerRole');
            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
            //$this->db->where("BS.PlayerRole", "WideReceiver");
            $this->db->where("BS.ContestID", $ContestID);
            $this->db->where("BS.SeriesID", $SeriesID);
            //$this->db->order_by("ST.YardsPerGame", "DESC");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $AllPlayer = $Query->row_array();
            }            
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $AllPlayer['PlayerID'],
                "SeriesID" => $SeriesID,
                "MatchID" => $MatchID,
                "DateTime" => date('Y-m-d H:i:s'),
                "DraftRound"=> $ContestCriteria['DraftLiveRound'],
                "PlayerSelectTypeRole" =>$AllPlayer['PlayerRoleShort']
            );
            $this->db->insert('nba_sports_users_team_players', $InsertData);
            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $AllPlayer['PlayerID']);
            $this->db->limit(1);
            $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $AllPlayer;
            return $Return;
        }
    }


    /*
      Description: draft player sold.
     */

    function draftPlayerSold($Input = array(), $SeriesID, $ContestID, $UserID, $PlayerID = "") {
        $Return = array();
        $Return["Data"]["Status"] = 0;
        $Return['Message'] = "Draft player error";
        $Return["Data"]['Player'] = array();
        $Return["Data"]['User'] = array();
        $Return["Data"]['DraftStatus'] = "Running";

        /** check auction completed * */
        $DraftGames = $this->getContests('ContestID,SeriesID,SeriesGUID,DraftTotalRounds,TotalJoined,DraftLiveRound,RosterSize,MatchID,WeekStart,Privacy', array('LeagueType' => "Draft", "ContestID" => $ContestID, "SeriesID" => $SeriesID), TRUE, 1);
        $RoosterSize = ($DraftGames['Data']['TotalRecords'] > 0) ? $DraftGames['Data']['Records'][0]['RosterSize'] : 0;
        $MatchID = ($DraftGames['Data']['TotalRecords'] > 0) ? $DraftGames['Data']['Records'][0]['MatchID'] : 0;
        $IsPrivate = ($DraftGames['Data']['TotalRecords'] > 0) ? $DraftGames['Data']['Records'][0]['Privacy'] : "No";
        /** check player in live * */
        $this->db->select("ContestID,DraftUserLiveTime,AuctionUserStatus");
        $this->db->from('nba_sports_contest_join');
        $this->db->where("DraftUserLive", "Yes");
        $this->db->where("UserID", $UserID);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->limit(1);
        $Query = $this->db->get();
        //print_r($Query);die;
        if ($Query->num_rows() > 0) {
            $DraftUserDetails = $Query->row_array();
            /** check is assistant and unsold player * */
            $this->db->select("FirstName,UserGUID,UserID,UserTeamCode");
            $this->db->from('tbl_users');
            $this->db->where("UserID", $UserID);
            $this->db->limit(1);
            $Query = $this->db->get();
            $UserDetails = $Query->row_array();
            /** check player id empty * */
            if (empty($PlayerID)) {
                $UserTimer = ($DraftUserDetails['AuctionUserStatus'] == "Online") ? DRAFT_TIME : DRAFT_TIME_USER;
                $DraftUserLiveTime = $DraftUserDetails['DraftUserLiveTime'];
                $CurrentDateTime = new DateTime($CurrentDateTime);
                $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                if ($diffSeconds >= $UserTimer) {
                    /** check is assistant and unsold player * */
                    $this->db->select('UTP.PlayerID,UT.UserTeamID,UT.UserID,BS.PlayerStatus,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRole');
                    $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP,nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                    $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                    $this->db->where("UT.ContestID", "BS.ContestID", FALSE);
                    $this->db->where("UT.SeriesID", "BS.SeriesID", FALSE);
                    $this->db->where("UTP.PlayerID", "BS.PlayerID", FALSE);
                    $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                    $this->db->where("UT.IsAssistant", "Yes");
                    $this->db->where("UT.IsPreTeam", "Yes");
                    $this->db->where("UT.UserTeamType", "Draft");
                    $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                    $this->db->where("UT.ContestID", $ContestID);
                    $this->db->where("UT.SeriesID", $SeriesID);
                    $this->db->where("UT.UserID", $UserID);
                    $this->db->order_by("UTP.AuctionDraftAssistantPriority", "ASC");
                    $this->db->limit(1);
                    $Query = $this->db->get();
                    $AssistantPlayers = $Query->result_array();
                    if ($Query->num_rows() > 0 && 1 == 0 /* Disable Auto Draft Player*/) {
                        $AssistantPlayers = $Query->result_array();
                        foreach ($AssistantPlayers as $Player) {
                            /** user team and squad create * */
                            $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $MatchID);
                            if ($UserTeamID) {
                                $Status = $this->addDraftUserTeamSquadAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Player, $RoosterSize);
                                if (empty($Status)) {
                                    /** check is assistant and unsold player * */
                                    $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                                    $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players SP');
                                    $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                                    // $this->db->where("BS.PlayerStatus", "Upcoming");
                                    $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                                    $this->db->where("BS.ContestID", $ContestID);
                                    $this->db->where("BS.SeriesID", $SeriesID);
                                    $this->db->limit(1);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $AllPlayer = $Query->result_array();
                                        foreach ($AllPlayer as $Player) {
                                            /** user team and squad create * */
                                            $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $MatchID);
                                            if ($UserTeamID) {
                                                $Status = $this->addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Player, $RoosterSize);
                                                if ($Status['Status'] == 1) {
                                                    $Return["Data"]["Status"] = 1;
                                                    $Return['Message'] = "Successfully player added";
                                                } else {
                                                    $Return['Message'] = "Team Players length can't greater than " . $RoosterSize;
                                                }
                                                $Return["Data"]['Player'] = $Status['Player'];
                                                $Return["Data"]['User'] = $UserDetails;
                                            }
                                        }
                                    }
                                } else {
                                    if ($Status['Status'] == 1) {
                                        $Return["Data"]["Status"] = 1;
                                        $Return['Message'] = "Successfully player added";
                                    } else {
                                        $Return['Message'] = "Team Players length can't greater than " . $RoosterSize;
                                    }
                                    $Return["Data"]['Player'] = $Status['Player'];
                                    $Return["Data"]['User'] = $UserDetails;
                                }
                            }
                        }
                    } else {
                        /** check is assistant and unsold player * */
                        $Return["Data"]["Status"] = 1;
                        $Return['Message'] = "Timeout";

                        /* Update Contest Status */
                        $this->db->select('ContestID');
                        $this->db->from('nba_sports_contest_join');
                        $this->db->where('UserID', $UserID);
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('IsAutoDraft', "Yes");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if($IsPrivate == "Yes" && $Query->num_rows() > 0){
                            $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                                        when "PointGuard" then "PG"
                                        when "Center" then "C"
                                        when "SmallForward" then "SF"
                                        when "ShootingGuard" then "SG"
                                        when "PowerForward" then "PF"
                                         END) as PlayerRole');
                            $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                            $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                            $this->db->where("BS.ContestID", $ContestID);
                            $this->db->where("BS.SeriesID", $SeriesID);
                            //$this->db->order_by("ST.YardsPerGame", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $AllPlayer = $Query->result_array();
                                foreach ($AllPlayer as $Player) {
                                    $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $MatchID);
                                    if ($UserTeamID) {
                                        $Status = $this->addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Player, $RoosterSize);
                                        if ($Status['Status'] == 1) {
                                            $Return["Data"]["Status"] = 1;
                                            $Return['Message'] = "Successfully player added";
                                        } else {
                                            $Return['Message'] = "Team Players length can't greater than " . $RoosterSize;
                                        }
                                        $Return["Data"]['Player'] = $Status['Player'];
                                        $Return["Data"]['User'] = $UserDetails;
                                    }
                                }
                            }

                        }
                    }
                }
            } else {
                /** check is assistant and unsold player * */
                $this->db->select('BS.PlayerStatus,BS.PlayerID,ST.PlayerName,(CASE BS.PlayerRole
                            when "PointGuard" then "PG"
                            when "Center" then "C"
                            when "SmallForward" then "SF"
                            when "ShootingGuard" then "SG"
                            when "PowerForward" then "PF"
                             END) as PlayerRole');
                $this->db->from('nba_tbl_auction_player_bid_status BS,nba_sports_players ST');
                $this->db->where("BS.PlayerID", "ST.PlayerID", FALSE);
                $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                $this->db->where("BS.ContestID", $ContestID);
                $this->db->where("BS.SeriesID", $SeriesID);
                $this->db->where("BS.PlayerID", $PlayerID);
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $AllPlayer = $Query->result_array();
                    foreach ($AllPlayer as $Player) {
                 
                        $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $MatchID);
                        if ($UserTeamID) {
                            $Status = $this->addDraftUserTeamSquadIsPlayer($UserTeamID, $UserID, $ContestID, $SeriesID, $MatchID, $Player, $RoosterSize, $Input['PlayerRole']);
                            if ($Status['Status'] == 1) {
                                $Return["Data"]["Status"] = 1;
                                $Return['Message'] = "Successfully player added";
                            } else {
                                $Return['Message'] = "Team Players length can't greater than " . $RoosterSize;
                                $Return['Message'] = $Status['Message'];
                            }
                            $Return["Data"]['Player'] = $Status['Player'];
                            $Return["Data"]['User'] = $UserDetails;
                        }
                    }
                } else {
                    $Return['Message'] = "Draft player already sold";
                }
            }

            if ($DraftGames['Data']['TotalRecords'] > 0) {
                $Users = array();
                foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {
                    if ($Draft['DraftLiveRound'] >= $Draft['DraftTotalRounds']) {
                        if (($Draft['DraftLiveRound'] % 2) == 0) {
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID");
                            $this->db->from('nba_sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.UserID", $UserID);
                            $this->db->where("J.DraftUserPosition", 1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $Return["Data"]['DraftStatus'] = "Completed";
                                /* draft complete . */
                                $UpdateData = array_filter(array(
                                    "AuctionStatusID" => 5,
                                    "AuctionUpdateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->limit(1);
                                $this->db->update('nba_sports_contest', $UpdateData);

                                //$this->playerOnBench($ContestID);
                            }
                        } else {
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID");
                            $this->db->from('nba_sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.UserID", $UserID);
                            $this->db->where("J.DraftUserPosition", $Draft['TotalJoined']);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $Return["Data"]['DraftStatus'] = "Completed";
                                /* draft complete . */
                                $UpdateData = array_filter(array(
                                    "AuctionStatusID" => 5,
                                    "AuctionUpdateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->limit(1);
                                $this->db->update('nba_sports_contest', $UpdateData);

                                //$this->playerOnBench($ContestID);
                            }
                        }
                    }
                }
            }
        } else {
            $Return['Message'] = "User not in live";
        }

        return $Return;
    }

    /** bench team submit * */
    function playerOnBench($ContestID) {

        $ContestsValue = $this->getContests('SeriesID,GameType,SubGameType,SubGameTypeKey,ContestID,WeekStart,WeekEnd,RosterSize,PlayedRoster,BatchRoster', array('AuctionStatusID' => 5, "ContestID" => $ContestID));
        $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $ContestID), TRUE, 0);
        if (!empty($JoinedContestsUsers)) {
            foreach ($JoinedContestsUsers['Data']['Records'] as $Rows) {
                /** to check week team submit or not* */
                $this->db->select('T.UserTeamID');
                $this->db->from('sports_users_teams T');
                $this->db->where("T.UserID", $Rows['UserID']);
                $this->db->where("T.ContestID", $ContestID);
                $this->db->where("T.IsPreTeam", "No");
                $this->db->where("T.IsAssistant", "No");
                $this->db->where("T.AuctionTopPlayerSubmitted", "Yes");
                $this->db->limit(1);
                $Query = $this->db->get();

                if ($Query->num_rows() == 0) {

                    /** to get week draft team * */
                    $this->db->select('T.UserTeamID,WeekID');
                    $this->db->from('sports_users_teams T');
                    $this->db->where("T.UserID", $Rows['UserID']);
                    $this->db->where("T.ContestID", $ContestID);
                    $this->db->where("T.IsPreTeam", "No");
                    $this->db->where("T.IsAssistant", "No");
                    $this->db->order_by("T.UserTeamID", "DESC");
                    $this->db->limit(1);
                    $Query = $this->db->get();

                    if ($Query->num_rows() > 0) {
                        $UserTeam = $Query->row_array();

                        /** to get week draft team * */
                        $this->db->select('UserTeamID,TeamID,PlayerPosition,SeriesID,TeamPlayingStatus,DateTime');
                        $this->db->from('sports_users_team_players');
                        $this->db->where("UserTeamID", $UserTeam['UserTeamID']);
                        $this->db->order_by("DateTime", "ASC");
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $UserTeamPlayer = $Query->result_array();
                            $EntityID = $UserTeam['UserTeamID'];

                            /* Delete Team Players */
                            //$this->db->delete('sports_users_team_players', array('UserTeamID' => $EntityID));
                            $PlayedRoster = $ContestsValue['PlayedRoster'];
                            $BenchRoster = $ContestsValue['BatchRoster'];
                            foreach ($UserTeamPlayer as $Key => $Player) {
                                $TeamPlayingStatus = ($Key + 1 <= $PlayedRoster) ? "Play" : "Bench";
                                $UpdateData = array(
                                    "TeamPlayingStatus" => $TeamPlayingStatus
                                );
                                $this->db->where('UserTeamID', $EntityID);
                                $this->db->where('TeamID', $Player['TeamID']);
                                $this->db->limit(1);
                                $this->db->update('sports_users_team_players', $UpdateData);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description: get draft rounds.
     */

    function getRounds($SeriesID, $ContestID, $DraftTotalRounds) {

        $Return = array();
        $Rounds = array();
        /** to check total player * */
        /* $Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
          $DraftTeamPlayerLimit = (!empty($Series['DraftTeamPlayerLimit'])) ? $Series['DraftTeamPlayerLimit'] : 11; */
        /** get total joined draft users * */
        $JoinedUsers = $this->getJoinedContestsUsers("FirstName,UserID,DraftUserPosition,ProfilePic,AuctionUserStatus,DraftUserLive,UserTeamCode", array('ContestID' => $ContestID, 'SeriesID' => $SeriesID, "OrderBy" => "DraftUserPosition", "Sequence" => "ASC"), TRUE);
        if (!empty($JoinedUsers)) {
            $TotalRecords = $JoinedUsers['Data']['TotalRecords'];
            if ($JoinedUsers['Data']['TotalRecords'] > 0) {
                for ($i = 1; $i <= $DraftTotalRounds; $i++) {
                    $Users = array();
                    foreach ($JoinedUsers['Data']['Records'] as $Rows) {
                        $Temp['DraftUserPosition'] = $Rows["DraftUserPosition"];
                        $Temp['UserGUID'] = $Rows["UserGUID"];
                        $Temp['FirstName'] = $Rows["FirstName"];
                        $Temp['UserTeamCode'] = $Rows["UserTeamCode"];
                        $Temp['UserID'] = $Rows["UserID"];
                        $Temp['UserGUID'] = $Rows["UserGUID"];
                        $Temp['ProfilePic'] = $Rows["ProfilePic"];
                        $Temp['AuctionUserStatus'] = $Rows["AuctionUserStatus"];
                        $Temp['DraftUserLive'] = $Rows["DraftUserLive"];
                        $Users[] = $Temp;
                    }
                    if ($i % 2 == 0) {
                        $Users = array_reverse($Users);
                    }
                    $Rounds[$i - 1]['Users'] = $Users;
                    $Rounds[$i - 1]['Round'] = $i;
                }
            }
        }
        return $Rounds;
    }

    function checkAuctionPlayerOnBidAndAuctionCompleted($SeriesID, $ContestID) {
        /** check upcoming player * */
        $this->db->select("PlayerID,BidCredit,PlayerStatus");
        $this->db->from("tbl_auction_player_bid_status");
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where_in('PlayerStatus', array("Upcoming", "Live"));
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

        return;
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
        $TimeZone = DEFAULT_TIMEZONE;
        if(!empty($Where['TimeZone'])){
          $TimeZone = $Where['TimeZone'];   
        }
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'StatusID' => 'E.StatusID',
                'MatchID' => 'M.MatchID',
                'MatchGUID' => 'M.MatchGUID',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamGUIDLocal' => 'TL.TeamGUID AS TeamGUIDLocal',
                'TeamGUIDVisitor' => 'TV.TeamGUID AS TeamGUIDVisitor',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'ContestID' => 'C.ContestID',
                'ContestGUID' => 'C.ContestGUID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'GameType' => 'C.GameType',
                'AuctionUpdateTime' => 'C.AuctionUpdateTime',
                'AuctionBreakDateTime' => 'C.AuctionBreakDateTime',
                'AuctionTimeBreakAvailable' => 'C.AuctionTimeBreakAvailable',
                'AuctionIsBreakTimeStatus' => 'C.AuctionIsBreakTimeStatus',
                'LeagueType' => 'IF(C.LeagueType = "Draft", "Snake Draft", "Auction Draft") as LeagueType',
                'LeagueJoinDateTime' => 'CONVERT_TZ(C.LeagueJoinDateTime,"+00:00","' .  $TimeZone . '") AS LeagueJoinDateTime',
                'LeagueJoinDateTimeUTC' => 'C.LeagueJoinDateTime as LeagueJoinDateTimeUTC',
                'GameTimeLive' => 'C.GameTimeLive',
                'AdminPercent' => 'C.AdminPercent',
                'IsConfirm' => 'C.IsConfirm',
                'IsAutoDraft' => 'C.IsAutoDraft',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'WinningAmount' => 'C.WinningAmount',
                'ContestSize' => 'C.ContestSize',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'ContestType' => 'C.ContestType',
                'ScoringType' => 'C.ScoringType',
                'PlayOff' => 'C.PlayOff',
                'DailyDate' => 'C.DailyDate',
                'ContestDuration' => 'C.ContestDuration',
                'WeekStart' => 'C.WeekStart',
                'WeekEnd' => 'C.WeekEnd',
                'EntryType' => 'C.EntryType',
                'UserJoinLimit' => 'C.UserJoinLimit',
                'DraftTotalRounds' => 'C.DraftTotalRounds',
                'RosterSize' => 'C.RosterSize',
                'PlayedRoster' => 'C.PlayedRoster',
                'BatchRoster' => 'C.BatchRoster',
                'MinimumUserJoined' => 'C.MinimumUserJoined',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'EntryType' => 'C.EntryType',
                'IsWinningDistributed' => 'C.IsWinningDistributed',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'DraftLiveRound' => 'C.DraftLiveRound',
                'SeriesID' => 'S.SeriesID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'C.DraftPlayerSelectionCriteria',
                'SeriesGUID' => 'S.SeriesGUID',
                'SeriesName' => 'S.SeriesName',
                'IsJoined' => '(SELECT IF( EXISTS(
                                SELECT EntryDate FROM nba_sports_contest_join
                                WHERE nba_sports_contest_join.ContestID =  C.ContestID AND UserID = ' . @$Where['SessionUserID'] . ' LIMIT 1), "Yes", "No")) AS IsJoined',
                'TotalJoined' => '(SELECT COUNT(0) FROM nba_sports_contest_join
                                WHERE nba_sports_contest_join.ContestID =  C.ContestID) AS TotalJoined',
                'StatusID' => 'E.StatusID',
                'AuctionStatusID' => 'C.AuctionStatusID',
                'SubGameTypeKey' => 'C.SubGameType SubGameTypeKey',
                'AuctionStatus' => 'CASE C.AuctionStatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "5" then "Completed"
                             when "3" then "Cancelled"
                             END as AuctionStatus',
                'Status' => 'CASE E.StatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "3" then "Cancelled"
                             when "5" then "Completed"
                             END as Status',
                'SubGameType' => 'CASE C.SubGameType
                             when "ProFootballPreSeasonOwners" then "Pro (Pre Season)"
                             when "ProBasketballRegularSeasonOwners" then "Pro (Regular Season)"
                             when "ProBasketballPlayoffs" then "Pro (Playoffs)"
                             END as SubGameType',
                'InvitePermission'=>'C.InvitePermission',
                'PrivatePointSystem' => 'IF(C.Privacy = "Yes", C.PrivatePointScoring, "") AS PrivatePointSystem',
                'isWeekStarted' => '(SELECT IF((SELECT E.StatusID FROM nba_sports_matches M JOIN tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchID = C.MatchID ORDER BY MatchStartDateTime ASC LIMIT 1) = "1", "No", "Yes")) AS isWeekStarted',

            );

            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('C.ContestGUID,C.ContestName,S.SeriesID,C.ContestID,C.UserID,S.SeriesGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_contest C,nba_sports_matches M, nba_sports_teams TL, nba_sports_teams TV,nba_sports_series S');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("M.MatchID", "C.MatchID", FALSE);
        $this->db->where("S.SeriesID", "C.SeriesID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
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
                if (isset($Where['Keyword']['ContestDuration'])) {
                    $this->db->where("C.ContestDuration", @$Where['Keyword']['ContestDuration']);
                }
                if (isset($Where['Keyword']['DailyDate'])) {
                    $this->db->where("C.DailyDate", @$Where['Keyword']['DailyDate']);
                }
                if (isset($Where['Keyword']['LeagueJoinDate'])) {

                    $this->db->where("DATE(C.LeagueJoinDateTime)", @$Where['Keyword']['LeagueJoinDate']);
                }
                if (isset($Where['Keyword']['LeagueJoinTime'])) {
                    // $time = explode(':', $Where['TimeZone']);
                    // $min = (int)($time[0]*60) + ($time[1]) + ($time[2]/60);
                    // $Math = substr($time[0],0,1);
                    // if($Math == '+'){
                    //     $LeagueJoinTime = strtotime($Where['Keyword']['LeagueJoinTime'])+$min*60;
                    // }
                    // if($Math == '-'){
                    //     $LeagueJoinTime = strtotime($Where['Keyword']['LeagueJoinTime'])+$min*60;
                    // }
                    $LeagueJoinDateTimes = strtotime($Where['Keyword']['LeagueJoinTime']) + strtotime($Where['NewTimeZone'].' minutes', 0);
                    $this->db->where("TIME(C.LeagueJoinDateTime)", date('H:i:s',$LeagueJoinDateTimes));
                }
                if (isset($Where['Keyword']['ContestSize'])) {
                    // $ContestSize = explode("-", $Where['Keyword']['ContestSize']);
                    // if (count($ContestSize) > 1) {
                    //     $this->db->where("C.ContestSize >=", @$ContestSize[0]);
                    //     $this->db->where("C.ContestSize <=", @$ContestSize[1]);
                    // } else {
                    //     $this->db->where("C.ContestSize >=", @$ContestSize[0]);
                    // }
                    $this->db->where("C.ContestSize =", @$Where['Keyword']['ContestSize']);
                }
                if (isset($Where['Keyword']['EntryFee'])) {
                    $EntryFee = $Where['Keyword']['EntryFee'];
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
                $this->db->or_like("M.MatchLocation", $Where['Keyword']);
                $this->db->or_like("M.MatchNo", $Where['Keyword']);
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
        if (!empty($Where['IsRandomDraft'])) {
            $this->db->where("C.IsRandomDraft", $Where['IsRandomDraft']);
        }
        if (!empty($Where['AuctionStatusID'])) {
            $this->db->where("C.AuctionStatusID", $Where['AuctionStatusID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("C.UserID", $Where['UserID']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
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
        if (!empty($Where['IsReminderMailSent'])) {
            $this->db->where("C.IsReminderMailSent", $Where['IsReminderMailSent']);
        }
        if (!empty($Where['ContestFormat'])) {
            $this->db->where("C.ContestFormat", $Where['ContestFormat']);
        }
        if (!empty($Where['IsPaid'])) {
            $this->db->where("C.IsPaid", $Where['IsPaid']);
        }
        if (!empty($Where['IsConfirm'])) {
            $this->db->where("C.IsConfirm", $Where['IsConfirm']);
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
        if (!empty($Where['LeagueJoinDateTime'])) {
            $this->db->where("C.LeagueJoinDateTime <=", $Where['LeagueJoinDateTime']);
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
        if (!empty($Where['MatchID'])) {
            $this->db->where("C.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['StatusID'])) {
            if (!empty($Where['CompleteContest']) && $Where['CompleteContest'] == "Yes") {
                $this->db->where_in("E.StatusID", array(5, 3));
            } else {
                $this->db->where("E.StatusID", $Where['StatusID']);
            }
        }
        if (!empty($Where['JoinedContestStatusID']) && $Where['JoinedContestStatusID'] == "Yes") {
            // $this->db->where_in("E.StatusID", array(1));
        }
        if (!empty($Where['AuctionStatusID']) && is_array($Where['AuctionStatusID'])) {
            $this->db->where_in("C.AuctionStatusID", $Where['AuctionStatusID']);
        }
        if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] == "Yes") {
            $this->db->where('EXISTS (select ContestID from nba_sports_contest_join JE where JE.ContestID = C.ContestID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
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
        }
        $this->db->order_by('C.LeagueJoinDateTime', 'DESC');

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
                foreach ($Query->result_array() as $key => $Record) {
                $defaultCustomizeWinningObj->WinningAmount = (!empty($Record['WinningAmount']) ? $Record['WinningAmount'] : 0);

                    $Records[] = $Record;
                    $Records[$key]['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array($defaultCustomizeWinningObj);
                    //$Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                    $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                    $Records[$key]['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                    $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                    $Records[$key]['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                    $Records[$key]['NoOfWinners'] = ((empty($Record['NoOfWinners']) || $Record['NoOfWinners'] == 0)  ? 1 : $Record['NoOfWinners']);
                    $Records[$key]['IsSeriesMatchStarted'] = "No";

                    if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] == "Yes") {
                        $Records[$key]['IsAuctionFinalTeamSubmitted'] = "No";
                        /** to check auction user final team submitted * */
                        $this->db->select("UserTeamID");
                        $this->db->from('nba_sports_users_teams');
                        $this->db->where("ContestID", $Record['ContestID']);
                        $this->db->where("UserID", @$Where['SessionUserID']);
                        $this->db->where("IsPreTeam", "No");
                        $this->db->where("IsAssistant", "No");
                        $this->db->where("AuctionTopPlayerSubmitted", "Yes");
                        $this->db->where("UserTeamType", "Draft");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Records[$key]['IsAuctionFinalTeamSubmitted'] = "Yes";
                        }
                    }

                    /** to check series stared or not * */
                    if (isset($Where['JoinedUsers']) && $Where['JoinedUsers'] == "Yes") {
                       $AllJoinedUsers = $this->SnakeDrafts_model->getJoinedContestsUsers("FirstName,UserGUID,UserID,UserTeamCode,ProfilePic", array('ContestID' => $Record['ContestID']), TRUE);
                       $Records[$key]['JoinedUsers'] = $AllJoinedUsers['Data']['TotalRecords'];
                    }

                    /** to check series stared or not * */
                    if (isset($Where['IsSeriesStarted']) && $Where['IsSeriesStarted'] == "Yes") {
                        $this->db->select("MatchID,MatchStartDateTime");
                        $this->db->from('nba_sports_matches');
                        $this->db->where("SeriesID", $Record['SeriesID']);
                        $this->db->order_by("MatchStartDateTime", "ASC");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $MatchDetails = $Query->row_array();
                            $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                            $MatchDateTime = strtotime($MatchDetails["MatchStartDateTime"]);
                            if ($CurrentDateTime >= $MatchDateTime) {
                                $Records[$key]['IsSeriesMatchStarted'] = "Yes";
                            }
                        }
                    }

                    /**- Get Private Contests user ids-**/
                    $Records[$key]['ContestCreaterUserGUID']= ''; 
                    if ($Record['Privacy']=='Yes') {
                        $Records[$key]['ContestCreaterUserGUID'] = $this->db->query("SELECT `UserGUID` FROM `tbl_users` WHERE `UserID` = ".$Record['UserID'])->row()->UserGUID;
                    }
                }

                $Return['Data']['Records'] = $Records;
            } else {
                $Record = $Query->row_array();
                $Record['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array();
                $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                $Record['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                $Record['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                if (in_array('DraftPlayerSelectionCriteria', $Params)) {
                    $Record['DraftPlayerSelectionCriteria'] = (!empty($Record['DraftPlayerSelectionCriteria'])) ? json_decode($Record['DraftPlayerSelectionCriteria'], TRUE) : array();
                }
                return $Record;
            }
        }
        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `nba_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from nba_sports_contest_join where nba_sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `nba_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from nba_sports_contest_join where nba_sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `nba_sports_contest_join` J, `nba_sports_contest` C,tbl_entity E WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestID = E.EntityID AND E.StatusID != 3 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `nba_sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `nba_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestSize != (SELECT COUNT(*) from nba_sports_contest_join where nba_sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();
        }
        $Return['Data']['Records'] = empty($Records) ? array() : $Records;
        return $Return;
    }

    function getTotalContestCollections($ContestGUID) {
        return $this->db->query('SELECT SUM(C.EntryFee) as TotalAmountReceived FROM nba_sports_contest C join nba_sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalAmountReceived;
    }

    function getTotalWinningAmount($ContestGUID) {
        return $this->db->query('SELECT SUM(J.UserWinningAmount) as TotalWinningAmount FROM nba_sports_contest C join nba_sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalWinningAmount;
    }

    /*
      Description: Join contest
     */

    function joinContest($Input = array(), $SessionUserID, $ContestID, $SeriesID, $MatchID, $UserTeamID, $IsAutoDraft) {

        $this->db->trans_start();
        /* Add entry to join contest table . */
        $DraftUserPosition = 0;
        $this->db->select("COUNT(UserID) as Joined");
        $this->db->from("nba_sports_contest_join");
        $this->db->where("ContestID", $ContestID);
        $Query = $this->db->get();
        $Result = $Query->row_array();
        if (isset($Result['Joined'])) {
            $DraftUserPosition = $Result['Joined'] + 1;
        }
        $InsertData = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "SeriesID" => $SeriesID,
            "MatchID" => $MatchID,
            "UserTeamID" => $UserTeamID,
            "IsAutoDraft" => $IsAutoDraft,
            "DraftUserPosition" => $DraftUserPosition,
            "EntryDate" => date('Y-m-d H:i:s')
        );
        $this->db->insert('nba_sports_contest_join', $InsertData);
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
            if ($ContestEntryRemainingFees > 0 && @$Input['WalletAmount'] > 0) {
                if (@$Input['WalletAmount'] >= $ContestEntryRemainingFees) {
                    $WalletAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WalletAmountDeduction = @$Input['WalletAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WinningAmount'] > 0) {
                if (@$Input['WinningAmount'] >= $ContestEntryRemainingFees) {
                    $WinningAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WinningAmountDeduction = @$Input['WinningAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
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
        }

        $ContestsData = $this->db->query('Select C.ContestSize,C.IsAutoCreate,(SELECT COUNT(*)
                                                        FROM nba_sports_contest_join
                                                        WHERE ContestID =  C.ContestID ) AS TotalJoined from nba_sports_contest C WHERE  ContestID = ' . $ContestID . ' LIMIT 1')->result_array()[0]; 
        if ($ContestsData['TotalJoined'] >= $ContestsData['ContestSize'] && $ContestsData['IsAutoCreate']=='Yes') {
            $ContestData = $this->db->query('SELECT * FROM nba_sports_contest WHERE ContestID = ' . $ContestID . ' LIMIT 1')->row_array();
            /* Create Contest */
            $this->addContestAuto($ContestData, $ContestData['UserID'], $ContestData['SeriesID'],$ContestData['MatchID']);

        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        /* update contest round * */
        /* $this->autoShuffleRoundUpdate($ContestID); */

        return $this->Users_model->getWalletDetails($SessionUserID);
    }

    function addContestAuto($Input = array(), $SessionUserID, $SeriesID,$MatchID, $StatusID = 1) {

        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();
        $EntityGUID = get_guid();

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" =>  $Input['UserID'], "StatusID" => $StatusID, "GameSportsType" => $Input['GameType']));

        // $Input['LeagueJoinDateTime'] = date('Y-m-d',strtotime($Input['LeagueJoinDateTime'])).' '.$Input['LeagueJoinTime'];
        // if(!empty($Input['TimeZone'])){
        //     $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime($Input['TimeZone'].' minutes', 0);
        // }else{
        //     $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('+300 minutes', 0);
        // }
        
        /* Add contest to contest table . */

        $RoosterSize = basketballGetConfiguration($Input['SubGameType']);
        $RoosterArray = $this->searchForId((int) $Input['ContestSize'], $RoosterSize);
        $RoosterConfiguration = basketballGetConfigurationPlayersRooster($Input['ContestSize']);

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
            "MatchID" => @$MatchID,
            "UserInvitationCode" => random_string('alnum', 6),
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "DraftTotalRounds" => $RoosterArray['RosterSize'],
            "DraftLiveRound" => 1,
            "RosterSize" => $RoosterArray['RosterSize'],
            "PlayedRoster" => $RoosterArray['Start'],
            "BatchRoster" => (!empty($RoosterArray['Batch'])) ? $RoosterArray['Batch'] : 0
        ));
        $this->db->insert('nba_sports_contest', $InsertData);
        // echo $this->db->last_query();die;
        // print_r($InsertData); die("kamle");
        $PlayerIs = $this->addAuctionPlayer($SeriesID, $EntityID, $MatchID,@$Input['ContestDuration'],@$Input['DailyDate']);
        // echo $PlayerIs;
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
                'ContestID' => 'C.ContestID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'IsConfirm' => 'C.IsConfirm',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'WinningAmount' => 'C.WinningAmount',
                'ContestSize' => 'C.ContestSize',
                'UserTeamID' => 'JC.UserTeamID',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'EntryType' => 'C.EntryType',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'UserID' => 'JC.UserID',
                'JoinInning' => 'JC.JoinInning',
                'EntryDate' => 'JC.EntryDate',
                'TotalPoints' => 'JC.TotalPoints',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'SeriesID' => 'S.SeriesID',
                'SeriesName' => 'S.SeriesName AS SeriesName',
                'TotalJoined' => '(SELECT COUNT(*) AS TotalJoined
                                                FROM nba_sports_contest_join
                                                WHERE nba_sports_contest_join.ContestID =  C.ContestID ) AS TotalJoined',
                'UserTotalJoinedInMatch' => '(SELECT COUNT(*)
                                                FROM nba_sports_contest_join
                                                WHERE nba_sports_contest_join.MatchID =  M.MatchID AND UserID= ' . $Where['SessionUserID'] . ') AS UserTotalJoinedInMatch',
                'UserRank' => 'JC.UserRank',
                'StatusID' => 'E.StatusID',
                'Status' => 'CASE E.StatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "3" then "Cancelled"
                when "5" then "Completed"
                END as Status',
                'CurrentDateTime' => 'DATE_FORMAT(CONVERT_TZ(Now(),"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") CurrentDateTime',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }

        $this->db->select('C.ContestGUID,C.ContestName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_contest C,nba_sports_contest_join JC');
        $this->db->where("C.ContestID", "JC.ContestID", FALSE);
        $this->db->where("C.ContestID", "E.EntityID", FALSE);

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->group_start();
            $this->db->like("C.ContestName", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("C.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['SessionUserID'])) {
            $this->db->where("JC.UserID", $Where['SessionUserID']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UT.UserTeamID", $Where['UserTeamID']);
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
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
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
        // $this->db->group_by("UT.UserTeamID");
        $Query = $this->db->get();
        //echo $this->db->last_query();
        //exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                $Return['Data']['Records'] = $Query->result_array();
            } else {
                $Record = $Query->row_array();
                return $Record;
            }
        } else {
            $Return['Data']['Records'] = array();
        }

        return $Return;
    }

    /*
      Description: To get all players
     */

    function getDraftTeams($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TeamID' => 'T.TeamID TeamID',
                'TeamKey' => 'T.TeamKey TeamKey',
                'TeamName' => 'T.TeamName TeamName',
                'ByeWeek' => 'T.ByeWeek ByeWeek',
                'TeamNameShort' => 'T.TeamNameShort TeamNameShort',
                'TeamFlag' => 'T.TeamFlag TeamFlag',
                'FantasyPoints' => 'T.FantasyPoints FantasyPoints',
                'TeamStats' => 'T.TeamStats TeamStats',
                'GameSportsType' => 'E.GameSportsType GameSportsType',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'TeamStatus' => 'APBS.PlayerStatus as TeamStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'TeamPosition' => 'UTP.PlayerPosition TeamPosition',
                'AuctionDraftAssistantPriority' => 'UTP.AuctionDraftAssistantPriority',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'TeamPlayingStatus' => 'UTP.TeamPlayingStatus',
                    //'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('T.TeamID,T.TeamGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_teams T');

        /** get teams in play drafting by contest* */
        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('nba_tbl_auction_player_bid_status APBS,nba_sports_series S,nba_sports_contest C');
            $this->db->where("APBS.TeamID", "T.TeamID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
        }

        /** get teams in play drafting by creating users* */
        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP');
            $this->db->where("UTP.TeamID", "T.TeamID", FALSE);
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
            if (!empty($Where['UserID'])) {
                $this->db->where("UT.UserID", @$Where['UserID']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            if (!empty($Where['WeekID'])) {
                $this->db->where("UT.WeekID", @$Where['WeekID']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }

        $this->db->where("T.TeamID", "E.EntityID", FALSE);

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("T.TeamName", $Where['TeamName']);
            $this->db->or_like("T.TeamNameShort", $Where['TeamNameShort']);
            $this->db->or_like("T.TeamKey", $Where['TeamKey']);
            $this->db->group_end();
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("T.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['GameType'])) {
            $this->db->where("E.GameSportsType", $Where['GameType']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            /* if (empty($Where['OrderBy']) && empty($Where['Sequence'])) {
              $this->db->order_by('T.FantasyPoints', 'DESC');
              $this->db->order_by('T.TeamName', 'ASC');
              } */
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            /* if ($PageNo != 0) {
              $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize));
              } */
        } else {
            $this->db->limit(1);
        }
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
                    $Records[$key]['TeamStats'] = (!empty($Record['TeamStats'])) ? json_decode($Record['TeamStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
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
                $Records[$key]['TeamStats'] = (!empty($Record['TeamStats'])) ? json_decode($Record['TeamStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                return $Record;
            }
        }
        return FALSE;
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
                'BidSoldCredit' => '(SELECT BidCredit FROM nba_tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                //'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'PlayerStatus' => 'APBS.PlayerStatus as PlayerStatus',
                'PlayerRole' => 'APBS.PlayerRole as PlayerRole',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerPosition' => 'UTP.PlayerPosition',
                'AuctionDraftAssistantPriority' => 'UTP.AuctionDraftAssistantPriority',
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
        $this->db->from('tbl_entity E, nba_sports_players P');

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('nba_tbl_auction_player_bid_status APBS,nba_sports_series S,nba_sports_contest C');
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
            $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP');
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
            if (!empty($Where['UserID'])) {
                $this->db->where("UT.UserID", @$Where['UserID']);
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
        $this->db->where('EXISTS (select PlayerID FROM nba_sports_team_players WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . '  AND MatchID=' . @$Where['MatchID'] . ')');
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
                    //$Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('TP.PlayerID,TP.PlayerRole,TP.PlayerSalary,T.TeamNameShort,T.TeamName');
                    $this->db->from('nba_sports_team_players TP,nba_sports_teams T');
                    $this->db->where('TP.TeamID', "T.TeamID", FALSE);
                    $this->db->where('TP.PlayerID', $Record['PlayerID']);
                    $this->db->where('TP.SeriesID', @$Where['SeriesID']);
                    $this->db->where('TP.MatchID', @$Where['MatchID']);
                    $this->db->order_by("TP.PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        //$Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                        $Records[$key]['TeamNameShort'] = $PlayerDetails['0']['TeamNameShort'];
                        $Records[$key]['TeamName'] = $PlayerDetails['0']['TeamName'];
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
                //$Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('nba_sports_team_players');
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
      Description: To get all players auction
     */

    function getPlayersAuction($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
        $this->db->from('tbl_entity E, sports_players P,tbl_auction_player_bid_status ABS');

        $this->db->where("ABS.PlayerID", "P.PlayerID", FALSE);

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
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("ABS.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("ABS.ContestID", $Where['ContestID']);
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
            //$this->db->order_by('P.PlayerSalary', 'DESC');
            //$this->db->order_by('CreateDateTime', 'ASC');
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
        //echo $this->db->last_query();
        //exit;
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

    /*
      Description: ADD user team
     */

    function addUserTeam($Input = array(), $SessionUserID, $SeriesID, $MatchID, $ContestID, $StatusID = 2) {

        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $SessionUserID, "StatusID" => $StatusID,"GameSportsType" => 'Nba'));
        /* Add user team to user team table . */
        $teamName = "PreSnakeTeam 1";
        $InsertData = array(
            "UserTeamID" => $EntityID,
            "UserTeamGUID" => $EntityGUID,
            "UserID" => $SessionUserID,
            "UserTeamName" => $teamName,
            "UserTeamType" => @$Input['UserTeamType'],
            "IsPreTeam" => @$Input['IsPreTeam'],
            "SeriesID" => @$SeriesID,
            "MatchID"  => @$MatchID,
            "ContestID" => @$ContestID,
            "IsAssistant" => "Yes",
        );
        $this->db->insert('nba_sports_users_teams', $InsertData);

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();

            foreach ($Input['UserTeamPlayers'] as $Key => $Value) {
                $UserTeamPlayers[] = array(
                    'UserTeamID'    => $EntityID,
                    'SeriesID'      => @$SeriesID,
                    'MatchID'       => @$MatchID,
                    'PlayerID'      => $Value['PlayerID'],
                    'PlayerSelectTypeRole' => $Value['PlayerRoleShort'],
                    'AuctionDraftAssistantPriority' => $Key + 1,
                    'DateTime' => date('Y-m-d H:i:s')
                );
            }
            if ($UserTeamPlayers)
                $UserTeamPlayers = array_map("unserialize", array_unique(array_map("serialize", $UserTeamPlayers)));
            $this->db->insert_batch('nba_sports_users_team_players', $UserTeamPlayers);
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

    function autoDraftOnOff($Input = array(), $SessionUserID, $SeriesID, $MatchID, $ContestID, $UserTeamID) {

        $this->db->trans_start();

        /* Update Contest Status */
        $this->db->where('UserID', $SessionUserID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('MatchID', $MatchID);
        $this->db->limit(1);
        $this->db->update('nba_sports_contest_join', array('IsAutoDraft' => @$Input['IsAutoDraft']));

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

        /*
      Description: Assistant on off
     */

    function assistantTeamOnOff($Input = array(), $SessionUserID, $SeriesID, $MatchID, $ContestID, $UserTeamID) {

        $this->db->trans_start();

        /* Update Contest Status */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->where('UserID', $SessionUserID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('MatchID', $MatchID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('IsPreTeam', "Yes");
        $this->db->limit(1);
        $this->db->update('nba_sports_users_teams', array('IsAssistant' => @$Input['IsAssistant']));

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: add auction player bid
     */

    function getJoinedDraftAllTeams($ContestID, $SeriesID, $UserID,$DraftHistory='') {
        $ContestRound = $this->SnakeDrafts_model->getContests('ContestID,DraftLiveRound,DraftTotalRounds', array("LeagueType" => "Draft", "ContestID" => $ContestID, "SeriesID" => $SeriesID));
        $DraftTotalRounds = $ContestRound['DraftLiveRound'];
        $AllTeams = array();
        $AllJoinedUsers = $this->SnakeDrafts_model->getJoinedContestsUsers("FirstName,UserGUID,UserID,UserTeamCode", array('ContestID' => $ContestID, 'SeriesID' => $SeriesID), TRUE);
        if ($AllJoinedUsers['Data']['TotalRecords'] > 0) {
            for ($i = 1; $i <= $DraftTotalRounds; $i++) {
                $Temp = array();
                foreach ($AllJoinedUsers['Data']['Records'] as $Rows) {
                    $this->db->select('UT.UserID,UT.UserTeamID,UTP.PlayerID,UTP.DateTime,T.PlayerGUID,
                        T.PlayerName,T.PlayerPic,T.PlayerRole,UTP.PlayerSelectTypeRole,
                        (CASE T.PlayerRole
                             when "ShootingGuard" then "SG"
                             when "Center" then "C"
                             when "PowerForward" then "PF"
                             when "PointGuard" then "PG"
                             when "SmallForward" then "SF"
                             END ) as PlayerRoleShort');
                    $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP, nba_sports_players T');
                    $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                    $this->db->where("T.PlayerID", "UTP.PlayerID", FALSE);
                    $this->db->where("UT.IsAssistant", "No");
                    $this->db->where("UT.IsPreTeam", "No");
                    $this->db->where("UT.ContestID", $ContestID);
                    $this->db->where("UT.SeriesID", $SeriesID);
                    $this->db->where("UT.UserID", $Rows['UserID']);
                    $this->db->where("UTP.DraftRound", $i);
                    $this->db->order_by("UTP.DateTime", "ASC");
                    $this->db->limit(1);
                    //$this->db->offset($i - 1);
                    $Query = $this->db->get();
                    $PlayersAssistant = $Query->row_array();
                    if ($Query->num_rows() > 0) {
                        $PlayersAssistant['UserGUID'] = $Rows['UserGUID'];
                        $PlayersAssistant['UserName'] = $Rows['FirstName'];
                        $PlayersAssistant['UserTeamCode'] = $Rows['UserTeamCode'];
                        $Temp[] = $PlayersAssistant;
                    } else {
                        $PlayersAssistant['UserGUID'] = $Rows['UserGUID'];
                        $PlayersAssistant['UserName'] = $Rows['FirstName'];
                        $PlayersAssistant['UserTeamCode'] = $Rows['UserTeamCode'];
                        $Temp[] = $PlayersAssistant;
                    }
                }

                if (!empty($DraftHistory) && $DraftHistory == 'Yes') {
                    if ($i%2 == 0) {
                        $Temp = array_reverse($Temp);
                    }
                }
                $AllTeams['Rounds ' . $i] = $Temp;
            }
        }
        return $AllTeams;
    }

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
        $this->db->from('nba_tbl_auction_player_bid');
        $this->db->where("PlayerID", $PlayerID);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->limit(1);
        $this->db->order_by("DateTime", "DESC");
        $this->db->order_by("BidCredit", "DESC");
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
        /* $AuctionGames = $this->getContests('ContestID,AuctionBreakDateTime,AuctionStatus,SeriesID,AuctionTimeBreakAvailable,AuctionIsBreakTimeStatus', array('AuctionStatusID' => 2, 'ContestID' => $ContestID), FALSE);
          if (empty($AuctionGames)) {
          $Return["Message"] = "Auction not stared.";
          $Return["Status"] = 0;
          return $Return;
          } */

        /** to check user available budget * */
        $this->db->select("AuctionBudget");
        $this->db->from('nba_sports_contest_join');
        $this->db->where("AuctionBudget >=", $Input['BidCredit']);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("UserID", $SessionUserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            /** To check player in assistant * */
//            $BidUserID = "";
//            $BidUserCredit = "";
//            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID");
//            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
//            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
//            $this->db->where("UT.IsAssistant", "Yes");
//            $this->db->where("UT.IsPreTeam", "Yes");
//            $this->db->where("UTP.BidCredit >", $Input['BidCredit']);
//            $this->db->where("UT.ContestID", $ContestID);
//            $this->db->where("UT.SeriesID", $SeriesID);
//            $this->db->where("UTP.PlayerID", $PlayerID);
//            $Query = $this->db->get();
//            $PlayersAssistant = $Query->result_array();
//            $Rows = $Query->num_rows();
//            if ($Rows > 0) {
//                /** To check assistant player single * */
//                if ($Rows == 1) {
//
//                    $CurrentBidCredit = $Input['BidCredit'];
//                    $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
//                    if ($AssistantBidCredit > $CurrentBidCredit) {
//                        if (100000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 100000;
//                        } else if (1000000 >= $CurrentBidCredit || $CurrentBidCredit < 10000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 1000000;
//                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 100000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 10000000;
//                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 100000000;
//                        }
//                    }
//                    $BidUserID = $PlayersAssistant[0]['UserID'];
//                    $BidUserCredit = $CurrentBidCredit;
//
//                    /** to check user available budget * */
//                    $this->db->select("AuctionBudget");
//                    $this->db->from('sports_contest_join');
//                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
//                    $this->db->where("ContestID", $ContestID);
//                    $this->db->where("SeriesID", $SeriesID);
//                    $this->db->where("UserID", $PlayersAssistant[0]['UserID']);
//                    $Query = $this->db->get();
//                    if ($Query->num_rows() > 0) {
//                        /* add player bid */
//                        $InsertData = array(
//                            "SeriesID" => $SeriesID,
//                            "ContestID" => $ContestID,
//                            "UserID" => $PlayersAssistant[0]['UserID'],
//                            "PlayerID" => $PlayerID,
//                            "BidCredit" => $CurrentBidCredit,
//                            "DateTime" => date('Y-m-d H:i:s')
//                        );
//                        $this->db->insert('tbl_auction_player_bid', $InsertData);
//                    } else {
//                        $Return["Message"] = "You have not insufficient budget";
//                        $Return["Status"] = 0;
//                        return $Return;
//                    }
//                } else if ($Rows > 1) {
//                    /** get second highest user* */
//                    $SecondUser = $this->get_max($PlayersAssistant, 1);
//                    if (empty($SecondUser)) {
//                        $SecondUser = $PlayersAssistant[0];
//                    }
//                    $CurrentBidCredit = $AssistantBidCredit = $SecondUser['BidCredit'];
//                    if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 100000;
//                    } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 1000000;
//                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 10000000;
//                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 100000000;
//                    }
//                    /** get top user* */
//                    $TopUser = $this->get_max($PlayersAssistant, 0);
//                    $TopUserBidCredit = $TopUser['BidCredit'];
//                    if ($CurrentBidCredit > $TopUserBidCredit) {
//                        $CurrentBidCredit = $TopUserBidCredit;
//                    }
//                    $BidUserID = $TopUser['UserID'];
//                    $BidUserCredit = $CurrentBidCredit;
//
//                    /** to check user available budget * */
//                    $this->db->select("AuctionBudget");
//                    $this->db->from('sports_contest_join');
//                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
//                    $this->db->where("ContestID", $ContestID);
//                    $this->db->where("SeriesID", $SeriesID);
//                    $this->db->where("UserID", $TopUser['UserID']);
//                    $Query = $this->db->get();
//                    if ($Query->num_rows() > 0) {
//                        /* add player bid */
//                        $InsertData = array(
//                            "SeriesID" => $SeriesID,
//                            "ContestID" => $ContestID,
//                            "UserID" => $TopUser['UserID'],
//                            "PlayerID" => $PlayerID,
//                            "BidCredit" => $CurrentBidCredit,
//                            "DateTime" => date('Y-m-d H:i:s')
//                        );
//                        $this->db->insert('tbl_auction_player_bid', $InsertData);
//                    } else {
//                        $Return["Message"] = "You have not insufficient budget";
//                        $Return["Status"] = 0;
//                        return $Return;
//                    }
//                }
//            } else {
//                $BidUserID = $SessionUserID;
//                $BidUserCredit = $Input['BidCredit'];
//                /* add player bid */
//                $InsertData = array(
//                    "SeriesID" => $SeriesID,
//                    "ContestID" => $ContestID,
//                    "UserID" => $SessionUserID,
//                    "PlayerID" => $PlayerID,
//                    "BidCredit" => @$Input['BidCredit'],
//                    "DateTime" => date('Y-m-d H:i:s')
//                );
//                $this->db->insert('tbl_auction_player_bid', $InsertData);
//            }

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
            $this->db->insert('nba_tbl_auction_player_bid', $InsertData);

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
                $PlayerInLive = $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Live', 'OrderBy' => "PlayerID", "Sequence" => "ASC")));
                if (!empty($playersData)) {
                    $Players[] = $playersData;
                } else {
                    /** get upcoming player * */
                    $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Upcoming', 'OrderBy' => "PlayerID", "Sequence" => "ASC")));
                    if (!empty($playersData)) {
                        $Players[] = $playersData;
                    }
                }
                if (!empty($Players)) {
                    foreach ($Players as $key => $Player) {
                        $Players[$key]['PreAssistant'] = "No";
                        if (empty($PlayerInLive)) {
                            $Players[$key]['AuctionTimeBreakAvailable'] = $Auction['AuctionTimeBreakAvailable'];
                        } else {
                            $Players[$key]['AuctionTimeBreakAvailable'] = "No";
                        }

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
                        $this->db->select("PlayerID,SeriesID,ContestID,BidCredit,DateTime,UserID");
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

                            /** check current player in assistant * */
                            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,U.UserGUID,UTP.DateTime");
                            $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_users U');
                            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                            $this->db->where("U.UserID", "UT.UserID", FALSE);
                            $this->db->where("UT.IsAssistant", "Yes");
                            $this->db->where("UT.IsPreTeam", "Yes");
                            $this->db->where("UT.ContestID", $Player['ContestID']);
                            $this->db->where("UT.SeriesID", $Player['SeriesID']);
                            $this->db->where("UTP.PlayerID", $Player['PlayerID']);
                            $this->db->where("UTP.BidCredit >", $PlayerDetails[0]['BidCredit']);
                            $this->db->order_by("UTP.BidCredit", "DESC");
                            $this->db->limit(2);
                            $Query = $this->db->get();
                            $Rows = $Query->num_rows();
                            if ($Rows > 0) {
                                if ($Rows > 1) {
                                    /** get second highest user* */
                                    $PlayersAssistant = $Query->result_array();
                                    //print_r($PlayersAssistant);exit;
                                    $UserID = 0;
                                    $UserGUID = 0;
                                    $BidCredit = array_column($PlayersAssistant, 'BidCredit', "UserGUID");
                                    $AssistantDateTime = array_column($PlayersAssistant, 'DateTime', "UserGUID");
                                    $UserIDGUID = array_column($PlayersAssistant, 'UserID', "UserGUID");
                                    $MoreThenSamePlayer = array_count_values($BidCredit);
                                    array_filter($MoreThenSamePlayer, function($n) {
                                        return $n > 1;
                                    });
                                    if (!empty($MoreThenSamePlayer)) {
                                        $UserGUID = array_search(min($AssistantDateTime), $AssistantDateTime);
                                        $UserID = $UserIDGUID[array_search(min($AssistantDateTime), $AssistantDateTime)];

                                        $CurrentBidCreditNew = $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
                                        if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000;
                                        } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 1000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 10000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000000;
                                        }
                                        if ($CurrentBidCreditNew > $PlayersAssistant[0]['BidCredit']) {
                                            $CurrentBidCreditNew = $PlayersAssistant[0]['BidCredit'];
                                        }
                                    } else {
                                        $SecondUser = $this->get_max($PlayersAssistant, 1);
                                        if (empty($SecondUser)) {
                                            $SecondUser = $PlayersAssistant[0];
                                        }
                                        $CurrentBidCreditNew = $AssistantBidCredit = $SecondUser['BidCredit'];
                                        if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000;
                                        } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 1000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 10000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000000;
                                        }
                                        /** get top user* */
                                        $TopUser = $this->get_max($PlayersAssistant, 0);
                                        $TopUserBidCredit = $TopUser['BidCredit'];
                                        if ($CurrentBidCreditNew > $TopUserBidCredit) {
                                            $CurrentBidCreditNew = $TopUserBidCredit;
                                        }
                                        $UserID = $TopUser['UserID'];
                                        $UserGUID = $TopUser['UserGUID'];
                                    }
                                    /** to check user available budget * */
                                    $this->db->select("AuctionBudget");
                                    $this->db->from('sports_contest_join');
                                    $this->db->where("AuctionBudget >=", $CurrentBidCreditNew);
                                    $this->db->where("ContestID", $Player['ContestID']);
                                    $this->db->where("SeriesID", $Player['SeriesID']);
                                    $this->db->where("UserID", $UserID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        /* add player bid */
                                        $Players[$key]['UserGUID'] = $UserGUID;
                                        $Players[$key]['BidCredit'] = $CurrentBidCreditNew;
                                        $Players[$key]['PreAssistant'] = "Yes";
                                    } else {
                                        $Players[$key]['PreAssistant'] = "No";
                                    }
                                } else {
                                    $PlayersAssistantOnBId = $Query->row_array();
                                    $Players[$key]['UserGUID'] = $PlayersAssistantOnBId["UserGUID"];
                                    if ($PlayersAssistantOnBId["UserID"] != $PlayerDetails[0]['UserID']) {
                                        $CurrentBidCredit = $PlayerDetails[0]['BidCredit'];
                                        $AssistantBidCredit = $PlayersAssistantOnBId['BidCredit'];
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
                                        if ($AssistantBidCredit >= $CurrentBidCredit) {
                                            $Players[$key]['BidCredit'] = $CurrentBidCredit;

                                            /** to check user available budget * */
                                            $this->db->select("AuctionBudget");
                                            $this->db->from('sports_contest_join');
                                            $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                                            $this->db->where("ContestID", $Player['ContestID']);
                                            $this->db->where("SeriesID", $Player['SeriesID']);
                                            $this->db->where("UserID", $PlayersAssistantOnBId['UserID']);
                                            $Query = $this->db->get();
                                            if ($Query->num_rows() > 0) {
                                                $Players[$key]['PreAssistant'] = "Yes";
                                            } else {
                                                $Players[$key]['PreAssistant'] = "No";
                                            }
                                        } else {
                                            $Players[$key]['PreAssistant'] = "No";
                                        }
                                    } else {
                                        $Players[$key]['PreAssistant'] = "No";
                                    }
                                }
                            }
                        } else {

                            /** check current player in assistant * */
                            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,U.UserGUID");
                            $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_users U');
                            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                            $this->db->where("U.UserID", "UT.UserID", FALSE);
                            $this->db->where("UT.IsAssistant", "Yes");
                            $this->db->where("UT.IsPreTeam", "Yes");
                            $this->db->where("UT.ContestID", $Player['ContestID']);
                            $this->db->where("UT.SeriesID", $Player['SeriesID']);
                            $this->db->where("UTP.PlayerID", $Player['PlayerID']);
                            $this->db->order_by("UTP.DateTime", "DESC");
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $PlayersAssistantOnBId = $Query->row_array();
                                $Players[$key]['UserGUID'] = $PlayersAssistantOnBId["UserGUID"];
                                $Players[$key]['BidCredit'] = 100000;
                                /** to check user available budget * */
                                $this->db->select("AuctionBudget");
                                $this->db->from('sports_contest_join');
                                $this->db->where("AuctionBudget >=", 100000);
                                $this->db->where("ContestID", $Player['ContestID']);
                                $this->db->where("SeriesID", $Player['SeriesID']);
                                $this->db->where("UserID", $PlayersAssistantOnBId['UserID']);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Players[$key]['PreAssistant'] = "Yes";
                                } else {
                                    $Players[$key]['PreAssistant'] = "No";
                                }
                            } else {
                                $Players[$key]['PreAssistant'] = "No";
                            }

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

    function editUserTeam($Input = array(), $UserTeamID, $MatchID) {

        $this->db->trans_start();

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('nba_sports_users_teams', array('UserTeamName' => $Input['UserTeamName'], 'UserTeamType' => $Input['UserTeamType']));

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get SeriesID */
            if (empty($Input['SeriesID'])) {
               $Input['SeriesID'] = $this->db->query('SELECT SeriesID FROM nba_sports_users_teams WHERE UserTeamID = ' . $UserTeamID . ' LIMIT 1')->row()->SeriesID;
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Key => $Value) {
                $UserTeamPlayers[] = array(
                    'UserTeamID' => $UserTeamID,
                    'SeriesID' => $Input['SeriesID'],
                    'MatchID' => $MatchID,
                    'PlayerID'      => $Value['PlayerID'],
                    'PlayerSelectTypeRole' => $Value['PlayerRoleShort'],
                    'AuctionDraftAssistantPriority' => $Key + 1,
                    'DateTime' => date('Y-m-d H:i:s'),
                );
            }
            if ($UserTeamPlayers)
                $UserTeamPlayers = array_map("unserialize", array_unique(array_map("serialize", $UserTeamPlayers)));
            /* Delete Team Players */
            $this->db->delete('nba_sports_users_team_players', array('UserTeamID' => $UserTeamID));
            /* INsert new player */
            $this->db->insert_batch('nba_sports_users_team_players', $UserTeamPlayers);
        }else {
            $this->db->delete('tbl_entity', array('EntityID' => $UserTeamID));
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
        $this->db->from('tbl_entity E, nba_sports_users_teams UT');
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
                SELECT COUNT(*) AS `NormalContest` FROM `nba_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal"
                )as NormalContest,
                (
                SELECT COUNT(*) AS `ReverseContest` FROM `nba_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse"
                )as ReverseContest,
                (
                SELECT COUNT(*) AS `JoinedContest` FROM `nba_sports_contest_join` J, `nba_sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '"
                )as JoinedContest,
                ( 
                SELECT COUNT(*) AS `TotalTeams` FROM `nba_sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '" 
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
                'PlayerID' => 'P.PlayerID',
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
        $this->db->from('nba_sports_users_team_players UTP, nba_sports_players P, nba_sports_team_players TP,nba_sports_teams T,nba_sports_matches M,nba_sports_set_match_types SM');
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        $this->db->where("UTP.PlayerID", "TP.PlayerID", FALSE);
        $this->db->where("T.TeamID", "TP.TeamID", FALSE);
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

    function getUserTeamPlayersAuction($Field = '', $Where = array()) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerPosition' => 'UTP.PlayerPosition',
                'Points' => 'UTP.Points',
                'PlayerName' => 'P.PlayerName',
                'PlayerID' => 'P.PlayerID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'TotalPoints' => 'SUM(UTP.Points) TotalPoints'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_users_team_players UTP, sports_players P');
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->like("P.PlayerName", $Where['Keyword']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UTP.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['PlayerPosition'])) {
            $this->db->where("UTP.PlayerPosition", $Where['PlayerPosition']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UTP.Points', 'DESC');
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Players = $Query->result_array();
            foreach ($Players as $Key => $Player) {
                $Players[$Key]['PlayerRole'] = "";
                $this->db->select("BS.PlayerRole,BS.PlayerID");
                $this->db->from('tbl_auction_player_bid_status BS');
                $this->db->where("BS.ContestID", $Where["ContestID"]);
                $this->db->where("BS.PlayerID", $Player["PlayerID"]);
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Role = $Query->row_array();
                    $Players[$Key]['PlayerRole'] = $Role['PlayerRole'];
                }
            }
            return $Players;
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
                'DraftUserLive' => 'JC.DraftUserLive',
                'TotalPointsSeason' => 'JC.TotalPoints TotalPointsSeason',
                'WeekTotalPoints' => '(SELECT TotalPoints FROM nba_sports_users_teams WHERE nba_sports_users_teams.UserID=JC.UserID AND nba_sports_users_teams.ContestID=JC.ContestID) WeekTotalPoints',
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
        $this->db->from('nba_sports_contest_join JC, tbl_users U');
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
        // echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    if (!empty($record['UserTeamID'])) {
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = array();
                    } else {
                        $UserTeamPlayers =$this->getPlayersMyTeam('PlayerRoleShort,TeamName,UserTeamGUID,PlayerID,UserTeamID,TotalPoints,PlayerPic,PointsDataPrivate,PointsData', array('ContestID' => $Where['ContestID'],'SessionUserID' => $record['UserID'],'MySquadPlayer'=>'Yes','IsPreTeam'=>'No','PlayerBidStatus'=>'Yes'), true, 0);
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = (!empty($UserTeamPlayers)) ? $UserTeamPlayers['Data']['Records'] : array();
                    }
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
      Description: To get joined contest users
     */

    function getJoinedContestsUsersWithTeam($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
                foreach ($Return['Data']['Records'] as $key => $record) {
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = array();

                        $UserTeamPlayers =$this->getPlayersMyTeam('PlayerRoleShort,TeamName,UserTeamGUID,PlayerID,UserTeamID,TotalPoints,PlayerPic,PointsDataPrivate', array('ContestID' => $Where['ContestID'],'SessionUserID' => $record['UserID'],'MySquadPlayer'=>'Yes','IsPreTeam'=>'No','PlayerBidStatus'=>'Yes'), true, 0);
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = (!empty($UserTeamPlayers)) ? $UserTeamPlayers['Data']['Records'] : array();

                        $this->db->select('UserTeamID,UserTeamGUID');
                        $this->db->from('sports_users_teams');
                        $this->db->where("UserID", $record['UserID']);
                        $this->db->where("ContestID", $Where['ContestID']);
                        $this->db->where("WeekID", $Where['WeekID']);
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Points = $Query->row_array();
                            $Return['Data']['Records'][$key]['UserTeamID'] = $Points['UserTeamID'];
                            $Return['Data']['Records'][$key]['UserTeamGUID'] = $Points['UserTeamGUID'];
                        }
                    
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
      Description: To get joined contest users
     */

    function contestUserLeaderboard($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                 'TotalPointsSeason' => '(SELECT TotalPoints FROM nba_sports_contest_join WHERE nba_sports_contest_join.UserID=UTW.UserID AND nba_sports_contest_join.ContestID=UTW.ContestID) TotalPointsSeason',
                'TotalPoints' => 'UTW.TotalPoints TotalPoints',
                'WeekTotalPoints' => 'UTW.TotalPoints WeekTotalPoints',
                'UserRank' => 'UTW.Rank UserRank',
                'FirstName' => 'U.FirstName',
                'WeekID' => 'UTW.WeekID',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'Username' => 'U.Username',
                'UserTeamCode' => 'U.UserTeamCode',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'UserTeamName' => 'UTW.UserTeamName',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID,UTW.UserTeamID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_users_teams_weekly UTW, tbl_users U');
        $this->db->where("UTW.UserID", "U.UserID", FALSE);
        if (!empty($Where['ContestID'])) {
            $this->db->where("UTW.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("UTW.UserID", $Where['UserID']);
        }
        if (!empty($Where['WeekID'])) {
            $this->db->where("UTW.WeekID", $Where['WeekID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("UTW.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            if (!empty($Where['SessionUserID'])) {
                $this->db->order_by('UTW.UserID=' . $Where['SessionUserID'] . ' DESC', null, FALSE);
            }
            $this->db->order_by('UTW.Rank', 'ASC');
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

                    $this->db->select("P.PlayerName,T.TeamName,UTPW.Points as TotalPoints,UTPW.PlayerSelectTypeRole,
                        IF(P.PlayerPic IS NULL,CONCAT('" . BASE_URL . "','uploads/PlayerPic/','player.png'),CONCAT('" . BASE_URL . "','uploads/PlayerPic/',P.PlayerPic)) AS PlayerPic");
                    $this->db->from('sports_users_team_players_weekly UTPW, sports_players P,sports_teams T');
                    $this->db->where("UTPW.PlayerID", "P.PlayerID", FALSE);
                    $this->db->where("P.TeamID", "T.TeamID", FALSE);
                    $this->db->where("UTPW.UserTeamID", $record["UserTeamID"]);
                    $UserTeamPlayers = $this->db->get()->result_array();
                    $Return['Data']['Records'][$key]['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();

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
      Description: To Auto Cancel Contest
     */

    function autoCancelContest() {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,IsPaid,EntryFee,LeagueJoinDateTime,MinimumUserJoined', array('AuctionStatusID' => 1, "LeagueJoinDateTime" => $LeagueJoinDateTime, "ContestFull" => "No"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {

            foreach ($ContestsUsers['Data']['Records'] as $Value) {

                $IsCancelled = (($Value['TotalJoined'] < $Value['MinimumUserJoined']) ? 1 : 0);
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

                    send_mail(array(
                        'emailTo' => $Rows['Email'],
                        'template_id' => 'd-9683d71dcf0546bdb255e4edaffa09ba',
                        'Subject' => SITE_NAME . " Contest Cancelled",
                        "Name" => $Rows['FirstName'],
                        "ContestName" => $Value['ContestName'],
                        "EmailText" => $Value['LeagueJoinDateTime']
                    ));
                }
            }
        }

        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,IsPaid,EntryFee', array('AuctionStatusID' => 1, "LeagueJoinDateTime" => $LeagueJoinDateTime, "IsRandomDraft" => "No"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {
            foreach ($ContestsUsers['Data']['Records'] as $Rows) {
                /* update contest round * */
                $this->autoShuffleRoundUpdate($Rows['ContestID']);
            }
        }
    }

    /*
      Description: To Auto suffle round update
     */

    function autoShuffleRoundUpdate($ContestID) {
        $this->db->select("J.ContestID,J.UserID,J.DraftUserPosition");
        $this->db->from('sports_contest_join J');
        $this->db->where("J.ContestID", $ContestID);
        $this->db->order_by("RAND()");
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $Users = $Query->result_array();
            shuffle($Users);
            $i = 1;
            foreach ($Users as $User) {
                /* Update auction Status */
                $this->db->where('ContestID', $User['ContestID']);
                $this->db->where('UserID', $User['UserID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', array('DraftUserPosition' => $i));
                $i++;
            }
            $this->db->where('ContestID', $User['ContestID']);
            $this->db->limit(1);
            $this->db->update('sports_contest', array('IsRandomDraft' => "Yes"));
        }
        return true;
    }

    /*
      Description: To Auto Draft Team Submit
     */

    function autoDraftTeamSubmit() {

        ini_set('max_execution_time', 300);
        /* Get Contest Data */

        $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
        $ContestsUsers = $this->getContests('SeriesID,GameType,SubGameType,SubGameTypeKey,ContestID,WeekStart,WeekEnd,RosterSize,PlayedRoster,BatchRoster,LeagueJoinDateTimeUTC,LeagueJoinDateTime', array('AuctionStatusID' => 1, "IsReminderMailSent" => "No"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {
            foreach ($ContestsUsers['Data']['Records'] as $Value) {
                $LeagueJoinDateTime = strtotime(date('Y-m-d H:i:s', strtotime('-1 day', strtotime($Value['LeagueJoinDateTimeUTC']))));
                if ($LeagueJoinDateTime <= $CurrentDateTime) {

                    /** joined contest users * */
                    $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID']), TRUE, 0);
                    if (!$JoinedContestsUsers)
                        continue;

                    foreach ($JoinedContestsUsers['Data']['Records'] as $Rows) {

                        /* Send Mail To Users */
                        send_mail(array(
                            'emailTo' => $Rows['Email'],
                            'template_id' => 'd-6d0d7c5c59704ec5a88a2b1cb70454d3',
                            'Subject' => SITE_NAME . " Contest Reminder",
                            "Name" => $Rows['FirstName'],
                            "ContestName" => $Value['ContestName'],
                            "EmailText" => $Value['LeagueJoinDateTime'],
                            "Message" => "This is a friendly reminder about your fantasy league draft on " . SITE_HOST . " We look forward to seeing you in the draft room. If you cannot attend live pre-rank your teams/schools in advance for best results!"
                        ));
                    }
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsReminderMailSent' => "Yes"));
                }
            }
        }


        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $ContestsUsers = $this->getContests('SeriesID,GameType,SubGameType,SubGameTypeKey,ContestID,WeekStart,WeekEnd,RosterSize,PlayedRoster,BatchRoster', array('AuctionStatusID' => 5), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            exit;
        }
        foreach ($ContestsUsers['Data']['Records'] as $Value) {
            $GameType = $Value['GameType'];
            $SubGameTypeKey = $Value['SubGameTypeKey'];
            $WeekStart = $Value['WeekStart'];
            $WeekEnd = $Value['WeekEnd'];

            for ($i = $WeekStart; $i <= $WeekEnd; $i++) {
                /** to check sports game week running or completed * */
                $this->db->select('M.MatchID,M.WeekID,M.MatchStartDateTime,CONVERT_TZ(M.MatchStartDateTime,"+00:00","+04:00") AS MatchDateUTC');
                $this->db->from('sports_matches M,tbl_entity E');
                $this->db->where("E.EntityID", "M.MatchID", FALSE);
                $this->db->where("M.WeekID", $i);
                $this->db->where("M.SeriesID", $Value['SeriesID']);
                $this->db->where("E.GameSportsType", $GameType);
                $this->db->order_by("M.MatchStartDateTime", "ASC");
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $MatchDetails = $Query->row_array();

                    $CurrentDate = strtotime(date('Y-m-d H:i:s'));
                    $MatchDateUTC = strtotime($MatchDetails['MatchDateUTC']) - 3600;
                    if ($CurrentDate < $MatchDateUTC) {
                        if ($i != $WeekStart) {
                            $this->db->select('M.MatchID,M.WeekID,M.MatchStartDateTime,CONVERT_TZ(M.MatchStartDateTime,"+00:00","+04:00") AS MatchDateUTC');
                            $this->db->from('sports_matches M,tbl_entity E');
                            $this->db->where("E.EntityID", "M.MatchID", FALSE);
                            $this->db->where("M.WeekID", $i - 1);
                            $this->db->where("M.SeriesID", $Value['SeriesID']);
                            $this->db->where("E.GameSportsType", $GameType);
                            $this->db->where("E.StatusID", 5);
                            $this->db->order_by("M.MatchStartDateTime", "DESC");
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() == 0) {
                                break;
                            }
                        }
                        $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID']), TRUE, 0);

                        if (!empty($JoinedContestsUsers)) {
                            foreach ($JoinedContestsUsers['Data']['Records'] as $Rows) {
                                /** to check week team submit or not* */
                                $this->db->select('T.UserTeamID');
                                $this->db->from('sports_users_teams T');
                                $this->db->where("T.UserID", $Rows['UserID']);
                                $this->db->where("T.WeekID", $i);
                                $this->db->where("T.ContestID", $Value['ContestID']);
                                $this->db->where("T.IsPreTeam", "No");
                                $this->db->where("T.IsAssistant", "No");
                                $this->db->where("T.AuctionTopPlayerSubmitted", "Yes");
                                $this->db->limit(1);
                                $Query = $this->db->get();

                                if ($Query->num_rows() == 0) {

                                    /** to get week draft team * */
                                    $this->db->select('T.UserTeamID,WeekID');
                                    $this->db->from('sports_users_teams T');
                                    $this->db->where("T.UserID", $Rows['UserID']);
                                    $this->db->where("T.ContestID", $Value['ContestID']);
                                    $this->db->where("T.IsPreTeam", "No");
                                    $this->db->where("T.IsAssistant", "No");
                                    $this->db->order_by("T.UserTeamID", "DESC");
                                    $this->db->limit(1);
                                    $Query = $this->db->get();

                                    if ($Query->num_rows() > 0) {
                                        $UserTeam = $Query->row_array();

                                        /** to get week draft team * */
                                        $this->db->select('UserTeamID,TeamID,PlayerPosition,SeriesID,TeamPlayingStatus');
                                        $this->db->from('sports_users_team_players');
                                        $this->db->where("UserTeamID", $UserTeam['UserTeamID']);
                                        $this->db->order_by("DateTime", "ASC");
                                        $Query = $this->db->get();
                                        if ($Query->num_rows() > 0) {
                                            $UserTeamPlayer = $Query->result_array();
                                            $EntityID = $UserTeam['UserTeamID'];
                                            if ($UserTeam['WeekID'] != $i) {
                                                $EntityGUID = get_guid();
                                                $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $Rows['UserID'], "StatusID" => 2));
                                                $TeamName = "PostSnakeTeam " . $i;
                                                $InsertData = array(
                                                    "UserTeamID" => $EntityID,
                                                    "UserTeamGUID" => $EntityGUID,
                                                    "UserID" => $Rows['UserID'],
                                                    "UserTeamName" => $TeamName,
                                                    "UserTeamType" => "Draft",
                                                    "IsPreTeam" => "No",
                                                    "SeriesID" => $Value['SeriesID'],
                                                    "ContestID" => $Value['ContestID'],
                                                    "WeekID" => $i,
                                                    "IsAssistant" => "No",
                                                    "AuctionTopPlayerSubmitted" => "Yes",
                                                );
                                                $this->db->insert('sports_users_teams', $InsertData);
                                            } else {
                                                $UpdateData = array(
                                                    "IsPreTeam" => "No",
                                                    "IsAssistant" => "No",
                                                    "AuctionTopPlayerSubmitted" => "Yes"
                                                );
                                                $this->db->where('UserTeamID', $EntityID);
                                                $this->db->limit(1);
                                                $this->db->update('sports_users_teams', $UpdateData);

                                                /* Delete Team Players */
                                                $this->db->delete('sports_users_team_players', array('UserTeamID' => $EntityID));
                                            }

                                            $PlayedRoster = $Value['PlayedRoster'];
                                            $BenchRoster = $Value['BatchRoster'];
                                            foreach ($UserTeamPlayer as $Key => $Player) {
                                                if ($UserTeam['WeekID'] != $i) {
                                                    $TeamPlayingStatus = $Player['TeamPlayingStatus'];
                                                } else {
                                                    $TeamPlayingStatus = ($Key + 1 <= $PlayedRoster) ? "Play" : "Bench";
                                                }

                                                $InsertData = array(
                                                    "UserTeamID" => $EntityID,
                                                    "TeamID" => $Player['TeamID'],
                                                    "TeamPlayingStatus" => $TeamPlayingStatus,
                                                    "SeriesID" => $Player['SeriesID'],
                                                    "DateTime" => date('Y-m-d H:i:s')
                                                );
                                                $this->db->insert('sports_users_team_players', $InsertData);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    }

    /*
      Description: To User Leave Draft Room
     */

    function leaveDraftRoom($ContestID, $UserID) {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm,IsPaid,EntryFee', array('AuctionStatusID' => 1, 'ContestID' => $ContestID, "ContestFull" => "No"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            exit;
        }

        foreach ($ContestsUsers['Data']['Records'] as $Value) {

            $IsCancelled = (($Value['TotalJoined'] != $Value['ContestSize']) ? 1 : 0);
            if ($IsCancelled == 0)
                continue;


            /* Get Joined Contest */
            $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID'], "UserID" => $UserID), TRUE, 0);
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
                    $MinimumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "LeaveContestCharge" AND StatusID = 2 LIMIT 1');
                    $ConfigTypeValue = $MinimumFirstTimeDepositLimit->row()->ConfigTypeValue;
                    if (!empty($ConfigTypeValue)) {
                        $WalletAmount = $WalletDetails['WalletAmount'] - round($WalletDetails['WalletAmount'] * $ConfigTypeValue / 100, 2);
                    } else {
                        $WalletAmount = $WalletDetails['WalletAmount'];
                    }

                    $InsertData = array(
                        "Amount" => $WalletAmount + $WalletDetails['WinningAmount'] + $WalletDetails['WinningAmount'],
                        "WalletAmount" => $WalletAmount,
                        "WinningAmount" => $WalletDetails['WinningAmount'],
                        "CashBonus" => $WalletDetails['CashBonus'],
                        "TransactionType" => 'Cr',
                        "EntityID" => $Value['ContestID'],
                        "Narration" => 'Cancel Contest',
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->Users_model->addToWallet($InsertData, $Rows['UserID'], 5);
                }

                /* Delete Team Players */
                $this->db->delete('nba_sports_contest_join', array('UserID' => $UserID, 'ContestID' => $ContestID));

                /* Send Mail To Users */
                /* $EmailArr = array(
                  "Name" => $Value['FirstName'],
                  "SeriesName" => @$Input['SeriesName'],
                  "ContestName" => @$Input['ContestName'],
                  "MatchNo" => @$Input['MatchNo'],
                  "TeamNameLocal" => @$Input['TeamNameLocal'],
                  "TeamNameVisitor" => @$Input['TeamNameVisitor']
                  );
                  sendMail(array(
                  'emailTo' => $Value['Email'],
                  'emailSubject' => "Cancel Contest- " . SITE_NAME,
                  'emailMessage' => emailTemplate($this->load->view('emailer/cancel_contest', $EmailArr, TRUE))
                  )); */
            }
        }
    }

    /*
      Description: To Cancel Contest
     */

    function cancelContest($Input = array(), $SessionUserID, $ContestID) {

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
                //'DateTime' => 'JC.DateTime',
                'DateTime' => 'DATE_FORMAT(CONVERT_TZ(DateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") DateTime',
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
        if (!empty($Where['PlayerID'])) {
            $this->db->where("JC.PlayerID", $Where['PlayerID']);
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
        if (isset($Contests['Data']['Records']) && !empty($Contests['Data']['Records'])) {
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
                        "AuctionUpdateTime" => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 3600)
                    );
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', $UpdateData);
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query()), JSON_UNESCAPED_UNICODE)));
        }
        return true;
    }

    /*
      Description: Update user status.
     */

    function changeUserStatus($Input = array(), $UserID, $ContestID) {

        /* Add contest to contest table . */

        /* $this->db->select("UserID");
          $this->db->from('sports_contest_join');
          $this->db->where("ContestID", $ContestID);
          $this->db->where("UserID", $UserID);
          $this->db->where("DraftUserLive", "Yes");
          $this->db->limit(1);
          $Query = $this->db->get();
          if ($Query->num_rows() > 0) {
          return true;
          exit;
          } */

        $UpdateData = array(
            "AuctionUserStatus" => $Input['DraftUserStatus']
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('nba_sports_contest_join', $UpdateData);
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

    /*
      Description: EDIT auction user team players
     */

    function auctionTeamPlayersSubmit($Input = array(), $UserTeamID, $SeriesID) {


        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));


        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

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

        $this->db->select("UserID,ContestID");
        $this->db->from('sports_users_teams');
        $this->db->where("UserTeamID", $UserTeamID);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = $Query->row_array();
            /* update join contest team . */
            $this->db->where('ContestID', $Records['ContestID']);
            $this->db->where('UserID', $Records['UserID']);
            $this->db->limit(1);
            $this->db->update('sports_contest_join', array('UserTeamID' => $UserTeamID));
        }


        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    function getAuctionPlayersPoints($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'TotalPoints' => 'SUM(TotalPoints) TotalPoints',
                'SeriesID' => 'TP.SeriesID',
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
        $this->db->from('tbl_entity E, sports_players P,sports_team_players TP');
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        $this->db->where("TP.PlayerID", "P.PlayerID", FALSE);

        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        }
        $this->db->group_by("TP.PlayerID");
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
        // echo $this->db->last_query();
        // exit;
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

    /*
      Description: EDIT auction user team players
     */

    function draftTeamPlayersSubmit($Input = array(), $UserTeamID, $SeriesID, $MatchID, $SessionUserID, $GameType) {

        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('nba_sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->where('UserID', $SessionUserID);
        $this->db->limit(1);
        $this->db->update('nba_sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));

        /* Add User Team Players */
        if (!empty($Input['UserTeams'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->SnakeDrafts_model->getDraftTeams('TeamID,TeamGUID', array('GameType' => $GameType), TRUE, 0);

            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['TeamGUID']] = $PlayerValue['TeamID'];
                }
            }
            /* Manage User Team Players */
            $Input['UserTeams'] = (!is_array($Input['UserTeams'])) ? json_decode($Input['UserTeams'], TRUE) : $Input['UserTeams'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeams'] as $Value) {
                if (isset($PlayersIdsData[$Value['TeamGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'MatchID' => $MatchID,
                        'TeamID' => $PlayersIdsData[$Value['TeamGUID']],
                        'TeamPlayingStatus' => $Value['TeamPlayingStatus'],
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('nba_sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->select("UserID,ContestID");
        $this->db->from('nba_sports_users_teams');
        $this->db->where("UserTeamID", $UserTeamID);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = $Query->row_array();
            /* update join contest team . */
            $this->db->where('ContestID', $Records['ContestID']);
            $this->db->where('UserID', $Records['UserID']);
            $this->db->limit(1);
            $this->db->update('nba_sports_contest_join', array('UserTeamID' => $UserTeamID));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: draft team auto submit
     */

    function draftTeamAutoSubmit($CronID) {

        /** get draft contest all joined user team not submitted after 15 min * */
        $this->db->select("C.ContestID,C.AuctionUpdateTime,TIMESTAMPDIFF(MINUTE,C.AuctionUpdateTime,UTC_TIMESTAMP()) as M");
        $this->db->from('sports_contest C');
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("C.LeagueType", "Draft");
        $this->db->where("C.DraftUserTeamSubmitted", "No");
        $this->db->where("TIMESTAMPDIFF(MINUTE,C.AuctionUpdateTime,UTC_TIMESTAMP()) >", 15);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            foreach ($Contests as $Contest) {
                $this->db->select("C.ContestID,C.UserID,T.UserTeamID,T.UserID");
                $this->db->from('sports_contest_join C,sports_users_teams T');
                $this->db->where("T.ContestID", "C.ContestID", FALSE);
                $this->db->where("T.UserID", "C.UserID", FALSE);
                $this->db->where("C.ContestID", $Contest['ContestID']);
                $this->db->where("T.UserTeamType", "Draft");
                $this->db->where("T.IsPreTeam", "No");
                $this->db->where("T.AuctionTopPlayerSubmitted", "No");
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedUser = $Query->result_array();
                    foreach ($JoinedUser as $Join) {
                        /** get first and second player* */
                        $Sql = "SELECT UserTeamID,PlayerID FROM sports_users_team_players WHERE UserTeamID = '" . $Join['UserTeamID'] . "'  ORDER BY DateTime ASC LIMIT 2";
                        $Players = $this->Sports_model->customQuery($Sql);
                        if (!empty($Players)) {
                            $PlayerPosition = array("Captain", "ViceCaptain");
                            foreach ($Players as $Key => $Player) {
                                /** first and second player position update* */
                                $Sql = "UPDATE sports_users_team_players SET PlayerPosition='" . $PlayerPosition[$Key] . "' WHERE UserTeamID = '" . $Join['UserTeamID'] . "' AND PlayerID='" . $Player['PlayerID'] . "'  LIMIT 1";
                                $Return = $this->Sports_model->customQuery($Sql, FALSE, TRUE);
                            }
                            /* Edit user team to user team table . */
                            $this->db->where('UserTeamID', $Join['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));

                            /* update join contest team . */
                            $this->db->where('ContestID', $Join['ContestID']);
                            $this->db->where('UserID', $Join['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('UserTeamID' => $Join['UserTeamID']));
                        }
                    }
                }
                /* Edit user team to user team table . */
                $this->db->where('ContestID', $Contest['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('DraftUserTeamSubmitted' => "Yes"));
            }
        }
    }

    /*
      Description: get teams data
     */

    function getTeamsData($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TeamID' => 'T.TeamID',
                'TeamNameShort' => 'T.TeamNameShort',
                // 'BidCredit' => 'UTP.BidCredit',
                //  'ContestID' => 'APBS.ContestID as ContestID',
                // 'SeriesID' => 'APBS.SeriesID as SeriesID',
                // 'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                // 'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                // 'ContestGUID' => 'C.ContestGUID as ContestGUID',
                // 'BidDateTime' => 'APBS.DateTime as BidDateTime',
                // 'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'TeamFlag' => 'T.TeamFlag, CONCAT("' . BASE_URL . '",,"uploads/TeamFlag/",,T.TeamFlag) AS TeamFlag',
                //'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('T.TeamGUID,T.TeamName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, nba_sports_teams T');


        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('nba_sports_users_teams UT, nba_sports_users_team_players UTP');
            $this->db->where("UTP.TeamID", "T.TeamID", FALSE);
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
            if (!empty($Where['UserID'])) {
                $this->db->where("UT.UserID", @$Where['UserID']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }
        $this->db->where("T.TeamID", "E.EntityID", FALSE);

        //$this->db->where('EXISTS (select TeamID FROM sports_team_players WHERE TeamID=T.TeamID AND SeriesID=' . @$Where['SeriesID'] . ')');
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("T.TeamID", $Where['TeamID']);
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
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    // print_r($Record);
                    $Records[] = $Record;
                    $IsAssistant = "";
                    //$AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
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
                //$Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('nba_sports_team_players');
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
      Description: get free gaent teams
     */

    function getFreeAgentTeam($ContestID, $MatchID, $SubGameTypeKey) {
        $AllMatchesList = array();
        $this->db->select("BPS.TeamID,BPS.IsTeamHold,BPS.PlayerStatus,BPS.CreateDateTime as HoldDateTime,T.TeamGUID,"
                . "T.TeamNameShort,T.TeamName,T.TeamKey,BPS.DateTime as HoldDateTime");
        $this->db->from('nba_tbl_auction_player_bid_status BPS,nba_sports_teams T');
        $this->db->where("T.TeamID", "BPS.TeamID", FALSE);
        $this->db->where("BPS.ContestID", $ContestID);
        $this->db->where("BPS.PlayerStatus", "Upcoming");
        $this->db->order_by("T.FantasyPoints", "DESC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Teams = $Query->result_array();
            foreach ($Teams as $Team) {
               // $SubGameTypeKey = ($SubGameTypeKey == "CollegeFootballPower5RegularSeason") ? "CollegeFootballRegularSeason" : $SubGameTypeKey;
                $Sql = "SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`,TL.TeamNameShort as LocalTeamName,
                        TV.TeamNameShort as VisitorTeamName,M.MatchStartDateTime,TL.TeamFlag as LocalTeamFlag,
                        TV.TeamFlag as VisitorTeamFlag,TV.TeamName as VisitorTeamFullName,TL.TeamName as LocalTeamFullName,
                        TV.TeamStats as VisitorTeamStats,TL.TeamStats as LocalTeamStats
                        FROM `nba_sports_matches` `M` , nba_sports_teams TL, nba_sports_teams TV
                        WHERE TL.TeamID=`M`.`TeamIDLocal` AND TV.TeamID=`M`.`TeamIDVisitor` AND
                        (M.TeamIDLocal = " . $Team['TeamID'] . "
                        OR M.TeamIDVisitor = " . $Team['TeamID'] . ")
                        AND `M`.`MatchID` = $MatchID AND M.SeasonType='" . $SubGameTypeKey . "'";
                $TeamMatch = $this->Sports_model->customQuery($Sql);
                if (!empty($TeamMatch)) {

                    foreach ($TeamMatch as $Match) {
                        $Temp = array();
                        $Temp['MatchID'] = $Match['MatchID'];
                        $Temp['MatchStartDateTime'] = $Match['MatchStartDateTime'];
                        $Temp['U_LocalTeamGUID'] = $Team['TeamGUID'];
                        $Temp['IsTeamHold'] = $Team['IsTeamHold'];
                        $Temp['HoldDateTime'] = (!empty($Team['HoldDateTime'])) ? convertEstDateTime($Team['HoldDateTime']) : "";
                        if ($Team['TeamID'] == $Match['TeamIDVisitor']) {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDVisitor'];
                            $Temp['U_LocalTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_LocalTeamFullName'] = $Match['VisitorTeamFullName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['VisitorTeamFlag'];

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDLocal'];
                            $Temp['U_VisitorTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_VisitorTeamFullName'] = $Match['LocalTeamFullName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['LocalTeamStats']);
                            $Temp['U_VisitorTag'] = "@";
                        } else {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDLocal'];
                            $Temp['U_LocalTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['LocalTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_LocalTeamFullName'] = $Match['LocalTeamFullName'];
                            $Temp['U_LocalTag'] = "@";

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDVisitor'];
                            $Temp['U_VisitorTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['VisitorTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_VisitorTeamFullName'] = $Match['VisitorTeamFullName'];
                        }
                        $AllMatchesList[] = $Temp;
                    }
                } else {
                    $Sql = "SELECT TL.TeamID,TL.TeamNameShort as LocalTeamName,TL.TeamFlag as LocalTeamFlag,
                            TL.TeamStats as LocalTeamStats,TL.TeamName as LocalTeamFullName
                            FROM  nba_sports_teams TL
                            WHERE TL.TeamID = " . $Team['TeamID'] . "";
                    $TeamMatch = $this->Sports_model->customQuery($Sql, TRUE);
                    if (!empty($TeamMatch)) {
                        if (!empty($TeamMatch['LocalTeamName'])) {
                            $Temp1['U_TeamIDLocal'] = $Team['TeamID'];
                            $Temp1['U_LocalTeamGUID'] = $TeamMatch['TeamGUID'];
                            $Temp1['TeamPlayingStatus'] = $Team['TeamPlayingStatus'];
                            $Temp1['IsTeamHold'] = $Team['IsTeamHold'];
                            $Temp1['HoldDateTime'] = convertEstDateTime($Team['HoldDateTime']);
                            $Temp1['U_LocalTeamName'] = $TeamMatch['LocalTeamName'];
                            $Temp1['U_LocalTeamFullName'] = $TeamMatch['LocalTeamFullName'];
                            $Temp1['U_LocalTeamFlag'] = $TeamMatch['LocalTeamFlag'];
                            $Temp1['U_LocalTeamStats'] = json_decode($TeamMatch['LocalTeamStats']);
                            $Temp1['U_VisitorTeamName'] = "Bye";
                            $Temp1['U_VisitorTeamFullName'] = "Bye";
                            $Temp1['U_VisitorTeamFlag'] = "";
                            $Temp1['MatchStartDateTime'] = "";
                            $AllMatchesList[] = $Temp1;
                        }
                    }
                }
            }
        }
        if (!empty($AllMatchesList)) {
            $AllMatchesList = array_unique($AllMatchesList, SORT_REGULAR);
            $AllMatch = array();
            foreach ($AllMatchesList as $Match) {
                $AllMatch[] = $Match;
            }
            return $AllMatch;
        } else {
            return $AllMatchesList;
        }
    }

    /*
      Description: get mysquad teams
     */

    function getMySquadTeam($ContestID, $MatchID, $SessionUserID, $SubGameTypeKey, $StatusID = "") {
        $AllMatchesList = array();
        $this->db->select("UT.UserTeamID,UT.UserID,UTP.TeamID,UTP.TeamPlayingStatus,T.TeamGUID,UT.UserTeamGUID");
        $this->db->from('nba_sports_users_teams UT,nba_sports_users_team_players UTP,nba_sports_teams T');
        $this->db->where("UTP.UserTeamID", "UT.UserTeamID", FALSE);
        $this->db->where("T.TeamID", "UTP.TeamID", FALSE);
        $this->db->where("UT.UserID", $SessionUserID);
        $this->db->where("UT.ContestID", $ContestID);
        $this->db->where("UT.IsPreTeam", "No");
        $this->db->where("UT.IsAssistant", "No");
        $this->db->where("UT.AuctionTopPlayerSubmitted", "Yes");
        $this->db->where("UT.MatchID", $MatchID);
        $this->db->order_by("UTP.DateTime", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Teams = $Query->result_array();
            foreach ($Teams as $Team) {
               // $SubGameTypeKey = ($SubGameTypeKey == "CollegeFootballPower5RegularSeason") ? "CollegeFootballRegularSeason" : $SubGameTypeKey;
                $Sql = "SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`,TL.TeamNameShort as LocalTeamName,
                        TV.TeamNameShort as VisitorTeamName,M.MatchStartDateTime,TL.TeamFlag as LocalTeamFlag,TV.TeamFlag as VisitorTeamFlag,
                        TV.TeamStats as VisitorTeamStats,TL.TeamStats as LocalTeamStats,
                        TV.TeamName as VisitorTeamFullName,TL.TeamName as LocalTeamFullName
                        FROM `nba_sports_matches` `M` , nba_sports_teams TL, nba_sports_teams TV
                        WHERE TL.TeamID=`M`.`TeamIDLocal` AND TV.TeamID=`M`.`TeamIDVisitor` AND
                        (M.TeamIDLocal = " . $Team['TeamID'] . "
                        OR M.TeamIDVisitor = " . $Team['TeamID'] . ")
                        AND `M`.`MatchID` = $MatchID AND M.SeasonType='" . $SubGameTypeKey . "' ";
                $TeamMatch = $this->Sports_model->customQuery($Sql);
                if (!empty($TeamMatch)) {
                    foreach ($TeamMatch as $Match) {
                        $Temp = array();
                        $Temp['MatchID'] = $Match['MatchID'];
                        $Temp['MatchStartDateTime'] = $Match['MatchStartDateTime'];
                        $Temp['UserTeamID'] = $Team['UserTeamID'];
                        $Temp['UserTeamGUID'] = $Team['UserTeamGUID'];
                        $Temp['TeamPlayingStatus'] = $Team['TeamPlayingStatus'];
                        $Temp['U_LocalTeamGUID'] = $Team['TeamGUID'];
                        if ($Team['TeamID'] == $Match['TeamIDVisitor']) {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDVisitor'];
                            $Temp['U_LocalTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_LocalTeamFullName'] = $Match['VisitorTeamFullName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['VisitorTeamFlag'];

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDLocal'];
                            $Temp['U_VisitorTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_VisitorTeamFullName'] = $Match['LocalTeamFullName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['LocalTeamStats']);
                            $Temp['U_VisitorTag'] = "@";
                        } else {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDLocal'];
                            $Temp['U_LocalTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['LocalTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_LocalTeamFullName'] = $Match['LocalTeamFullName'];
                            $Temp['U_LocalTag'] = "@";

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDVisitor'];
                            $Temp['U_VisitorTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['VisitorTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_VisitorTeamFullName'] = $Match['VisitorTeamFullName'];
                        }
                        $AllMatchesList[] = $Temp;
                    }
                } else {
                    $Sql = "SELECT TL.TeamID,TL.TeamNameShort as LocalTeamName,TL.TeamFlag as LocalTeamFlag,
                            TL.TeamStats as LocalTeamStats,TL.TeamGUID,TL.TeamName as LocalTeamFullName
                            FROM  nba_sports_teams TL
                            WHERE TL.TeamID = " . $Team['TeamID'] . "";
                    $TeamMatch = $this->Sports_model->customQuery($Sql, TRUE);
                    if (!empty($TeamMatch)) {
                        if (!empty($TeamMatch['LocalTeamName'])) {
                            $Temp1['U_TeamIDLocal'] = $Team['TeamID'];
                            $Temp1['UserTeamID'] = $Team['UserTeamID'];
                            $Temp1['UserTeamGUID'] = $Team['UserTeamGUID'];
                            $Temp1['U_LocalTeamGUID'] = $TeamMatch['TeamGUID'];
                            $Temp1['TeamPlayingStatus'] = $Team['TeamPlayingStatus'];
                            $Temp1['U_LocalTeamName'] = $TeamMatch['LocalTeamName'];
                            $Temp1['U_LocalTeamFlag'] = $TeamMatch['LocalTeamFlag'];
                            $Temp1['U_LocalTeamFullName'] = $TeamMatch['LocalTeamFullName'];
                            $Temp1['U_LocalTeamStats'] = json_decode($TeamMatch['LocalTeamStats']);
                            $Temp1['U_TeamIDVisitor'] = "";
                            $Temp1['U_VisitorTeamName'] = "Bye";
                            $Temp1['U_VisitorTeamFullName'] = "Bye";
                            $Temp1['U_VisitorTeamFlag'] = "";
                            $Temp1['MatchStartDateTime'] = "";
                            $AllMatchesList[] = $Temp1;
                        }
                    }
                }
            }
        }

        if (!empty($AllMatchesList)) {
            $AllMatchesList = array_unique($AllMatchesList, SORT_REGULAR);
            $AllMatch = array();
            foreach ($AllMatchesList as $Match) {
                if (!empty($StatusID)) {
                    if ($StatusID == 2) {
                        $this->db->select('TeamID,PointsData,TotalPoints,MatchID');
                        $this->db->from('nba_sports_team_players');
                        $this->db->where("TeamID", $Match['U_TeamIDLocal']);
                        $this->db->where("MatchID", trim($MatchID));
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $TeamStats = $Query->result_array();
                            $TeamPointStats = array();
                            foreach ($TeamStats as $Team) {
                                $PointsData = json_decode($Team['PointsData'], TRUE);
                                $TeamPointStats['TotalPoints'] += $PointsData['TotalPoints'];
                                $TeamPointStats['Win'] += $PointsData['Win'];
                                $TeamPointStats['ScorePoints'] += $PointsData['ScorePoints'];
                                $TeamPointStats['Score'] += $PointsData['Score'];
                                $TeamPointStats['PointDifference'] += $PointsData['PointDifference'];
                                $TeamPointStats['DifferenceScorePoint'] += $PointsData['DifferenceScorePoint'];
                                $TeamPointStats['OffensiveYards'] += $PointsData['OffensiveYards'];
                                $TeamPointStats['OffensiveYardsScorePoints'] += $PointsData['OffensiveYardsScorePoints'];
                                $TeamPointStats['DefensiveYards'] += $PointsData['DefensiveYards'];
                                $TeamPointStats['DefensiveYardsScorePoints'] += $PointsData['DefensiveYardsScorePoints'];
                            }
                            $Match['U_LocalTeamStats'] = $TeamPointStats;
                        }

                        $this->db->select('TeamID,PointsData,TotalPoints,WeekID');
                        $this->db->from('sports_team_players');
                        $this->db->where("TeamID", $Match['U_TeamIDVisitor']);
                        $this->db->where("WeekID", trim($WeekID));
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $TeamStats = $Query->result_array();
                            $TeamPointStats = array();
                            foreach ($TeamStats as $Team) {
                                $PointsData = json_decode($Team['PointsData'], TRUE);
                                $TeamPointStats['TotalPoints'] += $PointsData['TotalPoints'];
                                $TeamPointStats['Win'] += $PointsData['Win'];
                                $TeamPointStats['ScorePoints'] += $PointsData['ScorePoints'];
                                $TeamPointStats['Score'] += $PointsData['Score'];
                                $TeamPointStats['PointDifference'] += $PointsData['PointDifference'];
                                $TeamPointStats['DifferenceScorePoint'] += $PointsData['DifferenceScorePoint'];
                                $TeamPointStats['OffensiveYards'] += $PointsData['OffensiveYards'];
                                $TeamPointStats['OffensiveYardsScorePoints'] += $PointsData['OffensiveYardsScorePoints'];
                                $TeamPointStats['DefensiveYards'] += $PointsData['DefensiveYards'];
                                $TeamPointStats['DefensiveYardsScorePoints'] += $PointsData['DefensiveYardsScorePoints'];
                            }
                            $Match['U_VisitorTeamStats'] = $TeamPointStats;
                        }
                    }
                }
                $AllMatch[] = $Match;
            }
            return $AllMatch;
        } else {
            return $AllMatchesList;
        }
    }

    /*
      Description: get mysquad teams
     */

    function getMyPlayingRooster($ContestID, $WeekID, $SessionUserID, $SubGameTypeKey, $StatusID = "") {
        $AllMatchesList = array();
        $this->db->select("UT.UserTeamID,UT.UserID,UTP.TeamID,UTP.TeamPlayingStatus,T.TeamGUID,UT.UserTeamGUID");
        $this->db->from('sports_users_teams UT,sports_users_team_players UTP,sports_teams T');
        $this->db->where("UTP.UserTeamID", "UT.UserTeamID", FALSE);
        $this->db->where("T.TeamID", "UTP.TeamID", FALSE);
        $this->db->where("UT.UserID", $SessionUserID);
        $this->db->where("UT.ContestID", $ContestID);
        $this->db->where("UT.IsPreTeam", "No");
        $this->db->where("UT.IsAssistant", "No");
        //$this->db->where("UT.AuctionTopPlayerSubmitted", "No");
        $this->db->where("UT.WeekID", $WeekID);
        $this->db->order_by("UTP.DateTime", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Teams = $Query->result_array();
            foreach ($Teams as $Team) {
                $SubGameTypeKey = ($SubGameTypeKey == "CollegeFootballPower5RegularSeason") ? "CollegeFootballRegularSeason" : $SubGameTypeKey;
                $Sql = "SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`,TL.TeamNameShort as LocalTeamName,
                        TV.TeamNameShort as VisitorTeamName,M.MatchStartDateTime,TL.TeamFlag as LocalTeamFlag,TV.TeamFlag as VisitorTeamFlag,
                        TV.TeamStats as VisitorTeamStats,TL.TeamStats as LocalTeamStats,
                        TV.TeamName as VisitorTeamFullName,TL.TeamName as LocalTeamFullName
                        FROM `sports_matches` `M` , sports_teams TL, sports_teams TV
                        WHERE TL.TeamID=`M`.`TeamIDLocal` AND TV.TeamID=`M`.`TeamIDVisitor` AND
                        (M.TeamIDLocal = " . $Team['TeamID'] . "
                        OR M.TeamIDVisitor = " . $Team['TeamID'] . ")
                        AND `M`.`WeekID` = $WeekID AND SeasonType ='" . $SubGameTypeKey . "' ";
                $TeamMatch = $this->Sports_model->customQuery($Sql);
                if (!empty($TeamMatch)) {
                    foreach ($TeamMatch as $Match) {
                        $Temp = array();
                        $Temp['MatchID'] = $Match['MatchID'];
                        $Temp['MatchStartDateTime'] = $Match['MatchStartDateTime'];
                        $Temp['UserTeamID'] = $Team['UserTeamID'];
                        $Temp['UserTeamGUID'] = $Team['UserTeamGUID'];
                        $Temp['TeamPlayingStatus'] = $Team['TeamPlayingStatus'];
                        $Temp['U_LocalTeamGUID'] = $Team['TeamGUID'];
                        if ($Team['TeamID'] == $Match['TeamIDVisitor']) {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDVisitor'];
                            $Temp['U_LocalTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_LocalTeamFullName'] = $Match['VisitorTeamFullName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['VisitorTeamFlag'];

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDLocal'];
                            $Temp['U_VisitorTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_VisitorTeamFullName'] = "@ " . $Match['LocalTeamFullName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['LocalTeamStats']);
                        } else {
                            $Temp['U_TeamIDLocal'] = $Match['TeamIDLocal'];
                            $Temp['U_LocalTeamName'] = $Match['LocalTeamName'];
                            $Temp['U_LocalTeamStats'] = json_decode($Match['LocalTeamStats']);
                            $Temp['U_LocalTeamFlag'] = $Match['LocalTeamFlag'];
                            $Temp['U_LocalTeamFullName'] = "@ " . $Match['LocalTeamFullName'];

                            $Temp['U_TeamIDVisitor'] = $Match['TeamIDVisitor'];
                            $Temp['U_VisitorTeamName'] = $Match['VisitorTeamName'];
                            $Temp['U_VisitorTeamFlag'] = $Match['VisitorTeamFlag'];
                            $Temp['U_VisitorTeamStats'] = json_decode($Match['VisitorTeamStats']);
                            $Temp['U_VisitorTeamFullName'] = $Match['VisitorTeamFullName'];
                        }
                        $AllMatchesList[] = $Temp;
                    }
                } else {
                    $Sql = "SELECT TL.TeamID,TL.TeamNameShort as LocalTeamName,TL.TeamFlag as LocalTeamFlag,
                            TL.TeamStats as LocalTeamStats,TL.TeamGUID,TL.TeamName as LocalTeamFullName
                            FROM  sports_teams TL
                            WHERE TL.TeamID = " . $Team['TeamID'] . "";
                    $TeamMatch = $this->Sports_model->customQuery($Sql, TRUE);
                    if (!empty($TeamMatch)) {
                        if (!empty($TeamMatch['LocalTeamName'])) {
                            $Temp1['UserTeamID'] = $Team['UserTeamID'];
                            $Temp1['UserTeamGUID'] = $Team['UserTeamGUID'];
                            $Temp1['U_LocalTeamGUID'] = $TeamMatch['TeamGUID'];
                            $Temp1['TeamPlayingStatus'] = $Team['TeamPlayingStatus'];
                            $Temp1['U_LocalTeamName'] = "@ " . $TeamMatch['LocalTeamName'];
                            $Temp1['U_LocalTeamFlag'] = $TeamMatch['LocalTeamFlag'];
                            $Temp1['U_LocalTeamFullName'] = $TeamMatch['LocalTeamFullName'];
                            $Temp1['U_LocalTeamStats'] = json_decode($TeamMatch['LocalTeamStats']);
                            $Temp1['U_VisitorTeamName'] = "Bye";
                            $Temp1['U_VisitorTeamFullName'] = "Bye";
                            $Temp1['U_VisitorTeamFlag'] = "";
                            $Temp1['MatchStartDateTime'] = "";
                            $AllMatchesList[] = $Temp1;
                        }
                    }
                }
            }
        }

        $AllList = array();
        $AllList['Users'] = array();
        $this->db->select('U.UserGUID,U.UserTeamCode,U.FirstName,IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic');
        $this->db->from('tbl_users U');
        $this->db->where("U.UserID", $SessionUserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $AllList['Users'] = $Query->row_array();
        }

        if (!empty($AllMatchesList)) {
            $AllMatchesList = array_unique($AllMatchesList, SORT_REGULAR);
            $AllMatch = array();
            foreach ($AllMatchesList as $Match) {
                if (!empty($StatusID)) {
                    if ($StatusID == 2) {
                        $this->db->select('TeamID,PointsData,TotalPoints,WeekID');
                        $this->db->from('sports_team_players');
                        $this->db->where("TeamID", $Match['U_TeamIDLocal']);
                        $this->db->where("WeekID", trim($WeekID));
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $TeamStats = $Query->result_array();
                            $TeamPointStats = array();
                            foreach ($TeamStats as $Team) {
                                $PointsData = json_decode($Team['PointsData'], TRUE);
                                $TeamPointStats['TotalPoints'] += $PointsData['TotalPoints'];
                                $TeamPointStats['Win'] += $PointsData['Win'];
                                $TeamPointStats['ScorePoints'] += $PointsData['ScorePoints'];
                                $TeamPointStats['Score'] += $PointsData['Score'];
                                $TeamPointStats['PointDifference'] += $PointsData['PointDifference'];
                                $TeamPointStats['DifferenceScorePoint'] += $PointsData['DifferenceScorePoint'];
                                $TeamPointStats['OffensiveYards'] += $PointsData['OffensiveYards'];
                                $TeamPointStats['OffensiveYardsScorePoints'] += $PointsData['OffensiveYardsScorePoints'];
                                $TeamPointStats['DefensiveYards'] += $PointsData['DefensiveYards'];
                                $TeamPointStats['DefensiveYardsScorePoints'] += $PointsData['DefensiveYardsScorePoints'];
                            }
                            $Match['U_LocalTeamStats'] = $TeamPointStats;
                        }

                        $this->db->select('TeamID,PointsData,TotalPoints,WeekID');
                        $this->db->from('sports_team_players');
                        $this->db->where("TeamID", $Match['U_TeamIDVisitor']);
                        $this->db->where("WeekID", trim($WeekID));
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $TeamStats = $Query->result_array();
                            $TeamPointStats = array();
                            foreach ($TeamStats as $Team) {
                                $PointsData = json_decode($Team['PointsData'], TRUE);
                                $TeamPointStats['TotalPoints'] += $PointsData['TotalPoints'];
                                $TeamPointStats['Win'] += $PointsData['Win'];
                                $TeamPointStats['ScorePoints'] += $PointsData['ScorePoints'];
                                $TeamPointStats['Score'] += $PointsData['Score'];
                                $TeamPointStats['PointDifference'] += $PointsData['PointDifference'];
                                $TeamPointStats['DifferenceScorePoint'] += $PointsData['DifferenceScorePoint'];
                                $TeamPointStats['OffensiveYards'] += $PointsData['OffensiveYards'];
                                $TeamPointStats['OffensiveYardsScorePoints'] += $PointsData['OffensiveYardsScorePoints'];
                                $TeamPointStats['DefensiveYards'] += $PointsData['DefensiveYards'];
                                $TeamPointStats['DefensiveYardsScorePoints'] += $PointsData['DefensiveYardsScorePoints'];
                            }
                            $Match['U_VisitorTeamStats'] = $TeamPointStats;
                        }
                    }
                }
                $AllMatch[] = $Match;
            }
            $AllList['Teams'] = $AllMatch;
            return $AllList;
        } else {
            $AllList['Teams'] = $AllMatchesList;
            return $AllList;
        }
    }

    /*
      Description: request free agent team for change
     */

    function requestFreeAgentUserTeam($ContestID, $SessionUserID, $UserTeamID, $DropTeamID, $CatchTeamID, $WeekID, $Type) {

        if ($Type == "Free") {
            $CurrentDate = date('Y-m-d H:i:s');
            $Query = $this->db->query("SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`, `M`.`MatchStartDateTime`, `E`.`StatusID`
                                        FROM `sports_matches` `M`, `tbl_entity` `E`
                                        WHERE `E`.`StatusID` = 1
                                        AND `M`.`MatchStartDateTime` > '" . $CurrentDate . "'
                                        AND ( `M`.`TeamIDLocal` = '" . $CatchTeamID . "'
                                        OR `M`.`TeamIDVisitor` = '" . $CatchTeamID . "')
                                         LIMIT 1");
            $Matches = ($Query->num_rows() > 0) ? $Query->row_array() : false;
            if (!empty($Matches)) {
                $Matches = $Query->row_array();
                $MatchStartDateTime = $Matches['MatchStartDateTime'];
                $Hours = round((strtotime($MatchStartDateTime) - strtotime($CurrentDate)) / 3600);
                if ($Hours <= 24) {
                    return 2;
                }
            }

            /** check catch team id availabe or not * */
            $this->db->select("PBS.TeamID");
            $this->db->from('tbl_auction_player_bid_status PBS');
            $this->db->where("PBS.ContestID", $ContestID);
            $this->db->where("PBS.TeamID", $CatchTeamID);
            $this->db->where("PBS.PlayerStatus", "Upcoming");
            $this->db->where("PBS.IsTeamHold", "No");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $this->db->trans_start();
                /** add wire transaction * */
                $WireHistory = array(
                    'ContestID' => $ContestID,
                    'UserID' => $SessionUserID,
                    'UserTeamID' => $UserTeamID,
                    'WeekID' => $WeekID,
                    'DropTeamID' => $DropTeamID,
                    'CatchTeamID' => $CatchTeamID,
                    'Type' => "Free",
                    'StatusID' => 5,
                    'DateTime' => date('Y-m-d H:i:s')
                );
                $Insert = $this->db->insert('sports_wire_history', $WireHistory);
                if ($Insert) {
                    /** update catch player status on sold * */
                    $UpdateData = array(
                        "PlayerStatus" => "Sold"
                    );
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('TeamID', $CatchTeamID);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);

                    /** update drop player status on hold * */
                    $startDate = time();
                    $CurrentDateTime = date('Y-m-d H:i:s', strtotime('+1 day', $startDate));
                    $UpdateData = array(
                        "PlayerStatus" => "Upcoming",
                        "IsTeamHold" => "Yes",
                        "DateTime" => $CurrentDateTime
                    );
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('TeamID', $DropTeamID);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);

                    /** update catch player in user team replace to drop player* */
                    $UpdateData = array(
                        "TeamID" => $CatchTeamID
                    );
                    $this->db->where('UserTeamID', $UserTeamID);
                    $this->db->where('TeamID', $DropTeamID);
                    $this->db->limit(1);
                    $this->db->update('sports_users_team_players', $UpdateData);


                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        return 0;
                    }
                    return 1;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /*
      Description: request Waiver wire teams
     */

    function requestWaiverWireUserTeam($ContestID, $SessionUserID, $UserTeamID, $WaiverTeams, $WeekID, $Type) {

        if ($Type == "Wire") {
            $WaiverTeams = json_decode($WaiverTeams, true);
            if (is_array($WaiverTeams)) {
                foreach ($WaiverTeams as $Rows) {
                    /** check catch team id availabe or not * */
                    $this->db->select("PBS.TeamID");
                    $this->db->from('tbl_auction_player_bid_status PBS');
                    $this->db->where("PBS.ContestID", $ContestID);
                    $this->db->where("PBS.TeamID", $Rows['CatchTeamID']);
                    $this->db->where("PBS.PlayerStatus", "Upcoming");
                    $this->db->where("PBS.IsTeamHold", "Yes");
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        /** add wire transaction * */
                        $WireHistory = array(
                            'ContestID' => $ContestID,
                            'UserID' => $SessionUserID,
                            'UserTeamID' => $UserTeamID,
                            'WeekID' => $WeekID,
                            'DropTeamID' => $Rows['DropTeamID'],
                            'CatchTeamID' => $Rows['CatchTeamID'],
                            'Type' => "Wire",
                            'StatusID' => 1,
                            'OrderNo' => $Rows['Order'],
                            'DateTime' => date('Y-m-d H:i:s')
                        );
                        $Insert = $this->db->insert('sports_wire_history', $WireHistory);
                    }
                }
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /*
      Description: cancel request Waiver wire teams
     */

    function cancelRequestWaiverWireUserTeam($ContestID, $SessionUserID, $WireID) {


        /** update catch player in user team replace to drop player* */
        $UpdateData = array(
            "StatusID" => 4
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $SessionUserID);
        $this->db->where('WireID', $WireID);
        $this->db->limit(1);
        $Update = $this->db->update('sports_wire_history', $UpdateData);
        if ($Update) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*
      Description: get waiver wire transaction by user
     */

    function getWaiverTransaction($ContestID, $WeekID, $UserID) {
        $this->db->select("H.WireID,DT.TeamNameShort DropTeamName,CT.TeamNameShort as CatchTeamName,DT.TeamName DropTeamFullName,CT.TeamName as CatchTeamFullName,"
                . 'CONVERT_TZ(H.DateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS DateTime,H.Type,H.WeekID,'
                . " CASE WHEN H.StatusID = 1 THEN 'Processing' WHEN H.StatusID = 5 THEN 'Approved' WHEN H.StatusID = 3 THEN 'Declined' WHEN H.StatusID = 4 THEN 'Cancelled' END as  Status");
        $this->db->from('sports_wire_history H,sports_teams DT,sports_teams CT');
        $this->db->where("DT.TeamID", "H.DropTeamID", FALSE);
        $this->db->where("CT.TeamID", "H.CatchTeamID", FALSE);
        $this->db->where("H.WeekID", $WeekID);
        if (!empty($UserID)) {
            $this->db->where("H.UserID", $UserID);
        }
        $this->db->where("H.ContestID", $ContestID);
        $this->db->order_by("H.WireID", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Teams = $Query->result_array();
            return $Teams;
        } else {
            return false;
        }
    }

    /*
      Description: get waiver wire transaction by week
     */

    function getWaiverTransactionByWeek($ContestID) {

        $WaiverTransaction = array();
        $this->db->select("ContestID,WeekStart,WeekEnd");
        $this->db->from('sports_contest');
        $this->db->where("ContestID", $ContestID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contest = $Query->row_array();
            for ($i = $Contest['WeekStart']; $i <= $Contest['WeekEnd']; $i++) {
                $this->db->select("U.UserTeamCode,DT.TeamNameShort DropTeamName,CT.TeamNameShort as CatchTeamName,DT.TeamName DropTeamFullName,CT.TeamName as CatchTeamFullName,"
                        . 'CONVERT_TZ(H.DateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS DateTime,H.Type,H.WeekID,'
                        . " CASE WHEN H.StatusID = 1 THEN 'Processing' WHEN H.StatusID = 5 THEN 'Approved' WHEN H.StatusID = 3 THEN 'Declined'  END as  Status");
                $this->db->from('sports_wire_history H,sports_teams DT,sports_teams CT,tbl_users U');
                $this->db->where("DT.TeamID", "H.DropTeamID", FALSE);
                $this->db->where("CT.TeamID", "H.CatchTeamID", FALSE);
                $this->db->where("U.UserID", "H.UserID", FALSE);
                $this->db->where("H.WeekID", $i);
                $this->db->where("H.ContestID", $ContestID);
                $this->db->where("H.StatusID", 5);
                $this->db->order_by("H.WireID", "ASC");
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Teams = $Query->result_array();
                    $WaiverTransaction[] = array(
                        'Week' => $i,
                        'Transactions' => $Teams
                    );
                }
            }
            return $WaiverTransaction;
        } else {
            return false;
        }
    }

        /*
      Description: get draft transaction
     */

    function getDraftPlayerDropAddTransactions($ContestID) {
        $WaiverTransaction = array();
        $this->db->select("U.UserTeamCode,P.PlayerName,P.PlayerGUID,"
                . 'CONVERT_TZ(H.DateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS DateTime,H.Type,H.PlayerSelectTypeRole,'
                . '(CASE P.PlayerRole
                     when "ShootingGuard" then "SG"
                     when "Center" then "C"
                     when "PowerForward" then "PF"
                     when "PointGuard" then "PG"
                     when "SmallForward" then "SF"
                     END) as PlayerRoleShort'
                );
        $this->db->from('nba_sports_wire_history H,nba_sports_players P,tbl_users U');
        $this->db->where("P.PlayerID", "H.PlayerID", FALSE);
        $this->db->where("U.UserID", "H.UserID", FALSE);
        $this->db->where("H.ContestID", $ContestID);
        $this->db->order_by("H.WireID", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $WaiverTransaction = $Query->result_array();
        }
        return $WaiverTransaction;
    }

    /*
      Description: To Auto Draft dropper team assign multiple request single team
     */

    function droperTeamAssignOrDeclineMultiple($CatchTeamRequest, $UserWaiver) {


        /** user order change * */
        /* $I = 1;
          foreach ($UserWaiver as $Rows) {
          $UpdateData[] = array(
          "DraftUserPosition" => ($I == 1) ? 1 : $Rows['DraftUserPosition'] + 1,
          'UserID' => $Rows['UserID']
          );
          $this->db->where('ContestID', $Rows['ContestID']);
          $this->db->where('UserID', $Rows['UserID']);
          $this->db->limit(1);
          $this->db->update('sports_contest_join', $UpdateData);
          $I++;
          } */



        $FirstCatchTeam = $CatchTeamRequest[0];
        $KeyValue = 0;
        foreach ($CatchTeamRequest as $Key => $Catch) {
            if ($Catch['UserID'] == $UserWaiver[0]['UserID']) {
                $FirstCatchTeam = $Catch;
                $KeyValue = $Key;
            }
        }
        /** apply team * */
        $UpdateData = array(
            "StatusID" => 5
        );
        $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
        $this->db->where('UserTeamID', $FirstCatchTeam['UserTeamID']);
        $this->db->where('WeekID', $FirstCatchTeam['WeekID']);
        $this->db->where('UserID', $FirstCatchTeam['UserID']);
        $this->db->where('DropTeamID', $FirstCatchTeam['DropTeamID']);
        $this->db->where('CatchTeamID', $FirstCatchTeam['CatchTeamID']);
        $this->db->where("StatusID", 1);
        $this->db->limit(1);
        $Update = $this->db->update('sports_wire_history', $UpdateData);
        if ($Update) {
            /** update catch player in user team replace to drop player* */
            $UpdateData = array(
                "TeamID" => $FirstCatchTeam['CatchTeamID']
            );
            $this->db->where('UserTeamID', $FirstCatchTeam['UserTeamID']);
            $this->db->where('TeamID', $FirstCatchTeam['DropTeamID']);
            $this->db->limit(1);
            $this->db->update('sports_users_team_players', $UpdateData);

            /** update catch player status on sold * */
            $UpdateData = array(
                "PlayerStatus" => "Sold",
                "IsTeamHold" => "No",
                "DateTime" => null
            );
            $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
            $this->db->where('TeamID', $FirstCatchTeam['CatchTeamID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);

            $startDate = time();
            $CurrentDateTime = date('Y-m-d H:i:s', strtotime('+1 day', $startDate));
            $UpdateData = array(
                "PlayerStatus" => "Upcoming",
                "IsTeamHold" => "Yes",
                "DateTime" => $CurrentDateTime
            );
            $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
            $this->db->where('TeamID', $FirstCatchTeam['DropTeamID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);


            /** get dropper team request * */
            unset($CatchTeamRequest[$KeyValue]);
            $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                    . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
            $this->db->from('sports_wire_history H');
            $this->db->where("H.WeekID", $FirstCatchTeam['WeekID']);
            $this->db->where("H.ContestID", $FirstCatchTeam['ContestID']);
            $this->db->where("H.DropTeamID", $FirstCatchTeam['DropTeamID']);
            $this->db->where("H.CatchTeamID !=", $FirstCatchTeam['CatchTeamID']);
            $this->db->where("H.StatusID", 1);
            $this->db->order_by("H.OrderNo", "ASC");
            $this->db->order_by("H.DateTime", "ASC");
            $this->db->group_by("H.CatchTeamID");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $DropperTeams = $Query->result_array();
                $CatchTeamRequest = array_merge($CatchTeamRequest, $DropperTeams);
            }
            if (!empty($CatchTeamRequest)) {
                foreach ($CatchTeamRequest as $Catch) {
                    /** team request decline by already assign * */
                    $UpdateData = array(
                        "StatusID" => 3
                    );
                    $this->db->where('ContestID', $Catch['ContestID']);
                    $this->db->where('UserTeamID', $Catch['UserTeamID']);
                    $this->db->where('WeekID', $Catch['WeekID']);
                    $this->db->where('UserID', $Catch['UserID']);
                    $this->db->where('DropTeamID', $Catch['DropTeamID']);
                    $this->db->where('CatchTeamID', $Catch['CatchTeamID']);
                    $this->db->where("StatusID", 1);
                    $this->db->limit(1);
                    $this->db->update('sports_wire_history', $UpdateData);
                }
            }

        }

        /** user order change * */
        $FirstCatchTeamList = $UserWaiver[0];
        $this->db->select("J.UserID,J.ContestID,J.DraftUserPosition");
        $this->db->from('sports_contest_join J');
        $this->db->where("J.ContestID", $FirstCatchTeamList['ContestID']);
        $this->db->order_by('J.UserID=' . $FirstCatchTeamList['UserID'] . ' DESC', null, FALSE);
        $this->db->order_by("J.DraftUserPosition", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $UserWaiverWire = $Query->result_array();
            $K = $UserWaiverWire[0]['DraftUserPosition'];
            foreach ($UserWaiverWire as $Key => $Rows) {
                $I = $Rows['DraftUserPosition'];
                if ($Key == 0) {
                    $I = 1;
                } else if ($K > $Rows['DraftUserPosition']) {
                    $I = (int) $Rows['DraftUserPosition'] + 1;
                }
                $UpdateData = array(
                    "DraftUserPosition" => $I,
                );
                $this->db->where('ContestID', $Rows['ContestID']);
                $this->db->where('UserID', $Rows['UserID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', $UpdateData);
            }
        }
        return true;
    }

    /*
      Description: To Auto Draft dropper team assign request
     */

    function droperTeamAssignOrDeclineSingle($CatchTeamRequest) {

        $FirstCatchTeam = $CatchTeamRequest[0];

        /** apply team * */
        $UpdateData = array(
            "StatusID" => 5
        );
        $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
        $this->db->where('UserTeamID', $FirstCatchTeam['UserTeamID']);
        $this->db->where('WeekID', $FirstCatchTeam['WeekID']);
        $this->db->where('UserID', $FirstCatchTeam['UserID']);
        $this->db->where('DropTeamID', $FirstCatchTeam['DropTeamID']);
        $this->db->where('CatchTeamID', $FirstCatchTeam['CatchTeamID']);
        $this->db->where("StatusID", 1);
        $this->db->limit(1);
        $Update = $this->db->update('sports_wire_history', $UpdateData);
        if ($Update) {
            /** update catch player in user team replace to drop player* */
            $UpdateData = array(
                "TeamID" => $FirstCatchTeam['CatchTeamID']
            );
            $this->db->where('UserTeamID', $FirstCatchTeam['UserTeamID']);
            $this->db->where('TeamID', $FirstCatchTeam['DropTeamID']);
            $this->db->limit(1);
            $this->db->update('sports_users_team_players', $UpdateData);

            /** update catch player status on sold * */
            $UpdateData = array(
                "PlayerStatus" => "Sold",
                "IsTeamHold" => "No",
                "DateTime" => null
            );
            $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
            $this->db->where('TeamID', $FirstCatchTeam['CatchTeamID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);

            $startDate = time();
            $CurrentDateTime = date('Y-m-d H:i:s', strtotime('+1 day', $startDate));
            $UpdateData = array(
                "PlayerStatus" => "Upcoming",
                "IsTeamHold" => "Yes",
                "DateTime" => $CurrentDateTime
            );
            $this->db->where('ContestID', $FirstCatchTeam['ContestID']);
            $this->db->where('TeamID', $FirstCatchTeam['DropTeamID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);


            /** get dropper team request * */
            unset($CatchTeamRequest[0]);
            $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                    . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
            $this->db->from('sports_wire_history H');
            $this->db->where("H.WeekID", $FirstCatchTeam['WeekID']);
            $this->db->where("H.ContestID", $FirstCatchTeam['ContestID']);
            $this->db->where("H.DropTeamID", $FirstCatchTeam['DropTeamID']);
            $this->db->where("H.CatchTeamID !=", $FirstCatchTeam['CatchTeamID']);
            $this->db->where("H.StatusID", 1);
            $this->db->order_by("H.OrderNo", "ASC");
            $this->db->order_by("H.DateTime", "ASC");
            $this->db->group_by("H.CatchTeamID");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $DropperTeams = $Query->result_array();
                $CatchTeamRequest = $DropperTeams;
            }
            if (!empty($CatchTeamRequest)) {
                foreach ($CatchTeamRequest as $Catch) {
                    /** team request decline by already assign * */
                    $UpdateData = array(
                        "StatusID" => 3
                    );
                    $this->db->where('ContestID', $Catch['ContestID']);
                    $this->db->where('UserTeamID', $Catch['UserTeamID']);
                    $this->db->where('WeekID', $Catch['WeekID']);
                    $this->db->where('UserID', $Catch['UserID']);
                    $this->db->where('DropTeamID', $Catch['DropTeamID']);
                    $this->db->where('CatchTeamID', $Catch['CatchTeamID']);
                    $this->db->where("StatusID", 1);
                    $this->db->limit(1);
                    $this->db->update('sports_wire_history', $UpdateData);
                }
            }
        }

        /** user order change * */
        $this->db->select("J.UserID,J.ContestID,J.DraftUserPosition");
        $this->db->from('sports_contest_join J');
        $this->db->where("J.ContestID", $FirstCatchTeam['ContestID']);
        $this->db->order_by('J.UserID=' . $FirstCatchTeam['UserID'] . ' DESC', null, FALSE);
        $this->db->order_by("J.DraftUserPosition", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $UserWaiver = $Query->result_array();
            $K = $UserWaiver[0]['DraftUserPosition'];
            foreach ($UserWaiver as $Key => $Rows) {
                $I = $Rows['DraftUserPosition'];
                if ($Key == 0) {
                    $I = 1;
                } else if ($K > $Rows['DraftUserPosition']) {
                    $I = (int) $Rows['DraftUserPosition'] + 1;
                }
                $UpdateData = array(
                    "DraftUserPosition" => $I,
                );
                $this->db->where('ContestID', $Rows['ContestID']);
                $this->db->where('UserID', $Rows['UserID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', $UpdateData);
            }
        }
        return true;
    }

    /*
      Description: To Auto Draft waiver wire request by draft position
     */

    function applyWaiverWireActionDraftPosition($WeekID, $ContestID) {
        /** get all request users by week * */
        $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime,J.DraftUserPosition");
        $this->db->from('sports_wire_history H,sports_contest_join J');
        $this->db->where("J.ContestID", "H.ContestID", FALSE);
        $this->db->where("J.UserID", "H.UserID", FALSE);
        $this->db->where("H.WeekID", $WeekID);
        $this->db->where("H.ContestID", $ContestID);
        $this->db->where("H.StatusID", 1);
        $this->db->order_by("J.DraftUserPosition", "DESC");
        $this->db->group_by("H.UserID");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $UserWaiver = $Query->result_array();

            foreach ($UserWaiver as $Rows) {
                /** check waiver request by user * */
                $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                        . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
                $this->db->from('sports_wire_history H');
                $this->db->where("H.WeekID", $WeekID);
                $this->db->where("H.ContestID", $ContestID);
                $this->db->where("H.UserID", $Rows['UserID']);
                $this->db->where("H.StatusID", 1);
                $this->db->order_by("H.OrderNo", "ASC");
                $this->db->order_by("H.DateTime", "ASC");
                $Query = $this->db->get();
                $NumRows = $Query->num_rows();
                if ($NumRows > 0) {
                    $WaiverTeams = $Query->result_array();

                    foreach ($WaiverTeams as $Team) {
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        /** check catch team request by team id * */
                        $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                                . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
                        $this->db->from('sports_wire_history H,tbl_auction_player_bid_status APS');
                        $this->db->where("H.ContestID", "APS.ContestID", FALSE);
                        $this->db->where("H.CatchTeamID", "APS.TeamID", FALSE);
                        $this->db->where("H.WeekID", $WeekID);
                        $this->db->where("H.ContestID", $ContestID);
                        $this->db->where("APS.DateTime <=", $CurrentDateTime);
                        $this->db->where("H.CatchTeamID", $Team['CatchTeamID']);
                        $this->db->where("H.StatusID", 1);
                        $this->db->order_by("H.OrderNo", "ASC");
                        $Query = $this->db->get();
                        $NumRows = $Query->num_rows();
                        if ($NumRows == 1) {
                            $CatchTeamRequest = $Query->result_array();
                            /** to check Drop team id * */
                            $this->droperTeamAssignOrDeclineSingle($CatchTeamRequest);
                        } else if ($NumRows > 1) {
                            $CatchTeamRequest = $Query->result_array();
                            /** to check Drop team id * */
                            $this->droperTeamAssignOrDeclineMultiple($CatchTeamRequest, $UserWaiver);
                        }
                    }
                }
            }
        }
        return true;
    }

    /*
      Description: To Auto Draft waiver wire request by week rank position
     */

    function applyWaiverWireActionWeekRank($WeekID, $ContestID) {
        /** get all request users by week * */
        $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime,J.Rank as DraftUserPosition");
        $this->db->from('sports_wire_history H,sports_users_teams J');
        $this->db->where("J.ContestID", "H.ContestID", FALSE);
        $this->db->where("J.UserID", "H.UserID", FALSE);
        $this->db->where("H.WeekID", $WeekID);
        $this->db->where("H.ContestID", $ContestID);
        $this->db->where("H.StatusID", 1);
        $this->db->order_by("J.Rank", "DESC");
        $this->db->group_by("H.UserID");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $UserWaiver = $Query->result_array();

            foreach ($UserWaiver as $Rows) {
                /** check waiver request by user * */
                $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                        . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
                $this->db->from('sports_wire_history H');
                $this->db->where("H.WeekID", $WeekID);
                $this->db->where("H.ContestID", $ContestID);
                $this->db->where("H.UserID", $Rows['UserID']);
                $this->db->where("H.StatusID", 1);
                $this->db->order_by("H.OrderNo", "ASC");
                $this->db->order_by("H.DateTime", "ASC");
                $Query = $this->db->get();
                $NumRows = $Query->num_rows();
                if ($NumRows > 0) {
                    $WaiverTeams = $Query->result_array();

                    foreach ($WaiverTeams as $Team) {
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        /** check catch team request by team id * */
                        $this->db->select("H.ContestID,H.UserID,H.UserTeamID,H.OrderNo,"
                                . "H.WeekID,H.DropTeamID,H.CatchTeamID,H.DateTime");
                        $this->db->from('sports_wire_history H,tbl_auction_player_bid_status APS');
                        $this->db->where("H.ContestID", "APS.ContestID", FALSE);
                        $this->db->where("H.CatchTeamID", "APS.TeamID", FALSE);
                        $this->db->where("H.WeekID", $WeekID);
                        $this->db->where("H.ContestID", $ContestID);
                        $this->db->where("APS.DateTime <=", $CurrentDateTime);
                        $this->db->where("H.CatchTeamID", $Team['CatchTeamID']);
                        $this->db->where("H.StatusID", 1);
                        $this->db->order_by("H.OrderNo", "ASC");
                        $Query = $this->db->get();
                        $NumRows = $Query->num_rows();
                        if ($NumRows == 1) {
                            $CatchTeamRequest = $Query->result_array();
                            /** to check Drop team id * */
                            $this->droperTeamAssignOrDeclineSingle($CatchTeamRequest);
                        } else if ($NumRows > 1) {
                            $CatchTeamRequest = $Query->result_array();
                            /** to check Drop team id * */
                            $this->droperTeamAssignOrDeclineMultiple($CatchTeamRequest, $UserWaiver);
                        }
                    }
                }
            }
        }
        return true;
    }

    /*
      Description: To Auto Draft NFL Team Waiver Wire Request
     */

    function nflTeamDraftWaiverWireRequest() {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $CurrentDate = date('Y-m-d H:i:s');

        /** check catch team request by team id * */
        $this->db->select("APS.ContestID,APS.TeamID,APS.IsTeamHold,APS.SeriesID,APS.DateTime");
        $this->db->from('tbl_auction_player_bid_status APS');
        $this->db->where("APS.IsTeamHold", "Yes");
        $this->db->where("APS.DateTime <=", $CurrentDate);
        $this->db->order_by("APS.DateTime", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $CatchTeamRequest = $Query->result_array();
            foreach ($CatchTeamRequest as $Rows) {
                /** check team in wire history * */
                $this->db->select("ContestID,CatchTeamID,DropTeamID,UserTeamID,WeekID,StatusID");
                $this->db->from('sports_wire_history');
                $this->db->where("Type", "Wire");
                $this->db->where("StatusID", 1);
                $this->db->where("CatchTeamID", $Rows['TeamID']);
                $this->db->where("ContestID", $Rows['ContestID']);
                $this->db->group_by("CatchTeamID");
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $WireHistory = $Query->result_array();
                    $Query = $this->db->query("SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`, `M`.`MatchStartDateTime`, `E`.`StatusID`
                                        FROM `sports_matches` `M`, `tbl_entity` `E`
                                        WHERE `E`.`StatusID` = 1
                                        AND `M`.`MatchStartDateTime` > '" . $CurrentDate . "'
                                        AND ( `M`.`TeamIDLocal` = '" . $Rows['TeamID'] . "'
                                        OR `M`.`TeamIDVisitor` = '" . $Rows['TeamID'] . "')
                                         LIMIT 1");
                    $Matches = ($Query->num_rows() > 0) ? $Query->row_array() : false;
                    if ($Query->num_rows() > 0) {
                        $Matches = $Query->row_array();
                        $MatchStartDateTime = $Matches['MatchStartDateTime'];
                        $Hours = round((strtotime($MatchStartDateTime) - strtotime($CurrentDate)) / 3600);
                        if ($Hours >= 24) {
                            /** to process team awarded * */
                            foreach ($WireHistory as $WireTeam) {
                                $WeekID = $WireTeam['WeekID'];
                                /** get contest upcoming week start * */
                                $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
                                $this->db->from('sports_contest C,tbl_entity E');
                                $this->db->where("E.EntityID", "C.ContestID", FALSE);
                                $this->db->where("C.AuctionStatusID", 5);
                                $this->db->where("$WeekID BETWEEN `WeekStart` AND `WeekEnd`");
                                $this->db->where("C.ContestID", $WireTeam['ContestID']);
                                $this->db->where("E.GameSportsType", "Nfl");
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Contests = $Query->row_array();
                                    if (!empty($Contests)) {
                                        if ($Contests['WeekStart'] == $WeekID) {
                                            /** apply waiver wire * */
                                            $this->applyWaiverWireActionDraftPosition($WeekID, $Contests['ContestID']);
                                        } else {
                                            /** point wise check * */
                                            $this->db->select("H.CatchTeamID,COUNT(H.CatchTeamID) as Num");
                                            $this->db->from('sports_wire_history H');
                                            $this->db->where("H.WeekID", $WeekID);
                                            $this->db->where("H.ContestID", $Contests['ContestID']);
                                            $this->db->where_in("H.StatusID", array(3, 5));
                                            $this->db->having("Num > 1");
                                            $this->db->group_by("H.CatchTeamID");
                                            $Query = $this->db->get();
                                            if ($Query->num_rows() > 0) {
                                                /** apply waiver wire * */
                                                $this->applyWaiverWireActionDraftPosition($WeekID, $Contests['ContestID']);
                                            } else {
                                                /** apply waiver wire * */
                                                $this->applyWaiverWireActionWeekRank($WeekID, $Contests['ContestID']);
                                            }
                                            /** get all request users by week * */
                                        }
                                    }
                                }
                            }
                        } else {
                            /** to set hold date time next week on catcher team * */
                        }
                    }
                } else {
                    /** add to free agent team* */
                    $UpdateData = array(
                        "PlayerStatus" => "Upcoming",
                        "IsTeamHold" => "No",
                        "DateTime" => null
                    );
                    $this->db->where('ContestID', $Rows['ContestID']);
                    $this->db->where('TeamID', $Rows['TeamID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                }
            }
        }
    }

    function nflTeamDraftWaiverWireRequestOLD() {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $CurrentDate = date('Y-m-d');

        /** get upcoming week * */
        $this->db->select('M.MatchID,M.WeekID,M.MatchStartDateTime,CONVERT_TZ(M.MatchStartDateTime,"+00:00","+04:00") AS MatchDateUTC,E.StatusID');
        $this->db->from('sports_matches M,tbl_entity E');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("DATE(M.MatchStartDateTime) >=", $CurrentDate);
        $this->db->where("E.GameSportsType", "Nfl");
        $this->db->order_by("M.MatchStartDateTime", "ASC");
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Match = $Query->row_array();
            $WeekID = $Match['WeekID'];
            /** get contest upcoming week start * */
            $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
            $this->db->from('sports_contest C,tbl_entity E');
            $this->db->where("E.EntityID", "C.ContestID", FALSE);
            $this->db->where("C.AuctionStatusID", 5);
            $this->db->where("$WeekID BETWEEN `WeekStart` AND `WeekEnd`");
            $this->db->where("E.GameSportsType", "Nfl");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $ContestsUsers = $Query->result_array();
                foreach ($ContestsUsers as $Value) {
                    if ($Value['WeekStart'] == $WeekID) {
                        /** apply waiver wire * */
                        $this->applyWaiverWireActionDraftPosition($WeekID, $Value['ContestID']);
                    } else {
                        /** point wise check * */
                        $this->db->select("H.CatchTeamID,COUNT(H.CatchTeamID) as Num");
                        $this->db->from('sports_wire_history H');
                        $this->db->where("H.WeekID", $WeekID);
                        $this->db->where("H.ContestID", $Value['ContestID']);
                        $this->db->where_in("H.StatusID", array(3, 5));
                        $this->db->having("Num > 1");
                        $this->db->group_by("H.CatchTeamID");
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            /** apply waiver wire * */
                            $this->applyWaiverWireActionDraftPosition($WeekID, $Value['ContestID']);
                        } else {
                            /** apply waiver wire * */
                            $this->applyWaiverWireActionWeekRank($WeekID, $Value['ContestID']);
                        }
                        /** get all request users by week * */
                    }
                }
            }
        }
    }

    /*
      Description: To remove hold team if team don't have request and 24 hours complete
     */

    function resetHoldTeam($ContestsUsers) {
        foreach ($ContestsUsers as $Value) {
            $CurrentDateTime = date('Y-m-d H:i:s');
            /** check catch team request by team id * */
            $this->db->select("APS.ContestID,APS.TeamID,APS.IsTeamHold");
            $this->db->from('tbl_auction_player_bid_status APS');
            $this->db->where("APS.ContestID", $Value['ContestID']);
            $this->db->where("APS.IsTeamHold", "Yes");
            $this->db->where("APS.DateTime <=", $CurrentDateTime);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $CatchTeamRequest = $Query->result_array();
                foreach ($CatchTeamRequest as $Row) {
                    /** point wise check * */
                    $this->db->select("H.CatchTeamID");
                    $this->db->from('sports_wire_history H');
                    $this->db->where("H.CatchTeamID", $Row['TeamID']);
                    $this->db->where("H.ContestID", $Row['ContestID']);
                    $this->db->where("H.StatusID", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() == 0) {
                        /** update catch player status on sold * */
                        $UpdateData = array(
                            "PlayerStatus" => "Upcoming",
                            "IsTeamHold" => "No",
                            "DateTime" => null
                        );
                        $this->db->where('ContestID', $Row['ContestID']);
                        $this->db->where('TeamID', $Row['TeamID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                    }
                }
            }
        }
        return true;
    }

    /*
      Description: To Auto Draft NCAAF Team Waiver Wire Request
     */

    function ncaafTeamDraftWaiverWireRequest() {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $CurrentDate = date('Y-m-d H:i:s');

        /** check catch team request by team id * */
        $this->db->select("APS.ContestID,APS.TeamID,APS.IsTeamHold,APS.SeriesID,APS.DateTime");
        $this->db->from('tbl_auction_player_bid_status APS');
        $this->db->where("APS.IsTeamHold", "Yes");
        $this->db->where("APS.DateTime <=", $CurrentDate);
        $this->db->order_by("APS.DateTime", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $CatchTeamRequest = $Query->result_array();
            foreach ($CatchTeamRequest as $Rows) {
                /** check team in wire history * */
                $this->db->select("ContestID,CatchTeamID,DropTeamID,UserTeamID,WeekID,StatusID");
                $this->db->from('sports_wire_history');
                $this->db->where("Type", "Wire");
                $this->db->where("StatusID", 1);
                $this->db->where("CatchTeamID", $Rows['TeamID']);
                $this->db->where("ContestID", $Rows['ContestID']);
                $this->db->group_by("CatchTeamID");
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $WireHistory = $Query->result_array();
                    $Query = $this->db->query("SELECT `M`.`MatchID`, `M`.`TeamIDLocal`, `M`.`TeamIDVisitor`, `M`.`MatchStartDateTime`, `E`.`StatusID`
                                        FROM `sports_matches` `M`, `tbl_entity` `E`
                                        WHERE `E`.`StatusID` = 1
                                        AND `M`.`MatchStartDateTime` > '" . $CurrentDate . "'
                                        AND ( `M`.`TeamIDLocal` = '" . $Rows['TeamID'] . "'
                                        OR `M`.`TeamIDVisitor` = '" . $Rows['TeamID'] . "')
                                         LIMIT 1");
                    $Matches = ($Query->num_rows() > 0) ? $Query->row_array() : false;
                    if ($Query->num_rows() > 0) {
                        $Matches = $Query->row_array();
                        $MatchStartDateTime = $Matches['MatchStartDateTime'];
                        $Hours = round((strtotime($MatchStartDateTime) - strtotime($CurrentDate)) / 3600);
                        if ($Hours >= 24) {
                            /** to process team awarded * */
                            foreach ($WireHistory as $WireTeam) {
                                $WeekID = $WireTeam['WeekID'];
                                /** get contest upcoming week start * */
                                $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
                                $this->db->from('sports_contest C,tbl_entity E');
                                $this->db->where("E.EntityID", "C.ContestID", FALSE);
                                $this->db->where("C.AuctionStatusID", 5);
                                $this->db->where("$WeekID BETWEEN `WeekStart` AND `WeekEnd`");
                                $this->db->where("C.ContestID", $WireTeam['ContestID']);
                                $this->db->where("E.GameSportsType", "Ncaaf");
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Contests = $Query->row_array();
                                    if (!empty($Contests)) {
                                        if ($Contests['WeekStart'] == $WeekID) {
                                            /** apply waiver wire * */
                                            $this->applyWaiverWireActionDraftPosition($WeekID, $Contests['ContestID']);
                                        } else {
                                            /** point wise check * */
                                            $this->db->select("H.CatchTeamID,COUNT(H.CatchTeamID) as Num");
                                            $this->db->from('sports_wire_history H');
                                            $this->db->where("H.WeekID", $WeekID);
                                            $this->db->where("H.ContestID", $Contests['ContestID']);
                                            $this->db->where_in("H.StatusID", array(3, 5));
                                            $this->db->having("Num > 1");
                                            $this->db->group_by("H.CatchTeamID");
                                            $Query = $this->db->get();
                                            if ($Query->num_rows() > 0) {
                                                /** apply waiver wire * */
                                                $this->applyWaiverWireActionDraftPosition($WeekID, $Contests['ContestID']);
                                            } else {
                                                /** apply waiver wire * */
                                                $this->applyWaiverWireActionWeekRank($WeekID, $Contests['ContestID']);
                                            }
                                            /** get all request users by week * */
                                        }
                                    }
                                }
                            }
                        } else {
                            /** to set hold date time next week on catcher team * */
                        }
                    }
                } else {
                    /** add to free agent team* */
                    $UpdateData = array(
                        "PlayerStatus" => "Upcoming",
                        "IsTeamHold" => "No",
                        "DateTime" => null
                    );
                    $this->db->where('ContestID', $Rows['ContestID']);
                    $this->db->where('TeamID', $Rows['TeamID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                }
            }
        }
    }

    function ncaafTeamDraftWaiverWireRequestOLD() {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $LeagueJoinDateTime = strtotime(date("Y-m-d H:i:s")) + 3600;
        $LeagueJoinDateTime = date('Y-m-d H:i:s', $LeagueJoinDateTime);
        $CurrentDate = date('Y-m-d');

        /** get upcoming week * */
        $this->db->select('M.MatchID,M.WeekID,M.MatchStartDateTime,CONVERT_TZ(M.MatchStartDateTime,"+00:00","+04:00") AS MatchDateUTC,E.StatusID');
        $this->db->from('sports_matches M,tbl_entity E');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("DATE(M.MatchStartDateTime) >=", $CurrentDate);
        $this->db->where("E.GameSportsType", "Ncaaf");
        $this->db->order_by("M.MatchStartDateTime", "ASC");
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Match = $Query->row_array();
            $WeekID = $Match['WeekID'];
            /** get contest upcoming week start * */
            $this->db->select('C.SeriesID,C.GameType,C.SubGameType,C.ContestID,C.WeekStart,C.WeekEnd');
            $this->db->from('sports_contest C,tbl_entity E');
            $this->db->where("E.EntityID", "C.ContestID", FALSE);
            $this->db->where("C.AuctionStatusID", 5);
            $this->db->where("$WeekID BETWEEN `WeekStart` AND `WeekEnd`");
            $this->db->where("E.GameSportsType", "Ncaaf");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $ContestsUsers = $Query->result_array();
                foreach ($ContestsUsers as $Value) {
                    if ($Value['WeekStart'] == $WeekID) {
                        /** apply waiver wire * */
                        $this->applyWaiverWireActionDraftPosition($WeekID, $Value['ContestID']);
                    } else {
                        /** point wise check * */
                        $this->db->select("H.CatchTeamID,COUNT(H.CatchTeamID) as Num");
                        $this->db->from('sports_wire_history H');
                        $this->db->where("H.WeekID", $WeekID);
                        $this->db->where("H.ContestID", $Value['ContestID']);
                        $this->db->where_in("H.StatusID", array(3, 5));
                        $this->db->having("Num > 1");
                        $this->db->group_by("H.CatchTeamID");
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            /** apply waiver wire * */
                            $this->applyWaiverWireActionDraftPosition($WeekID, $Value['ContestID']);
                        } else {
                            /** apply waiver wire * */
                            $this->applyWaiverWireActionWeekRank($WeekID, $Value['ContestID']);
                        }
                        /** get all request users by week * */
                    }
                }
            }
        }
    }

    /*
      Description: Update user status.
     */

    function InviteContest($Input = array(), $ContestID, $SessionUserID) {

        $Insert = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "EmailPhone" => (!empty($Input['Email'])) ? $Input['Email'] : $Input['Phone'],
            "DateTime" => date('Y-m-d H:i:s'),
        );
        $this->db->insert('tbl_contest_invite_friends', $Insert);

        $UserData = $this->Users_model->getUsers('FirstName', array('UserID' => $SessionUserID));
        $ContestData = $this->getContests('ContestID,SubGameType,LeagueJoinDateTime,EntryFee', array('ContestID' => $ContestID));

        $UserInvitationCode = $Input['UserInvitationCode'];
        if (!empty($Input['Email'])) {
            /* Send referral Email to User with referral url */
            send_mail(array(
                'emailTo' => $Input['Email'],
                'template_id' => 'd-c51b06b02dff433694abe37a8bf1bea6',
                'Subject' => 'Contest Invitation - ' . SITE_NAME,
                "Name" => $UserData['FirstName'],
                "InviteCode" => $UserInvitationCode,
                "Message" => $ContestData['SubGameType'],
                "EmailText" => "The draft date is on: " . $ContestData['LeagueJoinDateTime'] . " EST. The entry fee is $" . $ContestData['EntryFee'],
            ));
        } else if (!empty(($Input['Phone']))) {
            /* Send referral SMS to User with referral url */
            $this->Utility_model->sendMobileSMS(array(
                'PhoneNumber' => $Input['Phone'],
                'Text' => "Play with me on Stat Action Sports. Click " . base_url() . " to login on portal and Use contest code " . $UserInvitationCode . " to join contest."
            ));
        }
    }

    function getContestInvite($ContestID, $SessionUserID) {

        ini_set('max_execution_time', 300);
        $Invite = array();
        /** get upcoming week * */
        $this->db->select('EmailPhone,CONVERT_TZ(DateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS DateTime');
        $this->db->from('tbl_contest_invite_friends');
        $this->db->where("UserID", $SessionUserID);
        $this->db->where("ContestID", $ContestID);
        $this->db->order_by("DateTime", "DESC");
        // $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Invite = $Query->result_array();
        }
        return $Invite;
    }

    function getWeekDate($SeriesID, $WeekID) {

        ini_set('max_execution_time', 300);
        $Invite = array();
        /** get upcoming week * */
        $this->db->select("DATE_FORMAT(MatchStartDateTime,'%y-%m-%d') MatchStartDateTime");
        $this->db->from('sports_matches');
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("WeekID", $WeekID);
        $this->db->order_by("MatchStartDateTime", "ASC");
        $this->db->group_by('date(MatchStartDateTime)');
        // $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Invite = $Query->result_array();
        }
        return $Invite;
    }

    function getCurrentWeek($SeriesID='') {

        $Invite = array();
        /** get upcoming week * */
        $this->db->select("M.WeekID");
        $this->db->from('sports_matches M,tbl_entity E');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        if(!empty($SeriesID)){
          $this->db->where("SeriesID", $SeriesID);   
        }
        $this->db->where("E.StatusID", 1);
        //$this->db->where("date(M.MatchStartDateTime) <=", date('Y-m-d'));
        $this->db->order_by("M.MatchStartDateTime", "ASC");
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Invite = $Query->row_array();
        }
        return $Invite;
    }

    function getCurrentMatchLast($SeriesID='') {

        $Invite = array();
        /** get upcoming match * */
        $this->db->select("MatchID");
        $this->db->from('nba_sports_matches');
        if(!empty($SeriesID)){
          $this->db->where("SeriesID", $SeriesID);   
        }
        $this->db->where("date(MatchStartDateTime) <", date('Y-m-d'));
        $this->db->order_by("MatchStartDateTime", "DESC");
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Invite = $Query->row_array();
        }
        return $Invite;
    }

    /*
      Description: request free agent team for change
     */

    function removeDraftPlayer($ContestID, $SessionUserID, $UserTeamID, $PlayerID, $PlayerSelectTypeRole, $MatchGUID='') {

            //echo $ContestID.'-'.$SessionUserID.'-'.$UserTeamID.'-'.$PlayerID;exit;
            $Return['Status'] = 0;
            $Return['Message'] = "Error";

            /** check catch team id availabe or not * */
            $this->db->select("U.UserTeamID");
            $this->db->from('nba_sports_users_teams U,nba_sports_users_team_players UTP');
            $this->db->where('UTP.UserTeamID','U.UserTeamID', FALSE);
            $this->db->where("U.ContestID", $ContestID);
            $this->db->where("U.UserID", $SessionUserID);
            $this->db->where("U.UserTeamID", $UserTeamID);
            $this->db->where("UTP.PlayerID", $PlayerID);
            $Query = $this->db->get();
            if ($Query->num_rows() <= 0) {
                $Return['Message'] = "Player not exists";
                return $Return;
            }
            /** check catch team id availabe or not * */
            $this->db->select("ContestID,ContestDuration,DailyDate");
            $this->db->from('nba_sports_contest');
            $this->db->where("ContestID", $ContestID);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
               $Contest = $Query->row_array();
               // print_r($Contest);die;
                if($Contest['ContestDuration'] == 'Daily'){
                    $Query = $this->db->query("SELECT `M`.`MatchID`
                                                FROM `nba_sports_matches` `M`
                                                WHERE `M`.`MatchGUID` = '" . $MatchGUID . "' AND DATE(MatchStartDateTime) <='" . date('Y-m-d') . "'
                                                 LIMIT 1");
                    if($Query->num_rows() > 0){
                        $M = $Query->row_array();
                        $this->db->select("EntityID");
                        $this->db->from('tbl_entity');
                        $this->db->where("EntityID", $M['MatchID']);
                        $this->db->where_in("StatusID", array(2,5));
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Return['Message'] = "Player already played in this match.";
                            return $Return;
                        }
                    }
               }else{
                    $Query = $this->db->query("SELECT `M`.`MatchID`
                                                FROM `nba_sports_matches` `M`
                                                WHERE `M`.`MatchGUID` = '" . $MatchGUID . "' AND DATE(MatchStartDateTime) <='" . date('Y-m-d') . "'
                                                 LIMIT 1");
                    if($Query->num_rows() > 0){
                        $M = $Query->row_array();
                        $this->db->select("EntityID");
                        $this->db->from('tbl_entity');
                        $this->db->where("EntityID", $M['MatchID']);
                        $this->db->where_in("StatusID", array(2,5));
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Return['Message'] = "Player already played in this match.";
                            return $Return;
                        }
                    }
                }
                /** add wire transaction * */
                $WireHistory = array(
                    'ContestID' => $ContestID,
                    'UserID' => $SessionUserID,
                    'UserTeamID' => $UserTeamID,
                    'PlayerID' => $PlayerID,
                    'Type' => "DROP",
                    'PlayerSelectTypeRole' => $PlayerSelectTypeRole,
                    'DateTime' => date('Y-m-d H:i:s')
                );
                $Insert = $this->db->insert('nba_sports_wire_history', $WireHistory);

                /** add wire transaction * */
                $this->db->where('UserTeamID', $UserTeamID);
                $this->db->where('PlayerID', $PlayerID);
                $this->db->limit(1);
                $this->db->delete('nba_sports_users_team_players');

                /** add to free agent team* */
                // $UpdateData = array(
                //     "PlayerStatus" => "Upcoming",
                // );
                // $this->db->where('ContestID', $ContestID);
                // $this->db->where('PlayerID', $PlayerID);
                // $this->db->limit(1);
                // $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                $Return['Status'] = 1;
                $Return['Message'] = "Player successfully dropped";
                return $Return;

            }else{
                return $Return;
            }
    }

        /*
      Description: request free agent team for change
     */

    function addDraftPlayer($ContestID, $SessionUserID, $UserTeamID, $PlayerID, $PlayerSelectTypeRole,$MatchGUID='') {

            //echo $ContestID.'-'.$SessionUserID.'-'.$UserTeamID.'-'.$PlayerID;exit;
            $Return['Status'] = 0;
            $Return['Message'] = "Error";

            /** check catch team id availabe or not * */
            $this->db->select("PlayerID");
            $this->db->from('nba_tbl_auction_player_bid_status');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("PlayerID", $PlayerID);
            $this->db->where("PlayerStatus", "Upcoming");
            $Query = $this->db->get();
            if ($Query->num_rows() <= 0) {
                $Return['Message'] = "This player already sold to another team";
                return $Return;
            }
            /** check catch team id availabe or not * */
            $this->db->select("ContestID,ContestDuration,DailyDate,SeriesID,RosterSize,DraftPlayerSelectionCriteria");
            $this->db->from('nba_sports_contest');
            $this->db->where("ContestID", $ContestID);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Contest = $Query->row_array();
                $RoosterSize = $Contest['RosterSize'];
                $DraftPlayerSelectionCriteria = json_decode($Contest['DraftPlayerSelectionCriteria'],true);

                $this->db->select("UTP.PlayerID,UTP.PlayerSelectTypeRole PlayerRole");
                $this->db->from('nba_sports_users_team_players UTP');
                $this->db->where("UTP.UserTeamID", $UserTeamID);
                $Query = $this->db->get();
                $Rows = $Query->num_rows();
                if ($Rows > 0) {
                     $DraftSquad = $Query->result_array();
                     $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));
                     if ($Rows >= $RoosterSize) {
                        $Return['Message'] = "Team Rooster length can't exceed $RoosterSize";
                        return $Return;
                     }
                     if ($PlayerSelectTypeRole == "PG") {
                        if (@$PlayerRoles['PG'] >= $DraftPlayerSelectionCriteria['PG']) {
                            $Return['Message'] = "Minimum Criteria for PG is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                     }

                    if ($PlayerSelectTypeRole == "SG") {
                        if (@$PlayerRoles['SG'] >= $DraftPlayerSelectionCriteria['SG']) {
                            $Return['Message'] = "Minimum Criteria for SG is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                     }

                    if ($PlayerSelectTypeRole == "SF") {
                        if (@$PlayerRoles['SF'] >= $DraftPlayerSelectionCriteria['SF']) {
                            $Return['Message'] = "Minimum Criteria for SF is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                     }

                    if ($PlayerSelectTypeRole == "PF") {
                        if (@$PlayerRoles['PF'] >= $DraftPlayerSelectionCriteria['PF']) {
                            $Return['Message'] = "Minimum Criteria for PF is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                    }
                    if ($PlayerSelectTypeRole == "C") {
                        if (@$PlayerRoles['C'] >= $DraftPlayerSelectionCriteria['C']) {
                            $Return['Message'] = "Minimum Criteria for C is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                    }

                    if ($PlayerSelectTypeRole == "FLEX") {
                        if (@$PlayerRoles['FLEX'] >= $DraftPlayerSelectionCriteria['FLEX']) {
                            $Return['Message'] = "Minimum Criteria for FLEX is fulfilled. Please select player for another position will you complete the minimum criteria of '".$RoosterSize."' Roosters";
                            return $Return;
                        }
                    }

                }
                if($Contest['ContestDuration'] == 'Daily'){
                    $Query = $this->db->query("SELECT `M`.`MatchID`
                                                FROM `nba_sports_matches` `M`
                                                WHERE  `M`.`MatchGUID`= '".$MatchGUID."' AND DATE(MatchStartDateTime) <='" . date('Y-m-d') . "'
                                                 LIMIT 1");
                    if($Query->num_rows() > 0){
                        $M = $Query->row_array();
                        $this->db->select("EntityID");
                        $this->db->from('tbl_entity');
                        $this->db->where("EntityID", $M['MatchID']);
                        $this->db->where_in("StatusID", array(2,5));
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Return['Message'] = "Player already played in this match.";
                            return $Return;
                        }
                    }
                }else{
                    $Query = $this->db->query("SELECT `M`.`MatchID`
                                                FROM `nba_sports_matches` `M`
                                                WHERE `M`.`MatchGUID` = '" . $MatchGUID . "' AND DATE(MatchStartDateTime) <='" . date('Y-m-d') . "'
                                                 LIMIT 1");
                    if($Query->num_rows() > 0){
                        $M = $Query->row_array();
                        $this->db->select("EntityID");
                        $this->db->from('tbl_entity');
                        $this->db->where("EntityID", $M['MatchID']);
                        $this->db->where_in("StatusID", array(2,5));
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Return['Message'] = "Player already played in this match.";
                            return $Return;
                        }
                    }
                }
                /** add wire transaction * */
                $WireHistory = array(
                    'ContestID' => $ContestID,
                    'UserID' => $SessionUserID,
                    'UserTeamID' => $UserTeamID,
                    'PlayerID' => $PlayerID,
                    'Type' => "ADD",
                    'PlayerSelectTypeRole' => $PlayerSelectTypeRole,
                    'DateTime' => date('Y-m-d H:i:s')
                );
                $Insert = $this->db->insert('nba_sports_wire_history', $WireHistory);

                $WireHistory = array(
                    'UserTeamID' => $UserTeamID,
                    'PlayerID' => $PlayerID,
                    'SeriesID' => $Contest['SeriesID'],
                    'PlayerPosition' => "Player",
                    "TeamPlayingStatus"=> "Play",
                    'PlayerSelectTypeRole' => $PlayerSelectTypeRole,
                    'DateTime' => date('Y-m-d H:i:s')
                );
                $Insert = $this->db->insert('nba_sports_users_team_players', $WireHistory);

                /** add to free agent team* */
                $UpdateData = array(
                    "PlayerStatus" => "Sold",
                );
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $PlayerID);
                $this->db->limit(1);
                $this->db->update('nba_tbl_auction_player_bid_status', $UpdateData);
                $Return['Status'] = 1;
                $Return['Message'] = "Player successfully add.";
                return $Return;

            }else{
                return $Return;
            }
    }

        /*
      Description: ADD user team
     */

    function addUserTeamDraft($SessionUserID, $SeriesID, $ContestID, $StatusID = 2) {

        if(empty($SessionUserID)){
            return array();
        }

        $Query = $this->db->query('SELECT MatchID FROM nba_sports_contest WHERE ContestID = "' . $ContestID . '" LIMIT 1');
        $MatchID = $Query->row()->MatchID;

        /** check catch team id availabe or not * */
        $this->db->select("UserTeamGUID,IsAssistant,UserTeamName,AuctionTopPlayerSubmitted");
        $this->db->from('nba_sports_users_teams');
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("UserID", $SessionUserID);
        $this->db->where("IsPreTeam", "No");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            return $Query->row_array();
        }

        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $SessionUserID, "StatusID" => $StatusID,"GameSportsType" => 'Nba'));
        /* Add user team to user team table . */
        $teamName = "PostSnakeTeam 1";
        $InsertData = array(
            "UserTeamID" => $EntityID,
            "UserTeamGUID" => $EntityGUID,
            "UserID" => $SessionUserID,
            "UserTeamName" => $teamName,
            "UserTeamType" => 'Draft',
            "IsPreTeam" => 'No',
            "SeriesID" => @$SeriesID,
            "ContestID" => @$ContestID,
            "IsAssistant" => "No",
            "MatchID" => $MatchID
        );
        $this->db->insert('nba_sports_users_teams', $InsertData);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        $InsertData = array(
            "UserTeamGUID" => $EntityGUID,
            "UserTeamName" => $teamName,
            "AuctionTopPlayerSubmitted" => 'Yes',
            "IsAssistant" => "No"
        );
        return $InsertData;
    }

    /*
      Description: auto Create SeasonLong Weekly Contest
    */
    function autoCreateSeasonLongWeeklyDraft() {
        /*Get Running Contest */ 
        $this->db->select("SeriesID,ContestID,WeekStart as WeekID,LeagueJoinDateTime");
        $this->db->from('tbl_entity as E, sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 2);
        $Query = $this->db->get();
        $getRunningContest = $Query->result_array();
      
        if (!empty($getRunningContest)) {
            foreach ($getRunningContest as $key => $RunContVal) {
                /*Get Complete Matches According to week */ 
                if($RunContVal['WeekID'] == 20){
                    $CompleteMatchData = $this->Sports_football_model->getMatches('MatchID,Status,WeekID,MatchStartDateTime', array('WeekID' => $RunContVal['WeekID'], 'StatusID' => array(1,2), 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'), FALSE, '', '');
                    if (empty($CompleteMatchData)) {
                        $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE E.StatusID = 2 AND  E.EntityID = ' . $RunContVal['ContestID']);
                        continue;
                    }  
                }
                $CompleteMatchData = $this->Sports_football_model->getMatches('MatchID,Status,WeekID,MatchStartDateTime', array('WeekID' => $RunContVal['WeekID'], 'StatusID' => array(1,2), 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'), FALSE, '', '');
                if (!empty($CompleteMatchData)) {
                     continue;
                }
                $WeekID = $RunContVal['WeekID']+1;
                $Query = $this->db->query("SELECT TeamIDLocal,TeamIDVisitor FROM sports_matches WHERE WeekID = '" . $WeekID . "'");
                $TeamData = ($Query->num_rows() > 0) ? $Query->result_array() : false;
                $TeamIDLocal = array_column($TeamData,'TeamIDLocal');
                $TeamIDVisitor = array_column($TeamData,'TeamIDVisitor');
                $AllTeam = array_unique(array_merge($TeamIDLocal,$TeamIDVisitor));
                if(empty($AllTeam)) continue;
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
                if ($Query->num_rows() == 0) {
                   continue;
                }

                $LeagueJoinDateTime = date('Y-m-d H:i:s', strtotime($RunContVal['LeagueJoinDateTime'] . ' +7 day'));
                /* Get User Joined Contests Data */
                $JoinedContestUserData = $this->getJoinedContestsUsersWithTeam('TotalPoints,UserRank,UserTeamID,UserWinningAmount,UserID', array('ContestID' => $RunContVal['ContestID'],'WeekID'=>$RunContVal['WeekID'],'OrderBy' => 'TotalPoints', 'Sequence' => 'DESC'), TRUE, '', '');
                $this->db->trans_start();
                $JoinedDataInsert   = [];
                $InsertTeamData     = [];
                $UserTeamID = "";
                //dump($JoinedContestUserData['Data']['Records']);
                if (!empty($JoinedContestUserData['Data']['Records'])) {
                    foreach ($JoinedContestUserData['Data']['Records'] as $key => $value) {
                        /**-- Set Draft Position According to last week users points  --**/ 
                        $this->db->set('JC.DraftUserPosition', $key+1);  
                        $this->db->set('JC.DraftUserLive', ($key == 0) ? 'Yes' : 'No');  
                        $this->db->where('JC.UserID', $value['UserID']);
                        $this->db->where('JC.ContestID', $RunContVal['ContestID']);
                        $this->db->update('sports_contest_join as JC');

                        $UserTeamID     = $value['UserTeamID'];
                        $UserTeamGUID   = $value['UserTeamGUID'];
                        $JoinedDataInsert[] = array_filter(array(
                            "ContestID"              => $RunContVal['ContestID'],
                            "UserID"                 => $value['UserID'],
                            "SeriesID"               => $RunContVal['SeriesID'],
                            "WeekID"                 => $RunContVal['WeekID'],
                            "WeekStartDate"          => $CompleteMatchData['MatchStartDateTime'],
                            "WeekEndDate"            => $CompleteMatchData['MatchStartDateTime'],
                            "UserTeamID"             => $UserTeamID,
                            "TotalPoints"            => $value['TotalPoints'],
                            "UserRank"               => $value['UserRank'],
                            "UserWinningAmount"      => $value['UserWinningAmount'],
                            "EntryDate"              => date('Y-m-d H:i:s'),
                        ));
                        
                        if(!empty($UserTeamID)){

                                                 $InsertTeamData[] = array_filter(array(
                            "UserTeamID"        => $UserTeamID,
                            "UserTeamGUID"      => $UserTeamGUID,
                            "UserID"            => $value['UserID'],
                            "UserTeamName"      => 'PostSnakeTeam '.$RunContVal['WeekID'],
                            "WeekID"            => $RunContVal['WeekID'],
                            "UserTeamType"      => 'Draft',
                            "SeriesID"          => $RunContVal['SeriesID'],
                            "ContestID"         => $RunContVal['ContestID'],
                            "IsPreTeam"         => 'No',
                            "IsAssistant"       => 'No',
                            "AuctionTopPlayerSubmitted" => 'Yes',
                            "TotalPoints"       => $value['TotalPoints'],
                            "Rank"              => $value['UserRank'],
                            "Win"               => 0,
                            "Loss"              => 0,
                            "Tie"               => 0
                        ));    
                        }


                        /**-- Player Data Insert--**/
                        $InsertPlayerData = []; 
                        if (!empty($value['UserTeamPlayers'])) {
                            foreach ($value['UserTeamPlayers'] as $PlayerVal) {
                                $InsertPlayerData[] = array(
                                    "UserTeamID"            => $UserTeamID,
                                    "PlayerPosition"        => 'Player',
                                    "Points"                => $PlayerVal['TotalPoints'],
                                    "SeriesID"              => $RunContVal['SeriesID'],
                                    "PlayerID"              => $PlayerVal['PlayerID'],
                                    "PlayerSelectTypeRole"  => $PlayerVal['PlayerSelectTypeRole'],
                                    "PointsData"  => $PlayerVal['PointsDataPrivate']
                                );                            
                            }
                            if(!empty($InsertPlayerData) && !empty($UserTeamID)){
                                  
                                $this->db->insert_batch('sports_users_team_players_weekly', $InsertPlayerData);
                            }
                        }

                    }
                    //dump($InsertTeamData);
                    if(!empty($InsertTeamData)){
                        $this->db->insert_batch('sports_users_teams_weekly', $InsertTeamData);
                    }
                     if(!empty($InsertTeamData)){
                        /**-- Delete Old Week User Team & Player Data --**/ 
                        $sql = "DELETE UT,UTP
                                FROM sports_users_teams as UT, sports_users_team_players as UTP 
                                WHERE UT.UserTeamID=UTP.UserTeamID 
                                AND UT.ContestID= ".$RunContVal['ContestID'];
                        $this->db->query($sql);

                        $sql = "DELETE UT
                                FROM sports_users_teams as UT
                                WHERE UT.ContestID= ".$RunContVal['ContestID'];
                        $this->db->query($sql);
                    }
                }

                /**-- Change Auction Status (Pending) Reset Draft --**/ 
                $this->db->set('C.WeekStart', 'C.WeekStart+1', FALSE);  
                $this->db->set('C.LeagueJoinDateTime', $LeagueJoinDateTime);
                $this->db->set('C.AuctionStatusID', '1');
                $this->db->set('C.DraftLiveRound', '1');
                $this->db->where('C.ContestID', $RunContVal['ContestID']);
                $this->db->update('sports_contest as C');

                $this->db->where('ContestID', $RunContVal['ContestID']);
                $this->db->where('SeriesID', $RunContVal['SeriesID']);
                $this->db->delete('tbl_auction_player_bid_status');

                $this->addAuctionPlayer($RunContVal['SeriesID'], $RunContVal['ContestID'], $RunContVal['WeekID']+1, 'SeasonLong', '');
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    return FALSE;
                }
            }//Running Contest Foreach End.

        } 
    }

        /*
      Description: auto Create SeasonLong Weekly Contest
    */
    function autoCompleteDaily() {

        /*Get Running Contest */ 
        $this->db->select("SeriesID,ContestID,DailyDate,ContestDuration");
        $this->db->from('tbl_entity as E, nba_sports_contest as C');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("C.ContestDuration !=", "SeasonLong");
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("E.StatusID", 2);
        $this->db->where("E.GameSportsType", 'Nba');
        $Query = $this->db->get();
        $getRunningContest = $Query->result_array();
        if (!empty($getRunningContest)) {
            foreach ($getRunningContest as $key => $RunContVal) {
                /*Get Complete Matches According to week */ 
                if($RunContVal['ContestDuration'] == 'Weekly'){
                    $CompleteMatchData = $this->Sports_model->getMatches('MatchID,Status,WeekID,MatchStartDateTime', array('WeekID' => $RunContVal['WeekID'], 'StatusID' => array(1,2), 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'), FALSE, '', '');
                    if (empty($CompleteMatchData)) {
                      
                        $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE E.StatusID = 2 AND  E.EntityID = ' . $RunContVal['ContestID']);

                    }  
                }else if($RunContVal['ContestDuration'] == 'Daily'){
                    if(strtotime($RunContVal['DailyDate']) < strtotime(date('Y-m-d'))){

                        $CompleteMatchData = $this->Sports_model->getMatches('MatchID,Status,MatchStartDateTime,MatchStartDateTimeEST', array('MatchStartDateTime'=>$RunContVal['DailyDate'], 'StatusID' => array(1,2), 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'), FALSE, '', '');
                        if (empty($CompleteMatchData)) {                          
                            $this->db->query('UPDATE nba_sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE E.StatusID = 2 AND   E.EntityID = ' . $RunContVal['ContestID']);
                        } 
                    }
                }else if($RunContVal['ContestDuration'] == 'SeasonLong'){
                    $CompleteMatchData = $this->Sports_football_model->getMatches('MatchID,Status,WeekID,MatchStartDateTime', array('WeekID' => $RunContVal['WeekEnd'], 'StatusID' => array(1,2), 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'DESC'), FALSE, '', '');
                    if (empty($CompleteMatchData)) {
                      
                        $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE E.StatusID = 2 AND C.ContestDuration="SeasonLong"  AND  E.EntityID = ' . $RunContVal['ContestID']);

                    }  
                }

            }

        }  
    }

}

?>