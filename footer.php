<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>Book Haven</h3>
            <p>Your literary paradise since 2010. We're dedicated to bringing you the best books from around the world.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Goodreads"><i class="fab fa-goodreads"></i></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="genres.php">Genres</a></li>
                <li><a href="about.php">About Us</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Customer Service</h3>
            <ul>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="faq.php">FAQs</a></li>
                <li><a href="shipping.php">Shipping Policy</a></li>
                <li><a href="returns.php">Returns</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Contact Info</h3>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> 123 Book Street, Library City</li>
                <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                <li><i class="fas fa-envelope"></i> info@bookhaven.com</li>
                <li><i class="fas fa-clock"></i> Mon-Fri: 9AM - 6PM</li>
            </ul>
        </div>
    </div>
    
    <div class="copyright">
        <p>&copy; <?php echo date('Y'); ?> Book Haven. All rights reserved.</p>
    </div>
</footer>

<style>
    footer {
        background-color: var(--footer-bg, #343a40);
        color: var(--footer-text, #f8f9fa);
        padding: 3rem 5% 1rem;
        margin-top: 3rem;
    }
    
    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .footer-section {
        margin-bottom: 1.5rem;
    }
    
    .footer-section h3 {
        margin-bottom: 1.2rem;
        font-size: 1.2rem;
        color: var(--accent-color);
    }
    
    .footer-section ul {
        list-style: none;
    }
    
    .footer-section ul li {
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .footer-section a {
        color: var(--footer-text);
        text-decoration: none;
        opacity: 0.8;
        transition: opacity 0.3s;
    }
    
    .footer-section a:hover {
        opacity: 1;
        text-decoration: underline;
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .social-links a {
        color: var(--footer-text);
        font-size: 1.2rem;
        opacity: 0.8;
        transition: opacity 0.3s;
    }
    
    .social-links a:hover {
        opacity: 1;
    }
    
    .copyright {
        text-align: center;
        padding-top: 2rem;
        margin-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .footer-container {
            grid-template-columns: 1fr;
        }
    }
</style>

</body>
</html>