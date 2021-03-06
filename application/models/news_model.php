<?php
class News_model extends CI_Model
{

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

	/**
	 * Fetches latest news
	 *
	 * @return array
	 */
    function get_latest_news()
    {
		// check if approved to see not approved news
		$admin = $this->login->has_privilege('news_editor');

		$this->db->select("users.first_name, users.last_name, users.id as userid, news_images.*, images.image_original_filename");
		$this->db->select("news.date, news.id, news.draft, news.approved, news_translation_language.title, news_translation_language.text, news_translation_language.lang_id");
		$this->db->select("(news.date > NOW()) as scheduled");
		$this->db->select("COALESCE(sticky_order, 0) as sticky_order",false);
		$this->db->from("news");
		$this->db->join("news_translation_language", 'news.id = news_translation_language.news_id', '');
		$this->db->join("users", 'news.user_id = users.id', '');
		$this->db->join("news_sticky", 'news.id = news_sticky.news_id', 'left');
		$this->db->join("news_images", 'news.id = news_images.news_id', 'left');
		$this->db->join("images", 'news_images.images_id = images.id', 'left');

		if(!$admin)
		{
			// not admin, forces news to be approved, not draft and not scheduled
			$this->db->where("news.draft",0);
			$this->db->where("news.approved",1);
			$this->db->where("DATE(news.date) <= DATE(NOW())");
		}

		$this->db->order_by("sticky_order DESC, news.date DESC");
		$query = $this->db->get();
        return $query->result();
    }

	/**
	 * Fetches latest news in a paged fashion
	 * @param  integer $page  the current page
	 * @param  integer $limit
	 * @return array
	 */
    function get_paged_news($page = 1, $limit = 10)
    {
    	$page--;
		// check if approved to see not approved news
		$admin = $this->login->has_privilege('news_editor');

		$this->db->select("users.first_name, users.last_name, users.id as userid, news_images.*, images.image_original_filename");
		$this->db->select("news.date, news.id, news.draft, news.approved, news_translation_language.title, news_translation_language.text, news_translation_language.lang_id");
		$this->db->select("(news.date > NOW()) as scheduled");
		$this->db->select("COALESCE(sticky_order, 0) as sticky_order",false);
		$this->db->from("news");
		$this->db->join("news_translation_language", 'news.id = news_translation_language.news_id', '');
		$this->db->join("users", 'news.user_id = users.id', '');
		$this->db->join("news_sticky", 'news.id = news_sticky.news_id', 'left');
		$this->db->join("news_images", 'news.id = news_images.news_id', 'left');
		$this->db->join("images", 'news_images.images_id = images.id', 'left');

		if(!$admin)
		{
			// not admin, forces news to be approved, not draft and not scheduled
			$this->db->where("news.date <= NOW()");
			$this->db->where("news.draft", 0);
			$this->db->where("news.approved", 1);
		}

		$this->db->order_by("sticky_order DESC, news.date DESC");
		$this->db->limit($limit, $page*$limit);
		$query = $this->db->get();
        return $query->result();
    }

    /**
     * count how many news that is available to the current user
     * @return int the number of news
     */
    function get_news_count()
    {
    	if(!$this->login->has_privilege('news_editor'))
    	{
    		$this->db->where('news.date <= NOW()');
    		$this->db->where('draft', 0);
    		$this->db->where('approved', 1);
    	}
    	$this->db->from('news');
    	return $this->db->count_all_results();
    }

    /**
     * count how many drafts there is
     * @return int the number of news
     */
    function get_draft_count()
    {
		$this->db->where('draft', 1);
    	$this->db->from('news');
    	return $this->db->count_all_results();
    }

    /**
     * count how many drafts there is
     * @return int the number of news
     */
    function get_needs_approval_count()
    {
		$this->db->where('approved', 0);
    	$this->db->from('news');
    	return $this->db->count_all_results();
    }

