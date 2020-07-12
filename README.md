# A Custom Notification Service for Wordpress 5.4.2 and Constant Contact API V2

<table width="100%">
	<tr>
		<td align="left" width="70">
			<strong>A WordPress Custom Plugin for Constant Contact API V2</strong><br />
			The use of this plugin is for user of Constant Contact and Wordpress. It was coding during the lockdown of 2020 for a US state, whereby users subscribe for JOBS. If a new job posted by the state, a notification is sent to only the subscribe campaign.    
		</td>
		<td align="right" width="20%">
			<a href="https://static.ctctcdn.com/lp/images/standard/logos/logo-ctct-color.svg">
				<img src="https://static.ctctcdn.com/lp/images/standard/logos/logo-ctct-color.svg" alt="Build status">
			</a>
		</td>
	</tr>
	<tr>
		<td>
			A <strong><a href="https://semeonline.co.za/">SemeOnline Digital</a></strong> project. Maintained by <a href="https://github.com/niloc95">Nilesh Cara</a>.
		</td>
		<td align="center">
			<img src="https://avatars1.githubusercontent.com/u/36539420?s=460&u=2fcdbd886c17b639862045b24ae31d50dda7e252&v=4" width="100" />
		</td>
	</tr>
</table>

Tags: Constant Contact, Newsletter, Email Marketing, Mailing List, Newsletter, Events, Event Marketing

## Getting Set Up

- Create a zip of the “job_notifications.php”
- Activate the plugin


## Configuration

- Create the Bearer_tokens and API keys from Constant Contact
- Get campaign id’s

### Setting up

- Should you wish to test a single campaign change line 195 “json_encode(array("id"=>"1111111111"))“

### HTML Campaign Template: 

-It's JSON, so escape all quotes and remove all white spaces. 
-Constant Contact V2 API – char set ISO-8859-1 
A <strong><a href="https://community.constantcontact.com/t5/API-Enhancement-Requests/API-connection-does-not-support-UTF-8-charset/td-p/70051/">Constant Contact community</a>


### Workflow

- Find an issue you'd like to help with, or create a new one for the change you'd like to introduce.
- Fork the repo to your own account
- Issue pull-requests from your fork to ours
- Tag the issue you're trying to resolve in your pull-request for some context
- Make sure the pull-request passed all Travis checks
- Tag any of the contributors for a review.

## Changelog

- 1,0
  - Stable version

