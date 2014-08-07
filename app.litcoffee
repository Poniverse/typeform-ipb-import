This script places data from Typeform in an IP.Board forum, using the relevant API's.

	fs = require 'fs'
	require 'coffee-script/register'
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

		updateId: (id) ->
			fs.writeFileSync './latest_id', id


		latestId: () ->
			@updateId config.typeform.latest_id unless fs.existsSync './latest_id'

			parseInt fs.readFileSync('./latest_id').toString()


Here, we...

		processData: () ->

			# ...skip responses we've already dealt with...
			@responses = @responses.filter (response) =>
				response.id > (Math.max config.typeform.latest_id, @latestId())


			# ...make yes/no answers more human-friendly...
			@responses = @responses.map (response) ->
				for question_id, answer of response.answers
					if question_id.match /terms_\d+/
						if answer == '1'
							response.answers[question_id] = 'I accept!'
						else answer = 'I don\'t accept.'
				response


			# ...give "choose from the list"-type questions unique names so that the
			# "other" option doesn't overwrite the chosen one...
			@questions = @questions.map (question) ->
				if question.id.match /list_\d+_(choice|other)/
					type = question.id.match /choice|other/g
					question.question = "#{question.question} (#{type[0]})"
				question



			# ...and leave data in a form that's ready to work with.
			@responses = @responses.map (response) =>
				newResponse =
					id: parseInt response.id
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
			@postTopic topic for topic in topics


		generateTopic: (response) ->
			title = "New moderator application!"
			post = ''

			for question, answer of response.answers
				post += """
				[b][u]#{question}[/u][/b]
				#{answer}


				"""

			return {
				response_id: response.id
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

			@updateId topic.response_id






We call the Typeform API for our configured form:

	rest = new restClient.Client
	rest.get "https://api.typeform.com/v0/form/#{config.typeform.form}?key=#{config.typeform.api_key}&completed=true", (data, response) ->
		applications = new FormData()
			.import data
			.export()
