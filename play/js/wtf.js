// Requires timer.js and ajax.js

var image_path = "./gameimages/";
var servertimer;
var timer_delay = 4000; // Delay in ms between chat XML requests (how often chat is updated)
var retry_delay = 1000;
var total_rounds = 7;
var current_state;
var debug_top = 0;
var debug_list = [];
var debug_len = 10;
//var requestid = 0; /* DEBUG TEMP */
var mute_list = []; // Array of muted players' names.  These players' chat messages will be ignored
var chat_re = /^<user\>(.*)<\/user\>(.*)/;

function ajaxGet(data) {
    var xmlhttp = getXMLHttpRequest();

    //	requestid++; /* DEBUG TEMP */
    //	writeDebug("Request sent [" + requestid + "]: " + data); /* DEBUG TEMP */

    xmlhttp.onreadystatechange = function() {
        //processResponse(xmlhttp, "[" + requestid + "]: " + data);
        processResponse(xmlhttp, data);
    };
    xmlhttp.open("GET", "wtf.php?" + data, true);
    //xmlhttp.setRequestHeader('Content-Type','text/xml; charset=utf-8');
    xmlhttp.send(null);
	
}

function postChat() {
    var chatinput = document.getElementById('chat_text')
    var msg = trim(chatinput.value);
    if (msg.length > 0) {
        ajaxGet("action=sayChat&msg=" + encodeURIComponent(msg));
        chatinput.value = '';
    }	
}

function getChat() {
    var xmlhttp = getXMLHttpRequest();
    var room_id = document.getElementById("room_id").value;
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            parseChatXML(xmlhttp.responseXML.documentElement);
            parsePlayerListXML(xmlhttp.responseXML.documentElement);
        //writeDebug((new Date()).getTime() + " - Chat/player data received.");
        }
    }
    xmlhttp.open("GET", "./tmp/chat" + room_id + ".xml?" + new Date().getTime()); // Add the getTime to prevent caching.
    xmlhttp.send(null);
//writeDebug((new Date()).getTime() + " - Requesting chat data.");
}

function doChatTimer() {
    getChat();
    setTimeout("doChatTimer()", timer_delay);
}

function setCaption() {
    var capinput = document.getElementById("caption_text");
    var capbutton = document.getElementById("caption_button");
    if (capinput.disabled == false) {
        var caption = trim(capinput.value);
        ajaxGet("action=setCaption&caption=" + encodeURIComponent(caption));
        capinput.disabled = true;
        capbutton.value = "Change";
        // Show loading image
        document.getElementById("caption_load_img").style.visibility="visible";
    } else {
        capinput.disabled = false;
        capbutton.value = "Okay";
    }
}
function setVote(vote_id) {
    ajaxGet("action=setVote&vote_id=" + vote_id);

    // Show loading image
    document.getElementById("vote_load_img").style.visibility="visible";
	
    var voteblock = document.getElementById("caption_list");
    var cap = voteblock.getElementsByTagName("li");
    for (var i=0; i<cap.length; i++) {
        if (cap[i].id == "v" + vote_id)
            cap[i].className = "selected";
        else
            cap[i].className = "not_selected";
    }
}

