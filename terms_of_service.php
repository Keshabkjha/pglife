<?php
session_start();
require_once "includes/database_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PG Life Terms of Service - Rules and conditions for using our PG accommodation platform.">
    <title>Terms of Service | PG Life</title>
    <?php include "includes/head_links.php"; ?>
    <link href="css/legal.css" rel="stylesheet">
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="legal-page">
        <div class="legal-container">
            <div class="legal-header">
                <h1>Terms of Service</h1>
                <p class="legal-updated">Last updated: June 20, 2026</p>
            </div>

            <div class="legal-section">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing or using PG Life ("the Platform"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you must not use the Platform.</p>
                <p>These Terms constitute a legally binding agreement between you ("User", "you", "your") and PG Life ("we", "us", "our").</p>
            </div>

            <div class="legal-section">
                <h2>2. Description of Service</h2>
                <p>PG Life is an online platform that facilitates the discovery of Paying Guest (PG) accommodations across Indian cities. The Platform enables:</p>
                <ul>
                    <li><strong>PG Seekers</strong> to browse property listings, book properties, communicate with owners, submit rent payment proofs, and leave reviews.</li>
                    <li><strong>PG Owners</strong> to list their properties, manage room availability, communicate with seekers, verify payment proofs, and manage maintenance requests.</li>
                </ul>
                <div class="legal-highlight">
                    <strong>Important:</strong> PG Life is an <em>intermediary platform</em> that connects seekers and owners. We do not own, operate, manage, or inspect any listed properties. We do not guarantee the accuracy of listings, the condition of properties, or the conduct of any user.
                </div>
            </div>

            <div class="legal-section">
                <h2>3. Eligibility</h2>
                <p>To use PG Life, you must:</p>
                <ul>
                    <li>Be at least 18 years of age.</li>
                    <li>Provide accurate, current, and complete information during registration.</li>
                    <li>Have a valid email address and phone number.</li>
                </ul>
                <p>By creating an account, you represent and warrant that you meet these eligibility requirements.</p>
            </div>

            <div class="legal-section">
                <h2>4. User Accounts</h2>

                <h3>4.1 Registration</h3>
                <p>You must create an account to access most features. During registration, you will choose a profile type:</p>
                <ul>
                    <li><strong>Seeker:</strong> For individuals looking for PG accommodation.</li>
                    <li><strong>Owner:</strong> For individuals who own or manage PG properties.</li>
                </ul>

                <h3>4.2 Account Security</h3>
                <p>You are responsible for:</p>
                <ul>
                    <li>Maintaining the confidentiality of your password.</li>
                    <li>All activities that occur under your account.</li>
                    <li>Promptly notifying us of any unauthorized use of your account.</li>
                </ul>

                <h3>4.3 Account Verification</h3>
                <p>Email verification via OTP is required during signup. Property owners may optionally submit KYC documents for identity verification. PG Life reserves the right to verify or reject any submitted documents at its discretion.</p>
            </div>

            <div class="legal-section">
                <h2>5. User Conduct</h2>
                <p>You agree not to:</p>
                <ul>
                    <li>Create fake accounts or provide false information.</li>
                    <li>List properties you do not own or are not authorized to represent.</li>
                    <li>Post misleading, inaccurate, or fraudulent property listings.</li>
                    <li>Use the Platform for any unlawful purpose.</li>
                    <li>Harass, threaten, or abuse other users through the messaging system.</li>
                    <li>Attempt to gain unauthorized access to other users' accounts or the Platform's systems.</li>
                    <li>Upload malicious files, documents containing viruses, or inappropriate content.</li>
                    <li>Submit fraudulent payment proofs (fake UTR numbers or altered screenshots).</li>
                    <li>Post fake, misleading, or defamatory reviews.</li>
                    <li>Use automated tools (bots, scrapers) to access the Platform.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>6. Bookings and Payments</h2>

                <h3>6.1 Bookings</h3>
                <p>When you book a property through PG Life, you are expressing your intent to rent. The booking creates a connection between you and the property owner. The actual rental agreement is between you and the property owner, not with PG Life.</p>

                <h3>6.2 Payments</h3>
                <p>PG Life does <strong>not</strong> process, hold, or transfer any monetary funds. The payment flow works as follows:</p>
                <ol>
                    <li>The seeker makes a UPI payment directly to the property owner's UPI ID (displayed on the dashboard).</li>
                    <li>The seeker submits a payment proof (UTR number and optional screenshot) through the Platform.</li>
                    <li>The property owner reviews and verifies or rejects the payment proof.</li>
                </ol>
                <div class="legal-highlight">
                    PG Life is not a payment processor. We are not responsible for payment disputes, failed transactions, or any financial loss arising from transactions between users. All rent payments are made directly between seekers and owners at their own risk.
                </div>

                <h3>6.3 Rent Bargaining</h3>
                <p>The Platform provides a chat-based rent negotiation feature. Any agreed-upon rent amount through bargaining is a mutual agreement between the seeker and owner. PG Life does not mediate or enforce these agreements.</p>

                <h3>6.4 Cancellation</h3>
                <p>Bookings can be cancelled through the Platform. However, any refund of advance payments or security deposits is a matter between the seeker and the property owner. PG Life has no role in refund processing.</p>
            </div>

            <div class="legal-section">
                <h2>7. Property Listings</h2>
                <p>Property owners are solely responsible for the accuracy of their listings, including but not limited to:</p>
                <ul>
                    <li>Property descriptions, photographs, and amenity lists.</li>
                    <li>Rent amounts and room availability.</li>
                    <li>Property address and location information.</li>
                    <li>Safety and cleanliness standards.</li>
                </ul>
                <p>PG Life does not independently verify property listings. We recommend that seekers physically visit and inspect any property before making financial commitments.</p>
            </div>

            <div class="legal-section">
                <h2>8. Reviews and Ratings</h2>
                <p>Users may leave reviews and ratings for properties. By submitting a review, you confirm that:</p>
                <ul>
                    <li>The review is based on your genuine experience.</li>
                    <li>The review does not contain defamatory, abusive, or false content.</li>
                </ul>
                <p>PG Life reserves the right to remove reviews that violate these Terms.</p>
            </div>

            <div class="legal-section">
                <h2>9. Intellectual Property</h2>
                <p>The PG Life platform, including its design, logo, code, and content structure, is owned by PG Life and protected under applicable intellectual property laws. PG Life is released under the MIT License for its source code.</p>
                <p>Property photos, descriptions, and listing content submitted by owners remain the property of the respective owners. By submitting content, you grant PG Life a non-exclusive, royalty-free license to display your content on the Platform.</p>
            </div>

            <div class="legal-section">
                <h2>10. Limitation of Liability</h2>
                <p>To the maximum extent permitted by applicable law:</p>
                <ul>
                    <li>PG Life is provided on an "as is" and "as available" basis without warranties of any kind.</li>
                    <li>We do not guarantee uninterrupted, timely, or error-free service.</li>
                    <li>We are not liable for any indirect, incidental, special, or consequential damages.</li>
                    <li>We are not liable for any disputes between seekers and property owners.</li>
                    <li>We are not liable for any financial loss arising from booking, payment, or rental agreements made through the Platform.</li>
                    <li>We are not liable for the condition, safety, or legality of any listed property.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>11. Indemnification</h2>
                <p>You agree to indemnify and hold harmless PG Life, its operators, and affiliates from any claims, damages, losses, or expenses arising from:</p>
                <ul>
                    <li>Your use of the Platform.</li>
                    <li>Your violation of these Terms.</li>
                    <li>Your violation of any rights of another user or third party.</li>
                    <li>Any content you submit through the Platform.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>12. Termination</h2>
                <p>We reserve the right to suspend or terminate your account at any time, with or without notice, for conduct that we determine violates these Terms or is harmful to other users, the Platform, or third parties.</p>
                <p>You may deactivate your account at any time by contacting us (see Privacy Policy, Section 9).</p>
            </div>

            <div class="legal-section">
                <h2>13. Modifications to Terms</h2>
                <p>We reserve the right to modify these Terms at any time. Updated Terms will be posted on this page with a revised "Last updated" date. Your continued use of the Platform after changes constitutes acceptance of the modified Terms.</p>
            </div>

            <div class="legal-section">
                <h2>14. Governing Law and Jurisdiction</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of India. Any disputes arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction of the courts in [Your City], India.</p>
            </div>

            <div class="legal-section">
                <h2>15. Severability</h2>
                <p>If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions shall continue in full force and effect.</p>
            </div>

            <div class="legal-section">
                <h2>16. Contact</h2>
                <p>For questions about these Terms, please contact us at:</p>
                <div class="legal-contact">
                    <p><strong>Email:</strong> [support@pglife.com]</p>
                    <p><strong>Address:</strong> [Sector 42, Gurgaon, Haryana, India]</p>
                </div>
            </div>

            <div class="legal-footer-nav">
                <a href="/privacy">Privacy Policy</a>
                <a href="/disclaimer">Disclaimer</a>
                <a href="/home">Home</a>
            </div>
        </div>
    </div>

    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>
</body>
</html>
