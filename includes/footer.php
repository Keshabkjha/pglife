<?php if (isset($_SESSION['user_id'])) { ?>
    <!-- Floating Chat Widget -->
    <div id="chat-box-widget">
        <div class="chat-header" id="chat-widget-header">
            <div class="chat-contact-info">
                <img src="img/man.png" id="chat-widget-avatar" class="chat-avatar" alt="Avatar" />
                <div class="chat-title-text">
                    <div id="chat-widget-contact-name" class="chat-contact-name">Contact Name</div>
                    <div id="chat-widget-property-context" class="chat-property-context">Property Name</div>
                </div>
            </div>
            <div class="chat-controls">
                <button type="button" class="chat-control-btn" id="chat-widget-minimize" title="Minimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="chat-control-btn" id="chat-widget-close" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Messages Area -->
        <div class="chat-messages-body" id="chat-widget-messages">
            <!-- Messages populated dynamically -->
        </div>

        <!-- Typing Indicator -->
        <div class="typing-indicator" id="chat-widget-typing">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <span class="ml-1">typing...</span>
        </div>

        <!-- Quick Reply Chips -->
        <div class="chat-quick-replies" id="chat-widget-quick-replies">
            <!-- Rendered depending on who is logged in -->
        </div>

        <!-- Bargain Rent Offer Input Bar -->
        <div class="chat-offer-input-container" id="chat-widget-offer-container">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text">₹</span>
                </div>
                <input type="number" id="chat-widget-offer-input" class="form-control" placeholder="Enter monthly offer..." min="1" />
                <div class="input-group-append">
                    <button class="btn btn-warning font-weight-bold text-dark" id="chat-widget-submit-offer" type="button">Send Offer</button>
                </div>
            </div>
        </div>

        <!-- Message Input Footer -->
        <form id="chat-widget-form" class="chat-footer">
            <input type="hidden" id="chat-widget-receiver-id" />
            <input type="hidden" id="chat-widget-property-id" />
            
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { ?>
                <button type="button" class="chat-action-btn bargain-msg" id="chat-widget-toggle-bargain" title="Bargain Monthly Rent">
                    <i class="fas fa-handshake"></i>
                </button>
            <?php } ?>
            
            <textarea id="chat-widget-input" class="chat-input" placeholder="Type a message..." rows="1" required></textarea>
            
            <button type="submit" class="chat-action-btn send-msg" title="Send Message">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <script type="text/javascript">
        window.csrfToken = "<?= $_SESSION['csrf_token'] ?>";
        window.userId = <?= (int)$_SESSION['user_id'] ?>;
        window.userRole = "<?= $_SESSION['role'] ?? 'seeker' ?>";
    </script>
<?php } ?>

<div class="footer">
    <div class="page-container footer-container">
        <div class="footer-cities">
            <div class="footer-city">
                <a href="/properties/Delhi">PG in Delhi</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Mumbai">PG in Mumbai</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Bengaluru">PG in Bengaluru</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Hyderabad">PG in Hyderabad</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Kolkata">PG in Kolkata</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Chennai">PG in Chennai</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Pune">PG in Pune</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Ahmedabad">PG in Ahmedabad</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Jaipur">PG in Jaipur</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Noida">PG in Noida</a>
            </div>
            <div class="footer-city">
                <a href="/properties/Gurgaon">PG in Gurgaon</a>
            </div>
        </div>
        <div class="footer-copyright">© <?= date('Y') ?> Copyright Keshabkjha. All rights reserved.</div>
    </div>
</div>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>   
<script type="text/javascript" src="js/common.js"></script>
<?php if (isset($_SESSION['user_id'])) { ?>
<script type="text/javascript" src="js/chat.js"></script>
<?php } ?>