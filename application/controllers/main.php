<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	class Main extends CI_Controller {
		
		public function index()
		{
			// Header data
			$header_data['title'] = "Medietkenik Main page";
			//$header_data['css_screen'] = array('reset', 'basic', 'base');
			$header_data['container'] = true;
			
			// Menu data
			$this->lang->load('menu', $this->config->item('language'));
			$menu_data = $this->lang->language;

			// Data for Startsida view
			$startsida_data['data'] = "data";

			$this->load->view('includes/head',$header_data);
			$this->load->view('includes/header');
			$this->parser->parse('includes/menu',$menu_data);
			$this->load->view('startsida', $startsida_data);
			$this->load->view('includes/footer',$header_data);
		}
	}
