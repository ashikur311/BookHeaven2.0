<?php include 'header.php'; ?>

<div class="two-column">
    <div style="flex: 2;">
        <div class="card">
            <div style="display: flex; align-items: center; margin-bottom: 20px;">
                <img src="https://via.placeholder.com/120x120?text=Fantasy" alt="Fantasy Lovers" style="width: 80px; height: 80px; border-radius: 8px; margin-right: 20px;">
                <div>
                    <h1>Fantasy Lovers</h1>
                    <div style="display: flex; color: var(--text-light); margin-bottom: 10px;">
                        <span style="margin-right: 15px;"><i class="fas fa-users"></i> 1,245 members</span>
                        <span><i class="fas fa-comment"></i> 45 posts today</span>
                    </div>
                    <p>Discuss your favorite fantasy books and authors with like-minded readers.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <a href="#" class="btn">Join Community</a>
                <a href="messages.php" class="btn btn-outline">Message Members</a>
            </div>
        </div>
        
        <div class="card">
            <h2>Create Post</h2>
            <form>
                <div class="form-group">
                    <textarea placeholder="What's on your mind?"></textarea>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <button type="button" style="background: none; border: none; color: var(--primary-color); cursor: pointer; margin-right: 15px;">
                            <i class="fas fa-image"></i> Photo
                        </button>
                        <button type="button" style="background: none; border: none; color: var(--primary-color); cursor: pointer;">
                            <i class="fas fa-link"></i> Link
                        </button>
                    </div>
                    <button type="submit" class="btn">Post</button>
                </div>
            </form>
        </div>
        
        <div class="post card">
            <div class="post-header">
                <img src="https://via.placeholder.com/50x50?text=User1" alt="User" class="post-user-img">
                <div>
                    <div class="post-user-name">Sarah Johnson</div>
                    <div class="post-time">2 hours ago</div>
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
            
            <div class="comment">
                <img src="https://via.placeholder.com/40x40?text=User2" alt="User" class="comment-user-img">
                <div class="comment-content">
                    <div class="comment-user-name">Michael Chen</div>
                    <div class="comment-text">I loved it too! The magic system is so unique and well thought out. Have you read "The Wise Man's Fear" yet?</div>
                    <div class="comment-time">1 hour ago</div>
                    <div class="comment-actions">
                        <span class="comment-action">Like</span>
                        <span class="comment-action">Reply</span>
                    </div>
                </div>
            </div>
            
            <div class="comment">
                <img src="<?php echo $current_user->user_profile; ?>" alt="User" class="comment-user-img">
                <div class="comment-content">
                    <div class="comment-user-name"><?php echo $current_user->username; ?></div>
                    <div class="comment-text">Not yet! I'm trying to decide if I should dive right in or take a break with something different first.</div>
                    <div class="comment-time">30 minutes ago</div>
                    <div class="comment-actions">
                        <span class="comment-action">Like</span>
                        <span class="comment-action">Reply</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <input type="text" placeholder="Write a comment..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
        </div>
        
        <div class="post card">
            <div class="post-header">
                <img src="https://via.placeholder.com/50x50?text=User3" alt="User" class="post-user-img">
                <div>
                    <div class="post-user-name">Emma Rodriguez</div>
                    <div class="post-time">1 day ago</div>
                </div>
            </div>
            <div class="post-content">
                <p>Looking for recommendations for fantasy books with strong female protagonists. I've already read Mistborn and Poppy War. What else should I check out?</p>
            </div>
            <div class="post-actions">
                <div class="post-action">
                    <i class="far fa-thumbs-up"></i> Like (28)
                </div>
                <div class="post-action">
                    <i class="far fa-comment"></i> Comment (9)
                </div>
                <div class="post-action">
                    <i class="fas fa-share"></i> Share
                </div>
            </div>
        </div>
    </div>
    
    <div style="flex: 1;">
        <div class="card">
            <h2>About</h2>
            <p>A community for fans of fantasy literature to discuss books, share recommendations, and connect with fellow readers.</p>
            <p><strong>Created:</strong> January 15, 2023</p>
            <p><strong>Admin:</strong> Sarah Johnson</p>
        </div>
        
        <div class="card">
            <h2>Community Rules</h2>
            <ol style="padding-left: 20px; margin-top: 10px;">
                <li>Be respectful to all members</li>
                <li>No spoilers without warning</li>
                <li>Keep discussions book-related</li>
                <li>No self-promotion without permission</li>
                <li>Report any inappropriate content</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Members (1,245)</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
                <img src="https://via.placeholder.com/40x40?text=User1" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User2" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User3" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User4" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User5" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User6" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User7" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
                <img src="https://via.placeholder.com/40x40?text=User8" alt="Member" style="width: 40px; height: 40px; border-radius: 50%;">
            </div>
            <a href="#" style="display: inline-block; margin-top: 10px;">See all members</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>