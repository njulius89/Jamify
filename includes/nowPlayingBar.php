<?php
$songQuery = mysqli_query($con, "SELECT * FROM songs ORDER BY RAND() LIMIT 10");
$resultArray = array();
while($row = mysqli_fetch_array($songQuery)) {
  array_push($resultArray, $row['id']);
}

$jsonArray = json_encode($resultArray);
?>

<script>

  $(document).ready(function() {
    currentPlaylist = <?php echo $jsonArray; ?>;
    audioElement = new Audio();
    setTrack(currentPlaylist[0], currentPlaylist, false);
    updateVolumeProgressBar(audioElement.audio);
    //stops elements from highlighting on page using prevent default
    $("#nowPlayingBarContainer").on("mousedown touchstart mousemove touchmove", function(e){
      e.preventDefault();
    })

    $(".playbackBar .progressBar").mousedown(function (){
      mousedown = true;
    });
    $(".playbackBar .progressBar").mousemove(function (e){
      if(mousedown == true){
        //set time of song depending on the mouse position
        timeFromOffset(e, this);
      }
    });
    $(".playbackBar .progressBar").mouseup(function (e){
      timeFromOffset(e, this);
    });

    $(".volumeBar .progressBar").mousedown(function (){
      mousedown = true;
    });
    $(".volumeBar .progressBar").mousemove(function (e){
      if(percentage >= 0 && percentage <= 1){
        var percentage = e.offsetX / $(this).width();
        if(mousedown == true){
          audioElement.audio.volume = percentage;
        }
      }
    });
    $(".volumeBar .progressBar").mouseup(function (e){
      var percentage = e.offsetX / $(this).width();
      if(mousedown == true){
        audioElement.audio.volume = percentage;
      }
    });

    $(document).mouseup(function(){
      mousedown = false;
    });
  });

  function timeFromOffset(mouse, progressBar) {
    //allows users to click anywhere on the bar and jump in the song
    var percentage = mouse.offsetX / $(progressBar).width() * 100;
    var seconds = audioElement.audio.duration * (percentage / 100);
    audioElement.setTime(seconds);
  }
  function prevSong(){
    if(audioElement.audio.currentTime >= 3 || currentIndex == 0){
      audioElement.setTime(0);
    }else{
      currentIndex = currentIndex -1;
      setTrack(currentPlaylist[currentIndex], currentPlaylist, true);
    }
  }
  function nextSong(){
    if (repeat == true) {
      audioElement.setTime(0);
      playSong();
      return;
    }
    if(currentIndex == currentPlaylist.length -1){
      currentIndex == 0;
    } else {
      currentIndex++;
    }
    var trackToPlay= currentPlaylist[currentIndex];
    setTrack(trackToPlay, currentPlaylist, true);
  }

  function setRepeat(){
    repeat = !repeat;
    var imageName = repeat ? "repeat-active.png" : "repeat.png";
    $(".controlButton.repeat img").attr("src", "assets/images/icons/" + imageName);
  }

  function setMute(){
    audioElement.audio.muted = !audioElement.audio.muted;
    var imageName = audioElement.audio.muted ? "volume-mute.png" : "volume.png";
    $(".controlButton.volume img").attr("src", "assets/images/icons/" + imageName);
  }

  function setShuffle(){
    shuffle = !shuffle;
    var imageName = shuffle ? "shuffle-active.png" : "shuffle.png";
    $(".controlButton.shuffle img").attr("src", "assets/images/icons/" + imageName);

    if(shuffle == true) {
      //randomize playlist
      shuffleArray(shufflePlaylist);
    } else {
      //shuffle off = ordered playlist
    }
  }

  function shuffleArray(a) {
    //shuffle the array
    var j, x, i;
    for (i = a.length - 1; i > 0; i--) {
        j = Math.floor(Math.random() * (i + 1));
        x = a[i];
        a[i] = a[j];
        a[j] = x;
    }
  }

  function setTrack(trackId, newPlaylist, play) {
    if(newPlaylist != currentPlaylist) {
      currentPlaylist = newPlaylist;
      //returns a copy 
      shufflePlaylist = currentPlaylist.slice();
      shuffleArray(shufflePlaylist);
    }
    currentIndex = currentPlaylist.indexOf(trackId);
    pauseSong();
    $.post("includes/handlers/ajax/getSongJson.php", { songId: trackId }, function(data) {

      var track = JSON.parse(data);
      $(".trackName span").text(track.title);
      $.post("includes/handlers/ajax/getArtistJson.php", { artistId: track.artist }, function(data) {
        var artist = JSON.parse(data);
        $(".artistName span").text(artist.name);
      });
      $.post("includes/handlers/ajax/getAlbumJson.php", { albumId: track.album }, function(data) {
        var album = JSON.parse(data);
        $(".albumLink img").attr("src", album.artworkPath);
      });
      audioElement.setTrack(track);
      playSong();

    });
    if(play == true) {
      audioElement.audio.play();
    }
  }
  function playSong() {
    if(audioElement.audio.currentTime == 0) {
      $.post("includes/handlers/ajax/updatePlays.php", { songId: audioElement.currentlyPlaying.id });
    }

    $(".controlButton.play").hide();
    $(".controlButton.pause").show();
    audioElement.audio.play();
  }
  function pauseSong() {
    $(".controlButton.play").show();
    $(".controlButton.pause").hide();
    audioElement.audio.pause();
  }
</script>

<div id="nowPlayingBarContainer">
  <div id="nowPlayingBar">
    <div id="nowPlayingLeft">
      <div class="content">
        <span class="albumLink">
          <img class ="albumArtwork" src="" alt="">
        </span>
        <div class="trackInfo">
          <span class="trackName">
            <span></span>
          </span>
          <span class="artistName">
            <span></span>
          </span>
        </div>
      </div>
    </div>
    <div id="nowPlayingCenter">
      <div class="content playerControls">
        <div class="buttons">
          <button class="controlButton shuffle"  title="shuffle" onclick="setShuffle()">
            <img src="assets/images/icons/shuffle.png" alt="shuffle">
          </button>
          <button class="controlButton previous"  title="previous" onclick="prevSong()">
            <img src="assets/images/icons/previous.png" alt="previous">
          </button>
          <button class="controlButton play"  title="play" onclick="playSong()">
            <img src="assets/images/icons/play.png" alt="play">
          </button>
          <button class="controlButton pause"  title="pause" style="display: none;" onclick="pauseSong()">
            <img src="assets/images/icons/pause.png" alt="pause">
          </button>
          <button class="controlButton next"  title="next button" onclick="nextSong()">
            <img src="assets/images/icons/next.png" alt="next">
          </button>
          <button class="controlButton repeat"  title="repeat button" onclick="setRepeat()">
            <img src="assets/images/icons/repeat.png" alt="repeat">
          </button>
        </div>
          <div class="playbackBar">
            <span class="progressTime current">0.00</span>
          <div class="progressBar">
            <div class="progressBarBg">
              <div class="progress"></div>
            </div>
          </div>
          <span class="progressTime remaining">0.00</span>
        </div>
      </div>
    </div>
    <div id="nowPlayingRight">
      <div class="volumeBar">
        <button class="controlButton volume" title="volume button" onclick="setMute()">
          <img src="assets/images/icons/volume.png" alt="volume">
        </button>
        <div class="progressBar">
          <div class="progressBarBg">
            <div class="progress"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
