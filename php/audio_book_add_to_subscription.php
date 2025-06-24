<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscription - Audio Books | BookHub</title>
    <style>
    :root {
        --primary-color: #57abd2;
        --primary-dark: #3d8eb4;
        --secondary-color: #f8f5fc;
        --accent-color: rgb(223, 219, 227);
        --text-color: #333;
        --text-light: #666;
        --light-purple: #e6d9f2;
        --dark-text: #212529;
        --light-text: #f8f9fa;
        --card-bg: #ffffff;
        --aside-bg: #f0f2f5;
        --nav-hover: #e0e0e0;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --border-color: #e0e0e0;
        --hover-bg: #f5f5f5;
        --even-row-bg: #f9f9f9;
        --header-bg: #f0f0f0;
        --header-text: #333;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        --plan-bg: linear-gradient(135deg, #57abd2 0%, #3d8eb4 100%);
    }

    .dark-mode {
        --primary-color: #57abd2;
        --primary-dark: #4a9bc1;
        --secondary-color: #2d3748;
        --accent-color: #4a5568;
        --text-color: #f8f9fa;
        --text-light: #a0aec0;
        --light-purple: #4a5568;
        --dark-text: #f8f9fa;
        --light-text: #212529;
        --card-bg: #1a202c;
        --aside-bg: #1a202c;
        --nav-hover: #4a5568;
        --border-color: #4a5568;
        --hover-bg: #2d3748;
        --even-row-bg: #2d3748;
        --header-bg: #1a202c;
        --header-text: #f8f9fa;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
        --plan-bg: linear-gradient(135deg, #1a3d4a 0%, #123140 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        transition: background-color 0.3s, color 0.3s;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--secondary-color);
        color: var(--text-color);
        line-height: 1.6;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    main {
        padding: 1.5rem 5%;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
        flex: 1;
    }

    /* Compact Plan Overview Section */
    .plan-overview {
        background: var(--plan-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        color: white;
    }

    .plan-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .plan-title {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .plan-status {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .plan-details-container {
        display: flex;
        gap: 1.5rem;
    }

    .plan-features {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .feature-item:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .feature-icon {
        width: 40px;
        height: 40px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .feature-icon svg {
        width: 18px;
        height: 18px;
        fill: white;
    }

    .feature-text {
        flex: 1;
    }

    .feature-label {
        font-size: 0.8rem;
        opacity: 0.8;
        margin-bottom: 0.1rem;
    }

    .feature-value {
        font-size: 1rem;
        font-weight: 600;
    }

    .plan-progress {
        flex: 0 0 300px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1rem;
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .progress-item {
        margin-bottom: 1rem;
    }

    .progress-item:last-child {
        margin-bottom: 0;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .progress-title {
        font-weight: 500;
    }

    .progress-value {
        font-weight: 600;
    }

    .progress-bar {
        height: 6px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.3rem;
    }

    .progress-fill {
        height: 100%;
        background-color: white;
        border-radius: 3px;
        width: 70%;
    }

    .progress-text {
        font-size: 0.75rem;
        opacity: 0.8;
        display: flex;
        justify-content: space-between;
    }

    .days-left {
        text-align: center;
        padding: 0.8rem;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        margin-top: 1rem;
    }

    .days-left-value {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.2rem;
    }

    .days-left-label {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    /* Add Books Header Section */
    .add-books-header {
        background-color: var(--card-bg);
        border-radius: 10px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .add-books-header h2 {
        font-size: 1.3rem;
        color: var(--primary-color);
        margin: 0;
    }

    .add-book-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        background-color: var(--primary-color);
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s;
        white-space: nowrap;
    }

    .add-book-btn:hover {
        background-color: var(--primary-dark);
    }

    .add-book-btn svg {
        fill: white;
    }

    /* Audio Book Grid */
    .catalog-container {
        display: flex;
        gap: 1.5rem;
    }

    .book-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .book-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
    }

    .book-card:hover {
        transform: translateY(-5px);
    }

    .book-cover {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .book-info {
        padding: 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .book-title {
        font-weight: 600;
        margin-bottom: 0.3rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .book-author {
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .book-rating {
        color: var(--warning-color);
        font-size: 0.9rem;
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
    }

    .star-icon {
        margin-right: 0.3rem;
    }

    /* Audio Player Styles - Compact Version */
    .audio-player {
        width: 100%;
        height: 36px; /* Reduced height */
        margin: 0.5rem 0;
        min-height: 36px; /* Ensures minimum height */
    }

    .audio-player::-webkit-media-controls-panel {
        background-color: var(--primary-color);
        border-radius: 5px;
        height: 36px;
    }

    .audio-player::-webkit-media-controls-play-button,
    .audio-player::-webkit-media-controls-mute-button {
        filter: brightness(0) invert(1);
    }

    .audio-player::-webkit-media-controls-current-time-display,
    .audio-player::-webkit-media-controls-time-remaining-display {
        color: white;
        font-size: 0.8rem;
    }

    .audio-player::-webkit-media-controls-timeline {
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 2px;
    }

    /* Hide download button */
    .audio-player::-webkit-media-controls-download-button {
        display: none !important;
    }

    .audio-player::-webkit-media-controls-enclosure {
        border-radius: 5px;
        height: 36px;
    }

    .add-button {
        width: 100%;
        padding: 0.5rem;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
        margin-top: auto;
    }

    .add-button:hover {
        background-color: var(--primary-dark);
    }

    /* Genre Sidebar */
    .genre-sidebar {
        width: 250px;
        background-color: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        padding: 1rem;
        height: fit-content;
    }

    .genre-title {
        font-size: 1.1rem;
        margin-bottom: 0.8rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--primary-color);
    }

    .genre-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .genre-item {
        padding: 0.6rem 0.8rem;
        background-color: var(--hover-bg);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .genre-item:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .genre-item.active {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
    }

    @media (max-width: 1200px) {
        .plan-progress {
            flex: 0 0 250px;
        }
    }

    @media (max-width: 992px) {
        .plan-details-container {
            flex-direction: column;
            gap: 1rem;
        }
        
        .plan-features {
            grid-template-columns: 1fr;
        }
        
        .plan-progress {
            flex: 1;
        }
    }

    @media (max-width: 768px) {
        main {
            padding: 1rem;
        }

        .catalog-container {
            flex-direction: column;
        }

        .genre-sidebar {
            width: 100%;
            margin-bottom: 1rem;
        }

        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .book-cover {
            height: 160px;
        }

        .audio-player {
            height: 32px;
            min-height: 32px;
        }

        .audio-player::-webkit-media-controls-panel,
        .audio-player::-webkit-media-controls-enclosure {
            height: 32px;
        }
    }

    @media (max-width: 576px) {
        .plan-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .book-cover {
            height: 140px;
        }

        .book-info {
            padding: 0.8rem;
        }

        .book-title {
            font-size: 0.9rem;
        }

        .book-author, .book-rating {
            font-size: 0.8rem;
        }

        .audio-player {
            height: 28px;
            min-height: 28px;
            margin: 0.3rem 0;
        }

        .audio-player::-webkit-media-controls-panel,
        .audio-player::-webkit-media-controls-enclosure {
            height: 28px;
        }

        .add-button {
            padding: 0.4rem;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 400px) {
        .book-grid {
            grid-template-columns: 1fr 1fr;
        }

        .feature-item {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
            padding: 0.8rem 0.5rem;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
        }

        .feature-text {
            width: 100%;
        }
    }
</style>
</head>

<body>
    <?php include_once("../header.php") ?>
    <main>
        <section>
            <!-- Compact Plan Overview Section -->
            <div class="plan-overview">
                <div class="plan-header">
                    <h1 class="plan-title">Premium Subscription</h1>
                    <div class="plan-status">Active</div>
                </div>
                
                <div class="plan-details-container">
                    <div class="plan-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M21 5h-8v14h8V5zm-10 0H3v14h8V5z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <div class="feature-label">Audio Books per month</div>
                                <div class="feature-value">5</div>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <div class="feature-label">Audio books remaining</div>
                                <div class="feature-value">3</div>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <div class="feature-label">Access to</div>
                                <div class="feature-value">All Genres</div>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <div class="feature-label">Premium Support</div>
                                <div class="feature-value">24/7</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="plan-progress">
                        <div class="progress-item">
                            <div class="progress-header">
                                <div class="progress-title">Audio Books Remaining</div>
                                <div class="progress-value">3/5</div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 60%"></div>
                            </div>
                            <div class="progress-text">
                                <span>Used: 2</span>
                                <span>Reset: June 30</span>
                            </div>
                        </div>
                        
                        <div class="days-left">
                            <div class="days-left-value">15</div>
                            <div class="days-left-label">days left</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Books Header Section -->
            <div class="add-books-header">
                <div class="header-container">
                    <h2>Add Audio Books</h2>
                    <a href="audio_book_add_to_subscription.php" class="add-book-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Add New Audio Book
                    </a>
                </div>
            </div>

            <!-- Catalog Section with Block Genre Display -->
            <div class="catalog-container">
                <aside class="genre-sidebar">
                    <h3 class="genre-title">Browse Genres</h3>
                    <div class="genre-list">
                        <div class="genre-item active">All Genres</div>
                        <div class="genre-item">Fiction</div>
                        <div class="genre-item">Mystery & Thriller</div>
                        <div class="genre-item">Science Fiction</div>
                        <div class="genre-item">Fantasy</div>
                        <div class="genre-item">Romance</div>
                        <div class="genre-item">Biography</div>
                        <div class="genre-item">History</div>
                        <div class="genre-item">Self-Help</div>
                        <div class="genre-item">Business</div>
                    </div>
                </aside>

                <div class="book-grid">
                    <!-- Audio Book Cards with Players -->
                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/57abd2/ffffff?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">The Silent Patient</h3>
                            <p class="book-author">Alex Michaelides</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.5
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>

                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/3d8eb4/ffffff?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">Atomic Habits</h3>
                            <p class="book-author">James Clear</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.8
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>

                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/e6d9f2/333333?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">Project Hail Mary</h3>
                            <p class="book-author">Andy Weir</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.7
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>

                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/f8f5fc/333333?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">The Midnight Library</h3>
                            <p class="book-author">Matt Haig</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.3
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>

                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/dfdbe3/333333?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">Educated</h3>
                            <p class="book-author">Tara Westover</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.6
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>

                    <div class="book-card">
                        <img src="https://via.placeholder.com/250x200/57abd2/ffffff?text=Audio+Book" alt="Audio Book Cover" class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title">Where the Crawdads Sing</h3>
                            <p class="book-author">Delia Owens</p>
                            <div class="book-rating">
                                <span class="star-icon">★</span> 4.8
                            </div>
                            <audio controls class="audio-player">
                                <source src="sample-audio.mp3" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button class="add-button">Add to My Subscription</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include_once("../footer.php") ?>
</body>

</html>