# ActiveRecord Variation Extension

[![Stable Version](https://poser.pugx.org/veksa/ar-variation/v/stable)](https://packagist.org/packages/veksa/carrot-php-api)
[![License](https://poser.pugx.org/veksa/carrot-php-api/license)](https://packagist.org/packages/veksa/carrot-php-api)
[![Total Downloads](https://poser.pugx.org/veksa/carrot-php-api/downloads)](https://packagist.org/packages/veksa/carrot-php-api)
[![Daily Downloads](https://poser.pugx.org/veksa/carrot-php-api/d/daily)](https://packagist.org/packages/veksa/carrot-php-api)
[![Build Status](https://travis-ci.org/veksa/carrot-php-api.svg)](https://travis-ci.org/veksa/carrot-php-api)
[![Code Climate](https://codeclimate.com/github/veksa/carrot-php-api/badges/gpa.svg)](https://codeclimate.com/github/veksa/carrot-php-api)
[![Test Coverage](https://codeclimate.com/github/veksa/carrot-php-api/badges/coverage.svg)](https://codeclimate.com/github/veksa/carrot-php-api/coverage)

An extended native php wrapper for [Carrot Quest API](https://carrotquest.io/developers/endpoints/) without requirements. Supports all methods and types of responses.

##Install
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require  veksa/carrot-php-api "~1.0"
```

or add

```
"veksa/carrot-php-api": "~1.0"
```

to the require section of your `composer.json` file.

## Usage

### API Wrapper
``` php
$carrot = new \Veksa\Carrot\Api('YOUR_APP_ID', 'YOUR_CARROT_API_KEY', 'YOUR_CARROT_API_SECRET_KEY');
```
### Methods

#### getActiveUsers
Get the users who are currently online on the status of "online".

----------

#### getCountLeads
Get count of users (leads). User if the lead is considered to be, if known contacts about it: name, email, phone, User ID, or it was at least one dialogue.

----------

#### getLeads
Get all users (leads). User if the lead is considered to be, if known contacts about it: name, email, phone, User ID, or it was at least one dialogue.

----------

#### getConversations
Get all the dialogues of application.

**id** *int*: user ID.

----------

#### getConversation
Get information about the specific dialog.

**id** *int*: dialogue ID. *Required options*

----------

#### getMessages
Get messages in a specific dialog.

**id** *int*: dialogue ID. *Required options*

----------

#### sendConversationMessage
Send messages to a specific dialog.

**id** *int*: dialogue ID. *Required options*

**message** *string*: text of message. *Required options*

**type** *string*: type of message. Default: note

**botName** *string*: name of bot. Default: Bot

----------

#### readMessages
Mark all messages as read in the dialogue (by the user on the site).

**id** *int*: dialogue ID. *Required options*

----------

#### setTyping
Set typing message in conversation.

**id** *int*: dialogue ID. *Required options*

**message** *string*: message.

----------

#### assignConversation
Assign a specific dialogue defined by the administrator (or removes the assignment).

**id** *int*: dialogue ID. *Required options*

**adminId** *int|null*: admin ID or null to remove assignment.

----------

#### addTag
Add tag to dialogue.

**id** *int*: dialogue ID. *Required options*

**tag** *string*: tag.

----------

#### deleteTag
Delete tag from dialogue.

**id** *int*: dialogue ID. *Required options*

**tag** *string*: tag.

----------

#### closeConversation
Close the conversation.

**id** *int*: dialogue ID. *Required options*

----------

#### getUser
Get user by ID.

**id** *int*: user ID. *Required options*

**isSystem** *bool*: system or local ID. Default is system

----------

#### setProps
Add or Update user props.

**id** *int*: user ID. *Required options*

**props** *array*: array of props. *Required options*

**isSystem** *bool*: is system ID?

----------

#### deleteProps
Delete user props.

**id** *int*: user ID. *Required options*

**props** *array*: array of props. *Required options*

**isSystem** *bool*: is system ID?

----------

#### setPresence
Set user status.

**id** *int*: user ID. *Required options*

**presence** *string*: user status. *Required options*

**sessionId** *string*: session ID *Required options*

----------

#### sendUserMessage
Send messages to a specific user.

**id** *int*: user ID. *Required options*

**message** *string*: text of message. *Required options*

**type** *string*: is note or message. Default: popup_chat

----------

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email admin@devblog.pro instead of using the issue tracker.

## Credits

- [Alex Khijnij](https://github.com/veksa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
