<!-- Seeker View -->
<?php if(count($booked_properties) > 0) { ?>
<div class="booked-properties">
    <div class="page-container py-4">
        <h1 class="mb-4">My Booked Properties</h1>
        
        <?php
            foreach ($booked_properties as $property) {
                $property_images = glob("img/properties/" . $property['id'] . "/*");
                $rent_amount = $property['bargained_rent'] !== null ? (int)$property['bargained_rent'] : (int)$property['rent'];
                $is_rent_bargained = $property['bargained_rent'] !== null;
        ?>
        <div class="property-card property-id-<?= $property['id'] ?> row mb-4">
            <div class="image-container col-md-4">
                <img src="<?= htmlspecialchars($property_images[0]) ?>" alt="<?= htmlspecialchars($property['name']) ?>" loading="lazy" />
            </div>
            <div class="content-container col-md-8">
                <div class="row no-gutters justify-content-between">
                     <?php
                         $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety'])/3;
                         $total_rating = round($total_rating, 1);
                     ?>
                    <div class="star-container" title="<?= $total_rating ?>">
                        <?php
                            $rating = $total_rating;
                            for ($i = 0; $i < 5; $i++) {
                                if ($rating >= $i + 0.8) {
                        ?>
                                <i class="fas fa-star"></i>
                        <?php
                            } elseif ($rating >= $i + 0.3) {
                        ?>
                                <i class="fas fa-star-half-alt"></i>
                        <?php
                            } else {
                        ?>
                                <i class="far fa-star"></i>
                        <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="interested-container">
                        <span class="badge badge-success px-3 py-2 text-uppercase">Booked</span>
                    </div>
                </div>
                <div class="detail-container">
                    <div class="property-name"><?= htmlspecialchars($property['name']) ?></div>
                    <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
                    <div class="property-gender">
                        <?php
                            if($property['gender'] == "male") {
                        ?>
                        <img src="img/male.png" alt="Male Only" />
                        <?php
                            } elseif ($property['gender'] == "female") {
                        ?>
                        <img src="img/female.png" alt="Female Only" />
                        <?php
                            } else {
                        ?>
                        <img src="img/unisex.png" alt="Unisex" />
                        <?php
                            }
                        ?>
                    </div>
                </div>

                <div class="payment-status-block my-3 p-3 rounded d-flex align-items-center justify-content-between" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <div>
                        <small class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Rent Payment</small>
                        <?php if ($property['payment_status'] === null || (int)$property['payment_status'] === 2) { ?>
                            <span class="font-weight-bold text-danger" style="font-size: 14px;">
                                <i class="fas fa-exclamation-circle mr-1"></i>Unpaid
                                <?php if ((int)$property['payment_status'] === 2) { ?>
                                    (Last transaction rejected)
                                <?php } ?>
                            </span>
                        <?php } else if ((int)$property['payment_status'] === 0) { ?>
                            <span class="font-weight-bold text-warning" style="font-size: 14px;">
                                <i class="fas fa-clock mr-1"></i>Pending Verification (UTR: <?= htmlspecialchars($property['payment_utr']) ?>)
                            </span>
                        <?php } else if ((int)$property['payment_status'] === 1) { ?>
                            <span class="font-weight-bold text-success" style="font-size: 14px;">
                                <i class="fas fa-check-circle mr-1"></i>Paid & Verified (UTR: <?= htmlspecialchars($property['payment_utr']) ?>)
                            </span>
                        <?php } ?>
                    </div>
                    <div>
                        <?php if ($property['payment_status'] === null || (int)$property['payment_status'] === 2) { ?>
                            <button class="btn btn-sm btn-primary seeker-pay-rent-btn font-weight-bold px-3" 
                                     data-booking-id="<?= $property['booking_id'] ?>"
                                     data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                     data-rent="<?= $rent_amount ?>"
                                     data-owner-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                     data-owner-upi="<?= htmlspecialchars($property['owner_upi'] ?? 'pglife@upi') ?>"
                                     style="border-radius: 20px; font-size: 12px; height: 32px;">
                                <i class="fas fa-wallet mr-1"></i>Pay Rent
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <div class="row no-gutters align-items-center">
                    <div class="rent-container col-sm-4 mb-2 mb-sm-0">
                        <?php if ($is_rent_bargained) { ?>
                            <div class="rent" style="font-size: 18px; color: #28a745;"><i class="fas fa-handshake mr-1"></i>₹ <?= number_format($rent_amount) ?>/-</div>
                            <div class="rent-unit"><del class="text-muted">₹ <?= number_format($property['rent']) ?></del> Negotiated</div>
                        <?php } else { ?>
                            <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                            <div class="rent-unit">per month</div>
                        <?php } ?>
                     </div>
                     <div class="button-container col-sm-8 d-flex justify-content-end flex-wrap gap-2">
                         <a href="/pg/<?= $property['id'] ?>" class="btn btn-primary mr-2 mb-2" style="width: auto; float: none; height: 36px; line-height: 24px;">View</a>
                         <button class="btn btn-outline-primary seeker-chat-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                 data-contact-id="<?= $property['owner_id'] ?>"
                                 data-contact-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                 data-contact-gender="<?= htmlspecialchars($property['owner_gender']) ?>"
                                 data-contact-profile-pic="<?= htmlspecialchars($property['owner_profile_pic'] ?? '') ?>"
                                 data-property-id="<?= $property['id'] ?>"
                                 data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                 style="width: auto; float: none; font-size: 13px; height: 36px;">
                             <i class="fas fa-comments mr-1"></i>Chat with Owner
                         </button>
                         <button class="btn btn-warning seeker-report-issue-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                 data-property-id="<?= $property['id'] ?>" 
                                 data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                 style="width: auto; float: none; font-size: 13px; height: 36px;"><i class="fas fa-exclamation-triangle mr-1"></i>Report Issue</button>
                         <button class="btn btn-info seeker-view-tickets-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                 data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                 data-tickets='<?= json_encode($seeker_tickets_by_prop[$property['id']] ?? []) ?>'
                                 style="width: auto; float: none; font-size: 13px; height: 36px;"><i class="fas fa-ticket-alt mr-1"></i>My Tickets (<?= count($seeker_tickets_by_prop[$property['id']] ?? []) ?>)</button>
                         <button class="btn btn-danger cancel-booking-btn mb-2 font-weight-bold d-flex align-items-center" property_id="<?= $property['id'] ?>" style="width: auto; float: none; height: 36px;">Cancel</button>
                     </div>
                </div>
            </div>
        </div>
        <?php
            }
        ?>
    </div>
</div>
<?php } else { ?>
<div class="border-top">
    <div class="page-container py-4">
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-home"></i></div>
            <div class="empty-state-title">No Bookings Yet</div>
            <div class="empty-state-text">You haven't booked any PG yet. Start exploring properties in your preferred city and book the one that suits you best.</div>
            <a href="/home" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Explore PGs</a>
        </div>
    </div>
</div>
<?php } ?>

<?php if(count($interested_properties) > 0) { ?>
<div class="interested-properties border-top">
    <div class="page-container py-4">
        <h1 class="mb-4">My Interested Properties</h1>
        <?php
            foreach ($interested_properties as $property) {
                $property_images = glob("img/properties/" . $property['id'] . "/*");
                $rent_amount = $property['bargained_rent'] !== null ? (int)$property['bargained_rent'] : (int)$property['rent'];
                $is_rent_bargained = $property['bargained_rent'] !== null;
        ?>
        <div class="property-card property-id-<?= $property['id'] ?> row">
            <div class="image-container col-md-4">
                <img src="<?= htmlspecialchars($property_images[0]) ?>" alt="<?= htmlspecialchars($property['name']) ?>" loading="lazy" />
            </div>
            <div class="content-container col-md-8">
                <div class="row no-gutters justify-content-between">
                     <?php
                         $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety'])/3;
                         $total_rating = round($total_rating, 1);
                     ?>
                    <div class="star-container" title="<?= $total_rating ?>">
                        <?php
                            $rating = $total_rating;
                            for ($i = 0; $i < 5; $i++) {
                                if ($rating >= $i + 0.8) {
                        ?>
                                <i class="fas fa-star"></i>
                        <?php
                            } elseif ($rating >= $i + 0.3) {
                        ?>
                                <i class="fas fa-star-half-alt"></i>
                        <?php
                            } else {
                        ?>
                                <i class="far fa-star"></i>
                        <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="interested-container">
                        <i class="is-interested-image fas fa-heart" property_id="<?= $property['id'] ?>"></i>
                    </div>
                </div>
                <div class="detail-container">
                    <div class="property-name"><?= htmlspecialchars($property['name']) ?></div>
                    <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
                    <div class="property-gender">
                        <?php
                            if($property['gender'] == "male") {
                        ?>
                        <img src="img/male.png" alt="Male Only" />
                        <?php
                            } elseif ($property['gender'] == "female") {
                        ?>
                        <img src="img/female.png" alt="Female Only" />
                        <?php
                            } else {
                        ?>
                        <img src="img/unisex.png" alt="Unisex" />
                        <?php
                            }
                        ?>
                    </div>
                </div>
                <div class="row no-gutters align-items-center">
                    <div class="rent-container col-sm-6 mb-2 mb-sm-0">
                        <?php if ($is_rent_bargained) { ?>
                            <div class="rent" style="font-size: 18px; color: #28a745;"><i class="fas fa-handshake mr-1"></i>₹ <?= number_format($rent_amount) ?>/-</div>
                            <div class="rent-unit"><del class="text-muted">₹ <?= number_format($property['rent']) ?></del> Negotiated</div>
                        <?php } else { ?>
                            <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                            <div class="rent-unit">per month</div>
                        <?php } ?>
                    </div>
                    <div class="button-container col-sm-6 d-flex justify-content-end gap-2 flex-wrap">
                        <a href="/pg/<?= $property['id'] ?>" class="btn btn-primary mr-2" style="width: auto; float: none; height: 36px; line-height: 24px;">View</a>
                        <button class="btn btn-outline-primary seeker-chat-btn font-weight-bold d-flex align-items-center" 
                                data-contact-id="<?= $property['owner_id'] ?>"
                                data-contact-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                data-contact-gender="<?= htmlspecialchars($property['owner_gender']) ?>"
                                data-contact-profile-pic="<?= htmlspecialchars($property['owner_profile_pic'] ?? '') ?>"
                                data-property-id="<?= $property['id'] ?>"
                                data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                style="width: auto; float: none; font-size: 13px; height: 36px;">
                            <i class="fas fa-comments mr-1"></i>Chat with Owner
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
            }
        ?>
    </div>
</div>
<?php } else { ?>
<div class="border-top">
    <div class="page-container py-4">
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-heart"></i></div>
            <div class="empty-state-title">No Saved Properties</div>
            <div class="empty-state-text">Tap the heart icon on any property listing to save it here for quick access later.</div>
            <a href="/home" class="btn btn-outline-primary"><i class="fas fa-compass mr-1"></i>Browse Properties</a>
        </div>
    </div>
</div>
<?php } ?>
