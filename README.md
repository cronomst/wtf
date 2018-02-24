wtf
===

WTF source

A configuration template can be found in /config/config.ini.template

## Game Controller API

- **Base URL**: /play/wtf.php

### Actions

#### sayChat

- **Method**: GET

Query parameters

- action: "sayChat"
- json: If it exists in the query string, then responses will be JSON. Otherwise XML.
- msg: Chat message

Example: `/play/wtf.php?action=sayChat&msg=hi`

##### Response Body Example

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<wtf>
    <chat>
        <msg>&lt;user&gt;Kenneth&lt;/user&gt;&lt;b&gt;Kenneth has joined the game.&lt;/b&gt;</msg>
        <msg>&lt;user&gt;Kenneth&lt;/user&gt;&lt;b&gt;Kenneth&lt;/b&gt;: hi</msg>
    </chat>
</wtf>
```

```json
{
    "chat":[
        "<user>Kenneth</user><b>Kenneth has joined the game.</b>",
        "<user>Kenneth</user><b>Kenneth</b>: hi"
    ]
}
```

#### getState

- **Method**: GET

Query parameters

- action: "getState"
- json: If it exists in the query string, then response will be JSON. Otherwise XML.

Example: `/play/wtf.php/action=getState`

##### Response Body Example

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<wtf>
    <state>
        <type>pregame</type>
        <image></image>
        <next_image></next_image>
        <round>0</round>
        <checkback>5000</checkback>
    </state>
</wtf>
```

#### setCaption

- **Method**: GET

Query parameters

- action: "setCaption"
- json
- caption: Player's caption

Example: `/play/wtf.php?action=setCaption&caption=MyCap`

##### Response Body Example

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<wtf>
    <response>caption_ok</response>
</wtf>
```

#### setVote

- **Method**: GET

Query parameters

- action: "setVote"
- json
- vote_id

Example: `/play/wtf.php?action=setVote&vote_id=5`

##### Response Body Example

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<wtf>
    <response>vote_ok</response>
</wtf>
```