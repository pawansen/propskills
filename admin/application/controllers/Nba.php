<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Nba extends Admin_Controller_Secure {
	
	/*------------------------------*/
	/*------------------------------*/	
	public function series()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/series.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/series.js', 
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/series/series_list');
		$this->load->view('includes/footer');
	}

	public function matches()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/matches.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/matches.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/matches/matches_list');
		$this->load->view('includes/footer');
	}

	public function players()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/players.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/players.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/players/players_list');
		$this->load->view('includes/footer');
	}

	public function teams()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/teams.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/teams.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/teams/teams_list');
		$this->load->view('includes/footer');
	}

	public function Privatecontests()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/Privatecontests.js',
			'asset/plugins/chosen/chosen.jquery.min.js'
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('user/privatecontests_list');
		$this->load->view('includes/footer');
	}

	public function auctionDrafts() {
        $load['css'] = array(
            'asset/plugins/chosen/chosen.min.css',
            'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
        );
        $load['js'] = array(
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
            'asset/plugins/chosen/chosen.jquery.min.js',
            'asset/plugins/jquery.form.js',
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
            'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
        );

        $this->load->view('includes/header', $load);
        $this->load->view('includes/menu');
        $this->load->view('nba/auctionDrafts/drafts_list');
        $this->load->view('includes/footer');
	}
	
	public function add() {
        $load['css'] = array(
            'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css',
			'asset/plugins/select2/css/select2.css'
        );
        $load['js'] = array(
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
            'asset/plugins/chosen/chosen.jquery.min.js',
            'asset/plugins/jquery.form.js',
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js',
			'asset/plugins/select2/js/select2.js',
        );

        $this->load->view('includes/header', $load);
        $this->load->view('includes/menu');
        $this->load->view('nba/auctionDrafts/add_form');
        $this->load->view('includes/footer');
    }
    
    public function edit() {
        $load['css'] = array(
            'asset/plugins/chosen/chosen.min.css',
            'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
        );
        $load['js'] = array(
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
            'asset/plugins/chosen/chosen.jquery.min.js',
            'asset/plugins/jquery.form.js',
            'asset/js/' . $this->ModuleData['ModuleName'] . '/auctionDrafts.js',
            'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
        );

        $this->load->view('includes/header', $load);
        $this->load->view('includes/menu');
        $this->load->view('nba/auctionDrafts/edit_form');
        $this->load->view('includes/footer');
    }

	public function winnings()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/winnings.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/winnings.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/winnings/winnings_list');
		$this->load->view('includes/footer');
	}

	public function pointsystem()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'/pointsystem.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'/pointsystem.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('nba/pointSystem/pointSystem_list');
		$this->load->view('includes/footer');
	}


}
