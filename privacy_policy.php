<?php
session_start();
require_once "includes/database_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PG Life Privacy Policy - How we collect, use, and protect your personal information.">
    <title>Privacy Policy | PG Life</title>
    <?php include "includes/head_links.php"; ?>
    <link href="css/legal.css" rel="stylesheet">
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="legal-page">
        <div class="legal-container">
            <div class="legal-header">
                <h1>Privacy Policy</h1>
                <p class="legal-updated">Last updated: June 20, 2026</p>
            </div>

            <div class="legal-section">
                <h2>1. Introduction</h2>
                <p>Welcome to PG Life ("we", "us", "our"). PG Life is an online platform that connects Paying Guest (PG) accommodation seekers with PG property owners across India. This Privacy Policy explains how we collect, use, disclose, and safeguard your personal information when you use our website and services.</p>
                <p>By using PG Life, you consent to the data practices described in this policy. If you do not agree with the terms of this Privacy Policy, please do not use our platform.</p>
            </div>

            <div class="legal-section">
                <h2>2. Information We Collect</h2>

                <h3>2.1 Information You Provide</h3>
                <table class="legal-table">
                    <thead>
                        <tr>
                            <th>Data Category</th>
                            <th>Specific Data</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Account Information</td>
                            <td>Full name, email address, phone number, gender</td>
                            <td>Account creation, communication, property matching</td>
                        </tr>
                        <tr>
                            <td>Authentication Data</td>
                            <td>Password (stored in hashed form)</td>
                            <td>Account security</td>
                        </tr>
                        <tr>
                            <td>Profile Information</td>
                            <td>Institution/organization, profile photo</td>
                            <td>Profile display, trust verification</td>
                        </tr>
                        <tr>
                            <td>Identity Verification (KYC)</td>
                            <td>Government-issued ID documents</td>
                            <td>Owner identity verification</td>
                        </tr>
                        <tr>
                            <td>Payment Information</td>
                            <td>UPI ID (owners only), UTR/transaction numbers, payment screenshots</td>
                            <td>Rent payment verification between seekers and owners</td>
                        </tr>
                        <tr>
                            <td>Property Data</td>
                            <td>Property details, addresses, photos, room types (owners)</td>
                            <td>Listing display and booking facilitation</td>
                        </tr>
                        <tr>
                            <td>Communications</td>
                            <td>Messages exchanged with other users, maintenance tickets</td>
                            <td>Facilitating seeker-owner communication</td>
                        </tr>
                        <tr>
                            <td>User Reviews</td>
                            <td>Ratings and review text</td>
                            <td>Community trust and property quality signals</td>
                        </tr>
                    </tbody>
                </table>

                <h3>2.2 Information Collected Automatically</h3>
                <p>We use <strong>session cookies</strong> (essential for login functionality) to maintain your authenticated session. We do <strong>not</strong> use tracking cookies, analytics cookies, advertising cookies, or any third-party tracking scripts.</p>

                <h3>2.3 Information We Do NOT Collect</h3>
                <ul>
                    <li>Credit/debit card numbers or banking details</li>
                    <li>GPS or precise location data (beyond the city you search for)</li>
                    <li>Biometric data</li>
                    <li>Health or medical information</li>
                    <li>Data from social media accounts</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>3. How We Use Your Information</h2>
                <p>We use the collected information for the following purposes:</p>
                <ul>
                    <li><strong>Account Management:</strong> Creating and managing your PG Life account, including email verification via OTP.</li>
                    <li><strong>Platform Operations:</strong> Enabling property searches, bookings, cancellations, and maintenance ticket submissions.</li>
                    <li><strong>Communication:</strong> Facilitating messages between seekers and property owners, including rent negotiation offers.</li>
                    <li><strong>Verification:</strong> Processing KYC documents to verify property owner identities.</li>
                    <li><strong>Payment Facilitation:</strong> Recording UPI payment proofs submitted by seekers and verified by owners. PG Life does not process, hold, or transfer any monetary funds.</li>
                    <li><strong>Security:</strong> Protecting against unauthorized access using CSRF tokens, session management, and password hashing.</li>
                    <li><strong>Email Notifications:</strong> Sending OTP codes for signup verification and password recovery via SMTP email.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>4. Data Sharing</h2>
                <p>We do <strong>not</strong> sell, trade, or rent your personal information to third parties. Information is shared only in the following limited circumstances:</p>
                <ul>
                    <li><strong>Between Users:</strong> Your name, profile photo, and relevant booking/payment details are visible to the property owner you are transacting with, and vice versa. Messages are visible only to the sender and recipient.</li>
                    <li><strong>Service Providers:</strong> We use Gmail SMTP to send OTP verification emails. Only your email address is shared with the email delivery service for this purpose.</li>
                    <li><strong>Legal Compliance:</strong> We may disclose information if required by law, regulation, or legal process.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>5. Data Storage and Security</h2>
                <p>Your data is stored in a MySQL/MariaDB database. We implement the following security measures:</p>
                <ul>
                    <li><strong>Password Protection:</strong> All passwords are hashed using bcrypt before storage. We never store or have access to your plaintext password.</li>
                    <li><strong>Session Security:</strong> Sessions use HttpOnly cookies, are cookie-only (no URL-based session IDs), and session IDs are regenerated upon login to prevent fixation attacks.</li>
                    <li><strong>CSRF Protection:</strong> All form submissions and API requests are validated with unique CSRF tokens.</li>
                    <li><strong>Input Validation:</strong> All user inputs are validated and sanitized. Database queries use prepared statements to prevent SQL injection.</li>
                    <li><strong>File Upload Security:</strong> Uploaded files (KYC documents, profile photos, payment receipts) are validated for MIME type and size, and stored with randomized filenames.</li>
                    <li><strong>Security Headers:</strong> HTTP response headers include X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, and Referrer-Policy.</li>
                </ul>
                <div class="legal-highlight">
                    <strong>Important:</strong> While we implement industry-standard security measures, no method of electronic transmission or storage is 100% secure. We cannot guarantee absolute security of your data.
                </div>
            </div>

            <div class="legal-section">
                <h2>6. Your Rights (Under India's DPDP Act, 2023)</h2>
                <p>As a data principal under India's Digital Personal Data Protection Act, 2023, you have the following rights:</p>
                <ul>
                    <li><strong>Right to Access:</strong> You may request a summary of your personal data that we process.</li>
                    <li><strong>Right to Correction:</strong> You can update your name, phone, institution, UPI ID, and profile photo through your dashboard at any time.</li>
                    <li><strong>Right to Erasure:</strong> You may request deletion of your account and associated personal data by contacting us (see Section 9).</li>
                    <li><strong>Right to Grievance Redressal:</strong> You may raise concerns about data processing with our Grievance Officer (see Section 9).</li>
                    <li><strong>Right to Nominate:</strong> You may nominate a person to exercise these rights on your behalf in case of incapacity.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>7. Data Retention</h2>
                <p>We retain your personal data for as long as your account is active or as needed to provide services. Specifically:</p>
                <ul>
                    <li><strong>Account Data:</strong> Retained until you request account deletion.</li>
                    <li><strong>KYC Documents:</strong> Retained until your account is deleted or you upload a replacement document (old documents are automatically deleted).</li>
                    <li><strong>Payment Records:</strong> Retained as long as the associated booking exists.</li>
                    <li><strong>Messages:</strong> Retained as long as the associated property listing and user accounts exist.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2>8. Children's Privacy</h2>
                <p>PG Life is intended for adults seeking or listing PG accommodation. We do not knowingly collect personal information from individuals under the age of 18. If we learn that we have collected data from a minor, we will take steps to delete it.</p>
            </div>

            <div class="legal-section">
                <h2>9. Grievance Officer</h2>
                <p>In accordance with the Information Technology Act, 2000 and the rules made thereunder, and the Digital Personal Data Protection Act, 2023, the name and contact details of the Grievance Officer are provided below:</p>
                <div class="legal-contact">
                    <p><strong>Name:</strong> [Grievance Officer Name]</p>
                    <p><strong>Designation:</strong> Grievance Officer</p>
                    <p><strong>Email:</strong> [grievance@pglife.com]</p>
                    <p><strong>Address:</strong> [Sector 42, Gurgaon, Haryana, India]</p>
                    <p style="margin-top: 12px; color: #94A3B8; font-size: 13px;">Grievances will be addressed within 30 days from the date of receipt.</p>
                </div>
            </div>

            <div class="legal-section">
                <h2>10. Third-Party Services</h2>
                <p>Our platform uses the following third-party services:</p>
                <ul>
                    <li><strong>Gmail SMTP:</strong> Used exclusively for sending OTP verification and password recovery emails.</li>
                    <li><strong>Google Fonts:</strong> Font assets loaded from Google's CDN for visual styling.</li>
                    <li><strong>FontAwesome CDN:</strong> Icon assets loaded for user interface elements.</li>
                </ul>
                <p>These services have their own privacy policies. We do not share your personal data with them beyond what is strictly necessary for the service (e.g., your email address for OTP delivery).</p>
            </div>

            <div class="legal-section">
                <h2>11. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. The "Last updated" date at the top reflects the most recent revision. We encourage you to review this policy periodically. Continued use of PG Life after changes constitutes acceptance of the updated policy.</p>
            </div>

            <div class="legal-footer-nav">
                <a href="/terms">Terms of Service</a>
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
