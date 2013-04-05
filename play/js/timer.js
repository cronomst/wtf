var timer_value;
var game_timer;
var game_timer_elem;

function setGameTimer(delay, elem) {
    game_timer_elem = elem;
    timer_value = delay;
    clearTimeout(game_timer);
    var tm = delay % 1000;
    game_timer = setTimeout("triggerTimer(1000)", tm);
    game_timer_elem.innerHTML = getTimerValue();
}

function getTimerValue() {
    return Math.floor(timer_value / 1000);
}

function triggerTimer(delay) {
    timer_value-= delay;
    if (timer_value < 0)
        timer_value = 0;
    game_timer_elem.innerHTML = getTimerValue();
    if (getTimerValue() > 0)
        game_timer = setTimeout("triggerTimer(1000)", 1000);
}