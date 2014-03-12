<?php
class Carousel_model extends CI_Model
{

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

	function admin_get_carousel_item($id)
    {
		$this->db->select("carousel.id, language.language_name, language.language_abbr, carousel_translation.*");
		$this->db->from("carousel");
		$this->db->from("language");
		$this->db->join("carousel_translation", 'carousel_translation.carousel_id = carousel.id AND carousel_translation.lang_id = language.id', 'left');
		$this->db->where("carousel.id",$id);
		$query = $this->db->get();
		$translations = $query->result();

		$this->db->select("carousel.*");
		$this->db->select("COALESCE(carousel_order, 0) as carousel_order",false);
		$this->db->from("carousel");
		$this->db->where("carousel.id",$id);
		$this->db->order_by("carousel_order ASC, carousel.date DESC");
		$query = $this->db->get();
		$carousel_items_array = $query->result();
		$carousel = $carousel_items_array[0];

		$this->db->select("carousel_images.*, images.image_original_filename");
		$this->db->from("carousel");
		$this->db->where("carousel.id",$id);
		$this->db->join("carousel_images", 'carousel.id = carousel_images.carousel_id', 'left');
		$this->db->join("images", 'carousel_images.images_id = images.id', 'left');

		$query = $this->db->get();
		$photos = $query->result();

		$carousel->translations = array();
		$carousel->photos = array();
		foreach($translations as $t) {
			if($t->id == $carousel->id) {
				array_push($carousel->translations, $t);
			}
		}
		foreach($photos as $photo) {
			if($photo->carousel_id == $carousel->id) {
				array_push($carousel->photos, $photo);
			}
		}

		return $carousel;
	}

	function get_carousel_item_images($id){
		$this->db->select("carousel_images.*, images.image_original_filename");
		$this->db->from("carousel");
		$this->db->where("carousel.id",$id);
		$this->db->join("carousel_images", 'carousel.id = carousel_images.carousel_id', 'left');
		$this->db->join("images", 'carousel_images.images_id = images.id', 'left');

		$query = $this->db->get();
		$images = $query->result();

		return $images;
	}

    function move_carousel_item_order($id, $direction){
    	$this->db->where('id', $id);
		$query = $this->db->get('carousel');
		if($query->num_rows != 1){
			return false;
		}
		
		$result = $query->result();
		$item = $result[0];
		$order = $item->carousel_order;

		if(($order == 1 && $direction == 'up') || ($order == $this->get_number_of_carousel_items() && $direction == 'down'))
		{
			return false;
		}

		if($direction == 'up'){
			$offset = -1;
		}
		else if($direction == 'down'){
			$offset = 1;
		}
		else
			return false;

		$this->db->where('carousel_order', $order + $offset);
		$query = $this->db->get('carousel');
		if($query->num_rows != 1){
			return false;
		}
		else
		{
			$result = $query->result();
			$switchitem = $result[0];
			$switchid = $switchitem->id;
		}

		// Item to switch to get temporary order NULL
		$data = array(
            'carousel_order' => NULL,
        );
		$this->db->where('id', $switchid);
		$this->db->update('carousel', $data); 

		// Change first item
		$data = array(
            'carousel_order' => $order + $offset,
        );
		$this->db->where('id', $id);
		$this->db->update('carousel', $data); 

		// Change second item 
		$data = array(
            'carousel_order' => $order,
        );
		$this->db->where('id', $switchid);
		$this->db->update('carousel', $data); 

		// Items switched order!
		return true;
    }

    function get_number_of_carousel_items(){
    	$this->db->from('carousel');
		$query = $this->db->get();
		$rowcount = $query->num_rows();

		return $rowcount;
    }

	function get_all_carousel_items()
	{
		$this->db->select("carousel.id, language.language_name, language.language_abbr, carousel_translation.*");
		$this->db->from("carousel");
		$this->db->from("language");
		$this->db->join("carousel_translation", 'carousel_translation.carousel_id = carousel.id AND carousel_translation.lang_id = language.id', 'left');
		$query = $this->db->get();
		$translations = $query->result();

		$this->db->select("carousel.*");
		$this->db->select("COALESCE(carousel_order, 0) as carousel_order",false);
		$this->db->from("carousel");
		$this->db->order_by("carousel_order ASC, carousel.date DESC");
		$query = $this->db->get();
		$carousel_items_array = $query->result();

		$this->db->select("carousel_images.*, images.image_original_filename");
		$this->db->from("carousel");
		$this->db->join("carousel_images", 'carousel.id = carousel_images.carousel_id', 'left');
		$this->db->join("images", 'carousel_images.images_id = images.id', 'left');

		$query = $this->db->get();
		$photos = $query->result();

		foreach($carousel_items_array as $carousel_item) {
			$carousel_item->translations = array();
			$carousel_item->photos = array();
			foreach($translations as $t) {
				if($t->id == $carousel_item->id) {
					array_push($carousel_item->translations, $t);
				}
			}
			foreach($photos as $photo) {
				if($photo->carousel_id == $carousel_item->id) {
					array_push($carousel_item->photos, $photo);
				}
			}
		}
		return $carousel_items_array;
	}

