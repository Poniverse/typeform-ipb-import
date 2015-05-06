<?php

return [
	// Typeform Configuration
	'typeform' => [

		// Your Typeform API key is found at: https://admin.typeform.com/account
		'apiKey'    => '',

		// This is the ID used in the form's URL:
		// https://{{username}}.typeform.com/to/{{form}}
		'form'      => '',

		// This is the ID number of the last entry we imported. ID's are assigned
		// sequentially by Typeform, so they're a reliable way to skip ahead to
		// the new stuff. Use 0 to process all entries!
		//
		// NOTE: This is only used the first time the script is run! After that,
		//       the ID of the last entry imported is stored in the "latest_id"
		//       file created in the script's root.
		'latestId'  => 0
	],

	// IP.Board Configuration
	// These settings are used to connect to the IP.Board API:
	'ipb' => [
		// URL of the IP.Board installation (no trailing slash)
		'url'       => '',

		// API key for an XML-RPC user. It needs access to the the postTopic and
		// fetchMember methods in the "ipb" module.
		'apiKey'    => '',

		// You can set an API module here in case you wish to use a custom one.
		'module'    => 'ipb',

		// The ID of the forum that the thread should be posted in.
		'forumId'   => 0,

		// The ID of the account that should post the thread.
		'userId'    => 0
	]
];
