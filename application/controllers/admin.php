<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
class Admin extends MY_Controller {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		
		if(!$this->login->is_admin() && $this->uri->segment(2) != "access_denied") {
			redirect('/admin/access_denied', 'refresh');
		}
    }

	public function index()
	{
		$this->overview();
	}
	
	public function access_denied() {
		// Data for denied view
		$main_data['lang'] = $this->lang_data;

		// composing the views
		$this->load->view('includes/head', $this->lang_data);
		$this->load->view('includes/header', $this->lang_data);
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('admin/denied',  $main_data, true);					
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
		$this->load->view('includes/footer');
	}

	function overview() {

		// Data for overview view
		$this->load->model('User_model');
		$main_data['privileges'] = $this->User_model->get_user_privileges($this->login->get_id());
		$main_data['lang'] = $this->lang_data;

		// composing the views
		$this->load->view('includes/head', $this->lang_data);
		$this->load->view('includes/header', $this->lang_data);
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('admin/overview',  $main_data, true);					
		$template_data['sidebar_content'] = $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
		$this->load->view('includes/footer');
	}

}
