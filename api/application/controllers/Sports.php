<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sports extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
    }

    /*
      Description: To get series data
     */

    public function getSeries_post() {
        $SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Description: To get matches data
     */

    public function getMatches_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Today,Series,MyJoinedMatch]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Matches Data */
        $MatchesData = $this->Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => @$this->SeriesID, 'StatusID' => $this->StatusID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData['Data'];
        }
    }

    /*
      Description: To get match details
     */

    public function getMatch_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Match Data */

        $MatchDetails = $this->Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => $this->MatchID, 'StatusID' => @$this->StatusID)));
        if (!empty($MatchDetails)) {
            $this->Return['Data'] = $MatchDetails;
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayers_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $playersData = $this->Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID, 'MatchID' => @$this->MatchID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
        }
    }

    /*
      Description: To get player details
     */

    public function getPlayer_post() {
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Player Data */
        $PlayerDetails = $this->Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('PlayerID' => $this->PlayerID)));
        if (!empty($PlayerDetails)) {
            $this->Return['Data'] = $PlayerDetails;
        }
    }

    /*
      Description: To get teams
     */

    public function getTeams_post() {
        $TeamsData = $this->Sports_model->getTeams(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($TeamsData)) {
            $this->Return['Data'] = $TeamsData['Data'];
        }
    }

    /*
      Description: To get team
     */

    public function getTeam_post() {
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|required|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Match Data */
        $TeamDetails = $this->Sports_model->getTeams(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => $this->TeamID)));
        if (!empty($TeamDetails)) {
            $this->Return['Data'] = $TeamDetails;
        }
    }

    /*
      Description: To get sports points
     */

    public function getPoints_post() {
        $this->form_validation->set_rules('PointsCategory', 'PointsCategory', 'trim|in_list[Normal,InPlay,Reverse]');
        $this->form_validation->validation($this);  /* Run validation */

        $PointsData = $this->Sports_model->getPoints($this->Post);
        if (!empty($PointsData)) {
            $this->Return['Data'] = $PointsData['Data'];
        }
    }

    public function match_players_best_played_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */
        $BestTeamData = $this->Sports_model->match_players_best_played(array('MatchID' => $this->MatchID), FALSE);
        if (!empty($BestTeamData)) {
            $this->Return['Data'] = $BestTeamData['Data'];
        }
    }


    /*
      Description: To get sports best played players of the match
     */

    public function getMatchBestPlayers_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);
        $BestTeamData = $this->Sports_model->getMatchBestPlayers(array('MatchID' => $this->MatchID), FALSE);
        if (!empty($BestTeamData)) {
            $this->Return['Data'] = $BestTeamData['Data'];
        }
    }

}


?>
