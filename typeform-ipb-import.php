#!/usr/bin/env/php
<?php

require 'vendor/autoload.php';
require 'lib/IPS.php';
require 'lib/Topic.php';

use Positivezero\RestClient;

$config = require 'config.php';
$ips = new IPS($config['ipb']['url'], $config['ipb']['module'], $config['ipb']['apiKey']);

echo 'Importing form responses...'.PHP_EOL;

$formData = loadFormResponses();
$formData = parseFormResponses($formData);
postApplications($formData);


/**
 * Given an IP.Board profile URL, this method attempts to return the user's
 * display name by parsing the member ID out of it and asking the IPB forum's
 * API for their details.
 *
 * @param $url
 * @return string
 */
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


/**
 * Load's the form's responses from Typeform's API, returning the
 * JSON data as an associate array.
 *
 * @return array
 */
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


/**
 * Processes the data from Typeform into a format suitable for display.
 *
 * @param array $formData Typeform data from loadFormResponses()
 * @return array
 */
function parseFormResponses($formData) {
	global $config;

	// Give "choose from the list"-type questions unique names so
	// that the "other" option doesn't overwrite the chosen one
	$questions = array_map(function($question) use ($config) {
		if (preg_match('/list_\d+_(choice|other)/', $question['id'])) {
			$matches = [];
			preg_match('/choice|other/', $question['id'], $matches);
			$type = $matches[0];
			$question['question'] = "{$question['question']} ({$type})";

		} elseif ($question['id'] === $config['typeform']['profileUrlField']) {
			$question['question'] = 'Profile URL';
		}
		return $question;
	}, $formData['questions']);


	// remove responses we've already processed
	$lastResponseId = getLatestId();
	$responses = array_filter($formData['responses'], function($response) use ($lastResponseId) {
		return $response['id'] > $lastResponseId;
	});


	// process the rest into a display-friendly format
	$responses = array_map(function($response) use ($config, $questions) {
		foreach ( $response['answers'] as $questionId => $answer ) {
			// make yes/no answers more human-friendly
			if (preg_match('/terms_\d/', $questionId)) {
				if ($answer === '1') {
					$response['answers'][$questionId] = 'I accept!';
				} else {
					$response['answers'][$questionId] = 'I don\'t accept';
				}

			// add the user's display name for the first question
			} elseif ($questionId === $config[ 'typeform' ][ 'profileUrlField' ]) {
				echo "Retrieving display name for response {$response['id']}...".PHP_EOL;
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


/**
 * Formatted form responses are posted to the IP.Board forum.
 *
 * @param array $formData
 */
function postApplications($formData) {
	global $config;
	global $ips;

	$topic = new Topic();
	$topic->setForumId($config['ipb']['forumId']);
	$topic->setUserId($config['ipb']['userId']);
	$topic->setIpsApi($ips);

	foreach ($formData['responses'] as $response) {
		echo "Posting response {$response['id']}..." . PHP_EOL;
		$topic->setFormResponse($response);
		$topic->post();
		updateLatestId($response['id']);
	}
}


/**
 * Appends an ID to the ids_processed file.
 *
 * @param int $id
 */
function updateLatestId($id) {
	$line = "$id\n";
	file_put_contents('ids_processed', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Returns the last ID stored in the ids_processed file.
 * If the ids_processed file doesn't exist, this returns the latest ID from
 * the config file.
 *
 * @return int
 */
function getLatestId() {
	global $config;
	$log = file_get_contents('ids_processed');

	if (!$log) {
		$id = $config['typeform']['latestId'];
	} else {
		$ids = explode("\n", $log);

		// The last element of the array is empty because it's a newline,
		// so we use the second-last element.
		end($ids);
		$id = (int) prev($ids);
	}

	return $id;
}