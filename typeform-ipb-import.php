<?php

require 'vendor/autoload.php';
require 'lib/IPS.php';
require 'lib/Topic.php';

use Positivezero\RestClient;

$config = require 'config.php';

$ips = new IPS($config['ipb']['url'], $config['ipb']['module'], $config['ipb']['apiKey']);


$formData = loadFormResponses();
$formData = parseFormResponses($formData);
postApplications($formData);
//print_r($formData);


function getDisplayNameFromUrl($url) {
	global $ips;

	$matches = [];
	$expression = '/(?<=user\/)(\d+)(?=-)/';

	if (preg_match($expression, $url, $matches)) {
		$userId = $matches[0];
		$user = $ips->fetchMember([
			'searchType' => 'member_id',
			'search_string' => $userId
		]);
		$displayName = $user['members_display_name'];

	} else {
		$displayName = ':: MAILMARE IS CONFUSED ::';
	}

	return $displayName;
}


function loadFormResponses() {
	global $config;

	// call Typeform API
	$rest = new RestClient();
	$response = $rest->get("https://api.typeform.com/v0/form/{$config['typeform']['form']}", [
		'key'       => $config['typeform']['apiKey'],
		'completed' => 'true'
	]);

	// return freshly parsed JSON
	return json_decode($response->response, true);
}




function parseFormResponses($formData) {
	global $config;

	// Give "choose from the list"-type questions unique names so
	// that the "other" option doesn't overwrite the chosen one
	$questions = array_map(function($question) {
		if (preg_match('/list_\d+(choice|other)/', $question['id'])) {
			$matches = [];
			preg_match('/choice|other/g', $question['id'], $matches);
			$type = $matches[0];
			$question['question'] = "{$question['question']} ({$type})";

		} elseif ($question['id'] === 'textfield_1420525') {
			$question['question'] = 'Profile URL';
		}
		return $question;
	}, $formData['questions']);


	// remove responses we've already processed
	$lastResponseId = $config['typeform']['latestId'];
	$responses = array_filter($formData['responses'], function($response) use ($lastResponseId) {
		return $response['id'] > $lastResponseId;
	});


	// process the rest into a display-friendly format
	$responses = array_map(function($response) use ($questions) {
		foreach ( $response['answers'] as $questionId => $answer ) {
			// make yes/no answers more human-friendly
			if (preg_match('/terms_\d/', $questionId)) {
				if ($answer === '1') {
					$response['answers'][$questionId] = 'I accept!';
				} else {
					$response['answers'][$questionId] = 'I don\'t accept';
				}

			// add the user's display name for the first question
			} elseif ($questionId === 'textfield_1420525') {
				$response['displayName'] = getDisplayNameFromUrl($answer);
			}
		}

		// Clean the data for returning
		$newResponse = [
			'id'            => (int) $response['id'],
			'completed'     => $response['completed'],
			'metadata'      => $response['metadata'],
			'displayName'   => $response['displayName'],
			'answers'       => []
		];

		foreach ($questions as $question) {
			$newResponse['answers'][$question['question']] = $response['answers'][$question['id']];
		}

		return $newResponse;
	}, $responses);

	return [
		'questions' => $questions,
		'responses' => $responses
	];
}




function postApplications($formData) {
	global $config;
	global $ips;

	$topic = new Topic();
	$topic->setForumId($config['ipb']['forumId']);
	$topic->setUserId($config['ipb']['userId']);
	$topic->setIpsApi($ips);

	foreach ($formData['responses'] as $response) {
		$topic->setFormResponse($response);
		$topic->post();
	}
}