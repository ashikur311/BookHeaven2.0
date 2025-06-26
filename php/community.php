<?php include 'header.php'; ?>

<div class="two-column">
    <div style="flex: 2;">
        <div class="post card">
            <div class="post-header">
                <img src="https://via.placeholder.com/50x50?text=User1" alt="User" class="post-user-img">
                <div>
                    <div class="post-user-name">Sarah Johnson</div>
                    <div class="post-time">2 hours ago in Fantasy Lovers</div>
                </div>
            </div>
            <div class="post-content">
                <p>Just finished reading "The Name of the Wind" by Patrick Rothfuss. Absolutely blown away by the prose and world-building! What did everyone else think of it?</p>
                <img src="https://via.placeholder.com/600x300?text=Name+of+the+Wind" alt="Book Cover" class="post-image">
            </div>
            <div class="post-actions">
                <div class="post-action">
                    <i class="far fa-thumbs-up"></i> Like (42)
                </div>
                <div class="post-action">
                    <i class="far fa-comment"></i> Comment (15)
                </div>
                <div class="post-action">
                    <i class="fas fa-share"></i> Share
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>15 Comments</h3>
            
            <div class="comment">
                <img src="https://via.placeholder.com/40x40?text=User2" alt="User" class="comment-user-img">
                <div class="comment-content">
                    <div class="comment-user-name">Michael Chen</div>
                    <div class="comment-text">I loved it too! The magic system is so unique and well thought out. Have you read "The Wise Man's Fear" yet?</div>
                    <div class="comment-time">1 hour ago</div>
                    <div class="comment-actions">
                        <span class="comment-action">Like (8)</span>
                        <span class="comment-action">Reply</span>
                    </div>
                    
                    <div class="comment" style="margin-top: 10px;">
                        <img src="<?php echo $current_user->user_profile; ?>" alt="User" class="comment-user-img">
                        <div class="comment-content">
                            <div class="comment-user-name"><?php echo $current_user->username; ?></div>
                            <div class="comment-text">Not yet! I'm trying to decide if I should dive right in or take a break with something different first.</div>
                            <div class="comment-time">30 minutes ago</div>
                            <div class="comment-actions">
                                <span class="comment-action">Like (3)</span>
                                <span class="comment-action">Reply</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="comment">
                <img src="https://via.placeholder.com/40x40?text=User3" alt="User" class="comment-user-img">
                <div class="comment-content">
                    <div class="comment-user-name">Emma Rodriguez</div>
                    <div class="comment-text">One of my all-time favorites! The way Rothfuss writes is just magical. I've read it three times and still find new details.</div>
                    <div class="comment-time">45 minutes ago</div>
                    <div class="comment-actions">
                        <span class="comment-action">Like (12)</span>
                        <span class="comment-action">Reply</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <div style="display: flex;">
                    <img src="<?php echo $current_user->user_profile; ?>" alt="User" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                    <input type="text" placeholder="Write a comment..." style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
                </div>
            </div>
        </div>
    </div>
    
    <div style="flex: 1;">
        <div class="card">
            <h3>About the Community</h3>
            <div style="display: flex; align-items: center; margin: 15px 0;">
                <img src="https://via.placeholder.com/50x50?text=Fantasy" alt="Fantasy Lovers" style="width: 50px; height: 50px; border-radius: 8px; margin-right: 10px;">
                <div>
                    <div style="font-weight: 600;">Fantasy Lovers</div>
                    <div style="font-size: 14px; color: var(--text-light);">1,245 members</div>
                </div>
            </div>
            <p style="margin-bottom: 15px;">A community for fans of fantasy literature to discuss books, share recommendations, and connect with fellow readers.</p>
            <a href="view.php" class="btn" style="display: block; text-align: center;">Visit Community</a>
        </div>
        
        <div class="card">
            <h3>Similar Posts</h3>
            <div style="margin-top: 15px;">
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 600; margin-bottom: 5px;">Looking for similar books to The Kingkiller Chronicle</div>
                    <div style="font-size: 14px; color: var(--text-light);">Posted in Fantasy Lovers</div>
                </div>
                
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 600; margin-bottom: 5px;">Patrick Rothfuss announces new book release date</div>
                    <div style="font-size: 14px; color: var(--text-light);">Posted in Fantasy Lovers</div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: 600; margin-bottom: 5px;">Discussion: Best fantasy books of the decade</div>
                    <div style="font-size: 14px; color: var(--text-light);">Posted in Fantasy Lovers</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>