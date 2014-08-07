This script places data from Typeform in an IP.Board forum, using the relevant API's.

	require('coffee-script/register');
	config = require './config'


Main Code
=========

The action begins here!

	restClient = require 'node-rest-client'
	xmlrpc = require 'xmlrpc'


IpsClient is an abstraction for interacting with the IP.Board API.

	class IpsClient
		constructor: (@url, @module, @api_key) ->
			@client = xmlrpc.createClient "#{config.ipb.url}/interface/board/index.php"

		call: (method, data) ->
			# merge in the relevant data
			data.api_module = @module
			data.api_key = @api_key

			@client.methodCall method, [data], (error, value) ->
				console.log value


	ips = new IpsClient config.ipb.url, config.ipb.module, config.ipb.api_key


	class FormData

Here, we...

		processData: () ->
...give "choose from the list"-type questions unique names so that the "other" option doesn't overwrite the chosen one...

			@questions = @questions.map (question) ->
				if question.id.match /list_\d+_(choice|other)/
					type = question.id.match /choice|other/g
					question.question = "#{question.question} (#{type[0]})"
				question

...skip responses we've already dealt with...

			@responses = @responses.filter (response) ->
				response.id > config.typeform.latest_id

...and leave data in a form that's ready to work with.

			@responses = @responses.map (response) =>
				newResponse =
					id: response.id
					completed: response.completed
					metadata: response.metadata
					answers: {}

				for question in @questions
					newResponse.answers[question.question] = response.answers[question.id]

				newResponse
			@ # return the object, for the sake of method chaining


This function parses the raw data, separating the questions and responses from it. For performance reasons, the questions are not "merged" with the responses here, as many responses might be skipped in processing.

		import: (rawJson) ->
			json = JSON.parse(rawJson)
			@questions = json.questions
			@responses = json.responses
			@processData()
			# the object is implicitly returned by @processData

Prints all responses to output. Useful for debugging.

		echo: () ->
			console.log @responses

Sends data over to IP.Board!

		export: () ->
			topics = (@generateTopic response for response in @responses)
			console.log topics
			@postTopic topic for topic in topics


		generateTopic: (response) ->
			console.log response
			title = "New moderator application!"
			post = ''

			for question, answer of response.answers
				post += """
				[b][u]#{question}[/u][/b]
				#{answer}
				

				"""

			return {
				title: title
				post: post
			}



		postTopic: (topic) ->
			ips.call 'postTopic',
				member_field: 'member_id'
				member_key: config.ipb.user_id
				forum_id: config.ipb.forum_id
				topic_title: topic.title
				post_content: topic.post






We call the Typeform API for our configured form:

	rest = new restClient.Client
	rest.get "https://api.typeform.com/v0/form/#{config.typeform.form}?key=#{config.typeform.api_key}&completed=true", (data, response) ->
		applications = new FormData()
			.import(data)
			.export()
