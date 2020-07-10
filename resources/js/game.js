var Game = function ()
{
    this.baseUrl = "../play/wtf.php";
    this.logging = false;
    // == START DEBUG ==
    this.baseUrl = "state-caption.json";
    this.logging = true;
    // == END DEBUG ==
    this.currentState = "";
    this.imagePath = "http://wordsthatfollow.com/play/gameimages/";
    

    this.init = function ()
    {
        this.requestState();
    };

    this.scheduleStateRequest = function (delay)
    {
        var thisGame = this;
        var request = function () {
            thisGame.requestState();
        };
        window.setTimeout(request, delay);
    };
    
    this.requestState = function ()
    {
        var thisGame = this;
        $.getJSON(this.baseUrl, {json:"", action:"getState"})
                .done(function (response) {
                    thisGame.parseStateResponse(response);
                    thisGame.scheduleStateRequest(response["state"]["checkback"]);
                })
                .fail(function () {
                    this.log("Failed to get state.");
                    thisGame.scheduleStateRequest(5000);
                });
    };

    this.requestChat = function ()
    {

    };

    this.postCaption = function (caption)
    {
        this.log("Posting caption: [" + caption + "]");
        var thisGame = this;
//        $.getJSON(this.baseUrl, {json:"", action:"setCaption", caption: caption})
//                .done(function (response) {
//                    if (response.response === "caption_ok") {
//                        this.log("Caption set successfully.");
//                    } else {
//                        this.log("Caption was not set. " + response);
//                    }
//                })
//                .fail(function () {
//                    this.log("Failed to set caption.");
//                });
    };

    this.postVote = function (voteId)
    {

    };

    this.postChat = function (message)
    {

    };

    this.parseStateResponse = function (stateResponse)
    {
        this.log(JSON.stringify(stateResponse));
        var stateMap = {
            "pregame": "pregame",
            "intro": "intro",
            "caption": "get-caption",
            "caption_wait": null,
            "vote_wait": null,
            "vote": "get-vote",
            "results": "round-results",
            "gameover": "game-results"
        };

        var stateType = stateMap[stateResponse["state"]["type"]];
        if (stateType === this.currentState) {
            return;
        }

        $(".game-main").empty();
        this.currentState = stateType;

        if (stateType === "pregame") {
            this.setStatePregame(stateResponse);
        } else if (stateType === "intro") {
            this.setStateIntro(stateResponse);
        } else if (stateType === "get-caption") {
            this.setStateGetCaption(stateResponse);
        } else if (stateType === "get-vote") {
            this.setStateGetVote(stateResponse);
        } else if (stateType === "round-results") {
            this.setStateRoundResults(stateResponse);
        }
    };

    this.setStatePregame = function (stateResponse)
    {
        $(".template.state-pregame").clone()
                .removeClass("template")
                .appendTo(".game-main");
    };

    this.setStateIntro = function (stateResponse)
    {
        var round = stateResponse.state.round;
        $(".state-intro h1").text("Round " + round);
        $(".template.state-intro").clone()
                .removeClass("template")
                .appendTo(".game-main");
        
        this.updateGamePhoto(stateResponse);
    };

    this.setStateGetCaption = function (stateResponse)
    {
        this.updateGamePhoto(stateResponse);
        var thisGame = this;
        
        $(".template.state-get-caption").clone()
                .removeClass("template")
                .appendTo(".game-main");
        
        var submitFunction = function() {
            thisGame.postCaption($(".game-main input").val());
        }
        $(".game-main form").submit(function(event){
            submitFunction();
            event.preventDefault();
        })
        $(".game-main button").click(submitFunction);
    };
    
    this.setStateGetVote = function(stateResponse)
    {
        this.updateGamePhoto(stateResponse);
        
        $(".template.state-get-vote ul").empty();
        stateResponse.state.captionList.forEach(function(captionItem) {
            var listItem = $("<li></li>").text(captionItem.caption);
            $(".template.state-get-vote ul").append(listItem);
        });
        
        $(".template.state-get-vote").clone()
                .removeClass("template")
                .appendTo(".game-main");
    };
    
    this.setStateRoundResults = function(stateResponse)
    {
        var thisGame = this;
        this.updateGamePhoto(stateResponse);
        $(".template.state-round-results .results-list").empty();
        stateResponse.state.resultList.forEach(function(resultItem) {
            var listItem = thisGame.createCaptionResultItem(resultItem.playerName,
                    resultItem.caption,
                    resultItem.votes,
                    resultItem.disqualified);
            $(".template.state-round-results .results-list").append(listItem);
        });
        
        $(".template.state-round-results").clone()
                .removeClass("template")
                .appendTo(".game-main");
    };

    this.getImageURL = function(image) {
        if (image.match(/^flickr:/))
            return image.substring(7, image.length);
        else
            return this.imagePath + image;
    };
    
    this.updateGamePhoto = function(stateResponse)
    {
        var image = new Image();
        image.src = this.getImageURL(stateResponse["state"]["image"]);
        $(".game-photo").empty().append("<img>");
        $(".game-photo img").attr("src", image.src);
    };
    
    this.createCaptionResultItem = function(playerName, caption, score, disqualified)
    {
        var result = $(".template.results-item").clone()
                .removeClass("template");
        if (disqualified) {
            result.addClass("disqualified");
        }
        $(".player-name", result).text(playerName);
        $(".votes", result).text(score);
        $(".caption", result).text(caption);
        
        return result;
        
    };
    
    this.log = function(msg)
    {
        if (this.logging) {
            console.log(msg);
        }
    };
};

$(function ()
{
    var game = new Game();
    game.init();
});