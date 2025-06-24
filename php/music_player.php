<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Player</title>
    <style>
        :root {
          --primary-color: #57abd2;
          --secondary-color: #f8f5fc;
          --accent-color: rgb(223, 219, 227);
          --text-color: #333;
          --light-purple: #e6d9f2;
          --dark-text: #212529;
          --light-text: #f8f9fa;
          --card-bg: #f8f9fa;
          --aside-bg: #f0f2f5;
          --nav-hover: #e0e0e0;
        }

        .dark-mode {
          --primary-color: #57abd2;
          --secondary-color: #2d3748;
          --accent-color: #4a5568;
          --text-color: #f8f9fa;
          --light-purple: #4a5568;
          --dark-text: #f8f9fa;
          --light-text: #212529;
          --card-bg: #1a202c;
          --aside-bg: #1a202c;
          --nav-hover: #4a5568;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            display: flex;
            flex: 1;
            padding: 20px;
            gap: 20px;
        }

        aside {
            flex: 0 0 300px;
            background-color: var(--aside-bg);
            border-radius: 10px;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 120px);
        }

        .player-container {
            flex: 1;
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .song-list {
            list-style: none;
        }

        .song-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s;
        }

        .song-item:hover {
            background-color: var(--nav-hover);
            transform: translateX(5px);
        }

        .song-item.active {
            background-color: var(--primary-color);
            color: var(--light-text);
        }

        .song-cover {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            object-fit: cover;
        }

        .song-info {
            flex: 1;
        }

        .song-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .song-artist {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .now-playing {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 500px;
        }

        .album-art {
            width: 250px;
            height: 250px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .song-details {
            text-align: center;
            margin-bottom: 25px;
            width: 100%;
        }

        .song-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .artist-name {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .progress-container {
            width: 100%;
            margin-bottom: 25px;
        }

        .progress-bar {
            height: 6px;
            width: 100%;
            background-color: var(--accent-color);
            border-radius: 3px;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .progress {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 3px;
            width: 0%;
        }

        .time-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 20px;
        }

        .control-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .control-btn:hover {
            background-color: var(--nav-hover);
        }

        .play-btn {
            background-color: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .play-btn:hover {
            background-color: #4a9bc1;
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 200px;
        }

        .volume-slider {
            flex: 1;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
            cursor: pointer;
        }

        .volume-progress {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 2px;
            width: 80%;
        }

        @media (max-width: 768px) {
            main {
                flex-direction: column;
            }

            aside {
                flex: 0 0 auto;
                height: auto;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include_once("../header.php"); ?>
    <main>
        <aside>
            <h2>Song List</h2>
            <ul class="song-list" id="songList">
                <li class="song-item active" 
                    data-cover="https://i.scdn.co/image/ab67616d00001e02ff9ca10b55ce82ae553c8228" 
                    data-title="Blinding Lights" 
                    data-artist="The Weeknd"
                    data-audio="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3">
                    <img src="https://i.scdn.co/image/ab67616d00001e02ff9ca10b55ce82ae553c8228" alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">Blinding Lights</div>
                        <div class="song-artist">The Weeknd</div>
                    </div>
                </li>
                <li class="song-item" 
                    data-cover="https://i.scdn.co/image/ab67616d00001e029ad3e9959f48d513886b8933" 
                    data-title="Save Your Tears" 
                    data-artist="The Weeknd"
                    data-audio="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3">
                    <img src="https://i.scdn.co/image/ab67616d00001e029ad3e9959f48d513886b8933" alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">Save Your Tears</div>
                        <div class="song-artist">The Weeknd</div>
                    </div>
                </li>
                <li class="song-item" 
                    data-cover="https://i.scdn.co/image/ab67616d00001e02c591d5a0ca51d4f1e96b0a1e" 
                    data-title="Levitating" 
                    data-artist="Dua Lipa"
                    data-audio="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3">
                    <img src="https://i.scdn.co/image/ab67616d00001e02c591d5a0ca51d4f1e96b0a1e" alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">Levitating</div>
                        <div class="song-artist">Dua Lipa</div>
                    </div>
                </li>
                <li class="song-item" 
                    data-cover="https://i.scdn.co/image/ab67616d00001e02a991995542d50a691b9ae5be" 
                    data-title="Don't Start Now" 
                    data-artist="Dua Lipa"
                    data-audio="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3">
                    <img src="https://i.scdn.co/image/ab67616d00001e02a991995542d50a691b9ae5be" alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">Don't Start Now</div>
                        <div class="song-artist">Dua Lipa</div>
                    </div>
                </li>
                <li class="song-item" 
                    data-cover="https://i.scdn.co/image/ab67616d00001e02b5ad99d6a963bc9b3c5a9c03" 
                    data-title="Watermelon Sugar" 
                    data-artist="Harry Styles"
                    data-audio="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3">
                    <img src="https://i.scdn.co/image/ab67616d00001e02b5ad99d6a963bc9b3c5a9c03" alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">Watermelon Sugar</div>
                        <div class="song-artist">Harry Styles</div>
                    </div>
                </li>
            </ul>
        </aside>
        <div class="player-container">
            <div class="now-playing">
                <img src="https://i.scdn.co/image/ab67616d00001e02ff9ca10b55ce82ae553c8228" alt="Album Art" class="album-art">
                <div class="song-details">
                    <h1 class="song-name">Blinding Lights</h1>
                    <p class="artist-name">The Weeknd</p>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar">
                        <div class="progress" id="progress"></div>
                    </div>
                    <div class="time-info">
                        <span class="current-time" id="currentTime">0:00</span>
                        <span class="duration" id="duration">0:00</span>
                    </div>
                </div>
                <div class="controls">
                    <button class="control-btn" id="prevBtn">
                        <i>⏮</i>
                    </button>
                    <button class="control-btn play-btn" id="playBtn">
                        <i>▶</i>
                    </button>
                    <button class="control-btn" id="nextBtn">
                        <i>⏭</i>
                    </button>
                </div>
                <div class="volume-control">
                    <span>🔈</span>
                    <div class="volume-slider" id="volumeSlider">
                        <div class="volume-progress" id="volumeProgress"></div>
                    </div>
                    <span>🔊</span>
                </div>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php") ?>

    <!-- Hidden audio element for actual playback -->
    <audio id="audioPlayer"></audio>

    <script>
        // DOM Elements
        const audioPlayer = document.getElementById('audioPlayer');
        const songList = document.getElementById('songList');
        const songItems = document.querySelectorAll('.song-item');
        const playBtn = document.getElementById('playBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const progressBar = document.getElementById('progressBar');
        const progress = document.getElementById('progress');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeProgress = document.getElementById('volumeProgress');

        // Player state
        let isPlaying = false;
        let currentSongIndex = 0;

        // Initialize player
        function initPlayer() {
            // Set initial volume
            audioPlayer.volume = 0.8;
            volumeProgress.style.width = '80%';
            
            // Load first song
            loadSong(currentSongIndex);
            
            // Event listeners
            playBtn.addEventListener('click', togglePlay);
            prevBtn.addEventListener('click', prevSong);
            nextBtn.addEventListener('click', nextSong);
            
            // Progress bar click
            progressBar.addEventListener('click', setProgress);
            
            // Volume control
            volumeSlider.addEventListener('click', setVolume);
            
            // Audio player events
            audioPlayer.addEventListener('timeupdate', updateProgress);
            audioPlayer.addEventListener('ended', nextSong);
            audioPlayer.addEventListener('loadedmetadata', updateSongInfo);
        }

        // Load song
        function loadSong(index) {
            const song = songItems[index];
            const { cover, title, artist, audio } = song.dataset;
            
            // Update UI
            document.querySelector('.album-art').src = cover;
            document.querySelector('.song-name').textContent = title;
            document.querySelector('.artist-name').textContent = artist;
            
            // Update active state
            songItems.forEach(item => item.classList.remove('active'));
            song.classList.add('active');
            
            // Move to top of list
            songList.insertBefore(song, songList.firstChild);
            
            // Load audio
            audioPlayer.src = audio;
            
            // If was playing, continue playback
            if (isPlaying) {
                audioPlayer.play().catch(e => {
                    console.log("Playback failed:", e);
                    isPlaying = false;
                    playBtn.innerHTML = '▶';
                });
            }
        }

        // Play/pause toggle
        function togglePlay() {
            if (isPlaying) {
                audioPlayer.pause();
                playBtn.innerHTML = '▶';
            } else {
                audioPlayer.play()
                    .then(() => {
                        playBtn.innerHTML = '⏸';
                    })
                    .catch(e => {
                        console.log("Playback failed:", e);
                        alert("Please click play again to start playback");
                    });
            }
            isPlaying = !isPlaying;
        }

        // Previous song
        function prevSong() {
            currentSongIndex--;
            if (currentSongIndex < 0) {
                currentSongIndex = songItems.length - 1;
            }
            loadSong(currentSongIndex);
        }

        // Next song
        function nextSong() {
            currentSongIndex++;
            if (currentSongIndex > songItems.length - 1) {
                currentSongIndex = 0;
            }
            loadSong(currentSongIndex);
        }

        // Update progress bar
        function updateProgress() {
            const { currentTime, duration } = audioPlayer;
            const progressPercent = (currentTime / duration) * 100;
            progress.style.width = `${progressPercent}%`;
            
            // Update time display
            currentTimeEl.textContent = formatTime(currentTime);
        }

        // Update song info when metadata loads
        function updateSongInfo() {
            durationEl.textContent = formatTime(audioPlayer.duration);
        }

        // Set progress bar on click
        function setProgress(e) {
            const width = this.clientWidth;
            const clickX = e.offsetX;
            const duration = audioPlayer.duration;
            audioPlayer.currentTime = (clickX / width) * duration;
        }

        // Set volume
        function setVolume(e) {
            const width = this.clientWidth;
            const clickX = e.offsetX;
            const volume = clickX / width;
            
            audioPlayer.volume = volume;
            volumeProgress.style.width = `${volume * 100}%`;
        }

        // Format time (seconds to MM:SS)
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs < 10 ? '0' + secs : secs}`;
        }

        // Song item click handler
        songItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                currentSongIndex = index;
                loadSong(currentSongIndex);
                if (!isPlaying) {
                    togglePlay();
                }
            });
        });

        // Initialize player when DOM loads
        document.addEventListener('DOMContentLoaded', initPlayer);
    </script>
</body>
</html>