<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class News extends MY_Controller
{

	public function index()
	{
		$this->load->model('News_model');
		$this->load->model('Carousel_model');

		// Data for news view
		$main_data['news_array'] = $this->News_model->get_paged_news(1, 9);
		$main_data['lang'] = $this->lang_data;
		$main_data['carousel_array'] = $this->Carousel_model->get_carousel_items();

		// carousel view
		$template_data['carousel_content'] = $this->load->view('includes/carousel',$main_data, true);

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('news_index', $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	public function archive($dummy = 'page', $page = 1, $limit = 10)
	{
		// Data for news view
		$this->load->model('News_model');
		$main_data['news_array'] = $this->News_model->get_paged_news($page, $limit);
		$main_data['news_count'] = $this->News_model->get_news_count();
		$main_data['news_limit'] = $limit;
		$main_data['news_page'] = $page;
		$main_data['lang'] = $this->lang_data;

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('news', $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	public function view($id, $slug = '')
	{
		// Data for news view
		$this->load->model('News_model');
		$main_data['news'] = $this->News_model->get_news($id);
		$main_data['lang'] = $this->lang_data;

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('news_full', $main_data, true);
		$template_data['sidebar_content'] =  $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}
}
