<?php

class Topic {
	protected $userId;
	protected $forumId;
	protected $response;

	/**
	 * @var IPS
	 */
	protected $ipsApi;

	public function setUserId($userId) { $this->userId = $userId; }
	public function setForumId($forumId) { $this->forumId = $forumId; }
	public function setFormResponse($response) { $this->response = $response; }
	public function setIpsApi(IPS $ipsApi) { $this->ipsApi = $ipsApi; }

	public function getTitle() {
		return "New moderator application from {$this->response['displayName']}!";
	}

	public function getPost() {
		$post = '';

		// Add metadata
		$post .=
<<<EOF
[b]Application started at:[/b] {$this->response['metadata']['date_land']}
[b]Application submitted at:[/b] {$this->response['metadata']['date_submit']}
[b]Applied from:[/b] {$this->response['metadata']['referer']}

[hr]

EOF;

		foreach ( $this->response['answers'] as $question => $answer ) {
			$post .=
<<<EOF
[b][u]{$question}[/u][/b]
{$answer}


EOF;
		}

		return $post;
	}

	public function post(){
		$this->ipsApi->postTopic([
			'member_field'  => 'member_id',
			'member_key'    => $this->userId,
			'forum_id'      => $this->forumId,
			'topic_title'   => $this->getTitle(),
			'post_content'  => $this->getPost()
		]);
	}
}