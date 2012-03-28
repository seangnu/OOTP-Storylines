<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
	Class: Storylines_articles_model
*/

class Storylines_articles_model extends BF_Model 
{

	protected $table		= 'storylines_articles';
	protected $key			= 'id';
	protected $soft_deletes	= true;
	protected $date_format	= 'datetime';
	protected $set_created	= true;
	protected $set_modified = true;
	
	/*-----------------------------------------------
	/	PUBLIC FUNCTIONS
	/----------------------------------------------*/
	public function build_article_tree($storyline_id = false)
	{
		if ($storyline_id === false)
		{
			$this->error .= "No storyline ID was received.<br/>\n";
			return false;
		}
		
		// Pull all starting articles without predecessors first
		$articles = $this->get_article_parents($storyline_id);

		if (count($articles))
		{
			foreach ($articles as $article)
			{
				$article->children = $this->get_article_children($article->id);
			}
		}
		return $articles;
	}
	
	private function get_article_details($article_id = false) 
	{
		if ($article_id === false)
		{
			$this->error .= "No article ID was received to retrieve details.<br/>\n";
			return false;
		}
		return $this->select('subject, wait_days_min, wait_days_max, in_game_message, comment_thread_id, created_on, modified_on, deleted')
					->find($article_id);
	}
	private function get_article_parents($storyline_id = false) 
	{
		$articles = array();
		$this->db->join('storylines_article_predecessors','storylines_article_predecessors.article_id = storylines_articles.id','left outer')
				 ->where('storylines_article_predecessors.article_id IS NULL')
				 ->where('storylines_articles.deleted',0);
		$articles = $this->select('storylines_articles.id, subject, wait_days_min, wait_days_max, in_game_message, comments_thread_id, created_on, modified_on, deleted')->find_all_by('storylines_articles.storyline_id',$storyline_id);
		
		return $articles;
	}
	/*-----------------------------------------------
	/	PRIVATE FUNCTIONS
	/----------------------------------------------*/
	private function get_article_children($article_id = false) 
	{
		if ($article_id === false)
		{
			$this->error .= "No article ID was received to find children.<br/>\n";
			return false;
		}
		$children = array();
		$query = $this->db->select('article_id')
							 ->where('predecessor_id',$article_id)
							 ->get('storylines_article_predecessors');
							 
		if ($query->num_rows() > 0)
		{
			$str_ids = "(";
			foreach ($query->result() as $row)
			{
				if ($str_ids != "(") { $str_ids .= ","; }
				$str_ids .= $row->article_id;
			}
			$str_ids .= ")";
			$this->db->select('id, subject, wait_days_min, wait_days_max, in_game_message, comments_thread_id, created_on, modified_on, deleted')
					->where('deleted',0)
					->where_in('id',$str_ids);
			$child_results = $this->db->get($this->table);
			if ($child_results->num_rows() > 0)
			{			
				foreach ($child_results->result() as $child)
				{	
					$child->children = $this->get_article_children($child->id);
					array_push($children,$child);
				}
			}
			$child_results->free_result();
		}
		$query->free_result();
		return $children;
	}
}