	/**
	 * Fetches a specific news item
	 *
	 * @param  integer	$id		The ID of the news item
	 * @return array
	 */
    function get_news($id)
    {
		// check if approved to see not approved news
		$admin = $this->login->has_privilege('news_editor');

		$this->db->select("users.first_name, users.last_name, users_data.gravatar as gravatar, users.id as userid, news_images.*, images.*");
		$this->db->select("news.id, news.date, news_translation_language.title, news_translation_language.text, news_translation_language.lang_id, news_translation_language.last_edit");
		$this->db->select("(news.date > NOW()) as scheduled");
		$this->db->from("news");
		$this->db->join("news_translation_language", 'news.id = news_translation_language.news_id', '');
		$this->db->join("users", 'news.user_id = users.id', '');
		$this->db->join("users_data", 'users_data.users_id = users.id', 'left');
		$this->db->join("news_images", 'news.id = news_images.news_id', 'left');
		$this->db->join("images", 'news_images.images_id = images.id', 'left');
		$this->db->where("news.id",$id);

		if(!$admin)
		{
			// not admin, forces news to be approved, not draft and not scheduled
			$this->db->where("news.draft",0);
			$this->db->where("news.approved",1);
			$this->db->where("news.date <= NOW()");
		}

		$this->db->limit(1);
		$query = $this->db->get();
		$res = $query->result();
        return $res[0];
	}

	/**
	 * fetches a specific news item, admin-style => more data included
	 *
	 * @param  integer	$id		The ID of the news item
	 * @return array
	 */
	function admin_get_news($id)
    {
		$this->db->select("news.*, language.language_name, language.language_abbr, news_translation.*");
		$this->db->from("news");
		$this->db->from("language");
		$this->db->join("news_translation", 'news_translation.news_id = news.id AND news_translation.lang_id = language.id', 'left');
		$this->db->where("news.id",$id);
		$query = $this->db->get();
		$translations = $query->result();

		$this->db->select("news.*, news_images.images_id, images.image_original_filename");
		$this->db->select("COALESCE(sticky_order, 0) as sticky_order",false);
		$this->db->from("news");
		$this->db->join("news_sticky", 'news.id = news_sticky.news_id', 'left');
		$this->db->join("news_images", 'news.id = news_images.news_id', 'left');
		$this->db->join("images", 'news_images.images_id = images.id', 'left');
		$this->db->where("news.id",$id);
		$this->db->limit(1);
		$query = $this->db->get();
		$news_array = $query->result();
		$news = $news_array[0];

		$news->translations = array();
		foreach($translations as $t)
		{
			if($t->id == $news->id)
			{
				array_push($news->translations, $t);
			}
		}

		return $news;
	}

	/**
	 * Fetches all news for the admin overview
	 *
	 * @return array
	 */
	function admin_get_all_news_overview()
	{
		$this->db->select("news.*, language.language_name, language.language_abbr, news_translation.*");
		$this->db->from("news");
		$this->db->from("language");
		$this->db->join("news_translation", 'news_translation.news_id = news.id AND news_translation.lang_id = language.id', 'left');
		$query = $this->db->get();
		$translations = $query->result();

		$this->db->select("news.*, news_images.images_id, images.image_original_filename");
		$this->db->select("COALESCE(sticky_order, 0) as sticky_order",false);
		$this->db->select("users.first_name, users.last_name");
		$this->db->from("news");
		$this->db->join("news_sticky", 'news.id = news_sticky.news_id', 'left');
		$this->db->join("news_images", 'news.id = news_images.news_id', 'left');
		$this->db->join("users", 'news.user_id = users.id', '');
		$this->db->join("images", 'news_images.images_id = images.id', 'left');
		$this->db->order_by("sticky_order DESC, news.date DESC");
		$query = $this->db->get();
		$news_array = $query->result();

		foreach($news_array as $news) {
			$news->translations = array();
			foreach($translations as $t) {
				if($t->id == $news->id) {
					array_push($news->translations, $t);
				}
			}
		}
		return $news_array;
	}