function replaceLTGT(str) {
    str = str.replace(/</g, "&lt;");
    str = str.replace(/>/g, "&gt;");
    str = str.replace(/'/g, "&#039;");
    str = str.replace(/"/g, "&quot;");
    return str;
}

function processResponse(xmlhttp, request_data) {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
        //		writeDebug("Response from " + request_data + " request"); /* DEBUG TEMP */
        if (!xmlhttp.responseXML && xmlhttp.responseText != "") {
            displayError("Server response: <samp>" + xmlhttp.responseText + "</samp><br />I don't understand what the server is saying.  Let's get out of here before we break anything else.",
                "index.php");
        } else if (xmlhttp.responseText == "") {
            writeDebug("Blank server response received.");
            setServerTimer(retry_delay); // Try again to see if we can get a real response.
        } else {
            var doc = xmlhttp.responseXML.documentElement;

            parseResponseXML(doc); // Responses from setCaption and setVote
            parseChatXML(doc);
            //parsePlayerListXML(doc);
            parseStateXML(doc);
            parseErrorXML(doc);
        }
    } else if (xmlhttp.readyState==4) {
        if (xmlhttp.status == 0) {
            // Take care of an issue in Firefox where it likes to return with a status of 0 inexplicably.
            setServerTimer(retry_delay); // Try again to see if we can get a real response.
            writeDebug("Status 0, trying again.");
        } else {
            // If we get something other than a 200, something went wrong.
            writeDebug("Server status: " + xmlhttp.status);
            setServerTimer(retry_delay); // Try again to see if we can get a real response.
        }
    }
}

function parseChatXML(doc) {
    var out_string = "";
    var elems = doc.getElementsByTagName("chat");
    var msg;
    if (elems.length > 0) {
        var msgs = elems[0].getElementsByTagName("msg");
        for (i=0; i < msgs.length; i++) {
            msg = msgs[i].firstChild.nodeValue;
            // Append this message to chat if the user name is not on the muted list
            if (isMsgMuted(msg) == false) {
                // Remove user field from message
                msg = removeUserField(msg);
                out_string+= msg + "<br />";
            }
        }
        setChatText(out_string);
    }
}

function parseResponseXML(doc) {
    if (tagExists(doc, "response")) {
        var response = getNodeValue(doc, "response");
        if (response == "caption_ok") {
            // Remove animated image
            document.getElementById("caption_load_img").style.visibility="hidden";
            writeDebug("Caption accepted");
        } else if (response == "vote_ok") {
            // Remove animated image
            document.getElementById("vote_load_img").style.visibility="hidden";
            writeDebug("Vote accepted");
        }
    }
}

function parsePlayerListXML(doc) {
    var li;
    var i;
    var elems = doc.getElementsByTagName("playerlist");
    var player_list = document.getElementById("player_list");
    var pname;
    if (elems.length > 0) {
        // Clear the current list
        player_list.innerHTML = "";
        var players = elems[0].getElementsByTagName("player");
        for (i=0; i<players.length; i++) {
            li = document.createElement("li");
            pname = getNodeValue(players[i], "name");
            escapedPname = replaceLTGT(pname);
            li.appendChild(document.createTextNode(pname + " "));
            var span = document.createElement("span");
            span.appendChild(document.createTextNode(getNodeValue(players[i], "score")));
            li.appendChild(span);
            if (isMuted(escapedPname)) {
                li.setAttribute("onclick", "javascript:mutePlayer('"+ escapedPname +"', false);");
                li.className = "muted";
                li.setAttribute("title", "Click to unmute this player");
            } else {
                li.setAttribute("onclick", "javascript:mutePlayer('"+ escapedPname +"', true);");
                li.setAttribute("title", "Click to mute this player");
            }
            player_list.appendChild(li);
        }
    }

}

function parseStateXML(doc) {
    var state_type;
    var state_elem = doc.getElementsByTagName("state");
    if (state_elem.length > 0) {
        state_type = getNodeValue(state_elem[0], "type");
        if (state_type == "pregame")
            setStatePregame(state_elem[0]);
        else if (state_type == "intro")
            setStateIntro(state_elem[0]);
        else if (state_type == "caption")
            setStateCaption(state_elem[0]);
        else if (state_type == "caption_wait" || state_type == "vote_wait")
            setStateWait(state_elem[0]);
        else if (state_type == "vote")
            setStateVote(state_elem[0]);
        else if (state_type == "results")
            setStateResults(state_elem[0]);
        else if (state_type == "gameover")
            setStateGameover(state_elem[0]);
    }
}

function parseErrorXML(doc) {
    var msg;
    var url;
    var error_elem = doc.getElementsByTagName("error");
    if (error_elem.length > 0) {
        msg = getNodeValue(error_elem[0], "msg");
        url = getNodeValue(error_elem[0], "url");
        displayError(msg, url);
    }
}

function parseCheckbackXML(elem) {
    var checkback = elem.getElementsByTagName("checkback");
    if (checkback.length > 0) {
        var delay = getNodeValue(elem, "checkback");
        setServerTimer(delay);
        setDebugCheckback(delay); // DEBUG
    }
}

function parseCaptionListXML(elem) {
    var list = elem.getElementsByTagName("captionlist")[0];
    var captions = list.getElementsByTagName("caption");
    var i;
    var id;
    var captext;
    var out = "";
    for (i=0; i<captions.length; i++) {
        id = captions[i].getAttribute("id");
        captext = replaceLTGT(captions[i].firstChild.nodeValue);
        out+= '<li id="v' + id + '" onclick="setVote('+ id + ')" />' + captext + '</li>';
    }
    document.getElementById("caption_list").innerHTML = out;
}

function parseResultListXML(elem) {
    var list = elem.getElementsByTagName("resultlist")[0];
    var results = list.getElementsByTagName("result");
    var i, name, votes, captext, disq;
    var out="";
    for (i=0; i<results.length; i++) {
        captext = replaceLTGT(results[i].firstChild.nodeValue);
        name = replaceLTGT(results[i].getAttribute("name"));
        votes = results[i].getAttribute("votes");
        disq = results[i].getAttribute("disqualified");
        if (disq)
            out+= '<li class="disqualified">';
        else
            out+= "<li>";
        out+= '<div class="votes">' + votes + "</div>"
        out+= '<div class="name">' + name + "</div>"
        out+= '<div class="caption">' + captext + "</div></li>";
    }
    if (results.length > 1) {
        document.getElementById("results_list").innerHTML = out;
    } else {
        document.getElementById("results_list").innerHTML = "<li>Not enough captions were submitted. Skipping this round.</li>";
    }
}

function parseTimerXML(elem) {
    var delay = getNodeValue(elem, "timer");
    setGameTimer(delay, document.getElementById("timer_container"));
}

/* Preloads the image and next_image and returns the filename for the current image */
function parseImageXML(elem) {
    var image = getNodeValue(elem, "image");
    var next_image = getNodeValue(elem, "next_image");
    var loader1 = new Image();
    var loader2 = new Image();
    // Preload the images
    //loader1.src = image_path + image;
    //loader2.src = image_path + next_image;
    loader1.src = getImageURL(image);
    loader2.src = getImageURL(next_image);
	
    // Return the current image for this round
    return loader1.src;
}

function parseWinnerXML(elem) {
    var list = elem.getElementsByTagName("winners")[0];
    var results = list.getElementsByTagName("winner");
    var i, name;
    var out;
	
    if (results.length == 0) {
        // No winner
        out = "I see no winners here.  Only losers.<br />Only losers...";
    } else if (results.length == 1) {
        // One winner
        name = replaceLTGT(results[0].firstChild.nodeValue);
        out = name + " wins!";
    } else {
        // It's a tie.
        out = "It's a tie!<br />";
        for (i=0; i<results.length; i++) {
            name = replaceLTGT(results[i].firstChild.nodeValue);
            if (i == results.length-1)
                out += " and ";
            else if (i > 0)
                out += ", ";
            out += name;
        }
        out += " win!";
    }
    document.getElementById("game_winners").innerHTML = out;
	
}

/* Shorter way to get the value of a node when there is only once instance of it within a given block (elem). */
function getNodeValue(elem, tag) {
    return elem.getElementsByTagName(tag)[0].firstChild.nodeValue;
}

function tagExists(elem, tag) {
    if (elem.getElementsByTagName(tag).length > 0)
        return true;
    return false;
}


/* Trims whitespace from either end of the given string */
function trim(s) {
    return s.replace(/^\s+|\s+$/g,"");
}

function startServerTimer() {
    servertimer = setTimeout("getState()", timer_delay);
}

function stopServerTimer() {
    clearTimeout(servertimer);
}
function resetServerTimer() {
    stopServerTimer()
    startServerTimer()
}
/* reset the timer to check the server for a specific delay.  Used when a <checkback> is received.*/
function setServerTimer(delay) {
    stopServerTimer();
    servertimer = setTimeout("getState()", delay);
}

function getState() {
    ajaxGet("action=getState");
//servertimer = setTimeout("getState()", timer_delay);
}

function initGame() {
    getState();
    doChatTimer();
}

function setChatText(text) {
    var chatbox = document.getElementById('chat_content');
    var at_bottom = chatbox.scrollHeight - chatbox.clientHeight;
    var scroll_top = chatbox.scrollTop;
    chatbox.innerHTML = text;
    // Automatically scroll the chat view down if the scrollbar was already all the way at the bottom.
    // If not, don't scroll it since the user was probably trying to read something earlier in the chat log.
    if (at_bottom == scroll_top)
        chatbox.scrollTop = chatbox.scrollHeight;
}

function displayError(msg, url) {
    var errorbox = document.getElementById('error_content');
    document.getElementById('error_text').innerHTML = msg;
    errorbox.style.display = "block";
    stopServerTimer(); // Stop the server updates from going off since something is clearly broken.
}

/* STATE MANAGEMENT */
function setStatePregame(xml) {
    // Clear out pic contents and main game contents.
    document.getElementById("pic_container").innerHTML = "";
    showState("pregame");
    parseCheckbackXML(xml);
}
function setStateIntro(xml) {
    if (current_state != "intro") {
        // Reset the form elements for the new round.
        resetRound();
		
        var rule_string = (tagExists(xml, "rule") ? getNodeValue(xml, "rule") : "");
        var round = getNodeValue(xml, "round");
        var round_string;
        // Show round
        if (round < total_rounds)
            round_string = "Round " + round;
        else
            round_string = "Final Round";
        // Include note on score multiplier if applicable
        if (round == total_rounds)
            round_string += " - Votes are worth TRIPLE points!";
        else if (round > 3)
            round_string += " - Votes are worth double points.";
        document.getElementById("game_state_intro").innerHTML = "<div><p>" +
        round_string + "</p><p>" +
        rule_string + "</p></div>";
        // Clear out pic
        document.getElementById("pic_container").innerHTML = "";

        parseImageXML(xml);
        showState("intro");
        playSound("intro");
    }
	
    parseCheckbackXML(xml);
	
}
function setStateCaption(xml) {
    if (current_state != "caption") {
        // Set picture
        var img = parseImageXML(xml);
        document.getElementById("caption_pic").innerHTML = '<img src="' + img + '" alt="" />';
        // Show any special rules
        var rule_string = (tagExists(xml, "rule") ? getNodeValue(xml, "rule") : "");
        document.getElementById("caption_rule").innerHTML = rule_string;
        // Hide loading animation in case it was still visible from last time
        document.getElementById("caption_load_img").style.visibility = "hidden";
        // Show timer
        document.getElementById("timer_container").style.display = "block";
		
        showState("caption");
    }
    parseTimerXML(xml);
    parseCheckbackXML(xml);
}
function setStateWait(xml) {
    document.getElementById("timer_container").style.display = "none";
    showState("wait"); // Not a real state, but it will have the desired effect.
    parseCheckbackXML(xml);
}

function setStateVote(xml) {
    if (current_state != "vote") {
        // Set picture
        var img = parseImageXML(xml);
        document.getElementById("pic_container").innerHTML = getPicContainerContents(img);
		
        // Show any special rules (to remind people or so that people just entering can see what the theme was)
        var rule_string = (tagExists(xml, "rule") ? "The rule for this round is: <b>" + getNodeValue(xml, "rule") + "</b>": "");
        document.getElementById("vote_rule").innerHTML = rule_string;
        document.getElementById("timer_container").style.display = "block";
        parseCaptionListXML(xml);

        // Hide loading animation in case it was still visible from last time
        document.getElementById("vote_load_img").style.visibility = "hidden";

        showState("vote");
    }
    // Show timer
    parseTimerXML(xml);
    parseCheckbackXML(xml);
}
function setStateResults(xml) {
    if (current_state != "results") {
        // Set picture
        var img = parseImageXML(xml);
        document.getElementById("pic_container").innerHTML = getPicContainerContents(img);
		
        // Show list of results, highlight winner(s).
        // Hide timer
        document.getElementById("timer_container").style.display = "none";
		
        parseResultListXML(xml);
        showState("results");
    }
    parseCheckbackXML(xml);
}
function setStateGameover(xml) {
    if (current_state != "gameover") {
        // Clear the picture.
        document.getElementById("pic_container").innerHTML = "";
		
        // Show player(s) with the highest score.
        parseWinnerXML(xml);

        showState("gameover");
    }
    parseCheckbackXML(xml);
}

function showState(state) {
    var types = ["pregame", "intro", "caption", "vote", "results", "gameover"];
    var i;
    for (i=0; i<types.length; i++) {
        if (state == types[i])
            document.getElementById("game_state_" + types[i]).style.display = "block";
        else
            document.getElementById("game_state_" + types[i]).style.display = "none";
    }
    // Set current state
    current_state = state;
}

/* Reset all the form elements for the next round */
function resetRound() {
    document.getElementById("caption_text").value = "";
    document.getElementById("caption_text").disabled = false;
    document.getElementById("caption_button").value="Okay";
}

function toggleChatSize() {
    var chat = document.getElementById("chat_content");
    if (chat.className == "min") {
        chat.className = "";
    } else {
        chat.className = "min";
    }
    chat.scrollTop = chat.scrollHeight; // Scroll chat back to bottom.
}
function toggleChatPosition(btn) {
    var chat = document.getElementById("chat_container");
    if (chat.className == "large") {
        chat.className = "";
        btn.innerHTML = "&lt;";
    } else {
        chat.className = "large";
        btn.innerHTML = "&gt;";
    }
}

// Returns the contents of the pic container.  This is just the image tag if it is a local picture, or the image and Flickr link is this is a Flickr photo.
function getPicContainerContents(img) {
    var result = '<div><img src="' + img + '" alt="" /></div>';
    // Show link to Flickr page if this is a Flickr photo
    if (isFlickrImage(img))
        result += '<div>' + getFlickrLink(img) + '</div>';
			
    return result;
}

function getImageURL(image) {
    if (image.match(/^flickr:/))
        return image.substring(7, image.length);
    else
        return image_path + image;
}

function isFlickrImage(url) {
    return (url.match(/flickr.com/) != null);
}

function getFlickrLink(url) {
    var short_url = flickr_getShortURL(url);
    var link = '<a target="_blank" id="pic_flickrlink" href="' + short_url + '">See more on Flickr</a>';
    return link;
}

function flickr_getShortURL(long_url) {
    var parts = long_url.split("/");
    var last = parts.length-1;
    var id = parts[last].split("_");
    var num = id[0];
    var short_url = "http://flic.kr/p/" + baseEncode(num);
    return short_url;
}
function baseEncode(num) {
    var alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
    var base_count = alphabet.length;
    var encoded = '';
    while (num >= base_count) {
        var div = Math.floor(num/base_count);
        var mod = (num-(base_count*div));
        encoded = alphabet[mod] + encoded;
        num = div;
    }

    if (num) encoded = alphabet[num] + encoded;

    return encoded;
}

function showDebug(show) {
    if (show) {
        document.getElementById("debug_content").style.display = "block";
    } else {
        document.getElementById("debug_content").style.display = "none";
    }
}
function writeDebug(msg) {
    var out = "";
    debug_list[debug_top] = msg;
    debug_top++;
    if (debug_top >= debug_len)
        debug_top = 0;
    for (var i=0; i<debug_len; i++) {
        var pos = i + debug_top;
        if (pos >= debug_len)
            pos-= debug_len;
        if (debug_list[pos] != null)
            out += "<div>" + debug_list[pos] + "</div>";
    }
    document.getElementById("debug_messages").innerHTML = out;
}
function setDebugCheckback(val) {
    document.getElementById("debug_checkback").innerHTML = "Checkback: " + val + "ms";
}

// Play the designated sound
function playSound(snd) {
    if (snd=="intro") {
        // Play intro sound.  Use a timer so that it runs in a new thread and doesn't prevent any subsequent code from being called if it fails.
        setTimeout('document.getElementById("sound").playSound("sound/intro.wav");', 0);		
    }
}

function toggleSound(btn) {
    if (sound_applet_loaded == false) {
        // If the applet is not loaded, then we need to set the sound setting cookie and reload the page
        // The reload is because the deplayJava script uses a document.write to insert the applet code.
        // Check if Java is installed before doing anything.  If not, notify them that they don't get sound.
        if (deployJava.versionCheck("1.6+")) {
            setCookie("wtfsetting_sound", "1");
            window.location.reload()
        } else {
            alert("The Java plugin is not installed on your browser, so sound cannot be enabled.\nGo to java.com for the latest version of the plugin.");
        }
    } else {
        var app = document.getElementById("sound");
        app.setSoundEnabled(!app.getSoundEnabled());
        if (app.getSoundEnabled()) {
            btn.src = "audio-on.png";//btn.innerHTML = "Sound is ON";
            setCookie("wtfsetting_sound", "1");
        } else {
            btn.src = "audio-mute.png"; //btn.innerHTML = "Sound is OFF";
            setCookie("wtfsetting_sound", "0");
        }
    }
}
function initSound() {
    var attributes = {
        code:'soundapplet.SoundApplet',
        width:0,
        height:0,
        archive:'SoundApplet.jar',
        id:'sound'
    }; 
    var parameters = {
        sounds:'sound/intro.wav',
        enabled:'true'
    } ;
    deployJava.runApplet(attributes, parameters, '1.6'); 
}

function setCookie(c_name,value) {
    var exdate=new Date();
    exdate.setDate(exdate.getDate() + 31);
    var c_value=escape(value) + ("; expires="+exdate.toUTCString()+"; path=/");
    document.cookie=c_name + "=" + c_value;
}
/*
   =================
   Mute-related functions
   =================
*/
/**
 * Checks if the given chat message should be muted
 *
 * Returns true if the message is from a name on the mute_list.  Otherwise returns false
 */
function isMsgMuted(msg) {
    var arr = msg.match(chat_re);
    if (arr != null)
        return isMuted(arr[1]);
    else
        return false;
}
function isMuted(pname) {
    for (var i=0; i<mute_list.length; i++) {
        if (pname == mute_list[i])
            return true;
    }
    return false;
}

function mutePlayer(pname, muted) {
    if (muted)
        mute_list.push(pname); // Add player to muted list
    else {
        // Remove player from muted list
        var remove_pos = -1;
        // Find the name's position in the array
        for (var i=0; i<mute_list.length; i++) {
            if (pname == mute_list[i]) {
                remove_pos = i;
                break;
            }
        }
        // If the name was found, remove it from the array
        if (remove_pos >= 0)
            mute_list.splice(remove_pos,1);
    }
    getChat();
}
/**
 * Removes the <user>...</user> part of the chat message since it should not be displayed
 */
function removeUserField(msg) {
    var arr = msg.match(chat_re);
    if (arr == null)
        return msg;
    else
        return arr[2];
}
