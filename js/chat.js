$(document).ready(function () {
    let chatInterval = null;
    let conversationsInterval = null;
    let activeContactId = null;
    let activePropertyId = null;

    // Seeker templates
    const seekerReplies = [
        "Is this still available?",
        "Can I visit tomorrow?",
        "What is the security deposit?",
        "Is food included?"
    ];

    // Owner templates
    const ownerReplies = [
        "Yes, it is available.",
        "Sure, you can visit.",
        "The deposit is 1 month rent.",
        "Food is included in the rent."
    ];

    // Event listener for "Chat with Owner" button (Seeker side)
    $(document).on('click', '.seeker-chat-btn', function (e) {
        e.preventDefault();
        const contactId = $(this).data('contact-id');
        const contactName = $(this).data('contact-name') || "Owner";
        const contactGender = $(this).data('contact-gender') || "";
        const contactPic = $(this).data('contact-profile-pic') || "";
        const propertyId = $(this).data('property-id');
        const propertyName = $(this).data('property-name') || "Property Details";

        openChatBox(contactId, contactName, contactGender, contactPic, propertyId, propertyName);
    });

    // Close Button handler
    $('#chat-widget-close').click(function () {
        closeChatBox();
    });

    // Minimize Button handler
    $('#chat-widget-minimize').click(function () {
        $('#chat-box-widget').toggleClass('minimized');
        const isMinimized = $('#chat-box-widget').hasClass('minimized');
        $(this).html(isMinimized ? '<i class="fas fa-chevron-up"></i>' : '<i class="fas fa-minus"></i>');
    });

    // Toggle Bargain Input handler
    $('#chat-widget-toggle-bargain').click(function () {
        $('#chat-widget-offer-container').slideToggle(200);
        $('#chat-widget-offer-input').focus();
    });

    // Submit Offer button handler
    $('#chat-widget-submit-offer').click(function () {
        const amount = parseInt($('#chat-widget-offer-input').val());
        if (isNaN(amount) || amount <= 0) {
            alert("Please enter a valid bargaining rent amount (positive number).");
            return;
        }

        sendChatOffer(amount);
    });

    // Quick reply chips click handler
    $(document).on('click', '.quick-reply-chip', function () {
        const text = $(this).text();
        sendChatMessage(text);
    });

    // Chat Message Form submission
    $('#chat-widget-form').submit(function (e) {
        e.preventDefault();
        const msgText = $('#chat-widget-input').val().trim();
        if (!msgText) return;

        sendChatMessage(msgText);
    });

    // Auto-expand/shrink textarea input
    $('#chat-widget-input').on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Accept / Decline offer button handler
    $(document).on('click', '.offer-btn', function () {
        const messageId = $(this).data('message-id');
        const action = $(this).data('action'); // 'accept' or 'decline'
        respondToOffer(messageId, action);
    });

    // Open Chat Box Function
    function openChatBox(contactId, contactName, contactGender, contactPic, propertyId, propertyName) {
        activeContactId = contactId;
        activePropertyId = propertyId;

        // Set receiver/property hidden fields
        $('#chat-widget-receiver-id').val(contactId);
        $('#chat-widget-property-id').val(propertyId);

        // Update header details
        $('#chat-widget-contact-name').text(contactName);
        $('#chat-widget-property-context').text(propertyName).attr('title', propertyName);

        // Resolve profile pic avatar
        let avatarSrc = 'img/man.png';
        if (contactPic) {
            avatarSrc = contactPic;
        } else if (contactGender === 'female') {
            avatarSrc = 'img/Female_icon.png';
        }
        $('#chat-widget-avatar').attr('src', avatarSrc);

        // Clear input and messages body
        $('#chat-widget-input').val('').css('height', 'auto');
        $('#chat-widget-offer-input').val('');
        $('#chat-widget-offer-container').hide();
        $('#chat-widget-messages').html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Loading history...</div>');

        // Show chat box widget & ensure not minimized
        $('#chat-box-widget').removeClass('minimized').addClass('chat-open');
        $('#chat-widget-minimize').html('<i class="fas fa-minus"></i>');

        // Load chips
        renderQuickReplyChips();

        // Fetch messages and start polling
        fetchChatMessages(true);
        if (chatInterval) clearInterval(chatInterval);
        chatInterval = setInterval(function () {
            fetchChatMessages(false);
        }, 3000);
    }

    // Close Chat Box Function
    function closeChatBox() {
        $('#chat-box-widget').removeClass('chat-open');
        if (chatInterval) {
            clearInterval(chatInterval);
            chatInterval = null;
        }
        activeContactId = null;
        activePropertyId = null;
    }

    // Render chips depending on Seeker or Owner role
    function renderQuickReplyChips() {
        const container = $('#chat-widget-quick-replies');
        container.empty();
        const chips = (window.userRole === 'owner') ? ownerReplies : seekerReplies;
        chips.forEach(function (text) {
            container.append(`<span class="quick-reply-chip">${text}</span>`);
        });
    }

    // Send Normal Text Message
    function sendChatMessage(text) {
        if (!activeContactId || !activePropertyId) return;

        $.ajax({
            url: 'api/send_message.php',
            type: 'POST',
            dataType: 'json',
            data: {
                receiver_id: activeContactId,
                property_id: activePropertyId,
                message: text,
                csrf_token: window.csrfToken
            },
            success: function (res) {
                if (res.success) {
                    $('#chat-widget-input').val('').css('height', 'auto');
                    appendMessageBubble(res.data, true);
                    scrollChatToBottom();
                    fetchChatMessages(false);
                } else {
                    alert(res.message);
                }
            },
            error: function () {
                console.error("Error sending message.");
            }
        });
    }

    // Send Bargaining Offer
    function sendChatOffer(amount) {
        if (!activeContactId || !activePropertyId) return;

        $.ajax({
            url: 'api/send_message.php',
            type: 'POST',
            dataType: 'json',
            data: {
                receiver_id: activeContactId,
                property_id: activePropertyId,
                offer_amount: amount,
                csrf_token: window.csrfToken
            },
            success: function (res) {
                if (res.success) {
                    $('#chat-widget-offer-input').val('');
                    $('#chat-widget-offer-container').slideUp(200);
                    appendMessageBubble(res.data, true);
                    scrollChatToBottom();
                    fetchChatMessages(false);
                } else {
                    alert(res.message);
                }
            },
            error: function () {
                console.error("Error sending rent offer.");
            }
        });
    }

    // Respond to Bargaining Offer (Accept/Decline)
    function respondToOffer(messageId, action) {
        $.ajax({
            url: 'api/respond_offer.php',
            type: 'POST',
            dataType: 'json',
            data: {
                message_id: messageId,
                action: action,
                csrf_token: window.csrfToken
            },
            success: function (res) {
                if (res.success) {
                    fetchChatMessages(false);
                    // If on dashboard, reload to apply new rent rates dynamically
                    if (window.location.pathname.indexOf('dashboard') !== -1) {
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    alert(res.message);
                }
            },
            error: function () {
                console.error("Error responding to bargaining offer.");
            }
        });
    }

    // Fetch Messages via AJAX polling
    function fetchChatMessages(isFirstLoad) {
        if (!activeContactId || !activePropertyId) return;

        $.ajax({
            url: 'api/get_messages.php',
            type: 'GET',
            dataType: 'json',
            data: {
                contact_id: activeContactId,
                property_id: activePropertyId
            },
            success: function (res) {
                if (res.success) {
                    const messages = res.data;
                    const container = $('#chat-widget-messages');
                    const wasScrolledBottom = isChatScrolledToBottom();

                    container.empty();
                    if (messages.length === 0) {
                        container.html('<div class="text-center py-4 text-muted" style="font-size: 12px;"><i class="far fa-comments mr-2" style="font-size: 18px;"></i>Send a message to start bargaining and secure your PG!</div>');
                        return;
                    }

                    messages.forEach(function (msg) {
                        appendMessageBubble(msg, false);
                    });

                    if (isFirstLoad || wasScrolledBottom) {
                        scrollChatToBottom();
                    }
                }
            },
            error: function () {
                console.error("Error fetching message history.");
            }
        });
    }

    // Append Message Bubble HTML
    function appendMessageBubble(msg, isLocalAppend) {
        const container = $('#chat-widget-messages');
        const isSent = (parseInt(msg.sender_id) === window.userId);
        
        // Remove empty state message if present
        container.find('.text-center.py-4.text-muted').remove();

        // Check if message is a system log
        if (msg.offer_status === 0 && !msg.offer_amount && (msg.message.indexOf("Offer of ₹") !== -1 || msg.message.indexOf("Owner accepted") !== -1 || msg.message.indexOf("declined by the") !== -1 || msg.message.indexOf("has been accepted") !== -1)) {
            // Renders as system bubble
            container.append(`
                <div class="chat-message-row system">
                    <div class="chat-bubble shadow-sm">
                        <i class="fas fa-info-circle mr-1"></i>${msg.message}
                    </div>
                </div>
            `);
            return;
        }

        // Render checkmarks for read receipt
        let checkmarkHTML = '';
        if (isSent) {
            if (parseInt(msg.is_read) === 1) {
                checkmarkHTML = '<span class="read-receipt read" title="Read"><i class="fas fa-check-double"></i></span>';
            } else {
                checkmarkHTML = '<span class="read-receipt unread" title="Delivered"><i class="fas fa-check-double"></i></span>';
            }
        }

        // Format creation time
        const timeStr = formatMsgTime(msg.created_at);

        let bubbleContent = `<div>${escapeHtml(msg.message)}</div>`;

        // If message is a bargaining rent offer card
        if (msg.offer_amount) {
            const offerAmt = parseInt(msg.offer_amount);
            const offerStatus = parseInt(msg.offer_status);
            let statusTextBadge = '';
            let buttonsHTML = '';

            if (offerStatus === 1) { // Pending Offer
                statusTextBadge = '<span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i>Pending</span>';
                if (!isSent && window.userRole === 'owner') {
                    buttonsHTML = `
                        <div class="chat-offer-buttons">
                            <button class="offer-btn accept" data-message-id="${msg.id}" data-action="accept"><i class="fas fa-check mr-1"></i>Accept</button>
                            <button class="offer-btn decline" data-message-id="${msg.id}" data-action="decline"><i class="fas fa-times mr-1"></i>Decline</button>
                        </div>
                    `;
                }
            } else if (offerStatus === 2) { // Accepted
                statusTextBadge = '<span class="badge badge-success" style="background-color: #28a745; color: #fff;"><i class="fas fa-check-circle mr-1"></i>Accepted</span>';
            } else if (offerStatus === 3) { // Declined
                statusTextBadge = '<span class="badge badge-danger" style="background-color: #dc3545; color: #fff;"><i class="fas fa-times-circle mr-1"></i>Declined</span>';
            }

            bubbleContent = `
                <div class="chat-offer-card">
                    <div class="chat-offer-header">
                        <span><i class="fas fa-handshake mr-1"></i>Rent Offer</span>
                        ${statusTextBadge}
                    </div>
                    <div class="chat-offer-amount">₹ ${offerAmt.toLocaleString()}/mo</div>
                    <div style="font-size: 11px; color: #475569;" class="mb-2">${escapeHtml(msg.message)}</div>
                    ${buttonsHTML}
                </div>
            `;
        }

        const rowClass = isSent ? 'sent' : 'received';
        container.append(`
            <div class="chat-message-row ${rowClass}">
                <div class="chat-bubble shadow-sm">
                    ${bubbleContent}
                    <span class="chat-time-meta">
                        ${timeStr}${checkmarkHTML}
                    </span>
                </div>
            </div>
        `);
    }

    // Helper functions
    function formatMsgTime(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr.replace(/-/g, "/"));
            let hours = date.getHours();
            let minutes = date.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            return hours + ':' + minutes + ' ' + ampm;
        } catch(e) {
            return '';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function isChatScrolledToBottom() {
        const el = document.getElementById('chat-widget-messages');
        if (!el) return false;
        return el.scrollHeight - el.clientHeight <= el.scrollTop + 50;
    }

    function scrollChatToBottom() {
        const el = document.getElementById('chat-widget-messages');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    /* OWNER SPECIFIC: Conversations list polling for dashboard */
    if (window.userRole === 'owner' && $('#owner-chats-table-body').length > 0) {
        fetchOwnerConversations();
        conversationsInterval = setInterval(fetchOwnerConversations, 10000);

        // Open chat box when conversation row is clicked
        $(document).on('click', '.chat-thread-row', function () {
            const contactId = $(this).data('contact-id');
            const contactName = $(this).data('contact-name');
            const contactGender = $(this).data('contact-gender');
            const contactPic = $(this).data('contact-profile-pic');
            const propertyId = $(this).data('property-id');
            const propertyName = $(this).data('property-name');

            openChatBox(contactId, contactName, contactGender, contactPic, propertyId, propertyName);
        });
    }

    function fetchOwnerConversations() {
        $.ajax({
            url: 'api/get_conversations.php',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    const threads = res.data;
                    const tbody = $('#owner-chats-table-body');
                    const noChatsMsg = $('#no-owner-chats-message');

                    tbody.empty();
                    if (threads.length === 0) {
                        tbody.hide();
                        noChatsMsg.removeClass('d-none').show();
                        return;
                    }

                    noChatsMsg.hide();
                    tbody.show();

                    threads.forEach(function (thread) {
                        let unreadBadge = '';
                        if (thread.unread_count > 0) {
                            unreadBadge = `<span class="chat-unread-badge ml-2">${thread.unread_count}</span>`;
                        }

                        // Avatar Pic
                        let avatarSrc = 'img/man.png';
                        if (thread.contact_profile_pic) {
                            avatarSrc = thread.contact_profile_pic;
                        } else if (thread.contact_gender === 'female') {
                            avatarSrc = 'img/Female_icon.png';
                        }

                        tbody.append(`
                            <tr class="chat-thread-row" 
                                data-contact-id="${thread.contact_id}" 
                                data-contact-name="${escapeHtml(thread.contact_name)}"
                                data-contact-gender="${thread.contact_gender}"
                                data-contact-profile-pic="${thread.contact_profile_pic || ''}"
                                data-property-id="${thread.property_id}"
                                data-property-name="${escapeHtml(thread.property_name)}">
                                <td>
                                    <div class="font-weight-bold text-primary">${escapeHtml(thread.property_name)}</div>
                                    <small class="text-muted">ID: ${thread.property_id}</small>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <img src="${avatarSrc}" class="rounded-circle mr-2 border" style="width: 30px; height: 30px; object-fit: cover;" />
                                        <span class="font-weight-bold">${escapeHtml(thread.contact_name)}</span>
                                        ${unreadBadge}
                                    </div>
                                </td>
                                <td class="align-middle text-truncate" style="max-width: 250px;">
                                    ${escapeHtml(thread.last_message) || '<span class="text-muted font-italic">No message text</span>'}
                                </td>
                                <td class="align-middle text-muted" style="font-size: 11px;">
                                    ${formatMsgTime(thread.last_time) || 'N/A'}
                                </td>
                                <td class="align-middle">
                                    <button class="btn btn-primary btn-sm font-weight-bold px-3" style="border-radius: 20px;">
                                        <i class="fas fa-comments mr-1"></i>Open Chat
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
            },
            error: function () {
                console.error("Error fetching conversations.");
            }
        });
    }
});
