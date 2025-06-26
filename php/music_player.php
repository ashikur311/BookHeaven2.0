<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audiobook Player</title>
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
          --box-shadow: 0 0 3rem rgba(0, 0, 0, 0.2);
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
           --box-shadow: 0 0 3rem rgba(255, 255, 255, 0.2);
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
             border: 2px solid #3498db;
            box-shadow: var( --box-shadow);
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 120px);
        }

        .player-container {
            flex: 1;
            background-color: var(--card-bg);
            border-radius: 10px;
            border: 2px solid #3498db;
            box-shadow: var( --box-shadow);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Music icons animation */
        .music-icons-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .music-icon {
            position: absolute;
            font-size: 1.2rem;
            opacity: 0.7;
            user-select: none;
            z-index: 1;
        }

        .background-art {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.1;
            z-index: 1;
            filter: blur(8px);
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
            position: relative;
            z-index: 2;
        }

        .album-art {
            width: 250px;
            height: 250px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            animation: pulse 8s infinite ease-in-out;
            transition: transform 0.3s;
        }

        .album-art:hover {
            transform: scale(1.03);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
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
            transition: all 0.2s;
        }

        .control-btn:hover {
            background-color: var(--nav-hover);
            transform: scale(1.1);
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
            transform: scale(1.05);
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

        .no-books {
            text-align: center;
            padding: 20px;
            color: var(--text-color);
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
            <h2>Your Audiobooks</h2>
            <ul class="song-list" id="songList">
                <!-- Audiobooks will be loaded here dynamically -->
                <div class="no-books" id="noBooksMessage">Loading your audiobooks...</div>
            </ul>
        </aside>
        <div class="player-container">
            <!-- Music icons animation container -->
            <div class="music-icons-container" id="musicIconsContainer"></div>
            
            <div class="background-art" id="backgroundArt"></div>
            <div class="now-playing">
                <img src="assets/default-audiobook-cover.jpg" alt="Audiobook Cover" class="album-art" id="albumArt">
                <div class="song-details">
                    <h1 class="song-name" id="songName">No Audiobook Selected</h1>
                    <p class="artist-name" id="artistName">Select an audiobook from the list</p>
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
        const playBtn = document.getElementById('playBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const progressBar = document.getElementById('progressBar');
        const progress = document.getElementById('progress');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeProgress = document.getElementById('volumeProgress');
        const albumArt = document.getElementById('albumArt');
        const songName = document.getElementById('songName');
        const artistName = document.getElementById('artistName');
        const backgroundArt = document.getElementById('backgroundArt');
        const noBooksMessage = document.getElementById('noBooksMessage');
        const musicIconsContainer = document.getElementById('musicIconsContainer');

        // Player state
        let isPlaying = false;
        let currentSongIndex = 0;
        let audiobooks = [];
        let musicIcons = [];
        let animationFrameId;
        
        // Music icons configuration
        const musicSymbols = ['♪', '♫', '♩', '♬', '♭', '♮', '♯', '🎵', '🎶', '🎧', '🎼', '📻'];
        const colors = ['#FF5252', '#FF4081', '#E040FB', '#7C4DFF', '#536DFE', '#448AFF', 
                        '#40C4FF', '#18FFFF', '#64FFDA', '#69F0AE', '#B2FF59', '#EEFF41', 
                        '#FFFF00', '#FFD740', '#FFAB40', '#FF6E40'];

        // Initialize player
        async function initPlayer() {
            // Set initial volume
            audioPlayer.volume = 0.8;
            volumeProgress.style.width = '80%';
            
            // Initialize music icons animation
            setupMusicIcons();
            
            // Load user's audiobooks
            await fetchUserAudiobooks();
            
            // If we have audiobooks, load the first one
            if (audiobooks.length > 0) {
                loadSong(currentSongIndex);
            } else {
                noBooksMessage.textContent = "You don't have any audiobooks in your subscription.";
            }
            
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
            audioPlayer.addEventListener('play', startMusicIconsAnimation);
            audioPlayer.addEventListener('pause', stopMusicIconsAnimation);
        }

        // Setup music icons
        function setupMusicIcons() {
            // Clear any existing icons
            musicIconsContainer.innerHTML = '';
            musicIcons = [];
            
            // Create initial set of icons
            for (let i = 0; i < 30; i++) {
                createMusicIcon(true);
            }
        }

        // Create a single music icon
        function createMusicIcon(initial = false) {
            const icon = document.createElement('div');
            icon.className = 'music-icon';
            
            // Random properties
            const symbol = musicSymbols[Math.floor(Math.random() * musicSymbols.length)];
            const color = colors[Math.floor(Math.random() * colors.length)];
            const size = Math.random() * 1.2 + 1.2; // 0.8rem to 1.6rem
            const left = Math.random() * 100;
            const speed = Math.random() * 2 + 10; // 1 to 3 seconds to reach top
            const delay = initial ? Math.random() * 5 : 0;
            const opacity = Math.random() * 0.5 + 0.3; // 0.3 to 0.8
            
            // Set styles
            icon.textContent = symbol;
            icon.style.color = color;
            icon.style.fontSize = `${size}rem`;
            icon.style.left = `${left}%`;
            icon.style.bottom = initial ? `${Math.random() * 20}%` : '-5%';
            icon.style.opacity = opacity;
            icon.style.transform = `rotate(${Math.random() * 360}deg)`;
            icon.style.animation = `floatUp ${speed}s linear ${delay}s infinite`;
            
            // Add to container
            musicIconsContainer.appendChild(icon);
            musicIcons.push(icon);
            
            // Remove icon when animation completes
            icon.addEventListener('animationend', function() {
                if (isPlaying) {
                    // Reset position and animate again
                    icon.style.bottom = '-5%';
                    icon.style.left = `${Math.random() * 100}%`;
                    icon.style.animation = `floatUp ${speed}s linear 0s infinite`;
                } else {
                    // Remove icon if not playing
                    musicIconsContainer.removeChild(icon);
                    musicIcons = musicIcons.filter(i => i !== icon);
                }
            });
        }

        // Animation for floating up
        function addFloatingAnimation() {
            const style = document.createElement('style');
            style.textContent = `
                @keyframes floatUp {
                    0% {
                        transform: translateY(0) rotate(${Math.random() * 360}deg);
                        opacity: 0.7;
                    }
                    100% {
                        transform: translateY(-105vh) rotate(${Math.random() * 360}deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Start music icons animation
        function startMusicIconsAnimation() {
            stopMusicIconsAnimation();
            addFloatingAnimation();
            
            // Create initial icons
            setupMusicIcons();
            
            // Add new icons periodically
            const addIconInterval = setInterval(() => {
                if (isPlaying && musicIcons.length < 50) {
                    createMusicIcon();
                }
            }, 300);
            
            // Store interval ID to clear later
            musicIconsContainer.dataset.intervalId = addIconInterval;
        }

        // Stop music icons animation
        function stopMusicIconsAnimation() {
            // Clear the interval for adding new icons
            if (musicIconsContainer.dataset.intervalId) {
                clearInterval(parseInt(musicIconsContainer.dataset.intervalId));
            }
            
            // Clear all animations
            cancelAnimationFrame(animationFrameId);
        }

        // Fetch user's audiobooks from backend
        async function fetchUserAudiobooks() {
            try {
                const response = await fetch('get_user_audiobooks.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch audiobooks');
                }
                const data = await response.json();
                
                if (data.success && data.audiobooks.length > 0) {
                    audiobooks = data.audiobooks;
                    renderAudiobookList();
                } else {
                    noBooksMessage.textContent = "You don't have any audiobooks in your subscription.";
                }
            } catch (error) {
                console.error('Error fetching audiobooks:', error);
                noBooksMessage.textContent = "Error loading your audiobooks. Please try again later.";
            }
        }

        // Render the audiobook list
        function renderAudiobookList() {
            songList.innerHTML = '';
            
            audiobooks.forEach((audiobook, index) => {
                const li = document.createElement('li');
                li.className = 'song-item' + (index === currentSongIndex ? ' active' : '');
                li.dataset.index = index;
                
                li.innerHTML = `
                    <img src="${audiobook.poster_url || 'assets/default-audiobook-cover.jpg'}" 
                         alt="Cover" class="song-cover">
                    <div class="song-info">
                        <div class="song-title">${audiobook.title}</div>
                        <div class="song-artist">${audiobook.writer}</div>
                    </div>
                `;
                
                li.addEventListener('click', () => {
                    currentSongIndex = index;
                    loadSong(currentSongIndex);
                    if (!isPlaying) {
                        togglePlay();
                    }
                });
                
                songList.appendChild(li);
            });
        }

        // Load song
        function loadSong(index) {
            if (audiobooks.length === 0) return;
            
            const audiobook = audiobooks[index];
            
            // Update UI
            albumArt.src = audiobook.poster_url || 'assets/default-audiobook-cover.jpg';
            albumArt.alt = audiobook.title;
            songName.textContent = audiobook.title;
            artistName.textContent = audiobook.writer;
            
            // Update background art
            backgroundArt.style.backgroundImage = `url(${audiobook.poster_url || 'assets/default-audiobook-cover.jpg'})`;
            
            // Update active state in list
            const songItems = document.querySelectorAll('.song-item');
            songItems.forEach(item => item.classList.remove('active'));
            if (songItems[index]) {
                songItems[index].classList.add('active');
            }
            
            // Load audio
            audioPlayer.src = audiobook.audio_url;
            
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
            if (audiobooks.length === 0) return;
            
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
            if (audiobooks.length === 0) return;
            
            currentSongIndex--;
            if (currentSongIndex < 0) {
                currentSongIndex = audiobooks.length - 1;
            }
            loadSong(currentSongIndex);
        }

        // Next song
        function nextSong() {
            if (audiobooks.length === 0) return;
            
            currentSongIndex++;
            if (currentSongIndex > audiobooks.length - 1) {
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
            if (isNaN(seconds)) return "0:00";
            
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs < 10 ? '0' + secs : secs}`;
        }

        // Initialize player when DOM loads
        document.addEventListener('DOMContentLoaded', initPlayer);
    </script>
</body>
</html>