	function add_carousel_item($user_id, $translations = array(), $carousel_type, $carousel_order, $disabled = 1)
	{
		if(!is_array($translations))
		{
			return false;
		}
		$arr_keys = array_keys($translations);
		if(!is_numeric($arr_keys[0]))
		{
			$theTranslations = array($translations);
		} else {
			$theTranslations = $translations;
		}
		foreach($theTranslations as &$translation)
		{
			$arr_keys = array_keys($translation);
			if((!in_array("lang_abbr",$arr_keys) && !in_array("lang",$arr_keys)) || !in_array("title",$arr_keys) || !in_array("content",$arr_keys)) {
				return false;
			}
			if(!in_array("lang_abbr",$arr_keys) && in_array("lang",$arr_keys)){
				$translation["lang_abbr"] = $translation["lang"];
			}
		}

		$this->db->where('id', $user_id);
		$query = $this->db->get('users');
		if($query->num_rows != 1)
		{
			return false;
		}

		$theTime = date("Y-m-d H:i:s", time());

		$this->db->trans_begin();

		$data = array(
		   'user_id' => $user_id,
		   'carousel_type' => $carousel_type,
		   'carousel_order' => $carousel_order,
		   'disabled' => $disabled,
		   'date' => $theTime,
		);
		$this->db->insert('carousel', $data);
		$carousel_id = $this->db->insert_id();

		$success = true;
		foreach($theTranslations as &$translation)
		{
			$lang_abbr = $translation["lang_abbr"];
			$title = $translation["title"];
			$content = $translation["content"];
			$theSuccess = $this->update_carousel_translation($carousel_id, $lang_abbr, $title, $content);
			if(!$theSuccess)
			{
				$success = $theSuccess;
			}

		}

		//if($use_transaction) {
			if ($this->db->trans_status() === FALSE || !$success)
			{
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
			}
		//}
		return $carousel_id;
	}

	function update_carousel_translation($carousel_id, $lang_abbr, $title, $content)
	{
		$theTitle = trim($title);
		$theContent = trim($content);

		// check if the carousel exists
		$this->db->where('id', $carousel_id);
		$query = $this->db->get('carousel');
		if($query->num_rows != 1)
		{
			return false;
		}

		// check if the language exists
		$this->db->where('language_abbr', $lang_abbr);
		$query = $this->db->get('language');
		if($query->num_rows != 1)
		{
			return false;
		}
		$lang_id = $query->result(); $lang_id = $lang_id[0]->id;

		// if both title and text is null then delete the translation
		if($theTitle == '' && $theContent == '')
		{
			$this->db->delete('carousel_translation', array('carousel_id' => $carousel_id, 'lang_id' => $lang_id));
			return true;
		}

		if($theTitle == '')
		{
			return false;
		}

		$query = $this->db->get_where('carousel_translation', array('carousel_id' => $carousel_id, 'lang_id' => $lang_id), 1, 0);
		if ($query->num_rows() == 0)
		{
			// A record does not exist, insert one.
			$data = array(	'carousel_id' 	=> $carousel_id,
							'lang_id' 	=> $lang_id,
							'title'		=> $theTitle,
							'content'		=> $theContent,
						);
			$query = $this->db->insert('carousel_translation', $data);
			// Check to see if the query actually performed correctly
			if ($this->db->affected_rows() > 0)
			{
				return TRUE;
			}
		} else {
			// A record does exist, update it.
			// update the translation, and if the texts have not been changed then dont update the last_edit field
			$theTime = date("Y-m-d H:i:s", time());
			$sql = 'UPDATE carousel_translation SET title = "'.$theTitle.'", content = "'.$theContent.'" WHERE carousel_id = "'.$carousel_id.'" AND lang_id = "'.$lang_id.'" ';
			$this->db->query($sql);
			return true;
		}
		return FALSE;
	}

	function add_carousel_image($carousel_id, $images_id, $photo, $link){
		$query = $this->db->get_where('carousel_images', array('carousel_id' => $carousel_id, 'images_id' => $images_id));
		if($query->num_rows() != 0)
			return false;
		else{
			$data = array(
				'carousel_id' => $carousel_id,
				'images_id' => $images_id,
				'photo' => $photo,
				'link' => $link,
				);
			$this->db->insert('carousel_images', $data);
		}
	}

	function remove_carousel_image($carousel_id, $images_id){
		$this->db->where('carousel_id', $carousel_id);
		$this->db->where('images_id', $images_id);
		$this->db->delete('carousel_images');
	}

	function delete_carousel_item($id)
	{
		$this->db->where('id', $id);
		$this->db->delete('carousel');

		$this->db->where('carousel_id', $id);
		$this->db->delete('carousel_images');

		$this->db->where('carousel_id', $id);
		$this->db->delete('carousel_translation');
	}
}