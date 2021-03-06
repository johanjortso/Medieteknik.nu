<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends MY_Controller
{

	public $languages = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();

		if(!$this->login->is_admin() )
		{
			redirect('/admin/admin/access_denied', 'refresh');
		}

		// access granted, loading modules
		$this->load->model('user_model');
		$this->load->helper('form');

		$this->languages = array(
								array(	'language_abbr' => 'se',
										'language_name' => 'Svenska',
										'id' => 1),
								array(	'language_abbr' => 'en',
										'language_name' => 'English',
										'id' => 2)
							);
    }

	public function index()
	{
		$this->overview();
	}

	function overview($method = 'page', $page = 1, $filter = 'all')
	{
		$this->load->model('User_model');
		$limit = 30;

		// search or no?
		if($method == 'search' && $this->input->get('q'))
		{
			$main_data['user_list'] = $this->User_model->search_user($this->input->get('q'));
			$main_data['query'] = $this->input->get('q');
		}
		else
		{
			$main_data['user_list'] = $this->User_model->get_all_users($limit, $page); // user data
		}

		// Data for overview view
		$main_data['notif'] = $this->User_model->admin_get_notifications(); // notif
		$main_data['lang'] = $this->lang_data;

		$main_data['user_limit'] = $limit;
		$main_data['user_count'] = $this->User_model->count_all_users();
		$main_data['user_page'] = $page;
		$main_data['user_method'] = $method;
		$main_data['user_filter'] = $filter;

		// load user list as var
		$main_data['list_users'] = $this->load->view('admin/user/list_users', $main_data, true);

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('admin/user/overview', $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	function edit($id, $message = '')
	{
		$this->load->model('User_model');

		if($this->input->post('save'))
		{
			$web = $this->input->post('web');
			$linkedin = $this->input->post('linkedin');
			$twitter = $this->input->post('twitter');
			$presentation = $this->input->post('presentation');
			$firstname = $this->input->post('firstname');
			$lastname = $this->input->post('lastname');
			$lukasid = $this->input->post('lukasid');
			$gravatar = $this->input->post('gravatar');
			$github = $this->input->post('github');

			$privil = $this->input->post('admin_privil');

			if($this->login->has_privilege('superadmin'))
			{
				if($privil !== 0)
					$this->User_model->edit_user_privil($id, $privil);
				else
					$this->User_model->remove_user_privil($id);
			}

			if($this->User_model->edit_user_data($id, $web, $linkedin, $twitter, $presentation, $gravatar, $github)
				&& $this->User_model->edit_user($id, $firstname, $lastname, $lukasid))
				redirect('admin/user/edit/'.$id.'/edit_done', 'location');
			else
				redirect('admin/user/edit/'.$id.'/error', 'location');
		}
		elseif($this->input->post('disable'))
		{
			$main_data['chstatus'] = $this->User_model->disableswitch($id);
			redirect('admin/user/edit/'.$id.'/disable');
		}
		elseif($this->input->post('activate'))
		{
			$main_data['chstatus'] = $this->User_model->enable($id);
			redirect('admin/user/edit/'.$id.'/enabled');
		}

		// Data for overview view
		$main_data['user'] = $this->User_model->get_user_profile($id);
		$main_data['user_privil'] = $this->User_model->get_user_privileges($id);
		$main_data['privil'] = $this->User_model->get_all_privileges($id);
		$main_data['lang'] = $this->lang_data;
		$main_data['message'] = $message;

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('admin/user/edit',  $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	function add($do = '')
	{
		$main_data['lang'] = $this->lang_data;
		$this->load->model('User_model');

		if($do == 'create') // if form is sent
		{

			$fn = $this->input->post('firstname');
			$ln = $this->input->post('lastname');
			$lid = $this->input->post('lukasid');

			$main_data['entered'] = array(
										'fname' => $fn,
										'lname' => $ln,
										'lid' => $lid
									);

			$createuser = $this->User_model->add_user($fn, $ln, $lid);

			// pass along error messages
			if(!$createuser)
			{
				$errormsg = '';

				if(strlen(trim($fn)) == 0)
					$errormsg .= $this->lang_data['admin_addusers_error_fname'].' ';
				if(strlen(trim($ln)) == 0)
					$errormsg .= $this->lang_data['admin_addusers_error_lname'].' ';
				if(strlen(trim($lid)) !== 8 || $this->user_model->lukasid_exists($lid))
					$errormsg .= $this->lang_data['admin_addusers_error_lid'].' ';

				$main_data['errormsg'] = $errormsg;
			}

			$main_data['status'] = $createuser;
		}

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('admin/user/add',  $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

}
?>
