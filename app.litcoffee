This script places data from Typeform in an IP.Board forum, using the relevant API's.

Typeform Configuration
----------------------

	api_key = ''
	form = ''

This is the ID number of the last entry we imported. ID's are assigned sequentially by Typeform, so they're a reliable way to skip ahead to the new stuff.

	latest_id = 0


IP.Board Configuration
----------------------

API Settings

	ipb_api_key = ''
	ipb_module = 'ipb'

URL of the IP.Board installation (no trailing slash)

	ipb_url = ''


The action begins here!

	Client = require 'node-rest-client'
		.Client
	rest = new Client


This function parses the raw data, separating the questions and responses from it.
For performance reasons, the questions are not "merged" with the responses here, as many responses might be skipped in processing.

	parseData = (rawJson, response) ->
		json = JSON.parse(rawJson)
		questions = json.questions
		responses = json.responses

Data is returned as a single object:

		questions: questions
		responses: responses



Here, we...

	processData = (data) ->

...give "choose from the list"-type questions unique names so that the "other" option doesn't overwrite the chosen one...

		data.questions = data.questions.map (question) ->
			if question.id.match /list_\d+_(choice|other)/
				type = question.id.match /choice|other/g
				question.question = "#{question.question} (#{type[0]})"
			question

...skip responses we've already dealt with...

		data.responses = data.responses.filter (response) ->
			response.id > latest_id

...leave data in a form that's ready to work with...

		data.responses = data.responses.map (response) ->
			newResponse =
				id: response.id
				completed: response.completed
				metadata: response.metadata
				answers: {}

			for question in data.questions
				newResponse.answers[question.question] = response.answers[question.id]

			newResponse

...and echo the result!

		console.log data.responses


We call the Typeform API for our configured form:

	rest.get "https://api.typeform.com/v0/form/#{form}?key=#{api_key}&completed=true", (data, response) ->
		processData(parseData(data))