	/**
	 * Create a new news
	 *
	 * @param  integer	$user_id		The ID os the user who creates the news
	 * @param  array 	$translations	All translations of the news item, array("lang_abbr" => "se", "title" => "Inte klistrad!", "text" => "Den här nyheten är inte klistrad eller översatt!")
	 * @param  date		$post_date		The date of the news item
	 * @param  integer	$draft			Specify if the news item is a draft, 1 = Draft, 0 = Not draft
	 * @param  integer	$approved		Specify if the news item is approved, 1 = Approved, 0 = Not approved
	 * @param  integer	$group_id		The id of the group the user belongs to when posting
	 * @return The news id
	 */
	function add_news($user_id, $translations = array(), $post_date = '', $draft = 0, $approved = 1, $group_id = 0)
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
			if((!in_array("lang_abbr",$arr_keys) && !in_array("lang",$arr_keys)) || !in_array("title",$arr_keys) || !in_array("text",$arr_keys)) {
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

		if(is_numeric($group_id) && $group_id > 0)
		{
			$this->db->where('user_id', $user_id);
			$this->db->where('group_id', $group_id);
			$query = $this->db->get('users_groups');
			if($query->num_rows != 1)
			{
				$theGroup = 0;
			} else {
				$theGroup = $group_id;
			}
		} else {
			$theGroup = 0;
		}

		$theTime = strtotime($post_date);
		if($theTime === false) {
			$theTime = date("Y-m-d H:i:s", time());
		} else {
			$theTime = date("Y-m-d H:i:s", $theTime);
		}

		//if($use_transaction)
		$this->db->trans_begin();

		$data = array(
		   'user_id' => $user_id,
		   'group_id' => $theGroup,
		   'date' => $theTime,
		   'draft' => $draft,
		   'approved' => $approved,
		);
		$this->db->insert('news', $data);
		$news_id = $this->db->insert_id();

		$success = true;
		foreach($theTranslations as &$translation)
		{
			$lang_abbr = $translation["lang_abbr"];
			$title = $translation["title"];
			$text = $translation["text"];
			$theSuccess = $this->update_translation($news_id, $lang_abbr, $title, $text);
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
		return $news_id;
	}

	/**
	 *	Counts different admin notifications
	 *
	 *	@return An array with the counted results
	 */
	function admin_get_notifications()
	{
		$this->db->select("SUM(news.approved=0) as news_unapproved, SUM(news.draft=1) as news_draft, SUM(news.approved=1 AND news.draft=0) as news_published");
		$this->db->from("news");
		$query = $this->db->get();
		$res = $query->result();
		return $res[0];
	}

	/**
	 * deleta a news entry and it's translations
	 * @param  int $id the id
	 * @return bool     success or not
	 */
	function delete($id)
	{
		if($this->is_draft($id))
		{
			$translations = $this->db->delete('news_translation', array('news_id' => $id));
			$sticky = $this->db->delete('news_sticky', array('news_id' => $id));
			$news = $this->db->delete('news', array('id' => $id, 'draft' => 1));

			return ($translations && $sticky && $news);
		}

		return false;
	}

	/**
	 * check wether post is draft
	 * @param  int  $id post id
	 * @return boolean
	 */
	function is_draft($id)
	{
		$this->db->where(array('id' => $id, 'draft' => 1));
		$q = $this->db->get('news');

		return $q->num_rows();
	}

	/**
	 * Update a translation of a specific news item
	 *
	 * @param  integer	$news_id		The ID of the news item
	 * @param  string	$lang_abbr		The language translation abbreviation
	 * @param  string	$title			The title of the news item translation
	 * @param  string	$text			The text of the news item translation
	 * @return bool		True or false depending on success or failure
	 */
	function update_translation($news_id, $lang_abbr, $title, $text)
	{
		$theTitle = trim($title);
		$theText = trim($text);

		// check if the news exists
		$this->db->where('id', $news_id);
		$query = $this->db->get('news');
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
		if($theTitle == '' && $theText == '')
		{
			$this->db->delete('news_translation', array('news_id' => $news_id, 'lang_id' => $lang_id));
			return true;
		}

		// if one of the title and the text is null then exit
		if($theTitle == '' || $theText == '')
		{
			return false;
		}

		$query = $this->db->get_where('news_translation', array('news_id' => $news_id, 'lang_id' => $lang_id), 1, 0);
		if ($query->num_rows() == 0)
		{
			// A record does not exist, insert one.
			$data = array(	'news_id' 	=> $news_id,
							'lang_id' 	=> $lang_id,
							'title'		=> $theTitle,
							'text'		=> $theText,
							'last_edit'	=> '0000-00-00 00:00:00',
						);
			$query = $this->db->insert('news_translation', $data);
			// Check to see if the query actually performed correctly
			if ($this->db->affected_rows() > 0)
			{
				return TRUE;
			}
		} else {
			// A record does exist, update it.
			// update the translation, and if the texts have not been changed then dont update the last_edit field
			$theTime = date("Y-m-d H:i:s", time());
			$sql = 'UPDATE news_translation SET last_edit = IF(STRCMP(title, "'.$theTitle.'") = 0, IF(STRCMP(text, "'.$theText.'") = 0, last_edit, "'.$theTime.'"), "'.$theTime.'"), title = "'.$theTitle.'", text = "'.$theText.'" WHERE news_id = "'.$news_id.'" AND lang_id = "'.$lang_id.'" ';
			$this->db->query($sql);
			return true;
		}
		return FALSE;
	}
}
