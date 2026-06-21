<?php
session_start();
require_once "includes/database_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PG Life Disclaimer - Important information about our platform's role and limitations.">
    <title>Disclaimer | PG Life</title>
    <?php include "includes/head_links.php"; ?>
    <link href="css/legal.css" rel="stylesheet">
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="legal-page">
        <div class="legal-container">
            <div class="legal-header">
                <h1>Disclaimer</h1>
                <p class="legal-updated">Last updated: June 20, 2026</p>
            </div>

            <div class="legal-section">
                <h2>1. Platform as Intermediary</h2>
                <p>PG Life is an <strong>intermediary platform</strong> under the meaning of Section 79 of the Information Technology Act, 2000. We serve as a technology bridge that connects Paying Guest (PG) accommodation seekers with PG property owners.</p>
                <div class="legal-highlight">
                    PG Life does <strong>not</strong> own, operate, manage, inspect, or endorse any property listed on the Platform. All property listings are created and maintained solely by independent property owners.
                </div>
            </div>

            <div class="legal-section">
                <h2>2. No Guarantee of Property Accuracy</h2>
                <p>We do not warrant or guarantee:</p>
                <ul>
                    <li>The accuracy, completeness, or truthfulness of any property listing, including descriptions, photographs, amenity lists, or pricing.</li>
                    <li>The actual condition, safety, cleanliness, or habitability of any property.</li>
                    <li>That listed amenities are functional or available at the time of your visit.</li>
                    <li>That room availability shown on the Platform is current or accurate.</li>
                    <li>The identity, background, or credentials of any property owner or seeker.</li>
                </ul>
                <p><strong>We strongly recommend</strong> that seekers physically visit and personally inspect any property before making any financial commitment or signing any rental agreement.</p>
            </div>

            <div class="legal-section">
                <h2>3. No Financial Responsibility</h2>
                <p>PG Life does <strong>not</strong>:</p>
                <ul>
                    <li>Process, hold, escrow, or transfer any monetary funds between users.</li>
                    <li>Charge any platform fees, commissions, or service charges (as of the current version).</li>
                    <li>Guarantee the completion of any payment between a seeker and a property owner.</li>
                    <li>Provide refunds, mediate payment disputes, or enforce financial agreements between users.</li>
                </ul>
                <p>All rent payments, security deposits, and advance payments are made directly between seekers and owners at their own risk and discretion. The UPI payment proof feature on PG Life is merely a record-keeping tool and does not constitute payment processing.</p>
            </div>

            <div class="legal-section">
                <h2>4. No Liability for User Conduct</h2>
                <p>PG Life is not responsible for:</p>
                <ul>
                    <li>The conduct, actions, or omissions of any user (seeker or owner) on or off the Platform.</li>
                    <li>Disputes between seekers and property owners regarding rent, deposits, living conditions, house rules, or eviction.</li>
                    <li>Any loss, damage, injury, or harm suffered by a user in connection with a property discovered through the Platform.</li>
                    <li>Fraudulent, misleading, or deceptive listings or communications by any user.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>5. KYC Verification Disclaimer</h2>
                <p>PG Life offers an optional KYC (Know Your Customer) document upload feature for property owners. Please note:</p>
                <ul>
                    <li>KYC verification is a basic identity check and does <strong>not</strong> constitute a background check, credit check, or criminal record verification.</li>
                    <li>A "Verified" badge indicates that a document was reviewed, not that the owner or property is endorsed or guaranteed.</li>
                    <li>Seekers should not rely solely on KYC verification status when making rental decisions.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>6. Reviews and Ratings</h2>
                <p>Reviews and ratings on PG Life are submitted by users and reflect their individual opinions. PG Life does not:</p>
                <ul>
                    <li>Verify the authenticity of any review or rating.</li>
                    <li>Endorse or adopt any opinion expressed in user reviews.</li>
                    <li>Guarantee that reviews are from genuine tenants or are free from bias.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>7. External Content and Links</h2>
                <p>The Platform may display property addresses, city names, and other location-related information. This information is provided for reference only and may not reflect current or accurate geographic data. PG Life is not affiliated with any municipal authority, real estate regulatory body, or government housing agency.</p>
            </div>

            <div class="legal-section">
                <h2>8. Service Availability</h2>
                <p>PG Life is provided on an "as is" and "as available" basis. We do not guarantee:</p>
                <ul>
                    <li>Uninterrupted or error-free access to the Platform.</li>
                    <li>That the Platform will be available at all times or free from technical issues.</li>
                    <li>The security of data transmitted over the internet.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>9. Use at Your Own Risk</h2>
                <p>By using PG Life, you acknowledge and agree that:</p>
                <ul>
                    <li>You use the Platform and rely on its content entirely at your own risk.</li>
                    <li>You are responsible for conducting your own due diligence before entering into any rental arrangement.</li>
                    <li>PG Life shall not be held liable for any direct, indirect, incidental, consequential, or punitive damages arising from your use of the Platform or any arrangement made through it.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>10. Contact</h2>
                <p>If you have concerns about any listing, user, or experience on PG Life, please contact us:</p>
                <div class="legal-contact">
                    <p><strong>Email:</strong> [support@pglife.com]</p>
                    <p><strong>Address:</strong> [Sector 42, Gurgaon, Haryana, India]</p>
                </div>
            </div>

            <div class="legal-footer-nav">
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms of Service</a>
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
