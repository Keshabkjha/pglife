<!-- Owner Control Panel -->
<div class="owner-dashboard page-container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="mb-0 font-weight-bold text-dark">Owner Dashboard</h1>
        <button class="btn btn-success font-weight-bold px-4 py-2 shadow-sm rounded-lg" data-toggle="modal" data-target="#add-property-modal" style="border-radius: 30px; font-size: 14px;">
            <i class="fas fa-plus-circle mr-2"></i>List New PG
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="row mb-5">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="owner-stat-card properties-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border: none;">
                <div class="stat-content">
                    <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Properties Listed</span>
                    <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_listings ?></span>
                </div>
                <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                    <i class="fas fa-home"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="owner-stat-card bookings-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none;">
                <div class="stat-content">
                    <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Total Bookings</span>
                    <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_bookings_count ?></span>
                </div>
                <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="owner-stat-card views-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #7f00ff 0%, #e100ff 100%); border: none;">
                <div class="stat-content">
                    <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Total Views</span>
                    <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_views_count ?></span>
                </div>
                <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="owner-stat-card rating-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); border: none;">
                <div class="stat-content">
                    <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Average Rating</span>
                    <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;">
                        <?= $avg_owner_rating > 0 ? $avg_owner_rating . ' <span style="font-size: 18px;"><i class="fas fa-star text-warning"></i></span>' : 'N/A' ?>
                    </span>
                </div>
                <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                    <i class="fas fa-star-half-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- My Properties & Performance -->
    <h2 class="mb-4 font-weight-bold text-dark" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">My Properties & Performance</h2>
    <?php if (count($owner_properties) === 0) { ?>
        <div class="empty-state border rounded bg-white shadow-sm mt-3">
            <div class="empty-state-icon"><i class="fas fa-hotel"></i></div>
            <div class="empty-state-title">No Properties Listed</div>
            <div class="empty-state-text">You haven't listed any PG properties yet. Add your first property to start receiving bookings.</div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#add-property-modal">List Your First PG</button>
        </div>
    <?php } else { ?>
        <div class="owner-properties-grid mt-3">
            <?php 
            foreach ($owner_properties as $property) { 
                $property_images = glob("img/properties/" . $property['id'] . "/*");
                $image_path = !empty($property_images) ? $property_images[0] : 'img/logo.png';
                
                $prop_bookings = $bookings_by_prop[$property['id']] ?? [];
                $prop_bookings_count = count($prop_bookings);
                
                $prop_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                $prop_rating = round($prop_rating, 1);

                $sql_pa = "SELECT amenity_id FROM properties_amenities WHERE property_id = ?";
                $stmt_pa = mysqli_prepare($conn, $sql_pa);
                $pa_ids = [];
                if ($stmt_pa) {
                    mysqli_stmt_bind_param($stmt_pa, "i", $property['id']);
                    mysqli_stmt_execute($stmt_pa);
                    $res_pa = mysqli_stmt_get_result($stmt_pa);
                    if ($res_pa) {
                        while($row_pa = mysqli_fetch_assoc($res_pa)) {
                            $pa_ids[] = (int)$row_pa['amenity_id'];
                        }
                    }
                    mysqli_stmt_close($stmt_pa);
                }

                $prop_room_types = [];
                $sql_rt = "SELECT id, room_type, label, price_per_month, total_beds, occupied_beds, amenities, is_active FROM room_types WHERE property_id = ? ORDER BY price_per_month ASC";
                $stmt_rt = mysqli_prepare($conn, $sql_rt);
                if ($stmt_rt) {
                    mysqli_stmt_bind_param($stmt_rt, "i", $property['id']);
                    mysqli_stmt_execute($stmt_rt);
                    $res_rt = mysqli_stmt_get_result($stmt_rt);
                    if ($res_rt) {
                        while ($row_rt = mysqli_fetch_assoc($res_rt)) {
                            $row_rt['available_beds'] = max(0, (int)$row_rt['total_beds'] - (int)$row_rt['occupied_beds']);
                            $prop_room_types[] = $row_rt;
                        }
                    }
                    mysqli_stmt_close($stmt_rt);
                }
            ?>
                <div class="property-card row mb-4 mx-0 shadow-sm border rounded bg-white overflow-hidden" style="border-radius: 10px; border: 1px solid #e3e3e3;">
                    <div class="image-container col-md-4 p-0">
                        <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($property['name']) ?>" class="img-fluid w-100 h-100" style="min-height: 200px; object-fit: cover; max-width: 100%;" loading="lazy" />
                    </div>
                    <div class="content-container col-md-8 p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                                <h3 class="property-name text-dark font-weight-bold mb-0" style="font-size: 20px;"><?= htmlspecialchars($property['name']) ?></h3>
                                <div class="gender-badge px-3 py-1 rounded text-uppercase font-weight-bold" style="font-size: 11px; background-color: #f7f7f7;">
                                    <?php if ($property['gender'] === 'male') { ?>
                                        <span class="text-primary"><i class="fas fa-male mr-1"></i>Boys Only</span>
                                    <?php } else if ($property['gender'] === 'female') { ?>
                                        <span class="text-danger"><i class="fas fa-female mr-1"></i>Girls Only</span>
                                    <?php } else { ?>
                                        <span class="text-success"><i class="fas fa-users mr-1"></i>Unisex</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <p class="property-address text-muted mb-3" style="font-size: 13px;"><i class="fas fa-map-marker-alt mr-2 text-danger"></i><?= htmlspecialchars($property['address']) ?></p>
                            
                            <div class="d-flex flex-wrap align-items-center mb-4">
                                <div class="metric-item mr-3 py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                    <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Views</span>
                                    <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;"><i class="far fa-eye mr-1 text-info"></i><?= $property['views'] ?></span>
                                </div>
                                <div class="metric-item mr-3 py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                    <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Bookings</span>
                                    <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;"><i class="far fa-calendar-check mr-1 text-success"></i><?= $prop_bookings_count ?></span>
                                </div>
                                <div class="metric-item py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                    <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Avg Rating</span>
                                    <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;">
                                        <i class="fas fa-star mr-1 text-warning"></i><?= $prop_rating > 0 ? $prop_rating : 'N/A' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row no-gutters align-items-center border-top pt-3">
                            <div class="rent-container col-sm-4 mb-2 mb-sm-0">
                                <div class="rent text-primary font-weight-bold mb-0" style="font-size: 20px;">₹ <?= number_format($property['rent']) ?>/-</div>
                                <div class="rent-unit text-muted" style="font-size: 11px;">per month</div>
                            </div>
                            <div class="button-container col-sm-8 d-flex justify-content-end flex-wrap gap-2">
                                <a href="/pg/<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm px-3 mr-2 font-weight-bold d-flex align-items-center" style="border-radius: 4px; height: 36px;">
                                    <i class="far fa-eye mr-1"></i>View
                                </a>
                                <button class="btn btn-outline-info btn-sm px-3 mr-2 font-weight-bold view-bookings-btn d-flex align-items-center" 
                                        data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                        data-bookings='<?= json_encode($bookings_by_prop[$property['id']] ?? []) ?>'
                                        style="border-radius: 4px; height: 36px;">
                                    <i class="fas fa-users mr-1"></i>Bookings (<?= $prop_bookings_count ?>)
                                </button>
                                <button class="btn btn-outline-warning btn-sm px-3 mr-2 font-weight-bold view-reviews-btn d-flex align-items-center" 
                                        data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                        data-reviews='<?= json_encode($reviews_by_prop[$property['id']] ?? []) ?>'
                                        style="border-radius: 4px; height: 36px;">
                                    <i class="far fa-comments mr-1"></i>Reviews (<?= count($reviews_by_prop[$property['id']] ?? []) ?>)
                                </button>
                                <button class="btn btn-outline-danger btn-sm px-3 mr-2 font-weight-bold view-tickets-btn d-flex align-items-center" 
                                        data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                        data-tickets='<?= json_encode($tickets_by_prop[$property['id']] ?? []) ?>'
                                        style="border-radius: 4px; height: 36px;">
                                    <i class="fas fa-tools mr-1"></i>Tickets (<?= count($tickets_by_prop[$property['id']] ?? []) ?>)
                                </button>
                                <?php $basenames = array_map('basename', $property_images); ?>
                                <button class="btn btn-outline-success btn-sm px-3 mr-2 font-weight-bold edit-property-btn d-flex align-items-center" 
                                        data-property='<?= json_encode($property) ?>'
                                        data-amenities-ids='<?= json_encode($pa_ids) ?>'
                                        data-images='<?= json_encode($basenames) ?>'
                                        data-primary-image="<?= htmlspecialchars($property['primary_image'] ?? '') ?>"
                                        style="border-radius: 4px; height: 36px;">
                                    <i class="far fa-edit mr-1"></i>Edit
                                </button>
                                <button class="btn btn-outline-danger btn-sm px-3 font-weight-bold delete-property-btn d-flex align-items-center" 
                                        property_id="<?= $property['id'] ?>"
                                        property_name="<?= htmlspecialchars($property['name']) ?>"
                                        style="border-radius: 4px; height: 36px;">
                                    <i class="far fa-trash-alt mr-1"></i>Delete
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($prop_room_types)) { ?>
                        <div class="border-top pt-3 mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted font-weight-bold" style="font-size: 12px;"><i class="fas fa-door-open mr-1 text-primary"></i>ROOM AVAILABILITY</small>
                                <button class="btn btn-link btn-sm p-0 text-primary manage-rooms-btn font-weight-bold" 
                                        data-property-id="<?= $property['id'] ?>"
                                        data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                        data-rooms='<?= json_encode($prop_room_types) ?>'
                                        style="font-size: 12px;">
                                    <i class="far fa-edit mr-1"></i>Manage Rooms
                                </button>
                            </div>
                            <div class="d-flex flex-wrap">
                            <?php foreach ($prop_room_types as $rt) {
                                $avail = $rt['available_beds'];
                                $sc = $avail === 0 ? 'danger' : ($avail <= 2 ? 'warning' : 'success');
                                $sl = $avail === 0 ? 'Full' : ($avail . ' free');
                            ?>
                                <span class="badge badge-<?= $sc ?> mr-1 mb-1 px-2 py-1" style="font-size: 11px; border-radius: 6px;">
                                    <?= htmlspecialchars($rt['label']) ?> &mdash; <?= $sl ?>
                                </span>
                            <?php } ?>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div class="border-top pt-2 mt-2 text-right">
                            <button class="btn btn-outline-secondary btn-sm manage-rooms-btn" 
                                    data-property-id="<?= $property['id'] ?>"
                                    data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                    data-rooms='[]'
                                    style="font-size: 12px; border-radius: 6px;">
                                <i class="fas fa-plus mr-1"></i>Add Room Types
                            </button>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- Active Chats & Bargains -->
    <h2 class="mb-4 font-weight-bold text-dark mt-5" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Active Chats & Rent Bargains</h2>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border rounded-lg" style="border-radius: 12px; border: 1px solid #e3e3e3;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-dark" style="font-size: 13px;">
                            <thead>
                                <tr class="bg-light">
                                    <th>Property Context</th>
                                    <th>Seeker Name</th>
                                    <th>Last Message Preview</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="owner-chats-table-body">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading conversations...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="no-owner-chats-message" class="text-center py-5 d-none text-muted">
                        <i class="far fa-comments mb-3" style="font-size: 48px; opacity: 0.3;"></i>
                        <p class="mb-0 font-weight-bold">No active conversations found.</p>
                        <small>Seekers will appear here when they send you inquiries or rent offers.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rent Collection Ledger -->
    <h2 class="mb-4 font-weight-bold text-dark mt-5" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Rent Collection Ledger</h2>
    <?php if (count($owner_payments) === 0) { ?>
        <div class="empty-state border rounded bg-white shadow-sm mt-3">
            <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
            <div class="empty-state-title">No Payments Received</div>
            <div class="empty-state-text">No rent payment submissions yet. Payments will appear here when seekers submit proof of payment.</div>
        </div>
    <?php } else { ?>
        <div class="table-responsive bg-white border rounded shadow-sm p-3 mt-3" style="border-radius: 12px;">
            <table class="table table-hover table-striped mb-0 text-dark" style="font-size: 13px;">
                <thead>
                    <tr class="thead-dark text-white">
                        <th>Property</th>
                        <th>Seeker Details</th>
                        <th>Rent Amount</th>
                        <th>UTR / Transaction ID</th>
                        <th>Payment Date</th>
                        <th>Receipt</th>
                        <th>Status & Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($owner_payments as $payment) { ?>
                        <tr id="owner-payment-row-<?= $payment['id'] ?>">
                            <td class="font-weight-bold"><?= htmlspecialchars($payment['property_name']) ?></td>
                            <td>
                                <div class="font-weight-bold"><?= htmlspecialchars($payment['seeker_name']) ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($payment['seeker_email']) ?> | <?= htmlspecialchars($payment['seeker_phone']) ?></div>
                            </td>
                            <td class="font-weight-bold text-primary">₹ <?= number_format($payment['amount']) ?></td>
                            <td><code class="px-2 py-1 bg-light text-danger rounded font-weight-bold"><?= htmlspecialchars($payment['utr_number']) ?></code></td>
                            <td><?= date('d M Y, h:i A', strtotime($payment['payment_date'])) ?></td>
                            <td>
                                <?php if (!empty($payment['screenshot'])) { ?>
                                    <a href="<?= htmlspecialchars($payment['screenshot']) ?>" target="_blank" class="btn btn-outline-info btn-xs py-1 px-2 font-weight-bold" style="font-size: 11px;">
                                        <i class="fas fa-file-image mr-1"></i>View Image
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted font-italic">No Attachment</span>
                                <?php } ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ((int)$payment['status'] === 0) { ?>
                                        <span class="badge badge-warning px-2 py-1 text-uppercase mr-2 payment-status-badge text-white" style="font-size: 11px; background-color: #f0ad4e;"><i class="fas fa-clock mr-1"></i>Pending</span>
                                        <button class="btn btn-xs btn-success font-weight-bold mr-1 owner-verify-payment-btn" data-payment-id="<?= $payment['id'] ?>" data-action="approve" style="font-size: 11px; padding: 4px 8px;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-xs btn-danger font-weight-bold owner-verify-payment-btn" data-payment-id="<?= $payment['id'] ?>" data-action="reject" style="font-size: 11px; padding: 4px 8px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php } else if ((int)$payment['status'] === 1) { ?>
                                        <span class="badge badge-success px-2 py-1 text-uppercase payment-status-badge text-white" style="font-size: 11px; background-color: #28a745;"><i class="fas fa-check-circle mr-1"></i>Verified</span>
                                    <?php } else if ((int)$payment['status'] === 2) { ?>
                                        <span class="badge badge-danger px-2 py-1 text-uppercase payment-status-badge text-white" style="font-size: 11px; background-color: #dc3545;"><i class="fas fa-times-circle mr-1"></i>Rejected</span>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>
