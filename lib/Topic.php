<?php

class Topic {
	protected $title;
	protected $post;
	protected $userId;
	protected $forumId;

	/**
	 * @var IPS
	 */
	protected $ipsApi;

	public function setTitle($title) { $this->title = $title; }
	public function setPost($post) { $this->post = $post; }
	public function setUserId($userId) { $this->userId = $userId; }
	public function setForumId($forumId) { $this->forumId = $forumId; }
	public function setIpsApi(IPS $ipsApi) { $this->ipsApi = $ipsApi; }


	public function post(){
		$this->ipsApi->postTopic([
			'member_field'  => 'member_id',
			'member_key'    => $this->userId,
			'forum_id'      => $this->forumId,
			'topic_title'   => $this->title,
			'post_content'  => $this->post
		]);
	}
}